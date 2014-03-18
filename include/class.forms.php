<?php
/*********************************************************************
    class.forms.php

    osTicket forms framework

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

/**
 * Form template, used for designing the custom form and for entering custom
 * data for a ticket
 */
class Form {
    var $fields = array();
    var $title = 'Unnamed';
    var $instructions = '';

    var $_errors = null;
    var $_source = false;

    function Form() {
        call_user_func_array(array($this, '__construct'), func_get_args());
    }
    function __construct($fields=array(), $source=null, $options=array()) {
        $this->fields = $fields;
        foreach ($fields as $f)
            $f->setForm($this);
        if (isset($options['title']))
            $this->title = $options['title'];
        if (isset($options['instructions']))
            $this->instructions = $options['instructions'];
        // Use POST data if source was not specified
        $this->_source = ($source) ? $source : $_POST;
    }
    function data($source) {
        foreach ($this->fields as $name=>$f)
            if (isset($source[$name]))
                $f->value = $source[$name];
    }

    function getFields() {
        return $this->fields;
    }

    function getField($name) {
        $fields = $this->getFields();
        foreach($fields as $f)
            if(!strcasecmp($f->get('name'), $name))
                return $f;
        if (isset($fields[$name]))
            return $fields[$name];
    }

    function getTitle() { return $this->title; }
    function getInstructions() { return $this->instructions; }
    function getSource() { return $this->_source; }

    /**
     * Validate the form and indicate if there no errors.
     *
     * Parameters:
     * $filter - (callback) function to receive each field and return
     *      boolean true if the field's errors are significant
     */
    function isValid($include=false) {
        if (!isset($this->_errors)) {
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
            foreach ($this->getFields() as $key=>$field) {
                if (!$field->hasData())
                    continue;
                $this->_clean[$key] = $this->_clean[$field->get('name')]
                    = $field->getClean();
            }
            unset($this->_clean[""]);
        }
        return $this->_clean;
    }

    function errors() {
        return $this->_errors;
    }

    function render($staff=true, $title=false, $instructions=false) {
        if ($title)
            $this->title = $title;
        if ($instructions)
            $this->instructions = $instructions;
        $form = $this;
        if ($staff)
            include(STAFFINC_DIR . 'templates/dynamic-form.tmpl.php');
        else
            include(CLIENTINC_DIR . 'templates/dynamic-form.tmpl.php');
    }
}

require_once(INCLUDE_DIR . "class.json.php");

class FormField {
    static $widget = false;

    var $ht = array(
        'label' => 'Unlabeled',
        'required' => false,
        'default' => false,
        'configuration' => array(),
    );

    var $_form;
    var $_cform;
    var $_clean;
    var $_errors = array();
    var $_widget;
    var $answer;
    var $parent;
    var $presentation_only = false;

    static $types = array(
        'Basic Fields' => array(
            'text'  => array('Short Answer', 'TextboxField'),
            'memo' => array('Long Answer', 'TextareaField'),
            'thread' => array('Thread Entry', 'ThreadEntryField', false),
            'datetime' => array('Date and Time', 'DatetimeField'),
            'phone' => array('Phone Number', 'PhoneField'),
            'bool' => array('Checkbox', 'BooleanField'),
            'choices' => array('Choices', 'ChoiceField'),
            'break' => array('Section Break', 'SectionBreakField'),
        ),
    );
    static $more_types = array();

    function __construct($options=array()) {
        static $uid = 100;
        $this->ht = array_merge($this->ht, $options);
        if (!isset($this->ht['id']))
            $this->ht['id'] = $uid++;
    }

    static function addFieldTypes($group, $callable) {
        static::$more_types[$group] = $callable;
    }

    static function allTypes() {
        if (static::$more_types) {
            foreach (static::$more_types as $group=>$c)
                static::$types[$group] = call_user_func($c);
            static::$more_types = array();
        }
        return static::$types;
    }

    static function getFieldType($type) {
        foreach (static::allTypes() as $group=>$types)
            if (isset($types[$type]))
                return $types[$type];
    }

    function get($what) {
        return $this->ht[$what];
    }

    /**
     * getClean
     *
     * Validates and cleans inputs from POST request. This is performed on a
     * field instance, after a DynamicFormSet / DynamicFormSection is
     * submitted via POST, in order to kick off parsing and validation of
     * user-entered data.
     */
    function getClean() {
        if (!isset($this->_clean)) {
            $this->_clean = (isset($this->value))
                ? $this->value : $this->parse($this->getWidget()->value);
            $this->validateEntry($this->_clean);
        }
        return $this->_clean;
    }
    function reset() {
        $this->_clean = $this->_widget = null;
    }

