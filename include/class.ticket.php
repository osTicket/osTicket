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
include_once(INCLUDE_DIR.'class.priority.php');
include_once(INCLUDE_DIR.'class.sla.php');

class Ticket{

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
       
        $sql='SELECT  ticket.*, topic.topic as helptopic, lock_id, dept_name, priority_desc '
            .' ,count(attach.attach_id) as attachments '
            .' ,count(DISTINCT message.msg_id) as messages '
            .' ,count(DISTINCT response.response_id) as responses '
            .' ,count(DISTINCT note.note_id) as notes '
            .' FROM '.TICKET_TABLE.' ticket '
            .' LEFT JOIN '.DEPT_TABLE.' dept ON (ticket.dept_id=dept.dept_id) '
            .' LEFT JOIN '.TICKET_PRIORITY_TABLE.' pri ON (ticket.priority_id=pri.priority_id) '
            .' LEFT JOIN '.TOPIC_TABLE.' topic ON (ticket.topic_id=topic.topic_id) '
            .' LEFT JOIN '.TICKET_LOCK_TABLE.' tlock ON (ticket.ticket_id=tlock.ticket_id AND tlock.expire>NOW()) '
            .' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach ON (ticket.ticket_id=attach.ticket_id) '
            .' LEFT JOIN '.TICKET_MESSAGE_TABLE.' message ON (ticket.ticket_id=message.ticket_id) '
            .' LEFT JOIN '.TICKET_RESPONSE_TABLE.' response ON (ticket.ticket_id=response.ticket_id) '
            .' LEFT JOIN '.TICKET_NOTE_TABLE.' note ON (ticket.ticket_id=note.ticket_id ) '
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
        $this->helptopic = $this->ht['helptopic'];
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

