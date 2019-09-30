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
    $errors['err']=sprintf(__('%s: Unknown or invalid'), __('team'));

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$team){
                $errors['err']=sprintf(__('%s: Unknown or invalid'), __('team'));
            }elseif($team->update($_POST,$errors)){
                $msg=sprintf(__('Successfully updated %s.'),
                    __('this team'));
            }elseif(!$errors['err']){
                $errors['err']=sprintf('%s %s',
                    sprintf(__('Unable to update %s.'), __('this team')),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'create':
            $team = Team::create();
            if (($team->update($_POST, $errors))){
                $msg=sprintf(__('Successfully added %s.'),Format::htmlchars($_POST['team']));
                $type = array('type' => 'created');
                Signal::send('object.created', $team, $type);
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']=sprintf('%s %s',
                    sprintf(__('Unable to add %s.'), __('this team')),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err']=sprintf(__('You must select at least %s.'), __('one team'));
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $num = Team::objects()->filter(array(
                            'team_id__in' => $_POST['ids']
                        ))->update(array(
                            'flags' => SqlExpression::bitor(
                                new SqlField('flags'),
                                Team::FLAG_ENABLED
                            )
                        ));

                        if ($num) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully activated %s'),
                                    _N('selected team', 'selected teams', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s activated'), $num, $count,
                                    _N('selected team', 'selected teams', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to activate %s'),
                                _N('selected team', 'selected teams', $count));
                        }
                        break;
                    case 'disable':
                        $num = Team::objects()->filter(array(
                            'team_id__in' => $_POST['ids']
                        ))->update(array(
                            'flags' => SqlExpression::bitand(
                                new SqlField('flags'),
                                ~Team::FLAG_ENABLED
                            )
                        ));

                        if ($num) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully disabled %s'),
                                    _N('selected team', 'selected teams', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s disabled'), $num, $count,
                                    _N('selected team', 'selected teams', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to disable %s'),
                                _N('selected team', 'selected teams', $count));
                        }
                        break;
                    case 'delete':
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=Team::lookup($v))) {
                              $t->delete();
                              $i++;
                            }
                        }
                        if($i && $i==$count)
                            $msg = sprintf(__('Successfully deleted %s.'),
                                _N('selected team', 'selected teams', $count));
                        elseif($i>0)
                            $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                                _N('selected team', 'selected teams', $count));
                        elseif(!$errors['err'])
                            $errors['err'] = sprintf(__('Unable to delete %s.'),
                                _N('selected team', 'selected teams', $count));
                        break;
                    default:
                        $errors['err'] = sprintf('%s - %s', __('Unknown action'), __('Get technical help!'));
                }
            }
            break;
        default:
            $errors['err']=__('Unknown action');
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
