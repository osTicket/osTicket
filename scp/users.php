<?php
/*********************************************************************
    users.php

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('staff.inc.php');
$user = null;
if ($_REQUEST['id'] && !($user=User::lookup($_REQUEST['id'])))
    $errors['err'] = 'Unknown or invalid user ID.';

if ($_POST) {
    switch(strtolower($_POST['do'])) {
        case 'update':
            if (!$user) {
                $errors['err']='Unknown or invalid user.';
            } elseif(($acct = $user->getAccount())
                    && !$acct->update($_POST, $errors)) {
                 $errors['err']='Unable to update user account information';
            } elseif($user->updateInfo($_POST, $errors)) {
                $msg='User updated successfully';
                $_REQUEST['a'] = null;
            } elseif(!$errors['err']) {
                $errors['err']='Unable to update user profile. Correct any error(s) below and try again!';
            }
            break;
        case 'create':
            $form = UserForm::getUserForm()->getForm($_POST);
            if (($user = User::fromForm($form))) {
                $msg = Format::htmlchars($user->getName()).' added successfully';
                $_REQUEST['a'] = null;
            } elseif (!$errors['err']) {
                $errors['err'] = 'Unable to add user. Correct any error(s) below and try again.';
            }
            break;
        case 'confirmlink':
            $errors['err'] = "Send Confirmation Link: Coming soon!";
            break;
        case 'pwreset':
            $errors['err'] = "Send Password Reset Link: Coming soon!";
            break;
        case 'mass_process':
            if (!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'You must select at least one user member.';
            } else {
                $errors['err'] = "Coming soon!";
            }
            break;
        default:
            $errors['err'] = 'Unknown action/command';
            break;
    }
}

$page = $user? 'user-view.inc.php' : 'users.inc.php';

$nav->setTabActive('users');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
