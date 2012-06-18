<?php
/*********************************************************************
    upgrader.php

    osTicket Upgrader Helper - called via ajax.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
function staffLoginPage($msg) {
    Http::response(403, $msg?$msg:'Access Denied');
    exit;
}

require '../scp/staff.inc.php';
if(!$thisstaff or !$thisstaff->isAdmin()) {
    staffLoginPage('Admin Access Required!');
    exit;
}

define('SETUPINC', true);
define('INC_DIR', './inc/');
define('SQL_DIR', INC_DIR.'sql/');

require_once INC_DIR.'class.upgrader.php';


$upgrader = new Upgrader($cfg->getSchemaSignature(), TABLE_PREFIX, SQL_DIR);

//Just report the next action on the first call.
if(!$_SESSION['ost_upgrader'][$upgrader->getShash()]['progress']) {
    $_SESSION['ost_upgrader'][$upgrader->getShash()]['progress'] = $upgrader->getNextAction();
    Http::response(200, $upgrader->getNextAction());
    exit;
}

if($upgrader->getNumPendingTasks()) {
    if($upgrader->doTasks() && !$upgrader->getNumPendingTasks() && $ost->isUpgradePending()) {
        //Just reporting done...with tasks - break in between patches!
        header("HTTP/1.1 304 Not Modified");
        exit;
    }
} elseif($ost->isUpgradePending() && $upgrader->isUpgradable()) {
    $version = $upgrader->getNextVersion();
    if($upgrader->upgrade()) {
        //We're simply reporting progress here - call back will report next action'
        Http::response(200, "Upgraded to $version ... post-upgrade checks!");
        exit;
    }
} elseif(!$ost->isUpgradePending()) {
    $upgrader->setState('done');
    session_write_close();
    header("HTTP/1.1 304 Not Modified");
    exit;
}

if($upgrader->isAborted() || $upgrader->getErrors()) {
    Http::response(416, "We have a problem ... wait a sec.");
    exit;
}

Http::response(200, $upgrader->getNextAction());
?>
