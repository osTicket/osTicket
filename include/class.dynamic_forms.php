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
    );

    // Registered form types
    static $types = array(
        'T' => 'Ticket Information',
        'U' => 'User Information',
    );

    var $_form;
    var $_fields;
    var $_has_data = false;
    var $_dfields;

    function getFields($cache=true) {
        if (!isset($this->_fields) || !$cache) {
            $this->_fields = array();
            foreach ($this->getDynamicFields() as $f)
                // TODO: Index by field name or id
                $this->_fields[$f->get('id')] = $f->getImpl($f);
        }
        return $this->_fields;
    }

    function getDynamicFields() {
        if (!isset($this->_dfields)) {
            $this->_dfields = DynamicFormField::objects()
                ->filter(array('form_id'=>$this->id))
                ->all();
            foreach ($this->_dfields as $f)
                $f->setForm($this);
        }
        return $this->_dfields;
    }

    // Multiple inheritance -- delegate to Form
    function __call($what, $args) {
        $delegate = array($this->getForm(), $what);
        if (!is_callable($delegate))
            throw new Exception($what.': Call to non-existing function');
        return call_user_func_array($delegate, $args);
    }

    function getField($name) {
        foreach ($this->getDynamicFields() as $f)
            if (!strcasecmp($f->get('name'), $name))
                return $f->getImpl();
    }

    function hasField($name) {
        return ($this->getField($name));
    }


    function getTitle() { return $this->get('title'); }
    function getInstructions() { return $this->get('instructions'); }

    function getForm($source=false) {
        if (!$this->_form || $source) {
            $fields = $this->getFields($this->_has_data);
            $this->_form = new Form($fields, $source, array(
                'title'=>$this->title, 'instructions'=>$this->instructions));
        }
        return $this->_form;
    }

    function isDeletable() {
        return $this->get('deletable');
    }

    function instanciate($sort=1) {
        return DynamicFormEntry::create(array(
            'form_id'=>$this->get('id'), 'sort'=>$sort));
    }

    function data($data) {
        if ($data instanceof DynamicFormEntry) {
            $this->_fields = $data->getFields();
            $this->_has_data = true;
        }
    }

    function save($refetch=false) {
        if (count($this->dirty))
            $this->set('updated', new SqlFunction('NOW'));
        if (isset($this->dirty['notes']))
            $this->notes = Format::sanitize($this->notes);
        return parent::save($refetch);
    }

    function delete() {
        if (!$this->isDeletable())
            return false;
        else
            return parent::delete();
    }

    static function create($ht=false) {
        $inst = parent::create($ht);
        $inst->set('created', new SqlFunction('NOW'));
        if (isset($ht['fields'])) {
            $inst->save();
            foreach ($ht['fields'] as $f) {
                $f = DynamicFormField::create($f);
                $f->form_id = $inst->id;
                $f->setForm($inst);
                $f->save();
            }
        }
        return $inst;
    }
}

class UserForm extends DynamicForm {
    static $instance;
    static $form;

    static function objects() {
        $os = parent::objects();
        return $os->filter(array('type'=>'U'));
    }

    static function getUserForm() {
        if (!isset(static::$form)) {
            $o = static::objects();
            static::$form = $o[0];
        }
        return static::$form;
    }

    static function getInstance() {
        if (!isset(static::$instance))
            static::$instance = static::getUserForm()->instanciate();
        return static::$instance;
    }
}

