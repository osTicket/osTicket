<?php
/*************************************************************************
    tickets.php

    Handles all tickets related actions.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require('staff.inc.php');
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');
require_once(INCLUDE_DIR.'class.filter.php');
require_once(INCLUDE_DIR.'class.canned.php');
require_once(INCLUDE_DIR.'class.json.php');
require_once(INCLUDE_DIR.'class.dynamic_forms.php');


$page='';
$ticket = $user = null; //clean start.
//LOCKDOWN...See if the id provided is actually valid and if the user has access.
if($_REQUEST['id']) {
    if(!($ticket=Ticket::lookup($_REQUEST['id'])))
         $errors['err']='Identifiant de ticket inconnu ou invalide';
    elseif(!$ticket->checkStaffAccess($thisstaff)) {
        $errors['err']='Accès refusé. Contactez l\'administrateur si vous croyez que c\'est une erreur';
        $ticket=null; //Clear ticket obj.
    }
}

//Lookup user if id is available.
if ($_REQUEST['uid'])
    $user = User::lookup($_REQUEST['uid']);

//At this stage we know the access status. we can process the post.
if($_POST && !$errors):

    if($ticket && $ticket->getId()) {
        //More coffee please.
        $errors=array();
        $lock=$ticket->getLock(); //Ticket lock if any
        $statusKeys=array('open'=>'Ouvert','Reopen'=>'Ouvert','Close'=>'Clos');
        switch(strtolower($_POST['a'])):
        case 'reply':
            if(!$thisstaff->canPostReply())
                $errors['err'] = 'Action refusée. Contactez l\'administrateur pour avoir l\'accès';
            else {

                if(!$_POST['response'])
                    $errors['response']='Réponse requise';
                //Use locks to avoid double replies
                if($lock && $lock->getStaffId()!=$thisstaff->getId())
                    $errors['err']='Action Refusée. Le ticket est verrouillé par quelqu\'un d\'autre !';

                //Make sure the email is not banned
                if(!$errors['err'] && TicketFilter::isBanned($ticket->getEmail()))
                    $errors['err']='L\'adresse de courriel est dans la liste de bannissement. Elle doit en être retirée pour répondre.';
            }

            $wasOpen =($ticket->isOpen());

            //If no error...do the do.
            $vars = $_POST;
            if(!$errors && $_FILES['attachments'])
                $vars['files'] = AttachmentFile::format($_FILES['attachments']);

            if(!$errors && ($response=$ticket->postReply($vars, $errors, $_POST['emailreply']))) {
                $msg='Réponse envoyée avec succès';
                $ticket->reload();

                if($ticket->isClosed() && $wasOpen)
                    $ticket=null;
                else
                    // Still open -- cleanup response draft for this user
                    Draft::deleteForNamespace(
                        'ticket.response.' . $ticket->getId(),
                        $thisstaff->getId());

            } elseif(!$errors['err']) {
                $errors['err']='Impossible d\'envoyer la réponse. Corrigez les erreurs ci-dessous et essayez encore !';
            }
            break;
        case 'transfer': /** Transfer ticket **/
            //Check permission
            if(!$thisstaff->canTransferTickets())
                $errors['err']=$errors['transfer'] = 'Action Refusée. Vous n\'êtes pas autorisé à transférer des tickets.';
            else {

                //Check target dept.
                if(!$_POST['deptId'])
                    $errors['deptId'] = 'Sélectionnez un département';
                elseif($_POST['deptId']==$ticket->getDeptId())
                    $errors['deptId'] = 'Le ticket est déjà dans le département';
                elseif(!($dept=Dept::lookup($_POST['deptId'])))
                    $errors['deptId'] = 'Département inconnu ou invalide';

                //Transfer message - required.
                if(!$_POST['transfer_comments'])
                    $errors['transfer_comments'] = 'Commentaire de transfert requis';
                elseif(strlen($_POST['transfer_comments'])<5)
                    $errors['transfer_comments'] = 'Le commentaire de transfert est trop court!';

                //If no errors - them attempt the transfer.
                if(!$errors && $ticket->transfer($_POST['deptId'], $_POST['transfer_comments'])) {
                    $msg = 'Ticket transféré avec succès à '.$ticket->getDeptName();
                    //Check to make sure the staff still has access to the ticket
                    if(!$ticket->checkStaffAccess($thisstaff))
                        $ticket=null;

                } elseif(!$errors['transfer']) {
                    $errors['err'] = 'Impossible de terminer le transfert du ticket';
                    $errors['transfer']='Corrigez les erreurs ci-dessous et essayez encore !';
                }
            }
            break;
        case 'assign':

             if(!$thisstaff->canAssignTickets())
                 $errors['err']=$errors['assign'] = 'Action Refusée. Vous n\'êtes pas autorisé à affecter ou réaffecter des tickets.';
             else {

                 $id = preg_replace("/[^0-9]/", "",$_POST['assignId']);
                 $claim = (is_numeric($_POST['assignId']) && $_POST['assignId']==$thisstaff->getId());

                 if(!$_POST['assignId'] || !$id)
                     $errors['assignId'] = 'Sélectionnez une personne affectée';
                 elseif($_POST['assignId'][0]!='s' && $_POST['assignId'][0]!='t' && !$claim)
                     $errors['assignId']='Identifiant de personne affectée invalide - contactez le support technique';
                 elseif($ticket->isAssigned()) {
                     if($_POST['assignId'][0]=='s' && $id==$ticket->getStaffId())
                         $errors['assignId']='Le ticket est déjà affecté au staff.';
                     elseif($_POST['assignId'][0]=='t' && $id==$ticket->getTeamId())
                         $errors['assignId']='Le ticket est déjà affecté à l\'équipe.';
                 }

                 //Comments are not required on self-assignment (claim)
                 if($claim && !$_POST['assign_comments'])
                     $_POST['assign_comments'] = 'Ticket réclamé par '.$thisstaff->getName();
                 elseif(!$_POST['assign_comments'])
                     $errors['assign_comments'] = 'Commentaire d\'affectation requis';
                 elseif(strlen($_POST['assign_comments'])<5)
                         $errors['assign_comments'] = 'Commentaire trop court';

                 if(!$errors && $ticket->assign($_POST['assignId'], $_POST['assign_comments'], !$claim)) {
                     if($claim) {
                         $msg = 'Le ticket vous est MAINTENANT affecté !';
                     } else {
                         $msg='Ticket affecté avec succès à '.$ticket->getAssigned();
                         TicketLock::removeStaffLocks($thisstaff->getId(), $ticket->getId());
                         $ticket=null;
                     }
                 } elseif(!$errors['assign']) {
                     $errors['err'] = 'Impossible de terminer l\'affectation du ticket';
                     $errors['assign'] = 'Corrigez les erreurs ci-dessous et essayez encore !';
                 }
             }
            break;
        case 'postnote': /* Post Internal Note */
            //Make sure the staff can set desired state
            if($_POST['state']) {
                if($_POST['state']=='closed' && !$thisstaff->canCloseTickets())
                    $errors['state'] = "Vous n'avez pas la permission de clore des tickets";
                elseif(in_array($_POST['state'], array('overdue', 'notdue', 'unassigned'))
                        && (!($dept=$ticket->getDept()) || !$dept->isManager($thisstaff)))
                    $errors['state'] = "Vous n'avez pas la permission de modifier l'état";
            }

            $vars = $_POST;
            if($_FILES['attachments'])
                $vars['files'] = AttachmentFile::format($_FILES['attachments']);

            $wasOpen = ($ticket->isOpen());
            if(($note=$ticket->postNote($vars, $errors, $thisstaff))) {

                $msg='Note interne envoyée avec succès';
                if($wasOpen && $ticket->isClosed())
                    $ticket = null; //Going back to main listing.
                else
                    // Ticket is still open -- clear draft for the note
                    Draft::deleteForNamespace('ticket.note.'.$ticket->getId(),
                        $thisstaff->getId());

            } else {

                if(!$errors['err'])
                    $errors['err'] = 'Impossible d\'envoyer la note interne - donnée(s) manquante(s) ou invalide(s).';

                $errors['postnote'] = 'Impossible d\'envoyer la note. Corrigez les erreurs ci-dessous et essayez encore !';
            }
            break;
        case 'edit':
        case 'update':
            $forms=DynamicFormEntry::forTicket($ticket->getId());
            foreach ($forms as $form) {
                // Don't validate deleted forms
                if (!in_array($form->getId(), $_POST['forms']))
                    continue;
                $form->setSource($_POST);
                if (!$form->isValid())
                    $errors = array_merge($errors, $form->errors());
            }
            if(!$ticket || !$thisstaff->canEditTickets())
                $errors['err']='Permission Refusée. Vous n\'avez pas la permission d\'éditer des tickets';
            elseif($ticket->update($_POST,$errors)) {
                $msg='Ticket mis à jour avec succès';
                $_REQUEST['a'] = null; //Clear edit action - going back to view.
                //Check to make sure the staff STILL has access post-update (e.g dept change).
                foreach ($forms as $f) {
                    // Drop deleted forms
                    $idx = array_search($f->getId(), $_POST['forms']);
                    if ($idx === false) {
                        $f->delete();
                    }
                    else {
                        $f->set('sort', $idx);
                        $f->save();
                    }
                }
                if(!$ticket->checkStaffAccess($thisstaff))
                    $ticket=null;
            } elseif(!$errors['err']) {
                $errors['err']='Impossible de mettre à jour le ticket. Corrigez les erreurs ci-dessous et essayez encore !';
            }
            break;
        case 'process':
            switch(strtolower($_POST['do'])):
                case 'close':
                    if(!$thisstaff->canCloseTickets()) {
                        $errors['err'] = 'Permission Refusée. Vous n\'avez pas la permission de clore des tickets.';
                    } elseif($ticket->isClosed()) {
                        $errors['err'] = 'Le ticket est déjà clos !';
                    } elseif($ticket->close()) {
                        $msg='Statut du ticket #'.$ticket->getNumber().' passé à FERMÉ';
                        //Log internal note
                        if($_POST['ticket_status_notes'])
                            $note = $_POST['ticket_status_notes'];
                        else
                            $note='Ticket clos (sans commentaires)';

                        $ticket->logNote('Ticket Clos', $note, $thisstaff);

                        //Going back to main listing.
                        TicketLock::removeStaffLocks($thisstaff->getId(), $ticket->getId());
                        $page=$ticket=null;

                    } else {
                        $errors['err']='Problèmes de clôture du ticket. Essayez encore';
                    }
                    break;
                case 'reopen':
                    //if staff can close or create tickets ...then assume they can reopen.
                    if(!$thisstaff->canCloseTickets() && !$thisstaff->canCreateTickets()) {
                        $errors['err']='Permission Refusée. Vous n\'avez pas la permission de réouvrir des tickets.';
                    } elseif($ticket->isOpen()) {
                        $errors['err'] = 'Le ticket est déjà ouvert !';
                    } elseif($ticket->reopen()) {
                        $msg='Ticket RÉOUVERT';

                        if($_POST['ticket_status_notes'])
                            $note = $_POST['ticket_status_notes'];
                        else
                            $note='Ticket réouvert (sans commentaires)';

                        $ticket->logNote('Ticket Réouvert', $note, $thisstaff);

                    } else {
                        $errors['err']='Problems de réouverture du ticket. Essayez encore';
                    }
                    break;
                case 'release':
                    if(!$ticket->isAssigned() || !($assigned=$ticket->getAssigned())) {
                        $errors['err'] = 'Le ticket n\'est pas affecté !';
                    } elseif($ticket->release()) {
                        $msg='Ticket relaché (non affecté) de '.$assigned;
                        $ticket->logActivity('Ticket non affecté',$msg.' par '.$thisstaff->getName());
                    } else {
                        $errors['err'] = 'Problèmes de relachement du ticket. Essayez encore';
                    }
                    break;
                case 'claim':
                    if(!$thisstaff->canAssignTickets()) {
                        $errors['err'] = 'Permission Refusée. Vous n\'avez pas la permission d\'affecter ou de réclamer des tickets.';
                    } elseif(!$ticket->isOpen()) {
                        $errors['err'] = 'Seuls les tickets ouverts peuvent être affectés';
                    } elseif($ticket->isAssigned()) {
                        $errors['err'] = 'Le ticket est déjà affecté à '.$ticket->getAssigned();
                    } elseif($ticket->assignToStaff($thisstaff->getId(), ('Ticket réclamé par '.$thisstaff->getName()), false)) {
                        $msg = 'Le ticket vous est maintenant affecté !';
                    } else {
                        $errors['err'] = 'Problèmes d\'affectation du ticket. Essayez encore';
                    }
                    break;
                case 'overdue':
                    $dept = $ticket->getDept();
                    if(!$dept || !$dept->isManager($thisstaff)) {
                        $errors['err']='Permission Refusée. Vous n\'avez pas la permission de marquer en retard des tickets';
                    } elseif($ticket->markOverdue()) {
                        $msg='Ticket marqué comme en retard';
                        $ticket->logActivity('Ticket Marqué En Retard',($msg.' par '.$thisstaff->getName()));
                    } else {
                        $errors['err']='Problèmes pour marquer le ticket comme en retard. Essayez encore';
                    }
                    break;
                case 'answered':
                    $dept = $ticket->getDept();
                    if(!$dept || !$dept->isManager($thisstaff)) {
                        $errors['err']='Permission Refusée. Vous n\'avez pas la permission de marquer des tickets';
                    } elseif($ticket->markAnswered()) {
                        $msg='Ticket marqué comme répondu';
                        $ticket->logActivity('Ticket Marqué Répondu',($msg.' par '.$thisstaff->getName()));
                    } else {
                        $errors['err']='Problèmes pour marquer le ticket comme répondu. Essayez encore';
                    }
                    break;
                case 'unanswered':
                    $dept = $ticket->getDept();
                    if(!$dept || !$dept->isManager($thisstaff)) {
                        $errors['err']='Permission Refusée. Vous n\'avez pas la permission de marquer des tickets' 
                    } elseif($ticket->markUnAnswered()) {
                        $msg='Ticket marqué comme non répondu';
                        $ticket->logActivity('Ticket Marqué Non Répondu',($msg.' par '.$thisstaff->getName()));
                    } else {
                        $errors['err']='Problèmes pour marquer le ticket comme non répondu. Essayez encore';
                    }
                    break;
                case 'banemail':
                    if(!$thisstaff->canBanEmails()) {
                        $errors['err']='Permission Refusée. Vous n\'avez pas la permission de bannir des adresses de courriel';
                    } elseif(BanList::includes($ticket->getEmail())) {
                        $errors['err']='Courriel déjà présent dans la liste de bannissement';
                    } elseif(Banlist::add($ticket->getEmail(),$thisstaff->getName())) {
                        $msg='Courriel ('.$ticket->getEmail().') ajouté à la liste de bannissement';
                    } else {
                        $errors['err']='Impossible d\'ajouter l\'adresse de courriel à la liste de bannissement';
                    }
                    break;
                case 'unbanemail':
                    if(!$thisstaff->canBanEmails()) {
                        $errors['err'] = 'Permission Refusée. Vous n\'avez pas la permission de retirer des adresses de courriel de la liste de bannissement.';
                    } elseif(Banlist::remove($ticket->getEmail())) {
                        $msg = 'Courriel retiré de la liste de bannissement';
                    } elseif(!BanList::includes($ticket->getEmail())) {
                        $warn = 'Courriel non présent dans la liste de bannissement';
                    } else {
                        $errors['err']='Impossible de retirer l\'adresse de courriel de la liste de bannissement. Essayez encore.';
                    }
                    break;
                case 'changeuser':
                    if (!$thisstaff->canEditTickets()) {
                        $errors['err'] = 'Permission Refusée. Vous n\'avez pas la permission d\'ÉDITER des tickets !!!';
                    } elseif (!$_POST['user_id'] || !($user=User::lookup($_POST['user_id']))) {
                        $errors['err'] = 'Sélection d\'un utilisateur inconnu !';
                    } elseif ($ticket->changeOwner($user)) {
                        $msg = 'Propriété du ticket changée pour ' . Format::htmlchars($user->getName());
                    } else {
                        $errors['err'] = 'Impossible de changer la propriété du ticket. Essayez encore';
                    }
                    break;
                case 'delete': // Dude what are you trying to hide? bad customer support??
                    if(!$thisstaff->canDeleteTickets()) {
                        $errors['err']='Permission Refusée. Vous n\'avez pas la permission de SUPPRIMER des tickets !!!';
                    } elseif($ticket->delete()) {
                        $msg='Ticket #'.$ticket->getNumber().' supprimé avec succès';
                        //Log a debug note
                        $ost->logDebug('Ticket #'.$ticket->getNumber().' supprimé',
                                sprintf('Ticket #%s supprimé par %s',
                                    $ticket->getNumber(), $thisstaff->getName())
                                );
                        $ticket=null; //clear the object.
                    } else {
                        $errors['err']='Problèmes de suppression du ticket. Essayez encore';
                    }
                    break;
                default:
                    $errors['err']='Vous devez sélectionner une action à exécuter';
            endswitch;
            break;
        default:
            $errors['err']='Action inconnue';
        endswitch;
        if($ticket && is_object($ticket))
            $ticket->reload();//Reload ticket info following post processing
    }elseif($_POST['a']) {

        switch($_POST['a']) {
            case 'mass_process':
                if(!$thisstaff->canManageTickets())
                    $errors['err']='Vous n\4avez pas la permission de modifier des tickets en masse. Contactez l\'administrateur pour obtenir un tel accès';
                elseif(!$_POST['tids'] || !is_array($_POST['tids']))
                    $errors['err']='Aucun ticket sélectionné. Vous devez sélectionner au moins un ticket.';
                else {
                    $count=count($_POST['tids']);
                    $i = 0;
                    switch(strtolower($_POST['do'])) {
                        case 'reopen':
                            if($thisstaff->canCloseTickets() || $thisstaff->canCreateTickets()) {
                                $note='Ticket réouvert par '.$thisstaff->getName();
                                foreach($_POST['tids'] as $k=>$v) {
                                    if(($t=Ticket::lookup($v)) && $t->isClosed() && @$t->reopen()) {
                                        $i++;
                                        $t->logNote('Ticket Réouvert', $note, $thisstaff);
                                    }
                                }

                                if($i==$count)
                                    $msg = "Tickets sélectionnés ($i) réouverts avec succès";
                                elseif($i)
                                    $warn = "$i ticket(s) sur $count tickets sélectionnés réouverts";
                                else
                                    $errors['err'] = 'Impossible de réouvrir les tickets sélectionnés';
                            } else {
                                $errors['err'] = 'Vous n\'avez pas la permission de réouvrir des tickets';
                            }
                            break;
                        case 'close':
                            if($thisstaff->canCloseTickets()) {
                                $note='Ticket clos sans réponse par '.$thisstaff->getName();
                                foreach($_POST['tids'] as $k=>$v) {
                                    if(($t=Ticket::lookup($v)) && $t->isOpen() && @$t->close()) {
                                        $i++;
                                        $t->logNote('Ticket Clos', $note, $thisstaff);
                                    }
                                }

                                if($i==$count)
                                    $msg ="Tickets sélectionnés ($i) clos avec succès";
                                elseif($i)
                                    $warn = "$i ticket(s) sur $count tickets sélectionnés clos";
                                else
                                    $errors['err'] = 'Impossible de clore les tickets sélectionnés';
                            } else {
                                $errors['err'] = 'Vous n\'avez pas la permission de clore des tickets';
                            }
                            break;
                        case 'mark_overdue':
                            $note='Ticket marqué comme en retard par '.$thisstaff->getName();
                            foreach($_POST['tids'] as $k=>$v) {
                                if(($t=Ticket::lookup($v)) && !$t->isOverdue() && $t->markOverdue()) {
                                    $i++;
                                    $t->logNote('Ticket Marqué En Retard', $note, $thisstaff);
                                }
                            }

                            if($i==$count)
                                $msg = "Tickets sélectionnés ($i) marqués comme en retard";
                            elseif($i)
                                $warn = "$i ticket(s) sur $count tickets sélectionnés marqués comme en retard";
                            else
                                $errors['err'] = 'Impossible de marquer les tickets sélectionnés comme en retard';
                            break;
                        case 'delete':
                            if($thisstaff->canDeleteTickets()) {
                                foreach($_POST['tids'] as $k=>$v) {
                                    if(($t=Ticket::lookup($v)) && @$t->delete()) $i++;
                                }

                                //Log a warning
                                if($i) {
                                    $log = sprintf('%s (%s) vient de supprimer %d ticket(s)',
                                            $thisstaff->getName(), $thisstaff->getUserName(), $i);
                                    $ost->logWarning('Tickets supprimés', $log, false);

                                }

                                if($i==$count)
                                    $msg = "Tickets sélectionnés ($i) supprimés avec succès";
                                elseif($i)
                                    $warn = "$i ticket(s) sur $count tickets sélectionnés supprimés";
                                else
                                    $errors['err'] = 'Impossible de supprimer les tickets sélectionnés';
                            } else {
                                $errors['err'] = 'Vous n\'avez pas la permission de supprimer des tickets';
                            }
                            break;
                        default:
                            $errors['err']='Action inconnue ou non supportée - veuillez contacter le support technique';
                    }
                }
                break;
            case 'open':
                $ticket=null;
                if(!$thisstaff || !$thisstaff->canCreateTickets()) {
                     $errors['err']='Vou n\'avez pas la permission de créer des tickets. Contactez l\'administrateur pour un tel accès.';
                } else {
                    $vars = $_POST;
                    $vars['uid'] = $user? $user->getId() : 0;

                    if(($ticket=Ticket::open($vars, $errors))) {
                        $msg='Ticket créé avec succès';
                        $_REQUEST['a']=null;
                        if (!$ticket->checkStaffAccess($thisstaff) || $ticket->isClosed())
                            $ticket=null;
                        Draft::deleteForNamespace('ticket.staff%', $thisstaff->getId());
                        unset($_SESSION[':form-data']);
                    } elseif(!$errors['err']) {
                        $errors['err']='Impossible de créer le ticket. Corrigez les erreurs et réessayez';
                    }
                }
                break;
        }
    }
    if(!$errors)
        $thisstaff ->resetStats(); //We'll need to reflect any changes just made!
