<?php
/*********************************************************************
    groups.php

    User Groups.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');

$group=null;
if($_REQUEST['id'] && !($group=Group::lookup($_REQUEST['id'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('group'));

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$group){
                $errors['err']=sprintf(__('%s: Unknown or invalid'), __('group'));
            }elseif($group->update($_POST,$errors)){
                $msg=sprintf(__('Successfully updated %s'),
                    __('this group'));
            }elseif(!$errors['err']){
                $errors['err']=sprintf(__('Unable to update %s. Correct error(s) below and try again!'),
                    __('this group'));
            }
            break;
        case 'create':
            if(($id=Group::create($_POST,$errors))){
                $msg=sprintf(__('Successfully added %s'),Format::htmlchars($_POST['name']));
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']=sprintf(__('Unable to add %s. Correct error(s) below and try again.'),
                    __('this group'));
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s.'), __('one group'));
            } elseif(in_array($thisstaff->getGroupId(), $_POST['ids'])) {
                $errors['err'] = __("As an admin, you cannot disable/delete a group you belong to - you might lockout all admins!");
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.GROUP_TABLE.' SET group_enabled=1, updated=NOW() '
                            .' WHERE group_id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())){
                            if($num==$count)
                                $msg = sprintf(__('Successfully activated %s'),
                                    _N('selected group', 'selected groups', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s activated'), $num, $count,
                                    _N('selected group', 'selected groups', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to activate %s'),
                                _N('selected group', 'selected groups', $count));
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.GROUP_TABLE.' SET group_enabled=0, updated=NOW() '
                            .' WHERE group_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully disabled %s'),
                                    _N('selected group', 'selected groups', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s disabled'), $num, $count,
                                    _N('selected group', 'selected groups', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to disable %s'),
                                _N('selected group', 'selected groups', $count));
                        }
                        break;
                    case 'delete':
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($g=Group::lookup($v)) && $g->delete())
                                $i++;
                        }

                        if($i && $i==$count)
                            $msg = sprintf(__('Successfully deleted %s'),
                                _N('selected group', 'selected groups', $count));
                        elseif($i>0)
                            $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                                _N('selected group', 'selected groups', $count));
                        elseif(!$errors['err'])
                            $errors['err'] = sprintf(__('Unable to delete %s'),
                                _N('selected group', 'selected groups', $count));
                        break;
                    default:
                        $errors['err']  = __('Unknown action - get technical help.');
                }
            }
            break;
        default:
            $errors['err']=__('Unknown action');
            break;
    }
}

$page='groups.inc.php';
$tip_namespace = 'staff.groups';
if($group || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='group.inc.php';
}

$nav->setTabActive('staff');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
