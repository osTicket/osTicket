<?php
/*********************************************************************
    class.migrater.php

    Migration utils required by upgrader.

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/


/*
    DatabaseMigrater.

    SQL database migrater. This provides the engine capable of rolling the
    database for an osTicket installation forward (and perhaps even
    backward) in time using a set of included migration scripts. Each script
    will roll the database between two database checkpoints. Where possible,
    the migrater will roll several checkpoint scripts into one to be applied
    together.

*/

class DatabaseMigrater {

    var $start;
    var $end;
    var $sqldir;

    function DatabaseMigrater($start, $end, $sqldir) {

        $this->start = $start;
        $this->end = $end;
        $this->sqldir = $sqldir;

    }

    function getPatches($stop=null) {

        $start= $this->start;
        $stop = $stop?$stop:$this->end;

        $patches = array();
        while (true) {
            $next = glob($this->sqldir . substr($start, 0, 8)
                         . '-*.patch.sql');
            if(!$next || empty($next)) {
                # No patches leaving the current signature.
                # Assume that we've applied all the available patches
                break;
            } elseif (count($next) == 1) {
                $patches[] = $next[0];
                $start = substr(basename($next[0]), 9, 8);
            } else {
                # Problem -- more than one patch exists from this snapshot.
                # We probably need a graph approach to solve this.
                break;
            }

            # Break if we've reached our target stop.
            if(!$start || !strncasecmp($start, $stop, 8))
                break;
        }

        return array_filter($patches);
    }

    /**
     * Reads update stream information from UPGRADE_DIR/<streams>/streams.cfg.
     * Each declared stream folder should contain a file under the name of the
     * stream with an 'sig' extension. The file will be the hash of the
     * signature of the tip of the stream. A corresponding config variable
     * 'schema_signature' should exist in the namespace of the stream itself.
     * If the hash file doesn't match the schema_signature on record, then an
     * update is triggered and the patches in the stream folder are used to
     * upgrade the database.
	 */
	/* static */
    function getUpgradeStreams($basedir) {
		static $streams = array();
        if ($streams) return $streams;

        // TODO: Make the hash algo configurable in the streams
        //       configuration ( core : md5 )
        $config = @file_get_contents($basedir.'/streams.cfg');
        if (!$config) $config = 'core';
        foreach (explode("\n", $config) as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line));
            if (!$line)
                continue;
            else if (file_exists($basedir."$line.sig") && is_dir($basedir.$line))
                $streams[$line] =
                    trim(file_get_contents($basedir."$line.sig"));
        }
        return $streams;
    }
}

class MigrationTask {
    var $description = "[Unnamed task]";
    var $status = "finished";

    /**
     * Function: run
     *
     * (Abstract) method which will perform the migration task. The task
     * does not have to be completed in one invocation; however, if the
     * run() method is called again, the task should not be restarted from the
     * beginning. The ::isFinished() method will be used to determine if the
     * migration task has more work to be done.
     *
     * If ::isFinished() returns boolean false, then the run method will be
     * called again. Note that the next invocation may be in a separate
     * request. Ensure that you properly capture the state of the task before
     * exiting the ::run() method. The entire MigrationTask instance is stored
     * in the migration session, so all instance variables will be preserved
     * between calls.
     *
     * Parameters:
     * max_time - (int) number of seconds the task should be allowed to run
     */
    /* abstract */
    function run($max_time) { }

    /**
     * Function: isFinished
     *
     * Returns boolean TRUE if another call to ::run() is required, and
     * false otherwise
     */
    /* abstract */
    function isFinished() { return true; }

    /**
     * Funciton: sleep
     *
     * Called if isFinished() returns false. The data returned is passed to
     * the ::wakeup() method before the ::run() method is called again
     */
    function sleep() { return null; }

    /**
     * Function: wakeup
     *
     * Called before the ::run() method if the migration task was saved in
     * the session and run in multiple requests
     */
    function wakeup($data) { }

    function getDescription() {
        return $this->description;
    }

    function getStatus() {
        return $this->status;
    }
    function setStatus($message) {
        $this->status = $message;
    }
}

?>
