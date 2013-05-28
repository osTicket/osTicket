<?php
/*********************************************************************
    settings.php

    Handles all admin settings.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
$errors=array();
$settingOptions=array(
                'system' => 'System Settings',
                'tickets' => 'Ticket Settings and Options',
                'emails' => 'Email Settings',
                'kb' => 'Knowledgebase Settings',
                'autoresp' => 'Autoresponder Settings',
                'alerts' => 'Alerts and Notices Settings');
//Handle a POST.
if($_POST && !$errors) {
    if($cfg && $cfg->updateSettings($_POST,$errors)) {
        $msg=Format::htmlchars($settingOptions[$_POST['t']]).' Updated Successfully';
    } elseif(!$errors['err']) {
        $errors['err']='Unable to update settings - correct errors below and try again';
    }
}

$target=($_REQUEST['t'] && $settingOptions[$_REQUEST['t']])?$_REQUEST['t']:'system';
$config=($errors && $_POST)?Format::input($_POST):Format::htmlchars($cfg->getConfigInfo());

$nav->setTabActive('settings', ('settings.php?t='.$target));
require_once(STAFFINC_DIR.'header.inc.php');
include_once(STAFFINC_DIR."settings-$target.inc.php");
include_once(STAFFINC_DIR.'footer.inc.php');
?>
