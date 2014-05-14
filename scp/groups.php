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
    $errors['err']='Unknown or invalid group ID.';

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$group){
                $errors['err']='Unknown or invalid group.';
            }elseif($group->update($_POST,$errors)){
                $msg='Group updated successfully';
            }elseif(!$errors['err']){
                $errors['err']='Unable to update group. Correct any error(s) below and try again!';
            }
            break;
        case 'create':
            if(($id=Group::create($_POST,$errors))){
                $msg=Format::htmlchars($_POST['name']).' added successfully';
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']='Unable to add group. Correct error(s) below and try again.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'You must select at least one group.';
            } elseif(in_array($thisstaff->getGroupId(), $_POST['ids'])) {
                $errors['err'] = "As an admin, you can't disable/delete a group you belong to - you might lockout all admins!";
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.GROUP_TABLE.' SET group_enabled=1, updated=NOW() '
                            .' WHERE group_id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())){
                            if($num==$count)
                                $msg = 'Selected groups activated';
                            else
                                $warn = "$num of $count selected groups activated";
                        } else {
                            $errors['err'] = 'Unable to activate selected groups';
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.GROUP_TABLE.' SET group_enabled=0, updated=NOW() '
                            .' WHERE group_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Selected groups disabled';
                            else
                                $warn = "$num of $count selected groups disabled";
                        } else {
                            $errors['err'] = 'Unable to disable selected groups';
                        }
                        break;
                    case 'delete':
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($g=Group::lookup($v)) && $g->delete())
                                $i++;
                        }

                        if($i && $i==$count)
                            $msg = 'Selected groups deleted successfully';
                        elseif($i>0)
                            $warn = "$i of $count selected groups deleted";
                        elseif(!$errors['err'])
                            $errors['err'] = 'Unable to delete selected groups';
                        break;
                    default:
                        $errors['err']  = 'Unknown action. Get technical help!';
                }
            }
            break;
        default:
            $errors['err']='Unknown action';
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
