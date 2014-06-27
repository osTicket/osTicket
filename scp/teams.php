<?php
/*********************************************************************
    teams.php

    Evertything about teams

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');

$team=null;
if($_REQUEST['id'] && !($team=Team::lookup($_REQUEST['id'])))
    $errors['err']=__('Unknown or invalid team.');

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$team){
                $errors['err']=__('Unknown or invalid team.');
            }elseif($team->update($_POST,$errors)){
                $msg=__('Team updated successfully');
            }elseif(!$errors['err']){
                $errors['err']=__('Unable to update team. Correct any error(s) below and try again.');
            }
            break;
        case 'create':
            if(($id=Team::create($_POST,$errors))){
                $msg=sprintf(__('%s added successfully'),Format::htmlchars($_POST['team']));
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']=__('Unable to add team. Correct any error(s) below and try again.');
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err']=__('You must select at least one team.');
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.TEAM_TABLE.' SET isenabled=1 '
                            .' WHERE team_id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = __('Selected teams activated');
                            else
                                $warn = sprintf(__('%1$d of %2$d selected teams activated'), $num, $count);
                        } else {
                            $errors['err'] = __('Unable to activate selected teams');
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.TEAM_TABLE.' SET isenabled=0 '
                            .' WHERE team_id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = __('Selected teams disabled');
                            else
                                $warn = sprintf(__('%1$d of %2$d selected teams disabled'), $num, $count);
                        } else {
                            $errors['err'] = __('Unable to disable selected teams');
                        }
                        break;
                    case 'delete':
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=Team::lookup($v)) && $t->delete())
                                $i++;
                        }
                        if($i && $i==$count)
                            $msg = __('Selected teams deleted successfully');
                        elseif($i>0)
                            $warn = sprintf(__('%1$d of %2$d selected teams deleted'), $i, $count);
                        elseif(!$errors['err'])
                            $errors['err'] = __('Unable to delete selected teams');
                        break;
                    default:
                        $errors['err'] = __('Unknown action. Get technical help!');
                }
            }
            break;
        default:
            $errors['err']=__('Unknown action/command');
            break;
    }
}

$page='teams.inc.php';
$tip_namespace = 'staff.team';
if($team || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='team.inc.php';
}

$nav->setTabActive('staff');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
