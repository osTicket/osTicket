<?php
/*********************************************************************
    departments.php

    Departments

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');

$dept=null;
if($_REQUEST['id'] && !($dept=Dept::lookup($_REQUEST['id'])))
    $errors['err']=__('Unknown or invalid department ID.');

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$dept){
                $errors['err']=__('Unknown or invalid department.');
            }elseif($dept->update($_POST,$errors)){
                $msg=__('Department updated successfully');
            }elseif(!$errors['err']){
                $errors['err']=__('Error updating department. Try again!');
            }
            break;
        case 'create':
            if(($id=Dept::create($_POST,$errors))){
                $msg=sprintf(__('%s added successfully'),Format::htmlchars($_POST['name']));
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']=__('Unable to add department. Correct error(s) below and try again.');
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = __('You must select at least one department');
            }elseif(in_array($cfg->getDefaultDeptId(),$_POST['ids'])) {
                $errors['err'] = __('You can not disable/delete a default department. Select a new default department and try again.');
            }else{
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'make_public':
                        $sql='UPDATE '.DEPT_TABLE.' SET ispublic=1 '
                            .' WHERE dept_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())){
                            if($num==$count)
                                $msg=__('Selected departments made public');
                            else
                                $warn=sprintf(__('%1$d of %2$d selected departments made public'), $num, $count);
                        } else {
                            $errors['err']=__('Unable to make selected department public.');
                        }
                        break;
                    case 'make_private':
                        $sql='UPDATE '.DEPT_TABLE.' SET ispublic=0  '
                            .' WHERE dept_id IN ('.implode(',', db_input($_POST['ids'])).') '
                            .' AND dept_id!='.db_input($cfg->getDefaultDeptId());
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = __('Selected departments made private');
                            else
                                $warn = sprintf(__('%1$d of %2$d selected departments made private'), $num, $count);
                        } else {
                            $errors['err'] = __('Unable to make selected department(s) private. Possibly already private!');
                        }
                        break;
                    case 'delete':
                        //Deny all deletes if one of the selections has members in it.
                        $sql='SELECT count(staff_id) FROM '.STAFF_TABLE
                            .' WHERE dept_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        list($members)=db_fetch_row(db_query($sql));
                        if($members)
                            $errors['err']=__('Departments with agents can not be deleted. Move the agents first.');
                        else {
                            $i=0;
                            foreach($_POST['ids'] as $k=>$v) {
                                if($v!=$cfg->getDefaultDeptId() && ($d=Dept::lookup($v)) && $d->delete())
                                    $i++;
                            }
                            if($i && $i==$count)
                                $msg = __('Selected departments deleted successfully');
                            elseif($i>0)
                                $warn = sprintf(__('%1$d of %2$d selected departments deleted'), $i, $count);
                            elseif(!$errors['err'])
                                $errors['err'] = __('Unable to delete selected departments.');
                        }
                        break;
                    default:
                        $errors['err']=__('Unknown action - get technical help.');
                }
            }
            break;
        default:
            $errors['err']=__('Unknown action/command');
            break;
    }
}

$page='departments.inc.php';
$tip_namespace = 'staff.department';
if($dept || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='department.inc.php';
}

$nav->setTabActive('staff');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
