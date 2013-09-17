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

    var $_fields;
    var $_dfields;

    function getFields() {
        if (!isset($this->_fields)) {
            $this->_fields = array();
            foreach ($this->getDynamicFields() as $f)
                $this->_fields[] = $f->getImpl();
        }
        return $this->_fields;
    }

    function getDynamicFields() {
        if (!isset($this->_dfields))
            $this->_dfields = DynamicFormField::objects()
                ->filter(array('form_id'=>$this->id))
                ->all();
        return $this->_dfields;
    }

    function hasField($name) {
        foreach ($this->getDynamicFields() as $f)
            if ($f->get('name') == $name)
                return true;
    }

    function getTitle() { return $this->get('title'); }
    function getInstructions() { return $this->get('instructions'); }

    function getForm() {
        $fields = $this->getFields();
        foreach ($fields as &$f)
            $f = $f->getField();
        return new Form($fields, $this->title, $this->instructions);
    }

    function instanciate($sort=1) {
        return DynamicFormEntry::create(array(
            'form_id'=>$this->get('id'), 'sort'=>$sort));
    }

    function save() {
        if (count($this->dirty))
            $this->set('updated', new SqlFunction('NOW'));
        return parent::save();
    }

    static function create($ht=false) {
        $inst = parent::create($ht);
        $inst->set('created', new SqlFunction('NOW'));
        if (isset($ht['fields'])) {
            $inst->save();
            foreach ($ht['fields'] as $f) {
                $f = DynamicFormField::create($f);
                $f->form_id = $inst->id;
                $f->save();
            }
        }
        return $inst;
    }
}

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
    function setConfiguration($errors) {
        $errors = $config = array();
        foreach ($this->getConfigurationForm() as $name=>$field) {
            $config[$name] = $field->getClean();
            $errors = array_merge($errors, $field->errors());
        }
        if (count($errors) === 0)
            $this->set('configuration', JsonDataEncoder::encode($config));
        $this->set('hint', $_POST['hint']);
        return count($errors) === 0;
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
                return $ans->getValue();
        return null;
    }

    function errors() {
        return $this->_errors;
    }

    function getTitle() { return $this->getForm()->getTitle(); }
    function getInstructions() { return $this->getForm()->getInstructions(); }

    function getForm() {
        if (!$this->_form)
            $this->_form = DynamicForm::lookup($this->get('form_id'));
        return $this->_form;
    }

    function getFields() {
        if (!$this->_fields) {
            $this->_fields = array();
            foreach ($this->getAnswers() as $a)
                $this->_fields[] = $a->getField();
        }
        return $this->_fields;
    }

    function isValid() {
        if (!is_array($this->_errors)) {
            $this->_errors = array();
            $this->getClean();
            foreach ($this->getFields() as $field)
                if ($field->errors())
                    $this->_errors[$field->get('id')] = $field->errors();
        }
        return !$this->_errors;
    }

    function getClean() {
        if (!$this->_clean) {
            $this->_clean = array();
            foreach ($this->getFields() as $field)
                $this->_clean[$field->get('id')] = $field->getClean();
        }
        return $this->_clean;
    }

    function forTicket($ticket_id) {
        static $entries = array();
        if (!isset($entries[$ticket_id]))
            $entries[$ticket_id] = DynamicFormEntry::objects()
                ->filter(array('ticket_id'=>$ticket_id));
        return $entries[$ticket_id];
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
        foreach ($this->getForm()->getFields() as $field) {
            $found = false;
            foreach ($this->getAnswers() as $answer) {
                if ($answer->get('field_id') == $field->get('id')) {
                    $found = true; break;
                }
            }
            if (!$found) {
                $a = DynamicFormEntryAnswer::create(
                    array('field_id'=>$field->get('id'), 'entry_id'=>$this->id));
                $a->field = $field;
                // Add to list of answers
                $this->_values[] = $a;
                $a->save();
            }
        }
    }

    function save() {
        if (count($this->dirty))
            $this->set('updated', new SqlFunction('NOW'));
        parent::save();
        foreach ($this->getAnswers() as $a) {
            $a->set('value', $a->getField()->to_database($a->getField()->getClean()));
            $a->set('entry_id', $this->get('id'));
            $a->save();
        }
        $this->_values = array();
    }

    static function create($ht=false) {
        $inst = parent::create($ht);
        $inst->set('created', new SqlFunction('NOW'));
        foreach ($inst->getForm()->getFields() as $f) {
            $a = DynamicFormEntryAnswer::create(
                array('field_id'=>$f->get('id')));
            $a->field = $f;
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
            $this->field = DynamicFormField::lookup($this->get('field_id'))->getImpl();
            $this->field->answer = $this;
        }
        return $this->field;
    }

    function getValue() {
        if (!$this->_value)
            $this->_value = $this->getField()->to_php($this->get('value'));
        return $this->_value;
    }

    function toString() {
        return $this->getField()->toString($this->getValue());
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
        if ($name = $this->get('plural_name'))
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
        return parent::save($refetch);
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
                array('Selection: ' .  $list->getPluralName(),
                    SelectionField, $list->get('id'));
        }
        return $selections;
    }
}
FormField::addFieldTypes(array(DynamicList, 'getSelections'));

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
    function getList() {
        if (!$this->_list) {
            $list_id = explode('-', $this->get('type'));
            $list_id = $list_id[1];
            $this->_list = DynamicList::lookup($list_id);
        }
        return $this->_list;
    }

    function getWidget() {
        return new SelectionWidget($this);
    }

    function parse($id) {
        return $this->to_php($id);
    }

    function to_php($id) {
        if (!$id)
            return null;
        list($id, $value) = explode(':', $id);
        $item = DynamicListItem::lookup($id);
        # Attempt item lookup by name too
        if (!$item) {
            $item = DynamicListItem::objects()->filter(array(
                        'value'=>$id,
                        'list_id'=>$this->getList()->get('id')));
            $item = (count($item)) ? $item[0] : null;
        }
        return $item;
    }

    function to_database($item) {
        if ($item && $item->get('id'))
            return $item->id . ':' . $item->value;
        return null;
    }

    function toString($item) {
        return ($item) ? $item->toString() : '';
    }

    function getConfigurationOptions() {
        return array(
            'typeahead' => new ChoiceField(array(
                'id'=>1, 'label'=>'Widget', 'required'=>false,
                'default'=>false,
                'choices'=>array(false=>'Drop Down', true=>'Typeahead'),
                'hint'=>'Typeahead will work better for large lists')),
        );
    }
}

