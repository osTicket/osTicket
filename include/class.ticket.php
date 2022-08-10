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
include_once(INCLUDE_DIR.'class.export.php');
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
require_once(INCLUDE_DIR.'class.task.php');
require_once(INCLUDE_DIR.'class.faq.php');

class Ticket extends VerySimpleModel
implements RestrictedAccess, Threadable, Searchable {
    static $meta = array(
        'table' => TICKET_TABLE,
        'pk' => array('ticket_id'),
        'select_related' => array('topic', 'staff', 'user', 'team', 'dept',
            'sla', 'thread', 'child_thread', 'user__default_email', 'status'),
        'joins' => array(
            'user' => array(
                'constraint' => array('user_id' => 'User.id'),
                'null' => true,
            ),
            'status' => array(
                'constraint' => array('status_id' => 'TicketStatus.id')
            ),
            'lock' => array(
                'constraint' => array('lock_id' => 'Lock.lock_id'),
                'null' => true,
            ),
            'dept' => array(
                'constraint' => array('dept_id' => 'Dept.id'),
                'null' => true,
            ),
            'sla' => array(
                'constraint' => array('sla_id' => 'Sla.id'),
                'null' => true,
            ),
            'staff' => array(
                'constraint' => array('staff_id' => 'Staff.staff_id'),
                'null' => true,
            ),
            'tasks' => array(
                'reverse' => 'Task.ticket',
            ),
            'team' => array(
                'constraint' => array('team_id' => 'Team.team_id'),
                'null' => true,
            ),
            'topic' => array(
                'constraint' => array('topic_id' => 'Topic.topic_id'),
                'null' => true,
            ),
            'thread' => array(
                'reverse' => 'TicketThread.ticket',
                'list' => false,
                'null' => true,
            ),
            'child_thread' => array(
                'constraint' => array(
                    'ticket_id'  => 'TicketThread.object_id',
                    "'C'" => 'TicketThread.object_type',
                ),
                'searchable' => false,
                'null' => true,
            ),
            'cdata' => array(
                'reverse' => 'TicketCData.ticket',
                'list' => false,
            ),
            'entries' => array(
                'constraint' => array(
                    "'T'" => 'DynamicFormEntry.object_type',
                    'ticket_id' => 'DynamicFormEntry.object_id',
                ),
                'list' => true,
            ),
        )
    );

    const PERM_CREATE   = 'ticket.create';
    const PERM_EDIT     = 'ticket.edit';
    const PERM_ASSIGN   = 'ticket.assign';
    const PERM_RELEASE  = 'ticket.release';
    const PERM_TRANSFER = 'ticket.transfer';
    const PERM_REFER    = 'ticket.refer';
    const PERM_MERGE    = 'ticket.merge';
    const PERM_LINK     = 'ticket.link';
    const PERM_REPLY    = 'ticket.reply';
    const PERM_MARKANSWERED = 'ticket.markanswered';
    const PERM_CLOSE    = 'ticket.close';
    const PERM_DELETE   = 'ticket.delete';

    const FLAG_COMBINE_THREADS     = 0x0001;
    const FLAG_SEPARATE_THREADS    = 0x0002;
    const FLAG_LINKED              = 0x0008;
    const FLAG_PARENT              = 0x0010;

    static protected $perms = array(
            self::PERM_CREATE => array(
                'title' =>
                /* @trans */ 'Create',
                'desc'  =>
                /* @trans */ 'Ability to open tickets on behalf of users'),
            self::PERM_EDIT => array(
                'title' =>
                /* @trans */ 'Edit',
                'desc'  =>
                /* @trans */ 'Ability to edit tickets'),
            self::PERM_ASSIGN => array(
                'title' =>
                /* @trans */ 'Assign',
                'desc'  =>
                /* @trans */ 'Ability to assign tickets to agents or teams'),
            self::PERM_RELEASE => array(
                'title' =>
                /* @trans */ 'Release',
                'desc'  =>
                /* @trans */ 'Ability to release ticket assignment'),
            self::PERM_TRANSFER => array(
                'title' =>
                /* @trans */ 'Transfer',
                'desc'  =>
                /* @trans */ 'Ability to transfer tickets between departments'),
            self::PERM_REFER => array(
                'title' =>
                /* @trans */ 'Refer',
                'desc'  =>
                /* @trans */ 'Ability to manage ticket referrals'),
            self::PERM_MERGE => array(
                'title' =>
                /* @trans */ 'Merge',
                'desc'  =>
                /* @trans */ 'Ability to merge tickets'),
            self::PERM_LINK => array(
                'title' =>
                /* @trans */ 'Link',
                'desc'  =>
                /* @trans */ 'Ability to link tickets'),
            self::PERM_REPLY => array(
                'title' =>
                /* @trans */ 'Post Reply',
                'desc'  =>
                /* @trans */ 'Ability to post a ticket reply'),
            self::PERM_MARKANSWERED => array(
                'title' =>
                /* @trans */ 'Mark as Answered',
                'desc'  =>
                /* @trans */ 'Ability to mark a ticket as Answered/Unanswered'),
            self::PERM_CLOSE => array(
                'title' =>
                /* @trans */ 'Close',
                'desc'  =>
                /* @trans */ 'Ability to close tickets'),
            self::PERM_DELETE => array(
                'title' =>
                /* @trans */ 'Delete',
                'desc'  =>
                /* @trans */ 'Ability to delete tickets'),
            );

    // Ticket Sources
    static protected $sources =  array(
            'Phone' =>
            /* @trans */ 'Phone',
            'Email' =>
            /* @trans */ 'Email',

            'Web' =>
            /* @trans */ 'Web',
            'API' =>
            /* @trans */ 'API',
            'Other' =>
            /* @trans */ 'Other',
            );

    var $lastMsgId;
    var $last_message;

    var $owner;     // TicketOwner
    var $_user;      // EndUser
    var $_answers;
    var $collaborators;
    var $active_collaborators;
    var $recipients;
    var $lastrespondent;
    var $lastuserrespondent;
    var $_children;

    function loadDynamicData($force=false) {
        if (!isset($this->_answers) || $force) {
            $this->_answers = array();
            foreach (DynamicFormEntryAnswer::objects()
                ->filter(array(
                    'entry__object_id' => $this->getId(),
                    'entry__object_type' => 'T'
                )) as $answer
            ) {
                $tag = mb_strtolower($answer->field->name)
                    ?: 'field.' . $answer->field->id;
                    $this->_answers[$tag] = $answer;
            }
        }
        return $this->_answers;
    }

    function getAnswer($field, $form=null) {
        // TODO: Prefer CDATA ORM relationship if already loaded
        $this->loadDynamicData();
        return $this->_answers[$field];
    }

    function getId() {
        return $this->ticket_id;
    }

    function getPid() {
        return $this->ticket_pid;
    }

    function getChildren() {
        if (!isset($this->_children) && $this->isParent())
            $this->_children = self::getChildTickets($this->getId());

        return $this->_children ?: array();
    }

    static function getMergeTypeByFlag($flag) {
        if (($flag & self::FLAG_COMBINE_THREADS) != 0)
            return 'combine';
        if (($flag & self::FLAG_SEPARATE_THREADS) != 0)
            return 'separate';
        else
            return 'visual';
        return 'visual';
    }

    function getMergeType() {
        if ($this->hasFlag(self::FLAG_COMBINE_THREADS))
            return 'combine';
        if ($this->hasFlag(self::FLAG_SEPARATE_THREADS))
            return 'separate';
        else
            return 'visual';
        return 'visual';
    }

    function isMerged() {
        if (!is_null($this->getPid()) || $this->isParent())
            return true;

        return false;
    }

    function isParent() {
        if ($this->hasFlag(self::FLAG_PARENT))
            return true;

        return false;
    }

    static function isParentStatic($flag=false) {
        if (is_numeric($flag) && ($flag & self::FLAG_PARENT) != 0)
            return true;

        return false;
    }

    function hasFlag($flag) {
        return ($this->get('flags', 0) & $flag) != 0;
    }

    function isChild($pid=false) {
        return ($this->getPid() ? true : false);
    }

    function hasState($state) {
        return  strcasecmp($this->getState(), $state) == 0;
    }

    function isOpen() {
        return $this->hasState('open');
    }

    function isReopened() {
        return null !== $this->getReopenDate();
    }

    function isReopenable() {
        return ($this->getStatus()->isReopenable() && $this->getDept()->allowsReopen()
        && ($this->getTopic() ? $this->getTopic()->allowsReopen() : true));
    }

    function isClosed() {
         return $this->hasState('closed');
    }

    function isCloseable() {
        global $cfg;

        if ($this->isClosed())
            return true;

        $warning = null;
        if (self::getMissingRequiredFields($this)) {
            $warning = sprintf(
                    __( '%1$s is missing data on %2$s one or more required fields %3$s and cannot be closed'),
                    __('This ticket'),
                    '', '');
        } elseif (($num=$this->getNumOpenTasks())) {
            $warning = sprintf(__('%1$s has %2$d open tasks and cannot be closed'),
                    __('This ticket'), $num);
        } elseif ($cfg->requireTopicToClose() && !$this->getTopicId()) {
            $warning = sprintf(
                    __( '%1$s is missing a %2$s and cannot be closed'),
                    __('This ticket'), __('Help Topic'), '');
        }

        return $warning ?: true;
    }

    function isArchived() {
         return $this->hasState('archived');
    }

    function isDeleted() {
         return $this->hasState('deleted');
    }

    function isAssigned($to=null) {
        if (!$this->isOpen())
            return false;

        if (is_null($to))
            return ($this->getStaffId() || $this->getTeamId());

        switch (true) {
        case $to instanceof Staff:
            return ($to->getId() == $this->getStaffId() ||
                    $to->isTeamMember($this->getTeamId()));
            break;
        case $to instanceof Team:
            return ($to->getId() == $this->getTeamId());
            break;
        }

        return false;
    }

    function isOverdue() {
        return $this->ht['isoverdue'];
    }

    function isAnswered() {
       return $this->ht['isanswered'];
    }

    function isLocked() {
        return null !== $this->getLock();
    }

    function getRole($staff) {
        if (!$staff instanceof Staff)
            return null;

        return $staff->getRole($this->getDept(), $this->isAssigned($staff));
    }

    function checkStaffPerm($staff, $perm=null) {

        // Must be a valid staff
        if ((!$staff instanceof Staff) && !($staff=Staff::lookup($staff)))
            return false;

        // check department access first
        if (!$staff->canAccessDept($this->getDept())
                // check assignment
                && !$this->isAssigned($staff)
                // check referral
                && !$this->getThread()->isReferred($staff))
            return false;

        // At this point staff has view access unless a specific permission is
        // requested
        if ($perm === null)
            return true;

        // Permission check requested -- get role if any
        if (!($role=$this->getRole($staff)))
            return false;

        // Check permission based on the effective role
        return $role->hasPerm($perm);
    }

    function checkUserAccess($user) {
        if (!$user || !($user instanceof EndUser))
            return false;

        // Ticket Owner
        if ($user->getId() == $this->getUserId())
            return true;

        // Organization
        if ($user->canSeeOrgTickets()
            && ($U = $this->getUser())
            && ($U->getOrgId() == $user->getOrgId())
        ) {
            // The owner of this ticket is in the same organization as the
            // user in question, and the organization is configured to allow
            // the user in question to see other tickets in the
            // organization.
            return true;
        }

        // Collaborator?
        // 1) If the user was authorized via this ticket.
        if ($user->getTicketId() == $this->getId()
            && !strcasecmp($user->getUserType(), 'collaborator')
        ) {
            return true;
        }
        // 2) Query the database to check for expanded access...
        if (Collaborator::lookup(array(
            'user_id' => $user->getId(),
            'thread_id' => $this->getThreadId()))
        ) {
            return true;
        }
        // 3) If the ticket is a child of a merge
        if ($this->isParent() && $this->getMergeType() != 'visual') {
            $children = Ticket::objects()
                    ->filter(array('ticket_pid'=>$this->getId()))
                    ->order_by('sort');

            foreach ($children as $child)
                if ($child->checkUserAccess($user))
                    return true;
        }

        return false;
    }

    // Getters
    function getNumber() {
        return $this->number;
    }

    function getOwnerId() {
        return $this->user_id;
    }

    function getOwner() {
        if (!isset($this->owner)) {
            $this->owner = new TicketOwner(new EndUser($this->user), $this);
        }
        return $this->owner;
    }

    function getEmail() {
        if ($o = $this->getOwner()) {
            return $o->getEmail();
        }
        return null;
    }

    function getReplyToEmail() {
        //TODO: Determine the email to use (once we enable multi-email support)
        return $this->getEmail();
    }

    // Deprecated
    function getOldAuthToken() {
        # XXX: Support variable email address (for CCs)
        return md5($this->getId() . strtolower($this->getEmail()) . SECRET_SALT);
    }

    function getName(){
        if ($o = $this->getOwner()) {
            return $o->getName();
        }
        return null;
    }

    function getSubject() {
        return (string) $this->getAnswer('subject');
    }

    /* Help topic title  - NOT object -> $topic */
    function getHelpTopic() {
        if ($this->topic)
            return $this->topic->getFullName();
    }

    function getCreateDate() {
        return $this->created;
    }

    function getOpenDate() {
        return $this->getCreateDate();
    }

    function getReopenDate() {
        return $this->reopened;
    }

    function getUpdateDate() {
        return $this->updated;
    }

    function getEffectiveDate() {
        return $this->lastupdate;
    }

    function getDueDate() {
        return $this->duedate;
    }

    function getSLADueDate($recompute=false) {
        global $cfg;

        if (!$recompute && $this->est_duedate)
            return $this->est_duedate;

        if (($sla = $this->getSLA()) && $sla->isActive()) {
            $schedule = $this->getDept()->getSchedule();
            $tz = new DateTimeZone($cfg->getDbTimezone());
            $dt = new DateTime($this->getReopenDate() ?:
                    $this->getCreateDate(), $tz);
            $dt = $sla->addGracePeriod($dt, $schedule);
            // Make sure time is in DB timezone
            $dt->setTimezone($tz);
            return $dt->format('Y-m-d H:i:s');
        }
    }

    function updateEstDueDate($clearOverdue=true) {
        if ($this->isOverdue() && $clearOverdue)
            $this->clearOverdue(false);

        $this->est_duedate = $this->getSLADueDate(true) ?: null;

        return $this->save();
    }

    function getEstDueDate() {
        // Real due date or  sla due date (If ANY)
        return $this->getDueDate() ?: $this->getSLADueDate();
    }


    function getCloseDate() {
        return $this->closed;
    }

    function getStatusId() {
        return $this->status_id;
    }

    /**
     * setStatusId
     *
     * Forceably set the ticket status ID to the received status ID. No
     * checks are made. Use ::setStatus() to change the ticket status
     */
    // XXX: Use ::setStatus to change the status. This can be used as a
    //      fallback if the logic in ::setStatus fails.
    function setStatusId($id) {
        $this->status_id = $id;
        return $this->save();
    }

    function getStatus() {
        return $this->status;
    }

    function getState() {
        if (!$this->getStatus()) {
            return '';
        }
        return $this->getStatus()->getState();
    }

    function getDeptId() {
       return $this->dept_id;
    }

    function getDeptName() {
        if ($this->dept instanceof Dept)
            return $this->dept->getFullName();
    }

    function getPriorityId() {
        global $cfg;

        if (($priority = $this->getPriority()))
            return $priority->getId();

        return $cfg->getDefaultPriorityId();
    }

    function getPriority() {
        if (($a = $this->getAnswer('priority')))
            return $a->getValue();

        return null;
    }

    function getPriorityField() {
        if (($a = $this->getAnswer('priority')))
            return $a->getField();

        return TicketForm::getInstance()->getField('priority');
    }

    function getPhoneNumber() {
        return (string)$this->getOwner()->getPhoneNumber();
    }

    function getSource() {
        $sources = $this->getSources();
        return $sources[$this->source] ?: $this->source;
    }

    function getIP() {
        return $this->ip_address;
    }

    function getHashtable() {
        return $this->ht;
    }

    function getUpdateInfo() {
        global $cfg;

        return array(
            'source'    => $this->source,
            'topicId'   => $this->getTopicId(),
            'slaId'     => $this->getSLAId(),
            'user_id'   => $this->getOwnerId(),
            'duedate'   => Misc::db2gmtime($this->getDueDate()),
        );
    }

    function getLock() {
        $lock = $this->lock;
        if ($lock && !$lock->isExpired())
            return $lock;
    }

    function acquireLock($staffId, $lockTime=null) {
        global $cfg;

        if (!isset($lockTime))
            $lockTime = $cfg->getLockTime();

        if (!$staffId or !$lockTime) //Lockig disabled?
            return null;

        // Check if the ticket is already locked.
        if (($lock = $this->getLock()) && !$lock->isExpired()) {
            if ($lock->getStaffId() != $staffId) //someone else locked the ticket.
                return null;

            //Lock already exits...renew it
            $lock->renew($lockTime); //New clock baby.

            return $lock;
        }
        // No lock on the ticket or it is expired
        $this->lock = Lock::acquire($staffId, $lockTime); //Create a new lock..

        if ($this->lock) {
            $this->save();
        }

        // load and return the newly created lock if any!
        return $this->lock;
    }

    function releaseLock($staffId=false) {
        if (!($lock = $this->getLock()))
            return false;

        if ($staffId && $lock->staff_id != $staffId)
            return false;

        if (!$lock->delete())
            return false;

        $this->lock = null;
        return $this->save();
    }

    function getDept() {
        global $cfg;

        return $this->dept ?: $cfg->getDefaultDept();
    }

    function getUserId() {
        return $this->getOwnerId();
    }

    function getUser() {
        if (!isset($this->_user) && $this->user) {
            $this->_user = new EndUser($this->user);
        }
        return $this->_user;
    }

    function getStaffId() {
        return $this->staff_id;
    }

    function getStaff() {
        return $this->staff;
    }

    function getTeamId() {
        return $this->team_id;
    }

    function getTeam() {
        return $this->team;
    }

    function getAssigneeId() {

        if (!($assignee=$this->getAssignee()))
            return null;

        $id = '';
        if ($assignee instanceof Staff)
            $id = 's'.$assignee->getId();
        elseif ($assignee instanceof Team)
            $id = 't'.$assignee->getId();

        return $id;
    }

    function getAssignee() {

        if (!$this->isOpen() || !$this->isAssigned())
            return false;

        if ($this->staff)
            return $this->staff;

        if ($this->team)
            return $this->team;

        return null;
    }

    function getAssignees() {

        $assignees = array();
        if ($staff = $this->getStaff())
            $assignees[] = $staff->getName();

        if ($team = $this->getTeam())
            $assignees[] = $team->getName();

        return $assignees;
    }

    function getAssigned($glue='/') {
        $assignees = $this->getAssignees();
        return $assignees ? implode($glue, $assignees) : '';
    }

    function getTopicId() {
        return $this->topic_id;
    }

    function getTopic() {
        return $this->topic;
    }


    function getSLAId() {
        return $this->sla_id;
    }

    function getSLA() {
        return $this->sla;
    }

    function getLastRespondent() {
        if (!isset($this->lastrespondent)) {
            if (!$this->getThread() || !$this->getThread()->entries)
                return $this->lastrespondent = false;
            $this->lastrespondent = Staff::objects()
                ->filter(array(
                'staff_id' => $this->getThread()->entries
                    ->filter(array(
                        'type' => 'R',
                        'staff_id__gt' => 0,
                    ))
                    ->values_flat('staff_id')
                    ->order_by('-id')
                    ->limit('1,1')
                ))
                ->first()
                ?: false;
        }
        return $this->lastrespondent;
    }

    function getLastUserRespondent() {
        if (!isset($this->$lastuserrespondent)) {
            if (!$this->getThread() || !$this->getThread()->entries)
                return $this->$lastuserrespondent = false;
            $this->$lastuserrespondent = User::objects()
                ->filter(array(
                'id' => $this->getThread()->entries
                    ->filter(array(
                        'user_id__gt' => 0,
                    ))
                    ->values_flat('user_id')
                    ->order_by('-id')
                    ->limit(1)
                ))
                ->first()
                ?: false;
        }
        return $this->$lastuserrespondent;
    }

    function getLastMessageDate() {
        return $this->getThread()->lastmessage;
    }

    function getLastMsgDate() {
        return $this->getLastMessageDate();
    }

    function getLastResponseDate() {
        return $this->getThread()->lastresponse;
    }

    function getLastRespDate() {
        return $this->getLastResponseDate();
    }

    function getLastMsgId() {
        return $this->lastMsgId;
    }

    function getLastMessage() {
        if (!isset($this->last_message)) {
            if ($this->getLastMsgId())
                $this->last_message = MessageThreadEntry::lookup(
                    $this->getLastMsgId(), $this->getThreadId());

            if (!$this->last_message)
                $this->last_message = $this->getThread() ? $this->getThread()->getLastMessage() : '';
        }
        return $this->last_message;
    }

    function getNumTasks() {
        // FIXME: Implement this after merging Tasks
        return count($this->tasks);
    }

    function getNumOpenTasks() {
        return count($this->tasks->filter(array(
                        'flags__hasbit' => TaskModel::ISOPEN)));
    }


    function getThreadId() {
        if ($this->getThread())
            return $this->getThread()->getId();
    }

    function getThread() {
        if (is_null($this->thread) && $this->child_thread)
            return $this->child_thread;

        return $this->thread;
    }

    function getThreadCount() {
        return $this->getClientThread()->count();
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
        return $this->getThreadEntries(array('M'));
    }

    function getResponses() {
        return $this->getThreadEntries(array('R'));
    }

    function getNotes() {
        return $this->getThreadEntries(array('N'));
    }

    function getClientThread() {
        return $this->getThreadEntries(array('M', 'R'));
    }

    function getThreadEntry($id) {
        return $this->getThread()->getEntry($id);
    }

    function getThreadEntries($type=false) {
        if ($this->getThread()) {
            $entries = $this->getThread()->getEntries();
            if ($type && is_array($type))
                $entries->filter(array('type__in' => $type));
        }

        return $entries;
    }

    // MailingList of participants  (owner + collaborators)
    function getRecipients($who='all', $whitelist=array(), $active=true) {
        $list = new MailingList();
        switch (strtolower($who)) {
            case 'user':
                $list->addTo($this->getOwner());
                break;
            case 'all':
                $list->addTo($this->getOwner());
                // Fall-trough
            case 'collabs':
                if (($collabs = $active ?  $this->getActiveCollaborators() :
                    $this->getCollaborators())) {
                    foreach ($collabs as $c)
                        if (!$whitelist || in_array($c->getUserId(),
                                    $whitelist))
                            $list->addCc($c);
                }
                break;
            default:
                return null;
        }
        return $list;
    }

    function getCollaborators() {
        return $this->getThread() ? $this->getThread()->getCollaborators() : '';
    }

    function getNumCollaborators() {
        return $this->getThread() ? $this->getThread()->getNumCollaborators() : '';
    }

    function getActiveCollaborators() {
        return $this->getThread() ? $this->getThread()->getActiveCollaborators() : '';
    }

    function getNumActiveCollaborators() {
        return $this->getThread() ? $this->getThread()->getNumActiveCollaborators() : '';
    }

    function getAssignmentForm($source=null, $options=array()) {
        global $thisstaff;

        $prompt = $assignee = '';
        // Possible assignees
        $dept = $this->getDept();
        switch (strtolower($options['target'])) {
            case 'agents':
                if (!$source && $this->isOpen() && $this->staff)
                    $assignee = sprintf('s%d', $this->staff->getId());
                $prompt = __('Select an Agent');
                break;
            case 'teams':
                if (!$source && $this->isOpen() && $this->team)
                    $assignee = sprintf('t%d', $this->team->getId());
                $prompt = __('Select a Team');
                break;
        }

        // Default to current assignee if source is not set
        if (!$source)
            $source = array('assignee' => array($assignee));

        $form = AssignmentForm::instantiate($source, $options);

        if (($refer = $form->getField('refer'))) {
            if (!$assignee) {
                $visibility = new VisibilityConstraint(
                        new Q(array()), VisibilityConstraint::HIDDEN);
                $refer->set('visibility', $visibility);
            } else {
                $refer->configure('desc', sprintf(__('Maintain referral access to %s'),
                        $this->getAssigned()));
            }
        }

        // Field configurations
        if ($f=$form->getField('assignee')) {
            $f->configure('dept', $dept);
            $f->configure('staff', $thisstaff);
            if ($prompt)
                $f->configure('prompt', $prompt);
            if ($options['target'])
                $f->configure('target', $options['target']);
        }

        return $form;
    }

    function getReferralForm($source=null, $options=array()) {
        global $thisstaff;

        $form = ReferralForm::instantiate($source, $options);
        $dept = $this->getDept();
        // Agents
        $staff = Staff::objects()->filter(array(
         'isactive' => 1,
         ))
         ->filter(Q::not(array('dept_id' => $dept->getId())));

        $staff = Staff::nsort($staff);
        $agents = array();
        foreach ($staff as $s)
          $agents[$s->getId()] = $s;
        $form->setChoices('agent', $agents);
        // Teams
        $form->setChoices('team', Team::getActiveTeams());
        // Depts
        $form->setChoices('dept', Dept::getActiveDepartments());

        // Field configurations
        if ($f=$form->getField('agent')) {
            $f->configure('dept', $dept);
            $f->configure('staff', $thisstaff);
        }

        if ($f = $form->getField('dept'))
            $f->configure('hideDisabled', true);

        return $form;
    }

    function getClaimForm($source=null, $options=array()) {
        global $thisstaff;

        $id = sprintf('s%d', $thisstaff->getId());
        if(!$source)
            $source = array('assignee' => array($id));

        $form = ClaimForm::instantiate($source, $options);
        $form->setAssignees(array($id => $thisstaff->getName()));

        return $form;

    }

    function getTransferForm($source=null) {
        global $thisstaff;

        if (!$source)
            $source = array('dept' => array($this->getDeptId()),
                    'refer' => false);

        $form = TransferForm::instantiate($source);

        $form->hideDisabled();

        return $form;
    }

    function getField($fid) {

        if (is_numeric($fid))
            return $this->getDynamicFieldById($fid);

        // Special fields
        switch ($fid) {
        case 'priority':
            return $this->getPriorityField();
            break;
        case 'sla':
            return SLAField::init(array(
                        'id' => $fid,
                        'name' => "{$fid}_id",
                        'label' => __('SLA Plan'),
                        'default' => $this->getSLAId(),
                        'choices' => SLA::getSLAs()
                        ));
            break;
        case 'topic':
            $current = array();
            if ($topic = $this->getTopic())
                $current = array($topic->getId());
            $choices = Topic::getHelpTopics(false, $topic ? (Topic::DISPLAY_DISABLED) : false, true, $current);
            return TopicField::init(array(
                        'id' => $fid,
                        'name' => "{$fid}_id",
                        'label' => __('Help Topic'),
                        'default' => $this->getTopicId(),
                        'choices' => $choices
                        ));
            break;
        case 'source':
            return ChoiceField::init(array(
                        'id' => $fid,
                        'name' => 'source',
                        'label' => __('Ticket Source'),
                        'default' => $this->source,
                        'choices' => Ticket::getSources()
                        ));
            break;
        case 'duedate':

            $hint = sprintf(__('Setting a %s will override %s'),
                    __('Due Date'), __('SLA Plan'));
            return DateTimeField::init(array(
                'id' => $fid,
                'name' => $fid,
                'default' => Misc::db2gmtime($this->getDueDate()),
                'label' => __('Due Date'),
                'hint' => $hint,
                'configuration' => array(
                    'min' => Misc::gmtime(),
                    'time' => true,
                    'gmt' => false,
                    'future' => true,
                    )
                ));
        }
    }

    function getDynamicFieldById($fid) {
        foreach (DynamicFormEntry::forTicket($this->getId()) as $form) {
            foreach ($form->getFields() as $field)
                if ($field->getId() == $fid) {
                    // This is to prevent SimpleForm using index name as
                    // field name when one is not set.
                    if (!$field->get('name'))
                        $field->set('name', "field_$fid");

                    return $field;
                }
        }
    }

    function getDynamicFields($criteria=array()) {

        $fields = DynamicFormField::objects()->filter(array(
                    'id__in' => $this->entries
                    ->filter($criteria)
                ->values_flat('answers__field_id')));

        return ($fields && count($fields)) ? $fields : array();
    }

    function hasClientEditableFields() {
        $forms = DynamicFormEntry::forTicket($this->getId());
        foreach ($forms as $form) {
            foreach ($form->getFields() as $field) {
                if ($field->isEditableToUsers())
                    return true;
            }
        }
    }

    //if ids passed, function returns only the ids of fields disabled by help topic
    static function getMissingRequiredFields($ticket, $ids=false) {
        // Check for fields disabled by Help Topic
        $disabled = array();
        foreach (($ticket->getTopic() ? $ticket->getTopic()->forms : $ticket->entries) as $f) {
            $extra = JsonDataParser::decode($f->extra);

            if (!empty($extra['disable']))
                $disabled[] = $extra['disable'];
        }

        $disabled = !empty($disabled) ? call_user_func_array('array_merge', $disabled) : NULL;

        if ($ids)
          return $disabled;

        $criteria = array(
                    'answers__field__flags__hasbit' => DynamicFormField::FLAG_ENABLED,
                    'answers__field__flags__hasbit' => DynamicFormField::FLAG_CLOSE_REQUIRED,
                    'answers__value__isnull' => true,
                    );

        // If there are disabled fields then exclude them
        if ($disabled)
            array_push($criteria, Q::not(array('answers__field__id__in' => $disabled)));

        return $ticket->getDynamicFields($criteria);
    }

    function getMissingRequiredField() {
        $fields = self::getMissingRequiredFields($this);
        return $fields ? $fields[0] : null;
    }

    function addCollaborator($user, $vars, &$errors, $event=true) {

        if ($user && $user->getId() == $this->getOwnerId())
            $errors['err'] = __('Ticket Owner cannot be a Collaborator');

        if ($user && !$errors
                && ($c = $this->getThread()->addCollaborator($user, $vars,
                        $errors, $event))) {
            $c->setCc($c->active);
            $this->collaborators = null;
            $this->recipients = null;
            return $c;
        }

        return null;
    }

    function addCollaborators($users, $vars, &$errors, $event=true) {

        if (!$users || !is_array($users))
            return null;

        $collabs = $this->getCollaborators();
        $new = array();
        foreach ($users as $user) {
            if (!($user instanceof User)
                    && !($user = User::lookup($user)))
                continue;
            if ($collabs->findFirst(array('user_id' => $user->getId())))
                continue;
            if ($user->getId() == $this->getOwnerId())
                continue;

            if ($c=$this->addCollaborator($user, $vars, $errors, $event))
                $new[] = $c;
        }
        return $new;
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
                        && $c->delete())
                     $collabs[] = (string) $c;
            }
            $this->logEvent('collab', array('del' => $collabs));
        }

        //statuses
        $cids = null;
        if($vars['cid'] && ($cids=array_filter($vars['cid']))) {
            $this->getThread()->collaborators->filter(array(
                'thread_id' => $this->getThreadId(),
                'id__in' => $cids
            ))->update(array(
                'updated' => SqlFunction::NOW(),
            ));
        }

        if ($cids) {
            $this->getThread()->collaborators->filter(array(
                'thread_id' => $this->getThreadId(),
                Q::not(array('id__in' => $cids))
            ))->update(array(
                'updated' => SqlFunction::NOW(),
            ));
        }

        unset($this->active_collaborators);
        $this->collaborators = null;

        return true;
    }

    function getAuthToken($user, $algo=1) {

        //Format: // <user type><algo id used>x<pack of uid & tid><hash of the algo>
        $authtoken = sprintf('%s%dx%s',
                ($user->getId() == $this->getOwnerId() ? 'o' : 'c'),
                $algo,
                Base32::encode(pack('VV',$user->getId(), $this->getId())));

        switch($algo) {
            case 1:
                $authtoken .= substr(base64_encode(
                            md5($user->getId().$this->getCreateDate().$this->getId().SECRET_SALT, true)), 8);
                break;
            default:
                return null;
        }

        return $authtoken;
    }

    function sendAccessLink($user) {
        global $ost;

        if (!($email = $ost->getConfig()->getDefaultEmail())
            || !($content = Page::lookupByType('access-link')))
            return;

        $vars = array(
            'url' => $ost->getConfig()->getBaseUrl(),
            'ticket' => $this,
            'user' => $user,
            'recipient' => $user,
            // Get ticket link, with authcode, directly to bypass collabs
            // check
            'recipient.ticket_link' => $user->getTicketLink(),
        );

        $lang = $user->getLanguage(UserAccount::LANG_MAILOUTS);
        $msg = $ost->replaceTemplateVariables(array(
            'subj' => $content->getLocalName($lang),
            'body' => $content->getLocalBody($lang),
        ), $vars);

        $email->send($user, Format::striptags($msg['subj']),
            $msg['body']);
    }


    /* -------------------- Setters --------------------- */
    public function setFlag($flag, $val) {

        if ($val)
            $this->flags |= $flag;
        else
            $this->flags &= ~$flag;
    }

    function setMergeType($combine=false, $parent=false) {
        //for $combine, 0 = separate, 1 = combine, 2 = link, 3 = regular ticket
        $flags = array(Ticket::FLAG_SEPARATE_THREADS, Ticket::FLAG_COMBINE_THREADS, Ticket::FLAG_LINKED);
        foreach ($flags as $key => $flag) {
            if ($combine == $key)
                $this->setFlag($flag, true);
            else
                $this->setFlag($flag, false);
        }
        if ($parent)
            $this->setFlag(Ticket::FLAG_PARENT, true);
        else
            $this->setFlag(Ticket::FLAG_PARENT, false);

        $this->save();
    }

    function setPid($pid) {
        return $this->ticket_pid = $this->getId() != $pid ? $pid : NULL;
    }

    function setSort($sort) {
        return $this->sort=$sort;
    }

    function setLastMsgId($msgid) {
        return $this->lastMsgId=$msgid;
    }
    function setLastMessage($message) {
        $this->last_message = $message;
        $this->setLastMsgId($message->getId());
    }

    //DeptId can NOT be 0. No orphans please!
    function setDeptId($deptId) {
        // Make sure it's a valid department
        if ($deptId == $this->getDeptId() || !($dept=Dept::lookup($deptId))) {
            return false;
        }
        $this->dept = $dept;
        return $this->save();
    }

    // Set staff ID...assign/unassign/release (id can be 0)
    function setStaffId($staffId) {
        if (!is_numeric($staffId))
            return false;

        $this->staff = Staff::lookup($staffId);
        return $this->save();
    }

    function setSLAId($slaId) {
        if ($slaId == $this->getSLAId())
            return true;

        $sla = null;
        if ($slaId && !($sla = Sla::lookup($slaId)))
            return false;

        $this->sla = $sla;
        return $this->save();
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
        if (!is_numeric($teamId))
            return false;

        $this->team = Team::lookup($teamId);
        return $this->save();
    }

    // Ticket Status helper.
    function setStatus($status, $comments='', &$errors=array(), $set_closing_agent=true, $force_close=false) {
        global $cfg, $thisstaff;

        if ($thisstaff && !($role=$this->getRole($thisstaff)))
            return false;

        if ((!$status instanceof TicketStatus)
                && !($status = TicketStatus::lookup($status)))
            return false;

        // Double check permissions (when changing status)
        if ($role && $this->getStatusId()) {
            switch ($status->getState()) {
            case 'closed':
                if (!($role->hasPerm(Ticket::PERM_CLOSE)))
                    return false;
                break;
            case 'deleted':
                // XXX: intercept deleted status and do hard delete TODO: soft deletes
                if ($role->hasPerm(Ticket::PERM_DELETE))
                    return $this->delete($comments);
                // Agent doesn't have permission to delete  tickets
                return false;
                break;
            }
        }

        $hadStatus = $this->getStatusId();
        if ($this->getStatusId() == $status->getId())
            return true;

        // Perform checks on the *new* status, _before_ the status changes
        $ecb = $refer = null;
        switch ($status->getState()) {
            case 'closed':
                // Check if ticket is closeable
                $closeable = $force_close ? true : $this->isCloseable();
                if ($closeable !== true)
                    $errors['err'] = $closeable ?: sprintf(__('%s cannot be closed'), __('This ticket'));

                if ($errors)
                    return false;

                $refer = $this->staff ?: $thisstaff;
                $this->closed = $this->lastupdate = SqlFunction::NOW();
                if ($thisstaff && $set_closing_agent)
                    $this->staff = $thisstaff;
                // Clear overdue flags & due dates
                $this->clearOverdue(false);

                $ecb = function($t) use ($status) {
                    $t->logEvent('closed', array('status' => array($status->getId(), $status->getName())), null, 'closed');
                    $t->deleteDrafts();
                };
                break;
            case 'open':
                if ($this->isClosed() && $this->isReopenable()) {
                    // Auto-assign to closing staff or the last respondent if the
                    // agent is available and has access. Otherwise, put the ticket back
                    // to unassigned pool.
                    $dept = $this->getDept();
                    $staff = $this->getStaff() ?: $this->getLastRespondent();
                    $autoassign = (!$dept->disableReopenAutoAssign());
                    if ($autoassign
                            && $staff
                            // Is agent on vacation ?
                            && $staff->isAvailable()
                            // Does the agent have access to dept?
                            && $staff->canAccessDept($dept))
                        $this->setStaffId($staff->getId());
                    else
                        $this->setStaffId(0); // Clear assignment
                }

                if ($this->isClosed()) {
                    $this->closed = null;
                    $this->lastupdate = $this->reopened = SqlFunction::NOW();
                    $ecb = function ($t) {
                        $t->logEvent('reopened', false, null, 'closed');
                        // Set new sla duedate if any
                        $t->updateEstDueDate();
                    };
                }

                // If the ticket is not open then clear answered flag
                if (!$this->isOpen())
                    $this->isanswered = 0;
                break;
            default:
                return false;

        }

        $this->status = $status;
        if (!$this->save(true))
            return false;

        // Refer thread to previously assigned or closing agent
        if ($refer && $cfg->autoReferTicketsOnClose())
            $this->getThread()->refer($refer);

        // Log status change b4 reload — if currently has a status. (On new
        // ticket, the ticket is opened and thereafter the status is set to
        // the requested status).
        if ($hadStatus) {
            $alert = false;
            if ($comments = ThreadEntryBody::clean($comments)) {
                // Send out alerts if comments are included
                $alert = true;
                $this->logNote(__('Status Changed'), $comments, $thisstaff, $alert);
            }
        }
        // Log events via callback
        if ($ecb)
            $ecb($this);
        elseif ($hadStatus)
            // Don't log the initial status change
            $this->logEvent('edited', array('status' => $status->getId()));

        return true;
    }

    function setState($state, $alerts=false) {
        switch (strtolower($state)) {
        case 'open':
            return $this->setStatus('open');
        case 'closed':
            return $this->setStatus('closed');
        case 'answered':
            return $this->setAnsweredState(1);
        case 'unanswered':
            return $this->setAnsweredState(0);
        case 'overdue':
            return $this->markOverdue();
        case 'notdue':
            return $this->clearOverdue();
        case 'unassined':
            return $this->unassign();
        }
        // FIXME: Throw and excception and add test cases
        return false;
    }

    function setAnsweredState($isanswered) {
        $this->isanswered = $isanswered;
        return $this->save();
    }

    function reopen() {
        global $cfg;

        if (!$this->isClosed())
            return false;

        // Set status to open based on current closed status settings
        // If the closed status doesn't have configured "reopen" status then use the
        // the default ticket status.
        if (!($status=$this->getStatus()->getReopenStatus()))
            $status = $cfg->getDefaultTicketStatusId();

        return $status ? $this->setStatus($status) : false;
    }

    function onNewTicket($message, $autorespond=true, $alertstaff=true) {
        global $cfg;

        //Log stuff here...

        if (!$autorespond && !$alertstaff)
            return true; //No alerts to send.

        /* ------ SEND OUT NEW TICKET AUTORESP && ALERTS ----------*/

        if(!$cfg
            || !($dept=$this->getDept())
            || !($tpl = $dept->getTemplate())
            || !($email=$dept->getAutoRespEmail())
        ) {
            return false;  //bail out...missing stuff.
        }

        $options = array();
        if (($message instanceof ThreadEntry)
                && $message->getEmailMessageId()) {
            $options += array(
                'inreplyto'=>$message->getEmailMessageId(),
                'references'=>$message->getEmailReferences(),
                'thread'=>$message
            );
        }
        else {
            $options += array(
                'thread' => $this->getThread(),
            );
        }

        //Send auto response - if enabled.
        if ($autorespond
            && $cfg->autoRespONNewTicket()
            && $dept->autoRespONNewTicket()
            && ($msg = $tpl->getAutoRespMsgTemplate())
        ) {
            $msg = $this->replaceVars(
                $msg->asArray(),
                array('message' => $message,
                      'recipient' => $this->getOwner(),
                      'signature' => ($dept && $dept->isPublic())?$dept->getSignature():''
                )
            );
            $email->sendAutoReply($this->getOwner(), $msg['subj'], $msg['body'],
                null, $options);
        }

        // Send alert to out sleepy & idle staff.
        if ($alertstaff
            && $cfg->alertONNewTicket()
            && ($email=$dept->getAlertEmail())
            && ($msg=$tpl->getNewTicketAlertMsgTemplate())
        ) {
            $msg = $this->replaceVars($msg->asArray(), array('message' => $message));
            $recipients = $sentlist = array();
            // Exclude the auto responding email just incase it's from staff member.
            if ($message instanceof ThreadEntry && $message->isAutoReply())
                $sentlist[] = $this->getEmail();

            if ($dept->getNumMembersForAlerts()) {
                // Only alerts dept members if the ticket is NOT assigned.
                $manager = $dept->getManager();
                if ($cfg->alertDeptMembersONNewTicket() && !$this->isAssigned()
                    && ($members = $dept->getMembersForAlerts())
                ) {
                    foreach ($members as $M)
                        if ($M != $manager)
                            $recipients[] = $M;
                }

                if ($cfg->alertDeptManagerONNewTicket() && $manager) {
                    $recipients[] = $manager;
                }

                // Account manager
                if ($cfg->alertAcctManagerONNewTicket()
                    && ($org = $this->getOwner()->getOrganization())
                    && ($acct_manager = $org->getAccountManager())
                ) {
                    if ($acct_manager instanceof Team)
                        $recipients = array_merge($recipients, $acct_manager->getMembersForAlerts());
                    else
                        $recipients[] = $acct_manager;
                }

                foreach ($recipients as $k=>$staff) {
                    if (!is_object($staff)
                        || !$staff->isAvailable()
                        || in_array($staff->getEmail(), $sentlist)
                    ) {
                        continue;
                    }
                    $alert = $this->replaceVars($msg, array('recipient' => $staff));
                    $email->sendAlert($staff, $alert['subj'], $alert['body'], null, $options);
                    $sentlist[] = $staff->getEmail();
                }
            }

            // Alert admin ONLY if not already a staff??
            if ($cfg->alertAdminONNewTicket()
                    && !in_array($cfg->getAdminEmail(), $sentlist)
                    && ($dept->isGroupMembershipEnabled() != Dept::ALERTS_DISABLED)) {
                $options += array('utype'=>'A');
                $alert = $this->replaceVars($msg, array('recipient' => 'Admin'));
                $email->sendAlert($cfg->getAdminEmail(), $alert['subj'],
                        $alert['body'], null, $options);
            }

        }
        return true;
    }

    function onOpenLimit($sendNotice=true) {
        global $ost, $cfg;

        //Log the limit notice as a warning for admin.
        $msg=sprintf(_S('Maximum open tickets (%1$d) reached for %2$s'),
            $cfg->getMaxOpenTickets(), $this->getEmail());
        $ost->logWarning(sprintf(_S('Maximum Open Tickets Limit (%s)'),$this->getEmail()),
            $msg);

        if (!$sendNotice || !$cfg->sendOverLimitNotice())
            return true;

        //Send notice to user.
        if (($dept = $this->getDept())
            && ($tpl=$dept->getTemplate())
            && ($msg=$tpl->getOverlimitMsgTemplate())
            && ($email=$dept->getAutoRespEmail())
        ) {
            $msg = $this->replaceVars(
                $msg->asArray(),
                array('signature' => ($dept && $dept->isPublic())?$dept->getSignature():'')
            );

            $email->sendAutoReply($this->getOwner(), $msg['subj'], $msg['body']);
        }

        $user = $this->getOwner();

        // Alert admin...this might be spammy (no option to disable)...but it is helpful..I think.
        $alert=sprintf(__('Maximum open tickets reached for %s.'), $this->getEmail())."\n"
              .sprintf(__('Open tickets: %d'), $user->getNumOpenTickets())."\n"
              .sprintf(__('Max allowed: %d'), $cfg->getMaxOpenTickets())
              ."\n\n".__("Notice sent to the user.");

        $ost->alertAdmin(__('Overlimit Notice'), $alert);

        return true;
    }

    function onResponse($response, $options=array()) {
        $this->isanswered = 1;
        $this->save();

        $vars = array_merge($options,
            array(
                'activity' => _S('New Response'),
                'threadentry' => $response
            )
        );
        $this->onActivity($vars);
    }

    /*
     * Notify collaborators on response or new message
     *
     */
    function notifyCollaborators($entry, $vars = array()) {
        global $cfg;

        if (!$entry instanceof ThreadEntry
            || !($recipients=$this->getRecipients())
            || !($dept=$this->getDept())
            || !($tpl=$dept->getTemplate())
            || !($msg=$tpl->getActivityNoticeMsgTemplate())
            || !($email=$dept->getEmail())
        ) {
            return;
        }

        $poster = User::lookup($entry->user_id);
        $posterEmail = $poster->getEmail()->address;

        $vars = array_merge($vars, array(
            'message' => (string) $entry,
            'poster' => $poster ?: _S('A collaborator'),
            )
        );

        $msg = $this->replaceVars($msg->asArray(), $vars);

        $attachments = $cfg->emailAttachments()?$entry->getAttachments():array();
        $options = array('thread' => $entry);

        if ($vars['from_name'])
            $options += array('from_name' => $vars['from_name']);

        $skip = array();
        if ($entry instanceof MessageThreadEntry) {
          foreach ($entry->getAllEmailRecipients() as $R) {
                $skip[] = $R->mailbox.'@'.$R->host;
            }
        }

        foreach ($recipients as $key => $recipient) {
            $recipient = $recipient->getContact();

            if(get_class($recipient) == 'TicketOwner')
                $owner = $recipient;

            if ((get_class($recipient) == 'Collaborator' ? $recipient->getUserId() : $recipient->getId()) == $entry->user_id)
                unset($recipients[$key]);
         }

        if (!count($recipients))
            return true;

        //see if the ticket user is a recipient
        if ($owner->getEmail()->address != $poster->getEmail()->address && !in_array($owner->getEmail()->address, $skip))
          $owner_recip = $owner->getEmail()->address;

        //say dear collaborator if the ticket user is not a recipient
        if (!$owner_recip) {
            $nameFormats = array_keys(PersonsName::allFormats());
            $names = array();
            foreach ($nameFormats as $key => $value) {
              $names['recipient.name.' . $value] = __('Collaborator');
            }
            $names = array_merge($names, array('recipient' => $recipient));
            $cnotice = $this->replaceVars($msg, $names);
        }
        //otherwise address email to ticket user
        else
            $cnotice = $this->replaceVars($msg, array('recipient' => $owner));

        $email->send($recipients, $cnotice['subj'], $cnotice['body'], $attachments,
            $options);
    }

    function onMessage($message, $autorespond=true, $reopen=true) {
        global $cfg;

        $this->isanswered = 0;
        $this->lastupdate = SqlFunction::NOW();
        $this->save();


        // Reopen if closed AND reopenable
        // We're also checking autorespond flag because we don't want to
        // reopen closed tickets on auto-reply from end user. This is not to
        // confused with autorespond on new message setting
        if ($reopen && $this->isClosed() && $this->isReopenable())
            $this->reopen();

        if (!$autorespond)
            return;

        // Figure out the user
        if ($this->getOwnerId() == $message->getUserId())
            $user = new TicketOwner(
                    User::lookup($message->getUserId()), $this);
        else
            $user = Collaborator::lookup(array(
                    'user_id' => $message->getUserId(),
                    'thread_id' => $this->getThreadId()));

        /**********   double check auto-response  ************/
        if (!$user)
            $autorespond = false;
        elseif ((Email::getIdByEmail($user->getEmail())))
            $autorespond = false;
        elseif (($dept=$this->getDept()))
            $autorespond = $dept->autoRespONNewMessage();

        if (!$autorespond
            || !$cfg->autoRespONNewMessage()
            || !$message
        ) {
            return;  //no autoresp or alerts.
        }

        $dept = $this->getDept();
        $email = $dept->getAutoRespEmail();

        // If enabled...send confirmation to user. ( New Message AutoResponse)
        if ($email
            && ($tpl=$dept->getTemplate())
            && ($msg=$tpl->getNewMessageAutorepMsgTemplate())
        ) {
            $msg = $this->replaceVars($msg->asArray(),
                array(
                    'recipient' => $user,
                    'signature' => ($dept && $dept->isPublic())?$dept->getSignature():''
                )
            );
            $options = array('thread' => $message);
            if ($message->getEmailMessageId()) {
                $options += array(
                        'inreplyto' => $message->getEmailMessageId(),
                        'references' => $message->getEmailReferences()
                        );
            }

            $email->sendAutoReply($user, $msg['subj'], $msg['body'],
                null, $options);
        }
    }

    function onActivity($vars, $alert=true) {
        global $cfg, $thisstaff;

        //TODO: do some shit
        if (!$alert // Check if alert is enabled
            || !$cfg->alertONNewActivity()
            || !($dept=$this->getDept())
            || !$dept->getNumMembersForAlerts()
            || !($email=$cfg->getAlertEmail())
            || !($tpl = $dept->getTemplate())
            || !($msg=$tpl->getNoteAlertMsgTemplate())
        ) {
            return;
        }

        // Alert recipients
        $recipients = array();

        //Last respondent.
        if ($cfg->alertLastRespondentONNewActivity())
            $recipients[] = $this->getLastRespondent();

        // Assigned staff / team
        if ($cfg->alertAssignedONNewActivity()) {
            if (isset($vars['assignee'])
                    && $vars['assignee'] instanceof Staff)
                 $recipients[] = $vars['assignee'];
            elseif ($this->isOpen() && ($assignee = $this->getStaff()))
                $recipients[] = $assignee;

            if ($team = $this->getTeam())
                $recipients = array_merge($recipients, $team->getMembersForAlerts());
        }

        // Dept manager
        if ($cfg->alertDeptManagerONNewActivity() && $dept && $dept->getManagerId())
            $recipients[] = $dept->getManager();

        $options = array();
        $staffId = $thisstaff ? $thisstaff->getId() : 0;
        if ($vars['threadentry'] && $vars['threadentry'] instanceof ThreadEntry) {
            $options = array('thread' => $vars['threadentry']);

            // Activity details
            if (!$vars['comments'])
                $vars['comments'] = $vars['threadentry'];

            // Staff doing the activity
            $staffId = $vars['threadentry']->getStaffId() ?: $staffId;
        }

        $msg = $this->replaceVars($msg->asArray(),
                array(
                    'note' => $vars['threadentry'], // For compatibility
                    'activity' => $vars['activity'],
                    'comments' => $vars['comments']));

        $isClosed = $this->isClosed();
        $sentlist=array();
        foreach ($recipients as $k=>$staff) {
            if (!is_object($staff)
                // Don't bother vacationing staff.
                || !$staff->isAvailable()
                // No need to alert the poster!
                || $staffId == $staff->getId()
                // No duplicates.
                || isset($sentlist[$staff->getEmail()])
                // Make sure staff has access to ticket
                || ($isClosed && !$this->checkStaffPerm($staff))
            ) {
                continue;
            }
            $alert = $this->replaceVars($msg, array('recipient' => $staff));
            $email->sendAlert($staff, $alert['subj'], $alert['body'], null, $options);
            $sentlist[$staff->getEmail()] = 1;
        }
    }

    function onAssign($assignee, $comments, $alert=true) {
        global $cfg, $thisstaff;

        if ($this->isClosed())
            $this->reopen(); //Assigned tickets must be open - otherwise why assign?

        // Assignee must be an object of type Staff or Team
        if (!$assignee || !is_object($assignee))
            return false;

        $user_comments = (bool) $comments;
        $assigner = $thisstaff ?: _S('SYSTEM (Auto Assignment)');

        //Log an internal note - no alerts on the internal note.
        if ($user_comments) {
            if ($assignee instanceof Staff
                    && $thisstaff
                    // self assignment
                    && $assignee->getId() == $thisstaff->getId())
                $title = sprintf(_S('Ticket claimed by %s'),
                    $thisstaff->getName());
            else
                $title = sprintf(_S('Ticket Assigned to %s'),
                        $assignee->getName());

            $note = $this->logNote($title, $comments, $assigner, false);
        }
        $dept = $this->getDept();
        // See if we need to send alerts
        if (!$alert || !$cfg->alertONAssignment() || !$dept->getNumMembersForAlerts())
            return true; //No alerts!

        if (!$dept
            || !($tpl = $dept->getTemplate())
            || !($email = $dept->getAlertEmail())
        ) {
            return true;
        }

        // Recipients
        $recipients = array();
        if ($assignee instanceof Staff) {
            if ($cfg->alertStaffONAssignment())
                $recipients[] = $assignee;
        } elseif (($assignee instanceof Team) && $assignee->alertsEnabled()) {
            if ($cfg->alertTeamMembersONAssignment() && ($members=$assignee->getMembersForAlerts()))
                $recipients = array_merge($recipients, $members);
            elseif ($cfg->alertTeamLeadONAssignment() && ($lead=$assignee->getTeamLead()))
                $recipients[] = $lead;
        }

        // Get the message template
        if ($recipients
            && ($msg=$tpl->getAssignedAlertMsgTemplate())
        ) {
            $msg = $this->replaceVars($msg->asArray(),
                array('comments' => $comments ?: '',
                      'assignee' => $assignee,
                      'assigner' => $assigner
                )
            );
            // Send the alerts.
            $sentlist = array();
            $options = $note instanceof ThreadEntry
                ? array('thread'=>$note)
                : array();
            foreach ($recipients as $k=>$staff) {
                if (!is_object($staff)
                    || !$staff->isAvailable()
                    || in_array($staff->getEmail(), $sentlist)
                ) {
                    continue;
                }
                $alert = $this->replaceVars($msg, array('recipient' => $staff));
                $email->sendAlert($staff, $alert['subj'], $alert['body'], null, $options);
                $sentlist[] = $staff->getEmail();
            }
        }
        return true;
    }

   function onOverdue($whine=true, $comments="") {
        global $cfg;

        if ($whine && ($sla = $this->getSLA()) && !$sla->alertOnOverdue())
            $whine = false;

        // Check if we need to send alerts.
        if (!$whine
            || !$cfg->alertONOverdueTicket()
            || !($dept = $this->getDept())
            || !$dept->getNumMembersForAlerts()
        ) {
            return true;
        }
        // Get the message template
        if (($tpl = $dept->getTemplate())
            && ($msg=$tpl->getOverdueAlertMsgTemplate())
            && ($email = $dept->getAlertEmail())
        ) {
            $msg = $this->replaceVars($msg->asArray(),
                array('comments' => $comments)
            );
            // Recipients
            $recipients = array();
            // Assigned staff or team... if any
            if ($this->isAssigned() && $cfg->alertAssignedONOverdueTicket()) {
                if ($this->getStaffId()) {
                    $recipients[]=$this->getStaff();
                }
                elseif ($this->getTeamId()
                    && ($team = $this->getTeam())
                    && ($members = $team->getMembersForAlerts())
                ) {
                    $recipients=array_merge($recipients, $members);
                }
            }
            elseif ($cfg->alertDeptMembersONOverdueTicket() && !$this->isAssigned()) {
                // Only alerts dept members if the ticket is NOT assigned.
                foreach ($dept->getMembersForAlerts() as $M)
                    $recipients[] = $M;
            }
            // Always alert dept manager??
            if ($cfg->alertDeptManagerONOverdueTicket()
                && $dept && ($manager=$dept->getManager())
            ) {
                $recipients[]= $manager;
            }
            $sentlist = array();
            foreach ($recipients as $k=>$staff) {
                if (!is_object($staff)
                    || !$staff->isAvailable()
                    || in_array($staff->getEmail(), $sentlist)
                ) {
                    continue;
                }
                $alert = $this->replaceVars($msg, array('recipient' => $staff));
                $email->sendAlert($staff, $alert['subj'], $alert['body'], null);
                $sentlist[] = $staff->getEmail();
            }
        }
        return true;
    }

    // TemplateVariable interface
    function asVar() {
       return $this->getNumber();
    }

    function getVar($tag) {
        global $cfg;

        switch(mb_strtolower($tag)) {
        case 'phone':
        case 'phone_number':
            return $this->getPhoneNumber();
            break;
        case 'auth_token':
            return $this->getOldAuthToken();
            break;
        case 'client_link':
            return sprintf('%s/view.php?t=%s',
                    $cfg->getBaseUrl(), $this->getNumber());
            break;
        case 'staff_link':
            return sprintf('%s/scp/tickets.php?id=%d', $cfg->getBaseUrl(), $this->getId());
            break;
        case 'create_date':
            return new FormattedDate($this->getCreateDate());
            break;
         case 'due_date':
            if ($due = $this->getEstDueDate())
                return new FormattedDate($due);
            break;
        case 'close_date':
            if ($this->isClosed())
                return new FormattedDate($this->getCloseDate());
            break;
        case 'last_update':
            return new FormattedDate($this->lastupdate);
        case 'user':
            return $this->getOwner();
        default:
            if ($a = $this->getAnswer($tag))
                // The answer object is retrieved here which will
                // automatically invoke the toString() method when the
                // answer is coerced into text
                return $a;
        }
    }

    static function getVarScope() {
        $base = array(
            'assigned' => __('Assigned Agent / Team'),
            'close_date' => array(
                'class' => 'FormattedDate', 'desc' => __('Date Closed'),
            ),
            'create_date' => array(
                'class' => 'FormattedDate', 'desc' => __('Date Created'),
            ),
            'dept' => array(
                'class' => 'Dept', 'desc' => __('Department'),
            ),
            'due_date' => array(
                'class' => 'FormattedDate', 'desc' => __('Due Date'),
            ),
            'email' => __('Default email address of ticket owner'),
            'id' => __('Ticket ID (internal ID)'),
            'name' => array(
                'class' => 'PersonsName', 'desc' => __('Name of ticket owner'),
            ),
            'number' => __('Ticket Number'),
            'phone' => __('Phone number of ticket owner'),
            'priority' => array(
                'class' => 'Priority', 'desc' => __('Priority'),
            ),
            'recipients' => array(
                'class' => 'UserList', 'desc' => __('List of all recipient names'),
            ),
            'source' => __('Source'),
            'status' => array(
                'class' => 'TicketStatus', 'desc' => __('Status'),
            ),
            'staff' => array(
                'class' => 'Staff', 'desc' => __('Assigned/closing agent'),
            ),
            'subject' => 'Subject',
            'team' => array(
                'class' => 'Team', 'desc' => __('Assigned/closing team'),
            ),
            'thread' => array(
                'class' => 'TicketThread', 'desc' => __('Ticket Thread'),
            ),
            'topic' => array(
                'class' => 'Topic', 'desc' => __('Help Topic'),
            ),
            // XXX: Isn't lastreponse and lastmessage more useful
            'last_update' => array(
                'class' => 'FormattedDate', 'desc' => __('Time of last update'),
            ),
            'user' => array(
                'class' => 'User', 'desc' => __('Ticket Owner'),
            ),
        );

        $extra = VariableReplacer::compileFormScope(TicketForm::getInstance());
        return $base + $extra;
    }

    // Searchable interface
    static function getSearchableFields() {
        global $thisstaff;

        $base = array(
            'number' => new TextboxField(array(
                'label' => __('Ticket Number')
            )),
            'created' => new DatetimeField(array(
                'label' => __('Create Date'),
                'configuration' => array(
                    'fromdb' => true, 'time' => true,
                    'format' => 'y-MM-dd HH:mm:ss'),
            )),
            'duedate' => new DatetimeField(array(
                'label' => __('Due Date'),
                'configuration' => array(
                    'fromdb' => true, 'time' => true,
                    'format' => 'y-MM-dd HH:mm:ss'),
            )),
            'est_duedate' => new DatetimeField(array(
                'label' => __('SLA Due Date'),
                'configuration' => array(
                    'fromdb' => true, 'time' => true,
                    'format' => 'y-MM-dd HH:mm:ss'),
            )),
            'reopened' => new DatetimeField(array(
                'label' => __('Reopen Date'),
                'configuration' => array(
                    'fromdb' => true, 'time' => true,
                    'format' => 'y-MM-dd HH:mm:ss'),
            )),
            'closed' => new DatetimeField(array(
                'label' => __('Close Date'),
                'configuration' => array(
                    'fromdb' => true, 'time' => true,
                    'format' => 'y-MM-dd HH:mm:ss'),
            )),
            'lastupdate' => new DatetimeField(array(
                'label' => __('Last Update'),
                'configuration' => array(
                    'fromdb' => true, 'time' => true,
                    'format' => 'y-MM-dd HH:mm:ss'),
            )),
            'assignee' => new AssigneeChoiceField(array(
                'label' => __('Assignee'),
            )),
            'staff_id' => new AgentSelectionField(array(
                'label' => __('Assigned Staff'),
                'configuration' => array('staff' => $thisstaff),
            )),
            'team_id' => new TeamSelectionField(array(
                'label' => __('Assigned Team'),
            )),
            'dept_id' => new DepartmentChoiceField(array(
                'label' => __('Department'),
            )),
            'sla_id' => new SLAChoiceField(array(
                'label' => __('SLA Plan'),
            )),
            'topic_id' => new HelpTopicChoiceField(array(
                'label' => __('Help Topic'),
            )),
            'source' => new TicketSourceChoiceField(array(
                'label' => __('Ticket Source'),
            )),
            'isoverdue' => new BooleanField(array(
                'label' => __('Overdue'),
                'descsearchmethods' => array(
                    'set' => '%s',
                    'nset' => 'Not %s'
                    ),
            )),
            'isanswered' => new BooleanField(array(
                'label' => __('Answered'),
                'descsearchmethods' => array(
                    'set' => '%s',
                    'nset' => 'Not %s'
                    ),
            )),
            'isassigned' => new AssignedField(array(
                        'label' => __('Assigned'),
            )),
            'merged' => new MergedField(array(
                'label' => __('Merged'),
            )),
            'linked' => new LinkedField(array(
                'label' => __('Linked'),
            )),
            'thread_count' => new TicketThreadCountField(array(
                        'label' => __('Thread Count'),
            )),
            'attachment_count' => new ThreadAttachmentCountField(array(
                        'label' => __('Attachment Count'),
            )),
            'collaborator_count' => new ThreadCollaboratorCountField(array(
                        'label' => __('Collaborator Count'),
            )),
            'task_count' => new TicketTasksCountField(array(
                        'label' => __('Task Count'),
            )),
            'reopen_count' => new TicketReopenCountField(array(
                        'label' => __('Reopen Count'),
            )),
            'ip_address' => new TextboxField(array(
                'label' => __('IP Address'),
                'configuration' => array('validator' => 'ip'),
            )),
        );
        $tform = TicketForm::getInstance();
        foreach ($tform->getFields() as $F) {
            $fname = $F->get('name') ?: ('field_'.$F->get('id'));
            if (!$F->hasData() || $F->isPresentationOnly() || !$F->isEnabled())
                continue;
            if (!$F->isStorable())
                $base[$fname] = $F;
            else
                $base["cdata__{$fname}"] = $F;
        }
        return $base;
    }

    static function supportsCustomData() {
        return true;
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

        // Only open tickets can be marked overdue
        if (!$this->isOpen())
            return false;

        if ($this->isOverdue())
            return true;

        $this->isoverdue = 1;
        if (!$this->save())
            return false;

        $this->logEvent('overdue');
        $this->onOverdue($whine);

        return true;
    }

    function clearOverdue($save=true) {

        //NOTE: Previously logged overdue event is NOT annuled.
        if ($this->isOverdue())
            $this->isoverdue = 0;

        // clear due date if it's in the past
        if ($this->getDueDate() && Misc::db2gmtime($this->getDueDate()) <= Misc::gmtime())
            $this->duedate = null;

        // Clear SLA if est. due date is in the past
        if ($this->getSLADueDate() && Misc::db2gmtime($this->getSLADueDate()) <= Misc::gmtime())
            $this->est_duedate = null;

        return $save ? $this->save() : true;
    }

    function unlinkChild($parent) {
        $this->setPid(NULL);
        $this->setSort(1);
        $this->setFlag(Ticket::FLAG_LINKED, false);
        $this->save();
        $this->logEvent('unlinked', array('ticket' => sprintf('Ticket #%s', $parent->getNumber()), 'id' => $parent->getId()));
        $parent->logEvent('unlinked', array('ticket' => sprintf('Ticket #%s', $this->getNumber()), 'id' => $this->getId()));
    }

    function unlink() {
        $pid = $this->isChild() ? $this->getPid() : $this->getId();
        $parent = $this->isParent() ? $this : (Ticket::lookup($pid));
        $child = $this->isChild() ? $this : '';
        $children = $this->getChildren();
        $count = count($children);

        if ($children) {
            foreach ($children as $child) {
                $child = Ticket::lookup($child[0]);
                $child->unlinkChild($parent);
                $count--;
            }
        } elseif ($child)
            $child->unlinkChild($parent);

        if ($this->isParent() && $count == 0) {
            $parent->setFlag(Ticket::FLAG_LINKED, false);
            $parent->setFlag(Ticket::FLAG_PARENT, false);
            $parent->save();
        }

        return true;
    }

    static function manageMerge($tickets) {
        global $thisstaff;

        $permission = ($tickets['title'] && $tickets['title'] == 'link') ? (Ticket::PERM_LINK) : (Ticket::PERM_MERGE);
        $eventName = ($tickets['title'] && $tickets['title'] == 'link') ? 'linked' : 'merged';
        //see if any tickets should be unlinked
        if ($tickets['dtids']) {
            foreach($tickets['dtids'] as $key => $value) {
                if (is_numeric($key) && $ticket = Ticket::lookup($value))
                    $ticket->unlink();
            }
            return true;
        } elseif ($tickets['tids']) { //see if any tickets should be merged
            $ticketObjects = array();
            foreach($tickets['tids'] as $key => $value) {
                if ($ticket = Ticket::lookupByNumber($value)) {
                    $ticketObjects[] = $ticket;
                    if (!$ticket->checkStaffPerm($thisstaff, $permission) && !$ticket->getThread()->isReferred())
                       return false;

                    if ($key == 0)
                        $parent = $ticket;
                    //changing from link to merge
                    if (($ticket->isParent() || $ticket->isChild()) &&
                         $ticket->getMergeType() == 'visual' && $tickets['combine'] != 2 ||
                        ($tickets['combine'] == 2 && !$parent->isParent() && $parent->isChild())) { //changing link parent
                            $ticket->unlink();
                            $changeParent = true;
                    }

                    if ($ticket->getMergeType() == 'visual') {
                        $ticket->setSort($key);
                        $ticket->save();
                    }

                    if ($parent && $parent->getId() != $ticket->getId()) {
                        if (($changeParent) || ($parent->isParent() && $ticket->getMergeType() == 'visual' && !$ticket->isChild()) || //adding to link/merge
                           (!$parent->isParent() && !$ticket->isChild())) { //creating fresh link/merge
                               $parent->logEvent($eventName, array('ticket' => sprintf('Ticket #%s', $ticket->getNumber()),  'id' => $ticket->getId()));
                               $ticket->logEvent($eventName, array('ticket' => sprintf('Ticket #%s', $parent->getNumber()),  'id' => $parent->getId()));
                               if ($ticket->getPid() != $parent->getId())
                                   $ticket->setPid($parent->getId());
                               $parent->setMergeType($tickets['combine'], true);
                               $ticket->setMergeType($tickets['combine']);

                               //referrals for merged tickets
                               if ($parent->getDeptId() != ($ticketDeptId = $ticket->getDeptId()) && $tickets['combine'] != 2) {
                                   $refDept = $ticket->getDept();
                                   $parent->getThread()->refer($refDept);
                                   $evd = array('dept' => $ticketDeptId);
                                   $parent->logEvent('referred', $evd);
                               }
                        }
                    //switch between combine and separate
                    } elseif ($parent->isParent() && $ticket->getMergeType() != 'visual' && $parent->getId() != $ticket->getId()) {
                        $ticket->setMergeType($tickets['combine']);
                    } elseif ($parent->isParent() && $ticket->getMergeType() != 'visual' && $parent->getId() == $ticket->getId())
                        $parent->setMergeType($tickets['combine'], true);
                }
            }
        }
        return $ticketObjects;
    }

    static function merge($tickets) {
        $options = $tickets;
        if (!$tickets = self::manageMerge($tickets))
            return false;
        if (is_bool($tickets))
            return true;

        $children = array();
        foreach ($tickets as $ticket) {
            if ($ticket->isParent())
                $parent = $ticket;
            else
                $children[] = $ticket;
        }

        if ($parent && $parent->getMergeType() != 'visual') {
            $errors = array();
            foreach ($children as $child) {
                if ($options['participants'] == 'all' && $collabs = $child->getCollaborators()) {
                    foreach ($collabs as $collab) {
                        $collab = $collab->getUser();
                        if ($collab->getId() != $parent->getOwnerId())
                            $parent->addCollaborator($collab, array(), $errors);
                    }
                }
                $cUser = $child->getUser();
                if ($cUser->getId() != $parent->getOwnerId())
                    $parent->addCollaborator($cUser, array(), $errors);
                $parentThread = $parent->getThread();

                $deletedChild = Thread::objects()
                    ->filter(array('extra__contains'=>'"ticket_id":'.$child->getId()))
                    ->values_flat('id', 'extra')
                    ->first();
                if ($deletedChild) {
                    $extraThread = Thread::lookup($deletedChild[0]);
                    $extraThread->setExtra($parentThread, array('extra' => $deletedChild[1], 'threadId' => $extraThread->getId()));
                }

                if ($child->getThread())
                    $child->getThread()->setExtra($parentThread);

                $child->setMergeType($options['combine']);
                $child->setStatus(intval($options['childStatusId']), false, $errors, true, true); //force close status for children

                if ($options['parentStatusId'])
                    $parent->setStatus(intval($options['parentStatusId']));

                if ($options['delete-child'] || $options['move-tasks']) {
                    if ($tasks = Task::objects()
                        ->filter(array('object_id' => $child->getId()))
                        ->values_flat('id')) {
                        foreach ($tasks as $key => $tid) {
                            $task = Task::lookup($tid[0]);
                            $task->object_id = $parent->getId();
                            $task->save();
                        }
                    }
                }

                if ($options['delete-child'])
                     $child->delete();
            }
            return $parent;
        }
        return false;
    }

    function getRelatedTickets() {
        return sprintf('<tr>
            <td width="8px">&nbsp;</td>
            <td>
                <a class="Icon strtolower(%s) Ticket preview"
                   data-preview="#tickets/%d/preview"
                   href="tickets.php?id=%d">%s</a>
            </td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
        </tr>',
        strtolower($this->getSource()), $this->getId(), $this->getId(), $this->getNumber(), $this->getSubject(),
            $this->getDeptName(), $this->getAssignee(), Format::datetime($this->getCreateDate()));
    }

    function hasReferral($object, $type) {
        if (($referral=$this->getThread()->getReferral($object->getId(), $type)))
            return $referral;

        return false;
    }

    //Dept Transfer...with alert.. done by staff
    function transfer(TransferForm $form, &$errors, $alert=true) {
        global $thisstaff, $cfg;

        // Check if staff can do the transfer
        if (!$this->checkStaffPerm($thisstaff, Ticket::PERM_TRANSFER))
            return false;

        $cdept = $this->getDept(); // Current department
        $dept = $form->getDept(); // Target department
        if (!$dept || !($dept instanceof Dept))
            $errors['dept'] = __('Department selection required');
        elseif ($dept->getid() == $this->getDeptId())
            $errors['dept'] = sprintf(
                    __('%s already in the department'), __('Ticket'));
        else {
            $this->dept_id = $dept->getId();

            // Make sure the new department allows assignment to the
            // currently assigned agent (if any)
            if ($this->isAssigned()
                && ($staff=$this->getStaff())
                && $dept->assignMembersOnly()
                && !$dept->isMember($staff)
            ) {
                $this->staff_id = 0;
            }
        }

        if ($errors || !$this->save(true))
            return false;

        // Reopen ticket if closed
        if ($this->isClosed())
            $this->reopen();

        // Set SLA of the new department
        if (!$this->getSLAId() || $this->getSLA()->isTransient())
            if (($slaId=$this->getDept()->getSLAId()))
                $this->selectSLAId($slaId);

        // Log transfer event
        $this->logEvent('transferred', array('dept' => $dept->getName()));

        if (($referral=$this->hasReferral($dept,ObjectModel::OBJECT_TYPE_DEPT)))
            $referral->delete();

        // Post internal note if any
        $note = null;
        $comments = $form->getField('comments')->getClean();
        if ($comments) {
            $title = sprintf(__('%1$s transferred from %2$s to %3$s'),
                    __('Ticket'),
                   $cdept->getName(),
                    $dept->getName());

            $_errors = array();
            $note = $this->postNote(
                    array('note' => $comments, 'title' => $title),
                    $_errors, $thisstaff, false);
        }

        if ($form->refer() && $cdept)
            $this->getThread()->refer($cdept);

        //Send out alerts if enabled AND requested
        if (!$alert || !$cfg->alertONTransfer() || !$dept->getNumMembersForAlerts())
            return true; //no alerts!!

         if (($email = $dept->getAlertEmail())
             && ($tpl = $dept->getTemplate())
             && ($msg=$tpl->getTransferAlertMsgTemplate())
         ) {
            $msg = $this->replaceVars($msg->asArray(),
                array('comments' => $note, 'staff' => $thisstaff));
            // Recipients
            $recipients = array();
            // Assigned staff or team... if any
            if($this->isAssigned() && $cfg->alertAssignedONTransfer()) {
                if($this->getStaffId())
                    $recipients[] = $this->getStaff();
                elseif ($this->getTeamId()
                    && ($team=$this->getTeam())
                    && ($members=$team->getMembersForAlerts())
                ) {
                    $recipients = array_merge($recipients, $members);
                }
            }
            elseif ($cfg->alertDeptMembersONTransfer() && !$this->isAssigned()) {
                // Only alerts dept members if the ticket is NOT assigned.
                foreach ($dept->getMembersForAlerts() as $M)
                    $recipients[] = $M;
            }

            // Always alert dept manager??
            if ($cfg->alertDeptManagerONTransfer()
                && $dept
                && ($manager=$dept->getManager())
            ) {
                $recipients[] = $manager;
            }
            $sentlist = $options = array();
            if ($note) {
                $options += array('thread'=>$note);
            }
            foreach ($recipients as $k=>$staff) {
                if (!is_object($staff)
                    || !$staff->isAvailable()
                    || in_array($staff->getEmail(), $sentlist)
                ) {
                    continue;
                }
                $alert = $this->replaceVars($msg, array('recipient' => $staff));
                $email->sendAlert($staff, $alert['subj'], $alert['body'], null, $options);
                $sentlist[] = $staff->getEmail();
            }
         }

         return true;
    }

    function claim(ClaimForm $form, &$errors) {
        global $thisstaff;

        $dept = $this->getDept();
        $assignee = $form->getAssignee();
        if (!($assignee instanceof Staff)
                || !$thisstaff
                || $thisstaff->getId() != $assignee->getId()) {
            $errors['err'] = __('Unknown assignee');
        } elseif (!$assignee->isAvailable()) {
            $errors['err'] = __('Agent is unavailable for assignment');
        } elseif (!$dept->canAssign($assignee)) {
            $errors['err'] = __('Permission denied');
        }

        if ($errors)
            return false;

        return $this->assignToStaff($assignee, $form->getComments(), false);
    }

    function assignToStaff($staff, $note, $alert=true, $user=null) {

        if(!is_object($staff) && !($staff = Staff::lookup($staff)))
            return false;

        if (!$staff->isAvailable() || !$this->setStaffId($staff->getId()))
            return false;

        $this->onAssign($staff, $note, $alert);

        global $thisstaff;
        $data = array();
        if ($thisstaff && $staff->getId() == $thisstaff->getId())
            $data['claim'] = true;
        else
            $data['staff'] = $staff->getId();

        $this->logEvent('assigned', $data, $user);

        $key = $data['claim'] ? 'claim' : 'auto';
        $type = array('type' => 'assigned', $key => true);
        Signal::send('object.edited', $this, $type);

        if (($referral=$this->hasReferral($staff,ObjectModel::OBJECT_TYPE_STAFF)))
            $referral->delete();

        return true;
    }

    function assignToTeam($team, $note, $alert=true, $user=null) {

        if(!is_object($team) && !($team = Team::lookup($team)))
            return false;

        if (!$team->isActive() || !$this->setTeamId($team->getId()))
            return false;

        //Clear - staff if it's a closed ticket
        //  staff_id is overloaded -> assigned to & closed by.
        if ($this->isClosed())
            $this->setStaffId(0);

        $this->onAssign($team, $note, $alert);
        $this->logEvent('assigned', array('team' => $team->getId()), $user);

        if (($referral=$this->hasReferral($team,ObjectModel::OBJECT_TYPE_TEAM)))
            $referral->delete();

        return true;
    }

    function assign(AssignmentForm $form, &$errors, $alert=true) {
        global $thisstaff;

        $evd = array();
        $audit = array();
        $refer = null;
        $dept = $this->getDept();
        $assignee = $form->getAssignee();
        if ($assignee instanceof Staff) {
            if ($this->getStaffId() == $assignee->getId()) {
                $errors['assignee'] = sprintf(__('%s already assigned to %s'),
                        __('Ticket'),
                        __('the agent')
                        );
            } elseif (!$assignee->isAvailable()) {
                $errors['assignee'] = __('Agent is unavailable for assignment');
            } elseif (!$dept->canAssign($assignee)) {
                $errors['err'] = __('Permission denied');
            } else {
                $refer = $this->staff ?: null;
                $this->staff_id = $assignee->getId();
                if ($thisstaff && $thisstaff->getId() == $assignee->getId()) {
                    $alert = false;
                    $evd['claim'] = true;
                    $audit = array('staff' => $assignee->getName()->name,'claim' => true);
                } else {
                    $evd['staff'] = array($assignee->getId(), (string) $assignee->getName()->getOriginal());
                    $audit = array('staff' => $assignee->getName()->name);
                }

                if (($referral=$this->hasReferral($assignee,ObjectModel::OBJECT_TYPE_STAFF)))
                    $referral->delete();
            }
        } elseif ($assignee instanceof Team) {
            if ($this->getTeamId() == $assignee->getId()) {
                $errors['assignee'] = sprintf(__('%s already assigned to %s'),
                        __('Ticket'),
                        __('the team')
                        );
            } elseif (!$dept->canAssign($assignee)) {
                $errors['err'] = __('Permission denied');
            } else {
                $refer = $this->team ?: null;
                $this->team_id = $assignee->getId();
                $evd = array('team' => $assignee->getId());
                $audit = array('team' => $assignee->getName());
                if (($referral=$this->hasReferral($assignee,ObjectModel::OBJECT_TYPE_TEAM)))
                    $referral->delete();
            }
        } else {
            $errors['assignee'] = __('Unknown assignee');
        }

        if ($errors || !$this->save(true))
            return false;

        $this->logEvent('assigned', $evd);

        $type = array('type' => 'assigned');
        $type += $audit;
        Signal::send('object.edited', $this, $type);

        $this->onAssign($assignee, $form->getComments(), $alert);

        if ($refer && $form->refer())
            $this->getThread()->refer($refer);

        return true;
    }

    // Unassign primary assignee
    function unassign() {
        // We can't release what is not assigned buddy!
        if (!$this->isAssigned())
            return true;

        // We can only unassigned OPEN tickets.
        if ($this->isClosed())
            return false;

        // Unassign staff (if any)
        if ($this->getStaffId() && !$this->setStaffId(0))
            return false;

        // Unassign team (if any)
        if ($this->getTeamId() && !$this->setTeamId(0))
            return false;

        return true;
    }

    function release(?array $info=array(), &$errors) {
        if (isset($info['sid']) && isset($info['tid']))
            return $this->unassign();
        elseif (isset($info['sid']) && $this->setStaffId(0))
            return true;
        elseif (isset($info['tid']) && $this->setTeamId(0))
            return true;

        return false;
    }

    function refer(ReferralForm $form, &$errors, $alert=true) {
        global $thisstaff;

        $evd = array();
        $audit = array();
        $referee = $form->getReferee();
        switch (true) {
        case $referee instanceof Staff:
            $dept = $this->getDept();
            if ($this->getStaffId() == $referee->getId()) {
                $errors['agent'] = sprintf(__('%s is assigned to %s'),
                        __('Ticket'),
                        __('the agent')
                        );
            } elseif(!$referee->isAvailable()) {
                $errors['agent'] = sprintf(__('Agent is unavailable for %s'),
                        __('referral'));
            } else {
                $evd['staff'] = array($referee->getId(), (string) $referee->getName()->getOriginal());
                $audit = array('staff' => $referee->getName()->name);
            }
            break;
        case $referee instanceof Team:
            if ($this->getTeamId() == $referee->getId()) {
                $errors['team'] = sprintf(__('%s is assigned to %s'),
                        __('Ticket'),
                        __('the team')
                        );
            } else {
                //TODO::
                $evd = array('team' => $referee->getId());
                $audit = array('team' => $referee->getName());
            }
            break;
        case $referee instanceof Dept:
            if ($this->getDeptId() == $referee->getId()) {
                $errors['dept'] = sprintf(__('%s is already in %s'),
                        __('Ticket'),
                        __('the department')
                        );
            } else {
                //TODO::
                $evd = array('dept' => $referee->getId());
                $audit = array('dept' => $referee->getName());
            }
            break;
        default:
            $errors['target'] = __('Unknown referral');
        }

        if (!$errors && !$this->getThread()->refer($referee))
            $errors['err'] = __('Unable to refer ticket');

        if ($errors)
            return false;

        $this->logEvent('referred', $evd);

        $type = array('type' => 'referred');
        $type += $audit;
        Signal::send('object.edited', $this, $type);

        return true;
    }

    function systemReferral($emails) {
        global $cfg;

        if (!$thread = $this->getThread())
            return;

        $eventEmails = array();
        $events = ThreadEvent::objects()
            ->filter(array('thread_id' => $thread->getId(),
                           'event__name' => 'transferred'));
        if ($events) {
            foreach ($events as $e) {
                $emailId = Dept::getEmailIdById($e->dept_id) ?: $cfg->getDefaultEmailId();
                if (!in_array($emailId, $eventEmails))
                    $eventEmails[] = $emailId;
            }
        }

        foreach ($emails as $id) {
            $refer = $eventEmails ? !in_array($id, $eventEmails) : true;
            if ($id != $this->email_id
                    && $refer
                    && ($email=Email::lookup($id))
                    && $this->getDeptId() != $email->getDeptId()
                    && ($dept=Dept::lookup($email->getDeptId()))
                    && $this->getThread()->refer($dept)
                    )
                $this->logEvent('referred',
                            array('dept' => $dept->getId()));
        }

    }

    //Change ownership
    function changeOwner($user) {
        global $thisstaff;

        if (!$user
            || ($user->getId() == $this->getOwnerId())
            || !($this->checkStaffPerm($thisstaff,
                Ticket::PERM_EDIT))
        ) {
            return false;
        }

        $this->user_id = $user->getId();
        if (!$this->save())
            return false;

        unset($this->user);
        $this->collaborators = null;
        $this->recipients = null;

        // Remove the new owner from list of collaborators
        $c = Collaborator::lookup(array(
            'user_id' => $user->getId(),
            'thread_id' => $this->getThreadId()
        ));
        if ($c)
            $c->delete();

        $this->logEvent('edited', array('owner' => $user->getId(), 'fields' => array('Ticket Owner' => $user->getName()->name)));

        return true;
    }

    // Insert message from client
    function postMessage($vars, $origin='', $alerts=true) {
        global $cfg;

        if ($origin)
            $vars['origin'] = $origin;
        if (isset($vars['ip']))
            $vars['ip_address'] = $vars['ip'];
        elseif (!$vars['ip_address'] && $_SERVER['REMOTE_ADDR'])
            $vars['ip_address'] = $_SERVER['REMOTE_ADDR'];

        //see if message should go to a parent ticket
        if ($this->isChild() && $this->getMergeType() != 'visual')
            $parent = self::lookup($this->getPid());

        $ticket = $parent ?: $this;
        $errors = array();
        if ($vars['userId'] != $ticket->user_id) {
            if ($vars['userId']) {
                $user = User::lookup($vars['userId']);
             } elseif ($vars['header']
                    && ($hdr= Mail_Parse::splitHeaders($vars['header'], true))
                    && $hdr['From']
                    && ($addr= Mail_Parse::parseAddressList($hdr['From']))) {
                $info = array(
                        'name' => $addr[0]->personal,
                        'email' => $addr[0]->mailbox.'@'.$addr[0]->host);
                if ($user=User::fromVars($info))
                    $vars['userId'] = $user->getId();
            }

            if ($user) {
                $v = array();
                $c = $ticket->getThread()->addCollaborator($user, $v,
                        $errors);
            }
       }

      // Get active recipients of the response
      // Initial Message from Tickets created by Agent
      if ($vars['reply-to'])
          $recipients = $ticket->getRecipients($vars['reply-to'], $vars['ccs']);
      // Messages from Web Portal
      elseif (strcasecmp($origin, 'email')) {
          $recipients = $ticket->getRecipients('all');
          foreach ($recipients as $key => $recipient) {
              if (!$recipientContact = $recipient->getContact())
                  continue;

              $userId = $recipientContact->getUserId() ?: $recipientContact->getId();
              // Do not list the poster as a recipient
              if ($userId == $vars['userId'])
                unset($recipients[$key]);
          }
      }
      if ($recipients && $recipients instanceof MailingList)
          $vars['thread_entry_recipients'] = $recipients->getEmailAddresses();

        if (!($message = $ticket->getThread()->addMessage($vars, $errors)))
            return null;

        $ticket->setLastMessage($message);

        // Add email recipients as collaborators...
        if ($vars['recipients']
            && (strtolower($origin) != 'email' || ($cfg && $cfg->addCollabsViaEmail()))
            //Only add if we have a matched local address
            && $vars['to-email-id']
        ) {
            //New collaborators added by other collaborators are disable --
            // requires staff approval.
            $info = array(
                'isactive' => ($message->getUserId() == $ticket->getUserId())? 1: 0);
            $collabs = array();
            foreach ($vars['recipients'] as $recipient) {
                // Skip virtual delivered-to addresses
                if (strcasecmp($recipient['source'], 'delivered-to') === 0)
                    continue;

                if (($cuser=User::fromVars($recipient))) {
                  if (!$existing = Collaborator::getIdByUserId($cuser->getId(), $ticket->getThreadId())) {
                    $_errors = array();
                    if ($c=$ticket->addCollaborator($cuser, $info, $_errors, false)) {
                      $c->setCc($c->active);

                      // FIXME: This feels very unwise — should be a
                      // string indexed array for future
                      $collabs[$c->user_id] = array(
                          'name' => $c->getName()->getOriginal(),
                          'src' => $recipient['source'],
                      );
                    }
                  }

                }

            }
            // TODO: Can collaborators add others?
            if ($collabs) {
                $ticket->logEvent('collab', array('add' => $collabs), $message->user);
                $type = array('type' => 'collab', 'add' => $collabs);
                Signal::send('object.created', $ticket, $type);
            }
        }

        // Do not auto-respond to bounces and other auto-replies
        $autorespond = isset($vars['mailflags'])
                ? !$vars['mailflags']['bounce'] && !$vars['mailflags']['auto-reply']
                : true;
        $reopen = $autorespond; // Do not reopen bounces
        if ($autorespond && $message->isBounceOrAutoReply())
            $autorespond = $reopen= false;
        elseif ($autorespond && isset($vars['autorespond']))
            $autorespond = $vars['autorespond'];

        $ticket->onMessage($message, ($autorespond && $alerts), $reopen); //must be called b4 sending alerts to staff.

        if ($autorespond && $alerts
            && $cfg && $cfg->notifyCollabsONNewMessage()
            && strcasecmp($origin, 'email')) {
          //when user replies, this is where collabs notified
          $ticket->notifyCollaborators($message, array('signature' => ''));
        }

        if (!($alerts && $autorespond))
            return $message; //Our work is done...

        $dept = $ticket->getDept();
        $variables = array(
            'message' => $message,
            'poster' => ($vars['poster'] ? $vars['poster'] : $ticket->getName())
        );

        $options = array('thread'=>$message);
        // If enabled...send alert to staff (New Message Alert)
        if ($cfg->alertONNewMessage()
            && ($email = $dept->getAlertEmail())
            && ($tpl = $dept->getTemplate())
            && ($msg = $tpl->getNewMessageAlertMsgTemplate())
        ) {
            $msg = $ticket->replaceVars($msg->asArray(), $variables);
            // Build list of recipients and fire the alerts.
            $recipients = array();
            //Last respondent.
            if ($cfg->alertLastRespondentONNewMessage() && ($lr = $ticket->getLastRespondent()))
                $recipients[] = $lr;

            //Assigned staff if any...could be the last respondent
            if ($cfg->alertAssignedONNewMessage() && $ticket->isAssigned()) {
                if ($staff = $ticket->getStaff())
                    $recipients[] = $staff;
                elseif ($team = $ticket->getTeam())
                    $recipients = array_merge($recipients, $team->getMembersForAlerts());
            }

            // Dept manager
            if ($cfg->alertDeptManagerONNewMessage()
                && $dept
                && ($manager = $dept->getManager())
            ) {
                $recipients[]=$manager;
            }

            // Account manager
            if ($cfg->alertAcctManagerONNewMessage()
                    && ($org = $this->getOwner()->getOrganization())
                    && ($acct_manager = $org->getAccountManager())) {
                if ($acct_manager instanceof Team)
                    $recipients = array_merge($recipients, $acct_manager->getMembersForAlerts());
                else
                    $recipients[] = $acct_manager;
            }

            $sentlist = array(); //I know it sucks...but..it works.
            foreach ($recipients as $k=>$staff) {
                if (!$staff || !$staff->getEmail()
                    || !$staff->isAvailable()
                    || in_array($staff->getEmail(), $sentlist)
                ) {
                    continue;
                }
                $alert = $this->replaceVars($msg, array('recipient' => $staff));
                $email->sendAlert($staff, $alert['subj'], $alert['body'], null, $options);
                $sentlist[] = $staff->getEmail();
            }
        }
        $type = array('type' => 'message', 'uid' => $vars['userId']);
        Signal::send('object.created', $this, $type);

        return $message;
    }

    function postCannedReply($canned, $message, $alert=true) {
        global $ost, $cfg;

        if ((!is_object($canned) && !($canned=Canned::lookup($canned)))
            || !$canned->isEnabled()
        ) {
            return false;
        }
        $files = array();
        foreach ($canned->attachments->getAll() as $att) {
            $files[] = array('id' => $att->file_id, 'name' => $att->getName());
            $_SESSION[':cannedFiles'][$att->file_id] = $att->getName();
        }

        if ($cfg->isRichTextEnabled())
            $response = new HtmlThreadEntryBody(
                $this->replaceVars($canned->getHtml()));
        else
            $response = new TextThreadEntryBody(
                $this->replaceVars($canned->getPlainText()));

        $info = array('msgId' => $message instanceof ThreadEntry ? $message->getId() : 0,
                      'poster' => __('SYSTEM (Canned Reply)'),
                      'response' => $response,
                      'files' => $files
        );
        $errors = array();
        if (!($response=$this->postReply($info, $errors, false, false)))
            return null;

        $this->markUnAnswered();

        if (!$alert)
            return $response;

        $dept = $this->getDept();

        if (($email=$dept->getEmail())
            && ($tpl = $dept->getTemplate())
            && ($msg=$tpl->getAutoReplyMsgTemplate())
        ) {
            if ($dept && $dept->isPublic())
                $signature=$dept->getSignature();
            else
                $signature='';

            $msg = $this->replaceVars($msg->asArray(),
                array(
                    'response' => $response,
                    'signature' => $signature,
                    'recipient' => $this->getOwner(),
                )
            );
            $attachments = ($cfg->emailAttachments() && $files)
                ? $response->getAttachments() : array();

            $options = array('thread' => $response);
            if (($message instanceof ThreadEntry)
                    && $message->getUserId() == $this->getUserId()
                    && ($mid=$message->getEmailMessageId())) {
                $options += array(
                        'inreplyto' => $mid,
                        'references' => $message->getEmailReferences()
                        );
            }

            $email->sendAutoReply($this->getOwner(), $msg['subj'], $msg['body'], $attachments,
                $options);
        }
        return $response;
    }

    /* public */
    function postReply($vars, &$errors, $alert=true, $claim=true) {
        global $thisstaff, $cfg;

        if (!$vars['poster'] && $thisstaff)
            $vars['poster'] = $thisstaff;

        if (!$vars['staffId'] && $thisstaff)
            $vars['staffId'] = $thisstaff->getId();

        if (!$vars['ip_address'] && $_SERVER['REMOTE_ADDR'])
            $vars['ip_address'] = $_SERVER['REMOTE_ADDR'];

        // clear db cache
        $this->getThread()->_collaborators = null;

        // Get active recipients of the response
        $recipients = $this->getRecipients($vars['reply-to'], $vars['ccs']);
        if ($recipients instanceof MailingList)
            $vars['thread_entry_recipients'] = $recipients->getEmailAddresses();

        if (!($response = $this->getThread()->addResponse($vars, $errors)))
            return null;

        $dept = $this->getDept();
        $assignee = $this->getStaff();
        // Set status if new is selected
        if ($vars['reply_status_id']
                && ($status = TicketStatus::lookup($vars['reply_status_id']))
                && $status->getId() != $this->getStatusId())
            $this->setStatus($status);

        // Claim on response bypasses the department assignment restrictions
        $claim = ($claim
                && $cfg->autoClaimTickets()
                && !$dept->disableAutoClaim());
        if ($claim && $thisstaff && $this->isOpen() && !$this->getStaffId()) {
            $this->setStaffId($thisstaff->getId()); //direct assignment;
        }

        $this->onResponse($response, array('assignee' => $assignee)); //do house cleaning..

        $this->lastrespondent = $response->staff;

        $type = array('type' => 'message');
        Signal::send('object.created', $this, $type);

        /* email the user??  - if disabled - then bail out */
        if (!$alert)
            return $response;

        //allow agent to send from different dept email
        if (!$vars['from_email_id']
                ||  !($email = Email::lookup($vars['from_email_id'])))
            $email = $dept->getEmail();

        $options = array('thread'=>$response);
        $signature = $from_name = '';
        if ($thisstaff && $vars['signature']=='mine')
            $signature=$thisstaff->getSignature();
        elseif ($vars['signature']=='dept' && $dept->isPublic())
            $signature=$dept->getSignature();

        if ($thisstaff && ($type=$thisstaff->getReplyFromNameType())) {
            switch ($type) {
                case 'mine':
                    if (!$cfg->hideStaffName())
                        $from_name = (string) $thisstaff->getName();
                    break;
                case 'dept':
                    if ($dept->isPublic())
                        $from_name = $dept->getName();
                    break;
                case 'email':
                default:
                    $from_name =  $email->getName();
            }
            if ($from_name)
                $options += array('from_name' => $from_name);
        }

        $variables = array(
            'response' => $response,
            'signature' => $signature,
            'staff' => $thisstaff,
            'poster' => $thisstaff
        );

        if ($email
                && $recipients
                && ($tpl = $dept->getTemplate())
                && ($msg=$tpl->getReplyMsgTemplate())) {

            // Add ticket link (possibly with authtoken) if the ticket owner
            // is the only recipient on a ticket with collabs
            if (count($recipients) == 1
                    && $this->getNumCollaborators()
                    && ($contact = $recipients->offsetGet(0)->getContact())
                    && ($contact instanceof TicketOwner))
                $variables['recipient.ticket_link'] =
                    $contact->getTicketLink();

            $msg = $this->replaceVars($msg->asArray(),
                $variables + array('recipient' => $this->getOwner())
            );

            // Attachments
            $attachments = $cfg->emailAttachments() ?
                $response->getAttachments() : array();

            //Send email to recepients
            $email->send($recipients, $msg['subj'], $msg['body'],
                    $attachments, $options);
        }

        return $response;
    }

    //Activity log - saved as internal notes WHEN enabled!!
    function logActivity($title, $note) {
        return $this->logNote($title, $note, 'SYSTEM', false);
    }

    // History log -- used for statistics generation (pretty reports)
    function logEvent($state, $data=null, $user=null, $annul=null) {
        switch ($state) {
            case 'collab':
            case 'transferred':
                $type = $data;
                $type['type'] = $state;
                break;
            case 'edited':
                $type = array('type' => $state, 'fields' => $data['fields'] ? $data['fields'] : $data);
                break;
            case 'assigned':
            case 'referred':
                break;
            default:
                $type = array('type' => $state);
                break;
        }
        if ($type)
            Signal::send('object.created', $this, $type);
        if ($this->getThread())
            $this->getThread()->getEvents()->log($this, $state, $data, $user, $annul);
    }

    //Insert Internal Notes
    function logNote($title, $note, $poster='SYSTEM', $alert=true) {
        // Unless specified otherwise, assume HTML
        if ($note && is_string($note))
            $note = new HtmlThreadEntryBody($note);

        $errors = array();
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

    function postNote($vars, &$errors, $poster=false, $alert=true) {
        global $cfg, $thisstaff;

        //Who is posting the note - staff or system? or user?
        if ($vars['staffId'] && !$poster)
            $poster = Staff::lookup($vars['staffId']);

        $vars['staffId'] = $vars['staffId'] ?: 0;
        if ($poster && is_object($poster) && !$vars['userId']) {
            $vars['staffId'] = $poster->getId();
            $vars['poster'] = $poster->getName();
        }
        elseif ($poster) { //string
            $vars['poster'] = $poster;
        }
        elseif (!isset($vars['poster'])) {
            $vars['poster'] = 'SYSTEM';
        }
        if (!$vars['ip_address'] && $_SERVER['REMOTE_ADDR'])
            $vars['ip_address'] = $_SERVER['REMOTE_ADDR'];

        if (!($note=$this->getThread()->addNote($vars, $errors)))
            return null;

        $alert = $alert && (
            isset($vars['mailflags'])
            // No alerts for bounce and auto-reply emails
            ? !$vars['mailflags']['bounce'] && !$vars['mailflags']['auto-reply']
            : true
        );

        // Get assigned staff just in case the ticket is closed.
        $assignee = $this->getStaff();

        if ($vars['note_status_id']
            && ($status=TicketStatus::lookup($vars['note_status_id']))
        ) {
            $this->setStatus($status);
        }

        $activity = $vars['activity'] ?: _S('New Internal Note');
        $this->onActivity(array(
            'activity' => $activity,
            'threadentry' => $note,
            'assignee' => $assignee
        ), $alert);

        $type = array('type' => 'note');
        Signal::send('object.created', $this, $type);

        return $note;
    }

    // Threadable interface
    function postThreadEntry($type, $vars, $options=array()) {
        $errors = array();
        switch ($type) {
        case 'M':
            return $this->postMessage($vars, $vars['origin']);
        case 'N':
            return $this->postNote($vars, $errors);
        case 'R':
            return $this->postReply($vars, $errors);
        }
    }

    // Print ticket... export the ticket thread as PDF.
    function pdfExport($psize='Letter', $notes=false, $events=false) {
        global $thisstaff;

        require_once(INCLUDE_DIR.'class.pdf.php');
        if (!is_string($psize)) {
            if ($_SESSION['PAPER_SIZE'])
                $psize = $_SESSION['PAPER_SIZE'];
            elseif (!$thisstaff || !($psize = $thisstaff->getDefaultPaperSize()))
                $psize = 'Letter';
        }

        $pdf = new Ticket2PDF($this, $psize, $notes, $events);
        $name = 'Ticket-'.$this->getNumber().'.pdf';
        Http::download($name, 'application/pdf', $pdf->output($name, 'S'));
        //Remember what the user selected - for autoselect on the next print.
        $_SESSION['PAPER_SIZE'] = $psize;
        exit;
    }

    function zipExport($notes=true, $tasks=false) {
        $exporter = new TicketZipExporter($this);
        $exporter->download(['notes'=>$notes, 'tasks'=>$tasks]);
        exit;
    }

    function delete($comments='') {
        global $ost, $thisstaff;

        //delete just orphaned ticket thread & associated attachments.
        // Fetch thread prior to removing ticket entry
        $t = $this->getThread();

        if (!parent::delete())
            return false;

        //deleting parent ticket
        if ($children = $this->getChildren()) {
            foreach ($children as $childId) {
                if (!($child = Ticket::lookup($childId[0])))
                    continue;

                $child->setPid(NULL);
                $child->setMergeType(3);
                $child->save();
                $childThread = $child->getThread();
                $childThread->object_type = 'T';
                $childThread->save();
            }
        }

        //deleting child ticket
        if ($this->isChild()) {
            $parent = Ticket::lookup($this->ticket_pid);
            if ($parent->isParent() && count($parent->getChildren()) == 0) {
                $parent->setMergeType(3);
                $parent->save();
            }
        } else
            $t->delete();

        $this->logEvent('deleted');

        foreach (DynamicFormEntry::forTicket($this->getId()) as $form)
            $form->delete();

        $this->deleteDrafts();

        if ($this->cdata)
            $this->cdata->delete();

        // Log delete
        $log = sprintf(__('Ticket #%1$s deleted by %2$s'),
            $this->getNumber(),
            $thisstaff ? $thisstaff->getName() : __('SYSTEM')
        );
        if ($comments)
            $log .= sprintf('<hr>%s', $comments);

        $ost->logDebug(
            sprintf( __('Ticket #%s deleted'), $this->getNumber()),
            $log
        );
        return true;
    }

    function deleteDrafts() {
        Draft::deleteForNamespace('ticket.%.' . $this->getId());
    }

    function save($refetch=false) {
        if ($this->dirty) {
            $this->updated = SqlFunction::NOW();
            if (isset($this->dirty['status_id']) && PHP_SAPI !== 'cli')
                // Refetch the queue counts
                SavedQueue::clearCounts();
        }
        return parent::save($this->dirty || $refetch);
    }

    function update($vars, &$errors) {
        global $cfg, $thisstaff;

        if (!$cfg
            || !($this->checkStaffPerm($thisstaff,
                Ticket::PERM_EDIT))
        ) {
            return false;
        }

        $fields = array();
        $fields['topicId']  = array('type'=>'int',      'required'=>1, 'error'=>__('Help topic selection is required'));
        $fields['slaId']    = array('type'=>'int',      'required'=>0, 'error'=>__('Select a valid SLA'));
        $fields['duedate']  = array('type'=>'date',     'required'=>0, 'error'=>__('Invalid date format - must be MM/DD/YY'));

        $fields['user_id']  = array('type'=>'int',      'required'=>0, 'error'=>__('Invalid user-id'));

        if (!Validator::process($fields, $vars, $errors) && !$errors['err'])
            $errors['err'] = sprintf('%s — %s',
                __('Missing or invalid data'),
                __('Correct any errors below and try again'));

        $vars['note'] = ThreadEntryBody::clean($vars['note']);

        if ($vars['duedate']) {
            if ($this->isClosed())
                $errors['duedate']=__('Due date can NOT be set on a closed ticket');
            elseif (strtotime($vars['duedate']) === false)
                $errors['duedate']=__('Invalid due date');
            elseif (Misc::user2gmtime($vars['duedate']) <= Misc::user2gmtime())
                $errors['duedate']=__('Due date must be in the future');
        }

        if (isset($vars['source']) // Check ticket source if provided
                && !array_key_exists($vars['source'], Ticket::getSources()))
            $errors['source'] = sprintf( __('Invalid source given - %s'),
                    Format::htmlchars($vars['source']));

        $topic = Topic::lookup($vars['topicId']);
        if($topic && !$topic->isActive())
          $errors['topicId']= sprintf(__('%s selected must be active'), __('Help Topic'));

        // Validate dynamic meta-data
        $forms = DynamicFormEntry::forTicket($this->getId());
        foreach ($forms as $form) {
            // Don't validate deleted forms
            if (!in_array($form->getId(), $vars['forms']))
                continue;
            $form->filterFields(function($f) { return !$f->isStorable(); });
            $form->setSource($_POST);
            if (!$form->isValid(function($f) {
                return $f->isVisibleToStaff() && $f->isEditableToStaff();
            })) {
                $errors = array_merge($errors, $form->errors());
            }
        }

        if ($errors)
            return false;

        // Decide if we need to keep the just selected SLA
        $keepSLA = ($this->getSLAId() != $vars['slaId']);

        $this->topic_id = $vars['topicId'];
        $this->sla_id = $vars['slaId'];
        $this->source = $vars['source'];
        $this->duedate = $vars['duedate']
            ? date('Y-m-d H:i:s',Misc::dbtime($vars['duedate']))
            : null;

        if ($vars['user_id'])
            $this->user_id = $vars['user_id'];
        if ($vars['duedate'])
            // We are setting new duedate...
            $this->isoverdue = 0;

        $changes = array();
        foreach ($this->dirty as $F=>$old) {
            switch ($F) {
            case 'topic_id':
            case 'user_id':
            case 'source':
            case 'duedate':
            case 'sla_id':
                $changes[$F] = array($old, $this->{$F});
            }
        }

        if (!$this->save())
            return false;

        $vars['note'] = ThreadEntryBody::clean($vars['note']);
        if ($vars['note'])
            $this->logNote(_S('Ticket Updated'), $vars['note'], $thisstaff);

        // Update dynamic meta-data
        foreach ($forms as $form) {
            if ($C = $form->getChanges())
                $changes['fields'] = ($changes['fields'] ?: array()) + $C;
            // Drop deleted forms
            $idx = array_search($form->getId(), $vars['forms']);
            if ($idx === false) {
                $form->delete();
            }
            else {
                $form->set('sort', $idx);
                $form->saveAnswers(function($f) {
                        return $f->isVisibleToStaff()
                        && $f->isEditableToStaff(); }
                        );
            }
        }

        if ($changes) {
          $this->logEvent('edited', $changes);
        }


        // Reselect SLA if transient
        if (!$keepSLA
            && (!$this->getSLA() || $this->getSLA()->isTransient())
        ) {
            $this->selectSLAId();
        }

        if (!$this->save())
            return false;

        $this->updateEstDueDate();
        Signal::send('model.updated', $this);

        return true;
   }

   function updateField($form, &$errors) {
       global $thisstaff, $cfg;

       if (!($field = $form->getField('field')))
           return null;

       $updateDuedate = false;
       if (!($changes = $field->getChanges()))
           $errors['field'] = sprintf(__('%s is already assigned this value'),
                   __($field->getLabel()));
       else {
           if ($field->answer) {
               if (!$field->isEditableToStaff())
                   $errors['field'] = sprintf(__('%s can not be edited'),
                           __($field->getLabel()));
               elseif (!$field->save(true))
                   $errors['field'] =  __('Unable to update field');

               // Strip tags from TextareaFields to ensure event data is not
               // truncated
               if ($field instanceof TextareaField)
                   foreach ($changes as $k=>$v)
                       $changes[$k] = Format::truncate(Format::striptags($v), 200);

               $changes['fields'] = array($field->getId() => $changes);
           } else {
               $val =  $field->getClean();
               $fid = $field->get('name');

               // Convert duedate to DB timezone.
               if ($fid == 'duedate') {
                   if (empty($val))
                       $val = null;
                   elseif ($dt = Format::parseDateTime($val)) {
                     // Make sure the due date is valid
                     if (Misc::user2gmtime($val) <= Misc::user2gmtime())
                         $errors['field']=__('Due date must be in the future');
                     else {
                         $dt->setTimezone(new DateTimeZone($cfg->getDbTimezone()));
                         $val = $dt->format('Y-m-d H:i:s');
                     }
                  }
               } elseif (is_object($val))
                   $val = $val->getId();

               $changes = array();
               $this->{$fid} = $val;
               foreach ($this->dirty as $F=>$old) {
                   switch ($F) {
                   case 'sla_id':
                   case 'duedate':
                        $updateDuedate = true;
                   case 'topic_id':
                   case 'user_id':
                   case 'source':
                       $changes[$F] = array($old, $this->{$F});
                   }
               }

               if (!$errors && !$this->save())
                   $errors['field'] =  __('Unable to update field');
           }
       }

       if ($errors)
           return false;

       // Record the changes
       $this->logEvent('edited', $changes);

       // Log comments (if any)
       if (($comments = $form->getField('comments')->getClean())) {
           $title = sprintf(__('%s updated'), __($field->getLabel()));
           $_errors = array();
           $this->postNote(
                   array('note' => $comments, 'title' => $title),
                   $_errors, $thisstaff, false);
       }

       $this->lastupdate = SqlFunction::NOW();

       if ($updateDuedate)
           $this->updateEstDueDate();

       $this->save();

       Signal::send('model.updated', $this);

       return true;
   }

   /*============== Static functions. Use Ticket::function(params); =============nolint*/
    static function getIdByNumber($number, $email=null, $ticket=false) {

        if (!$number)
            return 0;

        $query = static::objects()
            ->filter(array('number' => $number));

        if ($email)
            $query->filter(Q::any(array(
                'user__emails__address' => $email,
                'thread__collaborators__user__emails__address' => $email
            )));


        if (!$ticket) {
            $query = $query->values_flat('ticket_id');
            if ($row = $query->first())
                return $row[0];
        }
        else {
            return $query->first();
        }
    }

    static function lookupByNumber($number, $email=null) {
        return static::getIdByNumber($number, $email, true);
    }

    static function isTicketNumberUnique($number) {
        $num = static::objects()
            ->filter(array('number' => $number))
        ->count();

    return ($num === 0);
    }

    static function getChildTickets($pid) {
        return Ticket::objects()
                ->filter(array('ticket_pid'=>$pid))
                ->values_flat('ticket_id', 'number', 'ticket_pid', 'sort', 'thread__id', 'user_id', 'cdata__subject', 'user__name', 'flags')
                ->annotate(array('tasks' => SqlAggregate::COUNT('tasks__id', true),
                                 'collaborators' => SqlAggregate::COUNT('thread__collaborators__id'),
                                 'entries' => SqlAggregate::COUNT('thread__entries__id'),))
                ->order_by('sort');
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

    protected static function filterTicketData($origin, $vars, $forms, $user=false, $postCreate=false) {
        global $cfg;

        // Unset all the filter data field data in case things change
        // during recursive calls
        foreach ($vars as $k=>$v)
            if (strpos($k, 'field.') === 0)
                unset($vars[$k]);

        foreach ($forms as $F) {
            if ($F) {
                $vars += $F->getFilterData();
            }
        }

        if (!$user) {
            $interesting = array('name', 'email');
            $user_form = UserForm::getUserForm()->getForm($vars);
            // Add all the user-entered info for filtering
            foreach ($interesting as $F) {
                if ($field = $user_form->getField($F))
                    $vars[$F] = $field->toString($field->getClean());
            }
            // Attempt to lookup the user and associated data
            $user = User::lookupByEmail($vars['email']);
        }

        // Add in user and organization data for filtering
        if ($user) {
            $vars += $user->getFilterData();
            $vars['email'] = $user->getEmail();
            $vars['name'] = $user->getName()->getOriginal();
            if ($org = $user->getOrganization()) {
                $vars += $org->getFilterData();
            }
        }
        // Don't include org information based solely on email domain
        // for existing user instances
        else {
            // Unpack all known user info from the request
            foreach ($user_form->getFields() as $f) {
                $vars['field.'.$f->get('id')] = $f->toString($f->getClean());
            }
            // Add in organization data if one exists for this email domain
            list($mailbox, $domain) = explode('@', $vars['email'], 2);
            if ($org = Organization::forDomain($domain)) {
                $vars += $org->getFilterData();
            }
        }

        try {
            // Make sure the email address is not banned
            if (($filter=Banlist::isBanned($vars['email']))) {
                throw new RejectedException($filter, $vars);
            }

            // Init ticket filters...
            $ticket_filter = new TicketFilter($origin, $vars);
            $ticket_filter->apply($vars, $postCreate);

            if ($postCreate && $filterMatches = $ticket_filter->getMatchingFilterList()) {
                $username = __('Ticket Filter');
                foreach ($filterMatches as $f) {
                    $actions = $f->getActions();
                    foreach ($actions as $key => $value) {
                        $filterName = $f->getName();
                        if (!$coreClass = $value->lookupByType($value->type))
                            continue;

                        if ($description = $coreClass->getEventDescription($value, $filterName))
                            $postCreate->logEvent($description['type'], $description['desc'], $username);

                    }
                    if ($f->stopOnMatch()) break;
                }
            }
        }
        catch (FilterDataChanged $ex) {
            // Don't pass user recursively, assume the user has changed
            return self::filterTicketData($origin, $ex->getData(), $forms);
        }
        return $vars;
    }

    /*
     * The mother of all functions...You break it you fix it!
     *
     *  $autorespond and $alertstaff overrides config settings...
     */
    static function create($vars, &$errors, $origin, $autorespond=true,
            $alertstaff=true) {
        global $ost, $cfg, $thisstaff;

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
                    return $f->isVisibleToStaff();
                case 'web':
                    return $f->isVisibleToUsers();
                default:
                    return true;
                }
            };
        };

        $reject_ticket = function($message) use (&$errors) {
            global $ost;
            $errors = array(
                'errno' => 403,
                'err' => __('This help desk is for use by authorized users only'));
            $ost->logWarning(_S('Ticket denied'), $message, false);
            return 0;
        };

        Signal::send('ticket.create.before', null, $vars);

        // Create and verify the dynamic form entry for the new ticket
        $form = TicketForm::getNewInstance();
        $form->setSource($vars);

        // If submitting via email or api, ensure we have a subject and such
        if (!in_array(strtolower($origin), array('web', 'staff'))) {
            foreach ($form->getFields() as $field) {
                $fname = $field->get('name');
                if ($fname && isset($vars[$fname]) && !$field->value)
                    $field->value = $field->parse($vars[$fname]);
            }
        }

        if ($vars['uid'])
            $user = User::lookup($vars['uid']);

        $id=0;
        $fields=array();
        switch (strtolower($origin)) {
            case 'web':
                $fields['topicId']  = array('type'=>'int',  'required'=>1, 'error'=>__('Select a Help Topic'));
                break;
            case 'staff':
                $fields['deptId']   = array('type'=>'int',  'required'=>0, 'error'=>__('Department selection is required'));
                $fields['topicId']  = array('type'=>'int',  'required'=>1, 'error'=>__('Help topic selection is required'));
                $fields['duedate']  = array('type'=>'date', 'required'=>0, 'error'=>__('Invalid date format - must be MM/DD/YY'));
            case 'api':
                $fields['source']   = array('type'=>'string', 'required'=>1, 'error'=>__('Indicate ticket source'));
                break;
            case 'email':
                $fields['emailId']  = array('type'=>'int',  'required'=>1, 'error'=>__('Unknown system email'));
                break;
            default:
                # TODO: Return error message
                $errors['err']=$errors['origin'] = __('Invalid ticket origin given');
        }

        if(!Validator::process($fields, $vars, $errors) && !$errors['err'])
            $errors['err'] = sprintf('%s — %s',
                __('Missing or invalid data'),
                __('Correct any errors below and try again'));

        // Make sure the due date is valid
        if ($vars['duedate']) {
            if (strtotime($vars['duedate']) === false)
                $errors['duedate']=__('Invalid due date');
            elseif (Misc::user2gmtime($vars['duedate']) <= Misc::user2gmtime())
                $errors['duedate']=__('Due date must be in the future');
        }

        $topic_forms = array();
        if (!$errors) {

            // Handle the forms associate with the help topics. Instanciate the
            // entries, disable and track the requested disabled fields.
            if ($vars['topicId']) {
                if ($__topic=Topic::lookup($vars['topicId'])) {
                    foreach ($__topic->getForms() as $idx=>$__F) {
                        $disabled = array();
                        foreach ($__F->getFields() as $field) {
                            if (!$field->isEnabled() && $field->hasFlag(DynamicFormField::FLAG_ENABLED))
                                $disabled[] = $field->get('id');
                        }
                        // Special handling for the ticket form — disable fields
                        // requested to be disabled as per the help topic.
                        if ($__F->get('type') == 'T') {
                            foreach ($form->getFields() as $field) {
                                if (false !== array_search($field->get('id'), $disabled))
                                    $field->disable();
                            }
                            $form->sort = $idx;
                            $__F = $form;
                        }
                        else {
                            $__F = $__F->instanciate($idx);
                            $__F->setSource($vars);
                            $topic_forms[] = $__F;
                        }
                        // Track fields currently disabled
                        $__F->extra = JsonDataEncoder::encode(array(
                            'disable' => $disabled
                        ));
                    }
                }
            }

            try {
                $vars = self::filterTicketData($origin, $vars,
                    array_merge(array($form), $topic_forms), $user, false);
            }
            catch (RejectedException $ex) {
                return $reject_ticket(
                    sprintf(_S('Ticket rejected (%s) by filter "%s"'),
                    $ex->vars['email'], $ex->getRejectingFilter()->getName())
                );
            }

            //Make sure the open ticket limit hasn't been reached. (LOOP CONTROL)
            if ($cfg->getMaxOpenTickets() > 0
                    && strcasecmp($origin, 'staff')
                    && ($_user=TicketUser::lookupByEmail($vars['email']))
                    && ($openTickets=$_user->getNumOpenTickets())
                    && ($openTickets>=$cfg->getMaxOpenTickets()) ) {

                $errors = array('err' => __("You've reached the maximum open tickets allowed."));
                $ost->logWarning(sprintf(_S('Ticket denied - %s'), $vars['email']),
                        sprintf(_S('Max open tickets (%1$d) reached for %2$s'),
                            $cfg->getMaxOpenTickets(), $vars['email']),
                        false);

                return 0;
            }

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
                            sprintf(_S('Ticket rejected (%s) (unregistered client)'),
                                $vars['email']));
                    }
                }

                $user_form = UserForm::getUserForm()->getForm($vars);
                $can_create = !$thisstaff || $thisstaff->hasPerm(User::PERM_CREATE);
                if (!$user_form->isValid($field_filter('user'))
                    || !($user=User::fromVars($user_form->getClean(), $can_create))
                ) {
                    $errors['user'] = $can_create
                        ? __('Incomplete client information')
                        : __('You do not have permission to create users.');
                }
            }
        }

        if (!$form->isValid($field_filter('ticket')))
            $errors += $form->errors();

        if ($vars['topicId']) {
            if (($topic=Topic::lookup($vars['topicId']))
                    && $topic->isActive()) {
                foreach ($topic_forms as $topic_form) {
                    $TF = $topic_form->getForm($vars);
                    if (!$TF->isValid($field_filter('topic')))
                        $errors = array_merge($errors, $TF->errors());
                }
            } else  {
                $vars['topicId'] = 0;
            }
        }



        // Any errors above are fatal.
        if ($errors)
            return 0;

        Signal::send('ticket.create.validated', null, $vars);

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
        $statusId = $vars['statusId'];
        $deptId = $vars['deptId']; //pre-selected Dept if any.
        $source = ucfirst($vars['source']);

        // Apply email settings for emailed tickets. Email settings should
        // trump help topic settins if the email has an associated help
        // topic
        if ($vars['emailId'] && ($email=Email::lookup($vars['emailId']))) {
            $deptId = $deptId ?: $email->getDeptId();
            $dept = Dept::lookup($deptId);
            if ($dept && !$dept->isActive())
                $deptId = $cfg->getDefaultDeptId();
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
            $statusId = $statusId ?: $topic->getStatusId();
            $priority = $form->getAnswer('priority');
            if (!$priority || !$priority->getIdValue())
                $form->setAnswer('priority', null, $topic->getPriorityId());
            if ($autorespond)
                $autorespond = $topic->autoRespond();

            //Auto assignment.
            if (!isset($vars['staffId']) && $topic->getStaffId())
                $vars['staffId'] = $topic->getStaffId();
            elseif (!isset($vars['teamId']) && $topic->getTeamId())
                $vars['teamId'] = $topic->getTeamId();

            // Unset slaId if 0 to use the Help Topic SLA or Default SLA
            if ($vars['slaId'] == 0)
                unset($vars['slaId']);

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
        $statusId = $statusId ?: $cfg->getDefaultTicketStatusId();
        $topicId = isset($topic) ? $topic->getId() : 0;
        $ipaddress = $vars['ip'] ?: $_SERVER['REMOTE_ADDR'];
        $source = $source ?: 'Web';

        //We are ready son...hold on to the rails.
        $number = $topic ? $topic->getNewTicketNumber() : $cfg->getNewTicketNumber();
        $ticket = new static(array(
            'created' => SqlFunction::NOW(),
            'lastupdate' => SqlFunction::NOW(),
            'number' => $number,
            'user' => $user,
            'dept_id' => $deptId,
            'topic_id' => $topicId,
            'ip_address' => $ipaddress,
            'source' => $source,
        ));

        if (isset($vars['emailId']) && $vars['emailId'])
            $ticket->email_id = $vars['emailId'];

        //Make sure the origin is staff - avoid firebug hack!
        if ($vars['duedate'] && !strcasecmp($origin,'staff'))
            $ticket->duedate = date('Y-m-d G:i',
                Misc::dbtime($vars['duedate']));


        if (!$ticket->save())
            return null;
        if (!($thread = TicketThread::create($ticket->getId())))
            return null;

        /* -------------------- POST CREATE ------------------------ */

        // Save the (common) dynamic form
        // Ensure we have a subject
        $subject = $form->getAnswer('subject');
        if ($subject && !$subject->getValue() && $topic)
            $subject->setValue($topic->getFullName());

        $form->setTicketId($ticket->getId());
        $form->save();

        // Save the form data from the help-topic form, if any
        foreach ($topic_forms as $topic_form) {
            $topic_form->setTicketId($ticket->getId());
            $topic_form->save();
        }

        $ticket->loadDynamicData(true);

        $dept = $ticket->getDept();

        // Start tracking ticket lifecycle events (created should come first!)
        $ticket->logEvent('created', null, $thisstaff ?: $user);

        // Set default ticket status (if none) for Thread::getObject()
        // in addCollaborators()
        if ($ticket->getStatusId() <= 0)
            $ticket->setStatusId($cfg->getDefaultTicketStatusId());

        // Add collaborators (if any)
        if (isset($vars['ccs']) && count($vars['ccs']))
          $ticket->addCollaborators($vars['ccs'], array(), $errors);

        // Add organizational collaborators
        if ($org && $org->autoAddCollabs()) {
            $pris = $org->autoAddPrimaryContactsAsCollabs();
            $members = $org->autoAddMembersAsCollabs();
            $settings = array('isactive' => true);
            $collabs = array();
            foreach ($org->allMembers() as $u) {
                $_errors = array();
                if ($members || ($pris && $u->isPrimaryContact())) {
                    if ($c = $ticket->addCollaborator($u, $settings, $_errors)) {
                        $collabs[] = (string) $c;
                    }
                }
            }
            //TODO: Can collaborators add others?
            if ($collabs) {
                $ticket->logEvent('collab', array('org' => $org->getId()));
            }
        }

        //post the message.
        $vars['title'] = $vars['subject']; //Use the initial subject as title of the post.
        $vars['userId'] = $ticket->getUserId();
        $message = $ticket->postMessage($vars , $origin, false);

        $vars['ticket'] = $ticket;
        self::filterTicketData($origin, $vars,
            array_merge(array($form), $topic_forms), $user, $ticket);

        // If a message was posted, flag it as the orignal message. This
        // needs to be done on new ticket, so as to otherwise separate the
        // concept from the first message entry in a thread.
        if ($message instanceof ThreadEntry) {
            $message->setFlag(ThreadEntry::FLAG_ORIGINAL_MESSAGE);
            $message->save();
        }

        //check to see if ticket was created from a thread
        if ($_SESSION[':form-data']['ticketId'] || $_SESSION[':form-data']['taskId']) {
          $oldTicket = Ticket::lookup($_SESSION[':form-data']['ticketId']);
          $oldTask = Task::lookup($_SESSION[':form-data']['taskId']);

          //add internal note to new ticket.
          //New ticket should have link to old task/ticket:
          $link = sprintf('<a href="%s.php?id=%d"><b>#%s</b></a>',
              $oldTicket ? 'tickets' : 'tasks',
              $oldTicket ? $oldTicket->getId() : $oldTask->getId(),
              $oldTicket ? $oldTicket->getNumber() : $oldTask->getNumber());

          $note = array(
                  'title' => __('Ticket Created From Thread Entry'),
                  'body' => sprintf(__(
                        // %1$s is the word Ticket or Task, %2$s will be a link to it
                        'This Ticket was created from %1$s %2$s'),
                        $oldTicket ? __('Ticket') : __('Task'), $link)
                  );

          $ticket->logNote($note['title'], $note['body'], $thisstaff);

          //add internal note to referenced ticket/task
          // Old ticket/task should have link to new ticket
          $ticketLink = sprintf('<a href="tickets.php?id=%d"><b>#%s</b></a>',
              $ticket->getId(),
              $ticket->getNumber());

          $entryLink = sprintf('<a href="#entry-%d"><b>%s</b></a>',
              $_SESSION[':form-data']['eid'],
              Format::datetime($_SESSION[':form-data']['timestamp']));

          $ticketNote = array(
              'title' => __('Ticket Created From Thread Entry'),
              'body' => sprintf(__('Ticket %1$s<br/> Thread Entry: %2$s'),
                $ticketLink, $entryLink)
          );

          $taskNote = array(
              'title' => __('Ticket Created From Thread Entry'),
              'note' => sprintf(__('Ticket %1$s<br/> Thread Entry: %2$s'),
                $ticketLink, $entryLink)
          );

          if ($oldTicket)
            $oldTicket->logNote($ticketNote['title'], $ticketNote['body'], $thisstaff);
          elseif ($oldTask)
            $oldTask->postNote($taskNote, $errors, $thisstaff);
        }

        // Configure service-level-agreement for this ticket
        $ticket->selectSLAId($vars['slaId']);

        // Set status
        $status = TicketStatus::lookup($statusId);
        if (!$status || !$ticket->setStatus($status, false, $errors,
                    !strcasecmp($origin, 'staff'))) {
            // Tickets _must_ have a status. Forceably set one here
            $ticket->setStatusId($cfg->getDefaultTicketStatusId());
        }

        // Only do assignment if the ticket is in an open state
        if ($ticket->isOpen()) {
            // Assign ticket to staff or team (new ticket by staff)
            if ($vars['assignId']) {
                $asnform = $ticket->getAssignmentForm(array(
                            'assignee' => $vars['assignId'],
                            'comments' => $vars['note'])
                        );
                $e = array();
                $ticket->assign($asnform, $e);
            }
            else {
                // Auto assign staff or team - auto assignment based on filter
                // rules. Both team and staff can be assigned
                $username = __('Ticket Filter');
                if ($vars['staffId'])
                     $ticket->assignToStaff($vars['staffId'], false, true, $username);
                if ($vars['teamId'])
                    // No team alert if also assigned to an individual agent
                    $ticket->assignToTeam($vars['teamId'], false, !$vars['staffId'], $username);
            }
        }

        // Update the estimated due date in the database
        $ticket->updateEstDueDate();

        /**********   double check auto-response  ************/
        //Override auto responder if the FROM email is one of the internal emails...loop control.
        if($autorespond && (Email::getIdByEmail($ticket->getEmail())))
            $autorespond=false;

        # Messages that are clearly auto-responses from email systems should
        # not have a return 'ping' message
        if (isset($vars['mailflags']) && $vars['mailflags']['bounce'])
            $autorespond = false;
        if ($autorespond && $message instanceof ThreadEntry && $message->isAutoReply())
            $autorespond = false;

        // Post canned auto-response IF any (disables new ticket auto-response).
        if ($vars['cannedResponseId']
            && $ticket->postCannedReply($vars['cannedResponseId'], $message, $autorespond)) {
                $ticket->markUnAnswered(); //Leave the ticket as unanswred.
                $autorespond = false;
        }


        if ($vars['system_emails'])
            $ticket->systemReferral($vars['system_emails']);

        // Check department's auto response settings
        // XXX: Dept. setting doesn't affect canned responses.
        if ($autorespond && $dept && !$dept->autoRespONNewTicket())
            $autorespond=false;

        // Don't send alerts to staff when the message is a bounce
        // this is necessary to avoid possible loop (especially on new ticket)
        if ($alertstaff && $message instanceof ThreadEntry && $message->isBounce())
            $alertstaff = false;

        /***** See if we need to send some alerts ****/
        $ticket->onNewTicket($message, $autorespond, $alertstaff);

        /************ check if the user JUST reached the max. open tickets limit **********/
        if ($cfg->getMaxOpenTickets()>0
            && ($user=$ticket->getOwner())
            && ($user->getNumOpenTickets()==$cfg->getMaxOpenTickets())
        ) {
            $ticket->onOpenLimit($autorespond && strcasecmp($origin, 'staff'));
        }

        // Fire post-create signal (for extra email sending, searching)
        Signal::send('ticket.created', $ticket);

        /* Phew! ... time for tea (KETEPA) */

        return $ticket;
    }

    /* routine used by staff to open a new ticket */
    static function open($vars, &$errors) {
        global $thisstaff, $cfg;

        if (!$thisstaff)
            return false;

        if ($vars['deptId']
            && ($dept=Dept::lookup($vars['deptId']))
            && ($role = $thisstaff->getRole($dept))
            && !$role->hasPerm(Ticket::PERM_CREATE)
        ) {
            $errors['err'] = sprintf(__('You do not have permission to create a ticket in %s'), __('this department'));
            return false;
        }

        if (isset($vars['source']) // Check ticket source if provided
                && !array_key_exists($vars['source'], Ticket::getSources()))
            $errors['source'] = sprintf( __('Invalid source given - %s'),
                    Format::htmlchars($vars['source']));


        if (!$vars['uid']) {
            // Special validation required here
            if (!$vars['email'] || !Validator::is_email($vars['email']))
                $errors['email'] = __('Valid email address is required');

            if (!$vars['name'])
                $errors['name'] = __('Name is required');
        }

        // Ensure agent has rights to make assignment in the cited
        // department
        if ($vars['assignId'] && !(
            $role
            ? ($role->hasPerm(Ticket::PERM_ASSIGN) || $role->__new__)
            : $thisstaff->hasPerm(Ticket::PERM_ASSIGN, false)
        )) {
            $errors['assignId'] = __('Action Denied. You are not allowed to assign/reassign tickets.');
        }

        // TODO: Deny action based on selected department.
        $vars['response'] = ThreadEntryBody::clean($vars['response']);
        $vars['note'] = ThreadEntryBody::clean($vars['note']);
        $create_vars = $vars;
        $tform = TicketForm::objects()->one()->getForm($create_vars);
        $mfield = $tform->getField('message');
        $create_vars['message'] = $mfield->getClean();
        $create_vars['files'] = $mfield->getWidget()->getAttachments()->getFiles();

        if (!($ticket=self::create($create_vars, $errors, 'staff', false)))
            return false;

        $vars['msgId']=$ticket->getLastMsgId();

        // Effective role for the department
        $role = $ticket->getRole($thisstaff);

        $alert = strcasecmp('none', $vars['reply-to']);
        // post response - if any
        $response = null;
        if ($vars['response'] && $role->hasPerm(Ticket::PERM_REPLY)) {
            $vars['response'] = $ticket->replaceVars($vars['response']);
            // $vars['cannedatachments'] contains the attachments placed on
            // the response form.
            $response = $ticket->postReply($vars, $errors, ($alert &&
                        !$cfg->notifyONNewStaffTicket()));
        }

        // Not assigned...save optional note if any
        if (!$vars['assignId'] && $vars['note']) {
            if (!$cfg->isRichTextEnabled())
                $vars['note'] = new TextThreadEntryBody($vars['note']);
            $ticket->logNote(_S('New Ticket'), $vars['note'], $thisstaff, false);
        }

        if (!$cfg->notifyONNewStaffTicket()
            || !$alert
            || !($dept=$ticket->getDept())
        ) {
            return $ticket; //No alerts.
        }

        // Notice Recipients
        $recipients = $ticket->getRecipients($vars['reply-to']);

        // Send Notice to user --- if requested AND enabled!!
        if (($tpl=$dept->getTemplate())
            && ($msg=$tpl->getNewTicketNoticeMsgTemplate())
            && ($email=$dept->getEmail())
        ) {
           $attachments = array();
           $message = $ticket->getLastMessage();
           if ($cfg->emailAttachments()) {
               if ($message && $message->getNumAttachments()) {
                 foreach ($message->getAttachments() as $attachment)
                     $attachments[] = $attachment;
               }
               if ($response && $response->getNumAttachments()) {
                 foreach ($response->getAttachments() as $attachment)
                     $attachments[] = $attachment;
               }
           }

            if ($vars['signature']=='mine')
                $signature=$thisstaff->getSignature();
            elseif ($vars['signature']=='dept' && $dept && $dept->isPublic())
                $signature=$dept->getSignature();
            else
                $signature='';

            $msg = $ticket->replaceVars($msg->asArray(),
                array(
                    'message'   => $message ?: '',
                    'response'  => $response ?: '',
                    'signature' => $signature,
                    'recipient' => $ticket->getOwner(), //End user
                    'staff'     => $thisstaff,
                )
            );
            $message = $ticket->getLastMessage();
            $options = array(
                'thread' => $message ?: $ticket->getThread(),
            );

            //ticket created on user's behalf
            $email->send($recipients, $msg['subj'], $msg['body'], $attachments,
                $options);
        }
        return $ticket;
    }

    static function checkOverdue() {
        $overdue = static::objects()
            ->filter(array(
                'isoverdue' => 0,
                'status__state' => 'open',
                Q::any(array(
                    Q::all(array(
                        'duedate__isnull' => true,
                        'est_duedate__isnull' => false,
                        'est_duedate__lt' => SqlFunction::NOW())
                        ),
                    Q::all(array(
                        'duedate__isnull' => false,
                        'duedate__lt' => SqlFunction::NOW())
                        )
                    ))
                ))
            ->limit(100);

        foreach ($overdue as $ticket)
            $ticket->markOverdue();

    }

    static function agentActions($agent, $options=array()) {
        if (!$agent)
            return;

        require STAFFINC_DIR.'templates/tickets-actions.tmpl.php';
    }

    static function getLink($id) {
        global $thisstaff;

        switch (true) {
        case ($thisstaff instanceof Staff):
            return ROOT_PATH . sprintf('scp/tickets.php?id=%s', $id);
        }
    }

    static function getPermissions() {
        return self::$perms;
    }

    static function getSources() {
        static $translated = false;
        if (!$translated) {
            foreach (static::$sources as $k=>$v)
                static::$sources[$k] = __($v);
        }

        return static::$sources;
    }

    // TODO: Create internal Form for internal fields
    static function duedateField($name, $default='', $hint='') {
        return DateTimeField::init(array(
            'id' => $name,
            'name' => $name,
            'default' => $default ?: false,
            'label' => __('Due Date'),
            'hint' => $hint,
            'configuration' => array(
                'min' => Misc::gmtime(),
                'time' => true,
                'gmt' => false,
                'future' => true,
                )
            ));
    }

    static function registerCustomData(DynamicForm $form) {
        if (!isset(static::$meta['joins']['cdata+'.$form->id])) {
            $cdata_class = <<<EOF
class DynamicForm{$form->id} extends DynamicForm {
    static function getInstance() {
        static \$instance;
        if (!isset(\$instance))
            \$instance = static::lookup({$form->id});
        return \$instance;
    }
}
class TicketCdataForm{$form->id}
extends VerySimpleModel {
    static \$meta = array(
        'view' => true,
        'pk' => array('ticket_id'),
        'joins' => array(
            'ticket' => array(
                'constraint' => array('ticket_id' => 'Ticket.ticket_id'),
            ),
        )
    );
    static function getQuery(\$compiler) {
        return '('.DynamicForm{$form->id}::getCrossTabQuery('T', 'ticket_id').')';
    }
}
EOF;
            eval($cdata_class);
            $join = array(
                'constraint' => array('ticket_id' => 'TicketCdataForm'.$form->id.'.ticket_id'),
                'list' => true,
            );
            // This may be necessary if the model has already been inspected
            if (static::$meta instanceof ModelMeta)
                static::$meta->addJoin('cdata+'.$form->id, $join);
            else {
                static::$meta['joins']['cdata+'.$form->id] = array(
                    'constraint' => array('ticket_id' => 'TicketCdataForm'.$form->id.'.ticket_id'),
                    'list' => true,
                );
            }
        }
    }
}
RolePermission::register(/* @trans */ 'Tickets', Ticket::getPermissions(), true);

class TicketCData extends VerySimpleModel {
    static $meta = array(
        'pk' => array('ticket_id'),
        'joins' => array(
            'ticket' => array(
                'constraint' => array('ticket_id' => 'Ticket.ticket_id'),
            ),
            ':priority' => array(
                'constraint' => array('priority' => 'Priority.priority_id'),
                'null' => true,
            ),
        ),
    );
}
TicketCData::$meta['table'] = TABLE_PREFIX . 'ticket__cdata';
