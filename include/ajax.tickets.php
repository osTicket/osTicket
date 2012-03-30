<?php
/*********************************************************************
    ajax.tickets.php

    AJAX interface for tickets

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

if(!defined('INCLUDE_DIR')) die('403');

include_once(INCLUDE_DIR.'class.ticket.php');

class TicketsAjaxAPI extends AjaxController {
   
    function search() {

        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit']:25;
        $items=array();

        $sql='SELECT DISTINCT ticketID, email'
            .' FROM '.TICKET_TABLE;

        $emailSearch=false;
        if(is_numeric($_REQUEST['q']))
            $sql.=' WHERE ticketID LIKE \''.db_input($_REQUEST['q'], false).'%\'';
        else {
            $emailSearch=true;
            $sql.=' WHERE email LIKE \'%'.db_input(strtolower($_REQUEST['q']), false).'%\' ';
        }

        $sql.=' ORDER BY created  LIMIT '.$limit;

        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($id,$email,$name)=db_fetch_row($res)) {
                if($emailSearch) {
                    $info = "$email - $id";
                    $value = $email;
                } else {
                    $info = "$id -$email";
                    $value = $id;
                }

                $items[] = array('id'=>$id, 'email'=>$email, 'value'=>$value, 'info'=>$info);
            }
        }

        return $this->json_encode($items);
    }

    function acquireLock($tid) {
        global $cfg,$thisstaff;
        
        if(!$tid or !is_numeric($tid) or !$thisstaff or !$cfg) 
            return 0;
       
        $ticket = Ticket::lookup($tid);
        
        if(!$ticket || !$ticket->checkStaffAccess($thisstaff))
            return $this->json_encode(array('id'=>0, 'retry'=>false, 'msg'=>'Lock denied!'));
        
        //is the ticket already locked?
        if($ticket->isLocked() && ($lock=$ticket->getLock()) && !$lock->isExpired()) {
            /*Note: Ticket->acquireLock does the same logic...but we need it here since we need to know who owns the lock up front*/
            //Ticket is locked by someone else.??
            if($lock->getStaffId()!=$thisstaff->getId())
                return $this->json_encode(array('id'=>0, 'retry'=>false, 'msg'=>'Unable to acquire lock.'));
            
            //Ticket already locked by staff...try renewing it.
            $lock->renew(); //New clock baby!
            
            return $this->json_encode(array('id'=>$lock->getId(), 'time'=>$lock->getTime()));
        }
        
        //Ticket is not locked or the lock is expired...try locking it...
        if($lock=$ticket->acquireLock($thisstaff->getId(),$cfg->getLockTime())) //Set the lock.
            return $this->json_encode(array('id'=>$lock->getId(), 'time'=>$lock->getTime()));
        
        //unable to obtain the lock..for some really weired reason!
        //Client should watch for possible loop on retries. Max attempts?
        return $this->json_encode(array('id'=>0, 'retry'=>true));
    }

    function renewLock($tid, $id) {
        global $thisstaff;

        if(!$id or !is_numeric($id) or !$thisstaff)
            return $this->json_encode(array('id'=>0, 'retry'=>true));
       
        $lock= TicketLock::lookup($id);
        if(!$lock || !$lock->getStaffId() || $lock->isExpired()) //Said lock doesn't exist or is is expired
            return self::acquireLock($tid); //acquire the lock
        
        if($lock->getStaffId()!=$thisstaff->getId()) //user doesn't own the lock anymore??? sorry...try to next time.
            return $this->json_encode(array('id'=>0, 'retry'=>false)); //Give up...
   
        //Renew the lock.
        $lock->renew(); //Failure here is not an issue since the lock is not expired yet.. client need to check time!
        
        return $this->json_encode(array('id'=>$lock->getId(), 'time'=>$lock->getTime()));
    }

    function releaseLock($tid, $id=0) {
        global $thisstaff;

        if($id && is_numeric($id)){ //Lock Id provided!
        
            $lock = TicketLock::lookup($id, $tid);
            //Already gone?
            if(!$lock || !$lock->getStaffId() || $lock->isExpired()) //Said lock doesn't exist or is is expired
                return 1;
        
            //make sure the user actually owns the lock before releasing it.
            return ($lock->getStaffId()==$thisstaff->getId() && $lock->release())?1:0;

        }elseif($tid){ //release all the locks the user owns on the ticket.
            return TicketLock::removeStaffLocks($thisstaff->getId(),$tid)?1:0;
        }

        return 0;
    }

    function previewTicket ($tid) {

        global $thisstaff;

        if(!$thisstaff || !($ticket=Ticket::lookup($tid)) || !$ticket->checkStaffAccess($thisstaff))
            Http::response(404, 'No such ticket');

        $staff=$ticket->getStaff();
        $lock=$ticket->getLock();
        $error=$msg=$warn=null;

        if($lock && $lock->getStaffId()==$thisstaff->getId())
            $warn.='&nbsp;<span class="Icon lockedTicket">Ticket is locked by '.$lock->getStaffName().'</span>';
        elseif($ticket->isOverdue())
            $warn.='&nbsp;<span class="Icon overdueTicket">Marked overdue!</span>';
       
        ob_start();
        echo sprintf(
                '<div style="width:500px; padding: 2px 2px 0 5px;">
                 <h2>%s</h2><br>',Format::htmlchars($ticket->getSubject()));

        if($error)
            echo sprintf('<div id="msg_error">%s</div>',$error);
        elseif($msg)
            echo sprintf('<div id="msg_notice">%s</div>',$msg);
        elseif($warn)
            echo sprintf('<div id="msg_warning">%s</div>',$warn);

        echo '<table border="0" cellspacing="" cellpadding="1" width="100%" class="ticket_info">';

        $ticket_state=sprintf('<span>%s</span>',ucfirst($ticket->getStatus()));
        if($ticket->isOpen()) {
            if($ticket->isOverdue())
                $ticket_state.=' &mdash; <span>Overdue</span>';
            else
                $ticket_state.=sprintf(' &mdash; <span>%s</span>',$ticket->getPriority());
        }

        echo sprintf('
                <tr>
                    <th width="100">Ticket State:</th>
                    <td>%s</td>
                </tr>
                <tr>
                    <th>Create Date:</th>
                    <td>%s</td>
                </tr>',$ticket_state,
                Format::db_datetime($ticket->getCreateDate()));
        if($ticket->isClosed()) {
            echo sprintf('
                    <tr>
                        <th>Close Date:</th>
                        <td>%s   <span class="faded">by %s</span></td>
                    </tr>',
                    Format::db_datetime($ticket->getCloseDate()),
                    ($staff?$staff->getName():'staff')
                    );
        } elseif($ticket->getDueDate()) {
            echo sprintf('
                    <tr>
                        <th>Due Date:</th>
                        <td>%s</td>
                    </tr>',
                    Format::db_datetime($ticket->getDueDate()));
        }
        echo '</table>';


        echo '<hr>
            <table border="0" cellspacing="" cellpadding="1" width="100%" class="ticket_info">';
        if($ticket->isOpen()) {
            echo sprintf('
                    <tr>
                        <th width="100">Assigned To:</th>
                        <td>%s</td>
                    </tr>',$ticket->isAssigned()?$ticket->getAssignee():' <span class="faded">&mdash; Unassigned &mdash;</span>');
        }
        echo sprintf(
            '   <tr>
                    <th width="100">Department:</th>
                    <td>%s</td>
                </tr>
                <tr>
                    <th>Help Topic:</th>
                    <td>%s</td>
                </tr>
                <tr>
                    <th>From:</th>
                    <td>%s <span class="faded">%s</span></td>
                </tr>',
            Format::htmlchars($ticket->getDeptName()),
            Format::htmlchars($ticket->getHelpTopic()),
            Format::htmlchars($ticket->getName()),
            $ticket->getEmail());
        echo '
            </table>';
        $options[]=array('action'=>'Thread ('.$ticket->getThreadCount().')','url'=>"tickets.php?id=$tid");
        if($ticket->getNumNotes())
            $options[]=array('action'=>'Notes ('.$ticket->getNumNotes().')','url'=>"tickets.php?id=$tid#notes");
        
        if($ticket->isOpen())
            $options[]=array('action'=>'Reply','url'=>"tickets.php?id=$tid#reply");

        if($thisstaff->canAssignTickets())
            $options[]=array('action'=>($ticket->isAssigned()?'Reassign':'Assign'),'url'=>"tickets.php?id=$tid#assign");

        if($thisstaff->canTransferTickets())
            $options[]=array('action'=>'Transfer','url'=>"tickets.php?id=$tid#transfer");

        $options[]=array('action'=>'Post Note','url'=>"tickets.php?id=$tid#note");

        if($options) {
            echo '<ul class="tip_menu">';
            foreach($options as $option)
                echo sprintf('<li><a href="%s">%s</a></li>',$option['url'],$option['action']);
            echo '</ul>';
        }

        echo '</div>';
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
    }
}
?>
