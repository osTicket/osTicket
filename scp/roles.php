<?php
/*********************************************************************
    roles.php

    Agent's roles

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require 'admin.inc.php';
include_once INCLUDE_DIR . 'class.user.php';
include_once INCLUDE_DIR . 'class.organization.php';
include_once INCLUDE_DIR . 'class.canned.php';
include_once INCLUDE_DIR . 'class.faq.php';
include_once INCLUDE_DIR . 'class.email.php';
include_once INCLUDE_DIR . 'class.report.php';
include_once INCLUDE_DIR . 'class.thread.php';

$errors = array();
$role=null;
if ($_REQUEST['id'] && !($role = Role::lookup($_REQUEST['id'])))
    $errors['err'] = sprintf(__('%s: Unknown or invalid ID.'),
        __('Role'));

if ($_POST) {
    switch (strtolower($_POST['do'])) {
    case 'update':
        if (!$role) {
            $errors['err'] = sprintf(__('%s: Unknown or invalid ID.'),
                    __('Role'));
        } elseif ($role->update($_POST, $errors)) {
            $msg = __('Role updated successfully');
        } elseif ($errors) {
            $errors['err'] = $errors['err'] ?: sprintf('%s %s',
                sprintf(__('Unable to update %s.'), __('this role')),
                __('Correct any errors below and try again.'));
        } else {
            $errors['err'] = sprintf('%s %s',
                sprintf(__('Unable to update %s.'), __('this role')),
                    __('Internal error occurred'));
        }
        break;
    case 'add':
        $_role = Role::create();
        if ($_role->update($_POST, $errors)) {
            unset($_REQUEST['a']);
            $msg = sprintf(__('Successfully added %s.'),
                    __('role'));
        } elseif ($errors) {
            $errors['err'] = sprintf('%s %s',
                sprintf(__('Unable to add %s.'), __('this role')),
                __('Correct any errors below and try again.'));
        } else {
            $errors['err'] = sprintf(__('Unable to add %s.'), __('this role'))
                    .' — '.__('Internal error occurred');
        }
        break;
    case 'mass_process':
        if (!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
            $errors['err'] = sprintf(__('You must select at least %s.'),
                    __('one role'));
        } else {
            $count = count($_POST['ids']);
            switch(strtolower($_POST['a'])) {
            case 'enable':
                $num = Role::objects()->filter(array(
                    'id__in' => $_POST['ids']
                ))->update(array(
                    'flags'=> SqlExpression::bitor(
                        new SqlField('flags'),
                        Role::FLAG_ENABLED)
                ));
                if ($num) {
                    if($num==$count)
                        $msg = sprintf(__('Successfully enabled %s'),
                            _N('selected role', 'selected roles', $count));
                    else
                        $warn = sprintf(__('%1$d of %2$d %3$s enabled'), $num, $count,
                            _N('selected role', 'selected roles', $count));
                } else {
                    $errors['err'] = sprintf(__('Unable to enable %s'),
                        _N('selected role', 'selected roles', $count));
                }
                break;
            case 'disable':
                $num = Role::objects()->filter(array(
                    'id__in' => $_POST['ids']
                ))->update(array(
                    'flags'=> SqlExpression::bitand(
                        new SqlField('flags'),
                        (~Role::FLAG_ENABLED))
                ));

                if ($num) {
                    if($num==$count)
                        $msg = sprintf(__('Successfully disabled %s'),
                            _N('selected role', 'selected roles', $count));
                    else
                        $warn = sprintf(__('%1$d of %2$d %3$s disabled'), $num, $count,
                            _N('selected role', 'selected roles', $count));
                } else {
                    $errors['err'] = sprintf(__('Unable to disable %s'),
                        _N('selected role', 'selected roles', $count));
                }
                break;
            case 'delete':
                $i=0;
                foreach ($_POST['ids'] as $k=>$v) {
                    if (($r=Role::lookup($v)) && $r->isDeleteable() && $r->delete())
                        $i++;
                }
                if ($i && $i==$count)
                    $msg = sprintf(__('Successfully deleted %s.'),
                            _N('selected role', 'selected roles', $count));
                elseif ($i > 0)
                    $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                            _N('selected role', 'selected roles', $count));
                elseif (!$errors['err'])
                    $errors['err'] = sprintf(__('Unable to delete %s. They may be in use.'),
                            _N('selected role', 'selected roles', $count));
                break;
            default:
                $errors['err'] =  __('Unknown action');
            }
        }
        break;
    }
}

$page='roles.inc.php';
if($role || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'], 'add'))) {
    $page='role.inc.php';
    $ost->addExtraHeader('<meta name="tip-namespace" content="agents.role" />',
        "$('#content').data('tipNamespace', 'agents.role');");
}

$nav->setTabActive('staff');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
