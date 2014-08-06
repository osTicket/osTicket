<?php
/*********************************************************************
    l.php

    Link redirection

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once 'staff.inc.php';
//Basic url validation + token check.

# PHP < 5.4.7 will not handle a URL like //host.tld/path correctly
if (!($url=trim($_GET['url'])))
    Http::response(422, __('Invalid URL'));

$check = (strpos($url, '//') === 0) ? 'http:' . $url : $url;
if (!Validator::is_url($check) || !$ost->validateLinkToken($_GET['auth']))
    Http::response(403, __('URL link not authorized'));
elseif (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false)
    Http::redirect($url);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="refresh" content="0;URL=<?php echo $url; ?>"/>
</head>
<body/>
</html>