    function errors() {
        return $this->_errors;
    }
    function addError($message, $field=false) {
        if ($field)
            $this->_errors[$field] = $message;
        else
            $this->_errors[] = $message;
    }

    function isValidEntry() {
        $this->validateEntry();
        return count($this->_errors) == 0;
    }

    /**
     * validateEntry
     *
     * Validates user entry on an instance of the field on a dynamic form.
     * This is called when an instance of this field (like a TextboxField)
     * receives data from the user and that value should be validated.
     *
     * Parameters:
     * $value - (string) input from the user
     */
    function validateEntry($value) {
        if (!$value && count($this->_errors))
            return;

        # Validates a user-input into an instance of this field on a dynamic
        # form
        if ($this->get('required') && !$value && $this->hasData())
            $this->_errors[] = sprintf('%s is a required field', $this->getLabel());

        # Perform declared validators for the field
        if ($vs = $this->get('validators')) {
            if (is_array($vs)) {
                foreach ($vs as $validator)
                    if (is_callable($validator))
                        $validator($this, $value);
            }
            elseif (is_callable($vs))
                $vs($this, $value);
        }
    }

    /**
     * parse
     *
     * Used to transform user-submitted data to a PHP value. This value is
     * not yet considered valid. The ::validateEntry() method will be called
     * on the value to determine if the entry is valid. Therefore, if the
     * data is clearly invalid, return something like NULL that can easily
     * be deemed invalid in ::validateEntry(), however, can still produce a
     * useful error message indicating what is wrong with the input.
     */
    function parse($value) {
        return trim($value);
    }

    /**
     * to_php
     *
     * Transforms the data from the value stored in the database to a PHP
     * value. The ::to_database() method is used to produce the database
     * valse, so this method is the compliment to ::to_database().
     *
     * Parameters:
     * $value - (string or null) database representation of the field's
     *      content
     */
    function to_php($value) {
        return $value;
    }

    /**
     * to_database
     *
     * Determines the value to be stored in the database. The database
     * backend for all fields is a text field, so this method should return
     * a text value or NULL to represent the value of the field. The
     * ::to_php() method will convert this value back to PHP.
     *
     * Paremeters:
     * $value - PHP value of the field's content
     */
    function to_database($value) {
        return $value;
    }

    /**
     * toString
     *
     * Converts the PHP value created in ::parse() or ::to_php() to a
     * pretty-printed value to show to the user. This is especially useful
     * for something like dates which are stored considerably different in
     * the database from their respective human-friendly versions.
     * Furthermore, this method allows for internationalization and
     * localization.
     *
     * Parametes:
     * $value - PHP value of the field's content
     */
    function toString($value) {
        return (string) $value;
    }

    /**
     * Returns an HTML friendly value for the data in the field.
     */
    function display($value) {
        return Format::htmlchars($this->toString($value));
    }

    /**
     * Returns a value suitable for exporting to a foreign system. Mostly
     * useful for things like dates and phone numbers which should be
     * formatted using a standard when exported
     */
    function export($value) {
        return $this->toString($value);
    }

    function getLabel() { return $this->get('label'); }

    /**
     * getImpl
     *
     * Magic method that will return an implementation instance of this
     * field based on the simple text value of the 'type' value of this
     * field instance. The list of registered fields is determined by the
     * global get_dynamic_field_types() function. The data from this model
     * will be used to initialize the returned instance.
     *
     * For instance, if the value of this field is 'text', a TextField
     * instance will be returned.
     */
    function getImpl($parent=null) {
        // Allow registration with ::addFieldTypes and delayed calling
        $type = static::getFieldType($this->get('type'));
        $clazz = $type[1];
        $inst = new $clazz($this->ht);
        $inst->parent = $parent;
        $inst->setForm($this->_form);
        return $inst;
    }

    function __call($what, $args) {
        // XXX: Throw exception if $this->parent is not set
        if (!$this->parent)
            throw new Exception($what.': Call to undefined function');
        // BEWARE: DynamicFormField has a __call() which will create a new
        //      FormField instance and invoke __call() on it or bounce
        //      immediately back
        return call_user_func_array(
            array($this->parent, $what), $args);
    }

    function getAnswer() { return $this->answer; }
    function setAnswer($ans) { $this->answer = $ans; }

