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

function clientLoginPage($msg='Non autorisé') {
    Http::response(403,'Connexion requise : '.Format::htmlchars($msg));
    exit;
}

require('client.inc.php');

if(!defined('INCLUDE_DIR'))	Http::response(500, 'Erreur de configuration du serveur');
require_once INCLUDE_DIR.'/class.dispatcher.php';

$dispatcher = new Dispatcher();

Signal::send('ajax.client', $dispatcher);
print $dispatcher->resolve($ost->get_path_info());
