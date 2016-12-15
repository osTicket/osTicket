<?php
/*********************************************************************
    class.dynamic_forms.php

    Forms models built on the VerySimpleModel paradigm. Allows for arbitrary
    data to be associated with tickets. Eventually this model can be
    extended to associate arbitrary data with registered clients and thread
    entries.

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once(INCLUDE_DIR . 'class.orm.php');
require_once(INCLUDE_DIR . 'class.forms.php');
require_once(INCLUDE_DIR . 'class.list.php');
require_once(INCLUDE_DIR . 'class.filter.php');
require_once(INCLUDE_DIR . 'class.signal.php');

/**
 * Form template, used for designing the custom form and for entering custom
 * data for a ticket
 */
class DynamicForm extends VerySimpleModel {

    static $meta = array(
        'table' => FORM_SEC_TABLE,
        'ordering' => array('title'),
        'pk' => array('id'),
        'joins' => array(
            'fields' => array(
                'reverse' => 'DynamicFormField.form',
            ),
        ),
    );

    // Registered form types
    static $types = array(
        'T' => 'Ticket Information',
        'U' => 'User Information',
        'O' => 'Organization Information',
    );

    const FLAG_DELETABLE    = 0x0001;
    const FLAG_DELETED      = 0x0002;

    var $_form;
    var $_fields;
    var $_has_data = false;
    var $_dfields;

    function getInfo() {
        $base = $this->ht;
        unset($base['fields']);
        return $base;
    }

    function getId() {
        return $this->id;
    }

    /**
     * Fetch a list of field implementations for the fields defined in this
     * form. This method should *always* be preferred over
     * ::getDynamicFields() to avoid caching confusion
     */
    function getFields() {
        if (!$this->_fields) {
            $this->_fields = new ListObject();
            foreach ($this->getDynamicFields() as $f)
                $this->_fields->append($f->getImpl($f));
        }
        return $this->_fields;
    }

    /**
     * Fetch the dynamic fields associated with this dynamic form. Do not
     * use this list for data processing or validation. Use ::getFields()
     * for that.
     */
    function getDynamicFields() {
        return $this->fields;
    }

    // Multiple inheritance -- delegate methods not defined to a forms API
    // Form
    function __call($what, $args) {
        $delegate = array($this->getForm(), $what);
        if (!is_callable($delegate))
            throw new Exception(sprintf(__('%s: Call to non-existing function'), $what));
        return call_user_func_array($delegate, $args);
    }

    function getTitle() {
        return $this->getLocal('title');
    }

    function getInstructions() {
        return $this->getLocal('instructions');
    }

    /**
     * Drop field errors clean info etc. Useful when replacing the source
     * content of the form. This is necessary because the field listing is
     * cached under some circumstances.
     */
    function reset() {
        foreach ($this->getFields() as $f)
            $f->reset();
        return $this;
    }

    function getForm($source=false) {
        if ($source)
            $this->reset();
        $fields = $this->getFields();
        $form = new SimpleForm($fields, $source, array(
            'title' => $this->getLocal('title'),
            'instructions' => $this->getLocal('instructions'))
        );
        return $form;
    }

    function isDeletable() {
        return $this->flags & self::FLAG_DELETABLE;
    }

    function setFlag($flag) {
        $this->flags |= $flag;
    }

    function hasAnyVisibleFields($user=false) {
        global $thisstaff, $thisclient;
        $user = $user ?: $thisstaff ?: $thisclient;
        $visible = 0;
        $isstaff = $user instanceof Staff;
        foreach ($this->getFields() as $F) {
            if ($isstaff) {
                if ($F->isVisibleToStaff())
                    $visible++;
            }
            elseif ($F->isVisibleToUsers()) {
                $visible++;
            }
        }
        return $visible > 0;
    }

    function instanciate($sort=1, $data=null) {
        $inst = DynamicFormEntry::create(
            array('form_id'=>$this->get('id'), 'sort'=>$sort)
        );
        if ($data)
            $inst->setSource($data);
        return $inst;
    }

    function disableFields(array $ids) {
        foreach ($this->getFields() as $F) {
            if (in_array($F->get('id'), $ids)) {
                $F->disable();
            }
        }
    }