    function getFormName() {
        if (is_numeric($this->get('id')))
            return substr(md5(
                session_id() . '-field-id-'.$this->get('id')), -16);
        else
            return $this->get('id');
    }

    function setForm($form) {
        $this->_form = $form;
    }
    function getForm() {
        return $this->_form;
    }
    /**
     * Returns the data source for this field. If created from a form, the
     * data source from the form is returned. Otherwise, if the request is a
     * POST, then _POST is returned.
     */
    function getSource() {
        if ($this->_form)
            return $this->_form->getSource();
        elseif ($_SERVER['REQUEST_METHOD'] == 'POST')
            return $_POST;
        else
            return array();
    }

    function render($mode=null) {
        $this->getWidget()->render($mode);
    }

    function renderExtras($mode=null) {
        return;
    }

    function getConfigurationOptions() {
        return array();
    }

    /**
     * getConfiguration
     *
     * Loads configuration information from database into hashtable format.
     * Also, the defaults from ::getConfigurationOptions() are integrated
     * into the database-backed options, so that if options have not yet
     * been set or a new option has been added and not saved for this field,
     * the default value will be reflected in the returned configuration.
     */
    function getConfiguration() {
        if (!$this->_config) {
            $this->_config = $this->get('configuration');
            if (is_string($this->_config))
                $this->_config = JsonDataParser::parse($this->_config);
            elseif (!$this->_config)
                $this->_config = array();
            foreach ($this->getConfigurationOptions() as $name=>$field)
                if (!isset($this->_config[$name]))
                    $this->_config[$name] = $field->get('default');
        }
        return $this->_config;
    }

    /**
     * If the [Config] button should be shown to allow for the configuration
     * of this field
     */
    function isConfigurable() {
        return true;
    }

    /**
     * Field type is changeable in the admin interface
     */
    function isChangeable() {
        return true;
    }

    /**
     * Field does not contain data that should be saved to the database. Ie.
     * non data fields like section headers
     */
    function hasData() {
        return true;
    }

    /**
     * Returns true if the field/widget should be rendered as an entire
     * block in the target form.
     */
    function isBlockLevel() {
        return false;
    }

    /**
     * Fields should not be saved with the dynamic data. It is assumed that
     * some static processing will store the data elsewhere.
     */
    function isPresentationOnly() {
        return $this->presentation_only;
    }

    /**
     * Indicates if the field places data in the `value_id` column. This
     * is currently used by the materialized view system
     */
    function hasIdValue() {
        return false;
    }

    function getConfigurationForm() {
        if (!$this->_cform) {
            $type = static::getFieldType($this->get('type'));
            $clazz = $type[1];
            $T = new $clazz();
            $this->_cform = $T->getConfigurationOptions();
        }
        return $this->_cform;
    }

    function getWidget() {
        if (!static::$widget)
            throw new Exception('Widget not defined for this field');
        if (!isset($this->_widget)) {
            $wc = $this->get('widget') ? $this->get('widget') : static::$widget;
            $this->_widget = new $wc($this);
            $this->_widget->parseValue();
        }
        return $this->_widget;
    }
}

class TextboxField extends FormField {
    static $widget = 'TextboxWidget';

    function getConfigurationOptions() {
        return array(
            'size'  =>  new TextboxField(array(
                'id'=>1, 'label'=>'Size', 'required'=>false, 'default'=>16,
                    'validator' => 'number')),
            'length' => new TextboxField(array(
                'id'=>2, 'label'=>'Max Length', 'required'=>false, 'default'=>30,
                    'validator' => 'number')),
            'validator' => new ChoiceField(array(
                'id'=>3, 'label'=>'Validator', 'required'=>false, 'default'=>'',
                'choices' => array('phone'=>'Phone Number','email'=>'Email Address',
                    'ip'=>'IP Address', 'number'=>'Number', ''=>'None'))),
            'validator-error' => new TextboxField(array(
                'id'=>4, 'label'=>'Validation Error', 'default'=>'',
                'configuration'=>array('size'=>40, 'length'=>60),
                'hint'=>'Message shown to user if the input does not match the validator')),
            'placeholder' => new TextboxField(array(
                'id'=>5, 'label'=>'Placeholder', 'required'=>false, 'default'=>'',
                'hint'=>'Text shown in before any input from the user',
                'configuration'=>array('size'=>40, 'length'=>40),
            )),
        );
    }

