<?php
/*********************************************************************
    cli.inc.php

    Master include file which must be included at the start of every file.
    This is a modification of main.inc.php to support running cli scripts.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

#Disable direct access.
if(!strcasecmp(basename($_SERVER['SCRIPT_NAME']),basename(__FILE__))) die('kwaheri rafiki!');

define('ROOT_PATH', '/');
define('DISABLE_SESSION', true);
define('INC_DIR',dirname(__file__).'/../inc/'); //local include dir!

require_once INCLUDE_DIR . "class.cli.php";

Bootstrap::i18n_prep();
