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
    $errors['err']='Unknown or invalid team ID.';

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$team){
                $errors['err']='Unknown or invalid team.';
            }elseif($team->update($_POST,$errors)){
                $msg='Team updated successfully';
            }elseif(!$errors['err']){
                $errors['err']='Unable to update team. Correct any error(s) below and try again!';
            }
            break;
        case 'create':
            if(($id=Team::create($_POST,$errors))){
                $msg=Format::htmlchars($_POST['team']).' added successfully';
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']='Unable to add team. Correct any error(s) below and try again.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err']='You must select at least one team.';
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.TEAM_TABLE.' SET isenabled=1 '
                            .' WHERE team_id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Selected teams activated';
                            else
                                $warn = "$num of $count selected teams activated";
                        } else {
                            $errors['err'] = 'Unable to activate selected teams';
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.TEAM_TABLE.' SET isenabled=0 '
                            .' WHERE team_id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Selected teams disabled';
                            else
                                $warn = "$num of $count selected teams disabled";
                        } else {
                            $errors['err'] = 'Unable to disable selected teams';
                        }
                        break;
                    case 'delete':
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=Team::lookup($v)) && $t->delete())
                                $i++;
                        }
                        if($i && $i==$count)
                            $msg = 'Selected teams deleted successfully';
                        elseif($i>0)
                            $warn = "$i of $count selected teams deleted";
                        elseif(!$errors['err'])
                            $errors['err'] = 'Unable to delete selected teams';
                        break;
                    default:
                        $errors['err'] = 'Unknown action. Get technical help!';
                }
            }
            break;
        default:
            $errors['err']='Unknown action/command';
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