    function validateEntry($value) {
        parent::validateEntry($value);
        $config = $this->getConfiguration();
        $validators = array(
            '' =>       null,
            'email' =>  array(array('Validator', 'is_email'),
                'Enter a valid email address'),
            'phone' =>  array(array('Validator', 'is_phone'),
                'Enter a valid phone number'),
            'ip' =>     array(array('Validator', 'is_ip'),
                'Enter a valid IP address'),
            'number' => array('is_numeric', 'Enter a number')
        );
        // Support configuration forms, as well as GUI-based form fields
        $valid = $this->get('validator');
        if (!$valid) {
            $valid = $config['validator'];
        }
        if (!$value || !isset($validators[$valid]))
            return;
        $func = $validators[$valid];
        $error = $func[1];
        if ($config['validator-error'])
            $error = $config['validator-error'];
        if (is_array($func) && is_callable($func[0]))
            if (!call_user_func($func[0], $value))
                $this->_errors[] = $error;
    }
}

class PasswordField extends TextboxField {
    static $widget = 'PasswordWidget';

    function to_database($value) {
        return Crypto::encrypt($value, SECRET_SALT, $this->getFormName());
    }

    function to_php($value) {
        return Crypto::decrypt($value, SECRET_SALT, $this->getFormName());
    }
}

class TextareaField extends FormField {
    static $widget = 'TextareaWidget';

    function getConfigurationOptions() {
        return array(
            'cols'  =>  new TextboxField(array(
                'id'=>1, 'label'=>'Width (chars)', 'required'=>true, 'default'=>40)),
            'rows'  =>  new TextboxField(array(
                'id'=>2, 'label'=>'Height (rows)', 'required'=>false, 'default'=>4)),
            'length' => new TextboxField(array(
                'id'=>3, 'label'=>'Max Length', 'required'=>false, 'default'=>0)),
            'html' => new BooleanField(array(
                'id'=>4, 'label'=>'HTML', 'required'=>false, 'default'=>true,
                'configuration'=>array('desc'=>'Allow HTML input in this box'))),
            'placeholder' => new TextboxField(array(
                'id'=>5, 'label'=>'Placeholder', 'required'=>false, 'default'=>'',
                'hint'=>'Text shown in before any input from the user',
                'configuration'=>array('size'=>40, 'length'=>40),
            )),
        );
    }

    function display($value) {
        $config = $this->getConfiguration();
        if ($config['html'])
            return Format::safe_html($value);
        else
            return Format::htmlchars($value);
    }
}

class PhoneField extends FormField {
    static $widget = 'PhoneNumberWidget';

    function getConfigurationOptions() {
        return array(
            'ext' => new BooleanField(array(
                'label'=>'Extension', 'default'=>true,
                'configuration'=>array(
                    'desc'=>'Add a separate field for the extension',
                ),
            )),
            'digits' => new TextboxField(array(
                'label'=>'Minimum length', 'default'=>7,
                'hint'=>'Fewest digits allowed in a valid phone number',
                'configuration'=>array('validator'=>'number', 'size'=>5),
            )),
            'format' => new ChoiceField(array(
                'label'=>'Display format', 'default'=>'us',
                'choices'=>array(''=>'-- Unformatted --',
                    'us'=>'United States'),
            )),
        );
    }

    function validateEntry($value) {
        parent::validateEntry($value);
        $config = $this->getConfiguration();
        # Run validator against $this->value for email type
        list($phone, $ext) = explode("X", $value, 2);
        if ($phone && (
                !is_numeric($phone) ||
                strlen($phone) < $config['digits']))
            $this->_errors[] = "Enter a valid phone number";
        if ($ext && $config['ext']) {
            if (!is_numeric($ext))
                $this->_errors[] = "Enter a valid phone extension";
            elseif (!$phone)
                $this->_errors[] = "Enter a phone number for the extension";
        }
    }

    function parse($value) {
        // NOTE: Value may have a legitimate 'X' to separate the number and
        // extension parts. Don't remove the 'X'
        return preg_replace('/[^\dX]/', '', $value);
    }

    function toString($value) {
        $config = $this->getConfiguration();
        list($phone, $ext) = explode("X", $value, 2);
        switch ($config['format']) {
        case 'us':
            $phone = Format::phone($phone);
            break;
        }
        if ($ext)
            $phone.=" x$ext";
        return $phone;
    }
}

class BooleanField extends FormField {
    static $widget = 'CheckboxWidget';

    function getConfigurationOptions() {
        return array(
            'desc' => new TextareaField(array(
                'id'=>1, 'label'=>'Description', 'required'=>false, 'default'=>'',
                'hint'=>'Text shown inline with the widget',
                'configuration'=>array('rows'=>2)))
        );
    }

