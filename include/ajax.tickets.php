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
require_once(INCLUDE_DIR.'class.ajax.php');

class TicketsAjaxAPI extends AjaxController {

    function lookup() {
        global $thisstaff;

        if(!is_numeric($_REQUEST['q']))
            return self::lookupByEmail();


        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit']:25;
        $tickets=array();

        $sql='SELECT DISTINCT `number`, email.address AS email'
            .' FROM '.TICKET_TABLE.' ticket'
            .' LEFT JOIN '.USER_TABLE.' user ON user.id = ticket.user_id'
            .' LEFT JOIN '.USER_EMAIL_TABLE.' email ON user.id = email.user_id'
            .' WHERE `number` LIKE \''.db_input($_REQUEST['q'], false).'%\'';

        $sql.=' AND ( staff_id='.db_input($thisstaff->getId());

        if(($teams=$thisstaff->getTeams()) && count(array_filter($teams)))
            $sql.=' OR team_id IN('.implode(',', db_input(array_filter($teams))).')';

        if(!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts()))
            $sql.=' OR dept_id IN ('.implode(',', db_input($depts)).')';

        $sql.=' )  '
            .' ORDER BY ticket.created LIMIT '.$limit;

        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($id, $email)=db_fetch_row($res)) {
                $info = "$id - $email";
                $tickets[] = array('id'=>$id, 'email'=>$email, 'value'=>$id,
                    'info'=>$info, 'matches'=>$_REQUEST['q']);
            }
        }
        if (!$tickets)
            return self::lookupByEmail();

        return $this->json_encode($tickets);
    }

    function lookupByEmail() {
        global $thisstaff;


        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit']:25;
        $tickets=array();

        $sql='SELECT email.address AS email, count(ticket.ticket_id) as tickets '
            .' FROM '.TICKET_TABLE.' ticket'
            .' JOIN '.USER_TABLE.' user ON user.id = ticket.user_id'
            .' JOIN '.USER_EMAIL_TABLE.' email ON user.id = email.user_id'
            .' LEFT JOIN '.FORM_ENTRY_TABLE.' entry ON (entry.object_id = user.id
                AND entry.object_type=\'U\')
               LEFT JOIN '.FORM_ANSWER_TABLE.' data ON (data.entry_id = entry.id)'
            .' WHERE (email.address LIKE \'%'.db_input(strtolower($_REQUEST['q']), false).'%\'
                OR data.value LIKE \'%'.db_input($_REQUEST['q'], false).'%\')';

        $sql.=' AND ( staff_id='.db_input($thisstaff->getId());

        if(($teams=$thisstaff->getTeams()) && count(array_filter($teams)))
            $sql.=' OR team_id IN('.implode(',', db_input(array_filter($teams))).')';

        if(!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts()))
            $sql.=' OR dept_id IN ('.implode(',', db_input($depts)).')';

        $sql.=' ) '
            .' GROUP BY email.address '
            .' ORDER BY ticket.created  LIMIT '.$limit;

        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($email, $count)=db_fetch_row($res))
                $tickets[] = array('email'=>$email, 'value'=>$email,
                    'info'=>"$email ($count)", 'matches'=>$_REQUEST['q']);
        }

        return $this->json_encode($tickets);
    }

    function _search($req) {
        global $thisstaff, $cfg;

        $result=array();
        $select = 'SELECT ticket.ticket_id';
        $from = ' FROM '.TICKET_TABLE.' ticket ';
        //Access control.
        $where = ' WHERE ( (ticket.staff_id='.db_input($thisstaff->getId())
                    .' AND ticket.status="open" )';

        if(($teams=$thisstaff->getTeams()) && count(array_filter($teams)))
            $where.=' OR (ticket.team_id IN ('.implode(',', db_input(array_filter($teams)))
                   .' ) AND ticket.status="open")';

        if(!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts()))
            $where.=' OR ticket.dept_id IN ('.implode(',', db_input($depts)).')';

        $where.=' ) ';

        //Department
        if($req['deptId'])
            $where.=' AND ticket.dept_id='.db_input($req['deptId']);

        //Help topic
        if($req['topicId'])
            $where.=' AND ticket.topic_id='.db_input($req['topicId']);

        //Status
        switch(strtolower($req['status'])) {
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
        if(isset($req['assignee']) && strcasecmp($req['status'], 'closed'))  {
            $id=preg_replace("/[^0-9]/", "", $req['assignee']);
            $assignee = $req['assignee'];
            $where.= ' AND ( ( ticket.status="open" ';
            if($assignee[0]=='t')
                $where.=' AND ticket.team_id='.db_input($id);
            elseif($assignee[0]=='s')
                $where.=' AND ticket.staff_id='.db_input($id);
            elseif(is_numeric($id))
                $where.=' AND ticket.staff_id='.db_input($id);

            $where.=')';

            if($req['staffId'] && !$req['status']) //Assigned TO + Closed By
                $where.= ' OR (ticket.staff_id='.db_input($req['staffId']). ' AND ticket.status="closed") ';
            elseif(isset($req['staffId'])) // closed by any
                $where.= ' OR ticket.status="closed" ';

            $where.= ' ) ';
        } elseif($req['staffId']) {
            $where.=' AND (ticket.staff_id='.db_input($req['staffId']).' AND ticket.status="closed") ';
        }

        //dates
        $startTime  =($req['startDate'] && (strlen($req['startDate'])>=8))?strtotime($req['startDate']):0;
        $endTime    =($req['endDate'] && (strlen($req['endDate'])>=8))?strtotime($req['endDate']):0;
        if( ($startTime && $startTime>time()) or ($startTime>$endTime && $endTime>0))
            $startTime=$endTime=0;

        if($startTime)
            $where.=' AND ticket.created>=FROM_UNIXTIME('.$startTime.')';

        if($endTime)
            $where.=' AND ticket.created<=FROM_UNIXTIME('.$endTime.')';

        //Query
        $joins = array();
        if($req['query']) {
            $queryterm=db_real_escape($req['query'], false);

            // Setup sets of joins and queries
            $joins[] = array(
                'from' =>
                    'LEFT JOIN '.TICKET_THREAD_TABLE.' thread ON (ticket.ticket_id=thread.ticket_id )',
                'where' => "thread.title LIKE '%$queryterm%' OR thread.body LIKE '%$queryterm%'"
            );
            $joins[] = array(
                'from' =>
                    'LEFT JOIN '.FORM_ENTRY_TABLE.' tentry ON (tentry.object_id = ticket.ticket_id AND tentry.object_type="T")
                    LEFT JOIN '.FORM_ANSWER_TABLE.' tans ON (tans.entry_id = tentry.id AND tans.value_id IS NULL)',
                'where' => "tans.value LIKE '%$queryterm%'"
            );
            $joins[] = array(
                'from' =>
                   'LEFT JOIN '.FORM_ENTRY_TABLE.' uentry ON (uentry.object_id = ticket.user_id
                   AND uentry.object_type="U")
                   LEFT JOIN '.FORM_ANSWER_TABLE.' uans ON (uans.entry_id = uentry.id
                   AND uans.value_id IS NULL)
                   LEFT JOIN '.USER_TABLE.' user ON (ticket.user_id = user.id)
                   LEFT JOIN '.USER_EMAIL_TABLE.' uemail ON (user.id = uemail.user_id)',
                'where' =>
                    "uemail.address LIKE '%$queryterm%' OR user.name LIKE '%$queryterm%' OR uans.value LIKE '%$queryterm%'",
            );
        }

        // Dynamic fields
        $cdata_search = false;
        foreach (TicketForm::getInstance()->getFields() as $f) {
            if (isset($req[$f->getFormName()])
                    && ($val = $req[$f->getFormName()])) {
                $name = $f->get('name') ? $f->get('name')
                    : 'field_'.$f->get('id');
                if ($f->getImpl()->hasIdValue() && is_numeric($val))
                    $cwhere = "cdata.`{$name}_id` = ".db_input($val);
                else
                    $cwhere = "cdata.`$name` LIKE '%".db_real_escape($val)."%'";
                $where .= ' AND ('.$cwhere.')';
                $cdata_search = true;
            }
        }
        if ($cdata_search)
            $from .= 'LEFT JOIN '.TABLE_PREFIX.'ticket__cdata '
                    ." cdata ON (cdata.ticket_id = ticket.ticket_id)";

        $sections = array();
        foreach ($joins as $j) {
            $sections[] = "$select $from {$j['from']} $where AND ({$j['where']})";
        }
        if (!$joins)
            $sections[] = "$select $from $where";

        $sql=implode(' union ', $sections);
        if (!($res = db_query($sql)))
            return TicketForm::dropDynamicDataView();

        $tickets = array();
        while ($row = db_fetch_row($res))
            $tickets[] = $row[0];

        return $tickets;
    }

    function search() {
        $tickets = self::_search($_REQUEST);
        $result = array();

        if (count($tickets)) {
            $uid = md5($_SERVER['QUERY_STRING']);
            $_SESSION["adv_$uid"] = $tickets;
            $result['success'] =sprintf(
                "Search criteria matched %d %s - <a href='tickets.php?%s'>view</a>",
                count($tickets), (count($tickets)>1?"tickets":"ticket"),
                'advsid='.$uid
            );
        } else {
            $result['fail']='No tickets found matching your search criteria.';
        }

        return $this->json_encode($result);
    }

    function acquireLock($tid) {
        global $cfg,$thisstaff;

        if(!$tid || !is_numeric($tid) || !$thisstaff || !$cfg || !$cfg->getLockTime())
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

        if(!$tid || !is_numeric($tid) || !$id || !is_numeric($id) || !$thisstaff)
            return $this->json_encode(array('id'=>0, 'retry'=>true));

        $lock= TicketLock::lookup($id, $tid);
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

        ob_start();
        include STAFFINC_DIR . 'templates/ticket-preview.tmpl.php';
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
    }

    function addRemoteCollaborator($tid, $bk, $id) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffAccess($thisstaff))
            Http::response(404, 'No such ticket');
        elseif (!$bk || !$id)
            Http::response(422, 'Backend and user id required');
        elseif (!($backend = StaffAuthenticationBackend::getBackend($bk)))
            Http::response(404, 'User not found');

        $user_info = $backend->lookup($id);
        $form = UserForm::getUserForm()->getForm($user_info);
        $info = array();
        if (!$user_info)
            $info['error'] = 'Unable to find user in directory';

        return self::_addcollaborator($ticket, null, $form, $info);
    }

    //Collaborators utils
    function addCollaborator($tid, $uid=0) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffAccess($thisstaff))
            Http::response(404, 'No such ticket');


        $user = $uid? User::lookup($uid) : null;

        //If not a post then assume new collaborator form
        if(!$_POST)
            return self::_addcollaborator($ticket, $user);

        $user = $form = null;
        if (isset($_POST['id']) && $_POST['id']) { //Existing user/
            $user =  User::lookup($_POST['id']);
        } else { //We're creating a new user!
            $form = UserForm::getUserForm()->getForm($_POST);
            $user = User::fromForm($form);
        }

        $errors = $info = array();
        if ($user) {
            if ($user->getId() == $ticket->getOwnerId())
                $errors['err'] = sprintf('Ticket owner, %s, is a collaborator by default!',
                        $user->getName());
            elseif (($c=$ticket->addCollaborator($user,
                            array('isactive'=>1), $errors))) {
                $note = Format::htmlchars(sprintf('%s <%s> added as a collaborator',
                            $c->getName(), $c->getEmail()));
                $ticket->logNote('New Collaborator Added', $note,
                    $thisstaff, false);
                $info = array('msg' => sprintf('%s added as a collaborator',
                            $c->getName()));
                return self::_collaborators($ticket, $info);
            }
        }

        if($errors && $errors['err']) {
            $info +=array('error' => $errors['err']);
        } else {
            $info +=array('error' =>'Unable to add collaborator - try again');
        }

        return self::_addcollaborator($ticket, $user, $form, $info);
    }

    function updateCollaborator($cid) {
        global $thisstaff;

        if(!($c=Collaborator::lookup($cid))
                || !($user=$c->getUser())
                || !($ticket=$c->getTicket())
                || !$ticket->checkStaffAccess($thisstaff)
                )
            Http::response(404, 'Unknown collaborator');

        $errors = array();
        if(!$user->updateInfo($_POST, $errors))
            return self::_collaborator($c ,$user->getForms($_POST), $errors);

        $info = array('msg' => sprintf('%s updated successfully',
                    $c->getName()));

        return self::_collaborators($ticket, $info);
    }

    function viewCollaborator($cid) {
        global $thisstaff;

        if(!($collaborator=Collaborator::lookup($cid))
                || !($ticket=$collaborator->getTicket())
                || !$ticket->checkStaffAccess($thisstaff))
            Http::response(404, 'Unknown collaborator');

        return self::_collaborator($collaborator);
    }

    function showCollaborators($tid) {
        global $thisstaff;

        if(!($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffAccess($thisstaff))
            Http::response(404, 'No such ticket');

        if($ticket->getCollaborators())
            return self::_collaborators($ticket);

        return self::_addcollaborator($ticket);
    }

    function previewCollaborators($tid) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffAccess($thisstaff))
            Http::response(404, 'No such ticket');

        ob_start();
        include STAFFINC_DIR . 'templates/collaborators-preview.tmpl.php';
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
    }

    function _addcollaborator($ticket, $user=null, $form=null, $info=array()) {

        $info += array(
                    'title' => sprintf('Ticket #%s: Add a collaborator', $ticket->getNumber()),
                    'action' => sprintf('#tickets/%d/add-collaborator', $ticket->getId()),
                    'onselect' => sprintf('ajax.php/tickets/%d/add-collaborator/', $ticket->getId()),
                    );
        return self::_userlookup($user, $form, $info);
    }


    function updateCollaborators($tid) {
        global $thisstaff;

        if(!($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffAccess($thisstaff))
            Http::response(404, 'No such ticket');

        $errors = $info = array();
        if ($ticket->updateCollaborators($_POST, $errors))
            Http::response(201, sprintf('Recipients (%d of %d)',
                        $ticket->getNumActiveCollaborators(),
                        $ticket->getNumCollaborators()));

        if($errors && $errors['err'])
            $info +=array('error' => $errors['err']);

        return self::_collaborators($ticket, $info);
    }



    function _collaborator($collaborator, $form=null, $info=array()) {

        $info += array('action' => '#collaborators/'.$collaborator->getId());

        $user = $collaborator->getUser();

        ob_start();
        include(STAFFINC_DIR . 'templates/user.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
    }

    function _collaborators($ticket, $info=array()) {

        ob_start();
        include(STAFFINC_DIR . 'templates/collaborators.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();

        return $resp;
    }

    function viewUser($tid) {
        global $thisstaff;

        if(!$thisstaff
                || !($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffAccess($thisstaff))
            Http::response(404, 'No such ticket');


        if(!($user = User::lookup($ticket->getOwnerId())))
            Http::response(404, 'Unknown user');


        $info = array(
            'title' => sprintf('Ticket #%s: %s', $ticket->getNumber(),
                Format::htmlchars($user->getName()))
            );

        ob_start();
        include(STAFFINC_DIR . 'templates/user.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;

    }

    function updateUser($tid) {

        global $thisstaff;

        if(!$thisstaff
                || !($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffAccess($thisstaff)
                || !($user = User::lookup($ticket->getOwnerId())))
            Http::response(404, 'No such ticket/user');

        $errors = array();
        if($user->updateInfo($_POST, $errors))
             Http::response(201, $user->to_json());

        $forms = $user->getForms();

        $info = array(
            'title' => sprintf('Ticket #%s: %s', $ticket->getNumber(),
                Format::htmlchars($user->getName()))
            );

        ob_start();
        include(STAFFINC_DIR . 'templates/user.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;
    }

    function changeUserForm($tid) {
        global $thisstaff;

        if(!$thisstaff
                || !($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffAccess($thisstaff))
            Http::response(404, 'No such ticket');


        $user = User::lookup($ticket->getOwnerId());

        $info = array(
                'title' => sprintf('Change user for ticket #%s', $ticket->getNumber())
                );

        return self::_userlookup($user, null, $info);
    }

    function _userlookup($user, $form, $info) {

        ob_start();
        include(STAFFINC_DIR . 'templates/user-lookup.tmpl.php');
        $resp = ob_get_contents();
        ob_end_clean();
        return $resp;

    }

}
?>
