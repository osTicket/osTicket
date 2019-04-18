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
require_once(INCLUDE_DIR.'class.export.php');       // For paper sizes

$msg='';
$staff=Staff::lookup($thisstaff->getId());
if($_POST && $_POST['id']!=$thisstaff->getId()) { //Check dummy ID used on the form.
 $errors['err']=__('Action Denied.')
        .' '.__('Internal error occurred');
} elseif(!$errors && $_POST) { //Handle post

    if(!$staff)
        $errors['err']=sprintf(__('%s: Unknown or invalid'), __('agent'));
    elseif($staff->updateProfile($_POST,$errors)){
        $msg=__('Profile updated successfully');
    }elseif(!$errors['err'])
        $errors['err'] = sprintf('%s %s',
            __('Profile update error.'),
            __('Correct any errors below and try again.'));
}

//Forced password Change.
if($thisstaff->forcePasswdChange() && !$errors['err'])
    $errors['err'] = str_replace(
        '<a>',
        sprintf('<a data-dialog="ajax.php/staff/%d/change-password" href="#">', $thisstaff->getId()),
        sprintf(
            __('<b>Hi %s</b> - You must <a>change your password to continue</a>!'),
            $thisstaff->getFirstName()
        )
    );
elseif($thisstaff->onVacation() && !$warn)
    $warn=sprintf(__("<b>Welcome back %s</b>! You are listed as 'on vacation' Please let your manager know that you are back."),$thisstaff->getFirstName());

$inc='profile.inc.php';
if ($nav)
    $nav->setTabActive('dashboard');
$ost->addExtraHeader('<meta name="tip-namespace" content="dashboard.my_profile" />',
    "$('#content').data('tipNamespace', 'dashboard.my_profile');");
require_once(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$inc);
require_once(STAFFINC_DIR.'footer.inc.php');
?>
