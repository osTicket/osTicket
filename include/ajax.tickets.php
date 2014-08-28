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
require_once(INCLUDE_DIR.'class.note.php');

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
            .' WHERE (email.address LIKE \'%'.db_input(strtolower($_REQUEST['q']), false).'%\'
                OR user.name LIKE \'%'.db_input($_REQUEST['q'], false).'%\')';

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
        global $thisstaff, $cfg, $ost;

        $result=array();
        $criteria = array();

        $select = 'SELECT ticket.ticket_id';
        $from = ' FROM '.TICKET_TABLE.' ticket
                  LEFT JOIN '.TICKET_STATUS_TABLE.' status
                    ON (status.id = ticket.status_id) ';
        //Access control.
        $where = ' WHERE ( (ticket.staff_id='.db_input($thisstaff->getId())
                    .' AND status.state="open" )';

        if(($teams=$thisstaff->getTeams()) && count(array_filter($teams)))
            $where.=' OR (ticket.team_id IN ('.implode(',', db_input(array_filter($teams)))
                   .' ) AND status.state="open" )';

        if(!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts()))
            $where.=' OR ticket.dept_id IN ('.implode(',', db_input($depts)).')';

        $where.=' ) ';

        //Department
        if ($req['deptId']) {
            $where.=' AND ticket.dept_id='.db_input($req['deptId']);
            $criteria['dept_id'] = $req['deptId'];
        }

        //Help topic
        if($req['topicId']) {
            $where.=' AND ticket.topic_id='.db_input($req['topicId']);
            $criteria['topic_id'] = $req['topicId'];
        }

        // Status
        if ($req['statusId']
                && ($status=TicketStatus::lookup($req['statusId']))) {
            $where .= sprintf(' AND status.state="%s" ',
                    $status->getState());
            $criteria['status_id'] = $status->getId();
        }

        // Flags
        if ($req['flag']) {
            switch (strtolower($req['flag'])) {
                case 'answered':
                    $where .= ' AND ticket.isanswered =1 ';
                    $criteria['isanswered'] = 1;
                    $criteria['state'] = 'open';
                    $where .= ' AND status.state="open" ';
                    break;
                case 'overdue':
                    $where .= ' AND ticket.isoverdue =1 ';
                    $criteria['isoverdue'] = 1;
                    $criteria['state'] = 'open';
                    $where .= ' AND status.state="open" ';
                    break;
            }
        }

        //Assignee
        if(isset($req['assignee']) && strcasecmp($req['status'], 'closed'))  {
            $id=preg_replace("/[^0-9]/", "", $req['assignee']);
            $assignee = $req['assignee'];
            $where.= ' AND ( ( status.state="open" ';
            if($assignee[0]=='t') {
                $where.=' AND ticket.team_id='.db_input($id);
                $criteria['team_id'] = $id;
            }
            elseif($assignee[0]=='s') {
                $where.=' AND ticket.staff_id='.db_input($id);
                $criteria['staff_id'] = $id;
            }
            elseif(is_numeric($id))
                $where.=' AND ticket.staff_id='.db_input($id);

            $where.=')';

            if($req['staffId'] && !$req['status']) //Assigned TO + Closed By
                $where.= ' OR (ticket.staff_id='.db_input($req['staffId']).
                    ' AND status.state IN("resolved", "closed")) ';
            elseif(isset($req['staffId'])) // closed by any
                $where.= ' OR status.state IN("resolved", "closed") ';

            $where.= ' ) ';
        } elseif($req['staffId']) {
            $where.=' AND (ticket.staff_id='.db_input($req['staffId']).' AND
                status.state IN("resolved", "closed")) ';
        }

        //dates
        $startTime  =($req['startDate'] && (strlen($req['startDate'])>=8))?strtotime($req['startDate']):0;
        $endTime    =($req['endDate'] && (strlen($req['endDate'])>=8))?strtotime($req['endDate']):0;
        if( ($startTime && $startTime>time()) or ($startTime>$endTime && $endTime>0))
            $startTime=$endTime=0;

        if($startTime) {
            $where.=' AND ticket.created>=FROM_UNIXTIME('.$startTime.')';
            $criteria['created__gte'] = $startTime;
        }

        if($endTime) {
            $where.=' AND ticket.created<=FROM_UNIXTIME('.$endTime.')';
            $criteria['created__lte'] = $startTime;
        }

        // Dynamic fields
        $cdata_search = false;
        foreach (TicketForm::getInstance()->getFields() as $f) {
            if (isset($req[$f->getFormName()])
                    && ($val = $req[$f->getFormName()])
                    && strlen(trim($val))) {
                $name = $f->get('name') ? $f->get('name')
                    : 'field_'.$f->get('id');
                if ($f->getImpl()->hasIdValue() && is_numeric($val)) {
                    $cwhere = "cdata.`{$name}_id` = ".db_input($val);
                    $criteria["cdata.{$name}_id"] = $val;
                }
                else {
                    $cwhere = "cdata.`$name` LIKE '%".db_real_escape($val)."%'";
                    $criteria["cdata.{$name}"] = $val;
                }
                $where .= ' AND ('.$cwhere.')';
                $cdata_search = true;
            }
        }
        if ($cdata_search)
            $from .= 'LEFT JOIN '.TABLE_PREFIX.'ticket__cdata '
                    ." cdata ON (cdata.ticket_id = ticket.ticket_id)";

        //Query
        $joins = array();
        if($req['query']) {
            // Setup sets of joins and queries
            if ($s = $ost->searcher)
               return $s->find($req['query'], $criteria, 'Ticket');
        }

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
            $result['success'] = sprintf(__("Search criteria matched %s"),
                    sprintf(_N('%d ticket', '%d tickets'), count($tickets),
                        $tickets))
                . " - <a href='tickets.php?advsid=$uid'>".__('view')."</a>";
        } else {
            $result['fail']=__('No tickets found matching your search criteria.');
        }

        return $this->json_encode($result);
    }

    function acquireLock($tid) {
        global $cfg,$thisstaff;

        if(!$tid || !is_numeric($tid) || !$thisstaff || !$cfg || !$cfg->getLockTime())
            return 0;

        if(!($ticket = Ticket::lookup($tid)) || !$ticket->checkStaffAccess($thisstaff))
            return $this->json_encode(array('id'=>0, 'retry'=>false, 'msg'=>__('Lock denied!')));

        //is the ticket already locked?
        if($ticket->isLocked() && ($lock=$ticket->getLock()) && !$lock->isExpired()) {
            /*Note: Ticket->acquireLock does the same logic...but we need it here since we need to know who owns the lock up front*/
            //Ticket is locked by someone else.??
            if($lock->getStaffId()!=$thisstaff->getId())
                return $this->json_encode(array('id'=>0, 'retry'=>false, 'msg'=>__('Unable to acquire lock.')));

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
            Http::response(404, __('No such ticket'));

        include STAFFINC_DIR . 'templates/ticket-preview.tmpl.php';
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
            $info['error'] = __('Unable to find user in directory');

        return self::_addcollaborator($ticket, null, $form, $info);
    }

    //Collaborators utils
    function addCollaborator($tid, $uid=0) {
        global $thisstaff;

        if (!($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffAccess($thisstaff))
            Http::response(404, __('No such ticket'));


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
                $errors['err'] = sprintf(__('Ticket owner, %s, is a collaborator by default!'),
                        Format::htmlchars($user->getName()));
            elseif (($c=$ticket->addCollaborator($user,
                            array('isactive'=>1), $errors))) {
                $note = Format::htmlchars(sprintf(__('%s <%s> added as a collaborator'),
                            Format::htmlchars($c->getName()), $c->getEmail()));
                $ticket->logNote(__('New Collaborator Added'), $note,
                    $thisstaff, false);
                $info = array('msg' => sprintf(__('%s added as a collaborator'),
                            Format::htmlchars($c->getName())));
                return self::_collaborators($ticket, $info);
            }
        }

        if($errors && $errors['err']) {
            $info +=array('error' => $errors['err']);
        } else {
            $info +=array('error' =>__('Unable to add collaborator. Internal error'));
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
                    Format::htmlchars($c->getName())));

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
                    'title' => sprintf(__('Ticket #%s: Add a collaborator'), $ticket->getNumber()),
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
            'title' => sprintf(__('Ticket #%s: %s'), $ticket->getNumber(),
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
            'title' => sprintf(__('Ticket #%s: %s'), $ticket->getNumber(),
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
                'title' => sprintf(__('Change user for ticket #%s'), $ticket->getNumber())
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

    function manageForms($ticket_id) {
        $forms = DynamicFormEntry::forTicket($ticket_id);
        $info = array('action' => '#tickets/'.Format::htmlchars($ticket_id).'/forms/manage');
        include(STAFFINC_DIR . 'templates/form-manage.tmpl.php');
    }

    function updateForms($ticket_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required");
        elseif (!($ticket = Ticket::lookup($ticket_id)))
            Http::response(404, "No such ticket");
        elseif (!$ticket->checkStaffAccess($thisstaff))
            Http::response(403, "Access Denied");
        elseif (!isset($_POST['forms']))
            Http::response(422, "Send updated forms list");

        // Add new forms
        $forms = DynamicFormEntry::forTicket($ticket_id);
        foreach ($_POST['forms'] as $sort => $id) {
            $found = false;
            foreach ($forms as $e) {
                if ($e->get('form_id') == $id) {
                    $e->set('sort', $sort);
                    $e->save();
                    $found = true;
                    break;
                }
            }
            // New form added
            if (!$found && ($new = DynamicForm::lookup($id))) {
                $f = $new->instanciate();
                $f->set('sort', $sort);
                $f->setTicketId($ticket_id);
                $f->save();
            }
        }

        // Deleted forms
        foreach ($forms as $idx => $e) {
            if (!in_array($e->get('form_id'), $_POST['forms']))
                $e->delete();
        }

        Http::response(201, 'Successfully managed');
    }

    function cannedResponse($tid, $cid, $format='text') {
        global $thisstaff, $cfg;

        if (!($ticket = Ticket::lookup($tid))
                || !$ticket->checkStaffAccess($thisstaff))
            Http::response(404, 'Unknown ticket ID');


        if ($cid && !is_numeric($cid)) {
            if (!($response=$ticket->getThread()->getVar($cid)))
                Http::response(422, 'Unknown ticket variable');

            // Ticket thread variables are assumed to be quotes
            $response = "<br/><blockquote>$response</blockquote><br/>";
            //  Return text if html thread is not enabled
            if (!$cfg->isHtmlThreadEnabled())
                $response = Format::html2text($response, 90);

            // XXX: assuming json format for now.
            return Format::json_encode(array('response' => $response));
        }

        if (!$cfg->isHtmlThreadEnabled())
            $format.='.plain';

        $varReplacer = function (&$var) use($ticket) {
            return $ticket->replaceVars($var);
        };

        include_once(INCLUDE_DIR.'class.canned.php');
        if (!$cid || !($canned=Canned::lookup($cid)) || !$canned->isEnabled())
            Http::response(404, 'No such premade reply');

        return $canned->getFormattedResponse($format, $varReplacer);
    }

    function changeTicketStatus($tid, $status) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Access denied');
        elseif (!$tid
                || !($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffAccess($thisstaff))
            Http::response(404, 'Unknown ticket #');

        $info = array();
        switch($status) {
            case 'open':
            case 'reopen':
                $state = 'open';
                break;
            case 'resolve':
                $state = 'resolved';
                break;
            case 'close':
                if (!$thisstaff->canCloseTickets())
                    Http::response(403, 'Access denied');
                $state = 'closed';
                break;
            case 'delete':
                if (!$thisstaff->canDeleteTickets())
                    Http::response(403, 'Access denied');
                $state = 'deleted';
                $info = array(
                        'warn'  => sprintf(__('Are you sure you want to DELETE %s?'),
                            __('this ticket')),
                        //TODO: remove message below once we ship data retention plug
                        'extra' => sprintf('<strong>%s</strong>',
                            __('Deleted tickets CANNOT be recovered,
                                including any associated attachments.')));
                break;
            default:
                $info['warn'] = sprintf('%s %s',
                        __('Unknown or invalid'), __('status'));
        }

        $verb = TicketStateField::getVerb($state);

        $info['action'] = sprintf('#tickets/%d/status/%s', $ticket->getId(), $status);
        $info['title'] = sprintf('%s %s #%s',
                $verb ?: $status,
                __('Ticket'), $ticket->getNumber());
        $info['status_id'] = $_REQUEST['status_id'] ?: $ticket->getStatusId();

        return self::_setStatus($state, $info);
    }

    function setTicketStatus($tid, $state) {
        global $thisstaff, $ost;

        if (!$thisstaff)
            Http::response(403, 'Access denied');
        elseif (!$tid
                || !($ticket=Ticket::lookup($tid))
                || !$ticket->checkStaffAccess($thisstaff))
            Http::response(404, 'Unknown ticket #');

        $errors = $info = array();
        if (!($status= TicketStatus::lookup($_REQUEST['status_id'])))
            $errors['status_id'] = sprintf('%s %s',
                    __('Unknown or invalid'), __('status'));
        elseif (!$errors) {
            // Make sure the agent has permission to set the status
            switch(mb_strtolower($status->getState())) {
                case 'open':
                    if (!$thisstaff->canCloseTickets()
                            && !$thisstaff->canCreateTickets())
                        $errors['err'] = sprintf(__('You do not have
                                    permission to %s.'),
                                __('reopen tickets'));
                    break;
                case 'resolved':
                case 'closed':
                    if (!$thisstaff->canCloseTickets())
                        $errors['err'] = sprintf(__('You do not have
                                    permission to %s.'),
                                __('resolve/close tickets'));
                    break;
                case 'deleted':
                    if (!$thisstaff->canDeleteTickets())
                        $errors['err'] = sprintf(__('You do not have
                                    permission to %s.'),
                                __('archive/delete tickets'));
                    break;
                default:
                    $errors['err'] = sprintf('%s %s',
                            __('Unknown or invalid'), __('status'));
            }
        }

        if ($ticket->setStatus($status, $_REQUEST['comments'])) {
            $_SESSION['::sysmsgs']['msg'] = sprintf(
                    __('Successfully updated status to %s'),
                    $status->getName());
            Http::response(201, 'Successfully processed');
        }

        $errors['err'] = __('Error updating ticket status');
        $info['errors'] = $errors;
        return self::_setStatus($state, $info);
    }

    function changeTicketsStatus($status, $id=0) {
        global $thisstaff, $cfg;

        if (!$thisstaff)
            Http::response(403, 'Access denied');

        $state = null;
        $info = array();
        switch($status) {
            case 'open':
            case 'reopen':
                $state = 'open';
                break;
            case 'resolve':
                $state = 'resolved';
                break;
            case 'close':
                if (!$thisstaff->canCloseTickets())
                    Http::response(403, 'Access denied');
                $state = 'closed';
                break;
            case 'delete':
                if (!$thisstaff->canDeleteTickets())
                    Http::response(403, 'Access denied');

                $state = 'deleted';
                $info = array(
                        'warn'  => sprintf(__('Are you sure you want to DELETE %s?'),
                            _N('selected ticket', 'selected tickets', $_REQUEST['count'])),
                        //TODO: remove message below once we ship data retention plug
                        'extra' => sprintf('<strong>%s</strong>',
                            __('Deleted tickets CANNOT be recovered,
                                including any associated attachments.')));
                break;
            default:
                $info['warn'] = sprintf('%s %s',
                        __('Unknown or invalid'), __('status'));
        }

        $info['title'] = sprintf('%s %s',
                TicketStateField::getVerb($state),
                 __('Tickets'));

        if ($_REQUEST['count'])
            $info['title'] .= sprintf(' &mdash; %d %s',
                    $_REQUEST['count'], __('selected'));

        $info['status_id'] = $id;

        return self::_setStatus($state, $info);
    }

    function setTicketsStatus($state) {
        global $thisstaff, $ost;

        $errors = $info = array();
        if (!$thisstaff || !$thisstaff->canManageTickets())
            $errors['err']=__('You do not have permission to mass manage tickets. Contact admin for such access');
        elseif (!$_REQUEST['tids'] || !count($_REQUEST['tids']))
            $errors['err']=sprintf(__('You must select at least %s.'),
                    __('one ticket'));
        elseif (!($status= TicketStatus::lookup($_REQUEST['status_id'])))
            $errors['status_id'] = sprintf('%s %s',
                    __('Unknown or invalid'), __('status'));
        elseif (!$errors) {
            // Make sure the agent has permission to set the status
            switch(mb_strtolower($status->getState())) {
                case 'open':
                    if (!$thisstaff->canCloseTickets()
                            && !$thisstaff->canCreateTickets())
                        $errors['err'] = sprintf(__('You do not have
                                    permission to %s.'),
                                __('reopen tickets'));
                    break;
                case 'resolved':
                case 'closed':
                    if (!$thisstaff->canCloseTickets())
                        $errors['err'] = sprintf(__('You do not have
                                    permission to %s.'),
                                __('resolve/close tickets'));
                    break;
                case 'deleted':
                    if (!$thisstaff->canDeleteTickets())
                        $errors['err'] = sprintf(__('You do not have
                                    permission to %s.'),
                                __('archive/delete tickets'));
                    break;
                default:
                    $errors['err'] = sprintf('%s %s',
                            __('Unknown or invalid'), __('status'));
            }
        }

        if (!$errors) {
            $i = 0;
            $count = count($_REQUEST['tids']);
            $comments = $_REQUEST['comments'];
            foreach ($_REQUEST['tids'] as $tid) {
                if (($ticket=Ticket::lookup($tid))
                        && $ticket->getStatusId() != $status->getId()
                        && $ticket->checkStaffAccess($thisstaff)
                        && $ticket->setStatus($status, $comments))
                    $i++;
            }

            if (!$i)
                $errors['err'] = sprintf(__('Unable to set status for %s'),
                        _N('selected ticket', 'selected tickets', $count));
            else {
                // Assume success
                if ($i==$count) {
                    $_SESSION['::sysmsgs']['msg'] = sprintf(
                            __('Successfully updated %s status to %s'),
                            _N('selected ticket', 'selected tickets',
                                $count),
                            $status->getName());
                } else {
                    $_SESSION['::sysmsgs']['warn'] = sprintf(
                            __('%1$d of %2$d %3$s status updated to %4$s'),$i, $count,
                            _N('selected ticket', 'selected tickets',
                                $count),
                            $status->getName());
                }

                Http::response(201, 'Successfully processed');
            }
        }

        $info['errors'] = $errors;
        return self::_setStatus($state, $info);
    }

    function _setStatus($state, $info=array()) {

        $errors = array();
        if ($info && isset($info['errors']))
            $errors = $info['errors'];

        if (!$info['error'] && isset($errors['err']))
            $info['error'] = $errors['err'];

        include(STAFFINC_DIR . 'templates/ticket-status.tmpl.php');
    }
}
?>
