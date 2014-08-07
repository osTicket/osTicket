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

if ($cfg->getClientRegistrationMode() == 'disabled'
        || isset($_POST['lticket']))
    $inc = 'accesslink.inc.php';
else
    $inc = 'login.inc.php';

$suggest_pwreset = false;
if ($_POST && isset($_POST['luser'])) {
    if (!$_POST['luser'])
        $errors['err'] = __('Valid username or email address is required');
    elseif (($user = UserAuthenticationBackend::process($_POST['luser'],
            $_POST['lpasswd'], $errors))) {
        if ($user instanceof ClientCreateRequest) {
            if ($cfg && $cfg->isClientRegistrationEnabled()) {
                // Attempt to automatically register
                if ($user->attemptAutoRegister())
                    Http::redirect('tickets.php');

                // Auto-registration failed. Show the user the info we have
                $inc = 'register.inc.php';
                $user_form = UserForm::getUserForm()->getForm($user->getInfo());
            }
            else {
                $errors['err'] = __('Access Denied. Contact your help desk administrator to have an account registered for you');
                // fall through to show login page again
            }
        }
        else {
            Http::redirect($_SESSION['_client']['auth']['dest']
                ?: 'tickets.php');
        }
    } elseif(!$errors['err']) {
        $errors['err'] = __('Invalid username or password - try again!');
    }
    $suggest_pwreset = true;
}
elseif ($_POST && isset($_POST['lticket'])) {
    if (!Validator::is_email($_POST['lemail']))
        $errors['err'] = __('Valid email address and ticket number required');
    elseif (($user = UserAuthenticationBackend::process($_POST['lemail'],
            $_POST['lticket'], $errors))) {

        // If email address verification is not required, then provide
        // immediate access to the ticket!
        if (!$cfg->isClientEmailVerificationRequired())
            Http::redirect('tickets.php');

        // We're using authentication backend so we can guard aganist brute
        // force attempts (which doesn't buy much since the link is emailed)
        $user->sendAccessLink();
        $msg = sprintf(__("%s - access link sent to your email!"),
            Format::htmlchars($user->getName()->getFirst()));
        $_POST = null;
    } elseif(!$errors['err']) {
        $errors['err'] = __('Invalid email or ticket number - try again!');
    }
}
elseif (isset($_GET['do'])) {
    switch($_GET['do']) {
    case 'ext':
        // Lookup external backend
        if ($bk = UserAuthenticationBackend::getBackend($_GET['bk']))
            $bk->triggerAuth();
    }
}
elseif ($user = UserAuthenticationBackend::processSignOn($errors, false)) {
    // Users from the ticket access link
    if ($user && $user instanceof TicketUser && $user->getTicketId())
        Http::redirect('tickets.php?id='.$user->getTicketId());
    // Users imported from an external auth backend
    elseif ($user instanceof ClientCreateRequest) {
        if ($cfg && $cfg->isClientRegistrationEnabled()) {
            // Attempt to automatically register
            if ($user->attemptAutoRegister())
                Http::redirect('tickets.php');

            // Unable to auto-register. Fill in what we have and let the
            // user complete the info
            $inc = 'register.inc.php';
        }
        else {
            $errors['err'] = __('Access Denied. Contact your help desk administrator to have an account registered for you');
            // fall through to show login page again
        }
    }
    elseif ($user instanceof AuthenticatedUser) {
        Http::redirect('tickets.php');
    }
}

if (!$nav) {
    $nav = new UserNav();
    $nav->setActiveNav('status');
}
require CLIENTINC_DIR.'header.inc.php';
require CLIENTINC_DIR.$inc;
require CLIENTINC_DIR.'footer.inc.php';
?>
