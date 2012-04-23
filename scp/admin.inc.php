<?php
/*********************************************************************
    admin.inc.php

    Handles all admin related pages....everything admin!

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('staff.inc.php');
//Make sure config is loaded and the staff is set and of admin type
if(!$cfg or !$thisstaff or !$thisstaff->isadmin()) {
    header('Location: index.php');
    require('index.php'); // just in case!
    exit;
}

//Some security related warnings - bitch until fixed!!! :)
if($cfg->isUpgradePending()) {
    $errors['err']=$sysnotice='System upgrade is pending <a href="../setup/upgrade.php">Upgrade Now</a>';
} elseif(!$cfg->isHelpDeskOffline()) {
    
    if(file_exists('../setup/')) {
        $sysnotice='Please take a minute to delete <strong>setup/install</strong> directory (../setup/) for security reasons.';
    } elseif(CONFIG_FILE && file_exists(CONFIG_FILE) && is_writable(CONFIG_FILE)) {
            //Confirm for real that the file is writable by group or world.
            clearstatcache(); //clear the cache!
            $perms = @fileperms(CONFIG_FILE);
            if(($perms & 0x0002) || ($perms & 0x0010)) { 
                $sysnotice=sprintf('Please change permission of config file (%s) to remove write access. e.g <i>chmod 644 %s</i>',
                                basename(CONFIG_FILE), basename(CONFIG_FILE));
            }
    }

    if(!$sysnotice && ini_get('register_globals'))
        $sysnotice='Please consider turning off register globals if possible';
}

//Define some constants.
define('OSTADMININC',TRUE); //checked by admin include files
define('ADMINPAGE',TRUE);   //Used by the header to swap menus.
//Admin navigation - overwrites what was set in staff.inc.php
$nav = new AdminNav($thisstaff);
?>
