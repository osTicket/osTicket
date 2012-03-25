<?php
/*********************************************************************
    l.php

    Link redirection

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once 'staff.inc.php';

global $_GET;
$url = $_GET['url'];
if (!$url) exit();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="refresh" content="0;<?php echo $url; ?>"/>
</head>
<body/>
</html>