    function getTranslateTag($subtag) {
        return _H(sprintf('form.%s.%s', $subtag, $this->id));
    }
    function getLocal($subtag) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->get($subtag);
    }

    function save($refetch=false) {
        if (count($this->dirty))
            $this->set('updated', new SqlFunction('NOW'));
        if ($rv = parent::save($refetch | $this->dirty))
            return $this->saveTranslations();
        return $rv;
    }

    function delete() {

        if (!$this->isDeletable())
            return false;

        // Soft Delete: Mark the form as deleted.
        $this->setFlag(self::FLAG_DELETED);
        return $this->save();
    }

    function getExportableFields($exclude=array(), $prefix='__') {
        $fields = array();
        foreach ($this->getFields() as $f) {
            // Ignore core fields
            if ($exclude && in_array($f->get('name'), $exclude))
                continue;
            // Ignore non-data fields
            // FIXME: Consider ::isStorable() too
            elseif (!$f->hasData() || $f->isPresentationOnly())
                continue;

            $name = $f->get('name') ?: ('field_'.$f->get('id'));
            $fields[$prefix.$name] = $f;
        }
        return $fields;
    }

    static function create($ht=false) {
        $inst = new static($ht);
        $inst->set('created', new SqlFunction('NOW'));
        if (isset($ht['fields'])) {
            $inst->save();
            foreach ($ht['fields'] as $f) {
                $field = DynamicFormField::create(array('form' => $inst) + $f);
                $field->save();
            }
        }
        return $inst;
    }

    function saveTranslations($vars=false) {
        global $thisstaff;

        $vars = $vars ?: $_POST;
        $tags = array(
            'title' => $this->getTranslateTag('title'),
            'instructions' => $this->getTranslateTag('instructions'),
        );
        $rtags = array_flip($tags);
        $translations = CustomDataTranslation::allTranslations($tags, 'phrase');
        foreach ($translations as $t) {
            $T = $rtags[$t->object_hash];
            $content = @$vars['trans'][$t->lang][$T];
            if (!isset($content))
                continue;

            // Content is not new and shouldn't be added below
            unset($vars['trans'][$t->lang][$T]);

            $t->text = $content;
            $t->agent_id = $thisstaff->getId();
            $t->updated = SqlFunction::NOW();
            if (!$t->save())
                return false;
        }
        // New translations (?)
        if ($vars['trans'] && is_array($vars['trans'])) {
            foreach ($vars['trans'] as $lang=>$parts) {
                if (!Internationalization::isLanguageEnabled($lang))
                    continue;
                foreach ($parts as $T => $content) {
                    $content = trim($content);
                    if (!$content)
                        continue;
                    $t = CustomDataTranslation::create(array(
                        'type'      => 'phrase',
                        'object_hash' => $tags[$T],
                        'lang'      => $lang,
                        'text'      => $content,
                        'agent_id'  => $thisstaff->getId(),
                        'updated'   => SqlFunction::NOW(),
                    ));
                    if (!$t->save())
                        return false;
                }
            }
        }
        return true;
    }

    static function ensureDynamicDataView() {

        if (!($cdata=static::$cdata) || !$cdata['table'])
            return false;

        $sql = 'SHOW TABLES LIKE \''.$cdata['table'].'\'';
        if (!db_num_rows(db_query($sql)))
            return static::buildDynamicDataView($cdata);
    }

    static function buildDynamicDataView($cdata) {
        $sql = 'CREATE TABLE IF NOT EXISTS `'.$cdata['table'].'` (PRIMARY KEY
                ('.$cdata['object_id'].')) DEFAULT CHARSET=utf8 AS '
             .  static::getCrossTabQuery( $cdata['object_type'], $cdata['object_id']);
        db_query($sql);
    }

    static function dropDynamicDataView($table) {
        db_query('DROP TABLE IF EXISTS `'.$table.'`');
    }

    static function updateDynamicDataView($answer, $data) {
        // TODO: Detect $data['dirty'] for value and value_id
        // We're chiefly concerned with Ticket form answers

        $cdata = static::$cdata;
        if (!$cdata
                || !$cdata['table']
                || !($e = $answer->getEntry())
                || $e->form->get('type') != $cdata['object_type'])
            return;

        // $record = array();
        // $record[$f] = $answer->value'
        // TicketFormData::objects()->filter(array('ticket_id'=>$a))
        //      ->merge($record);
        $sql = 'SHOW TABLES LIKE \''.$cdata['table'].'\'';
        if (!db_num_rows(db_query($sql)))
            return;

        $f = $answer->getField();
        $name = $f->get('name') ? $f->get('name')
            : 'field_'.$f->get('id');
        $fields = sprintf('`%s`=', $name) . db_input($answer->getSearchKeys());
        $sql = 'INSERT INTO `'.$cdata['table'].'` SET '.$fields
            . sprintf(', `%s`= %s',
                    $cdata['object_id'],
                    db_input($answer->getEntry()->get('object_id')))
            .' ON DUPLICATE KEY UPDATE '.$fields;
        if (!db_query($sql))
            return self::dropDynamicDataView($cdata['table']);
    }

    static function updateDynamicFormEntryAnswer($answer, $data) {
        if (!$answer
                || !($e = $answer->getEntry())
                || !$e->form)
            return;

        switch ($e->form->get('type')) {
        case 'T':
            return TicketForm::updateDynamicDataView($answer, $data);
        case 'A':
            return TaskForm::updateDynamicDataView($answer, $data);
        case 'U':
            return UserForm::updateDynamicDataView($answer, $data);
        case 'O':
            return OrganizationForm::updateDynamicDataView($answer, $data);
        }

    }

    static function updateDynamicFormField($field, $data) {
        if (!$field || !$field->form)
            return;

        switch ($field->form->get('type')) {
        case 'T':
            return TicketForm::dropDynamicDataView(TicketForm::$cdata['table']);
        case 'A':
            return TaskForm::dropDynamicDataView(TaskForm::$cdata['table']);
        case 'U':
            return UserForm::dropDynamicDataView(UserForm::$cdata['table']);
        case 'O':
            return OrganizationForm::dropDynamicDataView(OrganizationForm::$cdata['table']);
        }

    }

    static function getCrossTabQuery($object_type, $object_id='object_id', $exclude=array()) {
        $fields = static::getDynamicDataViewFields($exclude);
        return "SELECT entry.`object_id` as `$object_id`, ".implode(',', $fields)
            .' FROM '.FORM_ENTRY_TABLE.' entry
            JOIN '.FORM_ANSWER_TABLE.' ans ON ans.entry_id = entry.id
            JOIN '.FORM_FIELD_TABLE." field ON field.id=ans.field_id
            WHERE entry.object_type='$object_type' GROUP BY entry.object_id";
    }

    // Materialized View for custom data (MySQL FlexViews would be nice)
    //
    // @see http://code.google.com/p/flexviews/
    static function getDynamicDataViewFields($exclude) {
        $fields = array();
        foreach (static::getInstance()->getFields() as $f) {
            if ($exclude && in_array($f->get('name'), $exclude))
                continue;

            $impl = $f->getImpl($f);
            if (!$impl->hasData() || $impl->isPresentationOnly())
                continue;

            $id = $f->get('id');
            $name = ($f->get('name')) ? $f->get('name')
                : 'field_'.$id;

            if ($impl instanceof ChoiceField || $impl instanceof SelectionField) {
                $fields[] = sprintf(
                    'MAX(CASE WHEN field.id=\'%1$s\' THEN REPLACE(REPLACE(REPLACE(REPLACE(coalesce(ans.value_id, ans.value), \'{\', \'\'), \'}\', \'\'), \'"\', \'\'), \':\', \',\') ELSE NULL END) as `%2$s`',
                    $id, $name);
            }
            else {
                $fields[] = sprintf(
                    'MAX(IF(field.id=\'%1$s\',coalesce(ans.value_id, ans.value),NULL)) as `%2$s`',
                    $id, $name);
            }
        }
        return $fields;
    }



}

class UserForm extends DynamicForm {
    static $instance;
    static $form;

    static $cdata = array(
            'table' => USER_CDATA_TABLE,
            'object_id' => 'user_id',
            'object_type' => ObjectModel::OBJECT_TYPE_USER,
        );

    static function objects() {
        $os = parent::objects();
        return $os->filter(array('type'=>'U'));
    }

    static function getUserForm() {
        if (!isset(static::$form)) {
            static::$form = static::objects()->one();
        }
        return static::$form;
    }

    static function getInstance() {
        if (!isset(static::$instance))
            static::$instance = static::getUserForm()->instanciate();
        return static::$instance;
    }

    static function getNewInstance() {
        $o = static::objects()->one();
        static::$instance = $o->instanciate();
        return static::$instance;
    }
}
Filter::addSupportedMatches(/* @trans */ 'User Data', function() {
    $matches = array();
    foreach (UserForm::getInstance()->getFields() as $f) {
        if (!$f->hasData())
            continue;
        $matches['field.'.$f->get('id')] = __('User').' / '.$f->getLabel();
        if (($fi = $f->getImpl()) && $fi->hasSubFields()) {
            foreach ($fi->getSubFields() as $p) {
                $matches['field.'.$f->get('id').'.'.$p->get('id')]
                    = __('User').' / '.$f->getLabel().' / '.$p->getLabel();
            }
        }
    }
    return $matches;
}, 20);

class TicketForm extends DynamicForm {
    static $instance;

    static $cdata = array(
            'table' => TICKET_CDATA_TABLE,
            'object_id' => 'ticket_id',
            'object_type' => 'T',
        );

    static function objects() {
        $os = parent::objects();
        return $os->filter(array('type'=>'T'));
    }

    static function getInstance() {
        if (!isset(static::$instance))
            self::getNewInstance();
        return static::$instance;
    }

