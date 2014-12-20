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
            'staff' => array(
                'constraint' => array('staff_id' => 'Staff.staff_id'),
                'null' => true,
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
                /* @trans */ 'Create',
                /* @trans */ 'Ability to create tasks'),
            self::PERM_EDIT      => array(
                /* @trans */ 'Edit',
                /* @trans */ 'Ability to edit tasks'),
            self::PERM_ASSIGN    => array(
                /* @trans */ 'Assign',
                /* @trans */ 'Ability to assign tasks to agents or teams'),
            self::PERM_TRANSFER  => array(
                /* @trans */ 'Transfer',
                /* @trans */ 'Ability to transfer tasks between departments'),
            self::PERM_CLOSE     => array(
                /* @trans */ 'Close',
                /* @trans */ 'Ability to close tasks'),
            self::PERM_DELETE    => array(
                /* @trans */ 'Delete',
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

    function getTeamId() {
        return $this->team_id;
    }

    function getDeptId() {
        return $this->dept_id;
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


class Task extends TaskModel {
    var $form;
    var $entry;
    var $thread;

    var $_entries;


    function getStatus() {
        return $this->isOpen() ? _('Open') : _('Closed');
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

    function getAssignees() {

        $assignees=array();
        if ($this->staff)
            $assignees[] = $this->staff->getName();

        //Add team assignment
        if (isset($this->team))
            $assignees[] = $this->team->getName();

        return $assignees;
    }

    function getAssigned($glue='/') {
        $assignees = $this->getAssignees();

        return $assignees ? implode($glue, $assignees):'';
    }

    function getThread() {

        if (!$this->thread)
            $this->thread = TaskThread::lookup(array(
                        'object_id' => $this->getId(),
                        'object_type' => ObjectModel::OBJECT_TYPE_TASK)
                    );

        return $this->thread;
    }

    function getThreadEntry($id) {
        return $this->getThread()->getEntry($id);
    }

    function getThreadEntries($type, $order='') {
        return $this->getThread()->getEntries(
                array('type' => $type, 'order' => $order));
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
            if (!$e->getForm()
                    || ($ftype && $ftype != $e->getForm()->get('type')))
                continue;

            // Get the named field and return the answer
            if ($f = $e->getForm()->getField($field))
                return $f->getAnswer();
        }

        return null;
    }

    function __toString() {
        return (string) $this->getTitle();
    }

    /* util routines */
    function assign($vars, &$errors) {
        global $thisstaff;

        if (!isset($vars['staff_id']) || !($staff=Staff::lookup($vars['staff_id'])))
            $errors['staff_id'] = __('Agent selection required');
        elseif ($staff->getid() == $this->getStaffId())
            $errors['dept_id'] = __('Task already assigned to agent');
        else
            $this->staff_id = $staff->getId();

        if ($errors || !$this->save())
            return false;

        // Transfer completed... post internal note.
        $title = sprintf(__('Task assigned to %s'),
                $staff->getName());
        if ($vars['comments']) {
            $note = $vars['comments'];
        } else {
            $note = $title;
            $title = '';
        }

        $this->postNote(
                array('note' => $note, 'title' => $title),
                $errors,
                $thisstaff);

        return true;
    }

    function transfer($vars, &$errors) {
        global $thisstaff;

        if (!isset($vars['dept_id']) || !($dept=Dept::lookup($vars['dept_id'])))
            $errors['dept_id'] = __('Department selection required');
        elseif ($dept->getid() == $this->getDeptId())
            $errors['dept_id'] = __('Task already in the department');
        else
            $this->dept_id = $dept->getId();

        if ($errors || !$this->save())
            return false;

        // Transfer completed... post internal note.
        $title = sprintf(__('Task transfered to %s department'),
                $dept->getName());
        if ($vars['comments']) {
            $note = $vars['comments'];
        } else {
            $note = $title;
            $title = '';
        }

        $this->postNote(
                array('note' => $note, 'title' => $title),
                $errors,
                $thisstaff);

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

        if (isset($vars['task_status'])) {
            if ($vars['task_status'])
                $this->reopen();
            else
                $this->close();

            $this->save(true);
        }

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

    static function create($vars) {
        global $cfg;

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
        Signal::send('model.created', $task);

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
}

class TaskForm extends DynamicForm {
    static $instance;
    static $defaultForm;
    static $internalForm;

    static $forms;

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
            static::$internalForm = new Form(self::getInternalFields(), $source);

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
        return parent::create(array(
                    'object_id' => $id,
                    'object_type' => ObjectModel::OBJECT_TYPE_TASK
                    ));
    }

}
?>
