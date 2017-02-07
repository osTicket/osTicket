<?php
/*********************************************************************
    class.task.php

    Task

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

include_once INCLUDE_DIR.'class.role.php';


class TaskModel extends VerySimpleModel {
    static $meta = array(
        'table' => TASK_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'dept' => array(
                'constraint' => array('dept_id' => 'Dept.id'),
            ),
            'lock' => array(
                'constraint' => array('lock_id' => 'Lock.lock_id'),
                'null' => true,
            ),
            'staff' => array(
                'constraint' => array('staff_id' => 'Staff.staff_id'),
                'null' => true,
            ),
            'team' => array(
                'constraint' => array('team_id' => 'Team.team_id'),
                'null' => true,
            ),
            'thread' => array(
                'constraint' => array(
                    'id'  => 'TaskThread.object_id',
                    "'A'" => 'TaskThread.object_type',
                ),
                'list' => false,
                'null' => false,
            ),
            'cdata' => array(
                'constraint' => array('id' => 'TaskCData.task_id'),
                'list' => false,
            ),
            'entries' => array(
                'constraint' => array(
                    "'A'" => 'DynamicFormEntry.object_type',
                    'id' => 'DynamicFormEntry.object_id',
                ),
                'list' => true,
            ),

            'ticket' => array(
                'constraint' => array(
                    'object_type' => "'T'",
                    'object_id' => 'Ticket.ticket_id',
                ),
                'null' => true,
            ),
        ),
    );

    const PERM_CREATE   = 'task.create';
    const PERM_EDIT     = 'task.edit';
    const PERM_ASSIGN   = 'task.assign';
    const PERM_TRANSFER = 'task.transfer';
    const PERM_REPLY    = 'task.reply';
    const PERM_CLOSE    = 'task.close';
    const PERM_DELETE   = 'task.delete';

    static protected $perms = array(
            self::PERM_CREATE    => array(
                'title' =>
                /* @trans */ 'Create',
                'desc'  =>
                /* @trans */ 'Ability to create tasks'),
            self::PERM_EDIT      => array(
                'title' =>
                /* @trans */ 'Edit',
                'desc'  =>
                /* @trans */ 'Ability to edit tasks'),
            self::PERM_ASSIGN    => array(
                'title' =>
                /* @trans */ 'Assign',
                'desc'  =>
                /* @trans */ 'Ability to assign tasks to agents or teams'),
            self::PERM_TRANSFER  => array(
                'title' =>
                /* @trans */ 'Transfer',
                'desc'  =>
                /* @trans */ 'Ability to transfer tasks between departments'),
            self::PERM_REPLY => array(
                'title' =>
                /* @trans */ 'Post Reply',
                'desc'  =>
                /* @trans */ 'Ability to post task update'),
            self::PERM_CLOSE     => array(
                'title' =>
                /* @trans */ 'Close',
                'desc'  =>
                /* @trans */ 'Ability to close tasks'),
            self::PERM_DELETE    => array(
                'title' =>
                /* @trans */ 'Delete',
                'desc'  =>
                /* @trans */ 'Ability to delete tasks'),
            );

    const ISOPEN    = 0x0001;
    const ISOVERDUE = 0x0002;


    protected function hasFlag($flag) {
        return ($this->get('flags') & $flag) !== 0;
    }

    protected function clearFlag($flag) {
        return $this->set('flags', $this->get('flags') & ~$flag);
    }

    protected function setFlag($flag) {
        return $this->set('flags', $this->get('flags') | $flag);
    }

    function getId() {
        return $this->id;
    }

    function getNumber() {
        return $this->number;
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

    function getDeptId() {
        return $this->dept_id;
    }

    function getDept() {
        return $this->dept;
    }

    function getCreateDate() {
        return $this->created;
    }

    function getDueDate() {
        return $this->duedate;
    }

    function getCloseDate() {
        return $this->isClosed() ? $this->closed : '';
    }

    function isOpen() {
        return $this->hasFlag(self::ISOPEN);
    }

    function isClosed() {
        return !$this->isOpen();
    }

    function isCloseable() {

        if ($this->isClosed())
            return true;

        $warning = null;
        if ($this->getMissingRequiredFields()) {
            $warning = sprintf(
                    __( '%1$s is missing data on %2$s one or more required fields %3$s and cannot be closed'),
                    __('This task'),
                    '', '');
        }

        return $warning ?: true;
    }

    protected function close() {
        return $this->clearFlag(self::ISOPEN);
    }

    protected function reopen() {
        return $this->setFlag(self::ISOPEN);
    }

    function isAssigned() {
        return ($this->isOpen() && ($this->getStaffId() || $this->getTeamId()));
    }

    function isOverdue() {
        return $this->hasFlag(self::ISOVERDUE);
    }

    static function getPermissions() {
        return self::$perms;
    }

}

