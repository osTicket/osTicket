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
    $errors['err'] = sprintf(__('%s: Unknown or invalid'), _N('end user', 'end users', 1));

if ($_POST) {
    switch(strtolower($_REQUEST['do'])) {
        case 'update':
            if (!$user) {
                $errors['err']=sprintf(__('%s: Unknown or invalid'), _N('end user', 'end users', 1));
            } elseif(($acct = $user->getAccount())
                    && !$acct->update($_POST, $errors)) {
                 $errors['err']=__('Unable to update user account information');
            } elseif($user->updateInfo($_POST, $errors)) {
                $msg=sprintf(__('Successfully updated %s'), __('this end user'));
                $_REQUEST['a'] = null;
            } elseif(!$errors['err']) {
                $errors['err']=sprintf(__('Unable to update %s. Correct error(s) below and try again!'),
                    __('this end user'));
            }
            break;
        case 'create':
            $form = UserForm::getUserForm()->getForm($_POST);
            if (($user = User::fromForm($form))) {
                $msg = Format::htmlchars(sprintf(__('Successfully added %s'), $user->getName()));
                $_REQUEST['a'] = null;
            } elseif (!$errors['err']) {
                $errors['err'] = sprintf(__('Unable to add %s. Correct error(s) below and try again.'),
                    __('this end user'));
            }
            break;
        case 'confirmlink':
            if (!$user || !$user->getAccount())
                $errors['err'] = sprintf(__('%s: Unknown or invalid'),
                    __('end user account'));
            elseif ($user->getAccount()->isConfirmed())
                $errors['err'] = __('Account is already confirmed');
            elseif ($user->getAccount()->sendConfirmEmail())
                $msg = sprintf(__('Account activation email sent to %s'),$user->getEmail());
            else
                $errors['err'] = __('Unable to send account activation email - try again!');
            break;
        case 'pwreset':
            if (!$user || !$user->getAccount())
                $errors['err'] = sprintf(__('%s: Unknown or invalid'), __('end user account'));
            elseif ($user->getAccount()->sendResetEmail())
                $msg = sprintf(__('Account password reset email sent to %s'),$user->getEmail());
            else
                $errors['err'] = __('Unable to send account password reset email - try again!');
            break;
        case 'mass_process':
            if (!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s.'),
                    __('one end user'));
            } else {
                $users = User::objects()->filter(
                    array('id__in' => $_POST['ids'])
                );
                $count = 0;
                switch (strtolower($_POST['a'])) {
                case 'lock':
                    foreach ($users as $U)
                        if (($acct = $U->getAccount()) && $acct->lock())
                            $count++;
                    break;

                case 'unlock':
                    foreach ($users as $U)
                        if (($acct = $U->getAccount()) && $acct->unlock())
                            $count++;
                    break;

                case 'delete':
                    foreach ($users as $U)
                        if ($U->delete())
                            $count++;
                    break;

                case 'reset':
                    foreach ($users as $U)
                        if (($acct = $U->getAccount()) && $acct->sendResetEmail())
                            $count++;
                    break;

                case 'register':
                    foreach ($users as $U) {
                        if (($acct = $U->getAccount()) && $acct->sendConfirmEmail())
                            $count++;
                        elseif ($acct = UserAccount::register($U,
                            array('sendemail' => true), $errors
                        )) {
                            $count++;
                        }
                    }
                    break;

                case 'setorg':
                    if (!($org = Organization::lookup($_POST['org_id'])))
                        $errors['err'] = __('Unknown action - get technical help.');
                    foreach ($users as $U) {
                        if ($U->setOrganization($org))
                            $count++;
                    }
                    break;

                default:
                    $errors['err']=__('Unknown action - get technical help.');
                }
                if (!$errors['err'] && !$count) {
                    $errors['err'] = __('Unable to manage any of the selected end users');
                }
                elseif ($_POST['count'] && $count != $_POST['count']) {
                    $warn = __('Not all selected items were updated');
                }
                elseif ($count) {
                    $msg = __('Successfully managed selected end users');
                }


            }
            break;
        case 'import-users':
            $status = User::importFromPost($_FILES['import'] ?: $_POST['pasted']);
            if (is_numeric($status))
                $msg = sprintf(__('Successfully imported %1$d %2$s.'), $status,
                    _N('end user', 'end users', $status));
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