class TicketForm extends DynamicForm {
    static $instance;

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
        $o = static::objects();
        static::$instance = $o[0]->instanciate();
        return static::$instance;
    }

    // Materialized View for Ticket custom data (MySQL FlexViews would be
    // nice)
    //
    // @see http://code.google.com/p/flexviews/
    static function getDynamicDataViewFields() {
        $fields = array();
        foreach (self::getInstance()->getFields() as $f) {
            $impl = $f->getImpl();
            if (!$impl->hasData() || $impl->isPresentationOnly())
                continue;

            $name = ($f->get('name')) ? $f->get('name')
                : 'field_'.$f->get('id');

            $fields[] = sprintf(
                'MAX(IF(field.name=\'%1$s\',ans.value,NULL)) as `%1$s`',
                $name);
            if ($impl->hasIdValue()) {
                $fields[] = sprintf(
                    'MAX(IF(field.name=\'%1$s\',ans.value_id,NULL)) as `%1$s_id`',
                    $name);
            }
        }
        return $fields;
    }

    static function ensureDynamicDataView() {
        $sql = 'SHOW TABLES LIKE \''.TABLE_PREFIX.'ticket__cdata\'';
        if (!db_num_rows(db_query($sql)))
            return static::buildDynamicDataView();
    }

    static function buildDynamicDataView() {
        // create  table __cdata (primary key (ticket_id)) as select
        // entry.object_id as ticket_id, MAX(IF(field.name = 'subject',
        // ans.value, NULL)) as `subject`,MAX(IF(field.name = 'priority',
        // ans.value, NULL)) as `priority_desc`,MAX(IF(field.name =
        // 'priority', ans.value_id, NULL)) as `priority_id`
        // FROM ost_form_entry entry LEFT JOIN ost_form_entry_values ans ON
        // ans.entry_id = entry.id LEFT JOIN ost_form_field field ON
        // field.id=ans.field_id
        // where entry.object_type='T' group by entry.object_id;
        $fields = static::getDynamicDataViewFields();
        $sql = 'CREATE TABLE `'.TABLE_PREFIX.'ticket__cdata` (PRIMARY KEY (ticket_id)) AS
            SELECT entry.`object_id` AS ticket_id, '.implode(',', $fields)
         .' FROM '.FORM_ENTRY_TABLE.' entry
            JOIN '.FORM_ANSWER_TABLE.' ans ON ans.entry_id = entry.id
            JOIN '.FORM_FIELD_TABLE.' field ON field.id=ans.field_id
            WHERE entry.object_type=\'T\' GROUP BY entry.object_id';
        db_query($sql);
    }

    static function dropDynamicDataView() {
        db_query('DROP TABLE IF EXISTS `'.TABLE_PREFIX.'ticket__cdata`');
    }

    static function updateDynamicDataView($answer, $data) {
        // TODO: Detect $data['dirty'] for value and value_id
        // We're chiefly concerned with Ticket form answers
        if (!($e = $answer->getEntry()) || $e->getForm()->get('type') != 'T')
            return;

        // $record = array();
        // $record[$f] = $answer->value'
        // TicketFormData::objects()->filter(array('ticket_id'=>$a))
        //      ->merge($record);
        $sql = 'SHOW TABLES LIKE \''.TABLE_PREFIX.'ticket__cdata\'';
        if (!db_num_rows(db_query($sql)))
            return;

        $f = $answer->getField();
        $name = $f->get('name') ? $f->get('name')
            : 'field_'.$f->get('id');
        $ids = $f->hasIdValue();
        $fields = sprintf('`%s`=', $name) . db_input($answer->get('value'));
        if ($f->hasIdValue())
            $fields .= sprintf(',`%s_id`=', $name) . db_input($answer->getIdValue());
        $sql = 'INSERT INTO `'.TABLE_PREFIX.'ticket__cdata` SET '.$fields
            .', `ticket_id`='.db_input($answer->getEntry()->get('object_id'))
            .' ON DUPLICATE KEY UPDATE '.$fields;
        if (!db_query($sql) || !db_affected_rows())
            return self::dropDynamicDataView();
    }
}
// Add fields from the standard ticket form to the ticket filterable fields
Filter::addSupportedMatches('Ticket Data', function() {
    $matches = array();
    foreach (TicketForm::getInstance()->getFields() as $f) {
        if (!$f->hasData())
            continue;
        $matches['field.'.$f->get('id')] = $f->getLabel();
    }
    return $matches;
});
// Manage materialized view on custom data updates
Signal::connect('model.created',
    array('TicketForm', 'updateDynamicDataView'),
    'DynamicFormEntryAnswer');
Signal::connect('model.updated',
    array('TicketForm', 'updateDynamicDataView'),
    'DynamicFormEntryAnswer');
// Recreate the dynamic view after new or removed fields to the ticket
// details form
Signal::connect('model.created',
    array('TicketForm', 'dropDynamicDataView'),
    'DynamicFormField',
    function($o) { return $o->getForm()->get('type') == 'T'; });
Signal::connect('model.deleted',
    array('TicketForm', 'dropDynamicDataView'),
    'DynamicFormField',
    function($o) { return $o->getForm()->get('type') == 'T'; });
