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
        if ($state == 'done')
            $this->createUpgradedTicket();
    }

    function createUpgradedTicket() {
        global $cfg;

        $i18n = new Internationalization();
        $vars = $i18n->getTemplate('templates/ticket/upgraded.yaml')->getData();
        $vars['deptId'] = $cfg->getDefaultDeptId();

        //Create a ticket to make the system warm and happy.
        $errors = array();
        Ticket::create($vars, $errors, 'api', false, false);
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

    function __call($what, $args) {
        if ($this->getCurrentStream()) {
            $callable = array($this->getCurrentStream(), $what);
            if (!is_callable($callable))
                throw new Exception('InternalError: Upgrader method not callable: '
                    . $what);
            return call_user_func_array($callable, $args);
        }
    }

    function getTask() {
        if($this->getCurrentStream())
            return $this->getCurrentStream()->getTask();
    }

    function doTask() {
        return $this->getCurrentStream()->doTask();
    }

    function getErrors() {
        if ($this->getCurrentStream())
            return $this->getCurrentStream()->getErrors();
    }

    function getUpgradeSummary() {
        if ($this->getCurrentStream())
            return $this->getCurrentStream()->getUpgradeSummary();
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

    var $phash;

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
            $_SESSION['ost_upgrader']['task'] = array();

        //Tasks to perform - saved on the session.
        $this->phash = &$_SESSION['ost_upgrader']['phash'];

        //Database migrater
        $this->migrater = null;
    }

    function check_prereq() {
        return (parent::check_prereq() && $this->check_mysql_version());
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
        return !($this->getNextPatch() || $this->getPendingTask());
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

    function getUpgradeSummary() {
        $summary = '';
        foreach ($this->getPatches() as $p) {
            $info = $this->readPatchInfo($p);
            $summary .= '<div class="patch">' . $info['version'];
            if (isset($info['title']))
                $summary .= ': <span class="patch-title">'.$info['title']
                    .'</span>';
            $summary .= '</div>';
        }
        return $summary;
    }

    function getNextAction() {

        $action='Upgrade osTicket to '.$this->getVersion();
        if($task=$this->getTask()) {
            $action = $task->getDescription() .' ('.$task->getStatus().')';
        } elseif($this->isUpgradable() && ($nextversion = $this->getNextVersion())) {
            $action = "Upgrade to $nextversion";
        }

        return '['.$this->name.'] '.$action;
    }

    function getPendingTask() {

        $pending=array();
        if (($task=$this->getTask()) && ($task instanceof MigrationTask))
            return ($task->isFinished()) ? 1 : 0;

        return false;
    }

    function getTask() {
        global $ost;

        $task_file = $this->getSQLDir() . "{$this->phash}.task.php";
        if (!file_exists($task_file))
            return null;

        if (!isset($this->task)) {
            $class = (include $task_file);
            if (!is_string($class) || !class_exists($class))
                return $ost->logError("Bogus migration task", "{$this->phash}:{$class}") ;
            $this->task = new $class();
            if (isset($_SESSION['ost_upgrader']['task'][$this->phash]))
                $this->task->wakeup($_SESSION['ost_upgrader']['task'][$this->phash]);
        }
        return $this->task;
    }

    function doTask() {

        if(!($task = $this->getTask()))
            return false; //Nothing to do.

        $this->log(
                sprintf('Upgrader - %s (task pending).', $this->getShash()),
                sprintf('The %s task reports there is work to do',
                    get_class($task))
                );
        if(!($max_time = ini_get('max_execution_time')))
            $max_time = 30; //Default to 30 sec batches.

        $task->run($max_time);
        if (!$task->isFinished()) {
            $_SESSION['ost_upgrader']['task'][$this->phash] = $task->sleep();
            return true;
        }
        // Run the cleanup script, if any, and destroy the task's session
        // data
        $this->cleanup();
        unset($_SESSION['ost_upgrader']['task'][$this->phash]);
        $this->phash = null;
        unset($this->task);
        return false;
    }

    function upgrade() {
        global $ost;

        if($this->getPendingTask() || !($patches=$this->getPatches()))
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

            $this->log("Upgrader - $shash applied", $logMsg);
            $this->signature = $shash; //Update signature to the *new* HEAD
            $this->phash = $phash;

            //Break IF elapsed time is greater than 80% max time allowed.
            if (!($task=$this->getTask())) {
                $this->cleanup();
                if (($elapsedtime=(Misc::micro_time()-$start_time))
                        && $max_time && $elapsedtime>($max_time*0.80))
                    break;
                else
                    // Apply the next patch
                    continue;
            }

            //We have work to do... set the tasks and break.
            $_SESSION['ost_upgrader'][$shash]['state'] = 'upgrade';
            break;
        }

        //Reset the migrater
        $this->migrater = null;

        return true;
    }

    function log($title, $message, $level=LOG_DEBUG) {
        global $ost;
        // Never alert the admin, and force the write to the database
        $ost->log($level, $title, $message, false, true);
    }

    /************* TASKS **********************/
    function cleanup() {
        $file = $this->getSQLDir().$this->phash.'.cleanup.sql';

        if(!file_exists($file)) //No cleanup script.
            return 0;

        //We have a cleanup script  ::XXX: Don't abort on error?
        if($this->load_sql_file($file, $this->getTablePrefix(), false, true)) {
            $this->log("Upgrader - {$this->phash} cleanup",
                "Applied cleanup script {$file}");
            return 0;
        }

        $this->log('Upgrader', sprintf("%s: Unable to process cleanup file",
                        $this->phash));
        return 0;
    }
}
?>