        return ($cfg && $cfg->showRelatedTickets() && $client->getTicketId()==$ticket->getExtId());
    }

    //Getters
    function getId(){
        return  $this->id;
    }

    function getExtId(){
        return  $this->extid;
    }
   
    function getEmail(){
        return $this->email;
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
            
        return $this->helptopic;
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

    function getTopicId(){
        return $this->topic_id;
    }

    function getTopic() { 

        if(!$this->topic && $this->getTopicId())
            $this->topic = Topic::lookup($this->getTopicId);

        return $this->topic;
    }

 
    function getSLAId() {
        return $this->sla_id;
    }

    function getSLA() {

        if(!$this->sla && $this->getSLAId())
            $this->sla = SLA::lookup($this->getSLAId);

        return $this->sla;
    }

    function getLastRespondent() {

        $sql ='SELECT  resp.staff_id '
             .' FROM '.TICKET_RESPONSE_TABLE.' resp '
             .' LEFT JOIN '.STAFF_TABLE. ' USING(staff_id) '
             .' WHERE  resp.ticket_id='.db_input($this->getId()).' AND resp.staff_id>0 '
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
        $sql='SELECT created FROM '.TICKET_MESSAGE_TABLE
            .' WHERE ticket_id='.db_input($this->getId())
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

        $sql='SELECT created FROM '.TICKET_RESPONSE_TABLE
            .' WHERE ticket_id='.db_input($this->getId())
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

    function getNotes($order='') {

        if(!$order || !in_array($order, array('DESC','ASC')))
            $order='DESC';

        $sql ='SELECT note.*, count(DISTINCT attach.attach_id) as attachments '
            .' FROM '.TICKET_NOTE_TABLE.' note '
            .' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach
                ON (note.ticket_id=attach.ticket_id AND note.note_id=attach.ref_id AND ref_type="N") '
            .' WHERE note.ticket_id='.db_input($this->getId())
            .' GROUP BY note.note_id '
            .' ORDER BY note.created '.$order;

        $notes=array();
        if(($res=db_query($sql)) && db_num_rows($res))
            while($rec=db_fetch_array($res))
                $notes[]=$rec;

        return $notes;
    }

    function getMessages() {

        $sql='SELECT msg.msg_id, msg.created, msg.message '
            .' ,count(DISTINCT attach.attach_id) as attachments, count( DISTINCT resp.response_id) as responses '
            .' FROM '.TICKET_MESSAGE_TABLE.' msg '
            .' LEFT JOIN '.TICKET_RESPONSE_TABLE. ' resp ON(resp.msg_id=msg.msg_id) '
            .' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach 
                ON (msg.ticket_id=attach.ticket_id AND msg.msg_id=attach.ref_id AND ref_type="M") '
            .' WHERE  msg.ticket_id='.db_input($this->getId())
            .' GROUP BY msg.msg_id '
            .' ORDER BY msg.created ASC ';

        $messages=array();
        if(($res=db_query($sql)) && db_num_rows($res))
            while($rec=db_fetch_array($res))
                $messages[] = $rec;
                
        return $messages;
    }

    function getResponses($msgId) {

        $sql='SELECT resp.*, count(DISTINCT attach.attach_id) as attachments '
            .' FROM '.TICKET_RESPONSE_TABLE. ' resp '
            .' LEFT JOIN '.TICKET_ATTACHMENT_TABLE.' attach 
                ON (resp.ticket_id=attach.ticket_id AND resp.response_id=attach.ref_id AND ref_type="R") '
            .' WHERE  resp.ticket_id='.db_input($this->getId())
            .' GROUP BY resp.response_id '
            .' ORDER BY resp.created';

        $responses=array();
        if(($res=db_query($sql)) && db_num_rows($res))
            while($rec= db_fetch_array($res))
                $responses[] = $rec;
                
        return $responses;
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
                $size=sprintf('(<i>%s</i>)',Format::file_size($attachment['size']));
                
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

        return (db_query($sql) && db_affected_rows($res));
    }

    //DeptId can NOT be 0. No orphans please!
    function setDeptId($deptId){
        
        //Make sure it's a valid department//
        if(!($dept=Dept::lookup($deptId)))
            return false;

      
        $sql='UPDATE '.TICKET_TABLE.' SET updated=NOW(), dept_id='.db_input($deptId)
            .' WHERE ticket_id='.db_input($this->getId());

        return (db_query($sql) && db_affected_rows());
    }
 
    //Set staff ID...assign/unassign/release (id can be 0)
    function setStaffId($staffId){
       
      $sql='UPDATE '.TICKET_TABLE.' SET updated=NOW(), staff_id='.db_input($staffId)
          .' WHERE ticket_id='.db_input($this->getId());

      return (db_query($sql)  && db_affected_rows());
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
        } elseif ($this->getDept()->getSLAId()) {
            $slaId = $this->getDept()->getSLAId();
        } elseif ($this->getTopicId() && $this->getTopic()) {
            $slaId = $this->getTopic()->getSLAId();
        } else {
            $slaId = $cfg->getDefaultSLAId();
        }
        return ($slaId && $this->setSLAId($slaId)) ? $slaId : false;
    }

    //Set team ID...assign/unassign/release (id can be 0)
    function setTeamId($teamId){

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
        
        $sql='UPDATE '.TICKET_TABLE.' SET closed=NOW(), isoverdue=0, duedate=NULL, updated=NOW(), status='.db_input('closed');
        
        if($thisstaff) //Give the closing  staff credit. 
            $sql.=', staff_id='.db_input($thisstaff->getId());

        $sql.=' WHERE ticket_id='.db_input($this->getId());

        $this->track('closed');
        return (db_query($sql) && db_affected_rows());
    }

    //set status to open on a closed ticket.
    function reopen($isanswered=0){

        $sql='UPDATE '.TICKET_TABLE.' SET updated=NOW(), reopened=NOW() '
            .' ,status='.db_input('open')
            .' ,isanswered='.db_input($isanswered)
            .' WHERE ticket_id='.db_input($this->getId());

        //TODO: log reopen event here 

        $this->track('reopened');
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
              
            $body=$this->replaceTemplateVars($msg['body']);
            $subj=$this->replaceTemplateVars($msg['subj']);
            $body = str_replace('%message', $message, $body);
            $body = str_replace('%signature',($dept && $dept->isPublic())?$dept->getSignature():'',$body);
            
            if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()))
                $body ="\n$tag\n\n".$body;
            
            //TODO: add auto flags....be nice to mail servers and sysadmins!!
            $email->send($this->getEmail(),$subj,$body);
        }
        
        if(!($email=$cfg->getAlertEmail()))
            $email =$cfg->getDefaultEmail();
          
        //Send alert to out sleepy & idle staff.
        if($alertstaff && $email
                && $cfg->alertONNewTicket() 
                && ($msg=$tpl->getNewTicketAlertMsgTemplate())) {
              
            $body=$this->replaceTemplateVars($msg['body']);
            $subj=$this->replaceTemplateVars($msg['subj']);
            $body = str_replace('%message', $message, $body);
            
            $recipients=$sentlist=array();
            
            //Alert admin??
            if($cfg->alertAdminONNewTicket()) {
                $alert = str_replace("%staff",'Admin',$body);
                $email->send($cfg->getAdminEmail(),$subj,$alert);
                $sentlist[]=$cfg->getAdminEmail();
            }
              
            //Only alerts dept members if the ticket is NOT assigned.
            if($cfg->alertDeptMembersONNewTicket() && !$this->isAssigned()) {
                if(($members=$dept->getAvailableMembers()))
                    $recipients=array_merge($recipients, $members);
            }
            
            if($cfg->alertDeptManagerONNewTicket() && $dept && ($manager=$dept->getManager()))
                $recipients[]= $manager;
               
            foreach( $recipients as $k=>$staff){
                if(!is_object($staff) || !$staff->isAvailable() || in_array($staff->getEmail(),$sentlist)) continue;
                $alert = str_replace("%staff",$staff->getFirstName(),$body);
                $email->send($staff->getEmail(),$subj,$alert);
            }
           
           
        }
        
        return true;
    }

    function onOpenLimit($sendNotice=true) {
        global $cfg;

        //Log the limit notice as a warning for admin.
        $msg=sprintf('Max open tickets (%d) reached  for %s ', $cfg->getMaxOpenTickets(), $this->getEmail());
        sys::log(LOG_WARNING, 'Max. Open Tickets Limit ('.$this->getEmail().')', $msg);

        if(!$sendNotice || !$cfg->sendOverlimitNotice()) return true;

        //Send notice to user.
        $dept = $this->getDept();
                    
        if(!$dept || !($tpl=$dept->getTemplate()))
            $tpl=$cfg->getDefaultTemplate();
            
        if(!$dept || !($email=$dept->getAutoRespEmail()))
            $email=$cfg->getDefaultEmail();

        if($tpl && ($msg=$tpl->getOverlimitMsgTemplate()) && $email) {
            $body=$this->replaceTemplateVars($msg['body']);
            $subj=$this->replaceTemplateVars($msg['subj']);
            $body = str_replace('%signature',($dept && $dept->isPublic())?$dept->getSignature():'',$body);
            $email->send($this->getEmail(), $subj, $body);
        }

        $client= $this->getClient();
        
        //Alert admin...this might be spammy (no option to disable)...but it is helpful..I think.
        $msg='Max. open tickets reached for '.$this->getEmail()."\n"
            .'Open ticket: '.$client->getNumOpenTickets()."\n"
            .'Max Allowed: '.$cfg->getMaxOpenTickets()."\n\nNotice sent to the user.";
            
        Sys::alertAdmin('Overlimit Notice',$msg);
       
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


        if(!$autorespond && !$cfg->autoRespONNewMessage()) return;  //no autoresp or alerts.

        $this->reload();


        if(!$dept && !($tpl = $dept->getTemplate()))
            $tpl= $cfg->getDefaultTemplate();
       
        //If enabled...send confirmation to user. ( New Message AutoResponse)
        if($tpl && ($msg=$tpl->getNewMessageAutorepMsgTemplate())) {
                        
            $body=$this->replaceTemplateVars($msg['body']);
            $subj=$this->replaceTemplateVars($msg['subj']);
            $body = str_replace('%signature',($dept && $dept->isPublic())?$dept->getSignature():'',$body);

            //Reply separator tag.
            if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()))
                $body ="\n$tag\n\n".$body;
            
            if(!$dept || !($email=$dept->getAutoRespEmail()))
                $email=$cfg->getDefaultEmail();
            
            if($email) {
                $email->send($this->getEMail(),$subj,$body);
            }
        }

    }

    function onAssign($note, $alert=true) {
        global $cfg;

        if($this->isClosed()) $this->reopen(); //Assigned tickets must be open - otherwise why assign?

        $this->reload();

        //Log an internal note - no alerts on the internal note.
        $note=$note?$note:'Ticket assignment';
        $this->postNote('Ticket Assigned to '.$this->getAssignee(),$note,false);

        //See if we need to send alerts
        if(!$alert || !$cfg->alertONAssignment()) return true; //No alerts!

        $dept = $this->getDept();

        //Get template.
        if(!$dept && !($tpl = $dept->getTemplate()))
            $tpl= $cfg->getDefaultTemplate();

        //Email to use!
        if(!($email=$cfg->getAlertEmail()))
            $email =$cfg->getDefaultEmail();

        //Get the message template
        if($tpl && ($msg=$tpl->getAssignedAlertMsgTemplate()) && $email) {

            $body=$this->replaceTemplateVars($msg['body']);
            $subj=$this->replaceTemplateVars($msg['subj']);
            $body = str_replace('%note', $note, $body);
            $body = str_replace('%message', $note, $body); //Previous versions used message.
            $body = str_replace('%assignee', $this->getAssignee(), $body);
            $body = str_replace('%assigner', ($thisstaff)?$thisstaff->getName():'System',$body);
            //recipients
            $recipients=array();
            //Assigned staff or team... if any
            // Assigning a ticket to a team when already assigned to staff disables alerts to the team (!))
            if($cfg->alertStaffONAssign() && $this->getStaffId())
                $recipients[]=$this->getStaff();
            elseif($this->getTeamId() && ($team=$this->getTeam())) {
                if($cfg->alertTeamMembersOnAssignment() && ($members=$team->getMembers()))
                    $recipients+=$members;
                elseif($cfg->alertTeamLeadOnAssignment() && ($lead=$team->getTeamLead()))
                    $recipients[]=$lead;
            }
            //Send the alerts.
            $sentlist=array();
            foreach( $recipients as $k=>$staff){
                if(!is_object($staff) || !$staff->isAvailable() || in_array($staff->getEmail(),$sentlist)) continue;
                $alert = str_replace('%staff', $staff->getFirstName(), $body);
                $email->send($staff->getEmail(), $subj, $alert);
            }
            print_r($sentlist);
        }

        return true;
    }

    function onOverdue($whine=true) {
        global $cfg;

        if($whine && ($sla=$this->getSLA()) && !$sla->alertOnOverdue())
            $whine = false;

        //check if we need to send alerts.
        if(!$whine || !$cfg->alertONOverdueTicket())
            return true;

        //Get template.
        if(!($tpl = $dept->getTemplate()))
            $tpl= $cfg->getDefaultTemplate();

        //Email to use!
        if(!($email=$cfg->getAlertEmail()))
            $email =$cfg->getDefaultEmail();

        //Get the message template
        if($tpl && ($msg=$tpl->getOverdueAlertMsgTemplate()) && $email) {

            $body=$this->replaceTemplateVars($msg['body']);
            $subj=$this->replaceTemplateVars($msg['subj']);
            $body = str_replace('%comments', $comments, $body); //Planned support.

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
                if(($members=$dept->getAvailableMembers()))
                    $recipients=array_merge($recipients, $members);
            }
            //Always alert dept manager??
            if($cfg->alertDeptManagerONOverdueTicket() && $dept && ($manager=$dept->getManager()))
                $recipients[]= $manager;

            $sentlist=array();
            foreach( $recipients as $k=>$staff){
                if(!is_object($staff) || !$staff->isAvailable() || in_array($staff->getEmail(),$sentlist)) continue;
                $alert = str_replace("%staff",$staff->getFirstName(),$body);
                $email->send($staff->getEmail(),$subj,$alert);
            }

        }

        return true;
    }

    //Replace base variables.
    function replaceTemplateVars($text){
        global $cfg;

        $dept = $this->getDept();
        $staff= $this->getStaff();
        $team = $this->getTeam();

        //TODO: add new vars (team, sla...etc)


        $search = array('/%id/','/%ticket/','/%email/','/%name/','/%subject/','/%topic/','/%phone/','/%status/','/%priority/',
                        '/%dept/','/%assigned_staff/','/%createdate/','/%duedate/','/%closedate/','/%url/');
        $replace = array($this->getId(),
                         $this->getExtId(),
                         $this->getEmail(),
                         $this->getName(),
                         $this->getSubject(),
                         $this->getHelpTopic(),
                         $this->getPhoneNumber(),
                         $this->getStatus(),
                         $this->getPriority(),
                         ($dept?$dept->getName():''),
                         ($staff?$staff->getName():''),
                         Format::db_daydatetime($this->getCreateDate()),
                         Format::db_daydatetime($this->getDueDate()),
                         Format::db_daydatetime($this->getCloseDate()),
                         $cfg->getBaseUrl());
        return preg_replace($search,$replace,$text);
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

        $this->onOverdue($whine);
        $this->track('overdue');

        return true;
    }

    //Dept Tranfer...with alert.. done by staff 
    function transfer($deptId, $comments, $alert = true) {
        global $cfg, $thisstaff;
      
        if(!$this->setDeptId($deptId))
            return false;
         
        // Change to SLA of the new department
        $this->selectSLAId();
         $currentDept = $this->getDeptName(); //XXX: add to olddept to tpl vars??

         // Reopen ticket if closed 
         if($this->isClosed())
             $this->reopen();
        
         $this->reload(); //reload - new dept!!

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
            
             $body=$this->replaceTemplateVars($msg['body']);
             $subj=$this->replaceTemplateVars($msg['subj']);
             $body = str_replace('%note', $comments, $body);
                        
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
                if(($members=$dept->getAvailableMembers()))
                    $recipients+=$members;
            }

            //Always alert dept manager??
            if($cfg->alertDeptManagerONTransfer() && $dept && ($manager=$dept->getManager()))
                $recipients[]= $manager;
             
            $sentlist=array();
            foreach( $recipients as $k=>$staff){
                if(!is_object($staff) || !$staff->isAvailable() || in_array($staff->getEmail(),$sentlist)) continue;
                $alert = str_replace("%staff",$staff->getFirstName(),$body);
                $email->send($staff->getEmail(),$subj,$alert);
            }
         }

         $this->track('transferred');
         return true;
    }

    function assignToStaff($staff, $note, $alert=true) {

        if(!is_object($staff) && !($staff=Staff::lookup($staff)))
            return false;
        
        if(!$this->setStaffId($staff->getId()))
            return false;

        $this->onAssign($note, $alert);

        $this->track('assigned');
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

        $this->onAssign($note, $alert);

        $this->track('assigned');
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
            $alert=($thisstaff && $thisstaff->getId()==$id)?false:$alert; //No alerts on self assigned tickets!!!
            //We don't care if a team is already assigned to the ticket - staff assignment takes precedence
            $rv=$this->assignToStaff($id, $note, $alert);
        }

        return $rv;
    }
    
    //unassign primary assignee
    function unassign() {

        if(!$this->isAssigned()) //We can't release what is not assigned buddy!
            return true;

        //We're unassigning in the order of precedence.
        if($this->getStaffId())
            return $this->setStaffId(0);
        elseif($this->getTeamId())
            return $this->setTeamId(0);

        return false;
    }

    function release() {
        return $this->unassign();
    }

    //Insert message from client
    function postMessage($msg,$source='',$msgid=NULL,$headers='',$newticket=false){
        global $cfg;
       
        if(!$this->getId()) return 0;
        
        # XXX: Refuse auto-response messages? (via email) XXX: No - but kill our auto-responder.

        $sql='INSERT INTO '.TICKET_MESSAGE_TABLE.' SET created=NOW() '
            .' ,ticket_id='.db_input($this->getId())
            .' ,messageId='.db_input($msgid)
            .' ,message='.db_input(Format::striptags($msg)) //Tags/code stripped...meaning client can not send in code..etc
            .' ,headers='.db_input($headers) //Raw header.
            .' ,source='.db_input($source?$source:$_SERVER['REMOTE_ADDR'])
            .' ,ip_address='.db_input($_SERVER['REMOTE_ADDR']);
    
        if(!db_query($sql) || !($msgid=db_insert_id())) return 0; //bail out....

        $this->setLastMsgId($msgid);

        if($newticket) return $msgid; //Our work is done...

        $autorespond = true;
        if ($autorespond && $headers && EmailFilter::isAutoResponse(Mail_Parse::splitHeaders($headers)))
            $autorespond=false;

        $this->onMessage($autorespond); //must be called b4 sending alerts to staff.

        $dept = $this->getDept();

        if(!$dept || !($tpl = $dept->getTemplate()))
            $tpl= $cfg->getDefaultTemplate();

        if(!($email=$cfg->getAlertEmail()))
            $email =$cfg->getDefaultEmail();


        //If enabled...send alert to staff (New Message Alert)
        if($cfg->alertONNewMessage() && $tpl && $email && ($msg=$tpl->getNewMessageAlertMsgTemplate())) {

            $body=$this->replaceTemplateVars($msg['body']);
            $subj=$this->replaceTemplateVars($msg['subj']);
            $body = str_replace("%message", $msg,$body);

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
                if(!$staff || !$staff->getEmail() || !$staff->isAvailable() && in_array($staff->getEmail(),$sentlist)) continue;
                $alert = str_replace("%staff",$staff->getFirstName(),$body);
                $email->send($staff->getEmail(),$subj,$alert);
                $sentlist[]=$staff->getEmail();
            }
        }
        
        return $msgid;
    }

    /* public */ 
    function postReply($vars, $files, $errors, $alert = true) {
        global $thisstaff,$cfg;

        if(!$thisstaff || !$thisstaff->isStaff() || !$cfg) return 0;

        if(!$vars['msgId'])
            $errors['msgId'] ='Missing messageId - internal error';
        if(!$vars['response'])
            $errors['response'] = 'Resonse message required';

        if($errors) return 0;

        $sql='INSERT INTO '.TICKET_RESPONSE_TABLE.' SET created=NOW() '
            .' ,ticket_id='.db_input($this->getId())
            .' ,msg_id='.db_input($vars['msgId'])
            .' ,response='.db_input(Format::striptags($vars['response']))
            .' ,staff_id='.db_input($thisstaff->getId())
            .' ,staff_name='.db_input($thisstaff->getName())
            .' ,ip_address='.db_input($thisstaff->getIP());

        if(!db_query($sql) || !($respId=db_insert_id()))
            return false;

        //Set status - if checked.
        if(isset($vars['reply_ticket_status']) && $vars['reply_ticket_status'])
            $this->setStatus($vars['reply_ticket_status']);

        /* We can NOT recover from attachment related failures at this point */
        //upload files.
        $attachments = $uploads = array();
        //Web based upload..
        if($files && is_array($files) && ($files=Format::files($files)))
            $attachments=array_merge($attachments,$files);

        //Canned attachments...
        if($vars['cannedattachments'] && is_array($vars['cannedattachments']))
            $attachments=array_merge($attachments,$vars['cannedattachments']);

        
        //Upload attachments -ids used on outgoing emails are returned.
        if($attachments)
            $uploads = $this->uploadAttachments($attachments, $respId,'R');

        $this->onResponse(); //do house cleaning..
        $this->reload();
        $dept = $this->getDept();

        /* email the user??  - if disabled - the bail out */
        if(!$alert) return $respId;

        if(!($tpl = $dept->getTemplate()))
            $tpl= $cfg->getDefaultTemplate();

        if(!($email=$cfg->getAlertEmail()))
            $email =$cfg->getDefaultEmail();

        if($tpl && ($msg=$tpl->getReplyMsgTemplate()) && $email) {
            $body=$this->replaceTemplateVars($msg['body']);
            $subj=$this->replaceTemplateVars($msg['subj']);
            $body = str_replace('%response',$vars['response'],$body);

            if($vars['signature']=='mine')
                $signature=$thisstaff->getSignature();
            elseif($vars['signature']=='dept' && $dept && $dept->isPublic())
                $signature=$dept->getSignature();
            else
                $signature='';

            $body = str_replace("%signature",$signature,$body);

            if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()))
                $body ="\n$tag\n\n".$body;

            //Set attachments if emailing.
            $attachments =($cfg->emailAttachments() && $uploads)?$this->getAttachments($respId,'R'):array();
            //TODO: setup  5 param (options... e.g mid trackable on replies)
            $email->send($this->getEmail(), $subj, $body, $attachments);
        }

        return $respId;
    }

    //Activity log - saved as internal notes WHEN enabled!!
    function logActivity($title,$note){
        global $cfg;

        if(!$cfg || !$cfg->logTicketActivity())
            return 0;

        return $this->postNote($title,$note,false,'system');
    }

    // History log -- used for statistics generation (pretty reports)
    function track($state, $staff=null) {
        global $thisstaff;

        if ($staff === null) {
            if ($thisstaff) $staff=$thisstaff->getUserName();
            else $staff='SYSTEM';               # XXX: Security Violation ?
        }

        return db_query('INSERT INTO '.TICKET_HISTORY_TABLE
            .' SET ticket_id='.db_input($this->getId())
            .', timestamp=NOW(), state='.db_input($state)
            .', staff='.db_input($staff))
            && db_affected_rows() == 1;
    }

    //Insert Internal Notes 
    function postNote($title,$note,$alert=true,$poster='') {        
        global $thisstaff,$cfg;

        $sql= 'INSERT INTO '.TICKET_NOTE_TABLE.' SET created=NOW() '.
                ',ticket_id='.db_input($this->getId()).
                ',title='.db_input(Format::striptags($title)).
                ',note='.db_input(Format::striptags($note)).
                ',staff_id='.db_input($thisstaff?$thisstaff->getId():0).
                ',source='.db_input(($poster || !$thisstaff)?$poster:$thisstaff->getName());
        //echo $sql;
        if(!db_query($sql) || !($id=db_insert_id()))
            return false;

        // If alerts are not enabled then return a success.
        if(!$alert || !$cfg->alertONNewNote() || !($dept=$this->getDept()))
            return $id;
        
        if(!($tpl = $dept->getTemplate()))
            $tpl= $cfg->getDefaultTemplate();

        if(!($email=$cfg->getAlertEmail()))
            $email =$cfg->getDefaultEmail();


        if($tpl && ($msg=$tpl->getNoteAlertMsgTemplate()) && $email) {
                    
            $body=$this->replaceTemplateVars($msg['body']);
            $subj=$this->replaceTemplateVars($msg['subj']);
            $body = str_replace('%note',"$title\n\n$note",$body);

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

            $sentlist=array();
            foreach( $recipients as $k=>$staff) {
                if(!$staff || !is_object($staff) || !$staff->getEmail() || !$staff->isAvailable()) continue;
                if(in_array($staff->getEmail(),$sentlist) || ($thisstaff && $thisstaff->getId()==$staff->getId())) continue; 
                $alert = str_replace('%staff',$staff->getFirstName(),$body);
                $email->send($staff->getEmail(),$subj,$alert);
                $sentlist[]=$staff->getEmail();
            }
        }
        
        return $id;
    }

    //online based attached files.
    function uploadAttachments($files, $refid, $type) {

        $uploaded=array();
        foreach($files as $file) {
            if(($fileId=is_numeric($file)?$file:AttachmentFile::upload($file)) && is_numeric($fileId))
                if($this->saveAttachment($fileId, $refid, $type))
                    $uploaded[]=$fileId;
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

        db_query('DELETE FROM '.TICKET_MESSAGE_TABLE.' WHERE ticket_id='.db_input($this->getId()));
        db_query('DELETE FROM '.TICKET_RESPONSE_TABLE.' WHERE ticket_id='.db_input($this->getId()));
        db_query('DELETE FROM '.TICKET_NOTE_TABLE.' WHERE ticket_id='.db_input($this->getId()));
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
        $fields['slaId']    = array('type'=>'int',      'required'=>1, 'error'=>'SLA required');
        $fields['priorityId'] = array('type'=>'int',    'required'=>1, 'error'=>'Priority required');
        $fields['phone']    = array('type'=>'phone',    'required'=>0, 'error'=>'Valid phone # required');
        $fields['duedate']  = array('type'=>'date',     'required'=>0, 'error'=>'Invalid date - must be MM/DD/YY');

        $fields['note']     = array('type'=>'text',     'required'=>1, 'error'=>'Reason for the update required');

        if(!Validator::process($fields, $vars, $errors) && !$errors['err'])
            $errors['err'] ='Missing or invalid data - check the errors and try again';

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

        $this->postNote('Ticket Updated', $vars['note']);
        $this->reload();
        
        return true;
    }

   
   /*============== Static functions. Use Ticket::function(params); ==================*/
    function getIdByExtId($extid) {
        $sql ='SELECT  ticket_id FROM '.TICKET_TABLE.' ticket WHERE ticketID='.db_input($extid);
        if(($res=db_query($sql)) && db_num_rows($res))
            list($id)=db_fetch_row($res);

        return $id;
    }


   
    function lookup($id) { //Assuming local ID is the only lookup used!
        return ($id && is_numeric($id) && ($ticket= new Ticket($id)) && $ticket->getId()==$id)?$ticket:null;    
    }

    function lookupByExtId($id) {
        return self::lookup(self:: getIdByExtId($id));
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
             ' LEFT JOIN '.TICKET_MESSAGE_TABLE.' msg USING(ticket_id) '.
             ' WHERE messageId='.db_input($mid).' AND email='.db_input($email);
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
        if(!$staff || (!is_object($staff) && !($staff=Staff::lookup($staff))) || !$staff->isStaff())
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
            $sql.=' OR ticket.team_id IN('.implode(',', array_filter($teams)).')';

        if(!$staff->showAssignedOnly()) //Staff with limited access just see Assigned tickets.
            $sql.=' OR ticket.dept_id IN('.implode(',',$staff->getDepts()).') ';

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
        global $cfg,$thisclient,$_FILES;

        //Check for 403
        if ($vars['email']  && Validator::is_email($vars['email'])) {

            //Make sure the email address is not banned
            if(EmailFilter::isBanned($vars['email'])) {
                $errors['err']='Ticket denied. Error #403';
                Sys::log(LOG_WARNING,'Ticket denied','Banned email - '.$vars['email']);
                return 0;
            }

            //Make sure the open ticket limit hasn't been reached. (LOOP CONTROL)
            if($cfg->getMaxOpenTickets()>0 && strcasecmp($origin,'staff') 
                    && ($client=Client::lookupByEmail($vars['email']))
                    && ($openTickets=$client->getNumOpenTickets())
                    && ($opentickets>=$cfg->getMaxOpenTickets()) ) {

                $errors['err']="You've reached the maximum open tickets allowed.";
                Sys::log(LOG_WARNING, 'Ticket denied -'.$vars['email'], 
                        sprintf('Max open tickets (%d) reached for %s ', $cfg->getMaxOpenTickets(), $vars['email']));

                return 0;
            }
        }
        // Make sure email contents should not be rejected
        if (($email_filter=new EmailFilter($vars))
                && ($filter=$email_filter->shouldReject())) {
            $errors['err']='Ticket denied. Error #403';
            Sys::log(LOG_WARNING,'Ticket denied',
                sprintf('Banned email - %s by filter "%s"', $vars['email'],
                    $filter->getName()));
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
        if($vars['duedate']){
            if(!$vars['time'] || strpos($vars['time'],':')===false)
                $errors['time']='Select time';
            elseif(strtotime($vars['duedate'].' '.$vars['time'])===false)
                $errors['duedate']='Invalid duedate';
            elseif(strtotime($vars['duedate'].' '.$vars['time'])<=time())
                $errors['duedate']='Due date must be in the future';
        }

        # Perform email filter actions on the new ticket arguments XXX: Move filter to the top and check for reject...
        if (!$errors && $email_filter) $email_filter->apply($vars);

        # Some things will need to be unpacked back into the scope of this
        # function
        if (isset($vars['autorespond'])) $autorespond=$vars['autorespond'];

        //Any error above is fatal.
        if($errors)  return 0;
        
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
        }elseif($vars['emailId'] && !$vars['deptId'] && ($email=Email::lookup($vars['emailId']))) { //Emailed Tickets
            $deptId=$email->getDeptId();
            $priorityId=$priorityId?$priorityId:$email->getPriorityId();
            if($autorespond) $autorespond=$email->autoRespond();
            $email=null;
            $source='Email';
        }elseif($vars['deptId']){ //Opened by staff.
            $deptId=$vars['deptId'];
            $source=ucfirst($vars['source']);
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
        $dept = $ticket->getDept();
     
        if(!$cfg->useRandomIds()){
            //Sequential ticketIDs support really..really suck arse.
            $extId=$id; //To make things really easy we are going to use autoincrement ticket_id.
            db_query('UPDATE '.TICKET_TABLE.' SET ticketID='.db_input($extId).' WHERE ticket_id='.$id.' LIMIT 1'); 
            //TODO: RETHING what happens if this fails?? [At the moment on failure random ID is used...making stuff usable]
        }   


        //post the message.
        $msgid=$ticket->postMessage($vars['message'],$source,$vars['mid'],$vars['header'],true);

        // Configure service-level-agreement for this ticket
        $ticket->selectSLAId($vars['slaId']);

        //Auto assign staff or team - auto assignment based on filter rules.
        if($vars['staffId'] && !$vars['assignId'])
             $ticket->assignToStaff($vars['staffId'],'auto-assignment');
        if($vars['teamId'] && !$vars['assignId'])
            $ticket->assignToTeam($vars['teamId'],'auto-assignment');

        /**********   double check auto-response  ************/
        //Overwrite auto responder if the FROM email is one of the internal emails...loop control.
        if($autorespond && (Email::getIdByEmail($ticket->getEmail())))
            $autorespond=false;

        if($autorespond && $dept && !$dept->autoRespONNewTicket())
            $autorespond=false;

        # Messages that are clearly auto-responses from email systems should
        # not have a return 'ping' message
        if ($autorespond && $vars['header'] &&
                EmailFilter::isAutoResponse(Mail_Parse::splitHeaders($vars['header']))) {
            $autorespond=false;
        }

        //Don't auto respond to mailer daemons.
        if( $autorespond &&
            (strpos(strtolower($vars['email']),'mailer-daemon@')!==false
             || strpos(strtolower($vars['email']),'postmaster@')!==false)) {
            $autorespond=false;
        }

        /***** See if we need to send some alerts ****/

        $ticket->onNewTicket($vars['message'], $autorespond, $alertstaff);

        /************ check if the user JUST reached the max. open tickets limit **********/
        if($cfg->getMaxOpenTickets()>0
                    && ($client=$ticket->getClient())
                    && ($client->getNumOpenTickets()==$cfg->getMaxOpenTickets())) {
            $ticket->onOpenLimit(($autorespond && strcasecmp($origin, 'staff')));
        }

        /* Phew! ... time for tea (KETEPA) */

        return $ticket;
    }

    function open($vars, $files, &$errors) {
        global $thisstaff,$cfg;

        if(!$thisstaff || !$thisstaff->canCreateTickets()) return false;
        
        if(!$vars['issue'])
            $errors['issue']='Summary of the issue required';
        else
            $vars['message']=$vars['issue'];

        if($var['source'] && !in_array(strtolower($var['source']),array('email','phone','other')))
            $errors['source']='Invalid source - '.Format::htmlchars($var['source']);

        if(!($ticket=Ticket::create($vars, $errors, 'staff', false, (!$vars['assignId']))))
            return false;

        $vars['msgId']=$ticket->getLastMsgId();
        $respId = 0;
        
        // post response - if any
        if($vars['response']) {
            $vars['response']=$ticket->replaceTemplateVars($vars['response']);
            if(($respId=$ticket->postReply($vars,  $files, $errors, false))) {
                //Only state supported is closed on response
                if(isset($vars['ticket_state']) && $thisstaff->canCloseTickets())
                    $ticket->setState($vars['ticket_state']);
            }
        }
        //Post Internal note
        if($var['assignId'] && $thisstaff->canAssignTickets()) { //Assign ticket to staff or team.
            $ticket->assign($vars['assignId'],$vars['note']);
        } elseif($vars['note']) { //Not assigned...save optional note if any
            $ticket->postNote('New Ticket',$vars['note'],false);
        } else { //Not assignment and no internal note - log activity
            $ticket->logActivity('New Ticket by Staff','Ticket created by staff -'.$thisstaff->getName());
        }

        $ticket->reload();
        
        if(!$cfg->notifyONNewStaffTicket() || !isset($var['alertuser']))
            return $ticket; //No alerts.

        //Send Notice to user --- if requested AND enabled!!
                
        $dept=$ticket->getDept();
        if(!$dept || !($tpl=$dept->getTemplate()))
            $tpl=$cfg->getDefaultTemplate();
                                
        if(!$dept || !($email=$dept->getEmail()))
            $email =$cfg->getDefaultEmail();

        if($tpl && ($msg=$tpl->getNewTicketNoticeMsgTemplate()) && $email) {
                        
            $message =$vars['issue']."\n\n".$vars['response'];
            $body=$ticket->replaceTemplateVars($msg['body']);
            $subj=$ticket->replaceTemplateVars($msg['subj']);
            $body = str_replace('%message',$message,$body);

            if($vars['signature']=='mine')
                $signature=$thisstaff->getSignature();
            elseif($vars['signature']=='dept' && $dept && $dept->isPublic())
                $signature=$dept->getSignature();
            else
                $signature='';

            $body = str_replace('%signature',$signature,$body);

            if($cfg->stripQuotedReply() && ($tag=trim($cfg->getReplySeparator())))
                $body ="\n$tag\n\n".$body;

            $attachments =($cfg->emailAttachments() && $respId)?$this->getAttachments($respId,'R'):array();
            $email->send($ticket->getEmail(), $subj, $body, $attachments);
        }

        return $ticket;
    
    }
   
    function checkOverdue() {
       
        $sql='SELECT ticket_id FROM '.TICKET_TABLE.' T1 '
            .' JOIN '.SLA_TABLE.' T2 ON (T1.sla_id=T2.id) '
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