// If the `name` column is in the dirty list, we would be renaming a
// column. Delete the view instead.
Signal::connect('model.updated',
    array('TicketForm', 'dropDynamicDataView'),
    'DynamicFormField',
    // TODO: Lookup the dynamic form to verify {type == 'T'}
    function($o, $d) { return isset($d['dirty'])
        && (isset($d['dirty']['name']) || isset($d['dirty']['type'])); });

require_once(INCLUDE_DIR . "class.json.php");

class DynamicFormField extends VerySimpleModel {

    static $meta = array(
        'table' => FORM_FIELD_TABLE,
        'ordering' => array('sort'),
        'pk' => array('id'),
        'joins' => array(
            'form' => array(
                'null' => true,
                'constraint' => array('form_id' => 'DynamicForm.id'),
            ),
        ),
    );

    var $_field;

    // Multiple inheritance -- delegate to FormField
    function __call($what, $args) {
        return call_user_func_array(
            array($this->getField(), $what), $args);
    }

    function getField() {
        if (!isset($this->_field))
            $this->_field = new FormField($this->ht);
        return $this->_field;
    }

    function getAnswer() { return $this->answer; }

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
     * errors - (OUT array) receives validation errors of the parsed
     *      configuration form
     *
     * Returns:
     * (bool) true if the configuration was updated, false if there were
     * errors. If false, the errors were written into the received errors
     * array.
     */
    function setConfiguration(&$errors=array()) {
        $config = array();
        foreach ($this->getConfigurationForm() as $name=>$field) {
            $config[$name] = $field->getClean();
            $errors = array_merge($errors, $field->errors());
        }
        if (count($errors) === 0)
            $this->set('configuration', JsonDataEncoder::encode($config));
        $this->set('hint', $_POST['hint']);
        return count($errors) === 0;
    }

