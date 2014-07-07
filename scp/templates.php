<?php
/*********************************************************************
    templates.php

    Email Templates

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
include_once(INCLUDE_DIR.'class.template.php');

$template=null;
if($_REQUEST['tpl_id'] &&
        !($template=EmailTemplateGroup::lookup($_REQUEST['tpl_id'])))
    $errors['err']=__('Unknown or invalid template set.');
elseif($_REQUEST['id'] &&
        !($template=EmailTemplate::lookup($_REQUEST['id'])))
    $errors['err']=__('Unknown or invalid template.');
elseif($_REQUEST['default_for']) {
    $sql = 'SELECT id FROM '.EMAIL_TEMPLATE_TABLE
        .' WHERE tpl_id='.db_input($cfg->getDefaultTemplateId())
        .' AND code_name='.db_input($_REQUEST['default_for']);
    if ($id = db_result(db_query($sql)))
        Http::redirect('templates.php?a=manage&id='.db_input($id));
}

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'updatetpl':
            if(!$template){
                $errors['err']=__('Unknown or invalid template.');
            }elseif($template->update($_POST,$errors)){
                $msg=__('Message template updated successfully');
                // Drop drafts for this template for ALL users
                Draft::deleteForNamespace('tpl.'.$template->getCodeName()
                    .'.'.$template->getTplId());
            }elseif(!$errors['err']){
                $errors['err']=__('Error updating message template. Try again!');
            }
            break;
        case 'implement':
            if(!$template){
                $errors['err']=__('Unknown or invalid template');
            }elseif($new = EmailTemplate::add($_POST,$errors)){
                $template = $new;
                $msg=__('Message template updated successfully');
                // Drop drafts for this user for this template
                Draft::deleteForNamespace('tpl.'.$new->getCodeName()
                    .$new->getTplId(), $thisstaff->getId());
            }elseif(!$errors['err']){
                $errors['err']=__('Error updating message template. Try again!');
            }
            break;
        case 'update':
            if(!$template){
                $errors['err']=__('Unknown or invalid template');
            }elseif($template->update($_POST,$errors)){
                $msg=__('Template updated successfully');
            }elseif(!$errors['err']){
                $errors['err']=__('Error updating template. Try again!');
            }
            break;
        case 'add':
            if(($new=EmailTemplateGroup::add($_POST,$errors))){
                $template=$new;
                $msg=__('Template added successfully');
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']=__('Unable to add template. Correct error(s) below and try again.');
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err']=__('You must select at least one template to process.');
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.EMAIL_TEMPLATE_GRP_TABLE.' SET isactive=1 '
                            .' WHERE tpl_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())){
                            if($num==$count)
                                $msg = __('Selected templates enabled');
                            else
                                $warn = sprintf(__('%1$d of %2$d selected templates enabled'), $num, $count);
                        } else {
                            $errors['err'] = __('Unable to enable selected templates');
                        }
                        break;
                    case 'disable':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=EmailTemplateGroup::lookup($v)) && !$t->isInUse() && $t->disable())
                                $i++;
                        }
                        if($i && $i==$count)
                            $msg = __('Selected templates disabled');
                        elseif($i)
                            $warn = sprintf(__('%1$d of %2$d selected template sets disabled (in-use templates cannot be disabled)'), $i, $count);
                        else
                            $errors['err'] = __("Unable to disable selected template sets (in-use or default template cannot be disabled)");
                        break;
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=EmailTemplateGroup::lookup($v)) && !$t->isInUse() && $t->delete())
                                $i++;
                        }

                        if($i && $i==$count)
                            $msg = __('Selected templates deleted successfully');
                        elseif($i>0)
                            $warn = sprintf(__('%1$d of %2$d selected templates deleted'), $i, $count);
                        elseif(!$errors['err'])
                            $errors['err'] = __('Unable to delete selected templates');
                        break;
                    default:
                        $errors['err']=__('Unknown template action');
                }
            }
            break;
        default:
            $errors['err']=__('Unknown action');
            break;
    }
}

$page='templates.inc.php';
$tip_namespace = 'emails.template';
if($template && !strcasecmp($_REQUEST['a'],'manage')){
    $page='tpl.inc.php';
}elseif($template && !strcasecmp($_REQUEST['a'],'implement')){
    $page='tpl.inc.php';
}elseif($template || !strcasecmp($_REQUEST['a'],'add')){
    $page='template.inc.php';
}

$nav->setTabActive('emails');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
