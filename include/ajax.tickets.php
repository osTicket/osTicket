<?php
/*********************************************************************
    ajax.tickets.php

    AJAX interface for tickets

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

if(!defined('INCLUDE_DIR')) die('403');

include_once(INCLUDE_DIR.'class.ticket.php');

class TicketsAjaxAPI extends AjaxController {

    function lookup() {
        global $thisstaff;

        if(!is_numeric($_REQUEST['q']))
            return self::lookupByEmail();


        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit']:25;
        $tickets=array();

        $sql='SELECT DISTINCT ticketID, email'
            .' FROM '.TICKET_TABLE
            .' WHERE ticketID LIKE \''.db_input($_REQUEST['q'], false).'%\'';

        $sql.=' AND ( staff_id='.db_input($thisstaff->getId());

        if(($teams=$thisstaff->getTeams()) && count(array_filter($teams)))
            $sql.=' OR team_id IN('.implode(',', db_input(array_filter($teams))).')';

        if(!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts()))
            $sql.=' OR dept_id IN ('.implode(',', db_input($depts)).')';

        $sql.=' )  '
            .' ORDER BY created  LIMIT '.$limit;

        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($id, $email)=db_fetch_row($res))
                $tickets[] = array('id'=>$id, 'email'=>$email, 'value'=>$id, 'info'=>"$id - $email");
        }

        return $this->json_encode($tickets);
    }

    function lookupByEmail() {
        global $thisstaff;


        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit']:25;
        $tickets=array();

        $sql='SELECT email, count(ticket_id) as tickets '
            .' FROM '.TICKET_TABLE
            .' WHERE email LIKE \'%'.db_input(strtolower($_REQUEST['q']), false).'%\' ';

        $sql.=' AND ( staff_id='.db_input($thisstaff->getId());

        if(($teams=$thisstaff->getTeams()) && count(array_filter($teams)))
            $sql.=' OR team_id IN('.implode(',', db_input(array_filter($teams))).')';

        if(!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts()))
            $sql.=' OR dept_id IN ('.implode(',', db_input($depts)).')';

        $sql.=' ) '
            .' GROUP BY email '
            .' ORDER BY created  LIMIT '.$limit;

        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($email, $count)=db_fetch_row($res))
                $tickets[] = array('email'=>$email, 'value'=>$email, 'info'=>"$email ($count)");
        }

        return $this->json_encode($tickets);
    }

    function search() {
        global $thisstaff, $cfg;

        $result=array();
        $select = 'SELECT count( DISTINCT ticket.ticket_id) as tickets ';
        $from = ' FROM '.TICKET_TABLE.' ticket ';
        $where = ' WHERE 1 ';

        //Access control.
        $where.=' AND ( ticket.staff_id='.db_input($thisstaff->getId());

        if(($teams=$thisstaff->getTeams()) && count(array_filter($teams)))
            $where.=' OR ticket.team_id IN('.implode(',', db_input(array_filter($teams))).')';

        if(!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts()))
            $where.=' OR ticket.dept_id IN ('.implode(',', db_input($depts)).')';

        $where.=' ) ';

        //Department
        if($_REQUEST['deptId'])
            $where.=' AND ticket.dept_id='.db_input($_REQUEST['deptId']);

        //Help topic
        if($_REQUEST['topicId'])
            $where.=' AND ticket.topic_id='.db_input($_REQUEST['topicId']);

        //Status
        switch(strtolower($_REQUEST['status'])) {
            case 'open':
                $where.=' AND ticket.status="open" ';
                break;
            case 'answered':
                $where.=' AND ticket.status="open" AND ticket.isanswered=1 ';
                break;
            case 'overdue':
                $where.=' AND ticket.status="open" AND ticket.isoverdue=1 ';
                break;
            case 'closed':
                $where.=' AND ticket.status="closed" ';
                break;
        }

        //Assignee
        if(isset($_REQUEST['assignee']) && strcasecmp($_REQUEST['status'], 'closed'))  {
            $id=preg_replace("/[^0-9]/", "", $_REQUEST['assignee']);
            $assignee = $_REQUEST['assignee'];
            $where.= ' AND ( ( ticket.status="open" ';
            if($assignee[0]=='t')
                $where.=' AND ticket.team_id='.db_input($id);
            elseif($assignee[0]=='s')
                $where.=' AND ticket.staff_id='.db_input($id);
            elseif(is_numeric($id))
                $where.=' AND ticket.staff_id='.db_input($id);

            $where.=')';

            if($_REQUEST['staffId'] && !$_REQUEST['status']) //Assigned TO + Closed By
                $where.= ' OR (ticket.staff_id='.db_input($_REQUEST['staffId']). ' AND ticket.status="closed") ';
            elseif(isset($_REQUEST['staffId'])) // closed by any
                $where.= ' OR ticket.status="closed" ';

            $where.= ' ) ';
        } elseif($_REQUEST['staffId']) {
            $where.=' AND (ticket.staff_id='.db_input($_REQUEST['staffId']).' AND ticket.status="closed") ';
        }

        //dates
        $startTime  =($_REQUEST['startDate'] && (strlen($_REQUEST['startDate'])>=8))?strtotime($_REQUEST['startDate']):0;
        $endTime    =($_REQUEST['endDate'] && (strlen($_REQUEST['endDate'])>=8))?strtotime($_REQUEST['endDate']):0;
        if( ($startTime && $startTime>time()) or ($startTime>$endTime && $endTime>0))
            $startTime=$endTime=0;

        if($startTime)
            $where.=' AND ticket.created>=FROM_UNIXTIME('.$startTime.')';

        if($endTime)
            $where.=' AND ticket.created<=FROM_UNIXTIME('.$endTime.')';

        //Query
        if($_REQUEST['query']) {
            $queryterm=db_real_escape($_REQUEST['query'], false);

            $from.=' LEFT JOIN '.TICKET_THREAD_TABLE.' thread ON (ticket.ticket_id=thread.ticket_id )';
            $where.=" AND (  ticket.email LIKE '%$queryterm%'"
                       ." OR ticket.name LIKE '%$queryterm%'"
                       ." OR ticket.subject LIKE '%$queryterm%'"
                       ." OR thread.title LIKE '%$queryterm%'"
                       ." OR thread.body LIKE '%$queryterm%'"
                       .' )';
        }

        $sql="$select $from $where";
        if(($tickets=db_result(db_query($sql)))) {
            $result['success'] =sprintf("Search criteria matched %s - <a href='tickets.php?%s'>view</a>",
                                        ($tickets>1?"$tickets tickets":"$tickets ticket"),
                                        str_replace(array('&amp;', '&'), array('&', '&amp;'), $_SERVER['QUERY_STRING']));
        } else {
            $result['fail']='No tickets found matching your search criteria.';
        }

        return $this->json_encode($result);
    }

    function acquireLock($tid) {
        global $cfg,$thisstaff;

        if(!$tid or !is_numeric($tid) or !$thisstaff or !$cfg or !$cfg->getLockTime())
            return 0;

        if(!($ticket = Ticket::lookup($tid)) || !$ticket->checkStaffAccess($thisstaff))
            return $this->json_encode(array('id'=>0, 'retry'=>false, 'msg'=>'Lock denied!'));

        //is the ticket already locked?
        if($ticket->isLocked() && ($lock=$ticket->getLock()) && !$lock->isExpired()) {
            /*Note: Ticket->acquireLock does the same logic...but we need it here since we need to know who owns the lock up front*/
            //Ticket is locked by someone else.??
            if($lock->getStaffId()!=$thisstaff->getId())
                return $this->json_encode(array('id'=>0, 'retry'=>false, 'msg'=>'Unable to acquire lock.'));

            //Ticket already locked by staff...try renewing it.
            $lock->renew(); //New clock baby!
        } elseif(!($lock=$ticket->acquireLock($thisstaff->getId(),$cfg->getLockTime()))) {
            //unable to obtain the lock..for some really weired reason!
            //Client should watch for possible loop on retries. Max attempts?
            return $this->json_encode(array('id'=>0, 'retry'=>true));
        }

        return $this->json_encode(array('id'=>$lock->getId(), 'time'=>$lock->getTime()));
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
        } elseif($ticket->getEstDueDate()) {
            echo sprintf('
                    <tr>
                        <th>Due Date:</th>
                        <td>%s</td>
                    </tr>',
                    Format::db_datetime($ticket->getEstDueDate()));
        }
        echo '</table>';


        echo '<hr>
            <table border="0" cellspacing="" cellpadding="1" width="100%" class="ticket_info">';
        if($ticket->isOpen()) {
            echo sprintf('
                    <tr>
                        <th width="100">Assigned To:</th>
                        <td>%s</td>
                    </tr>',$ticket->isAssigned()?implode('/', $ticket->getAssignees()):' <span class="faded">&mdash; Unassigned &mdash;</span>');
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

        if($thisstaff->canEditTickets())
            $options[]=array('action'=>'Edit Ticket','url'=>"tickets.php?id=$tid&a=edit");

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
