<?php
/*********************************************************************
    setup.inc.php

    Master include file for setup/install scripts.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

#define constants.
define('SETUPINC',true);
require_once(dirname(__file__).'/../bootstrap.php');
# start session if we don't have one active already
if (session_status() === PHP_SESSION_NONE) {
  Bootstrap::init();
  session_start();
}

#clear global vars
$errors=array();
$msg='';

define('URL',rtrim((Bootstrap::https()?'https':'http').'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']),'setup'));

#define paths
define('INC_DIR',dirname(__file__).'/inc/'); //local include dir!

#required files
require_once(INCLUDE_DIR.'class.setup.php');
require_once(INCLUDE_DIR.'class.validator.php');
require_once(INCLUDE_DIR.'class.passwd.php');
require_once(INCLUDE_DIR.'class.format.php');
require_once(INCLUDE_DIR.'class.misc.php');
require_once INCLUDE_DIR.'mysqli.php';
require_once INCLUDE_DIR.'class.i18n.php';

Internationalization::bootstrap();

// Set browser-preferred language (if installed)
require_once INCLUDE_DIR.'class.translation.php';

// Support flags in the setup portal too
if (isset($_GET['lang']) && $_GET['lang']) {
    Internationalization::setCurrentLanguage($_GET['lang']);
}
TextDomain::configureForUser();

?>