RolePermission::register(/* @trans */ 'Tasks', TaskModel::getPermissions());


class Task extends TaskModel implements RestrictedAccess, Threadable {
    var $form;
    var $entry;

    var $_thread;
    var $_entries;
    var $_answers;

    var $lastrespondent;

    function __onload() {
        $this->loadDynamicData();
    }

    function loadDynamicData() {
        if (!isset($this->_answers)) {
            $this->_answers = array();
            foreach (DynamicFormEntryAnswer::objects()
                ->filter(array(
                    'entry__object_id' => $this->getId(),
                    'entry__object_type' => ObjectModel::OBJECT_TYPE_TASK
                )) as $answer
            ) {
                $tag = mb_strtolower($answer->field->name)
                    ?: 'field.' . $answer->field->id;
                    $this->_answers[$tag] = $answer;
            }
        }
        return $this->_answers;
    }

    function getStatus() {
        return $this->isOpen() ? __('Open') : __('Completed');
    }

    function getTitle() {
        return $this->__cdata('title', ObjectModel::OBJECT_TYPE_TASK);
    }

    function checkStaffPerm($staff, $perm=null) {

        // Must be a valid staff
        if (!$staff instanceof Staff && !($staff=Staff::lookup($staff)))
            return false;

        // Check access based on department or assignment
        if (!$staff->canAccessDept($this->getDeptId())
                && $this->isOpen()
                && $staff->getId() != $this->getStaffId()
                && !$staff->isTeamMember($this->getTeamId()))
            return false;

        // At this point staff has access unless a specific permission is
        // requested
        if ($perm === null)
            return true;

        // Permission check requested -- get role.
        if (!($role=$staff->getRole($this->getDeptId())))
            return false;

        // Check permission based on the effective role
        return $role->hasPerm($perm);
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

    function getAssignees() {

        $assignees=array();
        if ($this->staff)
            $assignees[] = $this->staff->getName();

        //Add team assignment
        if ($this->team)
            $assignees[] = $this->team->getName();

        return $assignees;
    }

    function getAssigned($glue='/') {
        $assignees = $this->getAssignees();

        return $assignees ? implode($glue, $assignees):'';
    }

    function getLastRespondent() {

        if (!isset($this->lastrespondent)) {
            $this->lastrespondent = Staff::objects()
                ->filter(array(
                'staff_id' => static::objects()
                    ->filter(array(
                        'thread__entries__type' => 'R',
                        'thread__entries__staff_id__gt' => 0
                    ))
                    ->values_flat('thread__entries__staff_id')
                    ->order_by('-thread__entries__id')
                    ->limit(1)
                ))
                ->first()
                ?: false;
        }

        return $this->lastrespondent;
    }

    function getDynamicFields($criteria=array()) {

        $fields = DynamicFormField::objects()->filter(array(
                    'id__in' => $this->entries
                    ->filter($criteria)
                ->values_flat('answers__field_id')));

        return ($fields && count($fields)) ? $fields : array();
    }

    function getMissingRequiredFields() {

        return $this->getDynamicFields(array(
                    'answers__field__flags__hasbit' => DynamicFormField::FLAG_ENABLED,
                    'answers__field__flags__hasbit' => DynamicFormField::FLAG_CLOSE_REQUIRED,
                    'answers__value__isnull' => true,
                    ));
    }

    function getParticipants() {
        $participants = array();
        foreach ($this->getThread()->collaborators as $c)
            $participants[] = $c->getName();

        return $participants ? implode(', ', $participants) : ' ';
    }

    function getThreadId() {
        return $this->thread->getId();
    }

    function getThread() {
        return $this->thread;
    }

    function getThreadEntry($id) {
        return $this->getThread()->getEntry($id);
    }

    function getThreadEntries($type=false) {
        $thread = $this->getThread()->getEntries();
        if ($type && is_array($type))
            $thread->filter(array('type__in' => $type));
        return $thread;
    }

    function postThreadEntry($type, $vars, $options=array()) {
        $errors = array();
        $poster = isset($options['poster']) ? $options['poster'] : null;
        $alert = isset($options['alert']) ? $options['alert'] : true;
        switch ($type) {
        case 'N':
        default:
            return $this->postNote($vars, $errors, $poster, $alert);
        }
    }

    function getForm() {
        if (!isset($this->form)) {
            // Look for the entry first
            if ($this->form = DynamicFormEntry::lookup(
                        array('object_type' => ObjectModel::OBJECT_TYPE_TASK))) {
                return $this->form;
            }
            // Make sure the form is in the database
            elseif (!($this->form = DynamicForm::lookup(
                            array('type' => ObjectModel::OBJECT_TYPE_TASK)))) {
                $this->__loadDefaultForm();
                return $this->getForm();
            }
            // Create an entry to be saved later
            $this->form = $this->form->instanciate();
            $this->form->object_type = ObjectModel::OBJECT_TYPE_TASK;
        }

        return $this->form;
    }

    function getAssignmentForm($source=null, $options=array()) {
        $prompt = $assignee = '';
        // Possible assignees
        $assignees = array();
        switch (strtolower($options['target'])) {
            case 'agents':
                $dept = $this->getDept();
                foreach ($dept->getAssignees() as $member)
                    $assignees['s'.$member->getId()] = $member;

                if (!$source && $this->isOpen() && $this->staff)
                    $assignee = sprintf('s%d', $this->staff->getId());
                $prompt = __('Select an Agent');
                break;
            case 'teams':
                if (($teams = Team::getActiveTeams()))
                    foreach ($teams as $id => $name)
                        $assignees['t'.$id] = $name;

                if (!$source && $this->isOpen() && $this->team)
                    $assignee = sprintf('t%d', $this->team->getId());
                $prompt = __('Select a Team');
                break;
        }

        // Default to current assignee if source is not set
        if (!$source)
            $source = array('assignee' => array($assignee));

        $form = AssignmentForm::instantiate($source, $options);

        if ($assignees)
            $form->setAssignees($assignees);

        if ($prompt && ($f=$form->getField('assignee')))
            $f->configure('prompt', $prompt);


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

        if (!$source)
            $source = array('dept' => array($this->getDeptId()));

        return TransferForm::instantiate($source);
    }

    function addDynamicData($data) {

        $tf = TaskForm::getInstance($this->id, true);
        foreach ($tf->getFields() as $f)
            if (isset($data[$f->get('name')]))
                $tf->setAnswer($f->get('name'), $data[$f->get('name')]);

        $tf->save();

        return $tf;
    }

    function getDynamicData($create=true) {
        if (!isset($this->_entries)) {
            $this->_entries = DynamicFormEntry::forObject($this->id,
                    ObjectModel::OBJECT_TYPE_TASK)->all();
            if (!$this->_entries && $create) {
                $f = TaskForm::getInstance($this->id, true);
                $f->save();
                $this->_entries[] = $f;
            }
        }

        return $this->_entries ?: array();
    }

    function setStatus($status, $comments='', &$errors=array()) {
        global $thisstaff;

        $ecb = null;
        switch($status) {
        case 'open':
            if ($this->isOpen())
                return false;

            $this->reopen();
            $this->closed = null;

            $ecb = function ($t) {
                $t->logEvent('reopened', false, null, 'closed');
            };
            break;
        case 'closed':
            if ($this->isClosed())
                return false;

            // Check if task is closeable
            $closeable = $this->isCloseable();
            if ($closeable !== true)
                $errors['err'] = $closeable ?: sprintf(__('%s cannot be closed'), __('This task'));

            if ($errors)
                return false;

            $this->close();
            $this->closed = SqlFunction::NOW();
            $ecb = function($t) {
                $t->logEvent('closed');
            };
            break;
        default:
            return false;
        }

        if (!$this->save(true))
            return false;

        // Log events via callback
        if ($ecb) $ecb($this);

        if ($comments) {
            $errors = array();
            $this->postNote(array(
                        'note' => $comments,
                        'title' => sprintf(
                            __('Status changed to %s'),
                            $this->getStatus())
                        ),
                    $errors,
                    $thisstaff);
        }

        return true;
    }

    function to_json() {

        $info = array(
                'id'  => $this->getId(),
                'title' => $this->getTitle()
                );

        return JsonDataEncoder::encode($info);
    }

    function __cdata($field, $ftype=null) {

        foreach ($this->getDynamicData() as $e) {
            // Make sure the form type matches
            if (!$e->form
                    || ($ftype && $ftype != $e->form->get('type')))
                continue;

            // Get the named field and return the answer
            if ($a = $e->getAnswer($field))
                return $a;
        }

        return null;
    }

    function __toString() {
        return (string) $this->getTitle();
    }

    /* util routines */

    function logEvent($state, $data=null, $user=null, $annul=null) {
        $this->getThread()->getEvents()->log($this, $state, $data, $user, $annul);
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
        } elseif ($dept->assignMembersOnly() && !$dept->isMember($assignee)) {
            $errors['err'] = __('Permission denied');
        }

        if ($errors)
            return false;

        return $this->assignToStaff($assignee, $form->getComments(), false);
    }