class SelectionWidget extends ChoicesWidget {
    function render() {
        $config = $this->field->getConfiguration();
        $value = false;
        if (is_object($this->value) && get_class($this->value) == 'DynamicListItem') {
            // Loaded from database
            $value = $this->value->get('id');
            $name = $this->value->get('value');
        } elseif ($this->value) {
            // Loaded from POST
            $value = $this->value;
            $name = DynamicListItem::lookup($this->value);
            $name = ($name) ? $name->get('value') : null;
        }

        if (!$config['typeahead']) {
            $this->value = $value;
            return parent::render();
        }

        $source = array();
        foreach ($this->field->getList()->getItems() as $i)
            $source[] = array(
                'info' => $i->get('value'),
                'value' => strtolower($i->get('value').' '.$i->get('extra')),
                'id' => $i->get('id'));
        ?>
        <span style="display:inline-block">
        <input type="hidden" name="<?php echo $this->name; ?>"
            value="<?php echo $value; ?>" />
        <input type="text" size="30" id="<?php echo $this->name; ?>"
            value="<?php echo $name; ?>" />
        <script type="text/javascript">
        $(function() {
            $('#<?php echo $this->name; ?>').typeahead({
                source: <?php echo JsonDataEncoder::encode($source); ?>,
                onselect: function(item) {
                    $('#<?php echo $this->name; ?>').val(item['info'])
                    $('input[name="<?php echo $this->name; ?>"]').val(item['id'])
                }
            });
        });
        </script>
        </span>
        <?php
    }

    function getChoices() {
        if (!$this->_choices) {
            $this->_choices = array();
            foreach ($this->field->getList()->getItems() as $i)
                $this->_choices[$i->get('id')] = $i->get('value');
        }
        return $this->_choices;
    }
}

?>
