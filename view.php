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

// Try autologin the user
// Authenticated user can be of type ticket owner or collaborator
$errors = array();
$user =  UserAuthenticationBackend::processSignOn($errors);
if ($user && $user->getTicketId())
    Http::redirect('tickets.php?id='.$user->getTicketId());

//Simply redirecting to tickets.php until multiview is implemented.
require('tickets.php');
?>
