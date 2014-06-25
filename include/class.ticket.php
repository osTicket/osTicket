<?php
/*********************************************************************
    class.ticket.php

    The most important class! Don't play with fire please.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
include_once(INCLUDE_DIR.'class.thread.php');
include_once(INCLUDE_DIR.'class.staff.php');
include_once(INCLUDE_DIR.'class.client.php');
include_once(INCLUDE_DIR.'class.team.php');
include_once(INCLUDE_DIR.'class.email.php');
include_once(INCLUDE_DIR.'class.dept.php');
include_once(INCLUDE_DIR.'class.topic.php');
include_once(INCLUDE_DIR.'class.lock.php');
include_once(INCLUDE_DIR.'class.file.php');
include_once(INCLUDE_DIR.'class.attachment.php');
include_once(INCLUDE_DIR.'class.banlist.php');
include_once(INCLUDE_DIR.'class.template.php');
include_once(INCLUDE_DIR.'class.variable.php');
include_once(INCLUDE_DIR.'class.priority.php');
include_once(INCLUDE_DIR.'class.sla.php');
include_once(INCLUDE_DIR.'class.canned.php');
require_once(INCLUDE_DIR.'class.dynamic_forms.php');
require_once(INCLUDE_DIR.'class.user.php');
require_once(INCLUDE_DIR.'class.collaborator.php');


class Ticket {

    var $id;
    var $number;

    var $ht;

    var $lastMsgId;

    var $dept;  //Dept obj
    var $sla;   // SLA obj
    var $staff; //Staff obj
    var $client; //Client Obj
    var $team;  //Team obj
    var $topic; //Topic obj
    var $tlock; //TicketLock obj

    var $thread; //Thread obj.

    function Ticket($id) {
        $this->id = 0;
        $this->load($id);
    }

    function load($id=0) {

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT  ticket.*, lock_id, dept_name '
            .' ,IF(sla.id IS NULL, NULL, '
                .'DATE_ADD(ticket.created, INTERVAL sla.grace_period HOUR)) as sla_duedate '
            .' ,count(distinct attach.attach_id) as attachments'
            .' FROM '.TICKET_TABLE.' ticket '
            .' LEFT JOIN '.DEPT_TABLE.' dept ON (ticket.dept_id=dept.dept_id) '
            .' LEFT JOIN '.SLA_TABLE.' sla ON (ticket.sla_id=sla.id AND sla.isactive=1) '
            .' LEFT JOIN '.TICKET_LOCK_TABLE.' tlock
                ON ( ticket.ticket_id=tlock.ticket_id AND tlock.expire>NOW()) '
            .' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach
                ON ( ticket.ticket_id=attach.ticket_id) '
            .' WHERE ticket.ticket_id='.db_input($id)
            .' GROUP BY ticket.ticket_id';

        //echo $sql;
        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;


        $this->ht = db_fetch_array($res);

        $this->id       = $this->ht['ticket_id'];
        $this->number   = $this->ht['number'];
        $this->_answers = array();

        $this->loadDynamicData();

        //Reset the sub classes (initiated ondemand)...good for reloads.
        $this->staff = null;
        $this->client = null;
        $this->team  = null;
        $this->dept = null;
        $this->sla = null;
        $this->tlock = null;
        $this->stats = null;
        $this->topic = null;
        $this->thread = null;
        $this->collaborators = null;

        return true;
    }

    function loadDynamicData() {
        if (!$this->_answers) {
            foreach (DynamicFormEntry::forTicket($this->getId(), true) as $form)
                foreach ($form->getAnswers() as $answer)
                    if ($tag = mb_strtolower($answer->getField()->get('name')))
                        $this->_answers[$tag] = $answer;
        }
    }

    function reload() {
        return $this->load();
    }

    function isOpen() {
        return (strcasecmp($this->getStatus(),'Open')==0);
    }

    function isReopened() {
        return ($this->getReopenDate());
    }

    function isClosed() {
        return (strcasecmp($this->getStatus(),'Closed')==0);
    }

    function isAssigned() {
        return ($this->isOpen() && ($this->getStaffId() || $this->getTeamId()));
    }

    function isOverdue() {
        return ($this->ht['isoverdue']);
    }

    function isAnswered() {
       return ($this->ht['isanswered']);
    }

    function isLocked() {
        return ($this->getLockId());
    }

    function checkStaffAccess($staff) {

        if(!is_object($staff) && !($staff=Staff::lookup($staff)))
            return false;

        // Staff has access to the department.
        if (!$staff->showAssignedOnly()
                && $staff->canAccessDept($this->getDeptId()))
            return true;

        // Only consider assignment if the ticket is open
        if (!$this->isOpen())
            return false;

        // Check ticket access based on direct or team assignment
        if ($staff->getId() == $this->getStaffId()
                || ($this->getTeamId()
                    && $staff->isTeamMember($this->getTeamId())
        ))
            return true;

        // No access bro!
        return false;
    }

    function checkUserAccess($user) {

        if (!$user || !($user instanceof EndUser))
            return false;

        //Ticket Owner
        if ($user->getId() == $this->getUserId())
            return true;

        //Collaborator?
        // 1) If the user was authorized via this ticket.
        if ($user->getTicketId() == $this->getId()
                && !strcasecmp($user->getRole(), 'collaborator'))
            return true;

        // 2) Query the database to check for expanded access...
        if (Collaborator::lookup(array(
                        'userId' => $user->getId(),
                        'ticketId' => $this->getId())))
            return true;

        return false;
    }

    //Getters
    function getId() {
        return  $this->id;
    }

    function getNumber() {
        return $this->number;
    }

    function getOwnerId() {
        return $this->ht['user_id'];
    }

    function getOwner() {

        if (!isset($this->owner)
                && ($u=User::lookup($this->getOwnerId())))
            $this->owner = new TicketOwner(new EndUser($u), $this);

        return $this->owner;
    }

    function getEmail(){
        if ($o = $this->getOwner())
            return $o->getEmail();

        return null;
    }

    function getReplyToEmail() {
        //TODO: Determine the email to use (once we enable multi-email support)
        return $this->getEmail();
    }

    function getAuthToken() {
        # XXX: Support variable email address (for CCs)
        return md5($this->getId() . strtolower($this->getEmail()) . SECRET_SALT);
    }

    function getName(){
        if ($o = $this->getOwner())
            return $o->getName();
        return null;
    }

    function getSubject() {
        return (string) $this->_answers['subject'];
    }

    /* Help topic title  - NOT object -> $topic */
    function getHelpTopic() {

        if(!$this->ht['helptopic'] && ($topic=$this->getTopic()))
            $this->ht['helptopic'] = $topic->getFullName();

        return $this->ht['helptopic'];
    }

    function getCreateDate() {
        return $this->ht['created'];
    }

    function getOpenDate() {
        return $this->getCreateDate();
    }

    function getReopenDate() {
        return $this->ht['reopened'];
    }

    function getUpdateDate() {
        return $this->ht['updated'];
    }

    function getDueDate() {
        return $this->ht['duedate'];
    }

    function getSLADueDate() {
        return $this->ht['sla_duedate'];
    }

    function getEstDueDate() {

        //Real due date
        if(($duedate=$this->getDueDate()))
            return $duedate;

        //return sla due date (If ANY)
        return $this->getSLADueDate();
    }

    function getCloseDate() {
        return $this->ht['closed'];
    }

    function getStatus() {
        return $this->ht['status'];
    }

    function getDeptId() {
       return $this->ht['dept_id'];
    }

    function getDeptName() {

        if(!$this->ht['dept_name'] && ($dept = $this->getDept()))
            $this->ht['dept_name'] = $dept->getName();

       return $this->ht['dept_name'];
    }

    function getPriorityId() {
        global $cfg;

        if (($a = $this->_answers['priority'])
                && ($b = $a->getValue()))
            return $b->getId();
        return $cfg->getDefaultPriorityId();
    }

    function getPriority() {
        if (($a = $this->_answers['priority']) && ($b = $a->getValue()))
            return $b->getDesc();
        return '';
    }

    function getPhoneNumber() {
        return (string)$this->getOwner()->getPhoneNumber();
    }

    function getSource() {
        return $this->ht['source'];
    }

    function getIP() {
        return $this->ht['ip_address'];
    }

    function getHashtable() {
        return $this->ht;
    }

    function getUpdateInfo() {
        global $cfg;

        $info=array('source'    =>  $this->getSource(),
                    'topicId'   =>  $this->getTopicId(),
                    'slaId' =>  $this->getSLAId(),
                    'user_id' => $this->getOwnerId(),
                    'duedate'   =>  $this->getDueDate()
                        ? Format::userdate($cfg->getDateFormat(),
                            Misc::db2gmtime($this->getDueDate()))
                        :'',
                    'time'  =>  $this->getDueDate()?(Format::userdate('G:i', Misc::db2gmtime($this->getDueDate()))):'',
                    );

        return $info;
    }

    function getLockId() {
        return $this->ht['lock_id'];
    }

    function getLock() {

        if(!$this->tlock && $this->getLockId())
            $this->tlock= TicketLock::lookup($this->getLockId(), $this->getId());

        return $this->tlock;
    }

    function acquireLock($staffId, $lockTime) {

        if(!$staffId or !$lockTime) //Lockig disabled?
            return null;

        //Check if the ticket is already locked.
        if(($lock=$this->getLock()) && !$lock->isExpired()) {
            if($lock->getStaffId()!=$staffId) //someone else locked the ticket.
                return null;

            //Lock already exits...renew it
            $lock->renew($lockTime); //New clock baby.

            return $lock;
        }
        //No lock on the ticket or it is expired
        $this->tlock = null; //clear crap
        $this->ht['lock_id'] = TicketLock::acquire($this->getId(), $staffId, $lockTime); //Create a new lock..
        //load and return the newly created lock if any!
        return $this->getLock();
    }

    function getDept() {
        global $cfg;

        if(!$this->dept)
            if(!($this->dept = Dept::lookup($this->getDeptId())))
                $this->dept = $cfg->getDefaultDept();

        return $this->dept;
    }

    function getUserId() {
        return $this->getOwnerId();
    }

    function getUser() {

        if(!isset($this->user) && $this->getOwner())
            $this->user = new EndUser($this->getOwner());

        return $this->user;
    }

    function getStaffId() {
        return $this->ht['staff_id'];
    }

    function getStaff() {

        if(!$this->staff && $this->getStaffId())
            $this->staff= Staff::lookup($this->getStaffId());

        return $this->staff;
    }

    function getTeamId() {
        return $this->ht['team_id'];
    }

    function getTeam() {

        if(!$this->team && $this->getTeamId())
            $this->team = Team::lookup($this->getTeamId());

        return $this->team;
    }

    function getAssignee() {

        if($staff=$this->getStaff())
            return $staff->getName();

        if($team=$this->getTeam())
            return $team->getName();

        return '';
    }

    function getAssignees() {

        $assignees=array();
        if($staff=$this->getStaff())
            $assignees[] = $staff->getName();

        if($team=$this->getTeam())
            $assignees[] = $team->getName();

        return $assignees;
    }

    function getAssigned($glue='/') {
        $assignees = $this->getAssignees();
        return $assignees?implode($glue, $assignees):'';
    }

    function getTopicId() {
        return $this->ht['topic_id'];
    }

    function getTopic() {

        if(!$this->topic && $this->getTopicId())
            $this->topic = Topic::lookup($this->getTopicId());

        return $this->topic;
    }


    function getSLAId() {
        return $this->ht['sla_id'];
    }

    function getSLA() {

        if(!$this->sla && $this->getSLAId())
            $this->sla = SLA::lookup($this->getSLAId());

        return $this->sla;
    }

    function getLastRespondent() {

        $sql ='SELECT  resp.staff_id '
             .' FROM '.TICKET_THREAD_TABLE.' resp '
             .' LEFT JOIN '.STAFF_TABLE. ' USING(staff_id) '
             .' WHERE  resp.ticket_id='.db_input($this->getId()).' AND resp.staff_id>0 '
             .'   AND  resp.thread_type="R"'
             .' ORDER BY resp.created DESC LIMIT 1';

        if(!($res=db_query($sql)) || !db_num_rows($res))
            return null;

        list($id)=db_fetch_row($res);

        return Staff::lookup($id);

    }

    function getLastMessageDate() {
        return $this->ht['lastmessage'];
    }

    function getLastMsgDate() {
        return $this->getLastMessageDate();
    }

    function getLastResponseDate() {
        return $this->ht['lastresponse'];
    }

    function getLastRespDate() {
        return $this->getLastResponseDate();
    }


    function getLastMsgId() {
        return $this->lastMsgId;
    }

    function getLastMessage() {
        if (!isset($this->last_message)) {
            if($this->getLastMsgId())
                $this->last_message =  Message::lookup(
                    $this->getLastMsgId(), $this->getId());

            if (!$this->last_message)
                $this->last_message = Message::lastByTicketId($this->getId());
        }
        return $this->last_message;
    }

    function getThread() {

        if(!$this->thread)
            $this->thread = Thread::lookup($this);

        return $this->thread;
    }

    function getThreadCount() {
        return $this->getNumMessages() + $this->getNumResponses();
    }

    function getNumMessages() {
        return $this->getThread()->getNumMessages();
    }

    function getNumResponses() {
        return $this->getThread()->getNumResponses();
    }

    function getNumNotes() {
        return $this->getThread()->getNumNotes();
    }

    function getMessages() {
        return $this->getThreadEntries('M');
    }

    function getResponses() {
        return $this->getThreadEntries('R');
    }

    function getNotes() {
        return $this->getThreadEntries('N');
    }

    function getClientThread() {
        return $this->getThreadEntries(array('M', 'R'));
    }

    function getThreadEntry($id) {
        return $this->getThread()->getEntry($id);
    }

    function getThreadEntries($type, $order='') {
        return $this->getThread()->getEntries($type, $order);
    }

    //Collaborators
    function getNumCollaborators() {
        return count($this->getCollaborators());
    }

    function getNumActiveCollaborators() {

        if (!isset($this->ht['active_collaborators']))
            $this->ht['active_collaborators'] = count($this->getActiveCollaborators());

        return $this->ht['active_collaborators'];
    }

    function getActiveCollaborators() {
        return $this->getCollaborators(array('isactive'=>1));
    }


    function getCollaborators($criteria=array()) {

        if ($criteria)
            return Collaborator::forTicket($this->getId(), $criteria);

        if (!isset($this->collaborators))
            $this->collaborators = Collaborator::forTicket($this->getId());

        return $this->collaborators;
    }

    //UserList of recipients  (owner + collaborators)
    function getRecipients() {

        if (!isset($this->recipients)) {
            $list = new UserList();
            $list->add($this->getOwner());
            if ($collabs = $this->getActiveCollaborators()) {
                foreach ($collabs as $c)
                    $list->add($c);
            }
            $this->recipients = $list;
        }

        return $this->recipients;
    }


    function addCollaborator($user, $vars, &$errors) {

        if (!$user || $user->getId()==$this->getOwnerId())
            return null;

        $vars = array_merge(array(
                'ticketId' => $this->getId(),
                'userId' => $user->getId()), $vars);
        if (!($c=Collaborator::add($vars, $errors)))
            return null;

        $this->collaborators = null;
        $this->recipients = null;

        return $c;
    }

    //XXX: Ugly for now
    function updateCollaborators($vars, &$errors) {
        global $thisstaff;

        if (!$thisstaff) return;

        //Deletes
        if($vars['del'] && ($ids=array_filter($vars['del']))) {
            $collabs = array();
            foreach ($ids as $k => $cid) {
                if (($c=Collaborator::lookup($cid))
                        && $c->getTicketId() == $this->getId()
                        && $c->remove())
                     $collabs[] = $c;
            }

            $this->logNote('Collaborators Removed',
                    implode("<br>", $collabs), $thisstaff, false);
        }

        //statuses
        $cids = null;
        if($vars['cid'] && ($cids=array_filter($vars['cid']))) {
            $sql='UPDATE '.TICKET_COLLABORATOR_TABLE
                .' SET updated=NOW(), isactive=1 '
                .' WHERE ticket_id='.db_input($this->getId())
                .' AND id IN('.implode(',', db_input($cids)).')';
            db_query($sql);
        }

        $sql='UPDATE '.TICKET_COLLABORATOR_TABLE
            .' SET updated=NOW(), isactive=0 '
            .' WHERE ticket_id='.db_input($this->getId());
        if($cids)
            $sql.=' AND id NOT IN('.implode(',', db_input($cids)).')';

        db_query($sql);

        unset($this->ht['active_collaborators']);
        $this->collaborators = null;

        return true;
    }

    /* -------------------- Setters --------------------- */
    function setLastMsgId($msgid) {
        return $this->lastMsgId=$msgid;
    }
    function setLastMessage($message) {
        $this->last_message = $message;
        $this->setLastMsgId($message->getId());
    }

    //DeptId can NOT be 0. No orphans please!
    function setDeptId($deptId) {

        //Make sure it's a valid department//
        if(!($dept=Dept::lookup($deptId)) || $dept->getId()==$this->getDeptId())
            return false;


        $sql='UPDATE '.TICKET_TABLE.' SET updated=NOW(), dept_id='.db_input($deptId)
            .' WHERE ticket_id='.db_input($this->getId());

        return (db_query($sql) && db_affected_rows());
    }

    //Set staff ID...assign/unassign/release (id can be 0)
    function setStaffId($staffId) {

        if(!is_numeric($staffId)) return false;

        $sql='UPDATE '.TICKET_TABLE.' SET updated=NOW(), staff_id='.db_input($staffId)
            .' WHERE ticket_id='.db_input($this->getId());

        if (!db_query($sql)  || !db_affected_rows())
            return false;

        $this->staff = null;
        $this->ht['staff_id'] = $staffId;

        return true;
    }

    function setSLAId($slaId) {
        if ($slaId == $this->getSLAId()) return true;
        return db_query(
             'UPDATE '.TICKET_TABLE.' SET sla_id='.db_input($slaId)
            .' WHERE ticket_id='.db_input($this->getId()))
            && db_affected_rows();
    }
    /**
     * Selects the appropriate service-level-agreement plan for this ticket.
     * When tickets are transfered between departments, the SLA of the new
     * department should be applied to the ticket. This would be useful,
     * for instance, if the ticket is transferred to a different department
     * which has a shorter grace period, the ticket should be considered
     * overdue in the shorter window now that it is owned by the new
     * department.
     *
     * $trump - if received, should trump any other possible SLA source.
     *          This is used in the case of email filters, where the SLA
     *          specified in the filter should trump any other SLA to be
     *          considered.
     */
    function selectSLAId($trump=null) {
        global $cfg;
        # XXX Should the SLA be overridden if it was originally set via an
        #     email filter? This method doesn't consider such a case
        if ($trump && is_numeric($trump)) {
            $slaId = $trump;
        } elseif ($this->getDept() && $this->getDept()->getSLAId()) {
            $slaId = $this->getDept()->getSLAId();
        } elseif ($this->getTopic() && $this->getTopic()->getSLAId()) {
            $slaId = $this->getTopic()->getSLAId();
        } else {
            $slaId = $cfg->getDefaultSLAId();
        }

        return ($slaId && $this->setSLAId($slaId)) ? $slaId : false;
    }

    //Set team ID...assign/unassign/release (id can be 0)
    function setTeamId($teamId) {

        if(!is_numeric($teamId)) return false;

        $sql='UPDATE '.TICKET_TABLE.' SET updated=NOW(), team_id='.db_input($teamId)
            .' WHERE ticket_id='.db_input($this->getId());

        return (db_query($sql)  && db_affected_rows());
    }

    //Status helper.
    function setStatus($status) {

        if(strcasecmp($this->getStatus(), $status)==0)
            return true; //No changes needed.

        switch(strtolower($status)) {
            case 'open':
                return $this->reopen();
                break;
            case 'closed':
                return $this->close();
                break;
        }

        return false;
    }

    function setState($state, $alerts=false) {

        switch(strtolower($state)) {
            case 'open':
                return $this->setStatus('open');
                break;
            case 'closed':
                return $this->setStatus('closed');
                break;
            case 'answered':
                return $this->setAnsweredState(1);
                break;
            case 'unanswered':
                return $this->setAnsweredState(0);
                break;
            case 'overdue':
                return $this->markOverdue();
                break;
            case 'notdue':
                return $this->clearOverdue();
                break;
            case 'unassined':
                return $this->unassign();
        }

        return false;
    }




    function setAnsweredState($isanswered) {

        $sql='UPDATE '.TICKET_TABLE.' SET isanswered='.db_input($isanswered)
            .' WHERE ticket_id='.db_input($this->getId());

        return (db_query($sql) && db_affected_rows());
    }

    //Close the ticket
    function close() {
        global $thisstaff;

        $sql='UPDATE '.TICKET_TABLE.' SET closed=NOW(),isoverdue=0, duedate=NULL, updated=NOW(), status='.db_input('closed');
        if($thisstaff) //Give the closing  staff credit.
            $sql.=', staff_id='.db_input($thisstaff->getId());

        $sql.=' WHERE ticket_id='.db_input($this->getId());

        if(!db_query($sql) || !db_affected_rows())
            return false;

        $this->reload();
        $this->logEvent('closed');
        $this->deleteDrafts();

        return true;
    }

    //set status to open on a closed ticket.
    function reopen($isanswered=0) {

        $sql='UPDATE '.TICKET_TABLE.' SET closed=NULL, updated=NOW(), reopened=NOW() '
            .' ,status='.db_input('open')
            .' ,isanswered='.db_input($isanswered)
            .' WHERE ticket_id='.db_input($this->getId());

        if (!db_query($sql) || !db_affected_rows())
            return false;

        $this->logEvent('reopened', 'closed');
        $this->ht['status'] = 'open';
        $this->ht['isanswerd'] = $isanswered;

        return true;
    }

    function onNewTicket($message, $autorespond=true, $alertstaff=true) {
        global $cfg;

        //Log stuff here...

        if(!$autorespond && !$alertstaff) return true; //No alerts to send.

        /* ------ SEND OUT NEW TICKET AUTORESP && ALERTS ----------*/

        $this->reload(); //get the new goodies.
        if(!$cfg
                || !($dept=$this->getDept())
                || !($tpl = $dept->getTemplate())
                || !($email=$dept->getAutoRespEmail())) {
                return false;  //bail out...missing stuff.
        }

        $options = array(
            'inreplyto'=>$message->getEmailMessageId(),
            'references'=>$message->getEmailReferences(),
            'thread'=>$message);

        //Send auto response - if enabled.
        if($autorespond
                && $cfg->autoRespONNewTicket()
                && $dept->autoRespONNewTicket()
                &&  ($msg=$tpl->getAutoRespMsgTemplate())) {

            $msg = $this->replaceVars($msg->asArray(),
                    array('message' => $message,
                          'recipient' => $this->getOwner(),
                          'signature' => ($dept && $dept->isPublic())?$dept->getSignature():'')
                    );

            $email->sendAutoReply($this->getEmail(), $msg['subj'], $msg['body'],
                null, $options);
        }

        //Send alert to out sleepy & idle staff.
        if ($alertstaff
                && $cfg->alertONNewTicket()
                && ($email=$cfg->getAlertEmail())
                && ($msg=$tpl->getNewTicketAlertMsgTemplate())) {

            $msg = $this->replaceVars($msg->asArray(), array('message' => $message));

            $recipients=$sentlist=array();
            //Exclude the auto responding email just incase it's from staff member.
            if ($message->isAutoReply())
                $sentlist[] = $this->getEmail();

            //Alert admin??
            if($cfg->alertAdminONNewTicket()) {
                $alert = $this->replaceVars($msg, array('recipient' => 'Admin'));
                $email->sendAlert($cfg->getAdminEmail(), $alert['subj'], $alert['body'], null, $options);
                $sentlist[]=$cfg->getAdminEmail();
            }

            //Only alerts dept members if the ticket is NOT assigned.
            if($cfg->alertDeptMembersONNewTicket() && !$this->isAssigned()) {
                if(($members=$dept->getMembersForAlerts()))
                    $recipients=array_merge($recipients, $members);
            }

            if($cfg->alertDeptManagerONNewTicket() && $dept && ($manager=$dept->getManager()))
                $recipients[]= $manager;

            // Account manager
            if ($cfg->alertAcctManagerONNewMessage()
                    && ($org = $this->getOwner()->getOrganization())
                    && ($acct_manager = $org->getAccountManager())) {
                if ($acct_manager instanceof Team)
                    $recipients = array_merge($recipients, $acct_manager->getMembers());
                else
                    $recipients[] = $acct_manager;
            }

            foreach( $recipients as $k=>$staff) {
                if(!is_object($staff) || !$staff->isAvailable() || in_array($staff->getEmail(), $sentlist)) continue;
                $alert = $this->replaceVars($msg, array('recipient' => $staff));
                $email->sendAlert($staff->getEmail(), $alert['subj'], $alert['body'], null, $options);
                $sentlist[] = $staff->getEmail();
            }
        }

        return true;
    }

    function onOpenLimit($sendNotice=true) {
        global $ost, $cfg;

        //Log the limit notice as a warning for admin.
        $msg=sprintf('Max open tickets (%d) reached  for %s ', $cfg->getMaxOpenTickets(), $this->getEmail());
        $ost->logWarning('Max. Open Tickets Limit ('.$this->getEmail().')', $msg);

        if(!$sendNotice || !$cfg->sendOverLimitNotice())
            return true;

        //Send notice to user.
        if(($dept = $this->getDept())
            && ($tpl=$dept->getTemplate())
            && ($msg=$tpl->getOverlimitMsgTemplate())
            && ($email=$dept->getAutoRespEmail())) {

            $msg = $this->replaceVars($msg->asArray(),
                        array('signature' => ($dept && $dept->isPublic())?$dept->getSignature():''));

            $email->sendAutoReply($this->getEmail(), $msg['subj'], $msg['body']);
        }

        $user = $this->getOwner();

        //Alert admin...this might be spammy (no option to disable)...but it is helpful..I think.
        $alert='Max. open tickets reached for '.$this->getEmail()."\n"
              .'Open ticket: '.$user->getNumOpenTickets()."\n"
              .'Max Allowed: '.$cfg->getMaxOpenTickets()."\n\nNotice sent to the user.";

        $ost->alertAdmin('Overlimit Notice', $alert);

        return true;
    }

    function onResponse() {
        db_query('UPDATE '.TICKET_TABLE.' SET isanswered=1, lastresponse=NOW(), updated=NOW() WHERE ticket_id='.db_input($this->getId()));
        $this->reload();
    }

    /*
     * Notify collaborators on response or new message
     *
     */

    function  notifyCollaborators($entry, $vars = array()) {
        global $cfg;

        if (!$entry instanceof ThreadEntry
                || !($recipients=$this->getRecipients())
                || !($dept=$this->getDept())
                || !($tpl=$dept->getTemplate())
                || !($msg=$tpl->getActivityNoticeMsgTemplate())
                || !($email=$dept->getEmail()))
            return;

        //Who posted the entry?
        $uid = 0;
        if ($entry instanceof Message) {
            $poster = $entry->getUser();
            // Skip the person who sent in the message
            $uid = $entry->getUserId();
        } else {
            $poster = $entry->getStaff();
            // Skip the ticket owner
            $uid = $this->getUserId();
        }

        $vars = array_merge($vars, array(
                    'message' => (string) $entry,
                    'poster' => $poster? $poster : 'A collaborator',
                    )
                );

        $msg = $this->replaceVars($msg->asArray(), $vars);

        $attachments = $cfg->emailAttachments()?$entry->getAttachments():array();
        $options = array('inreplyto' => $entry->getEmailMessageId(),
                         'thread' => $entry);
        foreach ($recipients as $recipient) {
            if ($uid == $recipient->getId()) continue;
            $options['references'] =  $entry->getEmailReferencesForUser($recipient);
            $notice = $this->replaceVars($msg, array('recipient' => $recipient));
            $email->send($recipient->getEmail(), $notice['subj'], $notice['body'], $attachments,
                $options);
        }

        return;
    }

    function onMessage($message, $autorespond=true) {
        global $cfg;

        db_query('UPDATE '.TICKET_TABLE.' SET isanswered=0,lastmessage=NOW() WHERE ticket_id='.db_input($this->getId()));

        // Auto-assign to closing staff or last respondent
        // If the ticket is closed and auto-claim is not enabled then put the
        // ticket back to unassigned pool.
        if ($this->isClosed() && !$cfg->autoClaimTickets()) {
            $this->setStaffId(0);
        } elseif(!($staff=$this->getStaff()) || !$staff->isAvailable()) {
            // Ticket has no assigned staff -  if auto-claim is enabled then
            // try assigning it to the last respondent (if available)
            // otherwise leave the ticket unassigned.
            if ($cfg->autoClaimTickets() //Auto claim is enabled.
                    && ($lastrep=$this->getLastRespondent())
                    && $lastrep->isAvailable()) {
                $this->setStaffId($lastrep->getId()); //direct assignment;
            } else {
                $this->setStaffId(0); //unassign - last respondent is not available.
            }
        }

        // Reopen  if closed.
        if($this->isClosed()) $this->reopen();

       /**********   double check auto-response  ************/
        if (!($user = $message->getUser()))
            $autorespond=false;
        elseif ($autorespond && (Email::getIdByEmail($user->getEmail())))
            $autorespond=false;
        elseif ($autorespond && ($dept=$this->getDept()))
            $autorespond=$dept->autoRespONNewMessage();


        if(!$autorespond
                || !$cfg->autoRespONNewMessage()
                || !$message) return;  //no autoresp or alerts.

        $this->reload();
        $dept = $this->getDept();
        $email = $dept->getAutoRespEmail();

        //If enabled...send confirmation to user. ( New Message AutoResponse)
        if($email
                && ($tpl=$dept->getTemplate())
                && ($msg=$tpl->getNewMessageAutorepMsgTemplate())) {

            $msg = $this->replaceVars($msg->asArray(),
                            array(
                                'recipient' => $user,
                                'signature' => ($dept && $dept->isPublic())?$dept->getSignature():''));

            $options = array(
                'inreplyto'=>$message->getEmailMessageId(),
                'references' => $message->getEmailReferencesForUser($user),
                'thread'=>$message);
            $email->sendAutoReply($user->getEmail(), $msg['subj'], $msg['body'],
                null, $options);
        }
    }

    function onAssign($assignee, $comments, $alert=true) {
        global $cfg, $thisstaff;

        if($this->isClosed()) $this->reopen(); //Assigned tickets must be open - otherwise why assign?

        //Assignee must be an object of type Staff or Team
        if(!$assignee || !is_object($assignee)) return false;

        $this->reload();

        $comments = $comments?$comments:'Ticket assignment';
        $assigner = $thisstaff?$thisstaff:'SYSTEM (Auto Assignment)';

        //Log an internal note - no alerts on the internal note.
        $note = $this->logNote('Ticket Assigned to '.$assignee->getName(),
            $comments, $assigner, false);

        //See if we need to send alerts
        if(!$alert || !$cfg->alertONAssignment()) return true; //No alerts!

        $dept = $this->getDept();
        if(!$dept
                || !($tpl = $dept->getTemplate())
                || !($email = $cfg->getAlertEmail()))
            return true;

        //recipients
        $recipients=array();
        if ($assignee instanceof Staff) {
            if ($cfg->alertStaffONAssignment())
                $recipients[] = $assignee;
        } elseif (($assignee instanceof Team) && $assignee->alertsEnabled()) {
            if ($cfg->alertTeamMembersONAssignment() && ($members=$assignee->getMembers()))
                $recipients = array_merge($recipients, $members);
            elseif ($cfg->alertTeamLeadONAssignment() && ($lead=$assignee->getTeamLead()))
                $recipients[] = $lead;
        }

        //Get the message template
        if ($recipients
                && ($msg=$tpl->getAssignedAlertMsgTemplate())) {

            $msg = $this->replaceVars($msg->asArray(),
                        array('comments' => $comments,
                              'assignee' => $assignee,
                              'assigner' => $assigner
                              ));

            //Send the alerts.
            $sentlist=array();
            $options = array(
                'inreplyto'=>$note->getEmailMessageId(),
                'references'=>$note->getEmailReferences(),
                'thread'=>$note);
            foreach( $recipients as $k=>$staff) {
                if(!is_object($staff) || !$staff->isAvailable() || in_array($staff->getEmail(), $sentlist)) continue;
                $alert = $this->replaceVars($msg, array('recipient' => $staff));
                $email->sendAlert($staff->getEmail(), $alert['subj'], $alert['body'], null, $options);
                $sentlist[] = $staff->getEmail();
            }
        }

        return true;
    }

   function onOverdue($whine=true, $comments="") {
        global $cfg;

        if($whine && ($sla=$this->getSLA()) && !$sla->alertOnOverdue())
            $whine = false;

        //check if we need to send alerts.
        if(!$whine
                || !$cfg->alertONOverdueTicket()
                || !($dept = $this->getDept()))
            return true;

        //Get the message template
        if(($tpl = $dept->getTemplate())
                && ($msg=$tpl->getOverdueAlertMsgTemplate())
                && ($email=$cfg->getAlertEmail())) {

            $msg = $this->replaceVars($msg->asArray(),
                array('comments' => $comments));

            //recipients
            $recipients=array();
            //Assigned staff or team... if any
            if($this->isAssigned() && $cfg->alertAssignedONOverdueTicket()) {
                if($this->getStaffId())
                    $recipients[]=$this->getStaff();
                elseif($this->getTeamId() && ($team=$this->getTeam()) && ($members=$team->getMembers()))
                    $recipients=array_merge($recipients, $members);
            } elseif($cfg->alertDeptMembersONOverdueTicket() && !$this->isAssigned()) {
                //Only alerts dept members if the ticket is NOT assigned.
                if ($members = $dept->getMembersForAlerts())
                    $recipients = array_merge($recipients, $members);
            }
            //Always alert dept manager??
            if($cfg->alertDeptManagerONOverdueTicket() && $dept && ($manager=$dept->getManager()))
                $recipients[]= $manager;

            $sentlist=array();
            foreach( $recipients as $k=>$staff) {
                if(!is_object($staff) || !$staff->isAvailable() || in_array($staff->getEmail(), $sentlist)) continue;
                $alert = $this->replaceVars($msg, array('recipient' => $staff));
                $email->sendAlert($staff->getEmail(), $alert['subj'], $alert['body'], null);
                $sentlist[] = $staff->getEmail();
            }

        }

        return true;

    }

    //ticket obj as variable = ticket number.
    function asVar() {
       return $this->getNumber();
    }

    function getVar($tag) {
        global $cfg;

        if($tag && is_callable(array($this, 'get'.ucfirst($tag))))
            return call_user_func(array($this, 'get'.ucfirst($tag)));

        switch(mb_strtolower($tag)) {
            case 'phone':
            case 'phone_number':
                return $this->getPhoneNumber();
                break;
            case 'auth_token':
                return $this->getAuthToken();
                break;
            case 'client_link':
                return sprintf('%s/view.php?t=%s',
                        $cfg->getBaseUrl(), $this->getNumber());
                break;
            case 'staff_link':
                return sprintf('%s/scp/tickets.php?id=%d', $cfg->getBaseUrl(), $this->getId());
                break;
            case 'create_date':
                return Format::date(
                        $cfg->getDateTimeFormat(),
                        Misc::db2gmtime($this->getCreateDate()),
                        $cfg->getTZOffset(),
                        $cfg->observeDaylightSaving());
                break;
             case 'due_date':
                $duedate ='';
                if($this->getEstDueDate())
                    $duedate = Format::date(
                            $cfg->getDateTimeFormat(),
                            Misc::db2gmtime($this->getEstDueDate()),
                            $cfg->getTZOffset(),
                            $cfg->observeDaylightSaving());

                return $duedate;
                break;
            case 'close_date';
                $closedate ='';
                if($this->isClosed())
                    $duedate = Format::date(
                            $cfg->getDateTimeFormat(),
                            Misc::db2gmtime($this->getCloseDate()),
                            $cfg->getTZOffset(),
                            $cfg->observeDaylightSaving());

                return $closedate;
                break;
            case 'user':
                return $this->getOwner();
                break;
            default:
                if (isset($this->_answers[$tag]))
                    // The answer object is retrieved here which will
                    // automatically invoke the toString() method when the
                    // answer is coerced into text
                    return $this->_answers[$tag];
        }

        return false;
    }

    //Replace base variables.
    function replaceVars($input, $vars = array()) {
        global $ost;

        $vars = array_merge($vars, array('ticket' => $this));

        return $ost->replaceTemplateVariables($input, $vars);
    }

    function markUnAnswered() {
        return (!$this->isAnswered() || $this->setAnsweredState(0));
    }

    function markAnswered() {
        return ($this->isAnswered() || $this->setAnsweredState(1));
    }

    function markOverdue($whine=true) {

        global $cfg;

        if($this->isOverdue())
            return true;

        $sql='UPDATE '.TICKET_TABLE.' SET isoverdue=1, updated=NOW() '
            .' WHERE ticket_id='.db_input($this->getId());

        if(!db_query($sql) || !db_affected_rows())
            return false;

        $this->logEvent('overdue');
        $this->onOverdue($whine);

        return true;
    }

    function clearOverdue() {

        if(!$this->isOverdue())
            return true;

        //NOTE: Previously logged overdue event is NOT annuled.

        $sql='UPDATE '.TICKET_TABLE.' SET isoverdue=0, updated=NOW() ';

        //clear due date if it's in the past
        if($this->getDueDate() && Misc::db2gmtime($this->getDueDate()) <= Misc::gmtime())
            $sql.=', duedate=NULL';

        //Clear SLA if est. due date is in the past
        if($this->getSLADueDate() && Misc::db2gmtime($this->getSLADueDate()) <= Misc::gmtime())
            $sql.=', sla_id=0 ';

        $sql.=' WHERE ticket_id='.db_input($this->getId());

        return (db_query($sql) && db_affected_rows());
    }

    //Dept Tranfer...with alert.. done by staff
    function transfer($deptId, $comments, $alert = true) {

        global $cfg, $thisstaff;

        if(!$thisstaff || !$thisstaff->canTransferTickets())
            return false;

        $currentDept = $this->getDeptName(); //Current department

        if(!$deptId || !$this->setDeptId($deptId))
            return false;

        // Reopen ticket if closed
        if($this->isClosed()) $this->reopen();

        $this->reload();

        // Set SLA of the new department
        if(!$this->getSLAId() || $this->getSLA()->isTransient())
            $this->selectSLAId();

        /*** log the transfer comments as internal note - with alerts disabled - ***/
        $title='Ticket transfered from '.$currentDept.' to '.$this->getDeptName();
        $comments=$comments?$comments:$title;
        $note = $this->logNote($title, $comments, $thisstaff, false);

        $this->logEvent('transferred');

        //Send out alerts if enabled AND requested
        if(!$alert || !$cfg->alertONTransfer() || !($dept=$this->getDept()))
            return true; //no alerts!!

         if(($email=$cfg->getAlertEmail())
                     && ($tpl = $dept->getTemplate())
                     && ($msg=$tpl->getTransferAlertMsgTemplate())) {

            $msg = $this->replaceVars($msg->asArray(),
                array('comments' => $comments, 'staff' => $thisstaff));
            //recipients
            $recipients=array();
            //Assigned staff or team... if any
            if($this->isAssigned() && $cfg->alertAssignedONTransfer()) {
                if($this->getStaffId())
                    $recipients[]=$this->getStaff();
                elseif($this->getTeamId() && ($team=$this->getTeam()) && ($members=$team->getMembers()))
                    $recipients = array_merge($recipients, $members);
            } elseif($cfg->alertDeptMembersONTransfer() && !$this->isAssigned()) {
                //Only alerts dept members if the ticket is NOT assigned.
                if(($members=$dept->getMembersForAlerts()))
                    $recipients = array_merge($recipients, $members);
            }

            //Always alert dept manager??
            if($cfg->alertDeptManagerONTransfer() && $dept && ($manager=$dept->getManager()))
                $recipients[]= $manager;

            $sentlist=array();
            $options = array(
                'inreplyto'=>$note->getEmailMessageId(),
                'references'=>$note->getEmailReferences(),
                'thread'=>$note);
            foreach( $recipients as $k=>$staff) {
                if(!is_object($staff) || !$staff->isAvailable() || in_array($staff->getEmail(), $sentlist)) continue;
                $alert = $this->replaceVars($msg, array('recipient' => $staff));
                $email->sendAlert($staff->getEmail(), $alert['subj'], $alert['body'], null, $options);
                $sentlist[] = $staff->getEmail();
            }
         }

         return true;
    }

    function assignToStaff($staff, $note, $alert=true) {

        if(!is_object($staff) && !($staff=Staff::lookup($staff)))
            return false;

        if (!$staff->isAvailable() || !$this->setStaffId($staff->getId()))
            return false;

        $this->onAssign($staff, $note, $alert);
        $this->logEvent('assigned');

        return true;
    }

    function assignToTeam($team, $note, $alert=true) {

        if(!is_object($team) && !($team=Team::lookup($team)))
            return false;

        if (!$team->isActive() || !$this->setTeamId($team->getId()))
            return false;

        //Clear - staff if it's a closed ticket
        //  staff_id is overloaded -> assigned to & closed by.
        if($this->isClosed())
            $this->setStaffId(0);

        $this->onAssign($team, $note, $alert);
        $this->logEvent('assigned');

        return true;
    }

    //Assign ticket to staff or team - overloaded ID.
    function assign($assignId, $note, $alert=true) {
        global $thisstaff;

        $rv=0;
        $id=preg_replace("/[^0-9]/", "", $assignId);
        if($assignId[0]=='t') {
            $rv=$this->assignToTeam($id, $note, $alert);
        } elseif($assignId[0]=='s' || is_numeric($assignId)) {
            $alert=($alert && $thisstaff && $thisstaff->getId()==$id)?false:$alert; //No alerts on self assigned tickets!!!
            //We don't care if a team is already assigned to the ticket - staff assignment takes precedence
            $rv=$this->assignToStaff($id, $note, $alert);
        }

        return $rv;
    }

    //unassign primary assignee
    function unassign() {

        if(!$this->isAssigned()) //We can't release what is not assigned buddy!
            return true;

        //We can only unassigned OPEN tickets.
        if($this->isClosed())
            return false;

        //Unassign staff (if any)
        if($this->getStaffId() && !$this->setStaffId(0))
            return false;

        //unassign team (if any)
        if($this->getTeamId() && !$this->setTeamId(0))
            return false;

        $this->reload();

        return true;
    }

    function release() {
        return $this->unassign();
    }

    //Change ownership
    function changeOwner($user) {
        global $thisstaff;

        if (!$user
                || ($user->getId() == $this->getOwnerId())
                || !$thisstaff->canEditTickets())
            return false;

        $sql ='UPDATE '.TICKET_TABLE.' SET updated = NOW() '
            .', user_id = '.db_input($user->getId())
            .' WHERE ticket_id = '.db_input($this->getId());

        if (!db_query($sql) || !db_affected_rows())
            return false;

        $this->ht['user_id'] = $user->getId();
        $this->user = null;
        $this->collaborators = null;
        $this->recipients = null;

        //Log an internal note
        $note = sprintf('%s changed ticket ownership to %s',
                $thisstaff->getName(), $user->getName());

        //Remove the new owner from list of collaborators
        $c = Collaborator::lookup(array('userId' => $user->getId(),
                    'ticketId' => $this->getId()));
        if ($c && $c->remove())
            $note.= ' (removed as collaborator)';

        $this->logNote('Ticket ownership changed', $note);

        return true;
    }

    //Insert message from client
    function postMessage($vars, $origin='', $alerts=true) {
        global $cfg;

        $vars['origin'] = $origin;
        if(isset($vars['ip']))
            $vars['ip_address'] = $vars['ip'];
        elseif(!$vars['ip_address'] && $_SERVER['REMOTE_ADDR'])
            $vars['ip_address'] = $_SERVER['REMOTE_ADDR'];

        $errors = array();
        if(!($message = $this->getThread()->addMessage($vars, $errors)))
            return null;

        $this->setLastMessage($message);

        //Add email recipients as collaborators...
        if ($vars['recipients']
                && (strtolower($origin) != 'email' || ($cfg && $cfg->addCollabsViaEmail()))
                //Only add if we have a matched local address
                && $vars['to-email-id']) {
            //New collaborators added by other collaborators are disable --
            // requires staff approval.
            $info = array(
                    'isactive' => ($message->getUserId() == $this->getUserId())? 1: 0);
            $collabs = array();
            foreach ($vars['recipients'] as $recipient) {
                // Skip virtual delivered-to addresses
                if (strcasecmp($recipient['source'], 'delivered-to') === 0)
                    continue;

                if (($user=User::fromVars($recipient)))
                    if ($c=$this->addCollaborator($user, $info, $errors))
                        $collabs[] = sprintf('%s%s',
                                (string) $c,
                                $recipient['source'] ? " via {$recipient['source']}" : ''
                                );
            }
            //TODO: Can collaborators add others?
            if ($collabs) {
                //TODO: Change EndUser to name of  user.
                $this->logNote('Collaborators added by enduser',
                        implode("<br>", $collabs), 'EndUser', false);
            }
        }

        if(!$alerts) return $message; //Our work is done...

        // Do not auto-respond to bounces and other auto-replies
        $autorespond = isset($vars['flags'])
            ? !$vars['flags']['bounce'] && !$vars['flags']['auto-reply']
            : true;
        if ($autorespond && $message->isAutoReply())
            $autorespond = false;

        $this->onMessage($message, $autorespond); //must be called b4 sending alerts to staff.

        if ($autorespond && $cfg && $cfg->notifyCollabsONNewMessage())
            $this->notifyCollaborators($message, array('signature' => ''));

        $dept = $this->getDept();


        $variables = array(
                'message' => $message,
                'poster' => ($vars['poster'] ? $vars['poster'] : $this->getName())
                );
        $options = array(
                'inreplyto' => $message->getEmailMessageId(),
                'references' => $message->getEmailReferences(),
                'thread'=>$message);
        //If enabled...send alert to staff (New Message Alert)
        if($cfg->alertONNewMessage()
                && ($email = $cfg->getAlertEmail())
                && ($tpl = $dept->getTemplate())
                && ($msg = $tpl->getNewMessageAlertMsgTemplate())) {

            $msg = $this->replaceVars($msg->asArray(), $variables);

            //Build list of recipients and fire the alerts.
            $recipients=array();
            //Last respondent.
            if($cfg->alertLastRespondentONNewMessage() || $cfg->alertAssignedONNewMessage())
                $recipients[]=$this->getLastRespondent();

            //Assigned staff if any...could be the last respondent

            if($this->isAssigned() && ($staff=$this->getStaff()))
                $recipients[]=$staff;

            //Dept manager
            if($cfg->alertDeptManagerONNewMessage() && $dept && ($manager=$dept->getManager()))
                $recipients[]=$manager;

            // Account manager
            if ($cfg->alertAcctManagerONNewMessage()
                    && ($org = $this->getOwner()->getOrganization())
                    && ($acct_manager = $org->getAccountManager())) {
                if ($acct_manager instanceof Team)
                    $recipients = array_merge($recipients, $acct_manager->getMembers());
                else
                    $recipients[] = $acct_manager;
            }

            $sentlist=array(); //I know it sucks...but..it works.
            foreach( $recipients as $k=>$staff) {
                if(!$staff || !$staff->getEmail() || !$staff->isAvailable() || in_array($staff->getEmail(), $sentlist)) continue;
                $alert = $this->replaceVars($msg, array('recipient' => $staff));
                $email->sendAlert($staff->getEmail(), $alert['subj'], $alert['body'], null, $options);
                $sentlist[] = $staff->getEmail();
            }
        }

        return $message;
    }

    function postCannedReply($canned, $msgId, $alert=true) {
        global $ost, $cfg;

        if((!is_object($canned) && !($canned=Canned::lookup($canned))) || !$canned->isEnabled())
            return false;

        $files = array();
        foreach ($canned->attachments->getAll() as $file)
            $files[] = $file['id'];

        if ($cfg->isHtmlThreadEnabled())
            $response = new HtmlThreadBody(
                    $this->replaceVars($canned->getHtml()));
        else
            $response = new TextThreadBody(
                    $this->replaceVars($canned->getPlainText()));

        $info = array('msgId' => $msgId,
                      'poster' => 'SYSTEM (Canned Reply)',
                      'response' => $response,
                      'cannedattachments' => $files);

        $errors = array();
        if(!($response=$this->postReply($info, $errors, false)))
            return null;

        $this->markUnAnswered();

        if(!$alert) return $response;

        $dept = $this->getDept();

        if(($email=$dept->getEmail())
                && ($tpl = $dept->getTemplate())
                && ($msg=$tpl->getAutoReplyMsgTemplate())) {

            if($dept && $dept->isPublic())
                $signature=$dept->getSignature();
            else
                $signature='';

            $msg = $this->replaceVars($msg->asArray(),
                    array(
                        'response' => $response,
                        'signature' => $signature,
                        'recipient' => $this->getOwner(),
                        ));

            $attachments =($cfg->emailAttachments() && $files)?$response->getAttachments():array();
            $options = array(
                'inreplyto'=>$response->getEmailMessageId(),
                'references'=>$response->getEmailReferences(),
                'thread'=>$response);
            $email->sendAutoReply($this->getEmail(), $msg['subj'], $msg['body'], $attachments,
                $options);
        }

        return $response;
    }

    /* public */
    function postReply($vars, &$errors, $alert = true) {
        global $thisstaff, $cfg;


        if(!$vars['poster'] && $thisstaff)
            $vars['poster'] = $thisstaff;

        if(!$vars['staffId'] && $thisstaff)
            $vars['staffId'] = $thisstaff->getId();

        if(!($response = $this->getThread()->addResponse($vars, $errors)))
            return null;

        //Set status - if checked.
        if(isset($vars['reply_ticket_status']) && $vars['reply_ticket_status'])
            $this->setStatus($vars['reply_ticket_status']);

        if($thisstaff && $this->isOpen() && !$this->getStaffId()
                && $cfg->autoClaimTickets())
            $this->setStaffId($thisstaff->getId()); //direct assignment;

        $this->onResponse(); //do house cleaning..

        /* email the user??  - if disabled - the bail out */
        if(!$alert) return $response;

        $dept = $this->getDept();

        if($thisstaff && $vars['signature']=='mine')
            $signature=$thisstaff->getSignature();
        elseif($vars['signature']=='dept' && $dept && $dept->isPublic())
            $signature=$dept->getSignature();
        else
            $signature='';

        $variables = array(
                'response' => $response,
                'signature' => $signature,
                'staff' => $thisstaff,
                'poster' => $thisstaff);
        $options = array(
                'inreplyto' => $response->getEmailMessageId(),
                'references' => $response->getEmailReferences(),
                'thread'=>$response);

        if(($email=$dept->getEmail())
                && ($tpl = $dept->getTemplate())
                && ($msg=$tpl->getReplyMsgTemplate())) {

            $msg = $this->replaceVars($msg->asArray(),
                    $variables + array('recipient' => $this->getOwner()));

            $attachments = $cfg->emailAttachments()?$response->getAttachments():array();
            $email->send($this->getEmail(), $msg['subj'], $msg['body'], $attachments,
                $options);
        }

        if($vars['emailcollab'])
            $this->notifyCollaborators($response,
                    array('signature' => $signature));

        return $response;
    }

    //Activity log - saved as internal notes WHEN enabled!!
    function logActivity($title, $note) {
        return $this->logNote($title, $note, 'SYSTEM', false);
    }

    // History log -- used for statistics generation (pretty reports)
    function logEvent($state, $annul=null, $staff=null) {
        global $thisstaff;

        if ($staff === null) {
            if ($thisstaff) $staff=$thisstaff->getUserName();
            else $staff='SYSTEM';               # XXX: Security Violation ?
        }
        # Annul previous entries if requested (for instance, reopening a
        # ticket will annul an 'closed' entry). This will be useful to
        # easily prevent repeated statistics.
        if ($annul) {
            db_query('UPDATE '.TICKET_EVENT_TABLE.' SET annulled=1'
                .' WHERE ticket_id='.db_input($this->getId())
                  .' AND state='.db_input($annul));
        }

        return db_query('INSERT INTO '.TICKET_EVENT_TABLE
            .' SET ticket_id='.db_input($this->getId())
            .', staff_id='.db_input($this->getStaffId())
            .', team_id='.db_input($this->getTeamId())
            .', dept_id='.db_input($this->getDeptId())
            .', topic_id='.db_input($this->getTopicId())
            .', timestamp=NOW(), state='.db_input($state)
            .', staff='.db_input($staff))
            && db_affected_rows() == 1;
    }

    //Insert Internal Notes
    function logNote($title, $note, $poster='SYSTEM', $alert=true) {

        $errors = array();
        //Unless specified otherwise, assume HTML
        if ($note && is_string($note))
            $note = new HtmlThreadBody($note);

        return $this->postNote(
                array(
                    'title' => $title,
                    'note' => $note,
                ),
                $errors,
                $poster,
                $alert
        );
    }

    function postNote($vars, &$errors, $poster, $alert=true) {
        global $cfg, $thisstaff;

        //Who is posting the note - staff or system?
        $vars['staffId'] = 0;
        $vars['poster'] = 'SYSTEM';
        if($poster && is_object($poster)) {
            $vars['staffId'] = $poster->getId();
            $vars['poster'] = $poster->getName();
        }elseif($poster) { //string
            $vars['poster'] = $poster;
        }

        if(!($note=$this->getThread()->addNote($vars, $errors)))
            return null;

        $alert = $alert && (
            isset($vars['flags'])
            // No alerts for bounce and auto-reply emails
            ? !$vars['flags']['bounce'] && !$vars['flags']['auto-reply']
            : true
        );

        // Get assigned staff just in case the ticket is closed.
        $assignee = $this->getStaff();

        //Set state: Error on state change not critical!
        if(isset($vars['state']) && $vars['state']) {
            if($this->setState($vars['state']))
                $this->reload();
        }

        // If alerts are not enabled then return a success.
        if(!$alert || !$cfg->alertONNewNote() || !($dept=$this->getDept()))
            return $note;

        if(($email=$cfg->getAlertEmail())
                && ($tpl = $dept->getTemplate())
                && ($msg=$tpl->getNoteAlertMsgTemplate())) {

            $msg = $this->replaceVars($msg->asArray(),
                array('note' => $note));

            // Alert recipients
            $recipients=array();

            //Last respondent.
            if ($cfg->alertLastRespondentONNewNote())
                $recipients[] = $this->getLastRespondent();

            // Assigned staff / team
            if ($cfg->alertAssignedONNewNote()) {

                if ($assignee && $assignee instanceof Staff)
                    $recipients[] = $assignee;

                if ($team = $this->getTeam())
                    $recipients = array_merge($recipients, $team->getMembers());
            }

            // Dept manager
            if ($cfg->alertDeptManagerONNewNote() && $dept && $dept->getManagerId())
                $recipients[] = $dept->getManager();

            $options = array(
                'inreplyto'=>$note->getEmailMessageId(),
                'references'=>$note->getEmailReferences(),
                'thread'=>$note);

            $isClosed = $this->isClosed();
            $sentlist=array();
            foreach( $recipients as $k=>$staff) {
                if(!is_object($staff)
                        // Don't bother vacationing staff.
                        || !$staff->isAvailable()
                        // No duplicates.
                        || isset($sentlist[$staff->getEmail()])
                        // No need to alert the poster!
                        || $note->getStaffId() == $staff->getId()
                        // Make sure staff has access to ticket
                        || ($isClosed && !$this->checkStaffAccess($staff))
                        )
                    continue;
                $alert = $this->replaceVars($msg, array('recipient' => $staff));
                $email->sendAlert($staff->getEmail(), $alert['subj'], $alert['body'], null, $options);
                $sentlist[$staff->getEmail()] = 1;
            }
        }

        return $note;
    }

    //Print ticket... export the ticket thread as PDF.
    function pdfExport($psize='Letter', $notes=false) {
        global $thisstaff;

        require_once(INCLUDE_DIR.'class.pdf.php');
        if (!is_string($psize)) {
            if ($_SESSION['PAPER_SIZE'])
                $psize = $_SESSION['PAPER_SIZE'];
            elseif (!$thisstaff || !($psize = $thisstaff->getDefaultPaperSize()))
                $psize = 'Letter';
        }

        $pdf = new Ticket2PDF($this, $psize, $notes);
        $name='Ticket-'.$this->getNumber().'.pdf';
        $pdf->Output($name, 'I');
        //Remember what the user selected - for autoselect on the next print.
        $_SESSION['PAPER_SIZE'] = $psize;
        exit;
    }

    function delete() {

        //delete just orphaned ticket thread & associated attachments.
        // Fetch thread prior to removing ticket entry
        $t = $this->getThread();

        $sql = 'DELETE FROM '.TICKET_TABLE.' WHERE ticket_id='.$this->getId().' LIMIT 1';
        if(!db_query($sql) || !db_affected_rows())
            return false;

        $t->delete();

        foreach (DynamicFormEntry::forTicket($this->getId()) as $form)
            $form->delete();

        $this->deleteDrafts();

        return true;
    }

    function deleteDrafts() {
        Draft::deleteForNamespace('ticket.%.' . $this->getId());
    }

    function update($vars, &$errors) {

        global $cfg, $thisstaff;

        if(!$cfg || !$thisstaff || !$thisstaff->canEditTickets())
            return false;

        $fields=array();
        $fields['topicId']  = array('type'=>'int',      'required'=>1, 'error'=>'Help topic required');
        $fields['slaId']    = array('type'=>'int',      'required'=>0, 'error'=>'Select SLA');
        $fields['duedate']  = array('type'=>'date',     'required'=>0, 'error'=>'Invalid date - must be MM/DD/YY');

        $fields['note']     = array('type'=>'text',     'required'=>1, 'error'=>'Reason for the update required');
        $fields['user_id']  = array('type'=>'int',      'required'=>0, 'error'=>'Invalid user-id');

        if(!Validator::process($fields, $vars, $errors) && !$errors['err'])
            $errors['err'] = 'Missing or invalid data - check the errors and try again';

        if($vars['duedate']) {
            if($this->isClosed())
                $errors['duedate']='Due date can NOT be set on a closed ticket';
            elseif(!$vars['time'] || strpos($vars['time'],':')===false)
                $errors['time']='Select time';
            elseif(strtotime($vars['duedate'].' '.$vars['time'])===false)
                $errors['duedate']='Invalid due date';
            elseif(strtotime($vars['duedate'].' '.$vars['time'])<=time())
                $errors['duedate']='Due date must be in the future';
        }

        if($errors) return false;

        $sql='UPDATE '.TICKET_TABLE.' SET updated=NOW() '
            .' ,topic_id='.db_input($vars['topicId'])
            .' ,sla_id='.db_input($vars['slaId'])
            .' ,source='.db_input($vars['source'])
            .' ,duedate='.($vars['duedate']?db_input(date('Y-m-d G:i',Misc::dbtime($vars['duedate'].' '.$vars['time']))):'NULL');

        if($vars['user_id'])
            $sql.=', user_id='.db_input($vars['user_id']);
        if($vars['duedate']) { //We are setting new duedate...
            $sql.=' ,isoverdue=0';
        }

        $sql.=' WHERE ticket_id='.db_input($this->getId());

        if(!db_query($sql) || !db_affected_rows())
            return false;

        if(!$vars['note'])
            $vars['note']=sprintf('Ticket Updated by %s', $thisstaff->getName());

        $this->logNote('Ticket Updated', $vars['note'], $thisstaff);

        // Decide if we need to keep the just selected SLA
        $keepSLA = ($this->getSLAId() != $vars['slaId']);

        // Reload the ticket so we can do further checking
        $this->reload();

        // Reselect SLA if transient
        if (!$keepSLA
                && (!$this->getSLA() || $this->getSLA()->isTransient()))
            $this->selectSLAId();

        // Clear overdue flag if duedate or SLA changes and the ticket is no longer overdue.
        if($this->isOverdue()
                && (!$this->getEstDueDate() //Duedate + SLA cleared
                    || Misc::db2gmtime($this->getEstDueDate()) > Misc::gmtime() //New due date in the future.
                    )) {
            $this->clearOverdue();
        }

        return true;
    }


   /*============== Static functions. Use Ticket::function(params); =============nolint*/
    function getIdByNumber($number, $email=null) {

        if(!$number)
            return 0;

        $sql ='SELECT ticket.ticket_id FROM '.TICKET_TABLE.' ticket '
             .' LEFT JOIN '.USER_TABLE.' user ON user.id = ticket.user_id'
             .' LEFT JOIN '.USER_EMAIL_TABLE.' email ON user.id = email.user_id'
             .' WHERE ticket.`number`='.db_input($number);

        if($email)
            $sql .= ' AND email.address = '.db_input($email);

        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }



    function lookup($id) { //Assuming local ID is the only lookup used!
        return ($id
                && is_numeric($id)
                && ($ticket= new Ticket($id))
                && $ticket->getId()==$id)
            ?$ticket:null;
    }

    function lookupByNumber($number, $email=null) {
        return self::lookup(self:: getIdByNumber($number, $email));
    }

    function genRandTicketNumber($len = EXT_TICKET_ID_LEN) {

        //We can allow collissions...number and email must be unique ...so
        // same number with diff emails is ok.. But for clarity...we are going to make sure it is unique.
        $number = Misc::randNumber($len);
        if(db_num_rows(db_query('SELECT ticket_id FROM '.TICKET_TABLE.'
                        WHERE `number`='.db_input($number))))
            return Ticket::genRandTicketNumber($len);

        return $number;
    }

    function getIdByMessageId($mid, $email) {

        if(!$mid || !$email)
            return 0;

        $sql='SELECT ticket.ticket_id FROM '.TICKET_TABLE. ' ticket '.
             ' LEFT JOIN '.TICKET_THREAD_TABLE.' msg USING(ticket_id) '.
             ' INNER JOIN '.TICKET_EMAIL_INFO_TABLE.' emsg ON (msg.id = emsg.message_id) '.
             ' WHERE email_mid='.db_input($mid).' AND email='.db_input($email);
        $id=0;
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }

    /* Quick staff's tickets stats */
    function getStaffStats($staff) {
        global $cfg;

        /* Unknown or invalid staff */
        if(!$staff || (!is_object($staff) && !($staff=Staff::lookup($staff))) || !$staff->isStaff())
            return null;

        $where = array('(ticket.staff_id='.db_input($staff->getId()) .' AND ticket.status="open")');
        $where2 = '';

        if(($teams=$staff->getTeams()))
            $where[] = ' ( ticket.team_id IN('.implode(',', db_input(array_filter($teams)))
                        .') AND ticket.status="open")';

        if(!$staff->showAssignedOnly() && ($depts=$staff->getDepts())) //Staff with limited access just see Assigned tickets.
            $where[] = 'ticket.dept_id IN('.implode(',', db_input($depts)).') ';

        if(!$cfg || !($cfg->showAssignedTickets() || $staff->showAssignedTickets()))
            $where2 =' AND ticket.staff_id=0 ';
        $where = implode(' OR ', $where);
        if ($where) $where = 'AND ( '.$where.' ) ';

        $sql =  'SELECT \'open\', count( ticket.ticket_id ) AS tickets '
                .'FROM ' . TICKET_TABLE . ' ticket '
                .'WHERE ticket.status = \'open\' '
                .'AND ticket.isanswered =0 '
                . $where . $where2

                .'UNION SELECT \'answered\', count( ticket.ticket_id ) AS tickets '
                .'FROM ' . TICKET_TABLE . ' ticket '
                .'WHERE ticket.status = \'open\' '
                .'AND ticket.isanswered =1 '
                . $where

                .'UNION SELECT \'overdue\', count( ticket.ticket_id ) AS tickets '
                .'FROM ' . TICKET_TABLE . ' ticket '
                .'WHERE ticket.status = \'open\' '
                .'AND ticket.isoverdue =1 '
                . $where

                .'UNION SELECT \'assigned\', count( ticket.ticket_id ) AS tickets '
                .'FROM ' . TICKET_TABLE . ' ticket '
                .'WHERE ticket.status = \'open\' '
                .'AND ticket.staff_id = ' . db_input($staff->getId()) . ' '
                . $where

                .'UNION SELECT \'closed\', count( ticket.ticket_id ) AS tickets '
                .'FROM ' . TICKET_TABLE . ' ticket '
                .'WHERE ticket.status = \'closed\' '
                . $where;

        $res = db_query($sql);
        $stats = array();
        while($row = db_fetch_row($res)) {
            $stats[$row[0]] = $row[1];
        }
        return $stats;
    }

    /* Quick client's tickets stats
       @email - valid email.
     */
    function getUserStats($user) {
        if(!$user || !($user instanceof EndUser))
            return null;

        $sql='SELECT count(open.ticket_id) as open, count(closed.ticket_id) as closed '
            .' FROM '.TICKET_TABLE.' ticket '
            .' LEFT JOIN '.TICKET_TABLE.' open
                ON (open.ticket_id=ticket.ticket_id AND open.status=\'open\') '
            .' LEFT JOIN '.TICKET_TABLE.' closed
                ON (closed.ticket_id=ticket.ticket_id AND closed.status=\'closed\')'
            .' WHERE ticket.user_id = '.db_input($user->getId());

        return db_fetch_array(db_query($sql));
    }

    /*
     * The mother of all functions...You break it you fix it!
     *
     *  $autorespond and $alertstaff overrides config settings...
     */
    static function create($vars, &$errors, $origin, $autorespond=true,
            $alertstaff=true) {
        global $ost, $cfg, $thisclient, $_FILES;

        // Don't enforce form validation for email
        $field_filter = function($type) use ($origin) {
            return function($f) use ($origin, $type) {
                // Ultimately, only offer validation errors for web for
                // non-internal fields. For email, no validation can be
                // performed. For other origins, validate as usual
                switch (strtolower($origin)) {
                case 'email':
                    return false;
                case 'staff':
                    // Required 'Contact Information' fields aren't required
                    // when staff open tickets
                    return $type != 'user'
                        || in_array($f->get('name'), array('name','email'));
                case 'web':
                    return !$f->get('private');
                default:
                    return true;
                }
            };
        };

        $reject_ticket = function($message) use (&$errors) {
            global $ost;
            $errors = array(
                'errno' => 403,
                'err' => 'This help desk is for use by authorized users only');
            $ost->logWarning('Ticket Denied', $message, false);
            return 0;
        };

        // Create and verify the dynamic form entry for the new ticket
        $form = TicketForm::getNewInstance();
        // If submitting via email, ensure we have a subject and such
        foreach ($form->getFields() as $field) {
            $fname = $field->get('name');
            if ($fname && isset($vars[$fname]) && !$field->value)
                $field->value = $field->parse($vars[$fname]);
        }

        if (!$form->isValid($field_filter('ticket')))
            $errors += $form->errors();

        // Unpack dynamic variables into $vars for filter application
        $vars += $form->getFilterData();

        // Unpack the basic user information
        if ($vars['uid'] && ($user = User::lookup($vars['uid']))) {
            $vars['email'] = $user->getEmail();
            $vars['name'] = $user->getName();
            // Add in user and organization data for filtering
            $vars += $user->getFilterData();
            if ($org = $user->getOrganization()) {
                $vars += $org->getFilterData();
            }
        }
        else {
            $interesting = array('name', 'email');
            $user_form = UserForm::getUserForm()->getForm($vars);
            // Add all the user-entered info for filtering
            foreach ($user_form->getFields() as $f) {
                $vars['field.'.$f->get('id')] = $f->toString($f->getClean());
                if (in_array($f->get('name'), $interesting))
                    $vars[$f->get('name')] = $vars['field.'.$f->get('id')];
            }
            // Add in organization data if one exists for this email domain
            list($mailbox, $domain) = explode('@', $vars['email'], 2);
            if ($org = Organization::forDomain($domain)) {
                $vars += $org->getFilterData();
            }
        }


        //Check for 403
        if ($vars['email']
                && Validator::is_email($vars['email'])) {

            //Make sure the email address is not banned
            if (TicketFilter::isBanned($vars['email'])) {
                return $reject_ticket('Banned email - '.$vars['email']);
            }

            //Make sure the open ticket limit hasn't been reached. (LOOP CONTROL)
            if ($cfg->getMaxOpenTickets() > 0
                    && strcasecmp($origin, 'staff')
                    && ($_user=TicketUser::lookupByEmail($vars['email']))
                    && ($openTickets=$_user->getNumOpenTickets())
                    && ($openTickets>=$cfg->getMaxOpenTickets()) ) {

                $errors = array('err' => "You've reached the maximum open tickets allowed.");
                $ost->logWarning('Ticket denied -'.$vars['email'],
                        sprintf('Max open tickets (%d) reached for %s ',
                            $cfg->getMaxOpenTickets(), $vars['email']),
                        false);

                return 0;
            }
        }

        //Init ticket filters...
        $ticket_filter = new TicketFilter($origin, $vars);
        // Make sure email contents should not be rejected
        if ($ticket_filter
                && ($filter=$ticket_filter->shouldReject())) {
            return $reject_ticket(
                sprintf('Ticket rejected ( %s) by filter "%s"',
                    $vars['email'], $filter->getName()));
        }

        if ($vars['topicId'] && ($topic=Topic::lookup($vars['topicId']))) {
            if ($topic_form = $topic->getForm()) {
                $topic_form = $topic_form->instanciate();
                if (!$topic_form->getForm()->isValid($field_filter('topic')))
                    $errors = array_merge($errors, $topic_form->getForm()->errors());
            }
        }

        $id=0;
        $fields=array();
        $fields['message']  = array('type'=>'*',     'required'=>1, 'error'=>'Message required');
        switch (strtolower($origin)) {
            case 'web':
                $fields['topicId']  = array('type'=>'int',  'required'=>1, 'error'=>'Select help topic');
                break;
            case 'staff':
                $fields['deptId']   = array('type'=>'int',  'required'=>0, 'error'=>'Dept. required');
                $fields['topicId']  = array('type'=>'int',  'required'=>1, 'error'=>'Topic required');
                $fields['duedate']  = array('type'=>'date', 'required'=>0, 'error'=>'Invalid date - must be MM/DD/YY');
            case 'api':
                $fields['source']   = array('type'=>'string', 'required'=>1, 'error'=>'Indicate source');
                break;
            case 'email':
                $fields['emailId']  = array('type'=>'int',  'required'=>1, 'error'=>'Email unknown');
                break;
            default:
                # TODO: Return error message
                $errors['err']=$errors['origin'] = 'Invalid origin given';
        }

        if(!Validator::process($fields, $vars, $errors) && !$errors['err'])
            $errors['err'] ='Missing or invalid data - check the errors and try again';

        //Make sure the due date is valid
        if($vars['duedate']) {
            if(!$vars['time'] || strpos($vars['time'],':')===false)
                $errors['time']='Select time';
            elseif(strtotime($vars['duedate'].' '.$vars['time'])===false)
                $errors['duedate']='Invalid due date';
            elseif(strtotime($vars['duedate'].' '.$vars['time'])<=time())
                $errors['duedate']='Due date must be in the future';
        }

        if (!$errors) {

            # Perform ticket filter actions on the new ticket arguments
            if ($ticket_filter) $ticket_filter->apply($vars);

            // Allow vars to be changed in ticket filter and applied to the user
            // account created or detected
            if (!$user && $vars['email'])
                $user = User::lookupByEmail($vars['email']);

            if (!$user) {
                // Reject emails if not from registered clients (if
                // configured)
                if (strcasecmp($origin, 'email') === 0
                        && !$cfg->acceptUnregisteredEmail()) {
                    list($mailbox, $domain) = explode('@', $vars['email'], 2);
                    // Users not yet created but linked to an organization
                    // are still acceptable
                    if (!Organization::forDomain($domain)) {
                        return $reject_ticket(
                            sprintf('Ticket rejected (%s) (unregistered client)',
                                $vars['email']));
                    }
                }

                $user_form = UserForm::getUserForm()->getForm($vars);
                if (!$user_form->isValid($field_filter('user'))
                        || !($user=User::fromVars($user_form->getClean())))
                    $errors['user'] = 'Incomplete client information';
            }
        }

        // Any error above is fatal.
        if ($errors)
            return 0;

        # Some things will need to be unpacked back into the scope of this
        # function
        if (isset($vars['autorespond']))
            $autorespond = $vars['autorespond'];

        # Apply filter-specific priority
        if ($vars['priorityId'])
            $form->setAnswer('priority', null, $vars['priorityId']);

        // If the filter specifies a help topic which has a form associated,
        // and there was previously either no help topic set or the help
        // topic did not have a form, there's no need to add it now as (1)
        // validation is closed, (2) there may be a form already associated
        // and filled out from the original  help topic, and (3) staff
        // members can always add more forms now

        // OK...just do it.
        $deptId = $vars['deptId']; //pre-selected Dept if any.
        $source = ucfirst($vars['source']);

        // Apply email settings for emailed tickets. Email settings should
        // trump help topic settins if the email has an associated help
        // topic
        if ($vars['emailId'] && ($email=Email::lookup($vars['emailId']))) {
            $deptId = $deptId ?: $email->getDeptId();
            $priority = $form->getAnswer('priority');
            if (!$priority || !$priority->getIdValue())
                $form->setAnswer('priority', null, $email->getPriorityId());
            if ($autorespond)
                $autorespond = $email->autoRespond();
            if (!isset($topic)
                    && ($T = $email->getTopic())
                    && ($T->isActive())) {
                $topic = $T;
            }
            $email = null;
            $source = 'Email';
        }

        if (!isset($topic)) {
            // This may return NULL, no big deal
            $topic = $cfg->getDefaultTopic();
        }

        // Intenal mapping magic...see if we need to override anything
        if (isset($topic)) {
            $deptId = $deptId ?: $topic->getDeptId();
            $priority = $form->getAnswer('priority');
            if (!$priority || !$priority->getIdValue())
                $form->setAnswer('priority', null, $topic->getPriorityId());
            if ($autorespond)
                $autorespond = $topic->autoRespond();
            $source = $vars['source'] ?: 'Web';

            //Auto assignment.
            if (!isset($vars['staffId']) && $topic->getStaffId())
                $vars['staffId'] = $topic->getStaffId();
            elseif (!isset($vars['teamId']) && $topic->getTeamId())
                $vars['teamId'] = $topic->getTeamId();

            //set default sla.
            if (isset($vars['slaId']))
                $vars['slaId'] = $vars['slaId'] ?: $cfg->getDefaultSLAId();
            elseif ($topic && $topic->getSLAId())
                $vars['slaId'] = $topic->getSLAId();
        }

        // Auto assignment to organization account manager
        if (($org = $user->getOrganization())
                && $org->autoAssignAccountManager()
                && ($code = $org->getAccountManagerId())) {
            if (!isset($vars['staffId']) && $code[0] == 's')
                $vars['staffId'] = substr($code, 1);
            elseif (!isset($vars['teamId']) && $code[0] == 't')
                $vars['teamId'] = substr($code, 1);
        }

        // Last minute checks
        $priority = $form->getAnswer('priority');
        if (!$priority || !$priority->getIdValue())
            $form->setAnswer('priority', null, $cfg->getDefaultPriorityId());
        $deptId = $deptId ?: $cfg->getDefaultDeptId();
        $topicId = $vars['topicId'] ?: 0;
        $ipaddress = $vars['ip'] ?: $_SERVER['REMOTE_ADDR'];

        //We are ready son...hold on to the rails.
        $number = Ticket::genRandTicketNumber();
        $sql='INSERT INTO '.TICKET_TABLE.' SET created=NOW() '
            .' ,lastmessage= NOW()'
            .' ,user_id='.db_input($user->getId())
            .' ,`number`='.db_input($number)
            .' ,dept_id='.db_input($deptId)
            .' ,topic_id='.db_input($topicId)
            .' ,ip_address='.db_input($ipaddress)
            .' ,source='.db_input($source);

        if (isset($vars['emailId']) && $vars['emailId'])
            $sql.=', email_id='.db_input($vars['emailId']);

        //Make sure the origin is staff - avoid firebug hack!
        if($vars['duedate'] && !strcasecmp($origin,'staff'))
             $sql.=' ,duedate='.db_input(date('Y-m-d G:i',Misc::dbtime($vars['duedate'].' '.$vars['time'])));


        if(!db_query($sql) || !($id=db_insert_id()) || !($ticket =Ticket::lookup($id)))
            return null;

        /* -------------------- POST CREATE ------------------------ */

        if(!$cfg->useRandomIds()) {
            //Sequential ticket number support really..really suck arse.
            //To make things really easy we are going to use autoincrement ticket_id.
            db_query('UPDATE '.TICKET_TABLE.' SET `number`='.db_input($id).' WHERE ticket_id='.$id.' LIMIT 1');
            //TODO: RETHING what happens if this fails?? [At the moment on failure random ID is used...making stuff usable]
        }

        // Save the (common) dynamic form
        $form->setTicketId($id);
        $form->save();

        // Save the form data from the help-topic form, if any
        if ($topic_form) {
            $topic_form->setTicketId($id);
            $topic_form->save();
        }

        $ticket->loadDynamicData();

        $dept = $ticket->getDept();

        // Add organizational collaborators
        if ($org && $org->autoAddCollabs()) {
            $pris = $org->autoAddPrimaryContactsAsCollabs();
            $members = $org->autoAddMembersAsCollabs();
            $settings = array('isactive' => true);
            $collabs = array();
            foreach ($org->allMembers() as $u) {
                if ($members || ($pris && $u->isPrimaryContact())) {
                    if ($c = $ticket->addCollaborator($u, $settings, $errors)) {
                        $collabs[] = (string) $c;
                    }
                }
            }
            //TODO: Can collaborators add others?
            if ($collabs) {
                //TODO: Change EndUser to name of  user.
                $ticket->logNote(sprintf('Collaborators for %s organization added',
                        $org->getName()),
                    implode("<br>", $collabs), $org->getName(), false);
            }
        }

        //post the message.
        unset($vars['cannedattachments']); //Ticket::open() might have it set as part of  open & respond.
        $vars['title'] = $vars['subject']; //Use the initial subject as title of the post.
        $vars['userId'] = $ticket->getUserId();
        $message = $ticket->postMessage($vars , $origin, false);

        // Configure service-level-agreement for this ticket
        $ticket->selectSLAId($vars['slaId']);

        // Assign ticket to staff or team (new ticket by staff)
        if($vars['assignId']) {
            $ticket->assign($vars['assignId'], $vars['note']);
        }
        else {
            // Auto assign staff or team - auto assignment based on filter
            // rules. Both team and staff can be assigned
            if ($vars['staffId'])
                 $ticket->assignToStaff($vars['staffId'], 'Auto Assignment');
            if ($vars['teamId'])
                $ticket->assignToTeam($vars['teamId'], 'Auto Assignment');
        }

        /**********   double check auto-response  ************/
        //Override auto responder if the FROM email is one of the internal emails...loop control.
        if($autorespond && (Email::getIdByEmail($ticket->getEmail())))
            $autorespond=false;

        # Messages that are clearly auto-responses from email systems should
        # not have a return 'ping' message
        if (isset($vars['flags']) && $vars['flags']['bounce'])
            $autorespond = false;
        if ($autorespond && $message->isAutoReply())
            $autorespond = false;

        //post canned auto-response IF any (disables new ticket auto-response).
        if ($vars['cannedResponseId']
            && $ticket->postCannedReply($vars['cannedResponseId'], $message->getId(), $autorespond)) {
                $ticket->markUnAnswered(); //Leave the ticket as unanswred.
                $autorespond = false;
        }

        //Check department's auto response settings
        // XXX: Dept. setting doesn't affect canned responses.
        if($autorespond && $dept && !$dept->autoRespONNewTicket())
            $autorespond=false;

        //Don't send alerts to staff when the message is a bounce
        //  this is necessary to avoid possible loop (especially on new ticket)
        if ($alertstaff && $message->isBounce())
            $alertstaff = false;

        /***** See if we need to send some alerts ****/
        $ticket->onNewTicket($message, $autorespond, $alertstaff);

        /************ check if the user JUST reached the max. open tickets limit **********/
        if($cfg->getMaxOpenTickets()>0
                    && ($user=$ticket->getOwner())
                    && ($user->getNumOpenTickets()==$cfg->getMaxOpenTickets())) {
            $ticket->onOpenLimit(($autorespond && strcasecmp($origin, 'staff')));
        }

        /* Start tracking ticket lifecycle events */
        $ticket->logEvent('created');

        /* Phew! ... time for tea (KETEPA) */

        return $ticket;
    }

    /* routine used by staff to open a new ticket */
    static function open($vars, &$errors) {
        global $thisstaff, $cfg;

        if(!$thisstaff || !$thisstaff->canCreateTickets()) return false;

        if($vars['source'] && !in_array(strtolower($vars['source']),array('email','phone','other')))
            $errors['source']='Invalid source - '.Format::htmlchars($vars['source']);

        if (!$vars['uid']) {
            //Special validation required here
            if (!$vars['email'] || !Validator::is_email($vars['email']))
                $errors['email'] = 'Valid email required';

            if (!$vars['name'])
                $errors['name'] = 'Name required';
        }

        if (!$thisstaff->canAssignTickets())
            unset($vars['assignId']);

        if(!($ticket=Ticket::create($vars, $errors, 'staff', false)))
            return false;

        $vars['msgId']=$ticket->getLastMsgId();

        // post response - if any
        $response = null;
        if($vars['response'] && $thisstaff->canPostReply()) {

            // unpack any uploaded files into vars.
            if ($_FILES['attachments'])
                $vars['files'] = AttachmentFile::format($_FILES['attachments']);

            $vars['response'] = $ticket->replaceVars($vars['response']);
            if(($response=$ticket->postReply($vars, $errors, false))) {
                //Only state supported is closed on response
                if(isset($vars['ticket_state']) && $thisstaff->canCloseTickets())
                    $ticket->setState($vars['ticket_state']);
            }
        }

        // Not assigned...save optional note if any
        if (!$vars['assignId'] && $vars['note']) {
            $ticket->logNote('New Ticket', $vars['note'], $thisstaff, false);
        }
        else {
            // Not assignment and no internal note - log activity
            $ticket->logActivity('New Ticket by Staff',
                'Ticket created by staff -'.$thisstaff->getName());
        }

        $ticket->reload();

        if(!$cfg->notifyONNewStaffTicket()
                || !isset($vars['alertuser'])
                || !($dept=$ticket->getDept()))
            return $ticket; //No alerts.

        //Send Notice to user --- if requested AND enabled!!
        if(($tpl=$dept->getTemplate())
                && ($msg=$tpl->getNewTicketNoticeMsgTemplate())
                && ($email=$dept->getEmail())) {

            $message = (string) $ticket->getLastMessage();
            if($response) {
                $message .= ($cfg->isHtmlThreadEnabled()) ? "<br><br>" : "\n\n";
                $message .= $response->getBody();
            }

            if($vars['signature']=='mine')
                $signature=$thisstaff->getSignature();
            elseif($vars['signature']=='dept' && $dept && $dept->isPublic())
                $signature=$dept->getSignature();
            else
                $signature='';

            $attachments =($cfg->emailAttachments() && $response)?$response->getAttachments():array();

            $msg = $ticket->replaceVars($msg->asArray(),
                    array(
                        'message'   => $message,
                        'signature' => $signature,
                        'response'  => ($response) ? $response->getBody() : '',
                        'recipient' => $ticket->getOwner(), //End user
                        'staff'     => $thisstaff,
                        )
                    );

            $references = $ticket->getLastMessage()->getEmailMessageId();
            if (isset($response))
                $references = array($response->getEmailMessageId(), $references);
            $options = array(
                'references' => $references,
                'thread' => $ticket->getLastMessage()
            );
            $email->send($ticket->getEmail(), $msg['subj'], $msg['body'], $attachments,
                $options);
        }

        return $ticket;

    }

    function checkOverdue() {

        $sql='SELECT ticket_id FROM '.TICKET_TABLE.' T1 '
            .' LEFT JOIN '.SLA_TABLE.' T2 ON (T1.sla_id=T2.id AND T2.isactive=1) '
            .' WHERE status=\'open\' AND isoverdue=0 '
            .' AND ((reopened is NULL AND duedate is NULL AND TIME_TO_SEC(TIMEDIFF(NOW(),T1.created))>=T2.grace_period*3600) '
            .' OR (reopened is NOT NULL AND duedate is NULL AND TIME_TO_SEC(TIMEDIFF(NOW(),reopened))>=T2.grace_period*3600) '
            .' OR (duedate is NOT NULL AND duedate<NOW()) '
            .' ) ORDER BY T1.created LIMIT 50'; //Age upto 50 tickets at a time?
        //echo $sql;
        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($id)=db_fetch_row($res)) {
                if(($ticket=Ticket::lookup($id)) && $ticket->markOverdue())
                    $ticket->logActivity('Ticket Marked Overdue', 'Ticket flagged as overdue by the system.');
            }
        } else {
            //TODO: Trigger escalation on already overdue tickets - make sure last overdue event > grace_period.

        }
   }

}
?>
