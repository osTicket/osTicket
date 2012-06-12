<?php
/*************************************************************************
    tickets.php
    
    Handles all tickets related actions.
 
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
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


$page='';
$ticket=null; //clean start.
//LOCKDOWN...See if the id provided is actually valid and if the user has access.
if($_REQUEST['id']) {
    if(!($ticket=Ticket::lookup($_REQUEST['id'])))
         $errors['err']='Unknown or invalid ticket ID';
    elseif(!$ticket->checkStaffAccess($thisstaff)) {
        $errors['err']='Access denied. Contact admin if you believe this is in error';
        $ticket=null; //Clear ticket obj.
    }
}
//At this stage we know the access status. we can process the post.
if($_POST && !$errors):

    if($ticket && $ticket->getId()) {
        //More coffee please.
        $errors=array();
        $lock=$ticket->getLock(); //Ticket lock if any
        $statusKeys=array('open'=>'Open','Reopen'=>'Open','Close'=>'Closed');
        switch(strtolower($_POST['a'])):
        case 'reply':

            if(!$_POST['msgId'])
                $errors['err']='Missing message ID - Internal error';
            if(!$_POST['response'])
                $errors['response']='Response required';
            //Use locks to avoid double replies
            if($lock && $lock->getStaffId()!=$thisstaff->getId())
                $errors['err']='Action Denied. Ticket is locked by someone else!';
            
            //Make sure the email is not banned
            if(!$errors['err'] && EmailFilter::isBanned($ticket->getEmail()))
                $errors['err']='Email is in banlist. Must be removed to reply.';

            $wasOpen =($ticket->isOpen());
            //If no error...do the do.
            if(!$errors && ($respId=$ticket->postReply($_POST,$_FILES['attachments'],$errors))) {
                $msg='Reply posted successfully';
                $ticket->reload();
                if($ticket->isClosed() && $wasOpen)
                    $ticket=null;
            } elseif(!$errors['err']) {
                $errors['err']='Unable to post the reply. Correct the errors below and try again!';
            }
            break;
        case 'transfer': /** Transfer ticket **/
            //Check permission 
            if($thisstaff && $thisstaff->canTransferTickets()) {
                if(!$_POST['deptId'])
                    $errors['deptId']='Select department';
                elseif($_POST['deptId']==$ticket->getDeptId())
                    $errors['deptId']='Ticket already in the Dept.';
                elseif(!($dept=Dept::lookup($_POST['deptId'])))
                    $errors['deptId']='Unknown or invalid department';

                if(!$_POST['transfer_message'])
                    $errors['transfer_message'] = 'Transfer comments/notes required';
                elseif(strlen($_POST['transfer_message'])<5)
                    $errors['transfer_message'] = 'Transfer comments too short!';

                $currentDept = $ticket->getDeptName(); //save current dept name.
                if(!$errors && $ticket->transfer($_POST['deptId'], $_POST['transfer_message'])) {
                    $msg = 'Ticket transferred successfully to '.$ticket->getDeptName();
                    //ticket->transfer does a reload...new dept at this point.
                    $title='Dept. Transfer from '.$currentDept.' to '.$ticket->getDeptName();
                    /*** log the message as internal note - with alerts disabled - ***/
                    $ticket->postNote($title, $_POST['transfer_message'], false);
                    //Check to make sure the staff still has access to the ticket
                    if(!$ticket->checkStaffAccess($thisstaff))
                        $ticket=null;

                } else {
                    $errors['transfer']='Unable to complete the transfer - try again';
                    $errors['err']='Missing or invalid data. Correct the error(s) below and try again!';
                }
            } else {
                $errors['err']=$errors['transfer']='Action Denied. You are not allowed to transfer tickets.';
            }
            break;
        case 'assign':

             if($thisstaff && $thisstaff->canAssignTickets()) {
                 if(!$_POST['assignId'])
                     $errors['assignId'] = 'Select assignee';
                 elseif($_POST['assignId'][0]!='s' && $_POST['assignId'][0]!='t')
                     $errors['assignId']='Invalid assignee ID - get technical support';
                 elseif($ticket->isAssigned()) {
                     $id=preg_replace("/[^0-9]/", "",$_POST['assignId']);
                     if($_POST['assignId'][0]=='s' && $id==$ticket->getStaffId())
                         $errors['assignId']='Ticket already assigned to the staff.';
                     elseif($_POST['assignId'][0]=='t' && $id==$ticket->getTeamId())
                         $errors['assignId']='Ticket already assigned to the team.';
                 }

                 if(!$_POST['assign_message'])
                     $errors['assign_message']='Comments required';
                 elseif(strlen($_POST['assign_message'])<5)
                     $errors['assign_message']='Comments too short';

                 if(!$errors && $ticket->assign($_POST['assignId'],$_POST['assign_message'])) {
                     $msg='Ticket assigned successfully to '.$ticket->getAssignee();
                     TicketLock::removeStaffLocks($thisstaff->getId(),$ticket->getId());
                     $ticket=null;
                 }elseif(!$errors['err']) {
                     $errors['err']='Unable to assign the ticket. Correct the errors below and try again.';
                 }

             } else {
                 $errors['err']=$errors['assign']='Action Denied. You are not allowed to assign/reassign tickets.';
             }
            break; 
        case 'postnote': /* Post Internal Note */
            $fields=array();
            $fields['title']    = array('type'=>'string',   'required'=>1, 'error'=>'Title required');
            $fields['internal_note'] = array('type'=>'string',   'required'=>1, 'error'=>'Note message required');
            
            if(!Validator::process($fields, $_POST, $errors) && !$errors['err'])
                $errors['err']=$errors['note']='Missing or invalid data. Correct the error(s) below and try again!';
            
            if(!$errors && ($noteId=$ticket->postNote($_POST['title'], $_POST['internal_note']))) {
                $msg='Internal note posted successfully';
                //Upload attachments IF ANY - TODO: validate attachment types??
                if($_FILES['attachments'] && ($files=Format::files($_FILES['attachments'])))
                    $ticket->uploadAttachments($files,$noteId,'N');
                //Set state: Error on state change not critical! 
                if(isset($_POST['note_ticket_state']) && $_POST['note_ticket_state']) {
                    if($ticket->setState($_POST['note_ticket_state']) && $ticket->reload()) {
                         $msg.=' and state changed to '.strtoupper($_POST['note_ticket_state']);
                         if($ticket->isClosed())
                             $ticket=null; //Going back to main listing.
                    }
                }
            } elseif(!$errors['note']) {
                $errors['note']='Error(s) occurred. Unable to post the note.';
            }
            break;
        case 'edit':
        case 'update':
            if(!$ticket || !$thisstaff->canEditTickets())
                $errors['err']='Perm. Denied. You are not allowed to edit tickets';
            elseif($ticket->update($_POST,$errors)) {
                $msg='Ticket updated successfully';
                $_REQUEST['a'] = null;
            } elseif(!$errors['err']) {
                $errors['err']='Unable to update the ticket. Correct the errors below and try again!';
            }
            break;
        case 'process':
            $isdeptmanager=($ticket->getDeptId()==$thisstaff->getDeptId())?true:false;
            switch(strtolower($_POST['do'])):
                case 'change_priority':
                    if(!$thisstaff->canManageTickets() && !$thisstaff->isManager()){
                        $errors['err']='Perm. Denied. You are not allowed to change ticket\'s priority';
                    }elseif(!$_POST['ticket_priority'] or !is_numeric($_POST['ticket_priority'])){
                        $errors['err']='You must select priority';
                    }
                    if(!$errors){
                        if($ticket->setPriority($_POST['ticket_priority'])){
                            $msg='Priority Changed Successfully';
                            $ticket->reload();
                            $note='Ticket priority set to "'.$ticket->getPriority().'" by '.$thisstaff->getName();
                            $ticket->logActivity('Priority Changed',$note);
                        }else{
                            $errors['err']='Problems changing priority. Try again';
                        }
                    }
                    break;
                case 'close':
                    if(!$thisstaff->isAdmin() && !$thisstaff->canCloseTickets()){
                        $errors['err']='Perm. Denied. You are not allowed to close tickets.';
                    }else{
                        if($ticket->close()){
                            $msg='Ticket #'.$ticket->getExtId().' status set to CLOSED';
                            $note='Ticket closed without response by '.$thisstaff->getName();
                            $ticket->logActivity('Ticket Closed',$note);
                            $page=$ticket=null; //Going back to main listing.
                        }else{
                            $errors['err']='Problems closing the ticket. Try again';
                        }
                    }
                    break;
                case 'reopen':
                    //if they can close...then assume they can reopen.
                    if(!$thisstaff->isAdmin() && !$thisstaff->canCloseTickets()){
                        $errors['err']='Perm. Denied. You are not allowed to reopen tickets.';
                    }else{
                        if($ticket->reopen()){
                            $msg='Ticket status set to OPEN';
                            $note='Ticket reopened (without comments)';
                            if($_POST['ticket_priority']) {
                                $ticket->setPriority($_POST['ticket_priority']);
                                $ticket->reload();
                                $note.=' and status set to '.$ticket->getPriority();
                            }
                            $note.=' by '.$thisstaff->getName();
                            $ticket->logActivity('Ticket Reopened',$note);
                        }else{
                            $errors['err']='Problems reopening the ticket. Try again';
                        }
                    }
                    break;
                case 'release':
                    if(!($staff=$ticket->getStaff()))
                        $errors['err']='Ticket is not assigned!';
                    elseif($ticket->release()) {
                        $msg='Ticket released (unassigned) from '.$staff->getName().' by '.$thisstaff->getName();;
                        $ticket->logActivity('Ticket unassigned',$msg);
                    }else
                        $errors['err']='Problems releasing the ticket. Try again';
                    break;
                case 'overdue':
                    //Mark the ticket as overdue
                    if(!$thisstaff->isAdmin() && !$thisstaff->isManager()){
                        $errors['err']='Perm. Denied. You are not allowed to flag tickets overdue';
                    }else{
                        if($ticket->markOverdue()){
                            $msg='Ticket flagged as overdue';
                            $note=$msg;
                            if($_POST['ticket_priority']) {
                                $ticket->setPriority($_POST['ticket_priority']);
                                $ticket->reload();
                                $note.=' and status set to '.$ticket->getPriority();
                            }
                            $note.=' by '.$thisstaff->getName();
                            $ticket->logActivity('Ticket Marked Overdue',$note);
                        }else{
                            $errors['err']='Problems marking the the ticket overdue. Try again';
                        }
                    }
                    break;
                case 'banemail':
                    if(!$thisstaff->isAdmin() && !$thisstaff->canBanEmails()){
                        $errors['err']='Perm. Denied. You are not allowed to ban emails';
                    }elseif(Banlist::add($ticket->getEmail(),$thisstaff->getName())){
                        $msg='Email ('.$ticket->getEmail().') added to banlist';
                        if($ticket->isOpen() && $ticket->close()) {
                            $msg.=' & ticket status set to closed';
                            $ticket->logActivity('Ticket Closed',$msg);
                            $page=$ticket=null; //Going back to main listing.
                        }
                    }else{
                        $errors['err']='Unable to add the email to banlist';
                    }
                    break;
                case 'unbanemail':
                    if(!$thisstaff->isAdmin() && !$thisstaff->canBanEmails()){
                        $errors['err']='Perm. Denied. You are not allowed to remove emails from banlist.';
                    }elseif(Banlist::remove($ticket->getEmail())){
                        $msg='Email removed from banlist';
                    }else{
                        $errors['err']='Unable to remove the email from banlist. Try again.';
                    }
                    break;
                case 'delete': // Dude what are you trying to hide? bad customer support??
                    if(!$thisstaff->isAdmin() && !$thisstaff->canDeleteTickets()){
                        $errors['err']='Perm. Denied. You are not allowed to DELETE tickets!!';
                    }else{
                        if($ticket->delete()){
                            $page='tickets.inc.php'; //ticket is gone...go back to the listing.
                            $msg='Ticket Deleted Forever';
                            $ticket=null; //clear the object.
                        }else{
                            $errors['err']='Problems deleting the ticket. Try again';
                        }
                    }
                    break;
                default:
                    $errors['err']='You must select action to perform';
            endswitch;
            break;
        default:
            $errors['err']='Unknown action';
        endswitch;
        if($ticket && is_object($ticket))
            $ticket->reload();//Reload ticket info following post processing
    }elseif($_POST['a']) {
        switch($_POST['a']) {
            case 'mass_process':
                if(!$thisstaff->canManageTickets())
                    $errors['err']='You do not have permission to mass manage tickets. Contact admin for such access';    
                elseif(!$_POST['tids'] || !is_array($_POST['tids']))
                    $errors['err']='No tickets selected. You must select at least one ticket.';
                elseif(($_POST['reopen'] || $_POST['close']) && !$thisstaff->canCloseTickets())
                    $errors['err']='You do not have permission to close/reopen tickets';
                elseif($_POST['delete'] && !$thisstaff->canDeleteTickets())
                    $errors['err']='You do not have permission to delete tickets';
                elseif(!$_POST['tids'] || !is_array($_POST['tids']))
                    $errors['err']='You must select at least one ticket';
        
                if(!$errors) {
                    $count=count($_POST['tids']);
                    if(isset($_POST['reopen'])){
                        $i=0;
                        $note='Ticket reopened by '.$thisstaff->getName();
                        foreach($_POST['tids'] as $k=>$v) {
                            $t = new Ticket($v);
                            if($t && @$t->reopen()) {
                                $i++;
                                $t->logActivity('Ticket Reopened',$note,false,'System');
                            }
                        }
                        $msg="$i of $count selected tickets reopened";
                    }elseif(isset($_POST['close'])){
                        $i=0;
                        $note='Ticket closed without response by '.$thisstaff->getName();
                        foreach($_POST['tids'] as $k=>$v) {
                            $t = new Ticket($v);
                            if($t && @$t->close()){ 
                                $i++;
                                $t->logActivity('Ticket Closed',$note,false,'System');
                            }
                        }
                        $msg="$i of $count selected tickets closed";
                    }elseif(isset($_POST['overdue'])){
                        $i=0;
                        $note='Ticket flagged as overdue by '.$thisstaff->getName();
                        foreach($_POST['tids'] as $k=>$v) {
                            $t = new Ticket($v);
                            if($t && !$t->isOverdue())
                                if($t->markOverdue()) { 
                                    $i++;
                                    $t->logActivity('Ticket Marked Overdue',$note,false,'System');
                                }
                        }
                        $msg="$i of $count selected tickets marked overdue";
                    }elseif(isset($_POST['delete'])){
                        $i=0;
                        foreach($_POST['tids'] as $k=>$v) {
                            $t = new Ticket($v);
                            if($t && @$t->delete()) $i++;
                        }
                        $msg="$i of $count selected tickets deleted";
                    }
                }
                break;
            case 'open':
                $ticket=null;
                if(!$thisstaff || !$thisstaff->canCreateTickets()) {
                     $errors['err']='You do not have permission to create tickets. Contact admin for such access';
                }elseif(($ticket=Ticket::open($_POST, $_FILES['attachments'], $errors))) {
                    $msg='Ticket created successfully';
                    $_REQUEST['a']=null;
                    if(!$ticket->checkStaffAccess($thisstaff) || $ticket->isClosed())
                        $ticket=null;
                }elseif(!$errors['err']) {
                    $errors['err']='Unable to create the ticket. Correct the error(s) and try again';
                }
                break;
        }
    }
    if(!$errors)
        $thisstaff ->resetStats(); //We'll need to reflect any changes just made!
