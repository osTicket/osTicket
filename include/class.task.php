<?php

class TaskModel extends VerySimpleModel {
    static $meta = array(
        'table' => TASK_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'thread' => array(
                'reverse' => 'ThreadModel.object',
            ),
        )
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

}

class Task extends TaskModel {
    var $form;
    var $entry;
    var $thread;


    function getStatus() {
        return $this->isOpen() ? _('Open') : _('Closed');
    }

    function getTitle() {
        return $this->__cdata('title', ObjectModel::OBJECT_TYPE_TASK);
    }


    function getDept() {
        return "Dept Object";
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
                $this->open();
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

    static function create($form, $object) {
        global $cfg, $thisstaff;

        if (!$thisstaff
                || !$form
                // TODO: Make sure it's an instance of ORM Model
                || !$object)
            return null;

        if (!$form->isValid())
            return false;

        try {

            $task = parent::create(array(
                'flags' => 1,
                'object_id' => $object->getId(),
                'object_type' => $object->getObjectType(),
                'number' => $cfg->getNewTaskNumber(),
                'created' => new SqlFunction('NOW'),
                'updated' => new SqlFunction('NOW'),
            ));
            $task->save(true);
        } catch(OrmException $e) {
            return null;
        }

        $vars = $form->getClean();
        $task->addDynamicData($vars);
        // Create a thread + message.
        $thread = TaskThread::create($task);
        $desc = $form->getField('description');
        if ($desc
                && $desc->isAttachmentsEnabled()
                && ($attachments=$desc->getWidget()->getAttachments()))
            $vars['cannedattachments'] = $attachments->getClean();

        $vars['staffId'] = $thisstaff->getId();
        $vars['poster'] = $thisstaff;
        if (!$vars['ip_address'] && $_SERVER['REMOTE_ADDR'])
            $vars['ip_address'] = $SERVER['REMOTE_ADDR'];

        $thread->addDescription($vars);

        Signal::send('model.created', $task);

        return $task;
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
    static $form;

    static function objects() {
        $os = parent::objects();
        return $os->filter(array('type'=>ObjectModel::OBJECT_TYPE_TASK));
    }

    static function getDefaultForm() {
        if (!isset(static::$form)) {
            if (($o = static::objects()) && $o[0])
                static::$form = $o[0];
        }

        return static::$form;
    }

    static function getInstance($object_id=0, $new=false) {
        if ($new || !isset(static::$instance))
            static::$instance = static::getDefaultForm()->instanciate();

        static::$instance->object_type = ObjectModel::OBJECT_TYPE_TASK;

        if ($object_id)
            static::$instance->object_id = $object_id;

        return static::$instance;
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
