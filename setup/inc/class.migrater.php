<?php
/*********************************************************************
    class.migrater.php

    SQL database migrater. This provides the engine capable of rolling the
    database for an osTicket installation forward (and perhaps even
    backward) in time using a set of included migration scripts. Each script
    will roll the database between two database checkpoints. Where possible,
    the migrater will roll several checkpoint scripts into one to be applied
    together.

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

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
            if (count($next) == 1) {
                $patches[] = $next[0];
                $start = substr(basename($next[0]), 9, 8);
            } elseif (count($next) == 0) {
                # There are no patches leaving the current signature. We
                # have to assume that we've applied all the available
                # patches.
                break;
            } else {
                # Problem -- more than one patch exists from this snapshot.
                # We probably need a graph approach to solve this.
                break;
            }

            # Break if we've reached our target stop.
            if(!$start || !strncasecmp($start, $stop, 8))
                break;
        }

        return $patches;
    }
}
?>
