<?php
/*********************************************************************
    cron.php

    Auto-cron handle.
    File requested as 1X1 image on the footer of every staff's page

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
define('AJAX_REQUEST', 1);
require('staff.inc.php');
ignore_user_abort(1);//Leave me a lone bro!
@set_time_limit(0); //useless when safe_mode is on
$data=sprintf ("%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c",
        71,73,70,56,57,97,1,0,1,0,128,255,0,192,192,192,0,0,0,33,249,4,1,0,0,0,0,44,0,0,0,0,1,0,1,0,0,2,2,68,1,0,59);

// Flush the gif image
Http::flush(201, $data, 'image/gif');

// Keep the image output clean. Hide our dirt.
ob_start();
//TODO: Make cron DB based to allow for better time limits. Direct calls for now sucks big time.
//We DON'T want to spawn cron on every page load...we record the lastcroncall on the session per user
$sec=time()-$_SESSION['lastcroncall'];
$caller = $thisstaff->getUserName();

// Agent can call cron once every 3 minutes.
if ($sec < 180 || !$ost || $ost->isUpgradePending())
    return ob_end_clean();

require_once(INCLUDE_DIR.'class.cron.php');

// Run tickets count every 3rd run or so... force new count by skipping cached
// results
if ((mt_rand(1, 12) % 3) == 0)
    SavedQueue::counts($thisstaff, false);

// Clear staff obj to avoid false credit internal notes & auto-assignment
$thisstaff = null;

// Release the session to prevent locking a future request while this is
// running
$_SESSION['lastcroncall'] = time();
session_write_close();

// Age tickets: We're going to age tickets regardless of cron settings.
Cron::TicketMonitor();

// Run file purging about every 20 cron runs (1h40 on a five minute cron)
if (mt_rand(1, 20) == 4)
    Cron::CleanOrphanedFiles();

if($cfg && $cfg->isAutoCronEnabled()) { //ONLY fetch tickets if autocron is enabled!
    Cron::MailFetcher();  //Fetch mail.
    $ost->logDebug(_S('Auto Cron'), sprintf(_S('Mail fetcher cron call [%s]'), $caller));
}

$data = array('autocron'=>true);
Signal::send('cron', null, $data);

ob_end_clean();
?>
