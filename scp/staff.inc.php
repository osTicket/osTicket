<?php
/*************************************************************************
    staff.inc.php

    File included on every staff page...handles logins (security) and file path issues.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
if(basename($_SERVER['SCRIPT_NAME'])==basename(__FILE__)) die('Access denied'); //Say hi to our friend..

if(!file_exists('../main.inc.php')) die('Fatal error... Get technical help!');

require_once('../main.inc.php');

if(!defined('INCLUDE_DIR')) die('Fatal error... invalid setting.');

// Enforce ACL (if applicable)
if (!Validator::check_acl('staff'))
    die(__('Access Denied'));

/*Some more include defines specific to staff only */
if (!defined('STAFFINC_DIR'))
    define('STAFFINC_DIR',INCLUDE_DIR.'staff/');
define('SCP_DIR',str_replace('//','/',dirname(__FILE__).'/'));

/* Define tag that included files can check */
define('OSTSCPINC',TRUE);
define('OSTSTAFFINC',TRUE);

/* Tables used by staff only */
define('KB_PREMADE_TABLE',TABLE_PREFIX.'kb_premade');

/* include what is needed on staff control panel */

require_once(INCLUDE_DIR.'class.staff.php');
require_once(INCLUDE_DIR.'class.csrf.php');

/* First order of the day is see if the user is logged in and with a valid session.
    * User must be valid staff beyond this point
    * ONLY super admins can access the helpdesk on offline state.
*/


if(!function_exists('staffLoginPage')) { //Ajax interface can pre-declare the function to  trap expired sessions.
    function staffLoginPage($msg) {
        global $ost, $cfg;
        $_SESSION['_staff']['auth']['dest'] =
            '/' . ltrim($_SERVER['REQUEST_URI'], '/');
        $_SESSION['_staff']['auth']['msg']=$msg;

        // Redirect here with full path for application-type plugins
        Http::redirect(ROOT_PATH.'scp/login.php');
        exit;
    }
}

$thisstaff = StaffAuthenticationBackend::getUser();

// Bootstrap gettext translations as early as possible, but after attempting
// to sign on the agent
TextDomain::configureForUser($thisstaff);

//1) is the user Logged in for real && is staff.
if (!$thisstaff || !$thisstaff->getId() || !$thisstaff->isValid()) {
    if (isset($_SESSION['_staff']['auth']['msg'])) {
        $msg = $_SESSION['_staff']['auth']['msg'];
        unset($_SESSION['_staff']['auth']['msg']);
    } elseif ($thisstaff && !$thisstaff->isValid())
        $msg = __('Session timed out due to inactivity');
    else
        $msg = __('Authentication Required');

    staffLoginPage($msg);
    exit;
}
//2) if not super admin..check system status and group status
if(!$thisstaff->isAdmin()) {
    //Check for disabled staff or group!
    if (!$thisstaff->isActive()) {
        staffLoginPage(__('Access Denied. Contact Admin'));
        exit;
    }

    //Staff are not allowed to login in offline mode!!
    if(!$ost->isSystemOnline() || $ost->isUpgradePending()) {
        //logout current user if system is offline
        $thisstaff->logOut();
        staffLoginPage(__('System Offline'));
        exit;
    }
}

//Keep the session activity alive
$thisstaff->refreshSession();

/******* CSRF Protectin *************/
// Enforce CSRF protection for POSTS
if ($_POST  && !$ost->checkCSRFToken()) {
    Http::response(400, __('Valid CSRF Token Required'));
    exit;
}

//Add token to the header - used on ajax calls [DO NOT CHANGE THE NAME]
$ost->addExtraHeader('<meta name="csrf_token" content="'.$ost->getCSRFToken().'" />');

// Load the navigation after the user in case some things are hidden
require_once(INCLUDE_DIR.'class.nav.php');

/******* SET STAFF DEFAULTS **********/
define('PAGE_LIMIT', $thisstaff->getPageLimit() ?: DEFAULT_PAGE_LIMIT);

$tabs=array();
$submenu=array();
$exempt = in_array(basename($_SERVER['SCRIPT_NAME']), array('logout.php', 'ajax.php', 'logs.php', 'upgrade.php'));

if($ost->isUpgradePending() && !$exempt) {
    $errors['err']=$sysnotice=__('System upgrade is pending').' <a href="upgrade.php">'.__('Upgrade Now').'</a>';
    require('upgrade.php');
    exit;
} elseif($cfg->isHelpDeskOffline()) {
    $sysnotice='<strong>'.__('System is set to offline mode').'</strong> - '.__('Client interface is disabled and ONLY admins can access staff control panel.');
    $sysnotice.=' <a href="settings.php">'.__('Enable').'</a>.';
}

if (!defined('AJAX_REQUEST'))
    $nav = new StaffNav($thisstaff);

//Check for forced password change.
if($thisstaff->forcePasswdChange() && !$exempt) {
    # XXX: Call staffLoginPage() for AJAX and API requests _not_ to honor
    #      the request
    $sysnotice = __('Password change required to continue');
    require('profile.php'); //profile.php must request this file as require_once to avoid problems.
    exit;
} elseif ($thisstaff->force2faConfig() && !$exempt) {
    $sysnotice = __('Two Factor Authentication configuration required to continue');
    require('profile.php');
    exit;
}
$ost->setWarning($sysnotice);
$ost->setPageTitle(__('osTicket :: Staff Control Panel'));

?>