    static function getNewInstance() {
        $o = static::objects()->one();
        static::$instance = $o->instanciate();
        return static::$instance;
    }

}
// Add fields from the standard ticket form to the ticket filterable fields
Filter::addSupportedMatches(/* @trans */ 'Ticket Data', function() {
    $matches = array();
    foreach (TicketForm::getInstance()->getFields() as $f) {
        if (!$f->hasData())
            continue;
        $matches['field.'.$f->get('id')] = __('Ticket').' / '.$f->getLabel();
        if (($fi = $f->getImpl()) && $fi->hasSubFields()) {
            foreach ($fi->getSubFields() as $p) {
                $matches['field.'.$f->get('id').'.'.$p->get('id')]
                    = __('Ticket').' / '.$f->getLabel().' / '.$p->getLabel();
            }
        }
    }
    return $matches;
}, 30);
// Manage materialized view on custom data updates
Signal::connect('model.created',
    array('DynamicForm', 'updateDynamicFormEntryAnswer'),
    'DynamicFormEntryAnswer');
Signal::connect('model.updated',
    array('DynamicForm', 'updateDynamicFormEntryAnswer'),
    'DynamicFormEntryAnswer');
// Recreate the dynamic view after new or removed fields to the ticket
// details form
Signal::connect('model.created',
    array('DynamicForm', 'updateDynamicFormField'),
    'DynamicFormField');
Signal::connect('model.deleted',
    array('DynamicForm', 'updateDynamicFormField'),
    'DynamicFormField');
// If the `name` column is in the dirty list, we would be renaming a
// column. Delete the view instead.
Signal::connect('model.updated',
    array('DynamicForm', 'updateDynamicFormField'),
    'DynamicFormField',
    function($o, $d) { return isset($d['dirty'])
        && (isset($d['dirty']['name']) || isset($d['dirty']['type'])); });

Filter::addSupportedMatches(/* trans */ 'Custom Forms', function() {
    $matches = array();
    foreach (DynamicForm::objects()->filter(array('type'=>'G')) as $form) {
        foreach ($form->getFields() as $f) {
            if (!$f->hasData())
                continue;
            $matches['field.'.$f->get('id')] = $form->getTitle().' / '.$f->getLabel();
            if (($fi = $f->getImpl()) && $fi->hasSubFields()) {
                foreach ($fi->getSubFields() as $p) {
                    $matches['field.'.$f->get('id').'.'.$p->get('id')]
                        = $form->getTitle().' / '.$f->getLabel().' / '.$p->getLabel();
                }
            }
        }
    }
    return $matches;
}, 9900);

require_once(INCLUDE_DIR . "class.json.php");

class DynamicFormField extends VerySimpleModel {

    static $meta = array(
        'table' => FORM_FIELD_TABLE,
        'ordering' => array('sort'),
        'pk' => array('id'),
        'select_related' => array('form'),
        'joins' => array(
            'form' => array(
                'null' => true,
                'constraint' => array('form_id' => 'DynamicForm.id'),
            ),
        ),
    );

    var $_field;
    var $_disabled = false;

    const FLAG_ENABLED          = 0x00001;
    const FLAG_EXT_STORED       = 0x00002; // Value stored outside of form_entry_value
    const FLAG_CLOSE_REQUIRED   = 0x00004;

    const FLAG_MASK_CHANGE      = 0x00010;
    const FLAG_MASK_DELETE      = 0x00020;
    const FLAG_MASK_EDIT        = 0x00040;
    const FLAG_MASK_DISABLE     = 0x00080;
    const FLAG_MASK_REQUIRE     = 0x10000;
    const FLAG_MASK_VIEW        = 0x20000;
    const FLAG_MASK_NAME        = 0x40000;

    const MASK_MASK_INTERNAL    = 0x400B2;  # !change, !delete, !disable, !edit-name
    const MASK_MASK_ALL         = 0x700F2;

    const FLAG_CLIENT_VIEW      = 0x00100;
    const FLAG_CLIENT_EDIT      = 0x00200;
    const FLAG_CLIENT_REQUIRED  = 0x00400;

    const MASK_CLIENT_FULL      = 0x00700;

    const FLAG_AGENT_VIEW       = 0x01000;
    const FLAG_AGENT_EDIT       = 0x02000;
    const FLAG_AGENT_REQUIRED   = 0x04000;

    const MASK_AGENT_FULL       = 0x7000;

    // Multiple inheritance -- delegate methods not defined here to the
    // forms API FormField instance
    function __call($what, $args) {
        return call_user_func_array(
            array($this->getField(), $what), $args);
    }

    /**
     * Fetch a forms API FormField instance which represents this designable
     * DynamicFormField.
     */
    function getField() {
        global $thisstaff;

        // Finagle the `required` flag for the FormField instance
        $ht = $this->ht;
        $ht['required'] = ($thisstaff) ? $this->isRequiredForStaff()
            : $this->isRequiredForUsers();

        if (!isset($this->_field))
            $this->_field = new FormField($ht);
        return $this->_field;
    }

    function getForm() { return $this->form; }
    function getFormId() { return $this->form_id; }

    /**
     * setConfiguration
     *
     * Used in the POST request of the configuration process. The
     * ::getConfigurationForm() method should be used to retrieve a
     * configuration form for this field. That form should be submitted via
     * a POST request, and this method should be called in that request. The
     * data from the POST request will be interpreted and will adjust the
     * configuration of this field
     *
     * Parameters:
     * vars - POST request / data
     * errors - (OUT array) receives validation errors of the parsed
     *      configuration form
     *
     * Returns:
     * (bool) true if the configuration was updated, false if there were
     * errors. If false, the errors were written into the received errors
     * array.
     */
    function setConfiguration($vars, &$errors=array()) {
        $config = array();
        foreach ($this->getConfigurationForm($vars)->getFields() as $name=>$field) {
            $config[$name] = $field->to_php($field->getClean());
            $errors = array_merge($errors, $field->errors());
        }

        if (count($errors))
            return false;

        // See if field impl. need to save or override anything
        $config = $this->getImpl()->to_config($config);
        $this->set('configuration', JsonDataEncoder::encode($config));
        $this->set('hint', Format::sanitize($vars['hint']));

        return true;
    }

    function isDeletable() {
        return !$this->hasFlag(self::FLAG_MASK_DELETE);
    }
    function isNameForced() {
        return $this->hasFlag(self::FLAG_MASK_NAME);
    }
    function isPrivacyForced() {
        return $this->hasFlag(self::FLAG_MASK_VIEW);
    }
    function isRequirementForced() {
        return $this->hasFlag(self::FLAG_MASK_REQUIRE);
    }

    function  isChangeable() {
        return !$this->hasFlag(self::FLAG_MASK_CHANGE);
    }

    function  isEditable() {
        return $this->hasFlag(self::FLAG_MASK_EDIT);
    }
    function disable() {
        $this->_disabled = true;
    }
    function isEnabled() {
        return !$this->_disabled && $this->hasFlag(self::FLAG_ENABLED);
    }

