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
    use HasFlagsOrm;

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
                    'object_id' => 'Ticket.ticket_id',
                ),
                'null' => true,
            ),

            // Related tasks (via template groups)
            'set' => array(
                'constraint' => array('set_id' => 'TaskSet.id'),
                'null' => true,
            ),
            'template' => array(
                'constraint' => array('template_id' => 'TaskTemplate.id'),
            ),
        ),
        'select_related' => array(
            'staff', 'team', 'thread', 'dept', 'set'
        ),
        'ordering' => array(
            // Order by set and template order within set. Otherwise
            // by task number
            'set__sort', 'template__sort', 'number'
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
    const ISPENDING = 0x0004;   // Await completion of another task

    // XXX: Drop this when PHP 5.6 is required
    const ISOPEN_PENDING = 0x0005;

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

    function getTemplate() {
        return $this->template;
    }

    function getCreateDate() {
        return $this->created;
    }

    function getStartDate() {
        return $this->started;
    }

    function getDueDate() {
        return $this->duedate;
    }

    function getCloseDate() {
        return $this->isClosed() ? $this->closed : '';
    }

    /**
     * Fetches a list of tasks which this task depends on. That is, the
     * tasks of which this task is a child or sub-task. Returns an empty
     * array if this task is not part of a set.
     */
    function getDependencies() {
        if (!$this->template || !$this->hasRelatedTasks())
            return array();

        // Flip so the farthest depends are first
        $ids = array_reverse($this->template->getRecursiveDependentIds());
        if (count($ids) === 0)
            return array();

        $depends = array();
        foreach ($this->getRelatedTasks()->filter(array(
            'template_id__in' => $ids
        )) as $task) {
            $idx = array_search($task->template_id, $ids);
            $depends[$idx] = $task;
        }
        ksort($depends);
        return $depends;
    }

    /**
     * Fetches a list of tasks which are in the same set as this task. The
     * set is the tasks which were added as part of the same template group
     * when the template group (canned tasks) were added to a ticket. This
     * task is excluded from the resulting set.
     */
    function getRelatedTasks() {
        return static::objects()->filter(array(
            Q::not(['id' => $this->id]),
            'set_id' => $this->set_id,
        ));
    }

    /**
     * Returns TRUE/FALSE if this task is part of a set of tasks added at
     * one time to a ticket.
     */
    function hasRelatedTasks() {
        return !is_null($this->set_id);
    }

    /**
     * Fetch a list of tasks which depend on this task. This would be the
     * child or sub-tasks of this task. Returns an empty array if the task
     * is not a member of a set.
     */
    function getDependents() {
        if (!$this->hasRelatedTasks())
            return array();

        // Find all depedencies of all tasks related to this one
        $dependents = array();
        foreach ($this->getRelatedTasks()->select_related('template') as $task) {
            // See which tasks depend on this one. This is done by
            // inspecting the dependent template_ids on the template used to
            // create this task.
            if (in_array($this->template_id, $task->template->getDependentIds())) {
                $dependents[] = $task;
            }
        }
        return $dependents;
    }

    /**
     * Fetch information on the related task set via the related
     * TaskTemplateGroup object.
     */
    function getTaskTemplateGroup() {
        return $this->set->group;
    }

    function isOpen() {
        return $this->hasFlag(self::ISOPEN);
    }

    function isPending() {
        return $this->hasFlag(self::ISOPEN) && $this->hasFlag(self::ISPENDING);
    }

    function isCancelled() {
        return $this->isClosed() && $this->hasFlag(self::ISPENDING);
    }

    function isCancellable() {
        return $this->isPending();
    }

    function cancel() {
        // For now, an alias of close(), because no separate events or
        // signals are called.
        $this->clearFlag(self::ISOPEN);
        $this->closed = SqlFunction::NOW();
        $this->logEvent('cancelled');

        if (!$this->save())
            return false;
    }

    function isClosed() {
        return !$this->isOpen();
    }

    function isCloseable() {

        if ($this->isClosed())
            return true;

        if ($this->isPending())
            return false;

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
        global $thisstaff;

        $this->clearFlag(self::ISOPEN);
        $this->closed = SqlFunction::NOW();
        $this->logEvent('closed');

        if (!$this->save())
            return false;

        if ($this->ticket) {
            $vars = array(
               'title' => sprintf(__('Task %s Closed'),
                    $this->getNumber()),
               'note' => __('Task closed')
            );
            $this->ticket->logNote($vars['title'], $vars['note'], $thisstaff);
        }

        // Start dependent tasks immediately
        foreach ($this->getDependents() as $task) {
            if ($task->canStart()) {
                $task->start();
            }
        }

        Signal::send('task.closed', $this);
    }

    protected function reopen() {
        global $thisstaff;

        $this->setFlag(self::ISOPEN);
        $this->closed = null;
        $this->logEvent('reopened', false, null, 'closed');

        if (!$this->save())
            return false;

        if ($this->ticket) {
            $this->ticket->reopen();
            $vars = array(
                'title' => sprintf('Task %s Reopened',
                    $this->getNumber()),
                'note' => __('Task reopened')
            );
            $this->ticket->logNote($vars['title'], $vars['note'], $thisstaff);
        }
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
        static $states = array(
            self::ISOPEN                    => /* @trans */ 'Open',
            # self::ISOPEN | self::ISPENDING  => /* @trans */ 'Pending',
            self::ISOPEN_PENDING            => /* @trans */ 'Pending',
            self::ISPENDING                 => /* @trans */ 'Cancelled',
            0                               => /* @trans */ 'Completed',
        );

        $x = $states[$this->flags & (self::ISOPEN | self::ISPENDING)];
        return __($x);
    }

    function getTitle() {
        return $this->__cdata('title', ObjectModel::OBJECT_TYPE_TASK);
    }

    function checkStaffPerm($staff, $perm=null) {

        // Must be a valid staff
        if (!$staff instanceof Staff && !($staff=Staff::lookup($staff)))
            return false;

        // Check access based on department or assignment
        if (!$staff->canAccessDept($this->getDept())
                && $this->isOpen()
                && $staff->getId() != $this->getStaffId()
                && !$staff->isTeamMember($this->getTeamId()))
            return false;

        // At this point staff has access unless a specific permission is
        // requested
        if ($perm === null)
            return true;

        // Permission check requested -- get role.
        if (!($role=$staff->getRole($this->getDept())))
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
        case 'M':
            return $this->getThread()->addDescription($vars);
            break;
        default:
            return $this->postNote($vars, $errors, $poster, $alert);
        }
    }

    function getTicket() {
        if (!$this->object_id || $this->object_type != ObjectModel::OBJECT_TYPE_TICKET)
            return null;

        return Ticket::lookup($this->object_id);
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

        switch($status) {
        case 'open':
            if ($this->isOpen())
                return false;

            $this->reopen();
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
            break;

        case 'cancel':
            if ($this->isClosed())
                return false;

            try {
                $this->cancel();
            }
            catch (Exception $x) {
                $errors['err'] = $x->getMessage()
                    ?: sprintf(__('%s cannot be cancelled'), __('This task'));
            }
            break;

        case 'start':
            if (!$this->canStart())
                return false;

            try {
                $this->start();
            }
            catch (Exception $x) {
                $errors['err'] = $x->getMessage()
                    ?: sprintf(__('%s cannot be started'), __('This task'));
            }
            break;

        default:
            return false;
        }

        if ($errors || !$this->save(true))
            return false;

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
        } elseif (!$dept->canAssign($assignee)) {
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


    function assignViaForm(AssignmentForm $form, &$errors, $alert=true) {
        $assignee = $form->getAssignee();

        if (!$this->assign($assignee, $errors))
            return false;

        $this->onAssignment($assignee,
                $form->getField('comments')->getClean(),
                $alert);

        return true;
    }

    function assign($assignee, &$errors=array(), $event=true) {
        global $thisstaff;

        $evd = array();

        if ($assignee instanceof Staff) {
            $dept = $this->getDept();
            if ($this->getStaffId() == $assignee->getId()) {
                $errors['assignee'] = sprintf(__('%s already assigned to %s'),
                        __('Task'),
                        __('the agent')
                        );
            } elseif(!$assignee->isAvailable()) {
                $errors['assignee'] = __('Agent is unavailable for assignment');
              } elseif (!$dept->canAssign($assignee)) {
                $errors['err'] = __('Permission denied');
            }
            else {
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

        if ($event)
            $this->logEvent('assigned', $evd);

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
        Http::download($name, 'application/pdf', $pdf->output($name, 'S'));
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
            if ($due = $this->getDueDate())
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

    function addCollaborator($user, $vars, &$errors, $event=true) {
        if ($c = $this->getThread()->addCollaborator($user, $vars, $errors, $event)) {
            $this->collaborators = null;
            $this->recipients = null;
        }
        return $c;
    }

    /*
     * Notify collaborators on response or new message
     *
     */
    function  notifyCollaborators($entry, $vars = array()) {
        global $cfg;

        if (!$entry instanceof ThreadEntry
            || !($recipients=$this->getThread()->getRecipients())
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
                        'title' => _S('Task Updated'),
                        ),
                    $_errors,
                    $thisstaff);
        }

        $this->updated = SqlFunction::NOW();

        if ($changes)
            $this->logEvent('edited', array('fields' => $changes));

        return $this->save();
    }

    /* static routines */
    static function lookupIdByNumber($number) {
        try {
            $row = static::objects()
                ->filter(array('number' => $number))
                ->values_flat('id')
                ->one();
            return $row[0];
        }
        catch (DoesNotExist $e) {}
    }

    static function isNumberUnique($number) {
        return !self::lookupIdByNumber($number);
    }

    static function create($vars=false) {
        global $cfg;

        $task = new static(($vars ?: array()) + array(
            'flags' => self::ISPENDING | self::ISOPEN,
            // XXX: This should be done in the ::save method
            'number' => $cfg->getNewTaskNumber(),
            'created' => new SqlFunction('NOW'),
            'updated' => new SqlFunction('NOW'),
        ));

        return $task;
    }

    static function createFromUi($vars=false) {
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

        // Create a thread
        $thread = TaskThread::create($task);

        // Add dynamic data provided via UI
        $task->addDynamicData($vars['default_formdata']);
        $thread->addDescription($vars);

        $task->logEvent('created', null, $thisstaff);
        Signal::send('task.created', $task);

        // Get role for the dept
        $role = $thisstaff->getRole($task->getDept());
        // Assignment
        $assignee = $vars['internal_formdata']['assignee'];
        if ($assignee
            // Skip assignment if the user doesn't have perm.
            && $role->hasPerm(Task::PERM_ASSIGN)
        ) {
            // Set the assignee, but don't send alerts out
            $task->assign($assignee, array(), false);
        }

        $task->start(true);

        return $task;
    }

    /**
     * Determines if this task can be started. Current criteria include
     * checking if the task is in PENDING status and has all its dependency
     * tasks satisfied.
     */
    function canStart() {
        if (!$this->isPending())
            return false;

        foreach ($this->getDependencies() as $task) {
            if ($task->isOpen()) {
                return false;
            }
        }

        return true;
    }

    function isStarted() {
        return !is_null($this->started);
    }

    /**
     * Start the task. This operation is distinct from creating a task in
     * that the 'new task' alert is triggered and the assignment takes place
     * with its corresponding alert emails.
     */
    function start($alert=true) {
        global $cfg;

        if (!$this->isPending())
            throw new Exception('Task must be pending to be started');

        $this->clearFlag(self::ISPENDING);
        $this->started = SqlFunction::NOW();

        // Continue onward if this fails
        $this->save(true);

        // Calculate the automated due date, if any, before sending
        // communication emails
        if ($this->template && $this->template->duedate && !$this->duedate) {
            $this->duedate = $this->template->getCalculatedDueDate($this);
        }

        // Reprocess the assignment and send the alert this time
        if ($this->team_id || $this->staff_id) {
            $evd = array();
            if ($this->team)
                $evd['team'] = array($this->team_id, $this->team->getName());
            if ($this->staff)
                $evd['staff'] = array($this->staff_id, $this->staff->getName());
            $this->logEvent('assigned', $evd);
            $this->onAssignment($this->team ?: $this->staff);
        }

        $this->logEvent('started');
        Signal::send('task.started', $this);
        $this->save();

        // Send alerts out if enabled.
        if (!$alert || !$cfg->alertONNewTask())
            return true;

        if (!($dept=$this->getDept())
            || !($tpl = $dept->getTemplate())
            || !($email = $dept->getAlertEmail())
        ) {
            return true;
        }

        // Recipients
        $options = array('thread' => $this->getThread());
        $recipients = $sentlist = array();

        if ($dept instanceof Dept) {
            if ($cfg->alertDeptMembersONNewTask() && !$this->isAssigned()
                && ($members = $dept->getMembersForAlerts()->all())
            ) {
                $recipients = $members;
            }
            if ($cfg->alertDeptManagerONNewTask() &&
                ($manager = $dept->getManager())
            ) {
                $recipients[] = $manager;
            }
        }

        if ($recipients
            && ($msg = $tpl->getNewTaskAlertMsgTemplate())
        ) {
            // Send the alerts.
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

        if ($cfg->alertAdminONNewTask()) {
            $options += array('utype'=>'A');
            $alert = $this->replaceVars($msg, array('recipient' => 'Admin'));
            $email->sendAlert($cfg->getAdminEmail(), $alert['subj'],
                    $alert['body'], null, $options);
        }
    }

    function delete($comments='') {
        global $ost, $thisstaff;

        $thread = $this->getThread();

        if (!parent::delete())
            return false;

        $this->logEvent('deleted');

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

/**
 * The skeleton of a task to be created and stapled to a ticket in the
 * future. This template contains the information needed to create and start
 * a task. Other information for duedate, attached forms, and dependencies
 * is also represented in this model and breakouts.
 */
class TaskTemplate extends VerySimpleModel {
    use HasFlagsOrm;

    static $meta = array(
        'pk' => array('id'),
        'table' => TASK_TEMPLATE_TABLE,
        'joins' => array(
            'group' => array(
                'constraint' => array('group_id' => 'TaskTemplateGroup.id'),
            ),
            'forms' => array(
                'reverse' => 'TaskTemplateForm.template',
            ),
            'instances' => array(
                'reverse' => 'Task.template'
            ),
        ),
        'select_related' => array('group'),
    );

    protected $depchain;

    const FLAG_ENABLED  = 0x0001;
    const FLAG_DELETED  = 0x0002;

    function __construct($vars=false) {
        parent::__construct($vars);
        $this->created = SqlFunction::NOW();
    }

    function getId()    { return $this->id; }
    function getAttachedForms() { return $this->forms; }

    /**
     * Fetch a list of DynamicForm objects which are associated with the
     * task template. Fields which are marked as disabled are disabled on
     * the respective forms in this routine.
     */
    function getForms() {
        $forms = array();
        foreach ($this->getAttachedForms() as $F) {
            $forms[] = $F->getDynamicForm();
        }
        return $forms;
    }

    /**
     * Create a Task instance from this template. The forms associated with
     * the template are added immediately. The created and saved task is
     * returned.
     *
     * Parameters:
     * $vars - (array) Extra arguments for the Task constructor. Keys and
     *      values in this array should be valid for the Task ORM object.
     */
    function instanciate($vars=false) {
        $task = Task::create($vars);

        $task->template_id = $this->id;

        // Route and assign appropriately
        $task->dept_id = $this->dept_id;
        $task->staff_id = $this->staff_id;
        $task->team_id = $this->team_id;

        if (!$task->save())
            return;

        // Attach requested forms and initial data
        foreach ($this->getAttachedForms() as $tform) {
            $entry = $tform->getFormEntry($task, 1);
            $entry->save();
        }

        // Add basic task form entry (for title and such)
        $data = $this->getDataAsArray();
        $entry = TaskForm::getDefaultForm()->instanciate(0, $data);
        $entry->object_id = $task->id;
        $entry->object_type = ObjectModel::OBJECT_TYPE_TASK;
        $entry->save();

        // Add description as first item to the thread
        $thread = TaskThread::create($task);
        $thread->addDescription(array(
            'description' => $data['description'],
        ));

        // XXX: Should the current agent be logged in the event? This is
        //      automated, but might have been triggered by a person
        $task->logEvent('created');

        return $task;
    }

    /**
     * Fetch name (`title`) from the TaskForm and stored data
     */
    function getName() {
        // This is somewhat of a hack for a speed optimization
        $tf = TaskForm::getDefaultForm();
        $title = $tf->getField('title');
        $data = $this->getDataAsArray();
        return $title->to_php($data['title']);
    }

    function getStatus() {
        return $this->hasFlag(self::FLAG_ENABLED)
            ? __('Active') : __('Draft');
    }

    /**
     * Fetch a TaskForm instance configured from the data inside this task
     * template, or the $source data provided. One change is made to the
     * reference TaskForm -- The `description` field is changed here locally
     * to a normal TextareaField, with html optionally enabled, and a
     * SimpleForm instance is returned rather than a DynamicForm or
     * DynamicFormEntry.
     */
    function getTaskForm($source=false) {
        global $cfg;

        $tf = TaskForm::getDefaultForm();

        // Replace the `description` field with a simple html? textarea
        $fields = array();
        foreach ($tf->getFields() as $F) {
            $F->reset();
            $fields[$F->get('name') ?: $F->get('id')] = $F;
        }
        $description = $fields['description'];
        $fields['description'] = new TextareaField(array(
            'label' => $description->get('label'),
            'configuration' => array(
                'html' => $cfg->isRichTextEnabled(),
                'placeholder' => $description->get('hint'),
            ),
        ));

        // Create a surrogate form for the fields
        $form = new SimpleForm($fields, $source, array(
            'title' => $tf->getTitle(),
            'instructions' => $tf->getInstructions(),
        ));

        if ($source)
            $form->setSource($source);
        elseif (isset($this->id))
            $form->setSource($this->getDataAsArray());

        return $form;
    }

    function getDataAsArray() {
        return JsonDataParser::decode($this->data) ?: array();
    }

    function setData(array $data) {
        $this->data = JsonDataEncoder::encode($data);
    }

    /**
     * Fetch data for the TaskTemplateBasicForm from this task template
     */
    function getBasicForm($source=false, $options=array()) {
        @list($reference, $term, $interval, $tpl_id) = $this->getDueDateInfo();
        $source = $source ?: array(
            'dept_id' => $this->dept_id,
            'assignee' => $this->staff_id
                ? ('s'.$this->staff_id)
                : ($this->team_id
                    ? ('t'.$this->team_id)
                    : null),
            'duedate' => array(
                'term' => $term,
                'interval' => $interval,
                'reference' => $reference,
                'related' => $tpl_id,
            ),
            'depends' => $this->getDependentIds(),
        );

        // Add group_id for depends drop-down list
        $options += array('group_id' => $this->group_id);

        return new TaskTemplateBasicForm($source, $options);
    }

    /**
     * Calcuate the due date configured in this template for the referenced
     * task. The returned time is a DateTime instance of the duedate in the
     * database timezone.
     *
     * See getDueDateInfo for a description of the database format
     */
    function getCalculatedDueDate(Task $task) {
        global $cfg;

        static $multipliers = array(
            'm' => 1,
            'h' => 60,
            'd' => 1440,
            'w' => 10080,
        );

        // Separate the due date into the reference, term, and interval
        @list($reference, $term, $interval, $tpl_id) = $this->getDueDateInfo();
        if (!$term)
            return null;

        // Calcualte the term in minutes
        $multiplier = $multipliers[$interval] ?: 1;
        $term *= $multiplier;

        // Find the reference timestamp. Leave things in the database
        // time zone
        $references = array(
            'start'     => function($T) { return $T->started; },
            'ticket'    => function($T) { return $T->getTicket()->created; },
            'set'       => function($T) { return $T->set->created; },
            'related'   => function($T) use ($tpl_id) {
                try {
                    $tpl = $T->set->getTaskByTemplateId($tpl_id);
                    return $tpl->started;
                }
                catch (DoesNotExist $x) {
                    return null;
                }
            },
        );
        if (!isset($references[$reference]))
            return null;

        // Propogate null (undefined) from funcs
        $func = $references[$reference];
        if (!($starttime = $func($task)))
            return $starttime;

        return new DateTime(sprintf('%s + %d minutes', $starttime, $term),
            new DateTimeZone($cfg->getDbTimezone()));
    }

    /**
     * The format of the saved due date is assumed to be `reference:id+xh`,
     * where `reference` is the name of a time reference which should be one
     * of
     *   - `task` the time this task was started
     *   - `ticket` the time the associated ticket was created
     *   - `set` the time the set of tasks was attached to the ticket
     *   - `anoter` the time of the start of a related task in the set
     * and `x` is a number, and `h` is a interval time, which should be `m`,
     * `h`, `d`, or `w`, which indicates the number is a number of minutes,
     * hours, days, or weeks respectively.
     *
     * For the reference of `related`, the template_id of the other task is
     * also included in the format after the colon (`:`).
     */
    function getDueDateInfo() {
        if (is_null($this->duedate))
            return null;

        // Separate the due date into the reference, term, and interval
        list($reference, $period) = explode('+', $this->duedate);
        @list($reference, $tpl_id) = explode(':', $reference);
        if (!$period)
            return null;

        $term = (int) $period;
        $interval = substr($period, -1);

        return array($reference, $term, $interval, $tpl_id);
    }

    /**
     * Fetch a list of TaskTemplate instances on which this task template
     * depends. This template will not be included in the list.
     */
    function getDependencies() {
        $depends = $this->getDependentIds();
        if (count($depends) === 0)
            return array();

        $list = array();
        foreach ($this->group->templates as $tpl) {
            if (in_array($tpl->id, $depends))
                $list[] = $tpl;
        }
        return $list;
    }

    /**
     * Fetch a list of templates in the same group which are dependent on
     * this template
     */
    function getDependents() {
        // Find all depedencies of all tasks related to this one
        $dependents = array();
        foreach ($this->group->templates as $tpl) {
            // See which tasks depend on this one
            if (in_array($this->id, $tpl->getDependentIds())) {
                $dependents[] = $tpl;
            }
        }
        return $dependents;
    }

    /**
     * Fetch a list of template ids that this template is dependent on. That
     * is, ids of the next-level parent templates
     */
    function getDependentIds() {
        if (!$this->depends)
            return array();

        return array_map('intval', explode(',', $this->depends));
    }

    /**
     * Fetch a complete list of all templates which this task depends on,
     * including tasks on which those tasks depend. This is useful for
     * detecting circular dependencies.
     *
     * This list is somewhat sorted in that the members are added to the
     * list in the apparent order of recursive dependency. That is, the
     * first level dependencies are added first, then the next level for
     * each of the first level, and then so on.
     */
    function getRecursiveDependentIds() {
        // Manage a list where the keys are template ids which this task
        // depends on or is a recursive dependency. Use 0 for the key if
        // that tasks's dependencies have not yet been considered. This
        // algorithm assumes there are no circular dependencies in the
        // ancestry but also uses a failsafe of 100 loops max.
        $list = array_fill_keys($this->getDependentIds(), 0);
        $loops = 100;
        while (array_sum($list) < count($list) && --$loops) {
            foreach ($list as $id=>$searched) {
                if (!$searched && ($T = static::lookup($id))) {
                    foreach ($T->getDependentIds() as $id2) {
                        if (!isset($list[$id2]))
                            $list[$id2] = 0;
                    }
                    $list[$id] = 1;
                }
            }
        }
        return array_keys($list);
    }

    /**
     * Flow recursively through this template's dependencies and find the
     * longest chain of dependencies for this template. This is useful in
     * representing the template in a tree view for the respective task
     * template group. The rationale is that the displayed task should be
     * nested as deeply as possible based on its dependencies, if it is
     * dependent on more than one parent task.
     *
     * Returns:
     * This full list of dependencies for this task. This task will be the
     * last item in the list. The first item will be the top-level
     * dependency.
     */
    function getLongestDependencyChain() {
        if (isset($this->depchain))
            return $this->depchain;

        $this->depchain = array();

        foreach ($this->getDependentIds() as $id) {
            if (!($tpl = TaskTemplate::lookup($id)))
                continue;

            // Detect circular dependency
            $chain = $tpl->getLongestDependencyChain();
            if (in_array($this, $chain))
                // TODO: Do something
                return array();

            if (count($chain) + 1 > count($this->depchain)) {
                $chain[] = $tpl;
                $this->depchain = $chain;
            }
        }

        return $this->depchain;
    }

    function update(array $source, &$errors=array()) {
        if (!$this->group_id || !TaskTemplateGroup::lookup($this->group_id))
            $errors[] = __('Template must be associated with a valid group');

        $basic = $this->getBasicForm($source);
        if (!$basic->isValid())
            $errors[] = __('Please correct the errors below');

        $data = $basic->getClean(Form::FORMAT_PHP);
        $this->dept_id = $data['dept_id'];
        $this->staff_id = $data['assignee'] instanceof Staff
            ? $data['assignee']->getId() : null;
        $this->team_id = $data['assignee'] instanceof Team
            ? $data['assignee']->getId() : null;
        $this->duedate = $data['duedate']['term']
            ? (sprintf('%s%s+%d%s', $data['duedate']['reference'],
                $data['duedate']['related'] ? (':'.$data['duedate']['related']) : '',
                $data['duedate']['term'], $data['duedate']['interval']))
            : null;

        // Verify built-in form data
        $builtin = $this->getTaskForm($source);
        if (!$builtin->isValid())
            $errors[] = __('Please correct the errors below');

        $this->setData($builtin->getClean(Form::FORMAT_PHP));

        // Handling of extra forms, disabled fields, is performed by
        // ::updateForms and is expected to be a separate call because that
        // function implies save()ing.

        $this->depends = is_array($data['depends'])
            ? implode(',', array_keys($data['depends']))
            : null;
        unset($this->depchain);

        // Verify dependencies do not create circular reference
        if (isset($this->id)) {
            if (in_array($this->id, $this->getDependentIds())) {
                $errors[] = __('This task cannot depend on itself');
            }
            else {
                foreach ($this->getDependencies() as $tpl) {
                    // This is a bit unnecessary, and
                    // $this->getRecursiveDependentIds() would reveal a
                    // circular dependency; however, offering the name of
                    // the offending reference is valuable to the user
                    if (in_array($this->id, $tpl->getRecursiveDependentIds())) {
                        $errors[] = sprintf(
                            __('Circular dependency: "%s" requires this task'),
                            $tpl->getName()
                        );
                    }
                }
            }
        }

        return count($errors) === 0;
    }

    /**
     * Update the forms and enabled fields associated with this task
     * template.
     */
    function updateForms(array $form_ids, $field_ids) {
        $find_disabled = function($form) use ($field_ids) {
            $disabled = array();
            foreach ($form->fields->values_flat('id') as $row) {
                list($id) = $row;
                if (false === ($idx = array_search($id, $field_ids))) {
                    $disabled[] = $id;
                }
            }
            return $disabled;
        };

        // Consider all the forms in the request
        $current = array();
        foreach ($this->forms as $F) {
            if (false !== ($idx = array_search($F->form_id, $form_ids))) {
                $current[] = $F->form_id;
                $F->sort = $idx + 1;
                $F->extra = JsonDataEncoder::encode(
                    array('disable' => $find_disabled($F->form))
                );
                $F->save();
                unset($form_ids[$idx]);
            }
            else {
                $F->delete();
            }
        }
        foreach ($form_ids as $sort=>$id) {
            if (!($form = DynamicForm::lookup($id))) {
                continue;
            }
            elseif (in_array($id, $current)) {
                // Don't add a form more than once
                continue;
            }
            $tf = new TaskTemplateForm(array(
                'template' => $this,
                'form' => $form,
                'sort' => $sort + 1,
                'extra' => JsonDataEncoder::encode(
                    array('disable' => $find_disabled($form))
                )
            ));
            $tf->save();
        }
        $this->forms->reset();
        return true;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }
}

class TaskTemplateBasicForm
extends AbstractForm {
    function getTitle() { return __('Visibility and Assignment'); }
    function buildFields() {
        $related = TaskTemplateGroup::lookup($this->options['group_id'])->getTemplateNames();
        return array(
            'dept_id' => new ChoiceField(array(
                'label' => __('Department'),
                'layout' => new GridFluidCell(6),
                'choices' => Dept::getDepartments(),
                'configuration' => array(
                   'prompt' => __('Same as Ticket'),
                ),
            )),
            'assignee' => new AssigneeField(array(
                'label' => __('Assignee'),
                'layout' => new GridFluidCell(6),
                'configuration' => array(
                   'prompt' => __('Initially Unassigned'),
                ),
            )),
            'duedate' => new InlineFormField(array(
                'label' => __('Due Date'),
                'layout' => new GridFluidCell(6),
                'form' => array(
                    'term' => new TextboxField(array(
                        'layout' => new GridFluidCell(3),
                        'configuration' => array(
                            'validator' => 'number',
                        ),
                    )),
                    'interval' => new ChoiceField(array(
                        'layout' => new GridFluidCell(3),
                        'default' => 'd',
                        'choices' => array(
                            'h' => __("hours"),
                            'd' => __("days"),
                            'w' => __("weeks"),
                        ),
                    )),
                    'reference' => new ChoiceField(array(
                        'layout' => new GridFluidCell(6),
                        'default' => 'start',
                        'choices' => array(
                            'start' => __("from the start of this task"),
                            'set' => __("from the start of this task set"),
                            'ticket' => __("from the start of the ticket"),
                            'related' => __("from the start of a related task"),
                        ),
                    )),
                    'related' => new ChoiceField(array(
                        'choices' => $related,
                        'required' => true,
                        'visibility' => new VisibilityConstraint(array(
                            'reference' => 'related',
                        ), VisibilityConstraint::HIDDEN),
                    )),
                ),
            )),
            'sec2' => new SectionBreakField(array(
                'label' => __('Dependency'),
            )),
            'depends' => new ChoiceField(array(
                'label' => __('Dependent Tasks'),
                '--hint' => __('Tasks which must be completed before this task can be started'),
                'choices' => $related,
                'configuration' => array(
                    'multiselect' => true,
                ),
            )),
        );
    }
}

/**
 * A form and initial data associated with a TaskTemplate instance. This
 * represents a connection between a TaskTemplate and a DynamicForm. When
 * the template is instanced, the form is also instanciated to a
 * DynamicFormEntry and the initial data and sort order associated with this
 * form attachment is included.
 */
class TaskTemplateForm extends VerySimpleModel {
    static $meta = array(
        'pk' => array('template_id', 'form_id'),
        'table' => TASK_TEMPLATE_FORM_TABLE,
        'joins' => array(
            'template' => array(
                'constraint' => array('template_id' => 'TaskTemplate.id'),
            ),
            'form' => array(
                'constraint' => array('form_id' => 'DynamicForm.id'),
            ),
        ),
        'select_related' => array('form'),
    );

    protected $_form;

    /**
     * NOTE: The returned instance is not yet saved
     */
    function getFormEntry(Task $task=null, $sort=0) {
        $data = $this->getDataAsArray() ?: null;
        $entry = $this->form->instanciate($this->sort ?: 1, $data);

        if ($task) {
            $entry->object_type = ObjectModel::OBJECT_TYPE_TASK;
            $entry->object_id = $task->id;
            $entry->sort = $this->sort + $sort;
        }

        // TODO: Configured disabled fields

        return $entry;
    }

    function getDataAsArray() {
        return JsonDataParser::decode($this->data) ?: array();
    }

    function setData(DynamicFormEntry $data) {
        $this->data = JsonDataEncoder::encode(
            $data->getClean(Form::FORMAT_DATABASE)
        );
    }

    function getDynamicForm() {
        $extra = JsonDataParser::decode($this->extra) ?: array();
        // XXX: Does the form need to be copied to prevent subtle issues?
        $this->form->disableFields($extra['disable'] ?: array());
        return $this->form;
    }
}

/**
 * A logical grouping of task templates. This grouping has a name and is
 * used in the help topics and the ticket view page to add several tasks to
 * a ticket at one time. Once the tasks are added, the grouping is tracked
 * separately so that further changes to the template grouping does not
 * affect
 */
class TaskTemplateGroup extends VerySimpleModel {
    use HasFlagsOrm;

    static $meta = array(
        'pk' => array('id'),
        'table' => TASK_TEMPLATE_GROUP_TABLE,
        'joins' => array(
            'templates' => array(
                'reverse' => 'TaskTemplate.group'
            ),
            'instances' => array(
                'reverse' => 'TaskSet.group'
            ),
        ),
        'ordering' => array('name'),
    );

    const FLAG_ENABLED  = 0x0001;
    const FLAG_DELETED  = 0x0002;

    function __construct($vars=false) {
        parent::__construct($vars);
        $this->created = SqlFunction::NOW();
    }

    function getId()    { return $this->id; }
    function getName()  { return $this->name; }

    function getStatus() {
        return $this->hasFlag(self::FLAG_ENABLED)
            ? __('Active') : __('Draft');
    }

    /**
     * Fetch and ID keyed list of the related TaskTemplate objects in this
     * group.
     */
    function getTemplates() {
        $list = array();
        foreach ($this->templates as $tpl) {
            $list[$tpl->id] = $tpl;
        }
        return $list;
    }

    /**
     * Fetch an ID keyed list of the template names in this group
     */
    function getTemplateNames() {
        $names = array();
        foreach ($this->templates as $tpl) {
            $names[$tpl->id] = $tpl->getName();
        }
        return $names;
    }

    /**
     * Creates a TaskSet instance and instanciates all the tasks in this
     * group and adds them to the set. The created TaskSet is returned.
     */
    function instanciate(Ticket $ticket, $vars=false) {
        $set = new TaskSet(($vars ?: array()) + array(
            'template_group_id' => $this->id,
        ));
        $set->save();

        $templates = $this->templates->filter(array(
            // Don't add DRAFT templates
            'flags__hasbit' => TaskTemplate::FLAG_ENABLED,
        ))->all();

        // Ensure templates are created in the set in the dependency order
        usort($templates, function($a, $b) {
            return in_array($a->id, $b->getDependentIds()) ? -1
                : (in_array($b->id, $a->getDependentIds()) ? 1
                : $a->sort - $b->sort);
        });
        foreach ($templates as $tmpl) {
            $task = $tmpl->instanciate(array(
                'set_id' => $set->id,
                'object_id' => $ticket->getId(),
                'object_type' => ObjectModel::OBJECT_TYPE_TICKET,
            ));

            if ($task->dept_id == 0)
                $task->dept_id = $ticket->dept_id; // 0 := Same as ticket

            if (!$task->save())
                return;
        }
        return $set;
    }

    /**
     * Fetch the list of templates in this group sorted by their declared
     * sort order and by their interdepenency. That is, a group of tasks
     * with the same dependencies are sorted according to the declared sort
     * order. Otherwise, tasks are grouped in order to display a nice tree
     * view where dependent tasks are shown under their parents.
     *
     * Returns:
     * Tree-organized listing of templates in this group. The structure of
     * the returned list is
     *
     * [id => [<TaskTemplate>, [id => [TaskTemplate, []], id => [...]]]]
     *
     * Where `id` is the id number of a task template, which is the key to a
     * two-item array which is the template itself and the list of its
     * dependents. Each template should appear exactly once in the list and
     * every template in the group should be accounted for.
     */
    function getTreeOrganizedTemplates() {
        $templates = $this->templates->order_by('sort')->getIterator()->hash_by('id');

        // Now, go back through the list and arrange the templates by
        // dependents
        $chains = array();
        foreach ($templates as $tpl) {
            $chains[$tpl->id] = $tpl->getLongestDependencyChain();
        }

        // Now, sort the chains list so that the shortest chains come first.
        // Then, the first item in the chain otherwise is the item which
        // should be the parent of each template
        uasort($chains, function($a, $b) { return count($a) - count($b); });

        // Now organize the list as a list of singly-linked lists of the
        // dependency chains
        $root = array();
        foreach ($chains as $tpl_id=>$C) {
            $level = &$root;
            foreach ($C as $depend) {
                if (!isset($level[$depend->id])) {
                    // Divergence
                    throw new Exception('Dependency list creation diverged');
                }
                $level = &$level[$depend->id][1];
            }
            $level[$tpl_id] = array($templates[$tpl_id], array());
        }

        // Now sort each level by the `sort` property
        $sort = function(&$level) use (&$sort) {
            uasort($level, function($a, $b) { return $a[0]->sort - $b[0]->sort; });
            foreach ($level as &$next) {
                if (count($next[1]))
                    $sort($next[1]);
            }
            unset($next);
        };
        $sort($root);
        unset($level);

        return $root;
    }

    function __toString() {
        return $this->getName();
    }

    static function allActive() {
        return static::objects()
            ->filter(array('flags__hasbit'=>self::FLAG_ENABLED));
    }

    static function forTicket(Ticket $ticket) {
        return static::objects()->filter(array(
            // Don't offer DRAFT template groups
            'flags__hasbit' => self::FLAG_ENABLED,
            'dept_id__in' => array(0, $ticket->dept_id),
            'topic_id__in' => array_unique(array(0, $ticket->topic_id)),
        ));
    }

    function save($refetch=false) {
        if ($refetch || $this->dirty) {
            $this->updated = SqlFunction::NOW();
        }
        return parent::save($refetch || $this->dirty);
    }
}

class TaskTemplateGroupForm
extends AbstractForm {
    function buildFields() {
        return array(
            'name' => new TextboxField(array(
                'required' => true,
                'configuration' => array(
                    'placeholder' => __('Name'),
                ),
            )),
            '::' => new SectionBreakField(array(
                'label' => __('Visibility'),
                'hint' => __('Make this task group visible to only some departments or help topics'),
            )),
            'dept_id' => new ChoiceField(array(
                'layout' => new GridFluidCell(6),
                'choices' => array(
                    0 => '— '.__('Any Department').' —',
                ) + Dept::getDepartments()
            )),
            'topic_id' => new ChoiceField(array(
                'layout' => new GridFluidCell(6),
                'choices' => array(
                    0 => '— '.__('Any Help Topic').' —',
                ) + Topic::getHelpTopics(),
            )),  
            'notes' => new TextareaField(array(
                'label' => __('Internal Notes'),
                'configuration' => array(
                    'html' => true,
                    'placeholder' => __(/* Internal notes */ "be liberal, they're internal"),
                ),
            )),
        );
    }
}

/**
 * A logical group of tasks which were created at the same time from a
 * TaskTemplateGroup. These set entries are created whenever a group of
 * tasks is created and attached to a ticket so that, if in the future,
 * tasks were added or removed from the template group, that change is
 * isolated from previous usages of the template group. The individual items
 * are not recorded separately. Instead, each created task is linked to the
 * set. Therefore, all tasks linked to the same set are said to be
 * 'related'.
 */
class TaskSet extends VerySimpleModel {
    use HasFlagsOrm;

    static $meta = array(
        'pk' => array('id'),
        'table' => TASK_SET_TABLE,
        'joins' => array(
            'group' => array(
                'constraint' => array(
                    'template_group_id' => 'TaskTemplateGroup.id'
                ),
            ),
        ),
    );

    const FLAG_STARTED      = 0x0001;
    const FLAG_COMPLETED    = 0x0002;

    function __construct($vars=false) {
        parent::__construct($vars);
        $this->created = SqlFunction::NOW();
    }

    function getName() {
        return $this->group->getName();
    }

    /**
     * Called when a task is closed. If all the tasks in this set are
     * completed, then this set can also be marked as completed
     */
    static function onTaskClosed(Task $task) {
        // Scan for initial dependencies on a set
        if ($set = static::lookup(array('depends' => $task->id))) {
            // The task closing is a dependency on a task set (which has
            // not yet been started)
            $set->start();
        }
        if (!$task->set_id || !($set = static::lookup($task->set_id))) {
            // Not part of any TaskSet
            return;
        }

        // Check if task set can be closed out
        if ($set->getRemainingTasks()->count())
            return;

        $set->completed = SqlFunction::NOW();
        $set->setFlag(self::FLAG_COMPLETED);
        $set->save();
    }

    function isCompleted() {
        return !$this->hasFlag(self::FLAG_COMPLETED);
    }

    function getTasks() {
        return Task::objects()->filter(array(
            'set_id' => $this->id,
        ));
    }

    /**
     * Functionally equivalent to the method with the same name for the
     * TaskTemplateGroup, except that this applies to instanciated tasks.
     * In fact, the upstream set will be used and missing templates will be
     * removed from the set.
     */
    function getTreeOrganizedTasks() {
        $tree = $this->group->getTreeOrganizedTemplates();
        $tasks = array();
        foreach ($this->getTasks() as $T) {
            $tasks[$T->template_id] = $T;
        }
        $do_level = function($items, $level=0) use ($tasks, &$do_level) {
            foreach ($items as $id=>$info) {
                list($template, $children) = $info;
                if (!isset($tasks[$template->id]))
                    return array();
                if (count($children)) {
                    $items[$id] = array($tasks[$template->id],
                        $do_level($children, $level+1));
                }
                else {
                    $items[$id] = array($tasks[$template->id], array());
                }
            }
            return $items;
        };
        return $do_level($tree);
    }

    function getRemainingTasks() {
        return $this->getTasks()->filter(array(
            'flags__hasbit' => Task::ISOPEN,
        ));
    }

    function getTaskByTemplateId($template_id) {
        return $this->getTasks()->filter(array(
            'template_id' => $template_id,
        ))->one();
    }

    /**
     * Kick off this set of tasks. This is done by interrogating each of the
     * related tasks to see which ones can be started and kicking them off
     */
    function start($alert=true) {
        if ($this->hasFlag(self::FLAG_STARTED))
            return false;

        foreach ($this->getTasks() as $task) {
            if ($task->canStart())
                $task->start($alert);
        }
        $this->setFlag(self::FLAG_STARTED);
        return $this->save();
    }
}
Signal::connect('task.closed', array('TaskSet', 'onTaskClosed'));

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
        if (!isset($vars['message']) && $vars['description'])
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
