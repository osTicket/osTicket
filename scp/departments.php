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
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('department'));

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$dept){
                $errors['err']=sprintf(__('%s: Unknown or invalid'), __('department'));
            }elseif($dept->update($_POST,$errors)){
                $msg=sprintf(__('Successfully updated %s.'),
                    __('this department'));
            }elseif(!$errors['err']){
                $errors['err'] = sprintf('%s %s',
                    sprintf(__('Unable to update %s.'), __('this department')),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'create':
            $_dept = Dept::create();
            if(($_dept->update($_POST,$errors))){
                $msg=sprintf(__('Successfully added %s.'),Format::htmlchars($_POST['name']));
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']=sprintf('%s %s',
                    sprintf(__('Unable to add %s.'), __('this department')),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s.'),
                    __('one department'));
            }elseif(in_array($cfg->getDefaultDeptId(),$_POST['ids'])) {
                $errors['err'] = __('You cannot disable/delete a default department. Select a new default department and try again.');
            }else{
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'make_public':
                        $sql='UPDATE '.DEPT_TABLE.' SET ispublic=1 '
                            .' WHERE dept_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())){
                            if($num==$count)
                                $msg=sprintf(__('Successfully made %s PUBLIC'),
                                    _N('selected department', 'selected departments', $count));
                            else
                                $warn=sprintf(__(
                                    /* Phrase will read:
                                       <a> of <b> <selected objects> made PUBLIC */
                                    '%1$d of %2$d %3$s made PUBLIC'), $num, $count,
                                    _N('selected department', 'selected departments', $count));
                        } else {
                            $errors['err']=sprintf(__('Unable to make %s PUBLIC.'),
                                _N('selected department', 'selected departments', $count));
                        }
                        break;
                    case 'make_private':
                        $sql='UPDATE '.DEPT_TABLE.' SET ispublic=0  '
                            .' WHERE dept_id IN ('.implode(',', db_input($_POST['ids'])).') '
                            .' AND dept_id!='.db_input($cfg->getDefaultDeptId());
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully made %s PRIVATE'),
                                    _N('selected department', 'selected epartments', $count));
                            else
                                $warn = sprintf(__(
                                    /* Phrase will read:
                                       <a> of <b> <selected objects> made PRIVATE */
                                    '%1$d of %2$d %3$s made PRIVATE'), $num, $count,
                                    _N('selected department', 'selected departments', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to make %s private. Possibly already private!'),
                                _N('selected department', 'selected departments', $count));
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
                                $msg = sprintf(__('Successfully deleted %s.'),
                                    _N('selected department', 'selected departments', $count));
                            elseif($i>0)
                                $warn = sprintf(__(
                                    /* Phrase will read:
                                       <a> of <b> <selected objects> deleted */
                                    '%1$d of %2$d %3$s deleted'), $i, $count,
                                    _N('selected department', 'selected departments', $count));
                            elseif(!$errors['err'])
                                $errors['err'] = sprintf(__('Unable to delete %s.'),
                                    _N('selected department', 'selected departments', $count));
                        }
                        break;
                    default:
                        $errors['err']=__('Unknown action - get technical help.');
                }
            }
            break;
        default:
            $errors['err']=__('Unknown action');
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