    function hasFlag($flag) {
        return (isset($this->flags) && ($this->flags & $flag) != 0);
    }

    /**
     * Describes the current visibility settings for this field. Returns a
     * comma-separated, localized list of flag descriptions.
     */
    function getVisibilityDescription() {
        $F = $this->flags;

        if (!$this->hasFlag(self::FLAG_ENABLED))
            return __('Disabled');

        $impl = $this->getImpl();

        $hints = array();
        $VIEW = self::FLAG_CLIENT_VIEW | self::FLAG_AGENT_VIEW;
        if (($F & $VIEW) == 0) {
            $hints[] = __('Hidden');
        }
        elseif (~$F & self::FLAG_CLIENT_VIEW) {
            $hints[] = __('Internal');
        }
        elseif (~$F & self::FLAG_AGENT_VIEW) {
            $hints[] = __('For EndUsers Only');
        }
        if ($impl->hasData()) {
            if ($F & (self::FLAG_CLIENT_REQUIRED | self::FLAG_AGENT_REQUIRED)) {
                $hints[] = __('Required');
            }
            else {
                $hints[] = __('Optional');
            }
            if (!($F & (self::FLAG_CLIENT_EDIT | self::FLAG_AGENT_EDIT))) {
                $hints[] = __('Immutable');
            }
        }
        return implode(', ', $hints);
    }
    function getTranslateTag($subtag) {
        return _H(sprintf('field.%s.%s', $subtag, $this->id));
    }
    function getLocal($subtag, $default=false) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : ($default ?: $this->get($subtag));
    }

    /**
     * Fetch a list of names to flag settings to make configuring new fields
     * a bit easier.
     *
     * Returns:
     * <Array['desc', 'flags']>, where the 'desc' key is a localized
     * description of the flag set, and the 'flags' key is a bit mask of
     * flags which should be set on the new field to implement the
     * requirement / visibility mode.
     */
    function allRequirementModes() {
        return array(
            'a' => array('desc' => __('Optional'),
                'flags' => self::FLAG_CLIENT_VIEW | self::FLAG_AGENT_VIEW
                    | self::FLAG_CLIENT_EDIT | self::FLAG_AGENT_EDIT),
            'b' => array('desc' => __('Required'),
                'flags' => self::FLAG_CLIENT_VIEW | self::FLAG_AGENT_VIEW
                    | self::FLAG_CLIENT_EDIT | self::FLAG_AGENT_EDIT
                    | self::FLAG_CLIENT_REQUIRED | self::FLAG_AGENT_REQUIRED),
            'c' => array('desc' => __('Required for EndUsers'),
                'flags' => self::FLAG_CLIENT_VIEW | self::FLAG_AGENT_VIEW
                    | self::FLAG_CLIENT_EDIT | self::FLAG_AGENT_EDIT
                    | self::FLAG_CLIENT_REQUIRED),
            'd' => array('desc' => __('Required for Agents'),
                'flags' => self::FLAG_CLIENT_VIEW | self::FLAG_AGENT_VIEW
                    | self::FLAG_CLIENT_EDIT | self::FLAG_AGENT_EDIT
                    | self::FLAG_AGENT_REQUIRED),
            'e' => array('desc' => __('Internal, Optional'),
                'flags' => self::FLAG_AGENT_VIEW | self::FLAG_AGENT_EDIT),
            'f' => array('desc' => __('Internal, Required'),
                'flags' => self::FLAG_AGENT_VIEW | self::FLAG_AGENT_EDIT
                    | self::FLAG_AGENT_REQUIRED),
            'g' => array('desc' => __('For EndUsers Only'),
                'flags' => self::FLAG_CLIENT_VIEW | self::FLAG_CLIENT_EDIT
                    | self::FLAG_CLIENT_REQUIRED),
        );
    }

    /**
     * Fetch a list of valid requirement modes for this field. This list
     * will be filtered based on flags which are not supported or not
     * allowed for this field.
     *
     * Deprecated:
     * This was used in previous versions when a drop-down list was
     * presented for editing a field's visibility. The current software
     * version presents the drop-down list for new fields only.
     *
     * Returns:
     * <Array['desc', 'flags']> Filtered list from ::allRequirementModes
     */
    function getAllRequirementModes() {
        $modes = static::allRequirementModes();
        if ($this->isPrivacyForced()) {
            // Required to be internal
            foreach ($modes as $m=>$info) {
                if ($info['flags'] & (self::FLAG_CLIENT_VIEW | self::FLAG_AGENT_VIEW))
                    unset($modes[$m]);
            }
        }

        if ($this->isRequirementForced()) {
            // Required to be required
            foreach ($modes as $m=>$info) {
                if ($info['flags'] & (self::FLAG_CLIENT_REQUIRED | self::FLAG_AGENT_REQUIRED))
                    unset($modes[$m]);
            }
        }
        return $modes;
    }

    function setRequirementMode($mode) {
        $modes = $this->getAllRequirementModes();
        if (!isset($modes[$mode]))
            return false;

        $info = $modes[$mode];
        $this->set('flags', $info['flags'] | self::FLAG_ENABLED);
    }

    function isRequiredForStaff() {
        return $this->hasFlag(self::FLAG_AGENT_REQUIRED);
    }
    function isRequiredForUsers() {
        return $this->hasFlag(self::FLAG_CLIENT_REQUIRED);
    }
    function isRequiredForClose() {
        return $this->hasFlag(self::FLAG_CLOSE_REQUIRED);
    }
    function isEditableToStaff() {
        return $this->isEnabled()
            && $this->hasFlag(self::FLAG_AGENT_EDIT);
    }
    function isVisibleToStaff() {
        return $this->isEnabled()
            && $this->hasFlag(self::FLAG_AGENT_VIEW);
    }
    function isEditableToUsers() {
        return $this->isEnabled()
            && $this->hasFlag(self::FLAG_CLIENT_EDIT);
    }
    function isVisibleToUsers() {
        return $this->isEnabled()
            && $this->hasFlag(self::FLAG_CLIENT_VIEW);
    }

    /**
     * Used when updating the form via the admin panel. This represents
     * validation on the form field template, not data entered into a form
     * field of a custom form. The latter would be isValidEntry()
     */
    function isValid() {
        if (count($this->errors()))
            return false;
        if (!$this->get('label'))
            $this->addError(
                __("Label is required for custom form fields"), "label");
        if (($this->isRequiredForStaff() || $this->isRequiredForUsers())
            && !$this->get('name')
        ) {
            $this->addError(
                __("Variable name is required for required fields"
                /* `required` is a visibility setting fields */
                /* `variable` is used for automation. Internally it's called `name` */
                ), "name");
        }
        if (preg_match('/[.{}\'"`; ]/u', $this->get('name')))
            $this->addError(__(
                'Invalid character in variable name. Please use letters and numbers only.'
            ), 'name');
        return count($this->errors()) == 0;
    }

    function delete() {
        // Don't really delete form fields with data as that will screw up the data
        // model. Instead, just drop the association with the form which
        // will give the appearance of deletion. Not deleting means that
        // the field will continue to exist on form entries it may already
        // have answers on, but since it isn't associated with the form, it
        // won't be available for new form submittals.
        $this->set('form_id', 0);

        $impl = $this->getImpl();

        // Trigger db_clean so the field can do house cleaning
        $impl->db_cleanup(true);

        // Short-circuit deletion if the field has data.
        if ($impl->hasData())
            return $this->save();

        // Delete the field for realz
        parent::delete();

    }

    function save($refetch=false) {
        if (count($this->dirty))
            $this->set('updated', new SqlFunction('NOW'));
        return parent::save($this->dirty || $refetch);
    }

    static function create($ht=false) {
        $inst = new static($ht);
        $inst->set('created', new SqlFunction('NOW'));
        if (isset($ht['configuration']))
            $inst->configuration = JsonDataEncoder::encode($ht['configuration']);
        return $inst;
    }
}

