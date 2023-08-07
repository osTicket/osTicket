<?php
/*********************************************************************
    logo.php

    Simple logo to facilitate serving a customized client-side logo from
    osTicet. The logo is configurable in Admin Panel -> Settings -> Pages

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
// Use Noop Session Handler
define('NOOP_SESSION', true);
require('client.inc.php');
$ttl = 86400; // max-age
if (($logo = $ost->getConfig()->getClientLogo())) {
    $logo->display(false, $ttl);
}

header("Cache-Control: private, max-age=$ttl");
header('Pragma: private');
header('Location: '.ASSETS_PATH.'images/logo.png');
?>
