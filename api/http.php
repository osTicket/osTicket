<?php
/*********************************************************************
    http.php

    HTTP controller for the osTicket API

    Jared Hancock
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require 'api.inc.php';

# Include the main api urls
require_once INCLUDE_DIR."class.dispatcher.php";
$dispatcher = Dispatcher::include_urls("urls.conf.php");

# Call the respective function
$dispatcher->resolve($_SERVER['PATH_INFO']);

?>