/**
 * Represents an entry to a dynamic form. Used to render the completed form
 * in reference to the attached ticket, etc. A form is used to represent the
 * template of enterable data. This represents the data entered into an
 * instance of that template.
 *
 * The data of the entry is called 'answers' in this model. This model
 * represents an instance of a form entry. The data / answers to that entry
 * are represented individually in the DynamicFormEntryAnswer model.
 */
class DynamicFormEntry extends VerySimpleModel {

    static $meta = array(
        'table' => FORM_ENTRY_TABLE,
        'ordering' => array('sort'),
        'pk' => array('id'),
        'select_related' => array('form'),
        'joins' => array(
            'form' => array(
                'null' => true,
                'constraint' => array('form_id' => 'DynamicForm.id'),
            ),
            'answers' => array(
                'reverse' => 'DynamicFormEntryAnswer.entry'
            ),
        ),
    );

    var $_fields;
    var $_form;
    var $_errors = false;
    var $_clean = false;
    var $_source = null;

    function getId() {
        return $this->get('id');
    }

    function getAnswers() {
        return $this->answers;
    }

    function getAnswer($name) {
        foreach ($this->getAnswers() as $ans)
            if ($ans->getField()->get('name') == $name)
                return $ans;
        return null;
    }

    function setAnswer($name, $value, $id=false) {

        if ($ans=$this->getAnswer($name)) {
            $f = $ans->getField();
            if ($f->isStorable())
                $ans->setValue($value, $id);
        }
    }

    function errors() {
        return $this->_errors;
    }

    function getTitle() {
        return $this->form->getTitle();
    }

    function getInstructions() {
        return $this->form->getInstructions();
    }

    function getDynamicForm() {
        return $this->form;
    }

    function getForm($source=false, $options=array()) {
        if (!isset($this->_form)) {

            $fields = $this->getFields();
            if (isset($this->extra)) {
                $x = JsonDataParser::decode($this->extra) ?: array();
                foreach ($x['disable'] ?: array() as $id) {
                    unset($fields[$id]);
                }
            }

            $source = $source ?: $this->getSource();
            $options += array(
                'title' => $this->getTitle(),
                'instructions' => $this->getInstructions()
                );
            $this->_form = new CustomForm($fields, $source, $options);
        }


        return $this->_form;
    }

    function getDynamicFields() {
        return $this->form->fields;
    }

    function getMedia() {
        return $this->getForm()->getMedia();
    }

    function getFields() {
        if (!isset($this->_fields)) {
            $this->_fields = array();
            // Get all dynamic fields associated with the form
            // even when stored elsewhere -- important during validation
            foreach ($this->getDynamicFields() as $f) {
                $f = $f->getImpl($f);
                $this->_fields[$f->get('id')] = $f;
                $f->isnew = true;
            }
            // Include any other answers included in this entry, which may
            // be for fields which have since been deleted
            foreach ($this->getAnswers() as $a) {
                $f = $a->getField();
                $id = $f->get('id');
                if (!isset($this->_fields[$id])) {
                    // This field is not currently on the associated form
                    $a->deleted = true;
                }
                $this->_fields[$id] = $f;
                // This field has an answer, so it isn't new (to this entry)
                $f->isnew = false;
            }
        }
        return $this->_fields;
    }

    function filterFields($filter) {
        $this->getFields();
        foreach ($this->_fields as $i=>$f) {
            if ($filter($f))
                unset($this->_fields[$i]);
        }
    }

    function getSource() {
        return $this->_source ?: (isset($this->id) ? false : $_POST);
    }
    function setSource($source) {
        $this->_source = $source;
        // Ensure the field is connected to this data source
        foreach ($this->getFields() as $F)
            if (!$F->getForm())
                $F->setForm($this);
    }

    function getField($name) {
        foreach ($this->getFields() as $field)
            if (!strcasecmp($field->get('name'), $name))
                return $field;

        return null;
    }

    /**
     * Validate the form and indicate if there no errors.
     *
     * Parameters:
     * $filter - (callback) function to receive each field and return
     *      boolean true if the field's errors are significant
     * $options - options to pass to form and fields.
     *
     */
    function isValid($filter=false, $options=array()) {

        if (!is_array($this->_errors)) {
            $form = $this->getForm(false, $options);
            $form->isValid($filter);
            $this->_errors = $form->errors();
        }

        return !$this->_errors;
    }

    function isValidForClient() {
        $filter = function($f) {
            return $f->isVisibleToUsers();
        };
        return $this->isValid($filter);
    }

    function isValidForStaff() {
        $filter = function($f) {
            return $f->isVisibleToStaff();
        };
        return $this->isValid($filter);
    }

    function getClean() {
        return $this->getForm()->getClean();
    }

    /**
     * Compile a list of data used by the filtering system to match dynamic
     * content in this entry. This returs an array of `field.<id>` =>
     * <value> pairs where the <id> is the field id and the <value> is the
     * toString() value for the entered data.
     *
     * If the field returns an array for its ::getFilterData() method, the
     * data will be added in the array with the keys prefixed with
     * `field.<id>`. This is useful for properties on custom lists, for
     * instance, which can contain properties usefule for matching and
     * filtering.
     */
    function getFilterData() {
        $vars = array();
        foreach ($this->getFields() as $f) {
            $tag = 'field.'.$f->get('id');
            if ($d = $f->getFilterData()) {
                if (is_array($d)) {
                    foreach ($d as $k=>$v) {
                        if (is_string($k))
                            $vars["$tag$k"] = $v;
                        else
                            $vars[$tag] = $v;
                    }
                }
                else {
                    $vars[$tag] = $d;
                }
            }
        }
        return $vars;
    }

    function forTicket($ticket_id, $force=false) {
        static $entries = array();
        if (!isset($entries[$ticket_id]) || $force) {
            $stuff = DynamicFormEntry::objects()
                ->filter(array('object_id'=>$ticket_id, 'object_type'=>'T'));
            // If forced, don't cache the result
            if ($force)
                return $stuff;
            $entries[$ticket_id] = &$stuff;
        }
        return $entries[$ticket_id];
    }
    function setTicketId($ticket_id) {
        $this->object_type = 'T';
        $this->object_id = $ticket_id;
    }

