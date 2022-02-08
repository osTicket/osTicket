<?php
/*********************************************************************
    dispatcher.php

    Dispatcher for client applications

    Jared Hancock <jared@osticket.com>
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

function clientLoginPage($msg='Unauthorized') {
    Http::response(403,'Must login: '.Format::htmlchars($msg));
    exit;
}

require('client.inc.php');

if(!defined('INCLUDE_DIR'))	Http::response(500, 'Server configuration error');
require_once INCLUDE_DIR.'/class.dispatcher.php';

$dispatcher = new Dispatcher();

Signal::send('ajax.client', $dispatcher);
print $dispatcher->resolve(Osticket::get_path_info());