endif;

/*... Quick stats ...*/
$stats= $thisstaff->getTicketsStats();

// Switch queues on the fly! depending on stats
if(!$stats['open'] && $_REQUEST['a']!='search' && (!$_REQUEST['status'] || $_REQUEST['status']=='open')) {
    if(!$cfg->showAnsweredTickets() && $stats['answered'])
        $_REQUEST['status']= 'answered';
    else
        $_REQUEST['status']= 'closed';
}

//Navigation
$nav->setTabActive('tickets');
if($cfg->showAnsweredTickets()) {
    $nav->addSubMenu(array('desc'=>'Open ('.($stats['open']+$stats['answered']).')',
                            'title'=>'Open Tickets',
                            'href'=>'tickets.php',
                            'iconclass'=>'Ticket'),
                        (!$_REQUEST['status'] || $_REQUEST['status']=='open'));
} else {

    if(!$stats || $stats['open']) {
        $nav->addSubMenu(array('desc'=>'Open ('.$stats['open'].')',
                               'title'=>'Open Tickets',
                               'href'=>'tickets.php',
                               'iconclass'=>'Ticket'),
                            (!$_REQUEST['status'] || $_REQUEST['status']=='open'));
    }

    if($stats['answered']) {
        $nav->addSubMenu(array('desc'=>'Answered ('.$stats['answered'].')',
                               'title'=>'Answered Tickets',
                               'href'=>'tickets.php?status=answered',
                               'iconclass'=>'answeredTickets'),
                            ($_REQUEST['status']=='answered')); 
    }
}

