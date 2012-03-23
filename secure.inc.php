<?php
/*********************************************************************
    secure.inc.php

    File included on every client's "secure" pages

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
if(!strcasecmp(basename($_SERVER['SCRIPT_NAME']),basename(__FILE__))) die('Kwaheri!');
if(!file_exists('client.inc.php')) die('Fatal Error.');
require_once('client.inc.php');
//User must be logged in!
if(!$thisclient || !$thisclient->getId() || !$thisclient->isValid()){
    require('./login.php');
    exit;
}
$thisclient->refreshSession();
?>