    function to_database($value) {
        return ($value) ? '1' : '0';
    }

    function parse($value) {
        return $this->to_php($value);
    }
    function to_php($value) {
        return $value ? true : false;
    }

    function toString($value) {
        return ($value) ? 'Yes' : 'No';
    }
}

class ChoiceField extends FormField {
    static $widget = 'ChoicesWidget';

    function getConfigurationOptions() {
        return array(
            'choices'  =>  new TextareaField(array(
                'id'=>1, 'label'=>'Choices', 'required'=>false, 'default'=>'',
                'hint'=>'List choices, one per line. To protect against
                spelling changes, specify key:value names to preserve
                entries if the list item names change',
                'configuration'=>array('html'=>false)
            )),
            'default' => new TextboxField(array(
                'id'=>3, 'label'=>'Default', 'required'=>false, 'default'=>'',
                'hint'=>'(Enter a key). Value selected from the list initially',
                'configuration'=>array('size'=>20, 'length'=>40),
            )),
            'prompt' => new TextboxField(array(
                'id'=>2, 'label'=>'Prompt', 'required'=>false, 'default'=>'',
                'hint'=>'Leading text shown before a value is selected',
                'configuration'=>array('size'=>40, 'length'=>40),
            )),
        );
    }

    function parse($value) {
        if (is_numeric($value))
            return $value;
        foreach ($this->getChoices() as $k=>$v)
            if (strcasecmp($value, $k) === 0)
                return $k;
    }

    function toString($value) {
        $choices = $this->getChoices();
        if (isset($choices[$value]))
            return $choices[$value];
        else
            return $choices[$this->get('default')];
    }

    function getChoices() {
        if ($this->_choices === null) {
            // Allow choices to be set in this->ht (for configurationOptions)
            $this->_choices = $this->get('choices');
            if (!$this->_choices) {
                $this->_choices = array();
                $config = $this->getConfiguration();
                $choices = explode("\n", $config['choices']);
                foreach ($choices as $choice) {
                    // Allow choices to be key: value
                    list($key, $val) = explode(':', $choice);
                    if ($val == null)
                        $val = $key;
                    $this->_choices[trim($key)] = trim($val);
                }
            }
        }
        return $this->_choices;
     }
}

class DatetimeField extends FormField {
    static $widget = 'DatetimePickerWidget';

    function to_database($value) {
        // Store time in gmt time, unix epoch format
        return (string) $value;
    }

    function to_php($value) {
        if (!$value)
            return $value;
        else
            return (int) $value;
    }

    function parse($value) {
        if (!$value) return null;
        $config = $this->getConfiguration();
        return ($config['gmt']) ? Misc::db2gmtime($value) : $value;
    }

    function toString($value) {
        global $cfg;
        $config = $this->getConfiguration();
        $format = ($config['time'])
            ? $cfg->getDateTimeFormat() : $cfg->getDateFormat();
        if ($config['gmt'])
            // Return time local to user's timezone
            return Format::userdate($format, $value);
        else
            return Format::date($format, $value);
    }

    function export($value) {
        $config = $this->getConfiguration();
        if (!$value)
            return '';
        elseif ($config['gmt'])
            return Format::userdate('Y-m-d H:i:s', $value);
        else
            return Format::date('Y-m-d H:i:s', $value);
    }

    function getConfigurationOptions() {
        return array(
            'time' => new BooleanField(array(
                'id'=>1, 'label'=>'Time', 'required'=>false, 'default'=>false,
                'configuration'=>array(
                    'desc'=>'Show time selection with date picker'))),
            'gmt' => new BooleanField(array(
                'id'=>2, 'label'=>'Timezone Aware', 'required'=>false,
                'configuration'=>array(
                    'desc'=>"Show date/time relative to user's timezone"))),
            'min' => new DatetimeField(array(
                'id'=>3, 'label'=>'Earliest', 'required'=>false,
                'hint'=>'Earliest date selectable')),
            'max' => new DatetimeField(array(
                'id'=>4, 'label'=>'Latest', 'required'=>false,
                'default'=>null)),
            'future' => new BooleanField(array(
                'id'=>5, 'label'=>'Allow Future Dates', 'required'=>false,
                'default'=>true, 'configuration'=>array(
                    'desc'=>'Allow entries into the future'))),
        );
    }