    function assignToStaff($staff, $note, $alert=true) {

        if(!is_object($staff) && !($staff = Staff::lookup($staff)))
            return false;

        if (!$staff->isAvailable())
            return false;

        $this->staff_id = $staff->getId();

        if (!$this->save())
            return false;

        $this->onAssignment($staff, $note, $alert);

        global $thisstaff;
        $data = array();
        if ($thisstaff && $staff->getId() == $thisstaff->getId())
            $data['claim'] = true;
        else
            $data['staff'] = $staff->getId();

        $this->logEvent('assigned', $data);

        return true;
    }


    function assign(AssignmentForm $form, &$errors, $alert=true) {
        global $thisstaff;

        $evd = array();
        $assignee = $form->getAssignee();
        if ($assignee instanceof Staff) {
            if ($this->getStaffId() == $assignee->getId()) {
                $errors['assignee'] = sprintf(__('%s already assigned to %s'),
                        __('Task'),
                        __('the agent')
                        );
            } elseif(!$assignee->isAvailable()) {
                $errors['assignee'] = __('Agent is unavailable for assignment');
            } else {
                $this->staff_id = $assignee->getId();
                if ($thisstaff && $thisstaff->getId() == $assignee->getId())
                    $evd['claim'] = true;
                else
                    $evd['staff'] = array($assignee->getId(), $assignee->getName());
            }
        } elseif ($assignee instanceof Team) {
            if ($this->getTeamId() == $assignee->getId()) {
                $errors['assignee'] = sprintf(__('%s already assigned to %s'),
                        __('Task'),
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

        $this->onAssignment($assignee,
                $form->getField('comments')->getClean(),
                $alert);

        return true;
    }

    function onAssignment($assignee, $comments='', $alert=true) {
        global $thisstaff, $cfg;

        if (!is_object($assignee))
            return false;

        $assigner = $thisstaff ?: __('SYSTEM (Auto Assignment)');

        //Assignment completed... post internal note.
        $note = null;
        if ($comments) {

            $title = sprintf(__('Task assigned to %s'),
                    (string) $assignee);

            $errors = array();
            $note = $this->postNote(
                    array('note' => $comments, 'title' => $title),
                    $errors,
                    $assigner,
                    false);
        }

        // Send alerts out if enabled.
        if (!$alert || !$cfg->alertONTaskAssignment())
            return false;

        if (!($dept=$this->getDept())
            || !($tpl = $dept->getTemplate())
            || !($email = $dept->getAlertEmail())
        ) {
            return true;
        }

        // Recipients
        $recipients = array();
        if ($assignee instanceof Staff) {
            if ($cfg->alertStaffONTaskAssignment())
                $recipients[] = $assignee;
        } elseif (($assignee instanceof Team) && $assignee->alertsEnabled()) {
            if ($cfg->alertTeamMembersONTaskAssignment() && ($members=$assignee->getMembers()))
                $recipients = array_merge($recipients, $members);
            elseif ($cfg->alertTeamLeadONTaskAssignment() && ($lead=$assignee->getTeamLead()))
                $recipients[] = $lead;
        }

        if ($recipients
            && ($msg=$tpl->getTaskAssignmentAlertMsgTemplate())) {

            $msg = $this->replaceVars($msg->asArray(),
                array('comments' => $comments,
                      'assignee' => $assignee,
                      'assigner' => $assigner
                )
            );
            // Send the alerts.
            $sentlist = array();
            $options = $note instanceof ThreadEntry
                ? array('thread' => $note)
                : array();

            foreach ($recipients as $k => $staff) {
                if (!is_object($staff)
                    || !$staff->isAvailable()
                    || in_array($staff->getEmail(), $sentlist)) {
                    continue;
                }

                $alert = $this->replaceVars($msg, array('recipient' => $staff));
                $email->sendAlert($staff, $alert['subj'], $alert['body'], null, $options);
                $sentlist[] = $staff->getEmail();
            }
        }

        return true;
    }

    function transfer(TransferForm $form, &$errors, $alert=true) {
        global $thisstaff, $cfg;

        $cdept = $this->getDept();
        $dept = $form->getDept();
        if (!$dept || !($dept instanceof Dept))
            $errors['dept'] = __('Department selection is required');
        elseif ($dept->getid() == $this->getDeptId())
            $errors['dept'] = __('Task already in the department');
        else
            $this->dept_id = $dept->getId();

        if ($errors || !$this->save(true))
            return false;

        // Log transfer event
        $this->logEvent('transferred');

        // Post internal note if any
        $note = $form->getField('comments')->getClean();
        if ($note) {
            $title = sprintf(__('%1$s transferred from %2$s to %3$s'),
                    __('Task'),
                   $cdept->getName(),
                    $dept->getName());

            $_errors = array();
            $note = $this->postNote(
                    array('note' => $note, 'title' => $title),
                    $_errors, $thisstaff, false);
        }

        // Send alerts if requested && enabled.
        if (!$alert || !$cfg->alertONTaskTransfer())
            return true;

        if (($email = $dept->getAlertEmail())
             && ($tpl = $dept->getTemplate())
             && ($msg=$tpl->getTaskTransferAlertMsgTemplate())) {

            $msg = $this->replaceVars($msg->asArray(),
                array('comments' => $note, 'staff' => $thisstaff));
            // Recipients
            $recipients = array();
            // Assigned staff or team... if any
            if ($this->isAssigned() && $cfg->alertAssignedONTaskTransfer()) {
                if($this->getStaffId())
                    $recipients[] = $this->getStaff();
                elseif ($this->getTeamId()
                    && ($team=$this->getTeam())
                    && ($members=$team->getMembers())
                ) {
                    $recipients = array_merge($recipients, $members);
                }
            } elseif ($cfg->alertDeptMembersONTaskTransfer() && !$this->isAssigned()) {
                // Only alerts dept members if the task is NOT assigned.
                foreach ($dept->getMembersForAlerts() as $M)
                    $recipients[] = $M;
            }

            // Always alert dept manager??
            if ($cfg->alertDeptManagerONTaskTransfer()
                && ($manager=$dept->getManager())) {
                $recipients[] = $manager;
            }

            $sentlist = $options = array();
            if ($note instanceof ThreadEntry) {
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

    function postNote($vars, &$errors, $poster='', $alert=true) {
        global $cfg, $thisstaff;

        $vars['staffId'] = 0;
        $vars['poster'] = 'SYSTEM';
        if ($poster && is_object($poster)) {
            $vars['staffId'] = $poster->getId();
            $vars['poster'] = $poster->getName();
        } elseif ($poster) { //string
            $vars['poster'] = $poster;
        }

        if (!($note=$this->getThread()->addNote($vars, $errors)))
            return null;

        $assignee = $this->getStaff();

        if (isset($vars['task:status']))
            $this->setStatus($vars['task:status']);

        $this->onActivity(array(
            'activity' => $note->getActivity(),
            'threadentry' => $note,
            'assignee' => $assignee
        ), $alert);

        return $note;
    }

    /* public */
    function postReply($vars, &$errors, $alert = true) {
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

        if (isset($vars['task:status']))
            $this->setStatus($vars['task:status']);

        /*
        // TODO: add auto claim setting for tasks.
        // Claim on response bypasses the department assignment restrictions
        if ($thisstaff
            && $this->isOpen()
            && !$this->getStaffId()
            && $cfg->autoClaimTasks)
        ) {
            $this->staff_id = $thisstaff->getId();
        }
        */

        $this->lastrespondent = $response->staff;
        $this->save();

        // Send activity alert to agents
        $activity = $vars['activity'] ?: $response->getActivity();
        $this->onActivity( array(
                    'activity' => $activity,
                    'threadentry' => $response,
                    'assignee' => $assignee,
                    ));
        // Send alert to collaborators
        if ($alert && $vars['emailcollab']) {
            $signature = '';
            $this->notifyCollaborators($response,
                array('signature' => $signature)
            );
        }

        return $response;
    }

    function pdfExport($options=array()) {
        global $thisstaff;

        require_once(INCLUDE_DIR.'class.pdf.php');
        if (!isset($options['psize'])) {
            if ($_SESSION['PAPER_SIZE'])
                $psize = $_SESSION['PAPER_SIZE'];
            elseif (!$thisstaff || !($psize = $thisstaff->getDefaultPaperSize()))
                $psize = 'Letter';

            $options['psize'] = $psize;
        }

        $pdf = new Task2PDF($this, $options);
        $name = 'Task-'.$this->getNumber().'.pdf';
        Http::download($name, 'application/pdf', $pdf->Output($name, 'S'));
        //Remember what the user selected - for autoselect on the next print.
        $_SESSION['PAPER_SIZE'] = $options['psize'];
        exit;
    }

    /* util routines */
    function replaceVars($input, $vars = array()) {
        global $ost;

        return $ost->replaceTemplateVariables($input,
                array_merge($vars, array('task' => $this)));
    }

    function asVar() {
       return $this->getNumber();
    }

    function getVar($tag) {
        global $cfg;

        if ($tag && is_callable(array($this, 'get'.ucfirst($tag))))
            return call_user_func(array($this, 'get'.ucfirst($tag)));

        switch(mb_strtolower($tag)) {
        case 'phone':
        case 'phone_number':
            return $this->getPhoneNumber();
        case 'ticket_link':
            if ($ticket = $this->ticket) {
                return sprintf('%s/scp/tickets.php?id=%d#tasks',
                    $cfg->getBaseUrl(), $ticket->getId());
            }
        case 'staff_link':
            return sprintf('%s/scp/tasks.php?id=%d', $cfg->getBaseUrl(), $this->getId());
        case 'create_date':
            return new FormattedDate($this->getCreateDate());
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
        default:
            if (isset($this->_answers[$tag]))
                // The answer object is retrieved here which will
                // automatically invoke the toString() method when the
                // answer is coerced into text
                return $this->_answers[$tag];
        }
        return false;
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
            'number' => __('Task Number'),
            'recipients' => array(
                'class' => 'UserList', 'desc' => __('List of all recipient names'),
            ),
            'status' => __('Status'),
            'staff' => array(
                'class' => 'Staff', 'desc' => __('Assigned/closing agent'),
            ),
            'subject' => 'Subject',
            'team' => array(
                'class' => 'Team', 'desc' => __('Assigned/closing team'),
            ),
            'thread' => array(
                'class' => 'TaskThread', 'desc' => __('Task Thread'),
            ),
            'staff_link' => __('Link to view the task'),
            'ticket_link' => __('Link to view the task inside the ticket'),
            'last_update' => array(
                'class' => 'FormattedDate', 'desc' => __('Time of last update'),
            ),
        );

        $extra = VariableReplacer::compileFormScope(TaskForm::getInstance());
        return $base + $extra;
    }

    function onActivity($vars, $alert=true) {
        global $cfg, $thisstaff;

        if (!$alert // Check if alert is enabled
            || !$cfg->alertONTaskActivity()
            || !($dept=$this->getDept())
            || !($email=$cfg->getAlertEmail())
            || !($tpl = $dept->getTemplate())
            || !($msg=$tpl->getTaskActivityAlertMsgTemplate())
        ) {
            return;
        }

        // Alert recipients
        $recipients = array();
        //Last respondent.
        if ($cfg->alertLastRespondentONTaskActivity())
            $recipients[] = $this->getLastRespondent();

        // Assigned staff / team
        if ($cfg->alertAssignedONTaskActivity()) {
            if (isset($vars['assignee'])
                    && $vars['assignee'] instanceof Staff)
                 $recipients[] = $vars['assignee'];
            elseif ($this->isOpen() && ($assignee = $this->getStaff()))
                $recipients[] = $assignee;

            if ($team = $this->getTeam())
                $recipients = array_merge($recipients, $team->getMembers());
        }

        // Dept manager
        if ($cfg->alertDeptManagerONTaskActivity() && $dept && $dept->getManagerId())
            $recipients[] = $dept->getManager();

        $options = array();
        $staffId = $thisstaff ? $thisstaff->getId() : 0;
        if ($vars['threadentry'] && $vars['threadentry'] instanceof ThreadEntry) {
            $options = array('thread' => $vars['threadentry']);

            // Activity details
            if (!$vars['message'])
                $vars['message'] = $vars['threadentry'];

            // Staff doing the activity
            $staffId = $vars['threadentry']->getStaffId() ?: $staffId;
        }

        $msg = $this->replaceVars($msg->asArray(),
                array(
                    'note' => $vars['threadentry'], // For compatibility
                    'activity' => $vars['activity'],
                    'message' => $vars['message']));

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
                // Make sure staff has access to task
                || ($isClosed && !$this->checkStaffPerm($staff))
            ) {
                continue;
            }
            $alert = $this->replaceVars($msg, array('recipient' => $staff));
            $email->sendAlert($staff, $alert['subj'], $alert['body'], null, $options);
            $sentlist[$staff->getEmail()] = 1;
        }

    }

    /*
     * Notify collaborators on response or new message
     *
     */
    function  notifyCollaborators($entry, $vars = array()) {
        global $cfg;

        if (!$entry instanceof ThreadEntry
            || !($recipients=$this->getThread()->getParticipants())
            || !($dept=$this->getDept())
            || !($tpl=$dept->getTemplate())
            || !($msg=$tpl->getTaskActivityNoticeMsgTemplate())
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
        }

        $vars = array_merge($vars, array(
            'message' => (string) $entry,
            'poster' => $poster ?: _S('A collaborator'),
            )
        );

        $msg = $this->replaceVars($msg->asArray(), $vars);

        $attachments = $cfg->emailAttachments()?$entry->getAttachments():array();
        $options = array('thread' => $entry);

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

    function update($forms, $vars, &$errors) {
        global $thisstaff;


        if (!$forms || !$this->checkStaffPerm($thisstaff, Task::PERM_EDIT))
            return false;


        foreach ($forms as $form) {
            $form->setSource($vars);
            if (!$form->isValid(function($f) {
                return $f->isVisibleToStaff() && $f->isEditableToStaff();
            }, array('mode'=>'edit'))) {
                $errors = array_merge($errors, $form->errors());
            }
        }

        if ($errors)
            return false;

        // Update dynamic meta-data
        $changes = array();
        foreach ($forms as $f) {
            $changes += $f->getChanges();
            $f->save();
        }


        if ($vars['note']) {
            $_errors = array();
            $this->postNote(array(
                        'note' => $vars['note'],
                        'title' => __('Task Update'),
                        ),
                    $_errors,
                    $thisstaff);
        }

        if ($changes)
            $this->logEvent('edited', array('fields' => $changes));

        Signal::send('model.updated', $this);
        return $this->save();
    }

    /* static routines */
    static function lookupIdByNumber($number) {

        if (($task = self::lookup(array('number' => $number))))
            return $task->getId();

    }

    static function isNumberUnique($number) {
        return !self::lookupIdByNumber($number);
    }

    static function create($vars=false) {
        global $thisstaff, $cfg;

        if (!is_array($vars)
                || !$thisstaff
                || !$thisstaff->hasPerm(Task::PERM_CREATE, false))
            return null;

        $task = new static(array(
            'flags' => self::ISOPEN,
            'object_id' => $vars['object_id'],
            'object_type' => $vars['object_type'],
            'number' => $cfg->getNewTaskNumber(),
            'created' => new SqlFunction('NOW'),
            'updated' => new SqlFunction('NOW'),
        ));

        if ($vars['internal_formdata']['dept_id'])
            $task->dept_id = $vars['internal_formdata']['dept_id'];
        if ($vars['internal_formdata']['duedate'])
	    $task->duedate = date('Y-m-d G:i', Misc::dbtime($vars['internal_formdata']['duedate']));

        if (!$task->save(true))
            return false;

        // Add dynamic data
        $task->addDynamicData($vars['default_formdata']);

        // Create a thread + message.
        $thread = TaskThread::create($task);
        $thread->addDescription($vars);


        $task->logEvent('created', null, $thisstaff);

        // Get role for the dept
        $role = $thisstaff->getRole($task->dept_id);
        // Assignment
        $assignee = $vars['internal_formdata']['assignee'];
        if ($assignee
                // skip assignment if the user doesn't have perm.
                && $role->hasPerm(Task::PERM_ASSIGN)) {
            $_errors = array();
            $assigneeId = sprintf('%s%d',
                    ($assignee  instanceof Staff) ? 's' : 't',
                    $assignee->getId());

            $form = AssignmentForm::instantiate(array('assignee' => $assigneeId));

            $task->assign($form, $_errors);
        }

        Signal::send('task.created', $task);

        return $task;
    }

    function delete($comments='') {
        global $ost, $thisstaff;

        $thread = $this->getThread();

        if (!parent::delete())
            return false;

        $thread->delete();

        Draft::deleteForNamespace('task.%.' . $this->getId());

        foreach (DynamicFormEntry::forObject($this->getId(), ObjectModel::OBJECT_TYPE_TASK) as $form)
            $form->delete();

        // Log delete
        $log = sprintf(__('Task #%1$s deleted by %2$s'),
                $this->getNumber(),
                $thisstaff ? $thisstaff->getName() : __('SYSTEM'));

        if ($comments)
            $log .= sprintf('<hr>%s', $comments);

        $ost->logDebug(
                sprintf( __('Task #%s deleted'), $this->getNumber()),
                $log);

        return true;

    }

    static function __loadDefaultForm() {

        require_once INCLUDE_DIR.'class.i18n.php';

        $i18n = new Internationalization();
        $tpl = $i18n->getTemplate('form.yaml');
        foreach ($tpl->getData() as $f) {
            if ($f['type'] == ObjectModel::OBJECT_TYPE_TASK) {
                $form = DynamicForm::create($f);
                $form->save();
                break;
            }
        }
    }

    /* Quick staff's stats */
    static function getStaffStats($staff) {
        global $cfg;

        /* Unknown or invalid staff */
        if (!$staff
                || (!is_object($staff) && !($staff=Staff::lookup($staff)))
                || !$staff->isStaff())
            return null;

        $where = array('(task.staff_id='.db_input($staff->getId())
                    .sprintf(' AND task.flags & %d != 0 ', TaskModel::ISOPEN)
                    .') ');
        $where2 = '';

        if(($teams=$staff->getTeams()))
            $where[] = ' ( task.team_id IN('.implode(',', db_input(array_filter($teams)))
                        .') AND '
                        .sprintf('task.flags & %d != 0 ', TaskModel::ISOPEN)
                        .')';

        if(!$staff->showAssignedOnly() && ($depts=$staff->getDepts())) //Staff with limited access just see Assigned tasks.
            $where[] = 'task.dept_id IN('.implode(',', db_input($depts)).') ';

        $where = implode(' OR ', $where);
        if ($where) $where = 'AND ( '.$where.' ) ';

        $sql =  'SELECT \'open\', count(task.id ) AS tasks '
                .'FROM ' . TASK_TABLE . ' task '
                . sprintf(' WHERE task.flags & %d != 0 ', TaskModel::ISOPEN)
                . $where . $where2

                .'UNION SELECT \'overdue\', count( task.id ) AS tasks '
                .'FROM ' . TASK_TABLE . ' task '
                . sprintf(' WHERE task.flags & %d != 0 ', TaskModel::ISOPEN)
                . sprintf(' AND task.flags & %d != 0 ', TaskModel::ISOVERDUE)
                . $where

                .'UNION SELECT \'assigned\', count( task.id ) AS tasks '
                .'FROM ' . TASK_TABLE . ' task '
                . sprintf(' WHERE task.flags & %d != 0 ', TaskModel::ISOPEN)
                .'AND task.staff_id = ' . db_input($staff->getId()) . ' '
                . $where

                .'UNION SELECT \'closed\', count( task.id ) AS tasks '
                .'FROM ' . TASK_TABLE . ' task '
                . sprintf(' WHERE task.flags & %d = 0 ', TaskModel::ISOPEN)
                . $where;

        $res = db_query($sql);
        $stats = array();
        while ($row = db_fetch_row($res))
            $stats[$row[0]] = $row[1];

        return $stats;
    }

    static function getAgentActions($agent, $options=array()) {
        if (!$agent)
            return;

        require STAFFINC_DIR.'templates/tasks-actions.tmpl.php';
    }
}


class TaskCData extends VerySimpleModel {
    static $meta = array(
        'pk' => array('task_id'),
        'table' => TASK_CDATA_TABLE,
        'joins' => array(
            'task' => array(
                'constraint' => array('task_id' => 'TaskModel.task_id'),
            ),
        ),
    );
}


class TaskForm extends DynamicForm {
    static $instance;
    static $defaultForm;
    static $internalForm;

    static $forms;

    static $cdata = array(
            'table' => TASK_CDATA_TABLE,
            'object_id' => 'task_id',
            'object_type' => ObjectModel::OBJECT_TYPE_TASK,
        );

    static function objects() {
        $os = parent::objects();
        return $os->filter(array('type'=>ObjectModel::OBJECT_TYPE_TASK));
    }

    static function getDefaultForm() {
        if (!isset(static::$defaultForm)) {
            if (($o = static::objects()) && $o[0])
                static::$defaultForm = $o[0];
        }

        return static::$defaultForm;
    }

    static function getInstance($object_id=0, $new=false) {
        if ($new || !isset(static::$instance))
            static::$instance = static::getDefaultForm()->instanciate();

        static::$instance->object_type = ObjectModel::OBJECT_TYPE_TASK;

        if ($object_id)
            static::$instance->object_id = $object_id;

        return static::$instance;
    }

    static function getInternalForm($source=null, $options=array()) {
        if (!isset(static::$internalForm))
            static::$internalForm = new TaskInternalForm($source, $options);

        return static::$internalForm;
    }
}

class TaskInternalForm
extends AbstractForm {
    static $layout = 'GridFormLayout';

    function buildFields() {

        $fields = array(
                'dept_id' => new DepartmentField(array(
                    'id'=>1,
                    'label' => __('Department'),
                    'required' => true,
                    'layout' => new GridFluidCell(6),
                    )),
                'assignee' => new AssigneeField(array(
                    'id'=>2,
                    'label' => __('Assignee'),
                    'required' => false,
                    'layout' => new GridFluidCell(6),
                    )),
                'duedate'  =>  new DatetimeField(array(
                    'id' => 3,
                    'label' => __('Due Date'),
                    'required' => false,
                    'configuration' => array(
                        'min' => Misc::gmtime(),
                        'time' => true,
                        'gmt' => false,
                        'future' => true,
                        ),
                    )),

            );

        $mode = @$this->options['mode'];
        if ($mode && $mode == 'edit') {
            unset($fields['dept_id']);
            unset($fields['assignee']);
        }

        return $fields;
    }
}

// Task thread class
class TaskThread extends ObjectThread {

    function addDescription($vars, &$errors=array()) {

        $vars['threadId'] = $this->getId();
        $vars['message'] = $vars['description'];
        unset($vars['description']);
        return MessageThreadEntry::add($vars, $errors);
    }

    static function create($task=false) {
        assert($task !== false);

        $id = is_object($task) ? $task->getId() : $task;
        $thread = parent::create(array(
                    'object_id' => $id,
                    'object_type' => ObjectModel::OBJECT_TYPE_TASK
                    ));
        if ($thread->save())
            return $thread;
    }

}
?>
