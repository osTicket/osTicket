<?php
/*********************************************************************
    pwreset.php

    Handles step 2, 3 and 5 of password resetting
        1. Fail to login (2+ fail login attempts)
        2. Visit password reset form and enter username or email
        3. Receive an email with a link and follow it
        4. Visit password reset form again, with the link
        5. Enter the username or email address again and login
        6. Password change is now required, user changes password and
           continues on with the session

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once('../main.inc.php');
if(!defined('INCLUDE_DIR')) die('Fatal Error. Kwaheri!');

require_once(INCLUDE_DIR.'class.staff.php');
require_once(INCLUDE_DIR.'class.csrf.php');

$tpl = 'pwreset.php';
if($_POST) {
    if (!$ost->checkCSRFToken()) {
        Http::response(400, 'Valid CSRF Token Required');
        exit;
    }
    switch ($_POST['do']) {
        case 'sendmail':
            if (($staff=Staff::lookup($_POST['userid']))) {
                if (!$staff->hasPassword()) {
                    $msg = 'Unable to reset password. Contact your administrator';
                }
                elseif (!$staff->sendResetEmail()) {
                    $tpl = 'pwreset.sent.php';
                }
            }
            else
                $msg = 'Unable to verify username '
                    .Format::htmlchars($_POST['userid']);
            break;
        case 'newpasswd':
            // TODO: Compare passwords
            $tpl = 'pwreset.login.php';
            $errors = array();
            if ($staff = StaffAuthenticationBackend::processSignOn($errors)) {
                $info = array('page' => 'index.php');
                Http::redirect($info['page']);
            }
            elseif (isset($errors['msg'])) {
                $msg = $errors['msg'];
            }
            break;
    }
}
elseif ($_GET['token']) {
    $msg = 'Please enter your username or email';
    $_config = new Config('pwreset');
    if (($id = $_config->get($_GET['token']))
            && ($staff = Staff::lookup($id)))
        // TODO: Detect staff confirmation (for welcome email)
        $tpl = 'pwreset.login.php';
    else
        header('Location: index.php');
}
elseif ($cfg->allowPasswordReset()) {
    $msg = 'Enter your username or email address below';
}
else {
    $_SESSION['_staff']['auth']['msg']='Password resets are disabled';
    return header('Location: index.php');
}
define("OSTSCPINC",TRUE); //Make includes happy!
include_once(INCLUDE_DIR.'staff/'. $tpl);
