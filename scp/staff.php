<?php
/*********************************************************************
    staff.php

    Evertything about staff members.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');

$staff=null;
if($_REQUEST['id'] && !($staff=Staff::lookup($_REQUEST['id'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('agent'));

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$staff){
                $errors['err']=sprintf(__('%s: Unknown or invalid'), __('agent'));
            }elseif($staff->update($_POST,$errors)){
                $msg=sprintf(__('Successfully updated %s'),
                    __('this agent'));
            }elseif(!$errors['err']){
                $errors['err']=sprintf(__('Unable to update %s. Correct error(s) below and try again!'),
                    __('this agent'));
            }
            break;
        case 'create':
            if(($id=Staff::create($_POST,$errors))){
                $msg=sprintf(__('Successfully added %s'),Format::htmlchars($_POST['firstname']));
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']=sprintf(__('Unable to add %s. Correct error(s) below and try again.'),
                    __('this agent'));
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s.'),
                    __('one agent'));
            } elseif(in_array($thisstaff->getId(),$_POST['ids'])) {
                $errors['err'] = __('You can not disable/delete yourself - you could be the only admin!');
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.STAFF_TABLE.' SET isactive=1 '
                            .' WHERE staff_id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = sprintf('Successfully activated %s',
                                    _N('selected agent', 'selected agents', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s activated'), $num, $count,
                                    _N('selected agent', 'selected agents', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to activate %s'),
                                _N('selected agent', 'selected agents', $count));
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.STAFF_TABLE.' SET isactive=0 '
                            .' WHERE staff_id IN ('.implode(',', db_input($_POST['ids'])).') AND staff_id!='.db_input($thisstaff->getId());

                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = sprintf('Successfully disabled %s',
                                    _N('selected agent', 'selected agents', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s disabled'), $num, $count,
                                    _N('selected agent', 'selected agents', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to disable %s'),
                                _N('selected agent', 'selected agents', $count));
                        }
                        break;
                    case 'delete':
                        foreach($_POST['ids'] as $k=>$v) {
                            if($v!=$thisstaff->getId() && ($s=Staff::lookup($v)) && $s->delete())
                                $i++;
                        }

                        if($i && $i==$count)
                            $msg = sprintf(__('Successfully deleted %s'),
                                _N('selected agent', 'selected agents', $count));
                        elseif($i>0)
                            $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                                _N('selected agent', 'selected agents', $count));
                        elseif(!$errors['err'])
                            $errors['err'] = sprintf(__('Unable to delete %s'),
                                _N('selected agent', 'selected agents', $count));
                        break;
                    default:
                        $errors['err'] = __('Unknown action - get technical help.');
                }

            }
            break;
        default:
            $errors['err']=__('Unknown action');
            break;
    }
}

$page='staffmembers.inc.php';
$tip_namespace = 'staff.agent';
if($staff || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='staff.inc.php';
}

$nav->setTabActive('staff');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
