<?php
/*********************************************************************
    login.php

    User access link recovery

    TODO: This is a temp. fix to allow for collaboration in lieu of real
    username and password coming in 1.8.2

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once('client.inc.php');
if(!defined('INCLUDE_DIR')) die('Fatal Error');
define('CLIENTINC_DIR',INCLUDE_DIR.'client/');
define('OSTCLIENTINC',TRUE); //make includes happy

require_once(INCLUDE_DIR.'class.client.php');
require_once(INCLUDE_DIR.'class.ticket.php');

$inc = 'login.inc.php';
if ($_POST) {
    if (!$_POST['lticket'] || !Validator::is_email($_POST['lemail']))
        $errors['err'] = 'Valid email address and ticket number required';
    elseif (($user = UserAuthenticationBackend::process($_POST['lemail'],
                    $_POST['lticket'], $errors))) {
        //We're using authentication backend so we can guard aganist brute
        // force attempts (which doesn't buy much since the link is emailed)
        $user->sendAccessLink();
        $msg = sprintf("%s - access link sent to your email!",
            $user->getName()->getFirst());
        $_POST = null;
    } elseif(!$errors['err']) {
        $errors['err'] = 'Invalid email or ticket number - try again!';
    }
}

$nav = new UserNav();
$nav->setActiveNav('status');
require CLIENTINC_DIR.'header.inc.php';
require CLIENTINC_DIR.$inc;
require CLIENTINC_DIR.'footer.inc.php';
?>
