<?php
/*********************************************************************
    templates.php

    Email Templates

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
include_once(INCLUDE_DIR.'class.template.php');
$template=null;
if($_REQUEST['id'] && !($template=Template::lookup($_REQUEST['id'])))
    $errors['err']='Unknown or invalid template ID.';

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'updatetpl':
            if(!$template){
                $errors['err']='Unknown or invalid template';
            }elseif($template->updateMsgTemplate($_POST,$errors)){
                $template->reload();
                $msg='Message template updated successfully';
            }elseif(!$errors['err']){
                $errors['err']='Error updating message template. Try again!';
            }
            break;
        case 'update':
            if(!$template){
                $errors['err']='Unknown or invalid template';
            }elseif($template->update($_POST,$errors)){
                $msg='Template updated successfully';
            }elseif(!$errors['err']){
                $errors['err']='Error updating template. Try again!';
            }
            break;
        case 'add':
            if((Template::create($_POST,$errors))){
                $msg='Template added successfully';
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']='Unable to add template. Correct error(s) below and try again.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err']='You must select at least one template to process.';
            }else{
                $count=count($_POST['ids']);
                if($_POST['enable']){
                    $sql='UPDATE '.EMAIL_TEMPLATE_TABLE.' SET isactive=1 WHERE tpl_id IN ('.
                        implode(',', db_input($_POST['ids'])).')';
                    if(db_query($sql) && ($num=db_affected_rows())){
                        if($num==$count)
                            $msg='Selected templates enabled';
                        else
                            $warn="$num of $count selected templates enabled";
                    }else{
                        $errors['err']='Unable to enable selected templates';
                    }
                }elseif($_POST['disable']){

                    $i=0;
                    foreach($_POST['ids'] as $k=>$v) {
                        if(($t=Template::lookup($v)) && !$t->isInUse() && $t->disable())
                            $i++;
                    }

                    if($i && $i==$count)
                        $msg='Selected templates disabled';
                    elseif($i)
                        $warn="$i of $count selected templates disabled (in-use templates can't be disabled)";
                    else
                        $errors['err']="Unable to disable selected templates (in-use or default template can't be disabled)";
                }elseif($_POST['delete']){
                    $i=0;
                    foreach($_POST['ids'] as $k=>$v) {
                        if(($t=Template::lookup($v)) && $t->delete())
                            $i++;
                    }

                    if($i && $i==$count)
                        $msg='Selected templates deleted successfully';
                    elseif($i>0)
                        $warn="$i of $count selected templates deleted";
                    elseif(!$errors['err'])
                        $errors['err']='Unable to delete selected templates';
                    
                }else {
                    $errors['err']='Unknown template action';
                }
            }
            break;
        default:
            $errors['err']='Unknown action';
            break;
    }
}

$page='templates.inc.php';
if($template && !strcasecmp($_REQUEST['a'],'manage')){
    $page='tpl.inc.php';
}elseif($template || !strcasecmp($_REQUEST['a'],'add')){
    $page='template.inc.php';
}

$nav->setTabActive('emails');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
