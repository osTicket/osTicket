<?php
/*********************************************************************
    canned.php

    Canned Responses aka Premade Responses.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('staff.inc.php');
include_once(INCLUDE_DIR.'class.canned.php');

/* check permission */
if(!$thisstaff || !$thisstaff->canManageCannedResponses()) {
    header('Location: kb.php');
    exit;
}

//TODO: Support attachments!

$canned=null;
if($_REQUEST['id'] && !($canned=Canned::lookup($_REQUEST['id'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('canned response'));

$canned_form = new Form(array(
    'attachments' => new FileUploadField(array('id'=>'attach',
        'configuration'=>array('extensions'=>false,
            'size'=>$cfg->getMaxFileSize())
   )),
));

if($_POST && $thisstaff->canManageCannedResponses()) {
    switch(strtolower($_POST['do'])) {
        case 'update':
            if(!$canned) {
                $errors['err']=sprintf(__('%s: Unknown or invalid'), __('canned response'));
            } elseif($canned->update($_POST, $errors)) {
                $msg=sprintf(__('Successfully updated %s'),
                    __('this canned response'));
                //Delete removed attachments.
                //XXX: files[] shouldn't be changed under any circumstances.
                $keepers = $canned_form->getField('attachments')->getClean();
                $attachments = $canned->attachments->getSeparates(); //current list of attachments.
                foreach($attachments as $k=>$file) {
                    if($file['id'] && !in_array($file['id'], $keepers)) {
                        $canned->attachments->delete($file['id']);
                    }
                }

                //Upload NEW attachments IF ANY - TODO: validate attachment types??
                if ($keepers)
                    $canned->attachments->upload($keepers);

                // Attach inline attachments from the editor
                if (isset($_POST['draft_id'])
                        && ($draft = Draft::lookup($_POST['draft_id']))) {
                    $canned->attachments->deleteInlines();
                    $canned->attachments->upload(
                        $draft->getAttachmentIds($_POST['response']),
                        true);
                }

                $canned->reload();

                // XXX: Handle nicely notifying a user that the draft was
                // deleted | OR | show the draft for the user on the name
                // page refresh or a nice bar popup immediately with
                // something like "This page is out-of-date", and allow the
                // user to voluntarily delete their draft
                //
                // Delete drafts for all users for this canned response
                Draft::deleteForNamespace('canned.'.$canned->getId());
            } elseif(!$errors['err']) {
                $errors['err']=sprintf(__('Error updating %s. Try again!'), __('this canned response'));
            }
            break;
        case 'create':
            if(($id=Canned::create($_POST, $errors))) {
                $msg=sprintf(__('Successfully added %s'), Format::htmlchars($_POST['title']));
                $_REQUEST['a']=null;
                //Upload attachments
                $keepers = $canned_form->getField('attachments')->getClean();
                if (($c=Canned::lookup($id)) && $keepers)
                    $c->attachments->upload($keepers);

                // Attach inline attachments from the editor
                if ($c && isset($_POST['draft_id'])
                        && ($draft = Draft::lookup($_POST['draft_id'])))
                    $c->attachments->upload(
                        $draft->getAttachmentIds($_POST['response']), true);

                // Delete this user's drafts for new canned-responses
                Draft::deleteForNamespace('canned', $thisstaff->getId());
            } elseif(!$errors['err']) {
                $errors['err']=sprintf(__('Unable to add %s. Correct error(s) below and try again.'),
                    __('this canned response'));
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err']=sprintf(__('You must select at least %s'), __('one canned response'));
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.CANNED_TABLE.' SET isenabled=1 '
                            .' WHERE canned_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully enabled %s'),
                                    _N('selected canned response', 'selected canned responses', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %s enabled'), $num, $count,
                                    _N('selected canned response', 'selected canned responses', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to enable %s.'),
                                _N('selected canned response', 'selected canned responses', $count));
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.CANNED_TABLE.' SET isenabled=0 '
                            .' WHERE canned_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully disabled %s'),
                                    _N('selected canned response', 'selected canned responses', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %s disabled'), $num, $count,
                                    _N('selected canned response', 'selected canned responses', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to disable %s'),
                                _N('selected canned response', 'selected canned responses', $count));
                        }
                        break;
                    case 'delete':

                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($c=Canned::lookup($v)) && $c->delete())
                                $i++;
                        }

                        if($i==$count)
                            $msg = sprintf(__('Successfully deleted %s'),
                                _N('selected canned response', 'selected canned responses', $count));
                        elseif($i>0)
                            $warn=sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                                _N('selected canned response', 'selected canned responses', $count));
                        elseif(!$errors['err'])
                            $errors['err'] = sprintf(__('Unable to delete %s'),
                                _N('selected canned response', 'selected canned responses', $count));
                        break;
                    default:
                        $errors['err']=__('Unknown command');
                }
            }
            break;
        default:
            $errors['err']=__('Unknown action');
            break;
    }
}

$page='cannedresponses.inc.php';
$tip_namespace = 'knowledgebase.canned_response';
if($canned || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='cannedresponse.inc.php';
}

$nav->setTabActive('kbase');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
print $canned_form->getMedia();
include(STAFFINC_DIR.'footer.inc.php');
?>
