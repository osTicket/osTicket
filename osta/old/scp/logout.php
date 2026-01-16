<?php
/*********************************************************************
    logout.php

    Log out staff
    Destroy the session and redirect to login.php

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('staff.inc.php');

//Check token: Make sure the user actually clicked on the link to logout.
if(!$_GET['auth'] || !$ost->validateLinkToken($_GET['auth']))
    @header('Location: index.php');

try {
    $thisstaff->logOut();
    session_unset();
    osTicketSession::destroyCookie();
    session_destroy();
    //Clear any ticket locks the staff has.
    Lock::removeStaffLocks($thisstaff->getId());
}
catch (Exception $x) {
    // Lock::removeStaffLocks may throw InconsistentModel on upgrade
}

Http::redirect('login.php');
