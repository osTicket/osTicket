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
    var $title = 'Unnamed Form';
    var $instructions = '';

    function Form() {
        call_user_func_array(array($this, '__construct'), func_get_args());
    }
    function __construct($fields=array(), $title='Unnamed', $instructions='') {
        $this->fields = $fields;
        $this->title = $title;
        $this->instructions = $instructions;
    }

    function getFields() {
        return $this->fields;
    }
    function getTitle() { return $this->title; }
    function getInstructions() { return $this->instructions; }

    function isValid() {
        $this->validate();
        foreach ($this->fields as $f)
            if (!$f->isValidEntry())
                return false;
        return true;
    }

    function validate() {
        foreach ($this->fields as $f)
            $f->validateEntry();
    }
}

require_once(INCLUDE_DIR . "class.json.php");

class FormField {
    var $ht = array(
        'label' => 'Unlabeled',
        'required' => false,
        'default' => false,
        'configuration' => array(),
    );

    var $_cform;

    static $types = array(
        'text'  => array('Short Answer', TextboxField),
        'memo' => array('Long Answer', TextareaField),
        'datetime' => array('Date and Time', DatetimeField),
        'phone' => array('Phone Number', PhoneField),
        'bool' => array('Checkbox', BooleanField),
        'choices' => array('Choices', ChoiceField),
    );
    static $more_types = array();

    function FormField() {
        call_user_func_array(array($this, '__construct'), func_get_args());
    }
    function __construct($options=array()) {
        static $uid = 100;
        $this->ht = array_merge($this->ht, $options);
        if (!isset($this->ht['id']))
            $this->ht['id'] = $uid++;
    }

    static function addFieldTypes($callable) {
        static::$more_types[] = $callable;
    }

    static function allTypes() {
        if (static::$more_types) {
            foreach (static::$more_types as $c)
                static::$types = array_merge(static::$types,
                    call_user_func($c));
            static::$more_types = array();
        }
        return static::$types;
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
        $value = $this->getWidget()->value;
        $value = $this->parse($value);
        $this->validateEntry($value);
        return $value;
    }

    function errors() {
        if (!$this->_errors) return array();
        else return $this->_errors;
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
        # Validates a user-input into an instance of this field on a dynamic
        # form
        if (!is_array($this->_errors)) {
            $this->_errors = array();

            if ($this->get('required') && !$value)
                $this->_errors[] = $this->getLabel() . ' is a required field';
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
        return $value;
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
        return $value;
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
    function getImpl() {
        // Allow registration with ::addFieldTypes and delayed calling
        $types = static::allTypes();
        $clazz = $types[$this->get('type')][1];
        return new $clazz($this->ht);
    }

    function getAnswer() { return $this->answer; }

    function getFormName() {
        return '-field-id-'.$this->get('id');
    }

    function render() {
        $this->getWidget()->render();
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

    function isConfigurable() {
        return true;
    }

    function getConfigurationForm() {
        if (!$this->_cform) {
            $types = static::allTypes();
            $clazz = $types[$this->get('type')][1];
            $T = new $clazz();
            $this->_cform = $T->getConfigurationOptions();
        }
        return $this->_cform;
    }

}

class TextboxField extends FormField {
    function getWidget() {
        return new TextboxWidget($this);
    }

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
        );
    }

    function validateEntry($value) {
        parent::validateEntry($value);
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
            $config = $this->getConfiguration();
            $valid = $config['validator'];
        }
        $func = $validators[$valid];
        if (is_array($func) && is_callable($func[0]))
            if (!call_user_func($func[0], $value))
                $this->_errors[] = $func[1];
    }
}

class TextareaField extends FormField {
    function getWidget() {
        return new TextareaWidget($this);
    }
    function getConfigurationOptions() {
        return array(
            'cols'  =>  new TextboxField(array(
                'id'=>1, 'label'=>'Width (chars)', 'required'=>true, 'default'=>40)),
            'rows'  =>  new TextboxField(array(
                'id'=>2, 'label'=>'Height (rows)', 'required'=>false, 'default'=>4)),
            'length' => new TextboxField(array(
                'id'=>3, 'label'=>'Max Length', 'required'=>false, 'default'=>30))
        );
    }
}

class PhoneField extends FormField {
    function validateEntry($value) {
        parent::validateEntry($value);
        # Run validator against $this->value for email type
        list($phone, $ext) = explode("X", $value, 2);
        if ($phone && !Validator::is_phone($phone))
            $this->_errors[] = "Enter a valid phone number";
        if ($ext) {
            if (!is_numeric($ext))
                $this->_errors[] = "Enter a valide phone extension";
            elseif (!$phone)
                $this->_errors[] = "Enter a phone number for the extension";
        }
    }
    function getWidget() {
        return new PhoneNumberWidget($this);
    }

    function toString($value) {
        list($phone, $ext) = explode("X", $value, 2);
        $phone=Format::phone($phone);
        if ($ext)
            $phone.=" x$ext";
        return $phone;
    }
}

class BooleanField extends FormField {
    function getWidget() {
        return new CheckboxWidget($this);
    }

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

    function to_php($value) {
        return ((int)$value) ? true : false;
    }

    function toString($value) {
        return ($value) ? 'Yes' : 'No';
    }
}

