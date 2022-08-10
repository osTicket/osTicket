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

// Don't update the session for inline image fetches
if (!function_exists('noop')) { function noop() {} }
session_set_save_handler('noop','noop','noop','noop','noop','noop');
define('DISABLE_SESSION', true);

require_once('../main.inc.php');

$ttl = 86400; // max-age
if (isset($_GET['backdrop'])) {
    if (($backdrop = $ost->getConfig()->getStaffLoginBackdrop())) {
        $backdrop->display(false, $ttl);
        // ::display() will not return
    }
    header("Cache-Control: private, max-age=$ttl");
    header('Pragma: private');
    Http::redirect('images/login-headquarters.jpg');
}
elseif (($logo = $ost->getConfig()->getStaffLogo())) {
    $logo->display(false, $ttl);
}

header("Cache-Control: private, max-age=$ttl");
header('Pragma: private');
Http::redirect('images/ost-logo.png');

?>
