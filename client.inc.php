<?php
/*********************************************************************
    client.inc.php

    File included on every client page

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
if(!strcasecmp(basename($_SERVER['SCRIPT_NAME']),basename(__FILE__))) die('kwaheri rafiki!');

$thisdir=str_replace('\\\\', '/', realpath(dirname(__FILE__))).'/'; 
if(!file_exists($thisdir.'main.inc.php')) die('Fatal Error.');

require_once($thisdir.'main.inc.php');

if(!defined('INCLUDE_DIR')) die('Fatal error');

/*Some more include defines specific to client only */
define('CLIENTINC_DIR',INCLUDE_DIR.'client/');
define('OSTCLIENTINC',TRUE);

define('ASSETS_PATH',ROOT_PATH.'assets/default/');

//Check the status of the HelpDesk.
if (!in_array(strtolower(basename($_SERVER['SCRIPT_NAME'])), array('logo.php',))
        && !(is_object($ost) && $ost->isSystemOnline())) {
    include(ROOT_DIR.'offline.php');
    exit;
}

/* include what is needed on client stuff */
require_once(INCLUDE_DIR.'class.client.php');
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');

//clear some vars
$errors=array();
$msg='';
$thisclient=$nav=null;
//Make sure the user is valid..before doing anything else.
if($_SESSION['_client']['userID'] && $_SESSION['_client']['key'])
    $thisclient = new ClientSession($_SESSION['_client']['userID'],$_SESSION['_client']['key']);

//is the user logged in?
if($thisclient && $thisclient->getId() && $thisclient->isValid()){
     $thisclient->refreshSession();
} else {
    $thisclient = null;
}

/******* CSRF Protectin *************/
// Enforce CSRF protection for POSTS
if ($_POST  && !$ost->checkCSRFToken()) {
    @header('Location: index.php');
    //just incase redirect fails
    die('Action denied (400)!');
}

/* Client specific defaults */
define('PAGE_LIMIT', DEFAULT_PAGE_LIMIT);

$nav = new UserNav($thisclient, 'home');
?>