endif;

/*... Quick stats ...*/
$stats= $thisstaff->getTicketsStats();

//Navigation
$nav->setTabActive('tickets');
if($cfg->showAnsweredTickets()) {
    $nav->addSubMenu(array('desc'=>'Ouverts ('.number_format($stats['open']+$stats['answered']).')',
                            'title'=>'Tickets ouverts',
                            'href'=>'tickets.php',
                            'iconclass'=>'Ticket'),
                        (!$_REQUEST['status'] || $_REQUEST['status']=='open'));
} else {

    if($stats) {
        $nav->addSubMenu(array('desc'=>'Ouverts ('.number_format($stats['open']).')',
                               'title'=>'Tickets ouverts',
                               'href'=>'tickets.php',
                               'iconclass'=>'Ticket'),
                            (!$_REQUEST['status'] || $_REQUEST['status']=='open'));
    }

    if($stats['answered']) {
        $nav->addSubMenu(array('desc'=>'Répondus ('.number_format($stats['answered']).')',
                               'title'=>'Tickets répondus',
                               'href'=>'tickets.php?status=answered',
                               'iconclass'=>'answeredTickets'),
                            ($_REQUEST['status']=='answered'));
    }
}

if($stats['assigned']) {

    $nav->addSubMenu(array('desc'=>'Mes tickets ('.number_format($stats['assigned']).')',
                           'title'=>'Tickets affectés',
                           'href'=>'tickets.php?status=assigned',
                           'iconclass'=>'assignedTickets'),
                        ($_REQUEST['status']=='assigned'));
}

