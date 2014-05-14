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
    $errors['err']='Unknown or invalid department ID.';

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$dept){
                $errors['err']='Unknown or invalid department.';
            }elseif($dept->update($_POST,$errors)){
                $msg='Department updated successfully';
            }elseif(!$errors['err']){
                $errors['err']='Error updating department. Try again!';
            }
            break;
        case 'create':
            if(($id=Dept::create($_POST,$errors))){
                $msg=Format::htmlchars($_POST['name']).' added successfully';
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']='Unable to add department. Correct error(s) below and try again.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'You must select at least one department';
            }elseif(in_array($cfg->getDefaultDeptId(),$_POST['ids'])) {
                $errors['err'] = 'You can not disable/delete a default department. Remove default Dept. and try again.';
            }else{
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'make_public':
                        $sql='UPDATE '.DEPT_TABLE.' SET ispublic=1 '
                            .' WHERE dept_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())){
                            if($num==$count)
                                $msg='Selected departments made public';
                            else
                                $warn="$num of $count selected departments made public";
                        } else {
                            $errors['err']='Unable to make selected department public.';
                        }
                        break;
                    case 'make_private':
                        $sql='UPDATE '.DEPT_TABLE.' SET ispublic=0  '
                            .' WHERE dept_id IN ('.implode(',', db_input($_POST['ids'])).') '
                            .' AND dept_id!='.db_input($cfg->getDefaultDeptId());
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Selected departments made private';
                            else
                                $warn = "$num of $count selected departments made private";
                        } else {
                            $errors['err'] = 'Unable to make selected department(s) private. Possibly already private!';
                        }
                        break;
                    case 'delete':
                        //Deny all deletes if one of the selections has members in it.
                        $sql='SELECT count(staff_id) FROM '.STAFF_TABLE
                            .' WHERE dept_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        list($members)=db_fetch_row(db_query($sql));
                        if($members)
                            $errors['err']='Departments with staff can not be deleted. Move staff first.';
                        else {
                            $i=0;
                            foreach($_POST['ids'] as $k=>$v) {
                                if($v!=$cfg->getDefaultDeptId() && ($d=Dept::lookup($v)) && $d->delete())
                                    $i++;
                            }
                            if($i && $i==$count)
                                $msg = 'Selected departments deleted successfully';
                            elseif($i>0)
                                $warn = "$i of $count selected departments deleted";
                            elseif(!$errors['err'])
                                $errors['err'] = 'Unable to delete selected departments.';
                        }
                        break;
                    default:
                        $errors['err']='Unknown action - get technical help';
                }
            }
            break;
        default:
            $errors['err']='Unknown action/command';
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