    function isDeletable() {
        return ($this->get('edit_mask') & 1) == 0;
    }
    function isNameForced() {
        return $this->get('edit_mask') & 2;
    }
    function isPrivacyForced() {
        return $this->get('edit_mask') & 4;
    }
    function isRequirementForced() {
        return $this->get('edit_mask') & 8;
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
                "Label is required for custom form fields", "label");
        if ($this->get('required') && !$this->get('name'))
            $this->addError(
                "Variable name is required for required fields", "name");
        return count($this->errors()) == 0;
    }

    function delete() {
        // Don't really delete form fields as that will screw up the data
        // model. Instead, just drop the association with the form which
        // will give the appearance of deletion. Not deleting means that
        // the field will continue to exist on form entries it may already
        // have answers on, but since it isn't associated with the form, it
        // won't be available for new form submittals.
        $this->set('form_id', 0);
        $this->save();
    }

    function save() {
        if (count($this->dirty))
            $this->set('updated', new SqlFunction('NOW'));
        return parent::save();
    }

    static function create($ht=false) {
        $inst = parent::create($ht);
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
        'joins' => array(
            'form' => array(
                'null' => true,
                'constraint' => array('form_id' => 'DynamicForm.id'),
            ),
        ),
    );

    var $_values;
    var $_fields;
    var $_form;
    var $_errors = false;
    var $_clean = false;

    function getAnswers() {
        if (!isset($this->_values)) {
            $this->_values = DynamicFormEntryAnswer::objects()
                ->filter(array('entry_id'=>$this->get('id')))
                ->all();
            foreach ($this->_values as $v)
                $v->entry = $this;
        }
        return $this->_values;
    }

    function getAnswer($name) {
        foreach ($this->getAnswers() as $ans)
            if ($ans->getField()->get('name') == $name)
                return $ans;
        return null;
    }
    function setAnswer($name, $value, $id=false) {
        foreach ($this->getAnswers() as $ans) {
            if ($ans->getField()->get('name') == $name) {
                $ans->getField()->reset();
                $ans->set('value', $value);
                if ($id !== false)
                    $ans->set('value_id', $id);
                break;
            }
        }
    }

    function errors() {
        return $this->_errors;
    }

    function getTitle() { return $this->getForm()->getTitle(); }
    function getInstructions() { return $this->getForm()->getInstructions(); }

    function getForm() {
        if (!isset($this->_form)) {
            $this->_form = DynamicForm::lookup($this->get('form_id'));
            if ($this->id)
                $this->_form->data($this);
        }
        return $this->_form;
    }

    function getFields() {
        if (!isset($this->_fields)) {
            $this->_fields = array();
            foreach ($this->getAnswers() as $a)
                $this->_fields[] = $a->getField();
        }
        return $this->_fields;
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
     */
    function isValid($include=false) {
        if (!is_array($this->_errors)) {
            $this->_errors = array();
            $this->getClean();
            foreach ($this->getFields() as $field)
                if ($field->errors() && (!$include || $include($field)))
                    $this->_errors[$field->get('id')] = $field->errors();
        }
        return !$this->_errors;
    }

    function getClean() {
        if (!$this->_clean) {
            $this->_clean = array();
            foreach ($this->getFields() as $field)
                $this->_clean[$field->get('id')]
                    = $this->_clean[$field->get('name')] = $field->getClean();
        }
        return $this->_clean;
    }

    function forTicket($ticket_id, $force=false) {
        static $entries = array();
        if (!isset($entries[$ticket_id]) || $force)
            $entries[$ticket_id] = DynamicFormEntry::objects()
                ->filter(array('object_id'=>$ticket_id, 'object_type'=>'T'));
        return $entries[$ticket_id];
    }
    function setTicketId($ticket_id) {
        $this->object_type = 'T';
        $this->object_id = $ticket_id;
    }

    function forClient($user_id) {
        return DynamicFormEntry::objects()
            ->filter(array('object_id'=>$user_id, 'object_type'=>'U'));
    }
    function setClientId($user_id) {
        $this->object_type = 'U';
        $this->object_id = $user_id;
    }

    function render($staff=true, $title=false) {
        return $this->getForm()->render($staff, $title);
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
        foreach ($this->getForm()->getDynamicFields() as $field) {
            $found = false;
            foreach ($this->getAnswers() as $answer) {
                if ($answer->get('field_id') == $field->get('id')) {
                    $found = true; break;
                }
            }
            if (!$found && ($field = $field->getImpl($field))
                    && !$field->isPresentationOnly()) {
                $a = DynamicFormEntryAnswer::create(
                    array('field_id'=>$field->get('id'), 'entry_id'=>$this->id));
                $a->field = $field;
                $a->entry = $this;
                // Add to list of answers
                $this->_values[] = $a;
                $this->_fields[] = $field;
                // Omit fields without data
                // For user entries, the name and email fields should not be
                // saved with the rest of the data
                if (!($this->object_type == 'U'
                        && in_array($field->get('name'), array('name','email')))
                        && $field->hasData())
                    $a->save();
                $this->_form = null;
            }
            // Sort the form the way it is declared to be sorted
            if ($this->_fields)
                usort($this->_fields,
                    function($a, $b) {
                        return $a->get('sort') - $b->get('sort');
                });
        }
    }

    function save() {
        if (count($this->dirty))
            $this->set('updated', new SqlFunction('NOW'));
        parent::save();
        foreach ($this->getAnswers() as $a) {
            $field = $a->getField();
            if ($this->object_type == 'U'
                    && in_array($field->get('name'), array('name','email')))
                continue;
            $val = $field->to_database($field->getClean());
            if (is_array($val)) {
                $a->set('value', $val[0]);
                $a->set('value_id', $val[1]);
            }
            else
                $a->set('value', $val);
            $a->set('entry_id', $this->get('id'));
            // Don't save answers for presentation-only fields
            if ($field->hasData() && !$field->isPresentationOnly())
                $a->save();
        }
        $this->_values = null;
    }

    function delete() {
        foreach ($this->getAnswers() as $a)
            $a->delete();
        return parent::delete();
    }

    static function create($ht=false) {
        $inst = parent::create($ht);
        $inst->set('created', new SqlFunction('NOW'));
        foreach ($inst->getForm()->getFields() as $f) {
            if (!$f->hasData()) continue;
            $a = DynamicFormEntryAnswer::create(
                array('field_id'=>$f->get('id')));
            $a->field = $f;
            $a->field->setAnswer($a);
            $a->entry = $inst;
            $inst->_values[] = $a;
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
        'joins' => array(
            'field' => array(
                'constraint' => array('field_id' => 'DynamicFormField.id'),
            ),
            'entry' => array(
                'constraint' => array('entry_id' => 'DynamicFormEntry.id'),
            ),
        ),
    );

    var $field;
    var $form;
    var $entry;
    var $_value;

    function getEntry() {
        return $this->entry;
    }

    function getForm() {
        if (!$this->form)
            $this->form = $this->getEntry()->getForm();
        return $this->form;
    }

    function getField() {
        if (!isset($this->field)) {
            $f = DynamicFormField::lookup($this->get('field_id'));
            $this->field = $f->getImpl($f);
            $this->field->setAnswer($this);
        }
        return $this->field;
    }

    function getValue() {
        if (!$this->_value)
            $this->_value = $this->getField()->to_php(
                $this->get('value'), $this->get('value_id'));
        return $this->_value;
    }

    function getIdValue() {
        return $this->get('value_id');
    }

    function toString() {
        return $this->getField()->toString($this->getValue());
    }

    function display() {
        return $this->getField()->display($this->getValue());
    }

    function asVar() {
        return $this->toString();
    }

    function __toString() {
        $v = $this->toString();
        return is_string($v) ? $v : (string) $this->getValue();
    }
}

/**
 * Dynamic lists are used to represent list of arbitrary data that can be
 * used as dropdown or typeahead selections in dynamic forms. This model
 * defines a list. The individual items are stored in the DynamicListItem
 * model.
 */
class DynamicList extends VerySimpleModel {

    static $meta = array(
        'table' => LIST_TABLE,
        'ordering' => array('name'),
        'pk' => array('id'),
    );

    var $_items;

    function getSortModes() {
        return array(
            'Alpha'     => 'Alphabetical',
            '-Alpha'    => 'Alphabetical (Reversed)',
            'SortCol'   => 'Manually Sorted'
        );
    }

    function getListOrderBy() {
        switch ($this->sort_mode) {
            case 'Alpha':   return 'value';
            case '-Alpha':  return '-value';
            case 'SortCol': return 'sort';
        }
    }

    function getPluralName() {
        if ($name = $this->get('name_plural'))
            return $name;
        else
            return $this->get('name') . 's';
    }

    function getItems($limit=false, $offset=false) {
        if (!$this->_items) {
            $this->_items = DynamicListItem::objects()->filter(
                    array('list_id'=>$this->get('id')))
                ->order_by($this->getListOrderBy());
            if ($limit)
                $this->_items->limit($limit);
            if ($offset)
                $this->_items->offset($offset);
        }
        return $this->_items;
    }

    function getItemCount() {
        return DynamicListItem::objects()->filter(array('list_id'=>$this->id))
            ->count();
    }

    function save($refetch=false) {
        if (count($this->dirty))
            $this->set('updated', new SqlFunction('NOW'));
        if (isset($this->dirty['notes']))
            $this->notes = Format::sanitize($this->notes);
        return parent::save($refetch);
    }

    function delete() {
        $fields = DynamicFormField::objects()->filter(array(
            'type'=>'list-'.$this->id))->count();
        if ($fields == 0)
            return parent::delete();
        else
            // Refuse to delete lists that are in use by fields
            return false;
    }

    static function create($ht=false) {
        $inst = parent::create($ht);
        $inst->set('created', new SqlFunction('NOW'));
        return $inst;
    }

    static function getSelections() {
        $selections = array();
        foreach (DynamicList::objects() as $list) {
            $selections['list-'.$list->id] =
                array($list->getPluralName(),
                    SelectionField, $list->get('id'));
        }
        return $selections;
    }
}
FormField::addFieldTypes('Custom Lists', array('DynamicList', 'getSelections'));

/**
 * Represents a single item in a dynamic list
 *
 * Fields:
 * value - (char * 255) Actual list item content
 * extra - (char * 255) Other values that represent the same item in the
 *      list, such as an abbreviation. In practice, should be a
 *      space-separated list of tokens which should hit this list item in a
 *      search
 * sort - (int) If sorting by this field, represents the numeric sort order
 *      that this item should come in the dropdown list
 */
class DynamicListItem extends VerySimpleModel {

    static $meta = array(
        'table' => LIST_ITEM_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'list' => array(
                'null' => true,
                'constraint' => array('list_id' => 'DynamicList.id'),
            ),
        ),
    );

    function toString() {
        return $this->get('value');
    }

    function delete() {
        # Don't really delete, just unset the list_id to un-associate it with
        # the list
        $this->set('list_id', null);
        return $this->save();
    }
}

