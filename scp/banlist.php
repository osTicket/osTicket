<?php
/*********************************************************************
    banlist.php

    List of banned email addresses

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
include_once(INCLUDE_DIR.'class.banlist.php');

/* Get the system ban list filter */
if(!($filter=Banlist::getFilter())) 
    $warn='System ban list is empty.';
elseif(!$filter->isActive())
    $warn='SYSTEM BAN LIST filter is <b>DISABLED</b> - <a href="filters.php">enable here</a>.'; 
 
$rule=null; //ban rule obj.
if($filter && $_REQUEST['id'] && !($rule=$filter->getRule($_REQUEST['id'])))
    $errors['err']='Unknown or invalid ban list ID #';

if($_POST && !$errors && $filter){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$rule){
                $errors['err']='Unknown or invalid ban rule.';
            }elseif(!$_POST['val'] || !Validator::is_email($_POST['val'])){
                $errors['err']=$errors['val']='Valid email address required';
            }elseif(!$errors){
                $vars=array('w'=>'email',
                            'h'=>'equal',
                            'v'=>$_POST['val'],
                            'filter_id'=>$filter->getId(),
                            'isactive'=>$_POST['isactive'],
                            'notes'=>$_POST['notes']);
                if($rule->update($vars,$errors)){
                    $msg='Email updated successfully';
                }elseif(!$errors['err']){
                    $errors['err']='Error updating ban rule. Try again!';
                }
            }
            break;
        case 'add':
            if(!$filter) {
                $errors['err']='Unknown or invalid ban list';
            }elseif(!$_POST['val'] || !Validator::is_email($_POST['val'])) {
                $errors['err']=$errors['val']='Valid email address required';
            }elseif(BanList::includes($_POST['val'])) {
                $errors['err']=$errors['val']='Email already in the ban list';
            }elseif($filter->addRule('email','equal',$_POST['val'],array('isactive'=>$_POST['isactive'],'notes'=>$_POST['notes']))) {
                $msg='Email address added to ban list successfully';
                $_REQUEST['a']=null;
                //Add filter rule here.
            }elseif(!$errors['err']){
                $errors['err']='Error creating ban rule. Try again!';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err']='You must select at least one email to process.';
            }else{
                $count=count($_POST['ids']);
                if($_POST['enable']){
                    $sql='UPDATE '.EMAIL_FILTER_RULE_TABLE.' SET isactive=1 WHERE filter_id='.db_input($filter->getId()).
                         ' AND id IN ('.implode(',',$_POST['ids']).')';
                    if(db_query($sql) && ($num=db_affected_rows())){
                        if($num==$count)
                            $msg='Selected emails ban status set to enabled';
                        else
                            $warn="$num of $count selected emails enabled";
                    }else{
                        $errors['err']='Unable to enable selected emails';
                    }
                }elseif($_POST['disable']){
                    $sql='UPDATE '.EMAIL_FILTER_RULE_TABLE.' SET isactive=0  WHERE filter_id='.db_input($filter->getId()).
                         ' AND id IN ('.implode(',',$_POST['ids']).')';
                    if(db_query($sql) && ($num=db_affected_rows())) {
                        if($num==$count)
                            $msg='Selected emails ban status set to disabled';
                        else
                            $warn="$num of $count selected emails ban status set to disabled";
                    }else{
                        $errors['err']='Unable to disable selected emails';
                    }
                }elseif($_POST['delete']){
                    $i=0;
                    foreach($_POST['ids'] as $k=>$v) {
                        if(($r=FilterRule::lookup($v)) && $r->delete())
                            $i++;
                    }
                    if($i && $i==$count)
                        $msg='Selected emailes deleted successfully';
                    elseif($i>0)
                        $warn="$i of $count selected emails deleted";
                    elseif(!$errors['err'])
                        $errors['err']='Unable to delete selected emails';
                    
                }else{
                    $errors['err']='Unknown action';
                }
            }
            break;
        default:
            $errors['err']='Unknown action';
            break;
    }
}

$page='banlist.inc.php';
if(!$filter || ($rule || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))))
    $page='banrule.inc.php';

$nav->setTabActive('emails');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
