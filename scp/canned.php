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
    $errors['err']='Identifiant de réponse pré-remplie inconnu ou invalide.';

if($_POST && $thisstaff->canManageCannedResponses()) {
    switch(strtolower($_POST['do'])) {
        case 'update':
            if(!$canned) {
                $errors['err']='Réponse pré-remplie inconnue ou invalide.';
            } elseif($canned->update($_POST, $errors)) {
                $msg='Réponse pré-remplie mise à jour avec succès';
                //Delete removed attachments.
                //XXX: files[] shouldn't be changed under any circumstances.
                $keepers = $_POST['files']?$_POST['files']:array();
                $attachments = $canned->attachments->getSeparates(); //current list of attachments.
                foreach($attachments as $k=>$file) {
                    if($file['id'] && !in_array($file['id'], $keepers)) {
                        $canned->attachments->delete($file['id']);
                    }
                }
                //Upload NEW attachments IF ANY - TODO: validate attachment types??
                if($_FILES['attachments'] && ($files=AttachmentFile::format($_FILES['attachments'])))
                    $canned->attachments->upload($files);

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
                $errors['err']='Erreur lors de la mise à jour de la éponse pré-remplie. Essayez encore !';
            }
            break;
        case 'create':
            if(($id=Canned::create($_POST, $errors))) {
                $msg='Réponse pré-remplie ajoutée avec succès';
                $_REQUEST['a']=null;
                //Upload attachments
                if($_FILES['attachments'] && ($c=Canned::lookup($id)) && ($files=AttachmentFile::format($_FILES['attachments'])))
                    $c->attachments->upload($files);

                // Attach inline attachments from the editor
                if (isset($_POST['draft_id'])
                        && ($draft = Draft::lookup($_POST['draft_id'])))
                    $c->attachments->upload(
                        $draft->getAttachmentIds($_POST['response']), true);

                // Delete this user's drafts for new canned-responses
                Draft::deleteForNamespace('canned', $thisstaff->getId());
            } elseif(!$errors['err']) {
                $errors['err']='Impossible d\'ajouter une réponse pré-remplie. Corrigez les erreurs ci-dessous et essayez encore.';
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err']='Vous devez sélectionner au moins une réponse pré-remplie';
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.CANNED_TABLE.' SET isenabled=1 '
                            .' WHERE canned_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Réponses pré-remplies sélectionnées activées';
                            else
                                $warn = "$num réponses pré-remplies sur $count sélectionnées activées";
                        } else {
                            $errors['err'] = 'Impossible d\'activer les réponses pré-remplies sélectionnées.';
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.CANNED_TABLE.' SET isenabled=0 '
                            .' WHERE canned_id IN ('.implode(',', db_input($_POST['ids'])).')';
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = 'Réponses pré-remplies sélectionnées désactivées';
                            else
                                $warn = "$num réponses pré-remplies sur $count sélectionnées désactivées";
                        } else {
                            $errors['err'] = 'Impossible de désactiver les réponses pré-remplies sélectionnées.';
                        }
                        break;
                    case 'delete':

                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($c=Canned::lookup($v)) && $c->delete())
                                $i++;
                        }

                        if($i==$count)
                            $msg = 'Réponses pré-remplies sélectionnées supprimées';
                        elseif($i>0)
                            $warn = "$i réponses pré-remplies sur $count sélectionnées supprimées";
                        elseif(!$errors['err'])
                            $errors['err'] = 'Impossible de supprimer les réponses pré-remplies sélectionnées.';
                        break;
                    default:
                        $errors['err']='Commande inconnue';
                }
            }
            break;
        default:
            $errors['err']='Action inconnue';
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
include(STAFFINC_DIR.'footer.inc.php');
?>
