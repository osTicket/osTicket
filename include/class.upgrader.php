<?php
/*********************************************************************
    class.upgrader.php

    osTicket Upgrader

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require_once INCLUDE_DIR.'class.setup.php';
require_once INCLUDE_DIR.'class.migrater.php';

class Upgrader {
    function Upgrader($prefix, $basedir) {
        global $ost;

        $this->streams = array();
        foreach (DatabaseMigrater::getUpgradeStreams($basedir) as $stream=>$hash) {
            $signature = $ost->getConfig()->getSchemaSignature($stream);
            $this->streams[$stream] = new StreamUpgrader($signature, $hash, $stream,
                $prefix, $basedir.$stream.'/', $this);
        }

        //Init persistent state of upgrade.
        $this->state = &$_SESSION['ost_upgrader']['state'];

        $this->mode = &$_SESSION['ost_upgrader']['mode'];

        $this->current = &$_SESSION['ost_upgrader']['stream'];
        if (!$this->current || $this->getCurrentStream()->isFinished()) {
            $streams = array_keys($this->streams);
            do {
                $this->current = array_shift($streams);
            } while ($this->current && $this->getCurrentStream()->isFinished());
        }
    }

    function getCurrentStream() {
        return $this->streams[$this->current];
    }

    function isUpgradable() {
        if ($this->isAborted())
            return false;

        foreach ($this->streams as $s)
            if (!$s->isUpgradable())
                return false;

        return true;
    }

    function isAborted() {
        return !strcasecmp($this->getState(), 'aborted');
    }

    function abort($msg, $debug=false) {
        if ($this->getCurrentStream())
            $this->getCurrentStream()->abort($msg, $debug);
    }

    function getState() {
        return $this->state;
    }

    function setState($state) {
        $this->state = $state;
    }

    function getMode() {
        return $this->mode;
    }

    function setMode($mode) {
        $this->mode = $mode;
    }

    function upgrade() {
        if (!$this->current)
            return true;

        return $this->getCurrentStream()->upgrade();
    }

    function check_prereq() {
        if ($this->getCurrentStream())
            return $this->getCurrentStream()->check_prereq();
    }
    function check_php() {
        if ($this->getCurrentStream())
            return $this->getCurrentStream()->check_php();
    }
    function check_mysql() {
        if ($this->getCurrentStream())
            return $this->getCurrentStream()->check_mysql();
    }

    function getNumPendingTasks() {
        if ($this->getCurrentStream())
            return $this->getCurrentStream()->getNumPendingTasks();
    }

    function doTasks() {
        if ($this->getNumPendingTasks())
            return $this->getCurrentStream()->doTasks();
    }

    function getErrors() {
        if ($this->getCurrentStream())
            return $this->getCurrentStream()->getError();
    }

    function getNextAction() {
        if ($this->getCurrentStream())
            return $this->getCurrentStream()->getNextAction();
    }

    function getNextVersion() {
        return $this->getCurrentStream()->getNextVersion();
    }

    function getSchemaSignature() {
        if ($this->getCurrentStream())
            return $this->getCurrentStream()->getSchemaSignature();
    }

    function getSHash() {
        if ($this->getCurrentStream())
            return $this->getCurrentStream()->getSHash();
    }
}

/**
 * Updates a single database stream. In the classical sense, osTicket only
 * maintained a single database update stream. In that model, this
 * represents upgrading that single stream. In multi-stream mode,
 * customizations and plugins are supported to have their own respective
 * database update streams. The Upgrader class is used to coordinate updates
 * for all the streams, whereas the work to upgrade each stream is done in
 * this class
 */
class StreamUpgrader extends SetupWizard {

    var $prefix;
    var $sqldir;
    var $signature;

    var $state;
    var $mode;

    /**
     * Parameters:
     * schema_signature - (string<hash-hex>) Current database-reflected (via
     *      config table) version of the stream
     * target - (stream<hash-hex>) Current stream tip, as reflected by
     *      streams/<stream>.sig
     * stream - (string) Name of the stream (folder)
     * prefix - (string) Database table prefix
     * sqldir - (string<path>) Path of sql patches
     * upgrader - (Upgrader) Parent coordinator of parallel stream updates
     */
    function StreamUpgrader($schema_signature, $target, $stream, $prefix, $sqldir, $upgrader) {

        $this->signature = $schema_signature;
        $this->target = $target;
        $this->prefix = $prefix;
        $this->sqldir = $sqldir;
        $this->errors = array();
        $this->mode = 'ajax'; //
        $this->upgrader = $upgrader;
        $this->name = $stream;

        //Disable time limit if - safe mode is set.
        if(!ini_get('safe_mode'))
            set_time_limit(0);


        //Init the task Manager.
        if(!isset($_SESSION['ost_upgrader'][$this->getShash()]))
            $_SESSION['ost_upgrader'][$this->getShash()]['tasks']=array();

        //Tasks to perform - saved on the session.
        $this->tasks = &$_SESSION['ost_upgrader'][$this->getShash()]['tasks'];

        //Database migrater
        $this->migrater = null;
    }

