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

$thisdir=str_replace('\\', '/', dirname(__FILE__)).'/';
if(!file_exists($thisdir.'main.inc.php')) die('Fatal Error.');

require_once($thisdir.'main.inc.php');

if(!defined('INCLUDE_DIR')) die('Fatal error');

// Enforce ACL (if applicable)
if (!Validator::check_acl('client'))
    die(__('Access Denied'));

/*Some more include defines specific to client only */
define('CLIENTINC_DIR',INCLUDE_DIR.'client/');
define('OSTCLIENTINC',TRUE);

define('ASSETS_PATH',ROOT_PATH.'assets/default/');

//Check the status of the HelpDesk.
if (!in_array(strtolower(basename($_SERVER['SCRIPT_NAME'])), array('logo.php','file.php'))
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
$nav=null;
//Make sure the user is valid..before doing anything else.
$thisclient = UserAuthenticationBackend::getUser();

if (isset($_GET['lang']) && $_GET['lang']) {
    Internationalization::setCurrentLanguage($_GET['lang']);
}

// Bootstrap gettext translations as early as possible, but after attempting
// to sign on the agent
TextDomain::configureForUser($thisclient);

//is the user logged in?
if($thisclient && $thisclient->getId() && $thisclient->isValid()){
     $thisclient->refreshSession();
} else {
    $thisclient = null;
}

/******* CSRF Protectin *************/
// Enforce CSRF protection for POSTS
if ($_POST  && !$ost->checkCSRFToken()) {
    Http::redirect('index.php');
    //just incase redirect fails
    die('Action denied (400)!');
}

//Add token to the header - used on ajax calls [DO NOT CHANGE THE NAME]
$ost->addExtraHeader('<meta name="csrf_token" content="'.$ost->getCSRFToken().'" />');

/* Client specific defaults */
define('PAGE_LIMIT', DEFAULT_PAGE_LIMIT);

require(INCLUDE_DIR.'class.nav.php');
$nav = new UserNav($thisclient, 'home');

$exempt = in_array(basename($_SERVER['SCRIPT_NAME']), array('logout.php', 'ajax.php', 'logs.php', 'upgrade.php'));

if (!$exempt && $thisclient && ($acct = $thisclient->getAccount())
        && $acct->isPasswdResetForced()) {
    $warn = __('Password change required to continue');
    require('profile.php'); //profile.php must request this file as require_once to avoid problems.
    exit;
}
?>
