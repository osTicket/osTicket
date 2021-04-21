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
    'system' =>
        array(__('System Settings'), 'settings.system'),
    'tickets' =>
        array(__('Ticket Settings and Options'), 'settings.ticket'),
    'tasks' =>
        array(__('Task Settings and Options'), 'settings.tasks'),
    'agents' =>
        array(__('Agent Settings and Options'), 'settings.agents'),
    'users' =>
        array(__('User Settings and Options'), 'settings.users'),
    'pages' =>
        array(__('Site Pages'), 'settings.pages'),
    'kb' =>
        array(__('Knowledgebase Settings'), 'settings.kb'),
);
//Handle a POST.
$target=($_REQUEST['t'] && $settingOptions[$_REQUEST['t']])?$_REQUEST['t']:'system';
$page = false;
if (isset($settingOptions[$target]))
    $page = $settingOptions[$target];

if($page && $_POST && !$errors) {
    if($cfg && $cfg->updateSettings($_POST,$errors)) {
        $msg=sprintf(__('Successfully updated %s.'), Format::htmlchars($page[0]));
    } elseif(!$errors['err']) {
        $errors['err'] = sprintf('%s %s',
            __('Unable to update settings.'),
            __('Correct any errors below and try again.'));
    }
}

$config=($errors && $_POST)?Format::input($_POST):Format::htmlchars($cfg->getConfigInfo());
$ost->addExtraHeader('<meta name="tip-namespace" content="'.$page[1].'" />',
    "$('#content').data('tipNamespace', '".$page[1]."');");

$nav->setTabActive('settings', ('settings.php?t='.$target));
require_once(STAFFINC_DIR.'header.inc.php');
include_once(STAFFINC_DIR."settings-$target.inc.php");
include_once(STAFFINC_DIR.'footer.inc.php');
?>
