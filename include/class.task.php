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
        ),
    );

    const PERM_CREATE   = 'task.create';
    const PERM_EDIT     = 'task.edit';
    const PERM_ASSIGN   = 'task.assign';
    const PERM_TRANSFER = 'task.transfer';
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

    function isOpen() {
        return $this->hasFlag(self::ISOPEN);
    }

    function isClosed() {
        return !$this->isOpen();
    }

    function close() {
        return $this->clearFlag(self::ISOPEN);
    }

    function reopen() {
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


class Task extends TaskModel implements Threadable {
    var $form;
    var $entry;

    var $_thread;
    var $_entries;

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

    function postThreadEntry($type, $vars) {
        $errors = array();
        switch ($type) {
        case 'N':
        default:
            return $this->postNote($vars, $errors);
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

    function getAssignmentForm($source=null) {

        if (!$source)
            $source = array('assignee' => array($this->getAssigneeId()));

        return AssignmentForm::instantiate($source,
                array('dept' => $this->getDept()));
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

    function setStatus($status, $comments='') {
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
            $this->close();
            break;
        default:
            return false;
        }

        $this->save(true);
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
    function assign(AssignmentForm $form, &$errors, $alert=true) {

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
            }
        } elseif ($assignee instanceof Team) {
            if ($this->getTeamId() == $assignee->getId()) {
                $errors['assignee'] = sprintf(__('%s already assigned to %s'),
                        __('Task'),
                        __('the team')
                        );
            } else {
                $this->team_id = $assignee->getId();

            }
        } else {
            $errors['assignee'] = __('Unknown assignee');
        }

        if ($errors || !$this->save(true))
            return false;

        $this->onAssignment($assignee,
                $form->getField('comments')->getClean(),
                $alert);

        return true;
    }

    function onAssignment($assignee, $note='', $alert=true) {
        global $thisstaff;

        if (!is_object($assignee))
            return false;

        $assigner = $thisstaff ?: __('SYSTEM (Auto Assignment)');
        //Assignment completed... post internal note.
        $title = sprintf(__('Task assigned to %s'),
                (string) $assignee);

        if (!$note) {
            $note = $title;
            $title = '';
        }

        $errors = array();
        $note = $this->postNote(
                array('note' => $note, 'title' => $title),
                $errors,
                $assigner,
                false);

        // Send alerts out
        if (!$alert)
            return false;

        return true;
    }

    function transfer(TransferForm $form, &$errors, $alert=true) {
        global $thisstaff;

        $dept = $form->getDept();
        if (!$dept || !($dept instanceof Dept))
            $errors['dept'] = __('Department selection required');
        elseif ($dept->getid() == $this->getDeptId())
            $errors['dept'] = __('Task already in the department');
        else
            $this->dept_id = $dept->getId();

        if ($errors || !$this->save())
            return false;

        // Transfer completed... post internal note.
        $title = sprintf(__('%s transferred to %s department'),
                __('Task'),
                $dept->getName());

        $note = $form->getField('comments')->getClean();
        if (!$note) {
            $note = $title;
            $title = '';
        }
        $_errors = array();
        $note = $this->postNote(
                array('note' => $note, 'title' => $title),
                $_errors, $thisstaff, false);

        // Send alerts if requested && enabled.
        if (!$alert)
            return true;


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

        if (isset($vars['task_status']))
            $this->setStatus($vars['task_status']);

        return $note;
    }

    static function lookupIdByNumber($number) {
        $sql = 'SELECT id FROM '.TASK_TABLE
              .' WHERE `number`='.db_input($number);
        list($id) = db_fetch_row(db_query($sql));

        return $id;
    }

    static function isNumberUnique($number) {
        return !self::lookupIdByNumber($number);
    }

    static function create($vars=false) {
        global $cfg;

        if (!is_array($vars))
            return null;

        $task = parent::create(array(
            'flags' => self::ISOPEN,
            'object_id' => $vars['object_id'],
            'object_type' => $vars['object_type'],
            'number' => $cfg->getNewTaskNumber(),
            'created' => new SqlFunction('NOW'),
            'updated' => new SqlFunction('NOW'),
        ));
        // Save internal fields.
        if ($vars['internal_formdata']['staff_id'])
            $task->staff_id = $vars['internal_formdata']['staff_id'];
        if ($vars['internal_formdata']['dept_id'])
            $task->dept_id = $vars['internal_formdata']['dept_id'];
        if ($vars['internal_formdata']['duedate'])
            $task->duedate = $vars['internal_formdata']['duedate'];

        $task->save(true);

        // Add dynamic data
        $task->addDynamicData($vars['default_formdata']);

        // Create a thread + message.
        $thread = TaskThread::create($task);
        $thread->addDescription($vars);
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
            $where[] = ' ( flags.team_id IN('.implode(',', db_input(array_filter($teams)))
                        .') AND '
                        .sprintf('task.flags & %d != 0 ', TaskModel::ISOPEN)
                        .')';

        if(!$staff->showAssignedOnly() && ($depts=$staff->getDepts())) //Staff with limited access just see Assigned tickets.
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
            'object_type' => 'A',
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

    static function getInternalForm($source=null) {
        if (!isset(static::$internalForm))
            static::$internalForm = new SimpleForm(self::getInternalFields(), $source);

        return static::$internalForm;
    }

    static function getInternalFields() {
        return array(
                'dept_id' => new DepartmentField(array(
                    'id'=>1,
                    'label' => __('Department'),
                    'flags' => hexdec(0X450F3),
                    'required' => true,
                    )),
                'staff_id' => new AssigneeField(array(
                    'id'=>2,
                    'label' => __('Assignee'),
                    'flags' => hexdec(0X450F3),
                    'required' => false,
                    )),
                'duedate'  =>  new DatetimeField(array(
                    'id' => 3,
                    'label' => __('Due Date'),
                    'flags' => hexdec(0X450B3),
                    'required' => false,
                    'configuration' => array(
                        'min' => Misc::gmtime(),
                        'time' => true,
                        'gmt' => true,
                        'future' => true,
                        ),
                    )),

            );
    }
}

// Task thread class
class TaskThread extends ObjectThread {

    function addDescription($vars, &$errors=array()) {

        $vars['threadId'] = $this->getId();
        $vars['message'] = $vars['description'];
        unset($vars['description']);

        return MessageThreadEntry::create($vars, $errors);
    }

    static function create($task) {
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
