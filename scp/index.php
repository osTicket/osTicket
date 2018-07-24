<?php
/*********************************************************************
    index.php
    
    Future site for helpdesk summary aka Dashboard.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
//Nothing for now...simply redirect to tickets page.
header("Location: tickets.php?queue=3&p=1&l=0&s=0");
die();
?>
