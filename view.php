<?php
/*********************************************************************
    view.php

    Ticket View.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
require_once('client.inc.php');


//If the user is NOT logged in - try auto-login (if params exists).
if (!$thisclient || !$thisclient->isValid()) {
    // Try autologin the user
    // Authenticated user can be of type ticket owner or collaborator
    $errors = array();
    $user =  UserAuthenticationBackend::singleSignOn($errors);
    if ($user && $user->getTicketID())
        @header('Location: tickets.php?id='.$user->getTicketID());
}
//Simply redirecting to tickets.php until multiview is implemented.
require('tickets.php');
?>
