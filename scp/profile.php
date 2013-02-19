<?php
/*********************************************************************
    profile.php

    Staff's profile handle

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require_once('staff.inc.php');
$msg='';
$staff=Staff::lookup($thisstaff->getId());
if($_POST && $_POST['id']!=$thisstaff->getId()) { //Check dummy ID used on the form.
 $errors['err']='Internal Error. Action Denied';
} elseif(!$errors && $_POST) { //Handle post

    if(!$staff)
        $errors['err']='Unknown or invalid staff';
    elseif($staff->updateProfile($_POST,$errors)){
        $msg='Profile updated successfully';
        $thisstaff->reload();
        $staff->reload();
        $_SESSION['TZ_OFFSET']=$thisstaff->getTZoffset();
        $_SESSION['TZ_DST']=$thisstaff->observeDaylight();
    }elseif(!$errors['err'])
        $errors['err']='Profile update error. Try correcting the errors below and try again!';
}

//Forced password Change.
if($thisstaff->forcePasswdChange() && !$errors['err'])
    $errors['err']=sprintf('<b>Hi %s</b> - You must change your password to continue!',$thisstaff->getFirstName());
elseif($thisstaff->onVacation() && !$warn)
    $warn=sprintf('<b>Welcome back %s</b>! You are listed as \'on vacation\' Please let your manager know that you are back.',$thisstaff->getFirstName());

$inc='profile.inc.php';
$nav->setTabActive('dashboard');
require_once(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$inc);
require_once(STAFFINC_DIR.'footer.inc.php');
?>
