<?php
/*********************************************************************
    upgrade.php

    osTicket Upgrade Wizard

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
function staffLoginPage($msg) {
        
    $_SESSION['_staff']['auth']['dest']=THISPAGE;
    $_SESSION['_staff']['auth']['msg']=$msg;
    header('Location: ../scp/login.php');
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

//$_SESSION['ost_upgrader']=null;
$upgrader = new Upgrader($cfg->getSchemaSignature(), TABLE_PREFIX, SQL_DIR);


$wizard=array();
$wizard['title']='osTicket Upgrade Wizard';
$wizard['tagline']='Upgrading osTicket to v'.$upgrader->getVersionVerbose();
$wizard['logo']='logo-upgrade.png';
$wizard['menu']=array('Upgrade Guide'=>'http://osticket.com/wiki/Upgrade_and_Migration',
                      'Get Professional Help'=>'http://osticket.com/support');
$errors=array();
if($_POST && $_POST['s'] && !$upgrader->isAborted()) {
    switch(strtolower($_POST['s'])) {
        case 'prereq':
            //XXX: check if it's upgradable version??
            if(!$cfg->isUpgradePending())
                $errors['err']=' Nothing to do! System already upgraded to the current version';
            elseif(!$upgrader->isUpgradable())
                $errors['err']='The upgrader does NOT support upgrading from the current vesion!';
            elseif($upgrader->check_prereq())
                $upgrader->setState('upgrade');
            else
                $errors['prereq']='Minimum requirements not met!';
            break;
        case 'upgrade': //Manual upgrade.... when JS (ajax) is not supported.
            if($upgrader->getNumPendingTasks()) {
                $upgrader->doTasks();
            } elseif($cfg->isUpgradePending() && $upgrader->isUpgradable()) {
                $upgrader->upgrade();
            } elseif(!$cfg->isUpgradePending()) {
                $upgrader->setState('done');
            }

            if(($errors=$upgrader->getErrors()))  {
                $upgrader->setState('aborted');
            }
            break;
        default:
            $errors['err']='Unknown action!';
    }
}

switch(strtolower($upgrader->getState())) {
    case 'aborted':
        $inc='upgrade-aborted.inc.php';
        break;
    case 'upgrade':
        $inc='upgrade.inc.php';
        break;
    case 'done':
        $inc='upgrade-done.inc.php';
        break;
    default:
        $inc='upgrade-prereq.inc.php';
        if($upgrader->isAborted())
            $inc='upgrade-aborted.inc.php';
        elseif(!$cfg->isUpgradePending())
            $errors['err']='Nothing to do! System already upgraded to the latest version';
        elseif(!$upgrader->isUpgradable())
            $errors['err']='The upgrader does NOT support upgrading from the current vesion!';
}

require(INC_DIR.'header.inc.php');
require(INC_DIR.$inc);
require(INC_DIR.'footer.inc.php');
?>