    function setClientId($user_id) {
        $this->object_type = 'U';
        $this->object_id = $user_id;
    }

    function setObjectId($object_id) {
        $this->object_id = $object_id;
    }

    function forObject($object_id, $object_type) {
        return DynamicFormEntry::objects()
            ->filter(array('object_id'=>$object_id, 'object_type'=>$object_type));
    }

    function render($staff=true, $title=false, $options=array()) {
        return $this->getForm()->render($staff, $title, $options);
    }

    function getChanges() {
        $fields = array();
        foreach ($this->getAnswers() as $a) {
            $field = $a->getField();
            if (!$field->hasData() || $field->isPresentationOnly())
                continue;
            $after = $field->to_database($field->getClean());
            $before = $field->to_database($a->getValue());
            if ($before == $after)
                continue;
            $fields[$field->get('id')] = array($before, $after);
        }
        return $fields;
    }

    /**
     * addMissingFields
     *
     * Adds fields that have been added to the linked form (field set) since
     * this entry was originally created. If fields are added to the form,
     * the method will automatically add the fields and null answers to the
     * entry.
     */
    function addMissingFields() {
        foreach ($this->getFields() as $field) {
            if ($field->isnew && $field->isEnabled()
                && !$field->isPresentationOnly()
                && $field->hasData()
                && $field->isStorable()
            ) {
                $a = new DynamicFormEntryAnswer(
                    array('field_id'=>$field->get('id'), 'entry'=>$this));

                // Add to list of answers
                $this->answers->add($a);

                // Omit fields without data and non-storable fields.
                if (!$field->hasData() || !$field->isStorable())
                    continue;

                $a->save();
            }
        }

        // Sort the form the way it is declared to be sorted
        if ($this->_fields) {
            uasort($this->_fields,
                function($a, $b) {
                    return $a->get('sort') - $b->get('sort');
            });
        }
    }

    /**
     * Save the form entry and all associated answers.
     *
     * Returns:
     * (mixed) FALSE if updated failed, otherwise the number of dirty answers
     * which were save is returned (which may be ZERO).
     */
    function save($refetch=false) {
        if (count($this->dirty))
            $this->set('updated', new SqlFunction('NOW'));

        if (!parent::save($refetch || count($this->dirty)))
            return false;

        $dirty = 0;
        foreach ($this->getAnswers() as $a) {
            $field = $a->getField();

            // Don't save answers for presentation-only fields or fields
            // which are stored elsewhere
            if (!$field->hasData() || !$field->isStorable()
                || $field->isPresentationOnly()
            ) {
                continue;
            }
            // Set the entry here so that $field->getClean() can use the
            // entry-id if necessary
            $a->entry = $this;

            try {
                $field->setForm($this);
                $val = $field->to_database($field->getClean());
            }
            catch (FieldUnchanged $e) {
                // Don't update the answer.
                continue;
            }
            if (is_array($val)) {
                $a->set('value', $val[0]);
                $a->set('value_id', $val[1]);
            }
            else {
                $a->set('value', $val);
            }
            if ($a->dirty)
                $dirty++;
            $a->save($refetch);
        }
        return $dirty;
    }

    function delete() {
        if (!parent::delete())
            return false;

        foreach ($this->getAnswers() as $a)
            $a->delete();

        return true;
    }

    static function create($ht=false, $data=null) {
        $inst = new static($ht);
        $inst->set('created', new SqlFunction('NOW'));
        if ($data)
            $inst->setSource($data);
        foreach ($inst->getDynamicFields() as $field) {
            if (!($impl = $field->getImpl($field)))
                continue;
            if (!$impl->hasData() || !$impl->isStorable())
                continue;
            $a = new DynamicFormEntryAnswer(
                array('field'=>$field, 'entry'=>$inst));
            $a->field->setAnswer($a);
            $inst->answers->add($a);
        }
        return $inst;
    }
}

/**
 * Represents a single answer to a single field on a dynamic form. The
 * data / answer to the field is linked back to the form and field which was
 * originally used for the submission.
 */
class DynamicFormEntryAnswer extends VerySimpleModel {

    static $meta = array(
        'table' => FORM_ANSWER_TABLE,
        'ordering' => array('field__sort'),
        'pk' => array('entry_id', 'field_id'),
        'select_related' => array('field'),
        'fields' => array('entry_id', 'field_id', 'value', 'value_id'),
        'joins' => array(
            'field' => array(
                'constraint' => array('field_id' => 'DynamicFormField.id'),
            ),
            'entry' => array(
                'constraint' => array('entry_id' => 'DynamicFormEntry.id'),
            ),
        ),
    );

    var $_field;
    var $deleted = false;
    var $_value;

    function getEntry() {
        return $this->entry;
    }

    function getForm() {
        return $this->getEntry()->getForm();
    }

    function getField() {
        if (!isset($this->_field)) {
            $this->_field = $this->field->getImpl($this->field);
            $this->_field->setAnswer($this);
        }
        return $this->_field;
    }

    function getValue() {

        if (!isset($this->_value)) {
            //XXX: We're settting the value here to avoid infinite loop
            $this->_value = false;
            if (isset($this->value))
                $this->_value = $this->getField()->to_php(
                        $this->get('value'), $this->get('value_id'));
        }

        return $this->_value;
    }

    function setValue($value, $id=false) {
        $this->getField()->reset();
        $this->_value = null;
        $this->set('value', $value);
        if ($id !== false)
            $this->set('value_id', $id);
    }

    function getLocal($tag) {
        return $this->field->getLocal($tag);
    }

    function getIdValue() {
        return $this->get('value_id');
    }

    function isDeleted() {
        return $this->deleted;
    }

    function toString() {
        return $this->getField()->toString($this->getValue());
    }

    function display() {
        return $this->getField()->display($this->getValue());
    }

    function getSearchable($include_label=false) {
        if ($include_label)
            $label = Format::searchable($this->getField()->getLabel()) . " ";
        return sprintf("%s%s", $label,
            $this->getField()->searchable($this->getValue())
        );
    }

    function getSearchKeys() {
        return implode(',', (array) $this->getField()->getKeys($this->getValue()));
    }

    function asVar() {
        return $this->getField()->asVar(
            $this->get('value'), $this->get('value_id')
        );
    }

    function getVar($tag) {
        if (is_object($var = $this->asVar()) && method_exists($var, 'getVar'))
            return $var->getVar($tag);
    }

    function __toString() {
        $v = $this->toString();
        return is_string($v) ? $v : (string) $this->getValue();
    }

    function delete() {
        if (!parent::delete())
            return false;

        // Allow the field to cleanup anything else in the database
        $this->getField()->db_cleanup();
        return true;
    }

    function save($refetch=false) {
        if ($this->dirty)
            unset($this->_value);
        return parent::save($refetch);
    }
}