if($stats['overdue']) {
    $nav->addSubMenu(array('desc'=>'En retard ('.number_format($stats['overdue']).')',
                           'title'=>'Tickets en retard',
                           'href'=>'tickets.php?status=overdue',
                           'iconclass'=>'overdueTickets'),
                        ($_REQUEST['status']=='overdue'));

    if(!$sysnotice && $stats['overdue']>10)
        $sysnotice=$stats['overdue'] .' tickets en retard !';
}

if($thisstaff->showAssignedOnly() && $stats['closed']) {
    $nav->addSubMenu(array('desc'=>'Mes tickets clos ('.number_format($stats['closed']).')',
                           'title'=>'Mes tickets clos',
                           'href'=>'tickets.php?status=closed',
                           'iconclass'=>'closedTickets'),
                        ($_REQUEST['status']=='closed'));
} else {

    $nav->addSubMenu(array('desc'=>'Tickets clos ('.number_format($stats['closed']).')',
                           'title'=>'Tickets clos',
                           'href'=>'tickets.php?status=closed',
                           'iconclass'=>'closedTickets'),
                        ($_REQUEST['status']=='closed'));
}

if($thisstaff->canCreateTickets()) {
    $nav->addSubMenu(array('desc'=>'Nouveau ticket',
                           'title' => 'Ouvrir un nouveau ticket',
                           'href'=>'tickets.php?a=open',
                           'iconclass'=>'newTicket',
                           'id' => 'new-ticket'),
                        ($_REQUEST['a']=='open'));
}


