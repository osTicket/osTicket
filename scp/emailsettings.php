<?php
/*********************************************************************
    emailsettings.php

    Handles settings for the email channel

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');

$errors = array();
$tip_namespace = 'settings.email';
$inc = 'settings-emails.inc.php';

if ($_POST && !$errors) {
    if($cfg && $cfg->updateSettings($_POST,$errors)) {
        $msg=sprintf(__('Successfully updated %s.'), Format::htmlchars($page[0]));
    } elseif(!$errors['err']) {
        $errors['err'] = sprintf('%s %s',
            __('Unable to update settings.'),
            __('Correct any errors below and try again.'));
    }
}

$config=($errors && $_POST)?Format::input($_POST):Format::htmlchars($cfg->getConfigInfo());
$ost->addExtraHeader('<meta name="tip-namespace" content="'.$tip_namespace.'" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");

$nav->setTabActive('emails', 'emailsettings.php');
require_once(STAFFINC_DIR.'header.inc.php');
include_once(STAFFINC_DIR.$inc);
include_once(STAFFINC_DIR.'footer.inc.php');

?>