class SelectionField extends FormField {
    static $widget = 'ChoicesWidget';

    function getListId() {
        list(,$list_id) = explode('-', $this->get('type'));
        return $list_id ?: $this->get('list_id');
    }

    function getList() {
        if (!$this->_list)
            $this->_list = DynamicList::lookup($this->getListId());

        return $this->_list;
    }

    function getWidget($widgetClass=false) {
        $config = $this->getConfiguration();
        if ($config['widget'] == 'typeahead' && $config['multiselect'] == false)
            $widgetClass = 'TypeaheadSelectionWidget';
        elseif ($config['widget'] == 'textbox')
            $widgetClass = 'TextboxSelectionWidget';

        return parent::getWidget($widgetClass);
    }

    function display($value) {
        global $thisstaff;

        if (!is_array($value)
                || !$thisstaff // Only agents can preview for now
                || !($list=$this->getList()))
            return parent::display($value);

        $display = array();
        foreach ($value as $k => $v) {
            if (is_numeric($k)
                    && ($i=$list->getItem((int) $k))
                    && $i->hasProperties())
                $display[] = $i->display();
            else // Perhaps deleted  entry
                $display[] = $v;
        }

        return implode(',', $display);

    }

    function parse($value) {

        if (!($list=$this->getList()))
            return null;

        $config = $this->getConfiguration();
        $choices = $this->getChoices();
        $selection = array();

        if ($value && !is_array($value))
            $value = array($value);

        if ($value && is_array($value)) {
            foreach ($value as $k=>$v) {
                if ($k && ($i=$list->getItem((int) $k)))
                    $selection[$i->getId()] = $i->getValue();
                elseif (isset($choices[$k]))
                    $selection[$k] = $choices[$k];
                elseif (isset($choices[$v]))
                    $selection[$v] = $choices[$v];
                elseif (($i=$list->getItem($v, true)))
                    $selection[$i->getId()] = $i->getValue();
            }
        } elseif($value) {
            //Assume invalid textbox input to be validated
            $selection[] = $value;
        }

        // Don't return an empty array
        return $selection ?: null;
    }

    function to_database($value) {
        if (is_array($value)) {
            reset($value);
        }
        if ($value && is_array($value))
            $value = JsonDataEncoder::encode($value);

        return $value;
    }

    function to_php($value, $id=false) {
        if (is_string($value))
            $value = JsonDataParser::parse($value) ?: $value;

        if (!is_array($value)) {
            $values = array();
            $choices = $this->getChoices();
            foreach (explode(',', $value) as $V) {
                if (isset($choices[$V]))
                    $values[$V] = $choices[$V];
            }
            if ($id && isset($choices[$id]))
                $values[$id] = $choices[$id];

            if ($values)
                return $values;
            // else return $value unchanged
        }
        // Don't set the ID here as multiselect prevents using exactly one
        // ID value. Instead, stick with the JSON value only.
        return $value;
    }

    function getKeys($value) {
        if (!is_array($value))
            $value = $this->getChoice($value);
        if (is_array($value))
            return implode(', ', array_keys($value));
        return (string) $value;
    }

    // PHP 5.4 Move this to a trait
    function whatChanged($before, $after) {
        $before = (array) $before;
        $after = (array) $after;
        $added = array_diff($after, $before);
        $deleted = array_diff($before, $after);
        $added = array_map(array($this, 'display'), $added);
        $deleted = array_map(array($this, 'display'), $deleted);

        if ($added && $deleted) {
            $desc = sprintf(
                __('added <strong>%1$s</strong> and removed <strong>%2$s</strong>'),
                implode(', ', $added), implode(', ', $deleted));
        }
        elseif ($added) {
            $desc = sprintf(
                __('added <strong>%1$s</strong>'),
                implode(', ', $added));
        }
        elseif ($deleted) {
            $desc = sprintf(
                __('removed <strong>%1$s</strong>'),
                implode(', ', $deleted));
        }
        else {
            $desc = sprintf(
                __('changed to <strong>%1$s</strong>'),
                $this->display($after));
        }
        return $desc;
    }

    function asVar($value, $id=false) {
        $values = $this->to_php($value, $id);
        if (is_array($values)) {
            return new PlaceholderList($this->getList()->getAllItems()
                ->filter(array('id__in' => array_keys($values)))
            );
        }
    }

    function hasSubFields() {
        return $this->getList()->getForm();
    }
    function getSubFields() {
        $fields = new ListObject(array(
            new TextboxField(array(
                // XXX: i18n: Change to a better word when the UI changes
                'label' => '['.__('Abbrev').']',
                'id' => 'abb',
            ))
        ));
        $form = $this->getList()->getForm();
        if ($form && ($F = $form->getFields()))
            $fields->extend($F);
        return $fields;
    }

    function toString($items) {
        return is_array($items)
            ? implode(', ', $items) : (string) $items;
    }

    function validateEntry($entry) {
        parent::validateEntry($entry);
        if (!$this->errors()) {
            $config = $this->getConfiguration();
            if ($config['widget'] == 'textbox') {
                if ($entry && (
                        !($k=key($entry))
                     || !($i=$this->getList()->getItem((int) $k))
                 )) {
                    $config = $this->getConfiguration();
                    $this->_errors[] = $this->getLocal('validator-error', $config['validator-error'])
                        ?: __('Unknown or invalid input');
                }
            } elseif ($config['typeahead']
                    && ($entered = $this->getWidget()->getEnteredValue())
                    && !in_array($entered, $entry)
                    && $entered != $entry) {
                $this->_errors[] = __('Select a value from the list');
           }
        }
    }

    function getConfigurationOptions() {
        return array(
            'multiselect' => new BooleanField(array(
                'id'=>2,
                'label'=>__(/* Type of widget allowing multiple selections */ 'Multiselect'),
                'required'=>false, 'default'=>false,
                'configuration'=>array(
                    'desc'=>__('Allow multiple selections')),
            )),
            'widget' => new ChoiceField(array(
                'id'=>1,
                'label'=>__('Widget'),
                'required'=>false, 'default' => 'dropdown',
                'choices'=>array(
                    'dropdown' => __('Drop Down'),
                    'typeahead' => __('Typeahead'),
                    'textbox' => __('Text Input'),
                ),
                'configuration'=>array(
                    'multiselect' => false,
                ),
                'visibility' => new VisibilityConstraint(
                    new Q(array('multiselect__eq'=>false)),
                    VisibilityConstraint::HIDDEN
                ),
                'hint'=>__('Typeahead will work better for large lists')
            )),
            'validator-error' => new TextboxField(array(
                'id'=>5, 'label'=>__('Validation Error'), 'default'=>'',
                'configuration'=>array('size'=>40, 'length'=>80,
                    'translatable'=>$this->getTranslateTag('validator-error')
                ),
                'visibility' => new VisibilityConstraint(
                    new Q(array('widget__eq'=>'textbox')),
                    VisibilityConstraint::HIDDEN
                ),
                'hint'=>__('Message shown to user if the item entered is not in the list')
            )),
            'prompt' => new TextboxField(array(
                'id'=>3,
                'label'=>__('Prompt'), 'required'=>false, 'default'=>'',
                'hint'=>__('Leading text shown before a value is selected'),
                'configuration'=>array('size'=>40, 'length'=>40,
                    'translatable'=>$this->getTranslateTag('prompt'),
                ),
            )),
            'default' => new SelectionField(array(
                'id'=>4, 'label'=>__('Default'), 'required'=>false, 'default'=>'',
                'list_id'=>$this->getListId(),
                'configuration' => array('prompt'=>__('Select a Default')),
            )),
        );
    }