    function validateEntry($value) {
        $config = $this->getConfiguration();
        parent::validateEntry($value);
        if (!$value) return;
        if ($config['min'] and $value < $config['min'])
            $this->_errors[] = 'Selected date is earlier than permitted';
        elseif ($config['max'] and $value > $config['max'])
            $this->_errors[] = 'Selected date is later than permitted';
        // strtotime returns -1 on error for PHP < 5.1.0 and false thereafter
        elseif ($value === -1 or $value === false)
            $this->_errors[] = 'Enter a valid date';
    }
}

/**
 * This is kind-of a special field that doesn't have any data. It's used as
 * a field to provide a horizontal section break in the display of a form
 */
class SectionBreakField extends FormField {
    static $widget = 'SectionBreakWidget';

    function hasData() {
        return false;
    }

    function isBlockLevel() {
        return true;
    }
}

class ThreadEntryField extends FormField {
    static $widget = 'ThreadEntryWidget';

    function isChangeable() {
        return false;
    }
    function isBlockLevel() {
        return true;
    }
    function isPresentationOnly() {
        return true;
    }
    function renderExtras($mode=null) {
        if ($mode == 'client')
            // TODO: Pass errors arrar into showAttachments
            $this->getWidget()->showAttachments();
    }
}

class PriorityField extends ChoiceField {
    function getWidget() {
        $widget = parent::getWidget();
        if ($widget->value instanceof Priority)
            $widget->value = $widget->value->getId();
        return $widget;
    }

    function hasIdValue() {
        return true;
    }
    function isChangeable() {
        return $this->getForm()->get('type') != 'T' ||
            $this->get('name') != 'priority';
    }

    function getChoices() {
        global $cfg;
        $this->ht['default'] = $cfg->getDefaultPriorityId();

        $sql = 'SELECT priority_id, priority_desc FROM '.PRIORITY_TABLE
              .' ORDER BY priority_urgency DESC';
        $choices = array();
        if (!($res = db_query($sql)))
            return $choices;

        while ($row = db_fetch_row($res))
            $choices[$row[0]] = $row[1];
        return $choices;
    }

    function parse($id) {
        return $this->to_php(null, $id);
    }

    function to_php($value, $id) {
        return Priority::lookup($id);
    }

    function to_database($prio) {
        return ($prio instanceof Priority)
            ? array($prio->getDesc(), $prio->getId())
            : $prio;
    }

    function toString($value) {
        return ($value instanceof Priority) ? $value->getDesc() : $value;
    }

    function getConfigurationOptions() {
        return array(
            'prompt' => new TextboxField(array(
                'id'=>2, 'label'=>'Prompt', 'required'=>false, 'default'=>'',
                'hint'=>'Leading text shown before a value is selected',
                'configuration'=>array('size'=>40, 'length'=>40),
            )),
        );
    }
}
FormField::addFieldTypes('Built-in Lists', function() {
    return array(
        'priority' => array('Priority Level', PriorityField),
    );
});

class Widget {

    function __construct($field) {
        $this->field = $field;
        $this->name = $field->getFormName();
    }

    function parseValue() {
        $this->value = $this->getValue();
        if (!isset($this->value) && is_object($this->field->getAnswer()))
            $this->value = $this->field->getAnswer()->getValue();
        if (!isset($this->value) && $this->field->value)
            $this->value = $this->field->value;
    }

    function getValue() {
        $data = $this->field->getSource();
        // Search for HTML form name first
        if (isset($data[$this->name]))
            return $data[$this->name];
        elseif (isset($data[$this->field->get('name')]))
            return $data[$this->field->get('name')];
        return null;
    }
}

class TextboxWidget extends Widget {
    static $input_type = 'text';

    function render() {
        $config = $this->field->getConfiguration();
        if (isset($config['size']))
            $size = "size=\"{$config['size']}\"";
        if (isset($config['length']))
            $maxlength = "maxlength=\"{$config['length']}\"";
        if (isset($config['classes']))
            $classes = 'class="'.$config['classes'].'"';
        if (isset($config['autocomplete']))
            $autocomplete = 'autocomplete="'.($config['autocomplete']?'on':'off').'"';
        ?>
        <span style="display:inline-block">
        <input type="<?php echo static::$input_type; ?>"
            id="<?php echo $this->name; ?>"
            <?php echo $size . " " . $maxlength; ?>
            <?php echo $classes.' '.$autocomplete
                .' placeholder="'.$config['placeholder'].'"'; ?>
            name="<?php echo $this->name; ?>"
            value="<?php echo Format::htmlchars($this->value); ?>"/>
        </span>
        <?php
    }
}

