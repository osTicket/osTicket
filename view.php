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

$errors = array();
// Check if the client is already signed in. Don't corrupt their session!
if ($_GET['auth']
        && $thisclient
        && ($u = TicketUser::lookupByToken($_GET['auth']))
        && ($u->getUserId() == $thisclient->getId())
) {
    // Switch auth keys ? (Otherwise the user can never use links for two
    // different tickets)
    if (($bk = $thisclient->getAuthBackend()) instanceof AuthTokenAuthentication) {
        $bk->setAuthKey($u, $bk);
    }
    Http::redirect('tickets.php?id='.$u->getTicketId());
}
// Try autologin the user
// Authenticated user can be of type ticket owner or collaborator
elseif (isset($_GET['auth']) || isset($_GET['t'])) {
    // TODO: Consider receiving an AccessDenied object
    $user =  UserAuthenticationBackend::processSignOn($errors, false);
}

if (@$user && is_object($user) && $user->getTicketId())
    Http::redirect('tickets.php?id='.$user->getTicketId());

$nav = new UserNav();
$nav->setActiveNav('status');

$inc = 'accesslink.inc.php';
require CLIENTINC_DIR.'header.inc.php';
require CLIENTINC_DIR.$inc;
require CLIENTINC_DIR.'footer.inc.php';
?>
