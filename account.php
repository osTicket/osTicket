<?php
/*********************************************************************
    profile.php

    Manage client profile. This will allow a logged-in user to manage
    his/her own public (non-internal) information

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
require 'client.inc.php';

$inc = 'register.inc.php';

$errors = array();

if (!$cfg || !$cfg->isClientRegistrationEnabled()) {
    Http::redirect('index.php');
}

elseif ($thisclient) {
    $inc = isset($_GET['confirmed'])
        ? 'registration.confirmed.inc.php' : 'profile.inc.php';
    $user = User::lookup($thisclient->getId());
}

if ($user && $_POST) {
    if ($acct = $thisclient->getAccount()) {
       $acct->update($_POST, $errors);
    }
    if (!$errors && $user->updateInfo($_POST, $errors))
        Http::redirect('tickets.php');
}

elseif ($_POST) {
    $user_form = UserForm::getUserForm()->getForm($_POST);
    if (!$user_form->isValid(function($f) { return !$f->get('internal'); }))
        $errors['err'] = 'Incomplete client information';
    elseif (!$_POST['passwd1'])
        $errors['passwd1'] = 'New password required';
    elseif ($_POST['passwd2'] != $_POST['passwd1'])
        $errors['passwd1'] = 'Passwords do not match';

    elseif (!($user=User::fromForm($user_form)))
        $errors['err'] = 'Unable to register account. See messages below';
    else {
        if (!($acct = ClientAccount::createForUser($user)))
            $errors['err'] = 'Internal error. Unable to create new account';
        elseif (!$acct->update($_POST, $errors))
            $errors['err'] = 'Errors configuring your profile. See messages below';
    }

    if (!$errors) {
        switch ($_POST['do']) {
        case 'create':
            $inc = 'register.confirm.inc.php';
            $acct->sendResetEmail('registration-client');
        }
    }

    if ($errors && $user)
        $user->delete();
}

include(CLIENTINC_DIR.'header.inc.php');
include(CLIENTINC_DIR.$inc);
include(CLIENTINC_DIR.'footer.inc.php');