    function getConfiguration() {

        $config = parent::getConfiguration();
        if ($config['widget'])
            $config['typeahead'] = $config['widget'] == 'typeahead';

        // Drop down list does not support multiple selections
        if ($config['typeahead'])
            $config['multiselect'] = false;

        return $config;
    }

    function getChoices($verbose=false) {
        if (!$this->_choices || $verbose) {
            $choices = array();
            foreach ($this->getList()->getItems() as $i)
                $choices[$i->getId()] = $i->getValue();

            // Retired old selections
            $values = ($a=$this->getAnswer()) ? $a->getValue() : array();
            if ($values && is_array($values)) {
                foreach ($values as $k => $v) {
                    if (!isset($choices[$k])) {
                        if ($verbose) $v .= ' '.__('(retired)');
                        $choices[$k] = $v;
                    }
                }
            }

            if ($verbose) // Don't cache
                return $choices;

            $this->_choices = $choices;
        }

        return $this->_choices;
    }

    function getChoice($value) {
        $choices = $this->getChoices();
        if ($value && is_array($value)) {
            $selection = $value;
        } elseif (isset($choices[$value]))
            $selection[] = $choices[$value];
        elseif ($this->get('default'))
            $selection[] = $choices[$this->get('default')];

        return $selection;
    }

    function lookupChoice($value) {

        // See if it's in the choices.
        $choices = $this->getChoices();
        if ($choices && ($i=array_search($value, $choices)))
            return array($i=>$choices[$i]);

        // Query the store by value or extra (abbrv.)
        if (!($list=$this->getList()))
            return null;

        if ($i = $list->getItem($value))
            return array($i->getId() => $i->getValue());

        if ($i = $list->getItem($value, true))
            return array($i->getId() => $i->getValue());

        return null;
    }


    function getFilterData() {
        // Start with the filter data for the list item as the [0] index
        $data = array(parent::getFilterData());
        if (($v = $this->getClean())) {
            // Add in the properties for all selected list items in sub
            // labeled by their field id
            foreach ($v as $id=>$L) {
                if (!($li = DynamicListItem::lookup($id)))
                    continue;
                foreach ($li->getFilterData() as $prop=>$value) {
                    if (!isset($data[$prop]))
                        $data[$prop] = $value;
                    else
                        $data[$prop] .= " $value";
                }
            }
        }
        return $data;
    }

    function getSearchMethods() {
        return array(
            'set' =>        __('has a value'),
            'notset' =>     __('does not have a value'),
            'includes' =>   __('includes'),
            '!includes' =>  __('does not include'),
        );
    }

    function getSearchMethodWidgets() {
        return array(
            'set' => null,
            'notset' => null,
            'includes' => array('ChoiceField', array(
                'choices' => $this->getChoices(),
                'configuration' => array('multiselect' => true),
            )),
            '!includes' => array('ChoiceField', array(
                'choices' => $this->getChoices(),
                'configuration' => array('multiselect' => true),
            )),
        );
    }

    function getSearchQ($method, $value, $name=false) {
        $name = $name ?: $this->get('name');
        switch ($method) {
        case '!includes':
            return Q::not(array("{$name}__intersect" => array_keys($value)));
        case 'includes':
            return new Q(array("{$name}__intersect" => array_keys($value)));
        default:
            return parent::getSearchQ($method, $value, $name);
        }
    }
}

class TypeaheadSelectionWidget extends ChoicesWidget {
    function render($options=array()) {

        if ($options['mode'] == 'search')
            return parent::render($options);

        $name = $this->getEnteredValue();
        $config = $this->field->getConfiguration();
        if (is_array($this->value)) {
            $name = $name ?: current($this->value);
            $value = key($this->value);
        }
        else {
            // Pull configured default (if configured)
            $def_key = $this->field->get('default');
            if (!$def_key && $config['default'])
                $def_key = $config['default'];
            if (is_array($def_key))
                $name = current($def_key);
        }

        $source = array();
        foreach ($this->field->getList()->getItems() as $i)
            $source[] = array(
                'value' => $i->getValue(), 'id' => $i->getId(),
                'info' => sprintf('%s%s',
                    $i->getValue(),
                    (($extra= $i->getAbbrev()) ? " — $extra" : '')),
            );
        ?>
        <span style="display:inline-block">
        <input type="text" size="30" name="<?php echo $this->name; ?>_name"
            id="<?php echo $this->name; ?>" value="<?php echo Format::htmlchars($name); ?>"
            placeholder="<?php echo $config['prompt'];
            ?>" autocomplete="off" />
        <input type="hidden" name="<?php echo $this->name;
            ?>_id" id="<?php echo $this->name;
            ?>_id" value="<?php echo Format::htmlchars($value); ?>"/>
        <script type="text/javascript">
        $(function() {
            $('input#<?php echo $this->name; ?>').typeahead({
                source: <?php echo JsonDataEncoder::encode($source); ?>,
                property: 'info',
                onselect: function(item) {
                    $('input#<?php echo $this->name; ?>_name').val(item['value'])
                    $('input#<?php echo $this->name; ?>_id')
                      .attr('name', '<?php echo $this->name; ?>[' + item['id'] + ']')
                      .val(item['value']);
                    return false;
                }
            });
        });
        </script>
        </span>
        <?php
    }

    function parsedValue() {
        return array($this->getValue() => $this->getEnteredValue());
    }

    function getValue() {
        $data = $this->field->getSource();
        $name = $this->field->get('name');
        if (isset($data["{$this->name}_id"]) && is_numeric($data["{$this->name}_id"])) {
            return array($data["{$this->name}_id"] => $data["{$this->name}_name"]);
        }
        elseif (isset($data[$name])) {
            return $data[$name];
        }
        // Attempt to lookup typed value (usually from a default)
        elseif ($val = $this->getEnteredValue()) {
            return $this->field->lookupChoice($val);
        }

        return parent::getValue();
    }

    function getEnteredValue() {
        // Used to verify typeahead fields
        $data = $this->field->getSource();
        if (isset($data[$this->name.'_name'])) {
            // Drop the extra part, if any
            $v = $data[$this->name.'_name'];
            $pos = strrpos($v, ' — ');
            if ($pos !== false)
                $v = substr($v, 0, $pos);

            return trim($v);
        }
        return parent::getValue();
    }
}
?>
