<?php
/*********************************************************************
    view.php

    Ticket View.
    TODO: Support different views based on auth_token - e.g for BCC'ed users vs. Ticket owner.

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
if(!$thisclient || !$thisclient->isValid()) {
    // * On login Client::login will redirect the user to tickets.php view.
    // * See TODO above for planned multi-view.
    $user = null;
    if($_GET['t'] && $_GET['e'] && $_GET['a'])
        $user = Client::login($_GET['t'], $_GET['e'], $_GET['a'], $errors);

    //XXX: For now we're assuming the user is the ticket owner
    // (multi-view based on auth token will come later).
    if($user && $user->getTicketID()==trim($_GET['t']))
        @header('Location: tickets.php?id='.$user->getTicketID());
}

//Simply redirecting to tickets.php until multiview is implemented.
require('tickets.php');
?>
