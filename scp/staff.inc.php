<?php
/*************************************************************************
    staff.inc.php
    
    File included on every staff page...handles logins (security) and file path issues.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
if(basename($_SERVER['SCRIPT_NAME'])==basename(__FILE__)) die('Kwaheri rafiki!'); //Say hi to our friend..

if(!file_exists('../main.inc.php')) die('Fatal error... get technical support');

define('ROOT_PATH','../'); //Path to the root dir.
require_once('../main.inc.php');

if(!defined('INCLUDE_DIR')) die('Fatal error... invalid setting.');

/*Some more include defines specific to staff only */
define('STAFFINC_DIR',INCLUDE_DIR.'staff/');
define('SCP_DIR',str_replace('//','/',dirname(__FILE__).'/'));

/* Define tag that included files can check */
define('OSTSCPINC',TRUE);
define('OSTSTAFFINC',TRUE);

/* Tables used by staff only */
define('KB_PREMADE_TABLE',TABLE_PREFIX.'kb_premade');


/* include what is needed on staff control panel */

require_once(INCLUDE_DIR.'class.staff.php');
require_once(INCLUDE_DIR.'class.group.php');
require_once(INCLUDE_DIR.'class.nav.php');

/* First order of the day is see if the user is logged in and with a valid session.
    * User must be valid staff beyond this point 
    * ONLY super admins can access the helpdesk on offline state.
*/


if(!function_exists('staffLoginPage')) { //Ajax interface can pre-declare the function to  trap expired sessions.
    function staffLoginPage($msg) {
        $_SESSION['_staff']['auth']['dest']=THISPAGE;
        $_SESSION['_staff']['auth']['msg']=$msg;
        require(SCP_DIR.'login.php');
        exit;
    }
}

$thisstaff = new StaffSession($_SESSION['_staff']['userID']); //Set staff object.
//1) is the user Logged in for real && is staff.
if(!$thisstaff || !is_object($thisstaff) || !$thisstaff->getId() || !$thisstaff->isValid()){
    $msg=(!$thisstaff || !$thisstaff->isValid())?'Authentication Required':'Session timed out due to inactivity';
    staffLoginPage($msg);
    exit;
}
//2) if not super admin..check system status and group status
if(!$thisstaff->isAdmin()) {
    //Check for disabled staff or group!
    if(!$thisstaff->isactive() || !$thisstaff->isGroupActive()) {
        staffLoginPage('Access Denied. Contact Admin');
        exit;
    }

    //Staff are not allowed to login in offline mode!!
    if(!$ost->isSystemOffline() || $ost->isUpgradePending()) {
        staffLoginPage('System Offline');
        exit;
    }
}

//Keep the session activity alive
$thisstaff->refreshSession();

/******* SET STAFF DEFAULTS **********/
//Set staff's timezone offset.
$_SESSION['TZ_OFFSET']=$thisstaff->getTZoffset();
$_SESSION['TZ_DST']=$thisstaff->observeDaylight();

define('PAGE_LIMIT', $thisstaff->getPageLimit()?$thisstaff->getPageLimit():DEFAULT_PAGE_LIMIT);

//Clear some vars. we use in all pages.
$errors=array();
$msg=$warn=$sysnotice='';
$tabs=array();
$submenu=array();
if($ost->isUpgradePending()) {
    $errors['err']=$sysnotice='System upgrade is pending <a href="../setup/upgrade.php">Upgrade Now</a>';
} elseif($cfg->isHelpDeskOffline()) {
    $sysnotice='<strong>System is set to offline mode</strong> - Client interface is disabled and ONLY admins can access staff control panel.';
    $sysnotice.=' <a href="settings.php">Enable</a>.';
}

$nav = new StaffNav($thisstaff);
//Check for forced password change.
if($thisstaff->forcePasswdChange()){
    # XXX: Call staffLoginPage() for AJAX and API requests _not_ to honor
    #      the request
    require('profile.php'); //profile.php must request this file as require_once to avoid problems.
    exit;
}
?>
