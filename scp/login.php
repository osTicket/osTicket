<?php
/*********************************************************************
    login.php

    Handles staff authentication/logins

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once('../main.inc.php');
if(!defined('INCLUDE_DIR')) die('Fatal Error. Kwaheri!');

require_once(INCLUDE_DIR.'class.staff.php');

$msg=$_SESSION['_staff']['auth']['msg'];
$msg=$msg?$msg:'Authentication Required';
if($_POST && (!empty($_POST['username']) && !empty($_POST['passwd']))){
    //$_SESSION['_staff']=array(); #Uncomment to disable login strikes.
    $msg='Invalid login';
    if(($user=Staff::login($_POST['username'],$_POST['passwd'],$errors))){
        $dest=$_SESSION['_staff']['auth']['dest'];
        $dest=($dest && (!strstr($dest,'login.php') && !strstr($dest,'ajax.php')))?$dest:'index.php';
        @header("Location: $dest");
        require_once('index.php'); //Just incase header is messed up.
        exit;
    }elseif(!$errors['err']){
        $errors['err']='Login error - try again';
    }
}
define("OSTSCPINC",TRUE); //Make includes happy!
include_once(INCLUDE_DIR.'staff/login.tpl.php');
?>
