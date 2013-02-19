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

class Upgrader extends SetupWizard {

    var $prefix;
    var $sqldir;
    var $signature;

    function Upgrader($signature, $prefix, $sqldir) {

        $this->signature = $signature;
        $this->shash = substr($signature, 0, 8);
        $this->prefix = $prefix;
        $this->sqldir = $sqldir;
        $this->errors = array();

        //Disable time limit if - safe mode is set.
        if(!ini_get('safe_mode'))
            set_time_limit(0);

        //Init persistent state of upgrade.
        $this->state = &$_SESSION['ost_upgrader']['state'];

        //Init the task Manager.
        if(!isset($_SESSION['ost_upgrader'][$this->getShash()]))
            $_SESSION['ost_upgrader'][$this->getShash()]['tasks']=array();

        //Tasks to perform - saved on the session.
        $this->tasks = &$_SESSION['ost_upgrader'][$this->getShash()]['tasks'];

        //Database migrater 
        $this->migrater = new DatabaseMigrater($this->signature, SCHEMA_SIGNATURE, $this->sqldir);
    }

    function onError($error) {
        global $ost, $thisstaff;

        $ost->logError('Upgrader Error', $error);
        $this->setError($error);
        $this->setState('aborted');

        //Alert staff upgrading the system - if the email is not same as admin's
        // admin gets alerted on error log (above)
        if(!$thisstaff || !strcasecmp($thisstaff->getEmail(), $ost->getConfig()->getAdminEmail()))
            return;

        $email=null;
        if(!($email=$ost->getConfig()->getAlertEmail()))
            $email=$ost->getConfig()->getDefaultEmail(); //will take the default email.

        $subject = 'Upgrader Error';
        if($email) {
            $email->sendAlert($thisstaff->getEmail(), $subject, $error);
        } else {//no luck - try the system mail.
            Mailer::sendmail($thisstaff->getEmail(), $subject, $error, sprintf('"osTicket Alerts"<%s>', $thisstaff->getEmail()));
        }

    }

    function isUpgradable() {
        return (!$this->isAborted() && $this->getNextPatch());
    }

    function isAborted() {
        return !strcasecmp($this->getState(), 'aborted');
    }

    function getSchemaSignature() {
        return $this->signature;
    }

    function getShash() {
        return $this->shash;
    }

    function getTablePrefix() {
        return $this->prefix;
    }

    function getSQLDir() {
        return $this->sqldir;
    }

    function getState() {
        return $this->state;
    }

    function setState($state) {
        $this->state = $state;
    }

    function getPatches() {
        return $this->migrater->getPatches();
    }

    function getNextPatch() {
        $p = $this->getPatches();
        return (count($p)) ? $p[0] : false;
    }

    function getNextVersion() {
        if(!$patch=$this->getNextPatch())
            return '(Latest)';

        $info = $this->readPatchInfo($patch);
        return $info['version'];
    }

    function readPatchInfo($patch) {
        $info = array();
        if (preg_match('/\*(.*)\*/', file_get_contents($patch), $matches)) {
            if (preg_match('/@([\w\d_-]+)\s+(.*)$/', $matches[0], $matches2))
                foreach ($matches2 as $match)
                    $info[$match[0]] = $match[1];
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

        return $action;
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

        $ost->logDebug('Upgrader', sprintf('There are %d pending upgrade tasks', count($tasks)));
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

        foreach ($patches as $patch) {
            //TODO: check time used vs. max execution - break if need be
            if (!$this->load_sql_file($patch, $this->getTablePrefix()))
                return false;

            //clear previous patch info - 
            unset($_SESSION['ost_upgrader'][$this->getShash()]);

            $phash = substr(basename($patch), 0, 17);

            //Log the patch info
            $logMsg = "Patch $phash applied ";
            if(($info = $this->readPatchInfo($patch)) && $info['version'])
                $logMsg.= ' ('.$info['version'].') ';

            $ost->logDebug('Upgrader - Patch applied', $logMsg);
            
            //Check if the said patch has scripted tasks
            if(!($tasks=$this->getTasksForPatch($phash))) {
                //Break IF elapsed time is greater than 80% max time allowed.
                if(($elapsedtime=(Misc::micro_time()-$start_time)) && $max_time && $elapsedtime>($max_time*0.80))
                    break;

                continue;

            }

            //We have work to do... set the tasks and break.
            $shash = substr($phash, 9, 8);
            $_SESSION['ost_upgrader'][$shash]['tasks'] = $tasks;
            $_SESSION['ost_upgrader'][$shash]['state'] = 'upgrade';
            
            $ost->logDebug('Upgrader', sprintf('Found %d tasks to be executed for %s',
                            count($tasks), $shash));
            break;

        }

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