class ChoiceField extends FormField {
    function getWidget() {
        return new ChoicesWidget($this);
    }

    function getConfigurationOptions() {
        return array(
            'choices'  =>  new TextareaField(array(
                'id'=>1, 'label'=>'Choices', 'required'=>false, 'default'=>'')),
        );
    }
}

class DatetimeField extends FormField {
    function getWidget() {
        return new DatetimePickerWidget($this);
    }

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
        return ($config['gmt']) ? Misc::db2gmtime($value) : strtotime($value);
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

class Widget {
    function Widget() {
        # Not called in PHP5
        call_user_func_array(array(&$this, '__construct'), func_get_args());
    }

    function __construct($field) {
        $this->field = $field;
        $this->name = $field->getFormName();
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
            $this->value = $this->getValue();
        elseif (is_object($field->getAnswer()))
            $this->value = $field->getAnswer()->getValue();
        if (!$this->value && $field->value)
            $this->value = $field->value;
    }

    function getValue() {
        return $_POST[$this->name];
    }
}

class TextboxWidget extends Widget {
    function render() {
        $config = $this->field->getConfiguration();
        if (isset($config['size']))
            $size = "size=\"{$config['size']}\"";
        if (isset($config['length']))
            $maxlength = "maxlength=\"{$config['length']}\"";
        ?>
        <span style="display:inline-block">
        <input type="text" id="<?php echo $this->name; ?>"
            <?php echo $size . " " . $maxlength; ?>
            name="<?php echo $this->name; ?>"
            value="<?php echo Format::htmlchars($this->value); ?>"/>
        </span>
        <?php
    }
}

class TextareaWidget extends Widget {
    function render() {
        $config = $this->field->getConfiguration();
        if (isset($config['rows']))
            $rows = "rows=\"{$config['rows']}\"";
        if (isset($config['cols']))
            $cols = "cols=\"{$config['cols']}\"";
        if (isset($config['length']))
            $maxlength = "maxlength=\"{$config['length']}\"";
        ?>
        <span style="display:inline-block">
        <textarea <?php echo $rows." ".$cols." ".$length; ?>
            name="<?php echo $this->name; ?>"><?php
                echo Format::htmlchars($this->value);
            ?></textarea>
        </span>
        <?php
    }
}

class PhoneNumberWidget extends Widget {
    function render() {
        list($phone, $ext) = explode("X", $this->value);
        ?>
        <input type="text" name="<?php echo $this->name; ?>" value="<?php
            echo $phone; ?>"/> Ext: <input type="text" name="<?php
            echo $this->name; ?>-ext" value="<?php echo $ext; ?>" size="5"/>
        <?php
    }

    function getValue() {
        $ext = $_POST["{$this->name}-ext"];
        if ($ext) $ext = 'X'.$ext;
        return parent::getValue() . $ext;
    }
}

class ChoicesWidget extends Widget {
    function render() {
        $config = $this->field->getConfiguration();
        // Determine the value for the default (the one listed if nothing is
        // selected)
        $def_key = $this->field->get('default');
        $choices = $this->getChoices();
        $have_def = isset($choices[$def_key]);
        if (!$have_def)
            $def_val = 'Select '.$this->field->get('label');
        else
            $def_val = $choices[$def_key];
        ?> <span style="display:inline-block">
        <select name="<?php echo $this->name; ?>">
            <?php if (!$have_def) { ?>
            <option value="<?php echo $def_key; ?>">&mdash; <?php
                echo $def_val; ?> &mdash;</option>
            <?php }
            foreach ($choices as $key=>$name) {
                if (!$have_def && $key == $def_key)
                    continue; ?>
                <option value="<?php echo $key; ?>"
                <?php if ($this->value == $key) echo 'selected="selected"';
                ?>><?php echo $name; ?></option>
            <?php } ?>
        </select>
        </span>
        <?php
    }

    function getChoices() {
        if ($this->_choices === null) {
            // Allow choices to be set in this->ht (for configurationOptions)
            $this->_choices = $this->field->get('choices');
            if (!$this->_choices) {
                $this->_choices = array();
                $config = $this->field->getConfiguration();
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
        if (count($_POST))
            return @in_array($this->field->get('id'), $_POST[$this->name]);
        return parent::getValue();
    }
}

class DatetimePickerWidget extends Widget {
    function render() {
        $config = $this->field->getConfiguration();
        if ($this->value) {
            $this->value = (is_int($this->value) ? $this->value :
                    strtotime($this->value));
            if ($config['gmt'])
                $this->value += 3600 *
                    $_SESSION['TZ_OFFSET']+($_SESSION['TZ_DST']?date('I',$time):0);

            list($hr, $min) = explode(':', date('H:i', $this->value));
            $this->value = date('m/d/Y', $this->value);
        }
        ?>
        <input type="text" name="<?php echo $this->name; ?>"
            value="<?php echo Format::htmlchars($this->value); ?>" size="12"
            autocomplete="off" />
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
                    showOn:'both'
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
        $datetime = parent::getValue();
        if ($datetime && isset($_POST[$this->name . ':time']))
            $datetime .= ' ' . $_POST[$this->name . ':time'];
        return $datetime;
    }
}

?>