$ost->addExtraHeader('<script type="text/javascript" src="js/ticket.js"></script>');
$ost->addExtraHeader('<meta name="tip-namespace" content="tickets.queue" />',
    "$('#content').data('tipNamespace', 'tickets.queue');");

$inc = 'tickets.inc.php';
if($ticket) {
    $ost->setPageTitle('Ticket #'.$ticket->getNumber());
    $nav->setActiveSubMenu(-1);
    $inc = 'ticket-view.inc.php';
    if($_REQUEST['a']=='edit' && $thisstaff->canEditTickets()) {
        $inc = 'ticket-edit.inc.php';
        if (!$forms) $forms=DynamicFormEntry::forTicket($ticket->getId());
        // Auto add new fields to the entries
        foreach ($forms as $f) $f->addMissingFields();
    } elseif($_REQUEST['a'] == 'print' && !$ticket->pdfExport($_REQUEST['psize'], $_REQUEST['notes']))
        $errors['err'] = 'Erreur interne : impossible d\'exporter le ticket en PDF pour l\'imprimer.';
} else {
    $inc = 'tickets.inc.php';
    if($_REQUEST['a']=='open' && $thisstaff->canCreateTickets())
        $inc = 'ticket-open.inc.php';
    elseif($_REQUEST['a'] == 'export') {
        require_once(INCLUDE_DIR.'class.export.php');
        $ts = strftime('%Y%m%d');
        if (!($token=$_REQUEST['h']))
            $errors['err'] = 'Jeton de requête requis';
        elseif (!($query=$_SESSION['search_'.$token]))
            $errors['err'] = 'Jeton de requête non trouvé';
        elseif (!Export::saveTickets($query, "tickets-$ts.csv", 'csv'))
            $errors['err'] = 'Erreur interne : impossible de sortir les résultats de la requête';
    }

    //Clear active submenu on search with no status
    if($_REQUEST['a']=='search' && !$_REQUEST['status'])
        $nav->setActiveSubMenu(-1);

    //set refresh rate if the user has it configured
    if(!$_POST && !$_REQUEST['a'] && ($min=$thisstaff->getRefreshRate())) {
        $js = "clearTimeout(window.ticket_refresh);
               window.ticket_refresh = setTimeout($.refreshTicketView,"
            .($min*60000).");";
        $ost->addExtraHeader('<script type="text/javascript">'.$js.'</script>',
            $js);
    }
}

require_once(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.$inc);
require_once(STAFFINC_DIR.'footer.inc.php');
