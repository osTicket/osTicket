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
require_once(INCLUDE_DIR.'class.task.php');
require_once(INCLUDE_DIR.'class.faq.php');

class TicketModel extends VerySimpleModel {
    static $meta = array(
        'table' => TICKET_TABLE,
        'pk' => array('ticket_id'),
        'joins' => array(
            'user' => array(
                'constraint' => array('user_id' => 'User.id')
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
    const PERM_TRANSFER = 'ticket.transfer';
    const PERM_REPLY    = 'ticket.reply';
    const PERM_CLOSE    = 'ticket.close';
    const PERM_DELETE   = 'ticket.delete';


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
            self::PERM_TRANSFER => array(
                'title' =>
                /* @trans */ 'Transfer',
                'desc'  =>
                /* @trans */ 'Ability to transfer tickets between departments'),
            self::PERM_REPLY => array(
                'title' =>
                /* @trans */ 'Post Reply',
                'desc'  =>
                /* @trans */ 'Ability to post a ticket reply'),
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

    function getId() {
        return $this->ticket_id;
    }

    function getEffectiveDate() {
         return Format::datetime(max(
             strtotime($this->thread->lastmessage),
             strtotime($this->closed),
             strtotime($this->reopened),
             strtotime($this->created)
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
                'constraint' => array('ticket_id' => 'TicketModel.ticket_id'),
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

    static function getPermissions() {
        return self::$perms;
    }
}

RolePermission::register(/* @trans */ 'Tickets', TicketModel::getPermissions(), true);

class TicketCData extends VerySimpleModel {
    static $meta = array(
        'pk' => array('ticket_id'),
        'joins' => array(
            'ticket' => array(
                'constraint' => array('ticket_id' => 'TicketModel.ticket_id'),
            ),
            'priority' => array(
                'constraint' => array('priority' => 'Priority.priority_id'),
                'null' => true,
            ),
        ),
    );
}
TicketCData::$meta['table'] = TABLE_PREFIX . 'ticket__cdata';

class Ticket extends TicketModel
implements RestrictedAccess, Threadable {

    static $meta = array(
        'select_related' => array('topic', 'staff', 'user', 'team', 'dept', 'sla', 'thread',
            'user__default_email'),
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

    function __onload() {
        $this->loadDynamicData();
    }

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
        return $this->getStatus()->isReopenable();
    }

    function isClosed() {
         return $this->hasState('closed');
    }

    function isCloseable() {

        if ($this->isClosed())
            return true;

        $warning = null;
        if ($this->getMissingRequiredFields()) {
            $warning = sprintf(
                    __( '%1$s is missing data on %2$s one or more required fields %3$s and cannot be closed'),
                    __('This ticket'),
                    '', '');
        } elseif (($num=$this->getNumOpenTasks())) {
            $warning = sprintf(__('%1$s has %2$d open tasks and cannot be closed'),
                    __('This ticket'), $num);
        }

        return $warning ?: true;
    }

    function isArchived() {
         return $this->hasState('archived');
    }

    function isDeleted() {
         return $this->hasState('deleted');
    }

    function isAssigned() {
        return $this->isOpen() && ($this->getStaffId() || $this->getTeamId());
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

    function checkStaffPerm($staff, $perm=null) {
        // Must be a valid staff
        if (!$staff instanceof Staff && !($staff=Staff::lookup($staff)))
            return false;

        // Check access based on department or assignment
        if (($staff->showAssignedOnly()
            || !$staff->canAccessDept($this->getDeptId()))
            // only open tickets can be considered assigned
            && $this->isOpen()
            && $staff->getId() != $this->getStaffId()
            && !$staff->isTeamMember($this->getTeamId())
        ) {
            return false;
        }

        // At this point staff has view access unless a specific permission is
        // requested
        if ($perm === null)
            return true;

        // Permission check requested -- get role.
        if (!($role=$staff->getRole($this->getDeptId())))
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
        return (string) $this->_answers['subject'];
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

    function getSLADueDate() {
        if ($sla = $this->getSLA()) {
            $dt = new DateTime($this->getCreateDate());

            return $dt
                ->add(new DateInterval('PT' . $sla->getGracePeriod() . 'H'))
                ->format('Y-m-d H:i:s');
        }
    }

    function updateEstDueDate() {
        $this->est_duedate = $this->getEstDueDate();
        $this->save();
    }

    function getEstDueDate() {
        // Real due date
        if ($duedate = $this->getDueDate()) {
            return $duedate;
        }
        // return sla due date (If ANY)
        return $this->getSLADueDate();
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

        if (($a = $this->_answers['priority'])
            && ($b = $a->getValue())
        ) {
            return $b->getId();
        }
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
        return $this->source;
    }

    function getIP() {
        return $this->ip_address;
    }

    function getHashtable() {
        return $this->ht;
    }

    function getUpdateInfo() {
        return array(
            'source'    => $this->getSource(),
            'topicId'   => $this->getTopicId(),
            'slaId'     => $this->getSLAId(),
            'user_id'   => $this->getOwnerId(),
            'duedate'   => $this->getDueDate()
                ? Format::date($this->getDueDate())
                : '',
            'time'      => $this->getDueDate()?(Format::date($this->getDueDate(), true, 'HH:mm')):'',
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
            if (!$this->thread || !$this->thread->entries)
                return $this->lastrespondent = false;
            $this->lastrespondent = Staff::objects()
                ->filter(array(
                'staff_id' => $this->thread->entries
                    ->filter(array(
                        'type' => 'R',
                        'staff_id__gt' => 0,
                    ))
                    ->values_flat('staff_id')
                    ->order_by('-id')
                    ->limit(1)
                ))
                ->first()
                ?: false;
        }
        return $this->lastrespondent;
    }

    function getLastMessageDate() {
        return $this->thread->lastmessage;
    }

    function getLastMsgDate() {
        return $this->getLastMessageDate();
    }

    function getLastResponseDate() {
        return $this->thread->lastresponse;
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
                $this->last_message = $this->getThread()->getLastMessage();
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
        if ($this->thread)
            return $this->thread->id;
    }

    function getThread() {
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
        $entries = $this->getThread()->getEntries();
        if ($type && is_array($type))
            $entries->filter(array('type__in' => $type));

        return $entries;
    }

    //UserList of recipients  (owner + collaborators)
    function getRecipients() {
        if (!isset($this->recipients)) {
            $list = new UserList();
            $list->add($this->getOwner());
            if ($collabs = $this->getThread()->getActiveCollaborators()) {
                foreach ($collabs as $c)
                    $list->add($c);
            }
            $this->recipients = $list;
        }
        return $this->recipients;
    }

    function getAssignmentForm($source=null, $options=array()) {

        if (!$source)
            $source = array('assignee' => array($this->getAssigneeId()));

        $options += array('dept' => $this->getDept());

        return AssignmentForm::instantiate($source, $options);
    }

    function getTransferForm($source=null) {

        if (!$source)
            $source = array('dept' => array($this->getDeptId()));

        return TransferForm::instantiate($source);
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

    function getMissingRequiredFields() {

        return $this->getDynamicFields(array(
                    'answers__field__flags__hasbit' => DynamicFormField::FLAG_ENABLED,
                    'answers__field__flags__hasbit' => DynamicFormField::FLAG_CLOSE_REQUIRED,
                    'answers__value__isnull' => true,
                    ));
    }

    function getMissingRequiredField() {
        $fields = $this->getMissingRequiredFields();
        return $fields ? $fields[0] : null;
    }

    function addCollaborator($user, $vars, &$errors, $event=true) {

        if (!$user || $user->getId() == $this->getOwnerId())
            return null;

        if ($c = $this->getThread()->addCollaborator($user, $vars, $errors, $event)) {
            $this->collaborators = null;
            $this->recipients = null;
        }

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
                'isactive' => 1,
            ));
        }

        if ($cids) {
            $this->getThread()->collaborators->filter(array(
                'thread_id' => $this->getThreadId(),
                Q::not(array('id__in' => $cids))
            ))->update(array(
                'updated' => SqlFunction::NOW(),
                'isactive' => 0,
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

        $this->sla = Sla::lookup($slaId);
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

    //Status helper.

    function setStatus($status, $comments='', &$errors=array(), $set_closing_agent=true) {
        global $thisstaff;

        if ($thisstaff && !($role = $thisstaff->getRole($this->getDeptId())))
            return false;

        if ($status && is_numeric($status))
            $status = TicketStatus::lookup($status);

        if (!$status || !$status instanceof TicketStatus)
            return false;

        // Double check permissions (when changing status)
        if ($role && $this->getStatusId()) {
            switch ($status->getState()) {
            case 'closed':
                if (!($role->hasPerm(TicketModel::PERM_CLOSE)))
                    return false;
                break;
            case 'deleted':
                // XXX: intercept deleted status and do hard delete
                if ($role->hasPerm(TicketModel::PERM_DELETE))
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
        $ecb = null;
        switch ($status->getState()) {
            case 'closed':
                // Check if ticket is closeable
                $closeable = $this->isCloseable();
                if ($closeable !== true)
                    $errors['err'] = $closeable ?: sprintf(__('%s cannot be closed'), __('This ticket'));

                if ($errors)
                    return false;

                $this->closed = $this->lastupdate = SqlFunction::NOW();
                $this->duedate = null;
                if ($thisstaff && $set_closing_agent)
                    $this->staff = $thisstaff;
                $this->clearOverdue(false);

                $ecb = function($t) use ($status) {
                    $t->logEvent('closed', array('status' => array($status->getId(), $status->getName())));
                    $t->deleteDrafts();
                };
                break;
            case 'open':
                // TODO: check current status if it allows for reopening
                if ($this->isClosed()) {
                    $this->closed = $this->lastupdate = $this->reopened = SqlFunction::NOW();
                    $ecb = function ($t) {
                        $t->logEvent('reopened', false, null, 'closed');
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
        if (!$this->save())
            return false;

        // Log status change b4 reload — if currently has a status. (On new
        // ticket, the ticket is opened and thereafter the status is set to
        // the requested status).
        if ($hadStatus) {
            $alert = false;
            if ($comments) {
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
        if ($message instanceof ThreadEntry) {
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

            // Only alerts dept members if the ticket is NOT assigned.
            if ($cfg->alertDeptMembersONNewTicket() && !$this->isAssigned()) {
                if ($members = $dept->getMembersForAlerts()->all())
                    $recipients = array_merge($recipients, $members);
            }

            if ($cfg->alertDeptManagerONNewTicket() && $dept &&
                    ($manager=$dept->getManager())) {
                $recipients[] = $manager;
            }

            // Account manager
            if ($cfg->alertAcctManagerONNewMessage()
                && ($org = $this->getOwner()->getOrganization())
                && ($acct_manager = $org->getAccountManager())
            ) {
                if ($acct_manager instanceof Team)
                    $recipients = array_merge($recipients, $acct_manager->getMembers());
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

            // Alert admin ONLY if not already a staff??
            if ($cfg->alertAdminONNewTicket()
                    && !in_array($cfg->getAdminEmail(), $sentlist)) {
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

    function  notifyCollaborators($entry, $vars = array()) {
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
        // Who posted the entry?
        $skip = array();
        if ($entry instanceof MessageThreadEntry) {
            $poster = $entry->getUser();
            // Skip the person who sent in the message
            $skip[$entry->getUserId()] = 1;
            // Skip all the other recipients of the message
            foreach ($entry->getAllEmailRecipients() as $R) {
                foreach ($recipients as $R2) {
                    if (0 === strcasecmp($R2->getEmail(), $R->mailbox.'@'.$R->host)) {
                        $skip[$R2->getUserId()] = true;
                        break;
                    }
                }
            }
        } else {
            $poster = $entry->getStaff();
            // Skip the ticket owner
            $skip[$this->getUserId()] = 1;
        }

        $vars = array_merge($vars, array(
            'message' => (string) $entry,
            'poster' => $poster ?: _S('A collaborator'),
            )
        );

        $msg = $this->replaceVars($msg->asArray(), $vars);

        $attachments = $cfg->emailAttachments()?$entry->getAttachments():array();
        $options = array('inreplyto' => $entry->getEmailMessageId(),
                         'thread' => $entry);
        foreach ($recipients as $recipient) {
            // Skip folks who have already been included on this part of
            // the conversation
            if (isset($skip[$recipient->getUserId()]))
                continue;
            $notice = $this->replaceVars($msg, array('recipient' => $recipient));
            $email->send($recipient, $notice['subj'], $notice['body'], $attachments,
                $options);
        }
    }

    function onMessage($message, $autorespond=true) {
        global $cfg;

        $this->isanswered = 0;
        $this->lastupdate = SqlFunction::NOW();
        $this->save();


        // Reopen if closed AND reopenable
        // We're also checking autorespond flag because we don't want to
        // reopen closed tickets on auto-reply from end user. This is not to
        // confused with autorespond on new message setting
        if ($autorespond && $this->isClosed() && $this->isReopenable()) {
            $this->reopen();

            // Auto-assign to closing staff or last respondent
            // If the ticket is closed and auto-claim is not enabled then put the
            // ticket back to unassigned pool.
            if (!$cfg->autoClaimTickets()) {
                $this->setStaffId(0);
            }
            elseif (!($staff = $this->getStaff()) || !$staff->isAvailable()) {
                // Ticket has no assigned staff -  if auto-claim is enabled then
                // try assigning it to the last respondent (if available)
                // otherwise leave the ticket unassigned.
                if (($lastrep = $this->getLastRespondent())
                    && $lastrep->isAvailable()
                ) {
                    $this->setStaffId($lastrep->getId()); //direct assignment;
                }
                else {
                    // unassign - last respondent is not available.
                    $this->setStaffId(0);
                }
            }
        }

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
            $autorespond=false;
        elseif ($autorespond && (Email::getIdByEmail($user->getEmail())))
            $autorespond=false;
        elseif ($autorespond && ($dept=$this->getDept()))
            $autorespond=$dept->autoRespONNewMessage();

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
            $options = array(
                'inreplyto' => $message->getEmailMessageId(),
                'thread' => $message
            );
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
                $recipients = array_merge($recipients, $team->getMembers());
        }

        // Dept manager
        if ($cfg->alertDeptManagerONNewActivity() && $dept && $dept->getManagerId())
            $recipients[] = $dept->getManager();

        $options = array();
        $staffId = $thisstaff ? $thisstaff->getId() : 0;
        if ($vars['threadentry'] && $vars['threadentry'] instanceof ThreadEntry) {
            $options = array(
                'inreplyto' => $vars['threadentry']->getEmailMessageId(),
                'references' => $vars['threadentry']->getEmailReferences(),
                'thread' => $vars['threadentry']);

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
        $comments = $comments ?: _S('Ticket assignment');
        $assigner = $thisstaff ?: _S('SYSTEM (Auto Assignment)');

        //Log an internal note - no alerts on the internal note.
        if ($user_comments) {
            $note = $this->logNote(
                sprintf(_S('Ticket Assigned to %s'), $assignee->getName()),
                $comments, $assigner, false);
        }

        // See if we need to send alerts
        if (!$alert || !$cfg->alertONAssignment())
            return true; //No alerts!

        $dept = $this->getDept();
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
            if ($cfg->alertTeamMembersONAssignment() && ($members=$assignee->getMembers()))
                $recipients = array_merge($recipients, $members);
            elseif ($cfg->alertTeamLeadONAssignment() && ($lead=$assignee->getTeamLead()))
                $recipients[] = $lead;
        }

        // Get the message template
        if ($recipients
            && ($msg=$tpl->getAssignedAlertMsgTemplate())
        ) {
            $msg = $this->replaceVars($msg->asArray(),
                array('comments' => $comments,
                      'assignee' => $assignee,
                      'assigner' => $assigner
                )
            );
            // Send the alerts.
            $sentlist = array();
            $options = $note instanceof ThreadEntry
                ? array(
                    'inreplyto'=>$note->getEmailMessageId(),
                    'references'=>$note->getEmailReferences(),
                    'thread'=>$note)
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
                    && ($members = $team->getMembers())
                ) {
                    $recipients=array_merge($recipients, $members);
                }
            }
            elseif ($cfg->alertDeptMembersONOverdueTicket() && !$this->isAssigned()) {
                // Only alerts dept members if the ticket is NOT assigned.
                if ($members = $dept->getMembersForAlerts()->all())
                    $recipients = array_merge($recipients, $members);
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
            return new FormattedDate($this->last_update);
        case 'user':
            return $this->getOwner();
        default:
            if (isset($this->_answers[$tag]))
                // The answer object is retrieved here which will
                // automatically invoke the toString() method when the
                // answer is coerced into text
                return $this->_answers[$tag];
        }
    }

    static function getVarScope() {
        $base = array(
            'assigned' => __('Assigned agent and/or team'),
            'close_date' => array(
                'class' => 'FormattedDate', 'desc' => __('Date Closed'),
            ),
            'create_date' => array(
                'class' => 'FormattedDate', 'desc' => __('Date created'),
            ),
            'dept' => array(
                'class' => 'Dept', 'desc' => __('Department'),
            ),
            'due_date' => array(
                'class' => 'FormattedDate', 'desc' => __('Due Date'),
            ),
            'email' => __('Default email address of ticket owner'),
            'name' => array(
                'class' => 'PersonsName', 'desc' => __('Name of ticket owner'),
            ),
            'number' => __('Ticket number'),
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
                'class' => 'Topic', 'desc' => __('Help topic'),
            ),
            // XXX: Isn't lastreponse and lastmessage more useful
            'last_update' => array(
                'class' => 'FormattedDate', 'desc' => __('Time of last update'),
            ),
            'user' => array(
                'class' => 'User', 'desc' => __('Ticket owner'),
            ),
        );

        $extra = VariableReplacer::compileFormScope(TicketForm::getInstance());
        return $base + $extra;
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
        if (!$this->isOverdue())
            return true;

        //NOTE: Previously logged overdue event is NOT annuled.

        $this->isoverdue = 0;

        // clear due date if it's in the past
        if ($this->getDueDate() && Misc::db2gmtime($this->getDueDate()) <= Misc::gmtime())
            $this->duedate = null;

        // Clear SLA if est. due date is in the past
        if ($this->getSLADueDate() && Misc::db2gmtime($this->getSLADueDate()) <= Misc::gmtime())
            $this->sla = null;

        return $save ? $this->save() : true;
    }

    //Dept Tranfer...with alert.. done by staff
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
            $this->selectSLAId();

        // Log transfer event
        $this->logEvent('transferred');

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

        //Send out alerts if enabled AND requested
        if (!$alert || !$cfg->alertONTransfer())
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
                    && ($members=$team->getMembers())
                ) {
                    $recipients = array_merge($recipients, $members);
                }
            }
            elseif ($cfg->alertDeptMembersONTransfer() && !$this->isAssigned()) {
                // Only alerts dept members if the ticket is NOT assigned.
                if ($members = $dept->getMembersForAlerts()->all())
                    $recipients = array_merge($recipients, $members);
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
                $options += array(
                    'inreplyto'=>$note->getEmailMessageId(),
                    'references'=>$note->getEmailReferences(),
                    'thread'=>$note);
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

    function claim() {
        global $thisstaff;

        if (!$thisstaff || !$this->isOpen() || $this->isAssigned())
            return false;

        $dept = $this->getDept();
        if ($dept->assignMembersOnly() && !$dept->isMember($thisstaff))
            return false;

        return $this->assignToStaff($thisstaff->getId(), null, false);
    }

    function assignToStaff($staff, $note, $alert=true) {

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

        $this->logEvent('assigned', $data);

        return true;
    }

    function assignToTeam($team, $note, $alert=true) {

        if(!is_object($team) && !($team = Team::lookup($team)))
            return false;

        if (!$team->isActive() || !$this->setTeamId($team->getId()))
            return false;

        //Clear - staff if it's a closed ticket
        //  staff_id is overloaded -> assigned to & closed by.
        if ($this->isClosed())
            $this->setStaffId(0);

        $this->onAssign($team, $note, $alert);
        $this->logEvent('assigned', array('team' => $team->getId()));

        return true;
    }

    function assign(AssignmentForm $form, &$errors, $alert=true) {
        global $thisstaff;

        $evd = array();
        $assignee = $form->getAssignee();
        if ($assignee instanceof Staff) {
            if ($this->getStaffId() == $assignee->getId()) {
                $errors['assignee'] = sprintf(__('%s already assigned to %s'),
                        __('Ticket'),
                        __('the agent')
                        );
            } elseif(!$assignee->isAvailable()) {
                $errors['assignee'] = __('Agent is unavailable for assignment');
            } else {
                $this->staff_id = $assignee->getId();
                if ($thisstaff && $thisstaff->getId() == $assignee->getId())
                    $evd['claim'] = true;
                else
                    $evd['staff'] = array($assignee->getId(), (string) $assignee->getName()->getOriginal());
            }
        } elseif ($assignee instanceof Team) {
            if ($this->getTeamId() == $assignee->getId()) {
                $errors['assignee'] = sprintf(__('%s already assigned to %s'),
                        __('Ticket'),
                        __('the team')
                        );
            } else {
                $this->team_id = $assignee->getId();
                $evd = array('team' => $assignee->getId());
            }
        } else {
            $errors['assignee'] = __('Unknown assignee');
        }

        if ($errors || !$this->save(true))
            return false;

        $this->logEvent('assigned', $evd);

        $this->onAssign($assignee,
                $form->getField('comments')->getClean(),
                $alert);

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

    function release() {
        return $this->unassign();
    }

    //Change ownership
    function changeOwner($user) {
        global $thisstaff;

        if (!$user
            || ($user->getId() == $this->getOwnerId())
            || !($this->checkStaffPerm($thisstaff,
                TicketModel::PERM_EDIT))
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

        $this->logEvent('edited', array('owner' => $user->getId()));

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

        $errors = array();
        if (!($message = $this->getThread()->addMessage($vars, $errors)))
            return null;

        $this->setLastMessage($message);

        // Add email recipients as collaborators...
        if ($vars['recipients']
            && (strtolower($origin) != 'email' || ($cfg && $cfg->addCollabsViaEmail()))
            //Only add if we have a matched local address
            && $vars['to-email-id']
        ) {
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
                    if ($c=$this->addCollaborator($user, $info, $errors, false))
                        // FIXME: This feels very unwise — should be a
                        // string indexed array for future
                        $collabs[$c->user_id] = array(
                            'name' => $c->getName()->getOriginal(),
                            'src' => $recipient['source'],
                        );
            }
            // TODO: Can collaborators add others?
            if ($collabs) {
                $this->logEvent('collab', array('add' => $collabs), $message->user);
            }
        }

        // Find the last message from this user on this thread
        if ($this->getThread()->getLastMessage(array(
            'user_id' => $message->user_id,
            'id__lt' => $message->id,
            'created__gt' => SqlFunction::NOW()->minus(SqlInterval::MINUTE(5)),
        ))) {
            // One message already from this user in the last five minutes
            $alerts = false;
        }


        if (!$alerts)
            return $message; //Our work is done...

        // Do not auto-respond to bounces and other auto-replies
        $autorespond = isset($vars['mailflags'])
            ? !$vars['mailflags']['bounce'] && !$vars['mailflags']['auto-reply']
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
            'thread'=>$message
        );
        // If enabled...send alert to staff (New Message Alert)
        if ($cfg->alertONNewMessage()
            && ($email = $dept->getAlertEmail())
            && ($tpl = $dept->getTemplate())
            && ($msg = $tpl->getNewMessageAlertMsgTemplate())
        ) {
            $msg = $this->replaceVars($msg->asArray(), $variables);
            // Build list of recipients and fire the alerts.
            $recipients = array();
            //Last respondent.
            if ($cfg->alertLastRespondentONNewMessage() && ($lr = $this->getLastRespondent()))
                $recipients[] = $lr;

            //Assigned staff if any...could be the last respondent
            if ($cfg->alertAssignedONNewMessage() && $this->isAssigned()) {
                if ($staff = $this->getStaff())
                    $recipients[] = $staff;
                elseif ($team = $this->getTeam())
                    $recipients = array_merge($recipients, $team->getMembers());
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
                    $recipients = array_merge($recipients, $acct_manager->getMembers());
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
        foreach ($canned->attachments->getAll() as $file)
            $files[] = $file->file_id;

        if ($cfg->isRichTextEnabled())
            $response = new HtmlThreadEntryBody(
                $this->replaceVars($canned->getHtml()));
        else
            $response = new TextThreadEntryBody(
                $this->replaceVars($canned->getPlainText()));

        $info = array('msgId' => $message instanceof ThreadEntry ? $message->getId() : 0,
                      'poster' => __('SYSTEM (Canned Reply)'),
                      'response' => $response,
                      'cannedattachments' => $files
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
            $options = array(
                'inreplyto'=>$response->getEmailMessageId(),
                'references'=>$response->getEmailReferences(),
                'thread'=>$response);
            $email->sendAutoReply($this, $msg['subj'], $msg['body'], $attachments,
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

        if (!($response = $this->getThread()->addResponse($vars, $errors)))
            return null;

        $assignee = $this->getStaff();
        // Set status - if checked.
        if ($vars['reply_status_id']
            && $vars['reply_status_id'] != $this->getStatusId()
        ) {
            $this->setStatus($vars['reply_status_id']);
        }

        // Claim on response bypasses the department assignment restrictions
        if ($claim && $thisstaff && $this->isOpen() && !$this->getStaffId()
            && $cfg->autoClaimTickets()
        ) {
            $this->setStaffId($thisstaff->getId()); //direct assignment;
        }

        $this->lastrespondent = $response->staff;

        $this->onResponse($response, array('assignee' => $assignee)); //do house cleaning..

        /* email the user??  - if disabled - then bail out */
        if (!$alert)
            return $response;

        $dept = $this->getDept();

        if ($thisstaff && $vars['signature']=='mine')
            $signature=$thisstaff->getSignature();
        elseif ($vars['signature']=='dept' && $dept && $dept->isPublic())
            $signature=$dept->getSignature();
        else
            $signature='';

        $variables = array(
            'response' => $response,
            'signature' => $signature,
            'staff' => $thisstaff,
            'poster' => $thisstaff
        );
        $options = array(
            'inreplyto' => $response->getEmailMessageId(),
            'references' => $response->getEmailReferences(),
            'thread'=>$response
        );

        if (($email=$dept->getEmail())
            && ($tpl = $dept->getTemplate())
            && ($msg=$tpl->getReplyMsgTemplate())
        ) {
            $msg = $this->replaceVars($msg->asArray(),
                $variables + array('recipient' => $this->getOwner())
            );
            $attachments = $cfg->emailAttachments()?$response->getAttachments():array();
            $email->send($this->getOwner(), $msg['subj'], $msg['body'], $attachments,
                $options);
        }

        if ($vars['emailcollab']) {
            $this->notifyCollaborators($response,
                array('signature' => $signature)
            );
        }
        return $response;
    }

    //Activity log - saved as internal notes WHEN enabled!!
    function logActivity($title, $note) {
        return $this->logNote($title, $note, 'SYSTEM', false);
    }

    // History log -- used for statistics generation (pretty reports)
    function logEvent($state, $data=null, $user=null, $annul=null) {
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

        //Who is posting the note - staff or system?
        if ($vars['staffId'] && !$poster)
            $poster = Staff::lookup($vars['staffId']);

        $vars['staffId'] = $vars['staffId'] ?: 0;
        if ($poster && is_object($poster)) {
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
        $name = 'Ticket-'.$this->getNumber().'.pdf';
        Http::download($name, 'application/pdf', $pdf->Output($name, 'S'));
        //Remember what the user selected - for autoselect on the next print.
        $_SESSION['PAPER_SIZE'] = $psize;
        exit;
    }

    function delete($comments='') {
        global $ost, $thisstaff;

        //delete just orphaned ticket thread & associated attachments.
        // Fetch thread prior to removing ticket entry
        $t = $this->getThread();

        if (!parent::delete())
            return false;

        $t->delete();

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
        }
        return parent::save($this->dirty || $refetch);
    }

    function update($vars, &$errors) {
        global $cfg, $thisstaff;

        if (!$cfg
            || !($this->checkStaffPerm($thisstaff,
                TicketModel::PERM_EDIT))
        ) {
            return false;
        }
        $fields = array();
        $fields['topicId']  = array('type'=>'int',      'required'=>1, 'error'=>__('Help topic selection is required'));
        $fields['slaId']    = array('type'=>'int',      'required'=>0, 'error'=>__('Select a valid SLA'));
        $fields['duedate']  = array('type'=>'date',     'required'=>0, 'error'=>__('Invalid date format - must be MM/DD/YY'));

        $fields['user_id']  = array('type'=>'int',      'required'=>0, 'error'=>__('Invalid user-id'));

        if (!Validator::process($fields, $vars, $errors) && !$errors['err'])
            $errors['err'] = __('Missing or invalid data - check the errors and try again');

        if ($vars['duedate']) {
            if ($this->isClosed())
                $errors['duedate']=__('Due date can NOT be set on a closed ticket');
            elseif (!$vars['time'] || strpos($vars['time'],':') === false)
                $errors['time']=__('Select a time from the list');
            elseif (strtotime($vars['duedate'].' '.$vars['time']) === false)
                $errors['duedate']=__('Invalid due date');
            // FIXME: Using time() violates database and user timezone
            elseif (strtotime($vars['duedate'].' '.$vars['time']) <= time())
                $errors['duedate']=__('Due date must be in the future');
        }

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
            ? date('Y-m-d G:i',Misc::dbtime($vars['duedate'].' '.$vars['time']))
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

        if ($vars['note'])
            $this->logNote(_S('Ticket Updated'), $vars['note'], $thisstaff);

        // Update dynamic meta-data
        foreach ($forms as $f) {
            if ($C = $f->getChanges())
                $changes['fields'] = ($changes['fields'] ?: array()) + $C;
            // Drop deleted forms
            $idx = array_search($f->getId(), $vars['forms']);
            if ($idx === false) {
                $f->delete();
            }
            else {
                $f->set('sort', $idx);
                $f->save();
            }
        }

        if ($changes)
            $this->logEvent('edited', $changes);

        // Reselect SLA if transient
        if (!$keepSLA
            && (!$this->getSLA() || $this->getSLA()->isTransient())
        ) {
            $this->selectSLAId();
        }

        // Update estimated due date in database
        $estimatedDueDate = $this->getEstDueDate();
        $this->updateEstDueDate();

        // Clear overdue flag if duedate or SLA changes and the ticket is no longer overdue.
        if($this->isOverdue()
            && (!$estimatedDueDate //Duedate + SLA cleared
                || Misc::db2gmtime($estimatedDueDate) > Misc::gmtime() //New due date in the future.
        )) {
            $this->clearOverdue();
        }

        Signal::send('model.updated', $this);
        return $this->save();
    }

   /*============== Static functions. Use Ticket::function(params); =============nolint*/
    static function getIdByNumber($number, $email=null, $ticket=false) {

        if (!$number)
            return 0;

        $query = static::objects()
            ->filter(array('number' => $number));

        if ($email)
            $query->filter(array('user__emails__address' => $email));


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
        return 0 === static::objects()
            ->filter(array('number' => $number))
            ->count();
    }

    /* Quick staff's tickets stats */
    function getStaffStats($staff) {
        global $cfg;

        /* Unknown or invalid staff */
        if(!$staff || (!is_object($staff) && !($staff=Staff::lookup($staff))) || !$staff->isStaff())
            return null;

        // -- Open and assigned to me
        $assigned = Q::any(array(
            'staff_id' => $staff->getId(),
        ));
        // -- Open and assigned to a team of mine
        if ($teams = array_filter($staff->getTeams()))
            $assigned->add(array('team_id__in' => $teams));

        $visibility = Q::any(new Q(array('status__state'=>'open', $assigned)));

        // -- Routed to a department of mine
        if (!$staff->showAssignedOnly() && ($depts = $staff->getDepts()))
            $visibility->add(array('dept_id__in' => $depts));

        $blocks = Ticket::objects()
            ->filter(Q::any($visibility))
            ->filter(array('status__state' => 'open'))
            ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')))
            ->values('status__state', 'isanswered', 'isoverdue','staff_id', 'team_id');

        $stats = array();
        $hideassigned = ($cfg && !$cfg->showAssignedTickets()) && !$staff->showAssignedTickets();
        $showanswered = $cfg->showAnsweredTickets();
        $id = $staff->getId();
        foreach ($blocks as $S) {
            if ($showanswered || !$S['isanswered']) {
                if (!($hideassigned && ($S['staff_id'] || $S['team_id'])))
                    $stats['open'] += $S['count'];
            }
            else {
                $stats['answered'] += $S['count'];
            }
            if ($S['isoverdue'])
                $stats['overdue'] += $S['count'];
            if ($S['staff_id'] == $id)
                $stats['assigned'] += $S['count'];
            elseif ($S['team_id'] && $S['staff_id'] == 0)
                $stats['assigned'] += $S['count'];
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

    protected function filterTicketData($origin, $vars, $forms, $user=false) {
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
                $field = $user_form->getField($F);
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
            $ticket_filter->apply($vars);
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
        global $ost, $cfg, $thisclient, $thisstaff;

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
            $ost->logWarning(_S('Ticket Denied'), $message, false);
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
                $fields['topicId']  = array('type'=>'int',  'required'=>1, 'error'=>__('Select a help topic'));
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
            $errors['err'] =__('Missing or invalid data - check the errors and try again');

        //Make sure the due date is valid
        if($vars['duedate']) {
            if(!$vars['time'] || strpos($vars['time'],':')===false)
                $errors['time']=__('Select a time from the list');
            elseif(strtotime($vars['duedate'].' '.$vars['time'])===false)
                $errors['duedate']=__('Invalid due date');
            elseif(strtotime($vars['duedate'].' '.$vars['time'])<=time())
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
                    array_merge(array($form), $topic_forms), $user);
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
            if ($topic=Topic::lookup($vars['topicId'])) {
                foreach ($topic_forms as $topic_form) {
                    $TF = $topic_form->getForm($vars);
                    if (!$TF->isValid($field_filter('topic')))
                        $errors = array_merge($errors, $TF->errors());
                }
            }
            else  {
                $errors['topicId'] = 'Invalid help topic selected';
            }
        }

        // Any error above is fatal.
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
        $ticket = parent::create(array(
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
                Misc::dbtime($vars['duedate'].' '.$vars['time']));


        if (!$ticket->save())
            return null;
        if (!($thread = TicketThread::create($ticket->getId())))
            return null;

        /* -------------------- POST CREATE ------------------------ */

        // Save the (common) dynamic form
        // Ensure we have a subject
        $subject = $form->getAnswer('subject');
        if ($subject && !$subject->getValue()) {
            if ($topic) {
                $form->setAnswer('subject', $topic->getFullName());
            }
        }
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
                $ticket->logEvent('collab', array('org' => $org->getId()));
            }
        }

        //post the message.
        $vars['title'] = $vars['subject']; //Use the initial subject as title of the post.
        $vars['userId'] = $ticket->getUserId();
        $message = $ticket->postMessage($vars , $origin, false);

        // If a message was posted, flag it as the orignal message. This
        // needs to be done on new ticket, so as to otherwise separate the
        // concept from the first message entry in a thread.
        if ($message instanceof ThreadEntry) {
            $message->setFlag(ThreadEntry::FLAG_ORIGINAL_MESSAGE);
            $message->save();
        }

        // Configure service-level-agreement for this ticket
        $ticket->selectSLAId($vars['slaId']);

        // Assign ticket to staff or team (new ticket by staff)
        if ($vars['assignId']) {
            $asnform = new AssignmentForm(array('assignee' => $vars['assignId']));
            $ticket->assign($asnform, $vars['note']);
        }
        else {
            // Auto assign staff or team - auto assignment based on filter
            // rules. Both team and staff can be assigned
            if ($vars['staffId'])
                 $ticket->assignToStaff($vars['staffId'], false);
            if ($vars['teamId'])
                // No team alert if also assigned to an individual agent
                $ticket->assignToTeam($vars['teamId'], false, !$vars['staffId']);
        }

        // Update the estimated due date in the database
        $ticket->updateEstDueDate();

        // Apply requested status — this should be done AFTER assignment,
        // because if it is requested to be closed, it should not cause the
        // ticket to be reopened for assignment.
        if ($statusId) {
            if (!$ticket->setStatus($statusId, false, $errors, false)) {
                // Tickets _must_ have a status. Forceably set one here
                $ticket->setStatusId($cfg->getDefaultTicketStatusId());
            }
        }

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
            && ($role = $thisstaff->getRole($vars['deptId']))
            && !$role->hasPerm(TicketModel::PERM_CREATE)
        ) {
            $errors['err'] = __('You do not have permission to create a ticket in this department');
            return false;
        }

        if ($vars['source'] && !in_array(
            strtolower($vars['source']), array('email','phone','other'))
        ) {
            $errors['source'] = sprintf(
                __('Invalid source given - %s'),Format::htmlchars($vars['source'])
            );
        }

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
            ? $role->hasPerm(TicketModel::PERM_ASSIGN)
            : $thisstaff->hasPerm(TicketModel::PERM_ASSIGN, false)
        )) {
            $errors['assignId'] = __('Action Denied. You are not allowed to assign/reassign tickets.');
        }

        // TODO: Deny action based on selected department.

        $create_vars = $vars;
        $tform = TicketForm::objects()->one()->getForm($create_vars);
        $create_vars['cannedattachments']
            = $tform->getField('message')->getWidget()->getAttachments()->getClean();

        if (!($ticket=Ticket::create($create_vars, $errors, 'staff', false)))
            return false;

        $vars['msgId']=$ticket->getLastMsgId();

        // Effective role for the department
        $role = $thisstaff->getRole($ticket->getDeptId());

        // post response - if any
        $response = null;
        if($vars['response'] && $role->hasPerm(TicketModel::PERM_REPLY)) {
            $vars['response'] = $ticket->replaceVars($vars['response']);
            // $vars['cannedatachments'] contains the attachments placed on
            // the response form.
            $response = $ticket->postReply($vars, $errors, false);
        }

        // Not assigned...save optional note if any
        if (!$vars['assignId'] && $vars['note']) {
            if (!$cfg->isRichTextEnabled()) {
                $vars['note'] = new TextThreadEntryBody($vars['note']);
            }
            $ticket->logNote(_S('New Ticket'), $vars['note'], $thisstaff, false);
        }

        if (!$cfg->notifyONNewStaffTicket()
            || !isset($vars['alertuser'])
            || !($dept=$ticket->getDept())
        ) {
            return $ticket; //No alerts.
        }
        // Send Notice to user --- if requested AND enabled!!
        if (($tpl=$dept->getTemplate())
            && ($msg=$tpl->getNewTicketNoticeMsgTemplate())
            && ($email=$dept->getEmail())
        ) {
            $message = (string) $ticket->getLastMessage();
            if ($response) {
                $message .= ($cfg->isRichTextEnabled()) ? "<br><br>" : "\n\n";
                $message .= $response->getBody();
            }

            if ($vars['signature']=='mine')
                $signature=$thisstaff->getSignature();
            elseif ($vars['signature']=='dept' && $dept && $dept->isPublic())
                $signature=$dept->getSignature();
            else
                $signature='';

            $attachments = ($cfg->emailAttachments() && $response)
                ? $response->getAttachments() : array();

            $msg = $ticket->replaceVars($msg->asArray(),
                array(
                    'message'   => $message,
                    'signature' => $signature,
                    'response'  => ($response) ? $response->getBody() : '',
                    'recipient' => $ticket->getOwner(), //End user
                    'staff'     => $thisstaff,
                )
            );
            $references = array();
            $message = $ticket->getLastMessage();
            if (isset($message))
                $references[] = $message->getEmailMessageId();
            if (isset($response))
                $references[] = $response->getEmailMessageId();
            $options = array(
                'references' => $references,
                'thread' => $message ?: $ticket->getThread(),
            );
            $email->send($ticket->getOwner(), $msg['subj'], $msg['body'], $attachments,
                $options);
        }
        return $ticket;
    }

    static function checkOverdue() {
        /*
        $overdue = static::objects()
            ->filter(array(
                'isoverdue' => 0,
                Q::any(array(
                    Q::all(array(
                        'reopened__isnull' => true,
                        'duedate__isnull' => true,

         Punt for now
         */

        $sql='SELECT ticket_id FROM '.TICKET_TABLE.' T1 '
            .' INNER JOIN '.TICKET_STATUS_TABLE.' status
                ON (status.id=T1.status_id AND status.state="open") '
            .' LEFT JOIN '.SLA_TABLE.' T2 ON (T1.sla_id=T2.id AND T2.flags & 1 = 1) '
            .' WHERE isoverdue=0 '
            .' AND ((reopened is NULL AND duedate is NULL AND TIME_TO_SEC(TIMEDIFF(NOW(),T1.created))>=T2.grace_period*3600) '
            .' OR (reopened is NOT NULL AND duedate is NULL AND TIME_TO_SEC(TIMEDIFF(NOW(),reopened))>=T2.grace_period*3600) '
            .' OR (duedate is NOT NULL AND duedate<NOW()) '
            .' ) ORDER BY T1.created LIMIT 50'; //Age upto 50 tickets at a time?

        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($id)=db_fetch_row($res)) {
                if ($ticket=Ticket::lookup($id))
                    $ticket->markOverdue();
            }
        } else {
            //TODO: Trigger escalation on already overdue tickets - make sure last overdue event > grace_period.

        }
   }

    static function agentActions($agent, $options=array()) {
        if (!$agent)
            return;

        require STAFFINC_DIR.'templates/tickets-actions.tmpl.php';
    }
}
?>
