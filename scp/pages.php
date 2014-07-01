<?php
/*********************************************************************
    pages.php

    Site pages.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
require_once(INCLUDE_DIR.'class.page.php');

$page = null;
if($_REQUEST['id'] && !($page=Page::lookup($_REQUEST['id'])))
   $errors['err']='Unknown or invalid page';

if($_POST) {
    switch(strtolower($_POST['do'])) {
        case 'add':
            if(($pageId=Page::create($_POST, $errors))) {
                $_REQUEST['a'] = null;
                $msg='Page added successfully';
                // Attach inline attachments from the editor
                if (isset($_POST['draft_id'])
                        && ($draft = Draft::lookup($_POST['draft_id']))
                        && ($page = Page::lookup($pageId)))
                    $page->attachments->upload(
                        $draft->getAttachmentIds($_POST['response']), true);
                Draft::deleteForNamespace('page');
            } elseif(!$errors['err'])
                $errors['err'] = 'Unable to add page. Try again!';
        break;
        case 'update':
            if(!$page)
                $errors['err'] = 'Invalid or unknown page';
            elseif($page->update($_POST, $errors)) {
                $msg='Page updated successfully';
                $_REQUEST['a']=null; //Go back to view
                // Attach inline attachments from the editor
                if (isset($_POST['draft_id'])
                        && ($draft = Draft::lookup($_POST['draft_id']))) {
                    $page->attachments->deleteInlines();
                    $page->attachments->upload(
                        $draft->getAttachmentIds($_POST['response']),
                        true);
                }
                Draft::deleteForNamespace('page.'.$page->getId());
            } elseif(!$errors['err'])
                $errors['err'] = 'Unable to update page. Try again!';
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = 'You must select at least one page.';
            } elseif(array_intersect($_POST['ids'], $cfg->getDefaultPages()) && strcasecmp($_POST['a'], 'enable')) {
                 $errors['err'] = 'One or more of the selected pages is in-use and CANNOT be disabled/deleted.';
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.PAGE_TABLE.' SET isactive=1 '
                            .' WHERE id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Selected pages enabled';
                            else
                                $warn = "$num of $count selected pages enabled";
                        } else {
                            $errors['err'] = 'Unable to enable selected pages';
                        }
                        break;
                    case 'disable':
                        $i = 0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($p=Page::lookup($v)) && $p->disable())
                                $i++;
                        }

                        if($i && $i==$count)
                            $msg = 'Selected pages disabled';
                        elseif($i>0)
                            $warn = "$num of $count selected pages disabled";
                        elseif(!$errors['err'])
                            $errors['err'] = 'Unable to disable selected pages';
                        break;
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($p=Page::lookup($v)) && $p->delete())
                                $i++;
                        }

                        if($i && $i==$count)
                            $msg = 'Selected pages deleted successfully';
                        elseif($i>0)
                            $warn = "$i of $count selected pages deleted";
                        elseif(!$errors['err'])
                            $errors['err'] = 'Unable to delete selected pages';
                        break;
                    default:
                        $errors['err']='Unknown action - get technical help.';
                }
            }
            break;
        default:
            $errors['err']='Unknown action/command';
            break;
    }
}

$inc='pages.inc.php';
$tip_namespace = 'manage.pages';
if($page || $_REQUEST['a']=='add') {
    $inc='page.inc.php';
}

$nav->setTabActive('manage');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require_once(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.$inc);
require_once(STAFFINC_DIR.'footer.inc.php');
?>
