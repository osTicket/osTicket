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
require('secure.inc.php');
if(!is_object($thisclient) || !$thisclient->isValid()) die('Access denied'); //Double check again.
//We are now using tickets.php but we need to keep view.php for backward compatibility
require('tickets.php');
?>
