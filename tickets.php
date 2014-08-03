<?php
/*********************************************************************
    tickets.php

    Main client/user interface.
    Note that we are using external ID. The real (local) ids are hidden from user.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('secure.inc.php');
if(!is_object($thisclient) || !$thisclient->isValid()) die('Accès refusé'); //Double check again.

if ($thisclient->isGuest())
    $_REQUEST['id'] = $thisclient->getTicketId();

require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.json.php');
$ticket=null;
if($_REQUEST['id']) {
    if (!($ticket = Ticket::lookup($_REQUEST['id']))) {
        $errors['err']='Identifiant de ticket inconnu ou invalide.';
    } elseif(!$ticket->checkUserAccess($thisclient)) {
        $errors['err']='Ticket inconnu ou invalide.'; //Using generic message on purpose!
        $ticket=null;
    }
}

if (!$ticket && $thisclient->isGuest())
    Http::redirect('view.php');

//Process post...depends on $ticket object above.
if($_POST && is_object($ticket) && $ticket->getId()):
    $errors=array();
    switch(strtolower($_POST['a'])){
    case 'edit':
        if(!$ticket->checkUserAccess($thisclient) //double check perm again!
                || $thisclient->getId() != $ticket->getUserId())
            $errors['err']='Accès refusé. Il est possible que l\'identifiant du ticket soit invalide';
        elseif (!$cfg || !$cfg->allowClientUpdates())
            $errors['err']='Accès refusé. Les mises à jour par le client sont actuellement désactivées';
        else {
            $forms=DynamicFormEntry::forTicket($ticket->getId());
            foreach ($forms as $form) {
                $form->setSource($_POST);
                if (!$form->isValid())
                    $errors = array_merge($errors, $form->errors());
            }
        }
        if (!$errors) {
            foreach ($forms as $f) $f->save();
            $_REQUEST['a'] = null; //Clear edit action - going back to view.
            $ticket->logNote('Détails du ticket mis à jour', sprintf(
                'Les détails du ticket ont été mis à jour par le client %s &lt;%s&gt;',
                $thisclient->getName(), $thisclient->getEmail()));
        }
        break;
    case 'reply':
        if(!$ticket->checkUserAccess($thisclient)) //double check perm again!
            $errors['err']='Accès refusé. Il est possible que l\'identifiant du ticket soit invalide';

        if(!$_POST['message'])
            $errors['message']='Message requis';

        if(!$errors) {
            //Everything checked out...do the magic.
            $vars = array(
                    'userId' => $thisclient->getId(),
                    'poster' => (string) $thisclient->getName(),
                    'message' => $_POST['message']);
            if($cfg->allowOnlineAttachments() && $_FILES['attachments'])
                $vars['files'] = AttachmentFile::format($_FILES['attachments'], true);
            if (isset($_POST['draft_id']))
                $vars['draft_id'] = $_POST['draft_id'];

            if(($msgid=$ticket->postMessage($vars, 'Web'))) {
                $msg='Message envoyé avec succès';
                // Cleanup drafts for the ticket. If not closed, only clean
                // for this staff. Else clean all drafts for the ticket.
                Draft::deleteForNamespace('ticket.client.' . $ticket->getId());
            } else {
                $errors['err']='Impossible d\'envoyer le message. Réessayez';
            }

        } elseif(!$errors['err']) {
            $errors['err']='Des erreurs sont apparues. Veuillez réessayer s\'il-vous-plaît';
        }
        break;
    default:
        $errors['err']='Action inconnue';
    }
    $ticket->reload();
endif;
$nav->setActiveNav('tickets');
if($ticket && $ticket->checkUserAccess($thisclient)) {
    if (isset($_REQUEST['a']) && $_REQUEST['a'] == 'edit'
            && $cfg->allowClientUpdates()) {
        $inc = 'edit.inc.php';
        if (!$forms) $forms=DynamicFormEntry::forTicket($ticket->getId());
        // Auto add new fields to the entries
        foreach ($forms as $f) $f->addMissingFields();
    }
    else
        $inc='view.inc.php';
} elseif($thisclient->getNumTickets()) {
    $inc='tickets.inc.php';
} else {
    $nav->setActiveNav('new');
    $inc='open.inc.php';
}
include(CLIENTINC_DIR.'header.inc.php');
include(CLIENTINC_DIR.$inc);
include(CLIENTINC_DIR.'footer.inc.php');
?>
