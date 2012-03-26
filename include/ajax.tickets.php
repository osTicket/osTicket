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
        $ticketid=false;
        if(is_numeric($_REQUEST['q'])) {
            $WHERE=' WHERE ticketID LIKE \''.db_input($_REQUEST['q'], false).'%\'';
            $ticketid=true;
        } elseif(isset($_REQUEST['q'])) {
            $WHERE=' WHERE email LIKE \'%'.db_input(strtolower($_REQUEST['q']), false).'%\'';
        } else {
            Http::response(400, 'Query argument is required');
        }
        $sql='SELECT DISTINCT ticketID, email, name '
            .' FROM '.TICKET_TABLE.' '.$WHERE
            .' ORDER BY created '
            .' LIMIT '.$limit;

        if(($res=db_query($sql)) && db_num_rows($res)){
            while(list($id,$email,$name)=db_fetch_row($res)) {
                $info=($ticketid)?$email:$id;
                $id=($ticketid)?$id:$email;
                $items[] = array('id'=>$id, 'value'=>$id, 'info'=>$info,
                                 'name'=>$name);
            }
        }

        return $this->encode($items);
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


        $ticket = new Ticket($tid);

        $resp = sprintf(
                '<div style="width:500px;">
                 <strong>Ticket #%d Preview</strong><br>INFO HERE!!',
                 $ticket->getExtId());

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
            $resp.='<ul class="tip_menu">';
            foreach($options as $option) {
                $resp.=sprintf('<li><a href="%s">%s</a></li>',
                        $option['url'],$option['action']);
            }
            $resp.='</ul>';
        }

        $resp.='</div>';

        return $resp;
    }
}
?>