    function onError($error) {
        global $ost, $thisstaff;

        $subject = '['.$this->name.']: Upgrader Error';
        $ost->logError($subject, $error);
        $this->setError($error);
        $this->upgrader->setState('aborted');

        //Alert staff upgrading the system - if the email is not same as admin's
        // admin gets alerted on error log (above)
        if(!$thisstaff || !strcasecmp($thisstaff->getEmail(), $ost->getConfig()->getAdminEmail()))
            return;

        $email=null;
        if(!($email=$ost->getConfig()->getAlertEmail()))
            $email=$ost->getConfig()->getDefaultEmail(); //will take the default email.

        if($email) {
            $email->sendAlert($thisstaff->getEmail(), $subject, $error);
        } else {//no luck - try the system mail.
            Mailer::sendmail($thisstaff->getEmail(), $subject, $error, sprintf('"osTicket Alerts"<%s>', $thisstaff->getEmail()));
        }

    }

    function isUpgradable() {
        return $this->getNextPatch();
    }

    function getSchemaSignature() {
        return $this->signature;
    }

    function getShash() {
        return  substr($this->getSchemaSignature(), 0, 8);
    }

    function getTablePrefix() {
        return $this->prefix;
    }

    function getSQLDir() {
        return $this->sqldir;
    }

    function getMigrater() {
        if(!$this->migrater)
            $this->migrater = new DatabaseMigrater($this->signature, $this->target, $this->sqldir);

        return  $this->migrater;
    }

    function getPatches() {
        $patches = array();
        if($this->getMigrater())
            $patches = $this->getMigrater()->getPatches();

        return $patches;
    }

    function getNextPatch() {
        return (($p=$this->getPatches()) && count($p)) ? $p[0] : false;
    }

    function getNextVersion() {
        if(!$patch=$this->getNextPatch())
            return '(Latest)';

        $info = $this->readPatchInfo($patch);
        return $info['version'];
    }

    function isFinished() {
        # TODO: 1. Check if current and target hashes match,
        #       2. Any pending tasks
        return !($this->getNextPatch() || $this->getPendingTasks());
    }

    function readPatchInfo($patch) {
        $info = $matches = $matches2 = array();
        if (preg_match(':/\*\*(.*)\*/:s', file_get_contents($patch), $matches)) {
            if (preg_match_all('/@([\w\d_-]+)\s+(.*)$/m', $matches[0],
                        $matches2, PREG_SET_ORDER))
                foreach ($matches2 as $match)
                    $info[$match[1]] = $match[2];
        }
        if (!isset($info['version']))
            $info['version'] = substr(basename($patch), 9, 8);
        return $info;
    }

    function getNextAction() {

        $action='Upgrade osTicket to '.$this->getVersion();
        if($this->getNumPendingTasks() && ($task=$this->getNextTask())) {
            $action = $task['desc'];
            if($task['status']) //Progress report...
                $action.=' ('.$task['status'].')';
        } elseif($this->isUpgradable() && ($nextversion = $this->getNextVersion())) {
            $action = "Upgrade to $nextversion";
        }

        return '['.$this->name.'] '.$action;
    }

    function getNumPendingTasks() {

        return count($this->getPendingTasks());
    }

    function getPendingTasks() {

        $pending=array();
        if(($tasks=$this->getTasks())) {
            foreach($tasks as $k => $task) {
                if(!$task['done'])
                    $pending[$k] = $task;
            }
        }

        return $pending;
    }

    function getTasks() {
       return $this->tasks;
    }

    function getNextTask() {

        if(!($tasks=$this->getPendingTasks()))
            return null;

        return current($tasks);
    }

    function removeTask($tId) {

        if(isset($this->tasks[$tId]))
            unset($this->tasks[$tId]);

        return (!$this->tasks[$tId]);
    }

    function setTaskStatus($tId, $status) {
        if(isset($this->tasks[$tId]))
            $this->tasks[$tId]['status'] = $status;
    }

    function doTasks() {

        global $ost;
        if(!($tasks=$this->getPendingTasks()))
            return true; //Nothing to do.

        $c = count($tasks);
        $ost->logDebug(
                sprintf('Upgrader - %s (%d pending tasks).', $this->getShash(), $c),
                sprintf('There are %d pending upgrade tasks for %s patch', $c, $this->getShash())
                );
        $start_time = Misc::micro_time();
        foreach($tasks as $k => $task) {
            //TODO: check time used vs. max execution - break if need be
            if(call_user_func(array($this, $task['func']), $k)===0) {
                $this->tasks[$k]['done'] = true;
            } else { //Task has pending items to process.
                break;
            }
        }

        return $this->getPendingTasks();
    }

