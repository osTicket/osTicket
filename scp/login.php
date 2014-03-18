<?php
/*********************************************************************
    login.php

    Handles staff authentication/logins

    Peter Rotich <peter@osticket.com>
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

$dest = $_SESSION['_staff']['auth']['dest'];
$msg = $_SESSION['_staff']['auth']['msg'];
$msg = $msg?$msg:'Authentication Required';
$dest=($dest && (!strstr($dest,'login.php') && !strstr($dest,'ajax.php')))?$dest:'index.php';
$show_reset = false;
if($_POST) {
    // Lookup support backends for this staff
    $username = trim($_POST['userid']);
    if ($user = StaffAuthenticationBackend::process($username,
            $_POST['passwd'], $errors)) {
        session_write_close();
        Http::redirect($dest);
        require_once('index.php'); //Just incase header is messed up.
        exit;
    }

    $msg = $errors['err']?$errors['err']:'Invalid login';
    $show_reset = true;
}
// Consider single sign-on authentication backends
else if (!$thisstaff || !($thisstaff->getId() || $thisstaff->isValid())) {
    if (($user = StaffAuthenticationBackend::processSignOn($errors, false))
            && ($user instanceof StaffSession))
       @header("Location: $dest");
}

define("OSTSCPINC",TRUE); //Make includes happy!
include_once(INCLUDE_DIR.'staff/login.tpl.php');
?>
