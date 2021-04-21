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
require 'secure.inc.php';

require_once 'class.user.php';

// Check if User is Guest. If so, redirect them back to ticket page to
// prevent Account Takeover.
if ($thisclient->isGuest())
    Http::redirect('tickets.php');

$user = User::lookup($thisclient->getId());

if ($user && $_POST) {
    $errors = array();
    if ($acct = $thisclient->getAccount()) {
       $acct->update($_POST, $errors);
    }
    if (!$errors && $user->updateInfo($_POST, $errors))
        Http::redirect('tickets.php');
}

$inc = 'profile.inc.php';

include(CLIENTINC_DIR.'header.inc.php');
include(CLIENTINC_DIR.$inc);
include(CLIENTINC_DIR.'footer.inc.php');