class PasswordWidget extends TextboxWidget {
    static $input_type = 'password';

    function parseValue() {
        // Show empty box unless failed POST
        if ($_SERVER['REQUEST_METHOD'] == 'POST'
                && $this->field->getForm()->isValid())
            parent::parseValue();
        else
            $this->value = '';
    }
}

class TextareaWidget extends Widget {
    function render() {
        $config = $this->field->getConfiguration();
        $class = $cols = $rows = $maxlength = "";
        if (isset($config['rows']))
            $rows = "rows=\"{$config['rows']}\"";
        if (isset($config['cols']))
            $cols = "cols=\"{$config['cols']}\"";
        if (isset($config['length']) && $config['length'])
            $maxlength = "maxlength=\"{$config['length']}\"";
        if (isset($config['html']) && $config['html'])
            $class = 'class="richtext no-bar small"';
        ?>
        <span style="display:inline-block;width:100%">
        <textarea <?php echo $rows." ".$cols." ".$maxlength." ".$class
                .' placeholder="'.$config['placeholder'].'"'; ?>
            name="<?php echo $this->name; ?>"><?php
                echo Format::htmlchars($this->value);
            ?></textarea>
        </span>
        <?php
    }
}

class PhoneNumberWidget extends Widget {
    function render() {
        $config = $this->field->getConfiguration();
        list($phone, $ext) = explode("X", $this->value);
        ?>
        <input type="text" name="<?php echo $this->name; ?>" value="<?php
        echo $phone; ?>"/><?php
        // Allow display of extension field even if disabled if the phone
        // number being edited has an extension
        if ($ext || $config['ext']) { ?> Ext:
            <input type="text" name="<?php
            echo $this->name; ?>-ext" value="<?php echo $ext; ?>" size="5"/>
        <?php }
    }

    function getValue() {
        $data = $this->field->getSource();
        $base = parent::getValue();
        if ($base === null)
            return $base;
        $ext = $data["{$this->name}-ext"];
        // NOTE: 'X' is significant. Don't change it
        if ($ext) $ext = 'X'.$ext;
        return $base . $ext;
    }
}

class ChoicesWidget extends Widget {
    function render($mode=false) {
        $config = $this->field->getConfiguration();
        // Determine the value for the default (the one listed if nothing is
        // selected)
        $choices = $this->field->getChoices();
        // We don't consider the 'default' when rendering in 'search' mode
        $have_def = false;
        if ($mode != 'search') {
            $def_key = $this->field->get('default');
            if (!$def_key && $config['default'])
                $def_key = $config['default'];
            $have_def = isset($choices[$def_key]);
            if (!$have_def)
                $def_val = ($config['prompt'])
                   ? $config['prompt'] : 'Select';
            else
                $def_val = $choices[$def_key];
        } else {
            $def_val = ($config['prompt'])
                ? $config['prompt'] : 'Select';
        }
        $value = $this->value;
        if ($value === null && $have_def)
            $value = $def_key;
        ?> <span style="display:inline-block">
        <select name="<?php echo $this->name; ?>">
            <?php if (!$have_def) { ?>
            <option value="<?php echo $def_key; ?>">&mdash; <?php
                echo $def_val; ?> &mdash;</option>
            <?php }
            foreach ($choices as $key=>$name) {
                if (!$have_def && $key == $def_key)
                    continue; ?>
                <option value="<?php echo $key; ?>" <?php
                    if ($value == $key) echo 'selected="selected"';
                ?>><?php echo $name; ?></option>
            <?php } ?>
        </select>
        </span>
        <?php
    }
}

class CheckboxWidget extends Widget {
    function __construct($field) {
        parent::__construct($field);
        $this->name = '_field-checkboxes';
    }

    function render() {
        $config = $this->field->getConfiguration();
        ?>
        <input type="checkbox" name="<?php echo $this->name; ?>[]" <?php
            if ($this->value) echo 'checked="checked"'; ?> value="<?php
            echo $this->field->get('id'); ?>"/>
        <?php
        if ($config['desc']) { ?>
            <em style="display:inline-block"><?php
                echo Format::htmlchars($config['desc']); ?></em>
        <?php }
    }

    function getValue() {
        $data = $this->field->getSource();
        if (count($data))
            return @in_array($this->field->get('id'), $data[$this->name]);
        return parent::getValue();
    }
}