class SelectionField extends FormField {
    static $widget = 'SelectionWidget';

    function getListId() {
        list(,$list_id) = explode('-', $this->get('type'));
        return $list_id;
    }

    function getList() {
        if (!$this->_list)
            $this->_list = DynamicList::lookup($this->getListId());
        return $this->_list;
    }

    function parse($value) {
        $config = $this->getConfiguration();
        if (is_int($value))
            return $this->to_php($this->getWidget()->getEnteredValue(), (int) $value);
        elseif (!$config['typeahead'])
            return $this->to_php(null, (int) $value);
        else
            return $this->to_php($value);
    }

    function to_php($value, $id=false) {
        if ($id && is_int($id))
            $item = DynamicListItem::lookup($id);
        # Attempt item lookup by name too
        if (!$item || ($value !== null && $value != $item->get('value'))) {
            $item = DynamicListItem::lookup(array(
                'value'=>$value,
                'list_id'=>$this->getListId()));
        }
        return ($item) ? $item : $value;
    }

    function hasIdValue() {
        return true;
    }

    function to_database($item) {
        if ($item instanceof DynamicListItem)
            return array($item->value, $item->id);
        return null;
    }

    function toString($item) {
        return ($item instanceof DynamicListItem)
            ? $item->toString() : (string) $item;
    }

