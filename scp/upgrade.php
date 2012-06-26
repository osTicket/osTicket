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
require_once 'admin.inc.php';
require_once INCLUDE_DIR.'class.upgrader.php';

//$_SESSION['ost_upgrader']=null;
$upgrader = new Upgrader($cfg->getSchemaSignature(), TABLE_PREFIX, PATCH_DIR);

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
            if(!$ost->isUpgradePending())
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
            } elseif($ost->isUpgradePending() && $upgrader->isUpgradable()) {
                $upgrader->upgrade();
            } elseif(!$ost->isUpgradePending()) {
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
        $inc='aborted.inc.php';
        break;
    case 'upgrade':
        $inc='upgrade.inc.php';
        break;
    case 'done':
        $inc='done.inc.php';
        break;
    default:
        $inc='upgrade.inc.php';
        if($upgrader->isAborted())
            $inc='aborted.inc.php';
        elseif(!$ost->isUpgradePending())
            $errors['err']='Nothing to do! System already upgraded to <b>'.$ost->getVersion().'</b> with no pending patches to apply.';
        elseif(!$upgrader->isUpgradable())
            $errors['err']='The upgrader does NOT support upgrading from the current vesion!';
}

$nav->setTabActive('dashboard');
$nav->addSubMenu(array('desc'=>'Upgrader',
                           'title'=>'Upgrader',
                           'href'=>'upgrade.php',
                           'iconclass'=>'preferences'),
                        true);
$ost->addExtraHeader('<script type="text/javascript" src="./js/upgrader.js"></script>');
require(STAFFINC_DIR.'header.inc.php');
require(UPGRADE_DIR.$inc);
require(STAFFINC_DIR.'footer.inc.php');
?>
