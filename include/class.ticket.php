<?php
/*********************************************************************
    class.ticket.php

    The most important class! Don't play with fire please.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
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
include_once(INCLUDE_DIR.'class.pdf.php');
include_once(INCLUDE_DIR.'class.banlist.php');
include_once(INCLUDE_DIR.'class.template.php');
include_once(INCLUDE_DIR.'class.variable.php');
include_once(INCLUDE_DIR.'class.priority.php');
include_once(INCLUDE_DIR.'class.sla.php');
include_once(INCLUDE_DIR.'class.canned.php');

class Ticket {

    var $id;
    var $extid;
    var $email;
    var $status;
    var $created;
    var $reopened;
    var $updated;
    var $lastrespdate;
    var $lastmsgdate;
    var $duedate;
    var $priority;
    var $priority_id;
    var $fullname;
    var $staff_id;
    var $team_id;
    var $dept_id;
    var $topic_id;
    var $dept_name;
    var $subject;
    var $helptopic;
    var $overdue;

    var $lastMsgId;
    
    var $dept;  //Dept obj
    var $sla;   // SLA obj
    var $staff; //Staff obj
    var $client; //Client Obj
    var $team;  //Team obj
    var $topic; //Topic obj
    var $tlock; //TicketLock obj
    
    function Ticket($id){
        $this->id = 0;
        $this->load($id);
    }
    
    function load($id=0) {

        if(!$id && !($id=$this->getId()))
            return false;

        //TODO: delete helptopic field in ticket table.
       
        $sql='SELECT  ticket.*, lock_id, dept_name, priority_desc '
            .' ,count(attach.attach_id) as attachments '
            .' ,count(DISTINCT message.id) as messages '
            .' ,count(DISTINCT response.id) as responses '
            .' ,count(DISTINCT note.id) as notes '
            .' FROM '.TICKET_TABLE.' ticket '
            .' LEFT JOIN '.DEPT_TABLE.' dept ON (ticket.dept_id=dept.dept_id) '
            .' LEFT JOIN '.TICKET_PRIORITY_TABLE.' pri ON ('
                .'ticket.priority_id=pri.priority_id) '
            .' LEFT JOIN '.TICKET_LOCK_TABLE.' tlock ON ('
                .'ticket.ticket_id=tlock.ticket_id AND tlock.expire>NOW()) '
            .' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach ON ('
                .'ticket.ticket_id=attach.ticket_id) '
            .' LEFT JOIN '.TICKET_THREAD_TABLE.' message ON ('
                ."ticket.ticket_id=message.ticket_id AND message.thread_type = 'M') "
            .' LEFT JOIN '.TICKET_THREAD_TABLE.' response ON ('
                ."ticket.ticket_id=response.ticket_id AND response.thread_type = 'R') "
            .' LEFT JOIN '.TICKET_THREAD_TABLE.' note ON ( '
                ."ticket.ticket_id=note.ticket_id AND note.thread_type = 'N') "
            .' WHERE ticket.ticket_id='.db_input($id)
            .' GROUP BY ticket.ticket_id';

        //echo $sql;
        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        
        $this->ht=db_fetch_array($res);
        
        $this->id       = $this->ht['ticket_id'];
        $this->extid    = $this->ht['ticketID'];
         
        $this->email    = $this->ht['email'];
        $this->fullname = $this->ht['name'];
        $this->status   = $this->ht['status'];
        $this->created  = $this->ht['created'];
        $this->reopened = $this->ht['reopened'];
        $this->updated  = $this->ht['updated'];
        $this->duedate  = $this->ht['duedate'];
        $this->closed   = $this->ht['closed'];
        $this->lastmsgdate  = $this->ht['lastmessagedate'];
        $this->lastrespdate = $this->ht['lastresponsedate'];
        
        $this->lock_id  = $this->ht['lock_id'];
        $this->priority_id = $this->ht['priority_id'];
        $this->priority = $this->ht['priority_desc'];
        $this->staff_id = $this->ht['staff_id'];
        $this->team_id = $this->ht['team_id']; 
        $this->dept_id  = $this->ht['dept_id'];
        $this->dept_name = $this->ht['dept_name'];
        $this->sla_id = $this->ht['sla_id'];
        $this->topic_id = $this->ht['topic_id'];
        $this->subject = $this->ht['subject'];
        $this->overdue = $this->ht['isoverdue'];
        
        //Reset the sub classes (initiated ondemand)...good for reloads.
        $this->staff = null;
        $this->client = null;
        $this->team  = null;
        $this->dept = null;
        $this->sla = null;
        $this->tlock = null;
        $this->stats = null;
        $this->topic = null;
        
        return true;
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
        return ($this->overdue);
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

        return ((!$staff->showAssignedOnly() && $staff->canAccessDept($this->getDeptId()))
                 || ($this->getTeamId() && $staff->isTeamMember($this->getTeamId()))
                 || $staff->getId()==$this->getStaffId());
    }

    function checkClientAccess($client) {
        global $cfg;

        if(!is_object($client) && !($client=Client::lookup($client)))
            return false;

        if(!strcasecmp($client->getEmail(),$this->getEmail()))
            return true;

        return ($cfg && $cfg->showRelatedTickets() 
            && $client->getTicketId()==$this->getExtId());
    }

    //Getters
    function getId() {
        return  $this->id;
    }

    function getExtId() {
        return  $this->extid;
    }

    function getNumber() {
        return $this->getExtId();
    }
   
    function getEmail(){
        return $this->email;
    }

    function getAuthToken() {
        # XXX: Support variable email address (for CCs)
        return md5($this->getId() . $this->getEmail() . SECRET_SALT);
    }

    function getName(){
        return $this->fullname;
    }

    function getSubject() {
        return $this->subject;
    }

    /* Help topic title  - NOT object -> $topic */
    function getHelpTopic() {

        if(!$this->helpTopic && ($topic=$this->getTopic()))
            $this->helpTopic = $topic->getName();
            
        return $this->helpTopic;
    }
   
    function getCreateDate(){
        return $this->created;
    }

    function getOpenDate() {
        return $this->getCreateDate();
    }

    function getReopenDate() {
        return $this->reopened;
    }
    
    function getUpdateDate(){
        return $this->updated;
    }

    function getDueDate(){
        return $this->duedate;
    }

    function getCloseDate(){
        return $this->closed;
    }

    function getStatus(){
        return $this->status;
    }
   
    function getDeptId(){
       return $this->dept_id;
    }
   
    function getDeptName(){
       return $this->dept_name;
    }

    function getPriorityId() {
        return $this->priority_id;
    }
    
    function getPriority() {
        return $this->priority;
    }
     
    function getPhone() {
        return $this->ht['phone'];
    }

    function getPhoneExt() {
        return $this->ht['phone_ext'];
    }

    function getPhoneNumber() {
        $phone=Format::phone($this->getPhone());
        if(($ext=$this->getPhoneExt()))
            $phone.=" $ext";

        return $phone;
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

        $info=array('name'  =>  $this->getName(),
                    'email' =>  $this->getEmail(),
                    'phone' =>  $this->getPhone(),
                    'phone_ext' =>  $this->getPhoneExt(),
                    'subject'   =>  $this->getSubject(),
                    'source'    =>  $this->getSource(),
                    'topicId'   =>  $this->getTopicId(),
                    'priorityId'    =>  $this->getPriorityId(),
                    'slaId' =>  $this->getSLAId(),
                    'duedate'   =>  $this->getDueDate()?(Format::userdate('m/d/Y', Misc::db2gmtime($this->getDueDate()))):'',
                    'time'  =>  $this->getDueDate()?(Format::userdate('G:i', Misc::db2gmtime($this->getDueDate()))):'',
                    );
                  
        return $info;
    }

    function getLockId() {
        return $this->lock_id;
    }
    
    function getLock(){
        
        if(!$this->tlock && $this->getLockId())
            $this->tlock= TicketLock::lookup($this->getLockId(),$this->getId());
        
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
        $this->tlock=null; //clear crap
        $this->lock_id=TicketLock::acquire($this->getId(), $staffId, $lockTime); //Create a new lock..
        //load and return the newly created lock if any!
        return $this->getLock();
    }
    
    function getDept(){
        
        if(!$this->dept && $this->getDeptId())
            $this->dept= Dept::lookup($this->getDeptId());

        return $this->dept;
    }

    function getClient() {

        if(!$this->client)
            $this->client = Client::lookup($this->getExtId(), $this->getEmail());

        return $this->client;
    }
    
    function getStaffId(){
        return $this->staff_id;
    }

    function getStaff(){

        if(!$this->staff && $this->getStaffId())
            $this->staff= Staff::lookup($this->getStaffId());

        return $this->staff;
    }

    function getTeamId(){
        return $this->team_id;
    }

    function getTeam(){

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
        return $this->topic_id;
    }

    function getTopic() { 

        if(!$this->topic && $this->getTopicId())
            $this->topic = Topic::lookup($this->getTopicId());

        return $this->topic;
    }

 
    function getSLAId() {
        return $this->sla_id;
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

        if($this->lastmsgdate)
            return $this->lastmsgdate;

        //for old versions...XXX: still needed????
        $sql='SELECT created FROM '.TICKET_THREAD_TABLE
            .' WHERE ticket_id='.db_input($this->getId())
            ."   AND thread_type = 'M'"
            .' ORDER BY created DESC LIMIT 1';
        if(($res=db_query($sql)) && db_num_rows($res))
            list($this->lastmsgdate)=db_fetch_row($res);

        return $this->lastmsgdate;
    }

    function getLastMsgDate() {
        return $this->getLastMessageDate();
    }

    function getLastResponseDate() {
               
        if($this->lastrespdate)
            return $this->lastrespdate;

        $sql='SELECT created FROM '.TICKET_THREAD_TABLE
            .' WHERE ticket_id='.db_input($this->getId())
            .'   AND thread_type="R"'
            .' ORDER BY created DESC LIMIT 1';
        if(($res=db_query($sql)) && db_num_rows($res))
            list($this->lastrespdate)=db_fetch_row($res);

        return $this->lastrespdate;
    }

    function getLastRespDate() {
        return $this->getLastResponseDate();
    }

        
    function getLastMsgId() {
        return $this->lastMsgId;
    }

    function getRelatedTicketsCount(){

        $sql='SELECT count(*)  FROM '.TICKET_TABLE
            .' WHERE email='.db_input($this->getEmail());

        return db_result(db_query($sql));
    }

    function getThreadCount() {
        return $this->getNumMessages() + $this->getNumResponses();
    }

    function getNumMessages() {
        return $this->ht['messages'];
    }

    function getNumResponses() {
        return $this->ht['responses'];
    }

    function getNumNotes() {
        return $this->ht['notes'];
    }

    function getMessages() {
        return $this->getThreadByType('M');
    }

    function getResponses($msgId=0) {
        return $this->getThreadByType('R', $msgId);
    }

    function getNotes() {
        return $this->getThreadByType('N');
    }

    function getClientThread() {
        return $this->getThreadWithoutNotes();
    }

    function getThreadWithNotes() {
        return $this->getThread(true);
    }
    
    function getThreadWithoutNotes() {
        return $this->getThread(false);
    }

    function getThread($includeNotes=false, $order='') {

        $treadtypes=array('M', 'R'); // messages and responses.
        if($includeNotes) //Include notes??
            $treadtypes[] = 'N';

        return $this->getThreadByType($treadtypes, $order);
    }
        
    function getThreadByType($type, $order='ASC') {

        if(!$order || !in_array($order, array('DESC','ASC')))
            $order='ASC';

        $sql='SELECT thread.* '
            .' ,count(DISTINCT attach.attach_id) as attachments '
            .' FROM '.TICKET_THREAD_TABLE.' thread '
            .' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach 
                ON (thread.ticket_id=attach.ticket_id 
                        AND thread.id=attach.ref_id 
                        AND thread.thread_type=attach.ref_type) '
            .' WHERE  thread.ticket_id='.db_input($this->getId());

        if($type && is_array($type))
            $sql.=" AND thread.thread_type IN('".implode("','", $type)."')";
        elseif($type)
            $sql.=' AND thread.thread_type='.db_input($type);

        $sql.=' GROUP BY thread.id '
             .' ORDER BY thread.created '.$order;

        $thread=array();
        if(($res=db_query($sql)) && db_num_rows($res))
            while($rec=db_fetch_array($res))
                $thread[] = $rec;

        return $thread;
    }

    function getAttachments($refId=0, $type=null) {

        if($refId && !$type)
            return NULL;

        //XXX: inner join the file table instead?
        $sql='SELECT a.attach_id, f.id as file_id, f.size, f.hash as file_hash, f.name '
            .' FROM '.FILE_TABLE.' f '
            .' INNER JOIN '.TICKET_ATTACHMENT_TABLE.' a ON(f.id=a.file_id) '
            .' WHERE a.ticket_id='.db_input($this->getId());
       
        if($refId) 
            $sql.=' AND a.ref_id='.db_input($refId);

        if($type)
            $sql.=' AND a.ref_type='.db_input($type);

        $attachments = array();
        if(($res=db_query($sql)) && db_num_rows($res)) {
            while($rec=db_fetch_array($res))
                $attachments[] = $rec;
        }

        return $attachments;
    }

    function getAttachmentsLinks($refId, $type, $separator=' ',$target='') {

        $str='';
        foreach($this->getAttachments($refId, $type) as $attachment ) {
            /* The has here can be changed  but must match validation in attachment.php */
            $hash=md5($attachment['file_id'].session_id().$attachment['file_hash']); 
            if($attachment['size'])
                $size=sprintf('<em>(%s)</em>', Format::file_size($attachment['size']));
                
            $str.=sprintf('<a class="Icon file" href="attachment.php?id=%d&h=%s" target="%s">%s</a>%s&nbsp;%s',
                    $attachment['attach_id'], $hash, $target, Format::htmlchars($attachment['name']), $size, $separator);
        }

        return $str;
    }

    /* -------------------- Setters --------------------- */
    function setLastMsgId($msgid) {
        return $this->lastMsgId=$msgid;
    }

    function setPriority($priorityId) {

        //XXX: what happens to SLA priority???
        
        if(!$priorityId || $priorityId==$this->getPriorityId()) 
            return ($priorityId);
        
        $sql='UPDATE '.TICKET_TABLE.' SET updated=NOW() '
            .', priority_id='.db_input($priorityId)
            .' WHERE ticket_id='.db_input($this->getId());

        return (($res=db_query($sql)) && db_affected_rows($res));
    }

    //DeptId can NOT be 0. No orphans please!
    function setDeptId($deptId){
       
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
        
        $this->staff_id = $staffId;
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
     * department should be applied to the ticket. This would be usefule,
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
        # XXX Should the SLA be overwritten if it was originally set via an
        #     email filter? This method doesn't consider such a case
        if ($trump !== null) {
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

        if(strcasecmp($this->getStatus(),$status)==0)
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
    function close(){
        global $thisstaff;
        
        $sql='UPDATE '.TICKET_TABLE.' SET closed=NOW(),isoverdue=0, duedate=NULL, updated=NOW(), status='.db_input('closed');
        if($thisstaff) //Give the closing  staff credit. 
            $sql.=', staff_id='.db_input($thisstaff->getId());

        $sql.=' WHERE ticket_id='.db_input($this->getId());

        if(!db_query($sql) || !db_affected_rows())
            return false;

        $this->reload();
        $this->logEvent('closed');

        return true;
    }

    //set status to open on a closed ticket.
    function reopen($isanswered=0){

        $sql='UPDATE '.TICKET_TABLE.' SET updated=NOW(), reopened=NOW() '
            .' ,status='.db_input('open')
            .' ,isanswered='.db_input($isanswered)
            .' WHERE ticket_id='.db_input($this->getId());

        //TODO: log reopen event here 

        $this->logEvent('reopened', 'closed');
        return (db_query($sql) && db_affected_rows());
    }

    function onNewTicket($message, $autorespond=true, $alertstaff=true) {
        global $cfg;

        //Log stuff here...
        
        if(!$autorespond && !$alertstaff) return true; //No alerts to send.

        /* ------ SEND OUT NEW TICKET AUTORESP && ALERTS ----------*/
        
        $this->reload(); //get the new goodies.
        $dept= $this->getDept();

        if(!$dept || !($tpl = $dept->getTemplate()))
            $tpl= $cfg->getDefaultTemplate();
        
        if(!$tpl) return false;  //bail out...missing stuff.

        if(!$dept || !($email=$dept->getAutoRespEmail()))
            $email =$cfg->getDefaultEmail();

        //Send auto response - if enabled.
        if($autorespond && $email && $cfg->autoRespONNewTicket() 
                && $dept->autoRespONNewTicket() 
                &&  ($msg=$tpl->getAutoRespMsgTemplate())) {
            
            $msg = $this->replaceVars($msg, 
                    array('message' => $message,
                          'signature' => ($dept && $dept->isPublic())?$dept->getSignature():'')
                    );

            if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()))
                $msg['body'] ="\n$tag\n\n".$msg['body'];
            
            $email->sendAutoReply($this->getEmail(), $msg['subj'], $msg['body']);
        }
        
        if(!($email=$cfg->getAlertEmail()))
            $email =$cfg->getDefaultEmail();
          
        //Send alert to out sleepy & idle staff.
        if($alertstaff && $email
                && $cfg->alertONNewTicket() 
                && ($msg=$tpl->getNewTicketAlertMsgTemplate())) {
                    
            $msg = $this->replaceVars($msg, array('message' => $message));

            $recipients=$sentlist=array();
            //Alert admin??
            if($cfg->alertAdminONNewTicket()) {
                $alert = str_replace('%{recipient}', 'Admin', $msg['body']);
                $email->sendAlert($cfg->getAdminEmail(), $msg['subj'], $alert);
                $sentlist[]=$cfg->getAdminEmail();
            }
              
            //Only alerts dept members if the ticket is NOT assigned.
            if($cfg->alertDeptMembersONNewTicket() && !$this->isAssigned()) {
                if(($members=$dept->getMembers()))
                    $recipients=array_merge($recipients, $members);
            }
            
            if($cfg->alertDeptManagerONNewTicket() && $dept && ($manager=$dept->getManager()))
                $recipients[]= $manager;
               
            foreach( $recipients as $k=>$staff){
                if(!is_object($staff) || !$staff->isAvailable() || in_array($staff->getEmail(),$sentlist)) continue;
                $alert = str_replace('%{recipient}', $staff->getFirstName(), $msg['body']);
                $email->sendAlert($staff->getEmail(), $msg['subj'], $alert);
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

        if(!$sendNotice || !$cfg->sendOverLimitNotice()) return true;

        //Send notice to user.
        $dept = $this->getDept();
                    
        if(!$dept || !($tpl=$dept->getTemplate()))
            $tpl=$cfg->getDefaultTemplate();
            
        if(!$dept || !($email=$dept->getAutoRespEmail()))
            $email=$cfg->getDefaultEmail();

        if($tpl && ($msg=$tpl->getOverlimitMsgTemplate()) && $email) {
            
            $msg = $this->replaceVars($msg, 
                        array('signature' => ($dept && $dept->isPublic())?$dept->getSignature():''));
            
            $email->sendAutoReply($this->getEmail(), $msg['subj'], $msg['body']);
        }

        $client= $this->getClient();
        
        //Alert admin...this might be spammy (no option to disable)...but it is helpful..I think.
        $alert='Max. open tickets reached for '.$this->getEmail()."\n"
              .'Open ticket: '.$client->getNumOpenTickets()."\n"
              .'Max Allowed: '.$cfg->getMaxOpenTickets()."\n\nNotice sent to the user.";
            
        $ost->alertAdmin('Overlimit Notice', $alert);
       
        return true;
    }

    function onResponse(){
        db_query('UPDATE '.TICKET_TABLE.' SET isanswered=1,lastresponse=NOW(), updated=NOW() WHERE ticket_id='.db_input($this->getId()));
    }

    function onMessage($autorespond=true, $alert=true){
        global $cfg;

        db_query('UPDATE '.TICKET_TABLE.' SET isanswered=0,lastmessage=NOW() WHERE ticket_id='.db_input($this->getId()));
            
        //auto-assign to closing staff or last respondent 
        if(!($staff=$this->getStaff()) || !$staff->isAvailable()) {
            if($cfg->autoAssignReopenedTickets() && ($lastrep=$this->getLastRespondent()) && $lastrep->isAvailable()) {
                $this->setStaffId($lastrep->getId()); //direct assignment;
            } else {
                $this->setStaffId(0); //unassign - last respondent is not available.
            }
        }

        if($this->isClosed()) $this->reopen(); //reopen..

       /**********   double check auto-response  ************/
        if($autorespond && (Email::getIdByEmail($this->getEmail())))
            $autorespond=false;
        elseif($autorespond && ($dept=$this->getDept()))
            $autorespond=$dept->autoRespONNewMessage();


        if(!$autorespond || !$cfg->autoRespONNewMessage()) return;  //no autoresp or alerts.

        $this->reload();


        if(!$dept || !($tpl = $dept->getTemplate()))
            $tpl = $cfg->getDefaultTemplate();

        if(!$dept || !($email = $dept->getAutoRespEmail()))
            $email = $cfg->getDefaultEmail();
      
        //If enabled...send confirmation to user. ( New Message AutoResponse)
        if($email && $tpl && ($msg=$tpl->getNewMessageAutorepMsgTemplate())) {

            $msg = $this->replaceVars($msg,
                            array('signature' => ($dept && $dept->isPublic())?$dept->getSignature():''));

            //Reply separator tag.
            if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()))
                $msg['body'] ="\n$tag\n\n".$msg['body'];
        
            $email->sendAutoReply($this->getEmail(), $msg['subj'], $msg['body']);
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
        $this->logNote('Ticket Assigned to '.$assignee->getName(), $comments, $assigner, false);

        //See if we need to send alerts
        if(!$alert || !$cfg->alertONAssignment()) return true; //No alerts!

        $dept = $this->getDept();

        //Get template.
        if(!$dept || !($tpl = $dept->getTemplate()))
            $tpl = $cfg->getDefaultTemplate();

        //Email to use!
        if(!($email=$cfg->getAlertEmail()))
            $email = $cfg->getDefaultEmail();

        //recipients
        $recipients=array();
        if(!strcasecmp(get_class($assignee), 'Staff')) {
            if($cfg->alertStaffONAssignment())
                $recipients[] = $assignee;
        } elseif(!strcasecmp(get_class($assignee), 'Team')) {
            if($cfg->alertTeamMembersONAssignment() && ($members=$assignee->getMembers()))
                $recipients+=$members;
            elseif($cfg->alertTeamLeadONAssignment() && ($lead=$assignee->getTeamLead()))
                $recipients[] = $lead;
        }

        //Get the message template
        if($email && $recipients && $tpl && ($msg=$tpl->getAssignedAlertMsgTemplate())) {

            $msg = $this->replaceVars($msg, 
                        array('comments' => $comments,
                              'assignee' => $assignee,
                              'assigner' => $assigner
                              ));

            //Send the alerts.
            $sentlist=array();
            foreach( $recipients as $k=>$staff) {
                if(!is_object($staff) || !$staff->isAvailable() || in_array($staff->getEmail(),$sentlist)) continue;
                $alert = str_replace('%{recipient}', $staff->getFirstName(), $msg['body']);
                $email->sendAlert($staff->getEmail(), $msg['subj'], $alert);
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
        if(!$whine || !$cfg->alertONOverdueTicket())
            return true;

        $dept = $this->getDept();
        //Get department-defined or default template.
        if(!$dept || !($tpl = $dept->getTemplate()))
            $tpl= $cfg->getDefaultTemplate();

        //Email to use!
        if(!($email=$cfg->getAlertEmail()))
            $email =$cfg->getDefaultEmail();

        //Get the message template
        if($tpl && ($msg=$tpl->getOverdueAlertMsgTemplate()) && $email) {
            
            $msg = $this->replaceVars($msg, array('comments' => $comments));

            //recipients
            $recipients=array();
            //Assigned staff or team... if any
            if($this->isAssigned() && $cfg->alertAssignedONTransfer()) {
                if($this->getStaffId())
                    $recipients[]=$this->getStaff();
                elseif($this->getTeamId() && ($team=$this->getTeam()) && ($members=$team->getMembers()))
                    $recipients=array_merge($recipients, $members);
            } elseif($cfg->alertDeptMembersONOverdueTicket() && !$this->isAssigned()) {
                //Only alerts dept members if the ticket is NOT assigned.
                if(($members=$dept->getMembers()))
                    $recipients=array_merge($recipients, $members);
            }
            //Always alert dept manager??
            if($cfg->alertDeptManagerONOverdueTicket() && $dept && ($manager=$dept->getManager()))
                $recipients[]= $manager;

            $sentlist=array();
            foreach( $recipients as $k=>$staff){
                if(!is_object($staff) || !$staff->isAvailable() || in_array($staff->getEmail(),$sentlist)) continue;
                $alert = str_replace("%{recipient}", $staff->getFirstName(), $msg['body']);
                $email->sendAlert($staff->getEmail(), $msg['subj'], $alert);
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

        switch(strtolower($tag)) {
            case 'phone_number':
                return $this->getPhoneNumber();
                break;
            case 'auth_token':
                return $this->getAuthToken();
                break;
            case 'client_link':
                return sprintf('%s/view.php?t=%s&e=%s&a=%s',
                        $cfg->getBaseUrl(), $this->getNumber(), $this->getEmail(), $this->getAuthToken());
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
                if($this->getDueDate())
                    $duedate = Format::date(
                            $cfg->getDateTimeFormat(),
                            Misc::db2gmtime($this->getDueDate()),
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

        $sql='UPDATE '.TICKET_TABLE.' SET isoverdue=0, updated=NOW() ';
        //clear due date if it's in the past
        if($this->getDueDate() && strtotime($this->getDueDate())<=time())
            $sql.=', duedate=NULL';

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
        // Change to SLA of the new department
        $this->selectSLAId();
                  
        /*** log the transfer comments as internal note - with alerts disabled - ***/
        $title='Ticket transfered from '.$currentDept.' to '.$this->getDeptName();
        $comments=$comments?$comments:$title; 
        $this->logNote($title, $comments, $thisstaff, false);

        $this->logEvent('transferred');
        
        //Send out alerts if enabled AND requested
        if(!$alert || !$cfg->alertONTransfer() || !($dept=$this->getDept())) return true; //no alerts!!


         //Get template.
         if(!($tpl = $dept->getTemplate()))
             $tpl= $cfg->getDefaultTemplate();
        
         //Email to use!
         if(!($email=$cfg->getAlertEmail()))
             $email =$cfg->getDefaultEmail();
                
         //Get the message template 
         if($tpl && ($msg=$tpl->getTransferAlertMsgTemplate()) && $email) {
            
             $msg = $this->replaceVars($msg, array('comments' => $comments, 'staff' => $thisstaff));
            //recipients            
            $recipients=array();
            //Assigned staff or team... if any
            if($this->isAssigned() && $cfg->alertAssignedONTransfer()) {
                if($this->getStaffId())
                    $recipients[]=$this->getStaff();
                elseif($this->getTeamId() && ($team=$this->getTeam()) && ($members=$team->getMembers()))
                    $recipients+=$members;
            } elseif($cfg->alertDeptMembersONTransfer() && !$this->isAssigned()) {
                //Only alerts dept members if the ticket is NOT assigned.
                if(($members=$dept->getMembers()))
                    $recipients+=$members;
            }

            //Always alert dept manager??
            if($cfg->alertDeptManagerONTransfer() && $dept && ($manager=$dept->getManager()))
                $recipients[]= $manager;
             
            $sentlist=array();
            foreach( $recipients as $k=>$staff){
                if(!is_object($staff) || !$staff->isAvailable() || in_array($staff->getEmail(),$sentlist)) continue;
                $alert = str_replace('%{recipient}',$staff->getFirstName(), $msg['body']);
                $email->sendAlert($staff->getEmail(), $msg['subj'], $alert);
                $sentlist[] = $staff->getEmail();
            }
         }

         return true;
    }

    function assignToStaff($staff, $note, $alert=true) {

        if(!is_object($staff) && !($staff=Staff::lookup($staff)))
            return false;
        
        if(!$this->setStaffId($staff->getId()))
            return false;

        $this->onAssign($staff, $note, $alert);
        $this->logEvent('assigned');

        return true;
    }

    function assignToTeam($team, $note, $alert=true) {

        if(!is_object($team) && !($team=Team::lookup($team)))
            return false;

        if(!$this->setTeamId($team->getId()))
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
        $id=preg_replace("/[^0-9]/", "",$assignId);
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

    //Insert message from client
    function postMessage($message,$source='',$emsgid=null,$headers='',$newticket=false){
        global $cfg;
       
        if(!$this->getId()) return 0;

        //Strip quoted reply...on emailed replies
        if(!strcasecmp($source, 'Email') 
                && $cfg->stripQuotedReply() 
                && ($tag=$cfg->getReplySeparator()) && strpos($message, $tag))
            list($message)=split($tag, $message);

        # XXX: Refuse auto-response messages? (via email) XXX: No - but kill our auto-responder.

        $sql='INSERT INTO '.TICKET_THREAD_TABLE.' SET created=NOW()'
            .' ,thread_type="M" '
            .' ,ticket_id='.db_input($this->getId())
            # XXX: Put Subject header into the 'title' field
            .' ,body='.db_input(Format::striptags($message)) //Tags/code stripped...meaning client can not send in code..etc
            .' ,source='.db_input($source?$source:$_SERVER['REMOTE_ADDR'])
            .' ,ip_address='.db_input($_SERVER['REMOTE_ADDR']);
    
        if(!db_query($sql) || !($msgid=db_insert_id())) return 0; //bail out....

        $this->setLastMsgId($msgid);

        if ($emsgid !== null) {
            $sql='INSERT INTO '.TICKET_EMAIL_INFO_TABLE
                .' SET message_id='.db_input($msgid)
                .', email_mid='.db_input($emsgid)
                .', headers='.db_input($headers);
            db_query($sql);
        }

        if($newticket) return $msgid; //Our work is done...

        $autorespond = true;
        if ($autorespond && $headers && TicketFilter::isAutoResponse(Mail_Parse::splitHeaders($headers)))
            $autorespond=false;

        $this->onMessage($autorespond); //must be called b4 sending alerts to staff.

        $dept = $this->getDept();

        if(!$dept || !($tpl = $dept->getTemplate()))
            $tpl= $cfg->getDefaultTemplate();

        if(!($email=$cfg->getAlertEmail()))
            $email =$cfg->getDefaultEmail();

        //If enabled...send alert to staff (New Message Alert)
        if($cfg->alertONNewMessage() && $tpl && $email && ($msg=$tpl->getNewMessageAlertMsgTemplate())) {

            $msg = $this->replaceVars($msg, array('message' => $message));

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
                
            $sentlist=array(); //I know it sucks...but..it works.
            foreach( $recipients as $k=>$staff){
                if(!$staff || !$staff->getEmail() || !$staff->isAvailable() || in_array($staff->getEmail(), $sentlist)) continue;
                $alert = str_replace('%{recipient}', $staff->getFirstName(), $msg['body']);
                $email->sendAlert($staff->getEmail(), $msg['subj'], $alert);
                $sentlist[] = $staff->getEmail();
            }
        }
        
        return $msgid;
    }

    function postCannedReply($canned, $msgId, $alert=true) {
        global $ost, $cfg;

        if((!is_object($canned) && !($canned=Canned::lookup($canned))) || !$canned->isEnabled())
            return false;

        $files = array();
        foreach ($canned->getAttachments() as $file)
            $files[] = $file['id'];

        $info = array('msgId' => $msgId,
                      'response' => $this->replaceVars($canned->getResponse()),
                      'cannedattachments' => $files);

        if(!($respId=$this->postReply($info, $errors, false)))
            return false;

        $this->markUnAnswered();

        if(!$alert) return $respId;

        $dept = $this->getDept();

        if(!($tpl = $dept->getTemplate()))
            $tpl= $cfg->getDefaultTemplate();

        if(!$dept || !($email=$dept->getEmail()))
            $email = $cfg->getDefaultEmail();

        if($tpl && ($msg=$tpl->getAutoReplyMsgTemplate()) && $email) {

            if($dept && $dept->isPublic())
                $signature=$dept->getSignature();
            else
                $signature='';

            $msg = $this->replaceVars($msg, array('response' => $info['response'], 'signature' => $signature));

            if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()))
                $msg['body'] ="\n$tag\n\n".$msg['body'];

            $attachments =($cfg->emailAttachments() && $files)?$this->getAttachments($respId, 'R'):array();
            $email->sendAutoReply($this->getEmail(), $msg['subj'], $msg['body'], $attachments);
        }

        return $respId;
    }

    /* public */ 
    function postReply($vars, &$errors, $alert = true) {
        global $thisstaff, $cfg;

        if(!$vars['msgId'])
            $errors['msgId'] ='Missing messageId - internal error';
        if(!$vars['response'])
            $errors['response'] = 'Response message required';

        if($errors) return 0;

        $poster = $thisstaff?$thisstaff->getName():'SYSTEM (Canned Reply)';

        $sql='INSERT INTO '.TICKET_THREAD_TABLE.' SET created=NOW() '
            .' ,thread_type="R"'
            .' ,ticket_id='.db_input($this->getId())
            .' ,pid='.db_input($vars['msgId'])
            .' ,body='.db_input(Format::striptags($vars['response']))
            .' ,staff_id='.db_input($thisstaff?$thisstaff->getId():0)
            .' ,poster='.db_input($poster)
            .' ,ip_address='.db_input($thisstaff?$thisstaff->getIP():'');

        if(!db_query($sql) || !($respId=db_insert_id()))
            return false;

        //Set status - if checked.
        if(isset($vars['reply_ticket_status']) && $vars['reply_ticket_status'])
            $this->setStatus($vars['reply_ticket_status']);

        /* We can NOT recover from attachment related failures at this point */
        $attachments = array();
        //Web based upload.. note that we're not "validating" attachments on response.
        if($_FILES['attachments'] && ($files=Format::files($_FILES['attachments'])))
            $attachments=$this->uploadAttachments($files, $respId, 'R');

        //Canned attachments...
        if($vars['cannedattachments'] && is_array($vars['cannedattachments'])) {
            foreach($vars['cannedattachments'] as $fileId)
                if($fileId && $this->saveAttachment($fileId, $respId, 'R'))
                    $attachments[] = $fileId;
        }

        $this->onResponse(); //do house cleaning..
        $this->reload();

        /* email the user??  - if disabled - the bail out */
        if(!$alert) return $respId;

        $dept = $this->getDept();

        if(!($tpl = $dept->getTemplate()))
            $tpl= $cfg->getDefaultTemplate();

        if(!$dept || !($email=$dept->getEmail()))
            $email = $cfg->getDefaultEmail();

        if($tpl && ($msg=$tpl->getReplyMsgTemplate()) && $email) {

            if($thisstaff && $vars['signature']=='mine')
                $signature=$thisstaff->getSignature();
            elseif($vars['signature']=='dept' && $dept && $dept->isPublic())
                $signature=$dept->getSignature();
            else
                $signature='';
            
            $msg = $this->replaceVars($msg, 
                    array('response' => $vars['response'], 'signature' => $signature, 'staff' => $thisstaff));

            if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()))
                $msg['body'] ="\n$tag\n\n".$msg['body'];

            //Set attachments if emailing.
            $attachments =($cfg->emailAttachments() && $attachments)?$this->getAttachments($respId,'R'):array();
            //TODO: setup  5 param (options... e.g mid trackable on replies)
            $email->send($this->getEmail(), $msg['subj'], $msg['body'], $attachments);
        }

        return $respId;
    }

    //Activity log - saved as internal notes WHEN enabled!!
    function logActivity($title,$note){
        global $cfg;

        if(!$cfg || !$cfg->logTicketActivity())
            return 0;

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

        return $this->postNote(
                array('title' => $title, 'note' => $note),
                $errors,
                $poster,
                $alert);
    }

    function postNote($vars, &$errors, $poster, $alert=true) {        
        global $cfg, $thisstaff;

        if(!$vars || !is_array($vars))
            $errors['err'] = 'Missing or invalid data';
        elseif(!$vars['note'])
            $errors['note'] = 'Note required';

        if($errors) return false;
		
        $staffId = 0;
        if($poster && is_object($poster)) {
            $staffId = $poster->getId();
            $poster = $poster->getName();
        } elseif(!$poster) {
            $poster ='SYSTEM';
        }

        //TODO: move to class.thread.php

        $sql= 'INSERT INTO '.TICKET_THREAD_TABLE.' SET created=NOW() '.
                ',thread_type="N"'.
                ',ticket_id='.db_input($this->getId()).
                ',title='.db_input(Format::striptags($vars['title']?$vars['title']:'[No Title]')).
                ',body='.db_input(Format::striptags($vars['note'])).
                ',staff_id='.db_input($staffId).
                ',poster='.db_input($poster);
        //echo $sql;
        if(!db_query($sql) || !($id=db_insert_id()))
            return false;
                
        //Upload attachments IF ANY - TODO: validate attachment types??
        if($_FILES['attachments'] && ($files=Format::files($_FILES['attachments'])))
            $attachments = $this->uploadAttachments($files, $id, 'N');
            
        //Set state: Error on state change not critical! 
        if(isset($vars['state']) && $vars['state']) {
            if($this->setState($vars['state']))
                $this->reload();
        }

        // If alerts are not enabled then return a success.
        if(!$alert || !$cfg->alertONNewNote() || !($dept=$this->getDept()))
            return $id;

        //Note obj.
        $note = Note::lookup($id, $this->getId());
        
        if(!($tpl = $dept->getTemplate()))
            $tpl= $cfg->getDefaultTemplate();

        if(!($email=$cfg->getAlertEmail()))
            $email =$cfg->getDefaultEmail();


        if($tpl && ($msg=$tpl->getNoteAlertMsgTemplate()) && $email) {
                   
            $msg = $this->replaceVars($msg, array('note' => $note));

            // Alert recipients    
            $recipients=array();
            
            //Last respondent.
            if($cfg->alertLastRespondentONNewNote())
                $recipients[]=$this->getLastRespondent();
            
            //Assigned staff if any...could be the last respondent
            if($cfg->alertAssignedONNewNote() && $this->isAssigned() && $this->getStaffId())
                $recipients[]=$this->getStaff();
                
            //Dept manager
            if($cfg->alertDeptManagerONNewNote() && $dept && $dept->getManagerId())
                $recipients[]=$dept->getManager();

            $attachments =($attachments)?$this->getAttachments($id, 'N'):array();
            $sentlist=array();
            foreach( $recipients as $k=>$staff) {
                if(!$staff || !is_object($staff) || !$staff->getEmail() || !$staff->isAvailable()) continue;
                if(in_array($staff->getEmail(),$sentlist) || ($staffId && $staffId==$staff->getId())) continue; 
                $alert = str_replace('%{recipient}',$staff->getFirstName(), $msg['body']);
                $email->sendAlert($staff->getEmail(), $msg['subj'], $alert, $attachments);
                $sentlist[] = $staff->getEmail();
            }
        }
        
        return $id;
    }

    //Print ticket... export the ticket thread as PDF.
    function pdfExport($psize='Letter', $notes=false) {
        $pdf = new Ticket2PDF($this, $psize, $notes);
        $name='Ticket-'.$this->getExtId().'.pdf';
        $pdf->Output($name, 'I');
        //Remember what the user selected - for autoselect on the next print.
        $_SESSION['PAPER_SIZE'] = $psize;
        exit;
    }

    //online based attached files.
    function uploadAttachments($files, $refid, $type) {
        global $ost;

        $uploaded=array();
        foreach($files as $file) {
            if(!$file['error'] 
                    && ($id=AttachmentFile::upload($file)) 
                    && $this->saveAttachment($id, $refid, $type))
                $uploaded[]=$id;
            elseif($file['error']!=UPLOAD_ERR_NO_FILE) { 
                
                // log file upload errors as interal notes + syslog debug.
                if($file['error'] && gettype($file['error'])=='string')
                    $error = $file['error'];
                else
                    $error ='Error #'.$file['error'];

                $this->logNote('File Upload Error', $error, 'SYSTEM', false);
               
                $ost->logDebug('File Upload Error (Ticket #'.$this->getExtId().')', $error);
            }
            
        }
        
        return $uploaded;
    }

    /*
       Save attachment to the DB. uploads (above), email or json/xml.
       
       @file is a mixed var - can be ID or file hash.
     */
    function saveAttachment($file, $refid, $type) {

        if(!$refid || !$type || !($fileId=is_numeric($file)?$file:AttachmentFile::save($file)))
            return 0;

        $sql ='INSERT INTO '.TICKET_ATTACHMENT_TABLE.' SET created=NOW() '
             .' ,ticket_id='.db_input($this->getId())
             .' ,file_id='.db_input($fileId)
             .' ,ref_id='.db_input($refid)
             .' ,ref_type='.db_input($type);

        return (db_query($sql) && ($id=db_insert_id()))?$id:0;
    }
    


    function deleteAttachments(){
        
        $deleted=0;
        // Clear reference table
        $res=db_query('DELETE FROM '.TICKET_ATTACHMENT_TABLE.' WHERE ticket_id='.db_input($this->getId()));
        if ($res && db_affected_rows())
            $deleted = AttachmentFile::deleteOrphans();

        return $deleted;
    }


    function delete(){
        
        $sql='DELETE FROM '.TICKET_TABLE.' WHERE ticket_id='.$this->getId().' LIMIT 1';
        if(!db_query($sql) || !db_affected_rows())
            return false;

        db_query('DELETE FROM '.TICKET_THREAD_TABLE.' WHERE ticket_id='.db_input($this->getId()));
        $this->deleteAttachments();
        
        return true;
    }

    function update($vars, &$errors) {

        global $cfg, $thisstaff;
        
        if(!$cfg || !$thisstaff || !$thisstaff->canEditTickets())
            return false;
         
        $fields=array();
        $fields['name']     = array('type'=>'string',   'required'=>1, 'error'=>'Name required');
        $fields['email']    = array('type'=>'email',    'required'=>1, 'error'=>'Valid email required');
        $fields['subject']  = array('type'=>'string',   'required'=>1, 'error'=>'Subject required');
        $fields['topicId']  = array('type'=>'int',      'required'=>1, 'error'=>'Help topic required');
        $fields['priorityId'] = array('type'=>'int',    'required'=>1, 'error'=>'Priority required');
        $fields['slaId']    = array('type'=>'int',      'required'=>0, 'error'=>'Select SLA');
        $fields['phone']    = array('type'=>'phone',    'required'=>0, 'error'=>'Valid phone # required');
        $fields['duedate']  = array('type'=>'date',     'required'=>0, 'error'=>'Invalid date - must be MM/DD/YY');

        $fields['note']     = array('type'=>'text',     'required'=>1, 'error'=>'Reason for the update required');

        if(!Validator::process($fields, $vars, $errors) && !$errors['err'])
            $errors['err'] = 'Missing or invalid data - check the errors and try again';

        if($vars['duedate']) {     
            if($this->isClosed())
                $errors['duedate']='Duedate can NOT be set on a closed ticket';
            elseif(!$vars['time'] || strpos($vars['time'],':')===false)
                $errors['time']='Select time';
            elseif(strtotime($vars['duedate'].' '.$vars['time'])===false)
                $errors['duedate']='Invalid duedate';
            elseif(strtotime($vars['duedate'].' '.$vars['time'])<=time())
                $errors['duedate']='Due date must be in the future';
        }
        
        //Make sure phone extension is valid
        if($vars['phone_ext'] ) {
            if(!is_numeric($vars['phone_ext']) && !$errors['phone'])
                $errors['phone']='Invalid phone ext.';
            elseif(!$vars['phone']) //make sure they just didn't enter ext without phone #
                $errors['phone']='Phone number required';
        }

        if($errors) return false;

        $sql='UPDATE '.TICKET_TABLE.' SET updated=NOW() '
            .' ,email='.db_input($vars['email'])
            .' ,name='.db_input(Format::striptags($vars['name']))
            .' ,subject='.db_input(Format::striptags($vars['subject']))
            .' ,phone="'.db_input($vars['phone'],false).'"'
            .' ,phone_ext='.db_input($vars['phone_ext']?$vars['phone_ext']:NULL)
            .' ,priority_id='.db_input($vars['priorityId'])
            .' ,topic_id='.db_input($vars['topicId'])
            .' ,sla_id='.db_input($vars['slaId'])
            .' ,duedate='.($vars['duedate']?db_input(date('Y-m-d G:i',Misc::dbtime($vars['duedate'].' '.$vars['time']))):'NULL');
             
        if($vars['duedate']) { //We are setting new duedate...
            $sql.=' ,isoverdue=0';
        }
             
        $sql.=' WHERE ticket_id='.db_input($this->getId());

        if(!db_query($sql) || !db_affected_rows())
            return false;

        if(!$vars['note'])
            $vars['note']=sprintf('Ticket Updated by %s', $thisstaff->getName());

        $this->logNote('Ticket Updated', $vars['note'], $thisstaff);
        $this->reload();
        
        return true;
    }

   
   /*============== Static functions. Use Ticket::function(params); ==================*/
    function getIdByExtId($extId, $email=null) {
        
        if(!$extId || !is_numeric($extId)) 
            return 0;

        $sql ='SELECT  ticket_id FROM '.TICKET_TABLE.' ticket '
             .' WHERE ticketID='.db_input($extId);
        
        if($email)
            $sql.=' AND email='.db_input($email);

        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }


   
    function lookup($id) { //Assuming local ID is the only lookup used!
        return ($id && is_numeric($id) && ($ticket= new Ticket($id)) && $ticket->getId()==$id)?$ticket:null;    
    }

    function lookupByExtId($id, $email=null) {
        return self::lookup(self:: getIdByExtId($id, $email));
    }

    function genExtRandID() {
        global $cfg;

        //We can allow collissions...extId and email must be unique ...so same id with diff emails is ok..
        // But for clarity...we are going to make sure it is unique.
        $id=Misc::randNumber(EXT_TICKET_ID_LEN);
        if(db_num_rows(db_query('SELECT ticket_id FROM '.TICKET_TABLE.' WHERE ticketID='.db_input($id))))
            return Ticket::genExtRandID();

        return $id;
    }

    function getIdByMessageId($mid,$email) {

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

    function getOpenTicketsByEmail($email){

        $sql='SELECT count(*) as open FROM '.TICKET_TABLE.' WHERE status='.db_input('open').' AND email='.db_input($email);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($num)=db_fetch_row($res);

        return $num;
    }

    /* Quick staff's tickets stats */ 
    function getStaffStats($staff) {
        global $cfg;
        
        /* Unknown or invalid staff */
        if(!$staff || (!is_object($staff) && !($staff=Staff::lookup($staff))) || !$staff->isStaff() || $cfg->getDBVersion())
            return null;


        $sql='SELECT count(open.ticket_id) as open, count(answered.ticket_id) as answered '
            .' ,count(overdue.ticket_id) as overdue, count(assigned.ticket_id) as assigned, count(closed.ticket_id) as closed '
            .' FROM '.TICKET_TABLE.' ticket '
            .' LEFT JOIN '.TICKET_TABLE.' open
                ON (open.ticket_id=ticket.ticket_id AND open.status=\'open\' AND open.isanswered=0) '
            .' LEFT JOIN '.TICKET_TABLE.' answered
                ON (answered.ticket_id=ticket.ticket_id AND answered.status=\'open\' AND answered.isanswered=1) '
            .' LEFT JOIN '.TICKET_TABLE.' overdue
                ON (overdue.ticket_id=ticket.ticket_id AND overdue.status=\'open\' AND overdue.isoverdue=1) '
            .' LEFT JOIN '.TICKET_TABLE.' assigned
                ON (assigned.ticket_id=ticket.ticket_id AND assigned.status=\'open\' AND assigned.staff_id='.db_input($staff->getId()).')'
            .' LEFT JOIN '.TICKET_TABLE.' closed
                ON (closed.ticket_id=ticket.ticket_id AND closed.status=\'closed\' AND closed.staff_id='.db_input($staff->getId()).')'
            .' WHERE (ticket.staff_id='.db_input($staff->getId());

        if(($teams=$staff->getTeams()))
            $sql.=' OR ticket.team_id IN('.implode(',', db_input(array_filter($teams))).')';

        if(!$staff->showAssignedOnly() && ($depts=$staff->getDepts())) //Staff with limited access just see Assigned tickets.
            $sql.=' OR ticket.dept_id IN('.implode(',', db_input($depts)).') ';

        $sql.=')';

        if(!$cfg || !($cfg->showAssignedTickets() || $staff->showAssignedTickets()))
            $sql.=' AND (ticket.staff_id=0 OR ticket.staff_id='.db_input($staff->getId()).') ';

     
        return db_fetch_array(db_query($sql));
    }


    /* Quick client's tickets stats 
       @email - valid email. 
     */
    function getClientStats($email) {

        if(!$email || !Validator::is_email($email))
            return null;

        $sql='SELECT count(open.ticket_id) as open, count(closed.ticket_id) as closed '
            .' FROM '.TICKET_TABLE.' ticket '
            .' LEFT JOIN '.TICKET_TABLE.' open
                ON (open.ticket_id=ticket.ticket_id AND open.status=\'open\') '
            .' LEFT JOIN '.TICKET_TABLE.' closed
                ON (closed.ticket_id=ticket.ticket_id AND closed.status=\'closed\')'
            .' WHERE ticket.email='.db_input($email);

        return db_fetch_array(db_query($sql));
    }

    /*
     * The mother of all functions...You break it you fix it!
     *
     *  $autorespond and $alertstaff overwrites config settings...
     */      
    function create($vars, &$errors, $origin, $autorespond=true, $alertstaff=true) {
        global $ost, $cfg, $thisclient, $_FILES;

        //Check for 403
        if ($vars['email']  && Validator::is_email($vars['email'])) {

            //Make sure the email address is not banned
            if(TicketFilter::isBanned($vars['email'])) {
                $errors['err']='Ticket denied. Error #403';
                $errors['errno'] = 403;
                $ost->logWarning('Ticket denied', 'Banned email - '.$vars['email']);
                return 0;
            }

            //Make sure the open ticket limit hasn't been reached. (LOOP CONTROL)
            if($cfg->getMaxOpenTickets()>0 && strcasecmp($origin,'staff') 
                    && ($client=Client::lookupByEmail($vars['email']))
                    && ($openTickets=$client->getNumOpenTickets())
                    && ($openTickets>=$cfg->getMaxOpenTickets()) ) {

                $errors['err']="You've reached the maximum open tickets allowed.";
                $ost->logWarning('Ticket denied -'.$vars['email'], 
                        sprintf('Max open tickets (%d) reached for %s ', 
                            $cfg->getMaxOpenTickets(), $vars['email']));

                return 0;
            }
        }

        //Init ticket filters...
        $ticket_filter = new TicketFilter($origin, $vars);
        // Make sure email contents should not be rejected
        if($ticket_filter 
                && ($filter=$ticket_filter->shouldReject())) {
            $errors['err']='Ticket denied. Error #403';
            $errors['errno'] = 403;
            $ost->logWarning('Ticket denied', 
                    sprintf('Ticket rejected ( %s) by filter "%s"', 
                        $vars['email'], $filter->getName()));

            return 0;
        }

        $id=0;
        $fields=array();
        $fields['name']     = array('type'=>'string',   'required'=>1, 'error'=>'Name required');
        $fields['email']    = array('type'=>'email',    'required'=>1, 'error'=>'Valid email required');
        $fields['subject']  = array('type'=>'string',   'required'=>1, 'error'=>'Subject required');
        $fields['message']  = array('type'=>'text',     'required'=>1, 'error'=>'Message required');
        switch (strtolower($origin)) {
            case 'web':
                $fields['topicId']  = array('type'=>'int',  'required'=>1, 'error'=>'Select help topic');
                break;
            case 'staff':
                $fields['deptId']   = array('type'=>'int',  'required'=>1, 'error'=>'Dept. required');
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
        $fields['priorityId']   = array('type'=>'int',      'required'=>0, 'error'=>'Invalid Priority');
        $fields['phone']        = array('type'=>'phone',    'required'=>0, 'error'=>'Valid phone # required');
        
        if(!Validator::process($fields, $vars, $errors) && !$errors['err'])
            $errors['err'] ='Missing or invalid data - check the errors and try again';

        //Make sure phone extension is valid
        if($vars['phone_ext'] ) {
            if(!is_numeric($vars['phone_ext']) && !$errors['phone'])
                $errors['phone']='Invalid phone ext.';
            elseif(!$vars['phone']) //make sure they just didn't enter ext without phone # XXX: reconsider allowing!
                $errors['phone']='Phone number required';
        }

        //Make sure the due date is valid
        if($vars['duedate']) {
            if(!$vars['time'] || strpos($vars['time'],':')===false)
                $errors['time']='Select time';
            elseif(strtotime($vars['duedate'].' '.$vars['time'])===false)
                $errors['duedate']='Invalid duedate';
            elseif(strtotime($vars['duedate'].' '.$vars['time'])<=time())
                $errors['duedate']='Due date must be in the future';
        }

        //Any error above is fatal.
        if($errors)  return 0;

        # Perform ticket filter actions on the new ticket arguments
        if ($ticket_filter) $ticket_filter->apply($vars);

        # Some things will need to be unpacked back into the scope of this
        # function
        if (isset($vars['autorespond'])) $autorespond=$vars['autorespond'];

        // OK...just do it.
        $deptId=$vars['deptId']; //pre-selected Dept if any.
        $priorityId=$vars['priorityId'];
        $source=ucfirst($vars['source']);
        $topic=NULL;
        // Intenal mapping magic...see if we need to overwrite anything
        if(isset($vars['topicId']) && ($topic=Topic::lookup($vars['topicId']))) { //Ticket created via web by user/or staff
            $deptId=$deptId?$deptId:$topic->getDeptId();
            $priorityId=$priorityId?$priorityId:$topic->getPriorityId();
            if($autorespond) $autorespond=$topic->autoRespond();
            $source=$vars['source']?$vars['source']:'Web';

            //Auto assignment.
            if (!isset($vars['staffId']) && $topic->getStaffId())
                $vars['staffId'] = $topic->getStaffId();
            elseif (!isset($vars['teamId']) && $topic->getTeamId())
                $vars['teamId'] = $topic->getTeamId();

            //set default sla.
            if(!isset($vars['slaId']) && $topic->getSLAId())
                $vars['slaId'] = $topic->getSLAId();

        }elseif($vars['emailId'] && !$vars['deptId'] && ($email=Email::lookup($vars['emailId']))) { //Emailed Tickets
            $deptId=$email->getDeptId();
            $priorityId=$priorityId?$priorityId:$email->getPriorityId();
            if($autorespond) $autorespond=$email->autoRespond();
            $email=null;
            $source='Email';
        }
        //Last minute checks
        $priorityId=$priorityId?$priorityId:$cfg->getDefaultPriorityId();
        $deptId=$deptId?$deptId:$cfg->getDefaultDeptId();
        $topicId=$vars['topicId']?$vars['topicId']:0;
        $ipaddress=$vars['ip']?$vars['ip']:$_SERVER['REMOTE_ADDR'];
        
        //We are ready son...hold on to the rails.
        $extId=Ticket::genExtRandID();
        $sql='INSERT INTO '.TICKET_TABLE.' SET created=NOW() '
            .' ,lastmessage= NOW()'
            .' ,ticketID='.db_input($extId)
            .' ,dept_id='.db_input($deptId)
            .' ,topic_id='.db_input($topicId)
            .' ,priority_id='.db_input($priorityId)
            .' ,email='.db_input($vars['email'])
            .' ,name='.db_input(Format::striptags($vars['name']))
            .' ,subject='.db_input(Format::striptags($vars['subject']))
            .' ,phone="'.db_input($vars['phone'],false).'"'
            .' ,phone_ext='.db_input($vars['phone_ext']?$vars['phone_ext']:'')
            .' ,ip_address='.db_input($ipaddress) 
            .' ,source='.db_input($source);

        //Make sure the origin is staff - avoid firebug hack!
        if($vars['duedate'] && !strcasecmp($origin,'staff'))
             $sql.=' ,duedate='.db_input(date('Y-m-d G:i',Misc::dbtime($vars['duedate'].' '.$vars['time'])));


        if(!db_query($sql) || !($id=db_insert_id()) || !($ticket =Ticket::lookup($id)))
            return null;

        /* -------------------- POST CREATE ------------------------ */
        
        if(!$cfg->useRandomIds()){
            //Sequential ticketIDs support really..really suck arse.
            $extId=$id; //To make things really easy we are going to use autoincrement ticket_id.
            db_query('UPDATE '.TICKET_TABLE.' SET ticketID='.db_input($extId).' WHERE ticket_id='.$id.' LIMIT 1'); 
            //TODO: RETHING what happens if this fails?? [At the moment on failure random ID is used...making stuff usable]
        }

        $dept = $ticket->getDept();
        //post the message.
        $msgid=$ticket->postMessage($vars['message'],$source,$vars['mid'],$vars['header'],true);

        // Configure service-level-agreement for this ticket
        $ticket->selectSLAId($vars['slaId']);

        //Auto assign staff or team - auto assignment based on filter rules.
        if($vars['staffId'] && !$vars['assignId'])
             $ticket->assignToStaff($vars['staffId'], 'Auto Assignment');
        if($vars['teamId'] && !$vars['assignId'])
            $ticket->assignToTeam($vars['teamId'], 'Auto Assignment');

        /**********   double check auto-response  ************/
        //Overwrite auto responder if the FROM email is one of the internal emails...loop control.
        if($autorespond && (Email::getIdByEmail($ticket->getEmail())))
            $autorespond=false;

        # Messages that are clearly auto-responses from email systems should
        # not have a return 'ping' message
        if ($autorespond && $vars['header'] &&
                TicketFilter::isAutoResponse(Mail_Parse::splitHeaders($vars['header']))) {
            $autorespond=false;
        }

        //Don't auto respond to mailer daemons.
        if( $autorespond &&
            (strpos(strtolower($vars['email']),'mailer-daemon@')!==false
             || strpos(strtolower($vars['email']),'postmaster@')!==false)) {
            $autorespond=false;
        }

        //post canned auto-response IF any (disables new ticket auto-response).
        if ($vars['cannedResponseId']
            && $ticket->postCannedReply($vars['cannedResponseId'], $msgid, $autorespond)) {
                $ticket->markUnAnswered(); //Leave the ticket as unanswred. 
                $autorespond = false;
        }

        //Check department's auto response settings
        // XXX: Dept. setting doesn't affect canned responses.
        if($autorespond && $dept && !$dept->autoRespONNewTicket())
            $autorespond=false;

        /***** See if we need to send some alerts ****/
        $ticket->onNewTicket($vars['message'], $autorespond, $alertstaff);

        /************ check if the user JUST reached the max. open tickets limit **********/
        if($cfg->getMaxOpenTickets()>0
                    && ($client=$ticket->getClient())
                    && ($client->getNumOpenTickets()==$cfg->getMaxOpenTickets())) {
            $ticket->onOpenLimit(($autorespond && strcasecmp($origin, 'staff')));
        }
        
        /* Start tracking ticket lifecycle events */
        $ticket->logEvent('created');

        /* Phew! ... time for tea (KETEPA) */

        return $ticket;
    }

    function open($vars, &$errors) {
        global $thisstaff,$cfg;

        if(!$thisstaff || !$thisstaff->canCreateTickets()) return false;
        
        if(!$vars['issue'])
            $errors['issue']='Summary of the issue required';
        else
            $vars['message']=$vars['issue'];

        if($vars['source'] && !in_array(strtolower($vars['source']),array('email','phone','other')))
            $errors['source']='Invalid source - '.Format::htmlchars($vars['source']);

        if(!($ticket=Ticket::create($vars, $errors, 'staff', false, (!$vars['assignId']))))
            return false;

        $vars['msgId']=$ticket->getLastMsgId();
        $respId = 0;
        
        // post response - if any
        if($vars['response']) {
            $vars['response'] = $ticket->replaceVars($vars['response']);
            if(($respId=$ticket->postReply($vars, $errors, false))) {
                //Only state supported is closed on response
                if(isset($vars['ticket_state']) && $thisstaff->canCloseTickets())
                    $ticket->setState($vars['ticket_state']);
            }
        }
        //Post Internal note
        if($vars['assignId'] && $thisstaff->canAssignTickets()) { //Assign ticket to staff or team.
            $ticket->assign($vars['assignId'], $vars['note']);
        } elseif($vars['note']) { //Not assigned...save optional note if any
            $ticket->logNote('New Ticket', $vars['note'], $thisstaff, false);
        } else { //Not assignment and no internal note - log activity
            $ticket->logActivity('New Ticket by Staff','Ticket created by staff -'.$thisstaff->getName());
        }

        $ticket->reload();
        
        if(!$cfg->notifyONNewStaffTicket() || !isset($vars['alertuser']))
            return $ticket; //No alerts.

        //Send Notice to user --- if requested AND enabled!!
                
        $dept=$ticket->getDept();
        if(!$dept || !($tpl=$dept->getTemplate()))
            $tpl=$cfg->getDefaultTemplate();
                                
        if(!$dept || !($email=$dept->getEmail()))
            $email =$cfg->getDefaultEmail();

        if($tpl && ($msg=$tpl->getNewTicketNoticeMsgTemplate()) && $email) {
                        
            $message = $vars['issue'];
            if($vars['response'])
                $message.="\n\n".$vars['response'];

            if($vars['signature']=='mine')
                $signature=$thisstaff->getSignature();
            elseif($vars['signature']=='dept' && $dept && $dept->isPublic())
                $signature=$dept->getSignature();
            else
                $signature='';
            
            $msg = $ticket->replaceVars($msg, 
                    array('message' => $message, 'signature' => $signature));

            if($cfg->stripQuotedReply() && ($tag=trim($cfg->getReplySeparator())))
                $msg['body'] ="\n$tag\n\n".$msg['body'];

            $attachments =($cfg->emailAttachments() && $respId)?$ticket->getAttachments($respId,'R'):array();
            $email->send($ticket->getEmail(), $msg['subj'], $msg['body'], $attachments);
        }

        return $ticket;
    
    }
   
    function checkOverdue() {
       
        $sql='SELECT ticket_id FROM '.TICKET_TABLE.' T1 '
            .' INNER JOIN '.SLA_TABLE.' T2 ON (T1.sla_id=T2.id AND T2.isactive=1) '
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