    function upgrade() {
        global $ost;

        if($this->getPendingTasks() || !($patches=$this->getPatches()))
            return false;

        $start_time = Misc::micro_time();
        if(!($max_time = ini_get('max_execution_time')))
            $max_time = 300; //Apache/IIS defaults.

        // Apply up to five patches at a time
        foreach (array_slice($patches, 0, 5) as $patch) {
            //TODO: check time used vs. max execution - break if need be
            if (!$this->load_sql_file($patch, $this->getTablePrefix()))
                return false;

            //clear previous patch info -
            unset($_SESSION['ost_upgrader'][$this->getShash()]);

            $phash = substr(basename($patch), 0, 17);
            $shash = substr($phash, 9, 8);

            //Log the patch info
            $logMsg = "Patch $phash applied successfully ";
            if(($info = $this->readPatchInfo($patch)) && $info['version'])
                $logMsg.= ' ('.$info['version'].') ';

            $ost->logDebug("Upgrader - $shash applied", $logMsg);
            $this->signature = $shash; //Update signature to the *new* HEAD

            //Check if the said patch has scripted tasks
            if(!($tasks=$this->getTasksForPatch($phash))) {
                //Break IF elapsed time is greater than 80% max time allowed.
                if(($elapsedtime=(Misc::micro_time()-$start_time)) && $max_time && $elapsedtime>($max_time*0.80))
                    break;

                continue;

            }

            //We have work to do... set the tasks and break.
            $_SESSION['ost_upgrader'][$shash]['tasks'] = $tasks;
            $_SESSION['ost_upgrader'][$shash]['state'] = 'upgrade';
            break;
        }

        //Reset the migrater
        $this->migrater = null;

        return true;

    }

    function getTasksForPatch($phash) {

        $tasks=array();
        switch($phash) { //Add  patch specific scripted tasks.
            case 'c00511c7-7be60a84': //V1.6 ST- 1.7 * {{MD5('1.6 ST') -> c00511c7c1db65c0cfad04b4842afc57}}
                $tasks[] = array('func' => 'migrateSessionFile2DB',
                                 'desc' => 'Transitioning to db-backed sessions');
                break;
            case '98ae1ed2-e342f869': //v1.6 RC1-4 -> v1.6 RC5
                $tasks[] = array('func' => 'migrateAPIKeys',
                                 'desc' => 'Migrating API keys to a new table');
                break;
            case '435c62c3-2e7531a2':
                $tasks[] = array('func' => 'migrateGroupDeptAccess',
                                 'desc' => 'Migrating group\'s department access to a new table');
                break;
            case '15b30765-dd0022fb':
                $tasks[] = array('func' => 'migrateAttachments2DB',
                                 'desc' => 'Migrating attachments to database, it might take a while depending on the number of files.');
                break;
        }

        //Check IF SQL cleanup exists.
        $file=$this->getSQLDir().$phash.'.cleanup.sql';
        if(file_exists($file))
            $tasks[] = array('func' => 'cleanup',
                             'desc' => 'Post-upgrade cleanup!',
                             'phash' => $phash);

        return $tasks;
    }

    /************* TASKS **********************/
    function cleanup($taskId) {
        global $ost;

        $phash = $this->tasks[$taskId]['phash'];
        $file=$this->getSQLDir().$phash.'.cleanup.sql';

        if(!file_exists($file)) //No cleanup script.
            return 0;

        //We have a cleanup script  ::XXX: Don't abort on error?
        if($this->load_sql_file($file, $this->getTablePrefix(), false, true))
            return 0;

        $ost->logDebug('Upgrader', sprintf("%s: Unable to process cleanup file",
                        $phash));
        return 0;
    }

    function migrateAttachments2DB($taskId) {
        global $ost;

        if(!($max_time = ini_get('max_execution_time')))
            $max_time = 30; //Default to 30 sec batches.

        $att_migrater = new AttachmentMigrater();
        if($att_migrater->do_batch(($max_time*0.9), 100)===0)
            return 0;

        return $att_migrater->getQueueLength();
    }

    function migrateSessionFile2DB($taskId) {
        # How about 'dis for a hack?
        osTicketSession::write(session_id(), session_encode());
        return 0;
    }

    function migrateAPIKeys($taskId) {

        $res = db_query('SELECT api_whitelist, api_key FROM '.CONFIG_TABLE.' WHERE id=1');
        if(!$res || !db_num_rows($res))
            return 0;  //Reporting success.

        list($whitelist, $key) = db_fetch_row($res);

        $ips=array_filter(array_map('trim', explode(',', $whitelist)));
        foreach($ips as $ip) {
            $sql='INSERT INTO '.API_KEY_TABLE.' SET created=NOW(), updated=NOW(), isactive=1 '
                .',ipaddr='.db_input($ip)
                .',apikey='.db_input(strtoupper(md5($ip.md5($key))));
            db_query($sql);
        }

        return 0;
    }

    function migrateGroupDeptAccess($taskId) {

        $res = db_query('SELECT group_id, dept_access FROM '.GROUP_TABLE);
        if(!$res || !db_num_rows($res))
            return 0;  //No groups??

        while(list($groupId, $access) = db_fetch_row($res)) {
            $depts=array_filter(array_map('trim', explode(',', $access)));
            foreach($depts as $deptId) {
                $sql='INSERT INTO '.GROUP_DEPT_TABLE
                    .' SET dept_id='.db_input($deptId).', group_id='.db_input($groupId);
                db_query($sql);
            }
        }

        return 0;



    }
}
?>
