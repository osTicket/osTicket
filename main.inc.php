<?php
/*********************************************************************
main.inc.php

Master include file which must be included at the start of every file.
The brain of the whole sytem. Don't monkey with it.

Peter Rotich <peter@osticket.com>
Copyright (c)  2006-2013 osTicket
http://www.osticket.com

Released under the GNU General Public License WITHOUT ANY WARRANTY.
See LICENSE.TXT for details.

vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

#Disable direct access.
if(isset($_SERVER['SCRIPT_NAME'])
        && !strcasecmp(basename($_SERVER['SCRIPT_NAME']),basename(__FILE__)))
    die('kwaheri rafiki!');

require('bootstrap.php');
Bootstrap::loadConfig();
Bootstrap::defineTables(TABLE_PREFIX);
Bootstrap::i18n_prep();
Bootstrap::loadCode();
Bootstrap::connect();

# Global Override when behind a proxy
$_SERVER['REMOTE_ADDR'] = osTicket::get_client_ip();
$_SERVER['SERVER_PORT'] = osTicket::get_client_port();

if(!($ost=osTicket::start()) || !($cfg = $ost->getConfig()))
Bootstrap::croak(__('Unable to load config info from DB.').' '.__('Get technical help!'));

if ($cfg && $cfg->forceHttps()
        && !osTicket::is_cli()
        && !osTicket::is_https()) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET')
        Http::response(400, 'HTTPS Protocol Required');

    Http::redirect('https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
}

//Init
$session = $ost->getSession();

//System defaults we might want to make global//
#pagenation default - user can override it!
define('DEFAULT_PAGE_LIMIT', $cfg->getPageSize()?$cfg->getPageSize():25);

#Cleanup magic quotes crap.
if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
$_POST=Format::strip_slashes($_POST);
$_GET=Format::strip_slashes($_GET);
$_REQUEST=Format::strip_slashes($_REQUEST);
}

// extract system messages
$errors = array();
$msg=$warn=$sysnotice='';
if (isset($_SESSION['::sysmsgs'])) {
    extract($_SESSION['::sysmsgs']);
    unset($_SESSION['::sysmsgs']);
}
?>