    function validateEntry($item) {
        $config = $this->getConfiguration();
        parent::validateEntry($item);
        if ($item && !$item instanceof DynamicListItem)
            $this->_errors[] = 'Select a value from the list';
        elseif ($item && $config['typeahead']
                && $this->getWidget()->getEnteredValue() != $item->get('value'))
            $this->_errors[] = 'Select a value from the list';
    }

    function getConfigurationOptions() {
        return array(
            'typeahead' => new ChoiceField(array(
                'id'=>1, 'label'=>'Widget', 'required'=>false,
                'default'=>false,
                'choices'=>array(false=>'Drop Down', true=>'Typeahead'),
                'hint'=>'Typeahead will work better for large lists'
            )),
            'prompt' => new TextboxField(array(
                'id'=>2, 'label'=>'Prompt', 'required'=>false, 'default'=>'',
                'hint'=>'Leading text shown before a value is selected',
                'configuration'=>array('size'=>40, 'length'=>40),
            )),
        );
    }

    function getChoices() {
        if (!$this->_choices) {
            $this->_choices = array();
            foreach ($this->getList()->getItems() as $i)
                $this->_choices[$i->get('id')] = $i->get('value');
        }
        return $this->_choices;
    }

    function export($value) {
        if ($value && is_numeric($value)
                && ($item = DynamicListItem::lookup($value)))
            return $item->toString();
        return $value;
    }
}

class SelectionWidget extends ChoicesWidget {
    function render($mode=false) {
        $config = $this->field->getConfiguration();
        $value = false;
        if ($this->value instanceof DynamicListItem) {
            // Loaded from database
            $value = $this->value->get('id');
            $name = $this->value->get('value');
        } elseif ($this->value) {
            // Loaded from POST
            $value = $this->value;
            $name = $this->getEnteredValue();
        }
        if (!$config['typeahead'] || $mode=='search') {
            $this->value = $value;
            return parent::render($mode);
        }

        $source = array();
        foreach ($this->field->getList()->getItems() as $i)
            $source[] = array(
                'value' => $i->get('value'), 'id' => $i->get('id'),
                'info' => $i->get('value')." -- ".$i->get('extra'),
            );
        ?>
        <span style="display:inline-block">
        <input type="text" size="30" name="<?php echo $this->name; ?>"
            id="<?php echo $this->name; ?>" value="<?php echo $name; ?>"
            placeholder="<?php echo $config['prompt'];
            ?>" autocomplete="off" />
        <input type="hidden" name="<?php echo $this->name;
            ?>_id" id="<?php echo $this->name; ?>_id" value="<?php echo $value; ?>"/>
        <script type="text/javascript">
        $(function() {
            $('input#<?php echo $this->name; ?>').typeahead({
                source: <?php echo JsonDataEncoder::encode($source); ?>,
                property: 'info',
                onselect: function(item) {
                    $('input#<?php echo $this->name; ?>').val(item['value'])
                    $('input#<?php echo $this->name; ?>_id').val(item['id'])
                }
            });
        });
        </script>
        </span>
        <?php
    }

    function getValue() {
        $data = $this->field->getSource();
        // Search for HTML form name first
        if (isset($data[$this->name.'_id']))
            return (int) $data[$this->name.'_id'];
        return parent::getValue();
    }

    function getEnteredValue() {
        // Used to verify typeahead fields
        return parent::getValue();
    }
}
?>