class DatetimePickerWidget extends Widget {
    function render() {
        global $cfg;

        $config = $this->field->getConfiguration();
        if ($this->value) {
            $this->value = (is_int($this->value) ? $this->value :
                DateTime::createFromFormat($cfg->getDateFormat(), $this->value)
                ->format('U'));
            if ($config['gmt'])
                $this->value += 3600 *
                    $_SESSION['TZ_OFFSET']+($_SESSION['TZ_DST']?date('I',$this->value):0);

            list($hr, $min) = explode(':', date('H:i', $this->value));
            $this->value = date($cfg->getDateFormat(), $this->value);
        }
        ?>
        <input type="text" name="<?php echo $this->name; ?>"
            value="<?php echo Format::htmlchars($this->value); ?>" size="12"
            autocomplete="off" class="dp" />
        <script type="text/javascript">
            $(function() {
                $('input[name="<?php echo $this->name; ?>"]').datepicker({
                    <?php
                    if ($config['min'])
                        echo "minDate: new Date({$config['min']}000),";
                    if ($config['max'])
                        echo "maxDate: new Date({$config['max']}000),";
                    elseif (!$config['future'])
                        echo "maxDate: new Date().getTime(),";
                    ?>
                    numberOfMonths: 2,
                    showButtonPanel: true,
                    buttonImage: './images/cal.png',
                    showOn:'both',
                    dateFormat: $.translate_format('<?php echo $cfg->getDateFormat(); ?>'),
                });
            });
        </script>
        <?php
        if ($config['time'])
            // TODO: Add time picker -- requires time picker or selection with
            //       Misc::timeDropdown
            echo '&nbsp;' . Misc::timeDropdown($hr, $min, $this->name . ':time');
    }

    /**
     * Function: getValue
     * Combines the datepicker date value and the time dropdown selected
     * time value into a single date and time string value.
     */
    function getValue() {
        global $cfg;

        $data = $this->field->getSource();
        $config = $this->field->getConfiguration();
        if ($datetime = parent::getValue()) {
            $datetime = (is_int($datetime) ? $datetime :
                (($dt = DateTime::createFromFormat($cfg->getDateFormat() . ' G:i',
                        $datetime . ' 00:00'))
                    ? (int) $dt->format('U') : false)
            );
            if ($datetime && isset($data[$this->name . ':time'])) {
                list($hr, $min) = explode(':', $data[$this->name . ':time']);
                $datetime += $hr * 3600 + $min * 60;
            }
            if ($datetime && $config['gmt'])
                $datetime -= (int) (3600 * $_SESSION['TZ_OFFSET'] +
                    ($_SESSION['TZ_DST'] ? date('I',$datetime) : 0));
        }
        return $datetime;
    }
}

class SectionBreakWidget extends Widget {
    function render() {
        ?><div class="form-header section-break"><h3><?php
        echo Format::htmlchars($this->field->get('label'));
        ?></h3><em><?php echo Format::htmlchars($this->field->get('hint'));
        ?></em></div>
        <?php
    }
}

class ThreadEntryWidget extends Widget {
    function render($client=null) {
        global $cfg;

        ?><div style="margin-bottom:0.5em;margin-top:0.5em"><strong><?php
        echo Format::htmlchars($this->field->get('label'));
        ?></strong>:</div>
        <textarea name="<?php echo $this->field->get('name'); ?>"
            placeholder="<?php echo Format::htmlchars($this->field->get('hint')); ?>"
            <?php if (!$client) { ?>
                data-draft-namespace="ticket.staff"
            <?php } else { ?>
                data-draft-namespace="ticket.client"
                data-draft-object-id="<?php echo substr(session_id(), -12); ?>"
            <?php } ?>
            class="richtext draft draft-delete ifhtml"
            cols="21" rows="8" style="width:80%;"><?php echo
            $this->value; ?></textarea>
    <?php
    }

    function showAttachments($errors=array()) {
        global $cfg, $thisclient;

        if(($cfg->allowOnlineAttachments()
            && !$cfg->allowAttachmentsOnlogin())
            || ($cfg->allowAttachmentsOnlogin()
                && ($thisclient && $thisclient->isValid()))) { ?>
        <hr/>
        <div><strong style="padding-right:1em;vertical-align:top">Attachments: </strong>
        <div style="display:inline-block">
        <div class="uploads" style="display:block"></div>
        <input type="file" class="multifile" name="attachments[]" id="attachments" size="30" value="" />
        </div>
        <font class="error">&nbsp;<?php echo $errors['attachments']; ?></font>
        </div>
        <hr/>
        <?php
        }
    }
}

?>
