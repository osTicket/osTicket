<?php
/*********************************************************************
    tickets.php

    Main client/user interface.
    Note that we are using external ID. The real (local) ids are hidden from user.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('secure.inc.php');
if(!is_object($thisclient) || !$thisclient->isValid()) die('Access denied'); //Double check again.
require_once(INCLUDE_DIR.'class.ticket.php');
$ticket=null;
if($_REQUEST['id']) {
    if(!($ticket=Ticket::lookupByExtId($_REQUEST['id']))) {
        $errors['err']='Unknown or invalid ticket ID.';
    }elseif(!$ticket->checkClientAccess($thisclient)) {
        $errors['err']='Unknown or invalid ticket ID.'; //Using generic message on purpose!
        $ticket=null;
    }
}

//Process post...depends on $ticket object above.
if($_POST && is_object($ticket) && $ticket->getId()):
    $errors=array();
    switch(strtolower($_POST['a'])){
    case 'reply':
        if(!$ticket->checkClientAccess($thisclient)) //double check perm again!
            $errors['err']='Access Denied. Possibly invalid ticket ID';

        if(!$_POST['message'])
            $errors['message']='Message required';

        if(!$errors) {
            //Everything checked out...do the magic.
            if(($msgid=$ticket->postMessage($_POST['message'],'Web'))) {
                if($cfg->allowOnlineAttachments() 
                        && $_FILES['attachments']
                        && ($files=Format::files($_FILES['attachments']))) {
                    $ost->validateFileUploads($files); //Validator sets errors - if any.
                    $ticket->uploadAttachments($files, $msgid, 'M');
                }
                $msg='Message Posted Successfully';
            } else {
                $errors['err']='Unable to post the message. Try again';
            }

        } elseif(!$errors['err']) {
            $errors['err']='Error(s) occurred. Please try again';
        }
        break;
    default:
        $errors['err']='Unknown action';
    }
    $ticket->reload();
endif;
$nav->setActiveNav('tickets');
if($ticket && $ticket->checkClientAccess($thisclient)) {
    $inc='view.inc.php';
} elseif($cfg->showRelatedTickets() && $thisclient->getNumTickets()) {
    $inc='tickets.inc.php';
} else {
    $nav->setActiveNav('new');
    $inc='open.inc.php';
}
include(CLIENTINC_DIR.'header.inc.php');
include(CLIENTINC_DIR.$inc);
include(CLIENTINC_DIR.'footer.inc.php');
?>