if($stats['assigned']) {
    if(!$sysnotice && $stats['assigned']>10)
        $sysnotice=$stats['assigned'].' assigned to you!';

    $nav->addSubMenu(array('desc'=>'My Tickets ('.$stats['assigned'].')',
                           'title'=>'Assigned Tickets',
                           'href'=>'tickets.php?status=assigned',
                           'iconclass'=>'assignedTickets'),
                        ($_REQUEST['status']=='assigned'));
}

if($stats['overdue']) {
    $nav->addSubMenu(array('desc'=>'Overdue ('.$stats['overdue'].')',
                           'title'=>'Stale Tickets',
                           'href'=>'tickets.php?status=overdue',
                           'iconclass'=>'overdueTickets'),
                        ($_REQUEST['status']=='overdue'));

    if(!$sysnotice && $stats['overdue']>10)
        $sysnotice=$stats['overdue'] .' overdue tickets!';
}

if($thisstaff->showAssignedOnly() && $stats['closed']) {
    $nav->addSubMenu(array('desc'=>'My Closed Tickets ('.$stats['closed'].')',
                           'title'=>'My Closed Tickets',
                           'href'=>'tickets.php?status=closed',
                           'iconclass'=>'closedTickets'),
                        ($_REQUEST['status']=='closed'));
} else {

    $nav->addSubMenu(array('desc'=>'Closed Tickets',
                           'title'=>'Closed Tickets',
                           'href'=>'tickets.php?status=closed',
                           'iconclass'=>'closedTickets'),
                        ($_REQUEST['status']=='closed'));
}

