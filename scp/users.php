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

require_once INCLUDE_DIR.'class.note.php';

$user = null;
if ($_REQUEST['id'] && !($user=User::lookup($_REQUEST['id'])))
    $errors['err'] = __('Unknown or invalid user.');

if ($_POST) {
    switch(strtolower($_REQUEST['do'])) {
        case 'update':
            if (!$user) {
                $errors['err']=__('Unknown or invalid user.');
            } elseif(($acct = $user->getAccount())
                    && !$acct->update($_POST, $errors)) {
                 $errors['err']=__('Unable to update user account information');
            } elseif($user->updateInfo($_POST, $errors)) {
                $msg=__('User updated successfully');
                $_REQUEST['a'] = null;
            } elseif(!$errors['err']) {
                $errors['err']=__('Unable to update user profile. Correct any error(s) below and try again!');
            }
            break;
        case 'create':
            $form = UserForm::getUserForm()->getForm($_POST);
            if (($user = User::fromForm($form))) {
                $msg = Format::htmlchars(sprintf(__('%s added successfully'), $user->getName()));
                $_REQUEST['a'] = null;
            } elseif (!$errors['err']) {
                $errors['err'] = __('Unable to add user. Correct any error(s) below and try again.');
            }
            break;
        case 'confirmlink':
            if (!$user || !$user->getAccount())
                $errors['err'] = __('Unknown or invalid user account');
            elseif ($user->getAccount()->isConfirmed())
                $errors['err'] = __('Account is already confirmed');
            elseif ($user->getAccount()->sendConfirmEmail())
                $msg = sprintf(__('Account activation email sent to %s'),$user->getEmail());
            else
                $errors['err'] = __('Unable to send account activation email - try again!');
            break;
        case 'pwreset':
            if (!$user || !$user->getAccount())
                $errors['err'] = __('Unknown or invalid user account');
            elseif ($user->getAccount()->sendResetEmail())
                $msg = sprintf(__('Account password reset email sent to %s'),$user->getEmail());
            else
                $errors['err'] = __('Unable to send account password reset email - try again!');
            break;
        case 'mass_process':
            if (!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = __('You must select at least one user.');
            } else {
                $errors['err'] = "Coming soon!";
            }
            break;
        case 'import-users':
            $status = User::importFromPost($_FILES['import'] ?: $_POST['pasted']);
            if (is_numeric($status))
                $msg = sprintf(_N('Successfully imported %1$d client.', 'Successfully imported %1$d clients.', $status),
                   $status);
            else
                $errors['err'] = $status;
            break;
        default:
            $errors['err'] = __('Unknown action');
            break;
    }
} elseif($_REQUEST['a'] == 'export') {
    require_once(INCLUDE_DIR.'class.export.php');
    $ts = strftime('%Y%m%d');
    if (!($token=$_REQUEST['qh']))
        $errors['err'] = __('Query token required');
    elseif (!($query=$_SESSION['users_qs_'.$token]))
        $errors['err'] = __('Query token not found');
    elseif (!Export::saveUsers($query, __("users")."-$ts.csv", 'csv'))
        $errors['err'] = __('Internal error: Unable to dump query results');
}

$page = $user? 'user-view.inc.php' : 'users.inc.php';

$nav->setTabActive('users');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
