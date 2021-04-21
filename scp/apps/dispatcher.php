<?php
/*********************************************************************
    dispatcher.php

    Dispatcher for staff applications

    Jared Hancock <jared@osticket.com>
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
if (basename($_SERVER['SCRIPT_NAME'])==basename(__FILE__))
    die('Access denied'); //Say hi to our friend..

require('staff.inc.php');

//Clean house...don't let the world see your crap.
#ini_set('display_errors','0'); //Disable error display
#ini_set('display_startup_errors','0');

//TODO: disable direct access via the browser? i,e All request must have REFER?
if(!defined('INCLUDE_DIR'))	Http::response(500, 'Server configuration error');

require_once INCLUDE_DIR.'/class.dispatcher.php';
$dispatcher = new Dispatcher();

$PI = $ost->get_path_info();
if (strpos(strtolower($PI), '/admin/') === 0) {
    require('admin.inc.php');
    $PI = substr($PI, 6);
    Signal::send('apps.admin', $dispatcher);
}
else {
    Signal::send('apps.scp', $dispatcher);
}

$nav->setActiveTab('apps');

# Call the respective function
print $dispatcher->resolve($PI);