if($thisstaff->canCreateTickets()) {
    $nav->addSubMenu(array('desc'=>'New Ticket',
                           'href'=>'tickets.php?a=open',
                           'iconclass'=>'newTicket'),
                        ($_REQUEST['a']=='open'));    
}


$inc = 'tickets.inc.php';
if($ticket) {
    $nav->setActiveSubMenu(-1);
    $inc = 'ticket-view.inc.php';
    if($_REQUEST['a']=='edit' && $thisstaff->canEditTickets()) 
        $inc = 'ticket-edit.inc.php';
} else {
    $inc = 'tickets.inc.php';
    if($_REQUEST['a']=='open' && $thisstaff->canCreateTickets())
        $inc = 'ticket-open.inc.php';
    elseif($_REQUEST['a'] == 'export') {
        require_once(INCLUDE_DIR.'class.export.php');
        $ts = strftime('%Y%m%d');
        if (!($token=$_REQUEST['h']))
            $errors['err'] = 'Query token required';
        elseif (!($query=$_SESSION['search_'.$token]))
            $errors['err'] = 'Query token not found';
        elseif (!Export::saveTickets($query, "tickets-$ts.csv", 'csv'))
            $errors['err'] = 'Internal error: Unable to dump query results';
    }

    //Clear active submenu on search with no status
    if($_REQUEST['a']=='search' && !$_REQUEST['status'])
        $nav->setActiveSubMenu(-1);

    //set refresh rate if the user has it configured
    if(!$_POST && $_REQUEST['a']!='search'  && ($min=$thisstaff->getRefreshRate()))
        define('AUTO_REFRESH', $min*60); 
}

require_once(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.$inc);
require_once(STAFFINC_DIR.'footer.inc.php');
?>
