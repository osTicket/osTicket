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
    static $renderer = 'GridFluidLayout';
    static $id = 0;

    var $options = array();
    var $fields = array();
    var $title = '';
    var $notice = '';
    var $instructions = '';

    var $validators = array();

    var $_errors = null;
    var $_source = false;

    function __construct($source=null, $options=array()) {

        $this->options = $options;
        if (isset($options['title']))
            $this->title = $options['title'];
        if (isset($options['instructions']))
            $this->instructions = $options['instructions'];
        if (isset($options['notice']))
            $this->notice = $options['notice'];
        if (isset($options['id']))
            $this->id = $options['id'];

        // Use POST data if source was not specified
        $this->_source = $source ?: $_POST;
    }

    function getFormId() {
        return @$this->id ?: static::$id;
    }
    function setId($id) {
        $this->id = $id;
    }

    function setNotice($notice) {
        $this->notice = $notice;
    }

    function data($source) {
        foreach ($this->fields as $name=>$f)
            if (isset($source[$name]))
                $f->value = $source[$name];
    }

    function setFields($fields) {

        if (!is_array($fields) && !$fields instanceof Traversable)
            return;

        $this->fields = $fields;
        foreach ($fields as $k=>$f) {
            $f->setForm($this);
            if (!$f->get('name') && $k && !is_numeric($k))
                $f->set('name', $k);
        }
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

    function hasField($name) {
        return $this->getField($name);
    }

    function hasAnyEnabledFields() {
        return $this->hasAnyVisibleFields(false);
    }

    function hasAnyVisibleFields($user=false) {
        $visible = 0;
        $isstaff = $user instanceof Staff;
        foreach ($this->getFields() as $F) {
            if (!$user) {
                // Assume hasAnyEnabledFields
                if ($F->isEnabled())
                    $visible++;
            } elseif($isstaff) {
                if ($F->isVisibleToStaff())
                    $visible++;
            } elseif ($F->isVisibleToUsers()) {
                $visible++;
            }
        }
        return $visible > 0;
    }

    function getTitle() { return $this->title; }
    function getNotice() { return $this->notice; }
    function getInstructions() { return Format::htmldecode($this->instructions); }
    function getSource() { return $this->_source; }
    function setSource($source) { $this->_source = $source; }

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
            $this->validate($this->getClean());
            foreach ($this->getFields() as $field)
                if ($field->errors() && (!$include || $include($field)))
                    $this->_errors[$field->get('id')] = $field->errors();
        }
        return !$this->_errors;
    }

    function validate($clean_data) {
        // Validate the whole form so that errors can be added to the
        // individual fields and collected below.
        foreach ($this->validators as $V) {
            $V($this);
        }
    }

    function getClean($validate=true) {
        if (!$this->_clean) {
            $this->_clean = array();
            foreach ($this->getFields() as $key=>$field) {
                if (!$field->hasData())
                    continue;

                // Prefer indexing by field.id if indexing numerically
                if (is_int($key) && $field->get('id'))
                    $key = $field->get('id');
                $this->_clean[$key] = $this->_clean[$field->get('name')]
                    = $field->getClean($validate);
            }
            unset($this->_clean[""]);
        }
        return $this->_clean;
    }
    /*
     * Transform form data to database ready clean data.
     *
     */
    function to_db($clean=null, $validate=true) {
        if (!$clean
                && !$this->isValid()
                && !($clean=$this->getClean($validate)))
            return false;
        $data = [];
        foreach ($clean as $name => $val) {
            if (!($f = $this->getField($name)))
                continue;
            try {
                $data[$name] = $f->to_database($val);
            } catch (FieldUnchanged $e) {
                // Unset field if it's unchanged...mainly
                // useful for Secret/PasswordField
                unset($data[$name]);
            }
        }
        return $data;
    }

    /*
     * Process the form input and return clean data.
     *
     * It's similar to to_db but forms downstream can use it to skip or add
     * extra validations

     */
    function process($validate=true) {
        return $this->to_db($validate);
    }


    function errors($formOnly=false) {
        return ($formOnly) ? $this->_errors['form'] : $this->_errors;
    }

    function addError($message, $index=false) {
        if ($index)
            $this->_errors[$index] = $message;
        else
            $this->_errors['form'][] = $message;
    }

    function addErrors($errors=[]) {
        foreach ($errors as $k => $v) {
            if (($f=$this->getField($k)))
                $f->addError($v);
            else
                $this->addError($v, $k);
        }
    }

    function addValidator($function) {
        if (!is_callable($function))
            throw new Exception('Form validator must be callable');
        $this->validators[] = $function;
    }

    function render($options=array()) {
        if (isset($options['title']))
            $this->title = $options['title'];
        if (isset($options['instructions']))
            $this->instructions = $options['instructions'];
        if (isset($options['notice']))
            $this->notice = $options['notice'];

        $form = $this;
        $template = $options['template'] ?: 'dynamic-form.tmpl.php';
        if (isset($options['staff']) && $options['staff'])
            include(STAFFINC_DIR . 'templates/' . $template);
        else
            include(CLIENTINC_DIR . 'templates/' . $template);
        echo $this->getMedia();
    }

    function getLayout($title=false, $options=array()) {
        $rc = @$options['renderer'] ?: static::$renderer;
        return new $rc($title, $options);
    }

    function asTable($title=false, $options=array()) {
        return $this->getLayout($title, $options)->asTable($this);
        // XXX: Media can't go in a table
        echo $this->getMedia();
    }

    function getMedia() {
        static $dedup = array();

        foreach ($this->getFields() as $f) {
            if (($M = $f->getMedia()) && is_array($M)) {
                foreach ($M as $type=>$files) {
                    foreach ($files as $url) {
                        $key = strtolower($type.$url);
                        if (isset($dedup[$key]))
                            continue;

                        self::emitMedia($url, $type);

                        $dedup[$key] = true;
                    }
                }
            }
        }
    }

    function emitJavascript($options=array()) {

        // Check if we need to emit javascript
        if (!($fid=$this->getFormId()))
            return;
        ?>
        <script type="text/javascript">
          $(function() {
            <?php
            //XXX: We ONLY want to watch field on this form. We'll only
            // watch form inputs if form_id is specified. Current FORM API
            // doesn't generate the entire form  (just fields)
            if ($fid) {
                ?>
                $(document).off('change.<?php echo $fid; ?>');
                $(document).on('change.<?php echo $fid; ?>',
                    'form#<?php echo $fid; ?> :input',
                    function() {
                        //Clear any current errors...
                        var errors = $('#field'+$(this).attr('id')+'_error');
                        if (errors.length)
                            errors.slideUp('fast', function (){
                                $(this).remove();
                                });
                        //TODO: Validation input inplace or via ajax call
                        // and set any new errors AND visibilty changes
                    }
                   );
            <?php
            }
            ?>
            });
        </script>
        <?php
    }

    static function emitMedia($url, $type) {
        if ($url[0] == '/')
            $url = ROOT_PATH . substr($url, 1);

        switch (strtolower($type)) {
        case 'css': ?>
        <link rel="stylesheet" type="text/css" href="<?php echo $url; ?>"/><?php
            break;
        case 'js': ?>
        <script type="text/javascript" src="<?php echo $url; ?>"></script><?php
            break;
        }
    }

    /**
     * getState
     *
     * Retrieves an array of information which can be passed to the
     * ::loadState method later to recreate the current state of the form
     * fields and values.
     */
    function getState() {
        $info = array();
        foreach ($this->getFields() as $f) {
            // Skip invisible fields
            if (!$f->isVisible())
                continue;

            // Skip fields set to default values
            $v = $f->getClean();
            $d = $f->get('default');
            if ($v == $d)
                continue;

            // Skip empty values
            if (!$v)
                continue;

            $info[$f->get('name') ?: $f->get('id')] = $f->to_database($v);
        }
        return $info;
    }

    /**
     * loadState
     *
     * Reset this form to the state previously recorded by the ::getState()
     * method
     */
    function loadState($state) {
        foreach ($this->getFields() as $f) {
            $name = $f->get('name');
            $f->reset();
            if (isset($state[$name])) {
                $f->value = $f->to_php($state[$name]);
            }
        }
    }

    /*
     * Initialize a generic static form
     */
    static function instantiate() {
        $r = new ReflectionClass(get_called_class());
        return $r->newInstanceArgs(func_get_args());
    }
}

/**
 * SimpleForm
 * Wrapper for inline/static forms.
 *
 */
class SimpleForm extends Form {
    function __construct($fields=array(), $source=null, $options=array()) {
        parent::__construct($source, $options);
        if (isset($options['type']))
            $this->type = $options['type'];
        $this->setFields($fields);
    }

    function getId() {
        return $this->getFormId();
    }
}

class CustomForm extends SimpleForm {

    function getFields() {
        global $thisstaff, $thisclient;

        $options = $this->options;
        $user = $options['user'] ?: $thisstaff ?: $thisclient;
        $isedit = ($options['mode'] == 'edit');
        $fields = array();
        foreach (parent::getFields() as $field) {
            if ($isedit && !$field->isEditable($user))
                continue;

            $fields[] = $field;
        }

        return $fields;
    }
}

abstract class AbstractForm extends Form {
    function __construct($source=null, $options=array()) {
        parent::__construct($source, $options);
        $this->setFields($this->buildFields());
    }
    /**
     * Fetch the fields defined for this form. This method is only called
     * once.
     */
    abstract function buildFields();
}

/**
 * Container class to represent the connection between the form fields and the
 * rendered state of the form.
 */
interface FormRenderer {
    // Render the form fields into a table
    function asTable($form);
    // Render the form fields into divs
    function asBlock($form);
}

abstract class FormLayout {
    static $default_cell_layout = 'Cell';

    var $title;
    var $options;

    function __construct($title=false, $options=array()) {
        $this->title = $title;
        $this->options = $options;
    }

    function getLayout($field) {
        $layout = $field->get('layout') ?: static::$default_cell_layout;
        if (is_string($layout))
            $layout = new $layout();
        return $layout;
    }
}

class GridFluidLayout
extends FormLayout
implements FormRenderer {
    function asTable($form) {
      ob_start();
?>
      <table class="<?php echo 'grid form' ?>">
          <caption><?php echo Format::htmlchars($this->title ?: $form->getTitle()); ?>
            <div><small><?php echo Format::viewableImages($form->getInstructions()); ?></small></div>
            <?php
            if ($form->getNotice())
                echo sprintf('<div><small><p id="msg_warning">%s</p></small></div>',
                        Format::htmlchars($form->getNotice()));
            ?>
        </caption>
        <tbody><tr><?php for ($i=0; $i<12; $i++) echo '<td style="width:8.3333%"/>'; ?></tr></tbody>
<?php
      $row_size = 12;
      $cols = $row = 0;

      //Layout and rendering options
      $options = $this->options;

      foreach ($form->getFields() as $f) {
          $layout = $this->getLayout($f);
          $size = $layout->getWidth() ?: 12;
          if ($offs = $layout->getOffset()) {
              $size += $offs;
          }
          if ($cols < $size || $layout->isBreakForced()) {
              if ($row) echo '</tr>';
              echo '<tr>';
              $cols = $row_size;
              $row++;
          }
          // Render the cell
          $cols -= $size;
          $attrs = array('colspan' => $size, 'rowspan' => $layout->getHeight(),
              'style' => '"'.$layout->getOption('style').'"');
          if ($offs) { ?>
              <td colspan="<?php echo $offs; ?>"></td> <?php
          }
          ?>
          <td class="cell" <?php echo Format::array_implode('=', ' ', array_filter($attrs)); ?>
              data-field-id="<?php echo $f->get('id'); ?>">
              <fieldset class="field <?php if (!$f->isVisible()) echo 'hidden'; ?>"
                id="field<?php echo $f->getWidget()->id; ?>"
                data-field-id="<?php echo $f->get('id'); ?>">
<?php         $label = $f->get('label'); ?>
              <label class="<?php if ($f->isRequired()) echo 'required'; ?>"
                  for="<?php echo $f->getWidget()->id; ?>">
                  <?php echo $label ? (Format::htmlchars($label).':') : '&nbsp;'; ?>
                <?php if ($f->isRequired()) { ?>
                <span class="error">*</span>
              </label>
<?php         }
              if ($f->get('hint')) { ?>
                  <div class="field-hint-text">
                      <?php echo Format::htmlchars($f->get('hint')); ?>
                  </div>
<?php         }
              $f->render($options);
              if ($f->errors())
                  foreach ($f->errors() as $e)
                      echo sprintf('<div class="error">%s</div>', Format::htmlchars($e));
?>
              </fieldset>
          </td>
      <?php
      }
      if ($row)
        echo  '</tr>';

      echo '</tbody></table>';

      return ob_get_clean();
    }

    function asBlock($form) {}
}

/**
 * Basic container for field and form layouts. By default every cell takes
 * a whole output row and does not imply any sort of width.
 */
class Cell {
    function isBreakForced()  { return true; }
    function getWidth()       { return false; }
    function getHeight()      { return 1; }
    function getOffset()      { return 0; }
    function getOption($prop) { return false; }
}

/**
 * Fluid grid layout, meaning each cell renders to the right of the previous
 * cell (for left-to-right layouts). A width in columns can be specified for
 * each cell along with an offset from the previous cell. A height of columns
 * along with an optional break is supported.
 */
class GridFluidCell
extends Cell {
    var $span;
    var $options;

    function __construct($span, $options=array()) {
        $this->span = $span;
        $this->options = $options + array(
            'rows' => 1,        # rowspan
            'offset' => 0,      # skip some columns
            'break' => false,   # start on a new row
        );
    }

    function isBreakForced()  { return $this->options['break']; }
    function getWidth()       { return $this->span; }
    function getHeight()      { return $this->options['rows']; }
    function getOffset()      { return $this->options['offset']; }
    function getOption($prop) { return $this->options[$prop]; }
}

require_once(INCLUDE_DIR . "class.json.php");

class FormField {
    static $widget = false;

    var $ht = array(
        'label' => false,
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
        /* @trans */ 'Basic Fields' => array(
            'text'  => array(   /* @trans */ 'Short Answer', 'TextboxField'),
            'memo' => array(    /* @trans */ 'Long Answer', 'TextareaField'),
            'thread' => array(  /* @trans */ 'Thread Entry', 'ThreadEntryField', false),
            'datetime' => array(/* @trans */ 'Date and Time', 'DatetimeField'),
            'timezone' => array(/* @trans */ 'Timezone', 'TimezoneField'),
            'phone' => array(   /* @trans */ 'Phone Number', 'PhoneField'),
            'bool' => array(    /* @trans */ 'Checkbox', 'BooleanField'),
            'choices' => array( /* @trans */ 'Choices', 'ChoiceField'),
            'files' => array(   /* @trans */ 'File Upload', 'FileUploadField'),
            'break' => array(   /* @trans */ 'Section Break', 'SectionBreakField'),
            'info' => array(    /* @trans */ 'Information', 'FreeTextField'),
        ),
    );
    static $more_types = array();
    static $uid = null;

    static function _uid() {
        return ++self::$uid;
    }

    function __construct($options=array()) {
        $this->ht = array_merge($this->ht, $options);
        if (!isset($this->ht['id']))
            $this->ht['id'] = self::_uid();
    }

    function __clone() {
        $this->_widget = null;
        $this->ht['id'] = self::_uid();
    }

    static function addFieldTypes($group, $callable) {
        static::$more_types[$group][] = $callable;
    }

    static function allTypes() {
        if (static::$more_types) {
            foreach (static::$more_types as $group => $entries)
                foreach ($entries as $c)
                    static::$types[$group] = array_merge(
                            static::$types[$group] ?? array(), call_user_func($c));

            static::$more_types = array();
        }
        return static::$types;
    }

    static function getFieldType($type) {
        foreach (static::allTypes() as $group=>$types)
            if (isset($types[$type]))
                return $types[$type];
    }

    function get($what, $default=null) {
        return array_key_exists($what, $this->ht)
            ? $this->ht[$what]
            : $default;
    }
    function set($field, $value) {
        $this->ht[$field] = $value;
    }

    function getId() {
        return $this->ht['id'];
    }

    /**
     * getClean
     *
     * Validates and cleans inputs from POST request. This is performed on a
     * field instance, after a DynamicFormSet / DynamicFormSection is
     * submitted via POST, in order to kick off parsing and validation of
     * user-entered data.
     */
    function getClean($validate=true) {
        if (!isset($this->_clean)) {
            $this->_clean = (isset($this->value))
                // XXX: The widget value may be parsed already if this is
                //      linked to dynamic data via ::getAnswer()
                ? $this->value : $this->parse($this->getWidget()->value);

            if ($vs = $this->get('cleaners')) {
                if (is_array($vs)) {
                    foreach ($vs as $cleaner)
                        if (is_callable($cleaner))
                            $this->_clean = call_user_func_array(
                                    $cleaner, array($this, $this->_clean));
                }
                elseif (is_callable($vs))
                    $this->_clean = call_user_func_array(
                            $vs, array($this, $this->_clean));
            }

            if (!isset($this->_clean) && ($d = $this->get('default')))
                $this->_clean = $d;

            if ($this->isVisible() && $validate)
                $this->validateEntry($this->_clean);
        }
        return $this->_clean;
    }

    function reset() {
        $this->value = $this->_clean = $this->_widget = null;
    }

    function getValue() {
        return $this->getWidget()->getValue();
    }

    function errors() {
        return $this->_errors;
    }

    function resetErrors() {
        $this->_errors = [];
        return !($this->_errors);
    }

    function addError($message, $index=false) {
        if ($index)
            $this->_errors[$index] = $message;
        else
            $this->_errors[] = $message;

        // Update parent form errors for the field
        if ($this->_form)
            $this->_form->addError($this->errors(), $this->get('id'));
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
            $this->_errors[] = $this->getLocal('label')
                ? sprintf(__('%s is a required field'), $this->getLocal('label'))
                : __('This is a required field');

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
     * isVisible
     *
     * If this field has visibility configuration, then it will parse the
     * constraints with the visibility configuration to determine if the
     * field is visible and should be considered for validation
     */
    function isVisible() {
        if ($this->get('visibility') instanceof VisibilityConstraint) {
            return $this->get('visibility')->isVisible($this);
        }
        return true;
    }

    /**
     * Check if the user has edit rights
     *
     */

    function isEditable($user=null) {

        // Internal editable flag used by internal forms e.g internal lists
        if (!$user && isset($this->ht['editable']))
            return $this->ht['editable'];

        if ($user instanceof Staff)
            $flag = DynamicFormField::FLAG_AGENT_EDIT;
        else
            $flag = DynamicFormField::FLAG_CLIENT_EDIT;

        return (($this->get('flags') & $flag) != 0);
    }


    /**
     * isStorable
     *
     * Indicate if this field data is storable locally (default).Some field's data
     * might beed to be stored elsewhere for optimization reasons at the
     * application level.
     *
     */

    function isStorable() {
        return (($this->get('flags') & DynamicFormField::FLAG_EXT_STORED) == 0);
    }

    function isRequired() {
        return $this->get('required');
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
        return is_string($value) ? trim($value) : $value;
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
     * to_config
     *
     * Transform the data from the value to config form (as determined by
     * field). to_php is used for each field returned from
     * ::getConfigurationOptions(), and when the whole configuration is
     * built, to_config() is called and receives the config array. The array
     * should be returned, perhaps with modifications, and will be JSON
     * encoded and stashed in the database.
     */
    function to_config($value) {
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

    function __toString() {
        return $this->toString($this->value);
    }

    /**
     * When data for this field is deleted permanently from some storage
     * backend (like a database), other associated data may need to be
     * cleaned as well. This hook allows fields to participate when the data
     * for a field is cleaned up.
     */
    function db_cleanup($field=false) {
    }

    /**
     * Returns an HTML friendly value for the data in the field.
     */
    function display($value) {
        return Format::htmlchars($this->toString($value ?: $this->value));
    }

    /**
     * Returns a value suitable for exporting to a foreign system. Mostly
     * useful for things like dates and phone numbers which should be
     * formatted using a standard when exported
     */
    function export($value) {
        return $this->toString($value);
    }

    /**
     * Fetch a value suitable for embedding the value of this field in an
     * email template. Reference implementation uses ::to_php();
     */
    function asVar($value, $id=false) {
        return $this->to_php($value, $id);
    }

    /**
     * Fetch the var type used with the email templating system's typeahead
     * feature. This helps with variable expansion if supported by this
     * field's ::asVar() method. This method should return a valid classname
     * which implements the `TemplateVariable` interface.
     */
    function asVarType() {
        return false;
    }

    /**
     * Describe the difference between the to two values. Note that the
     * values should be passed through ::parse() or to_php() before
     * utilizing this method.
     */
    function whatChanged($before, $after) {
        if ($before)
            $desc = __('changed from <strong>%1$s</strong> to <strong>%2$s</strong>');
        else
            $desc = __('set to <strong>%2$s</strong>');
        return sprintf($desc, $this->display($before), $this->display($after));
    }

    /**
     * Convert the field data to something matchable by filtering. The
     * primary use of this is for ticket filtering.
     */
    function getFilterData() {
        return $this->toString($this->getClean());
    }

    /**
     * Fetches a value that represents this content in a consistent,
     * searchable format. This is used by the search engine system and
     * backend.
     */
    function searchable($value) {
        return Format::searchable($this->toString($value));
    }

    function getKeys($value) {
        return $this->to_database($value);
    }

    /**
     * Fetches a list of options for searching. The values returned from
     * this method are passed to the widget's `::render()` method so that
     * the widget can be affected by this setting. For instance, date fields
     * might have a 'between' search option which should trigger rendering
     * of two date widgets for search results.
     */
    function getSearchMethods() {
        return array(
            'set' =>        __('has a value'),
            'nset' =>       __('does not have a value'),
            'equal' =>      __('is'),
            'nequal' =>     __('is not'),
            'contains' =>   __('contains'),
            'match' =>      __('matches'),
        );
    }

    function getSearchMethodWidgets() {
        return array(
            'set' => null,
            'nset' => null,
            'equal' => array('TextboxField', array('configuration' => array('size' => 40))),
            'nequal' => array('TextboxField', array('configuration' => array('size' => 40))),
            'contains' => array('TextboxField', array('configuration' => array('size' => 40))),
            'match' => array('TextboxField', array(
                'placeholder' => __('Valid regular expression'),
                'configuration' => array('size'=>30),
                'validators' => function($self, $v) {
                    if (false === @preg_match($v, ' ')
                        && false === @preg_match("/$v/", ' '))
                        $self->addError(__('Cannot compile this regular expression'));
                })),
        );
    }

    /**
     * This is used by the searching system to build a query for the search
     * engine. The function should return a criteria listing to match
     * content saved by the field by the `::to_database()` function.
     */
    function getSearchQ($method, $value, $name=false) {
        $criteria = array();
        $Q = new Q();
        $name = $name ?: $this->get('name');
        switch ($method) {
            case 'nset':
                $Q->negate();
            case 'set':
                $criteria[$name . '__isnull'] = false;
                break;

            case 'nequal':
                $Q->negate();
            case 'equal':
                $criteria[$name] = $value;
                break;

            case 'contains':
                $criteria[$name . '__contains'] = $value;
                break;

            case 'match':
                $criteria[$name . '__regex'] = $value;
                break;
        }
        return $Q->add($criteria);
    }

    function getSearchWidget($method) {
        $methods = $this->getSearchMethodWidgets();
        $info = $methods[$method];
        if (is_array($info)) {
            $class = $info[0];
            return new $class($info[1]);
        }
        return $info;
    }

    function describeSearchMethod($method) {
        switch ($method) {
        case 'set':
            return __('%s has a value');
        case 'nset':
            return __('%s does not have a value');
        case 'equal':
            return __('%s is %s' /* describes an equality */);
        case 'nequal':
            return __('%s is not %s' /* describes an inequality */);
        case 'contains':
            return __('%s contains "%s"');
        case 'match':
            return __('%s matches pattern %s');
        case 'includes':
            return __('%s in (%s)');
        case '!includes':
            return __('%s not in (%s)');
        }
    }

    function describeSearch($method, $value, $name=false) {
        $name = $name ?: $this->get('name');
        $desc = $this->describeSearchMethod($method);
        switch ($method) {
            case 'set':
            case 'nset':
                return sprintf($desc, $name);
            default:
                 return sprintf($desc, $name, $this->toString($value));
        }
    }

    function addToQuery($query, $name=false) {
        return $query->values($name ?: $this->get('name'));
    }

    /**
     * Similary to to_php() and parse(), except a row from a queryset is
     * passed. The value returned should be what would be retured from
     * parse() or to_php()
     */
    function from_query($row, $name=false) {
        return $row[$name ?: $this->get('name')];
    }

    /**
     * If the field can be used in a quick filter. To be used, it should
     * also implement getQuickFilterChoices() which should return a list of
     * choices to appear in a quick filter drop-down
     */
    function supportsQuickFilter() {
        return false;
    }

    /**
     * Fetch a keyed array of quick filter choices. The keys should be
     * passed later to ::applyQuickFilter() to apply the quick filter to a
     * query. The values should be localized titles for the choices.
     */
    function getQuickFilterChoices() {
        return array();
    }

    /**
     * Apply a quick filter selection of this field to the query. The
     * modified query should be returned. Optionally, the orm path / field
     * name can be passed.
     */
    function applyQuickFilter($query, $choice, $name=false) {
        return $query;
    }

    function getLabel() { return $this->get('label'); }

    function getSortKeys($path) {
        return array($path);
    }

    function getOrmPath($name=false, $query=null) {
        return CustomQueue::getOrmPath($name ?:$this->get('name'), $query);
    }

    function applyOrderBy($query, $reverse=false, $name=false) {
        $col = sprintf('%s%s',
                $reverse ? '-' : '',
                $this->getOrmPath($name, $query));
        return $query->order_by($col);
    }

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
            throw new Exception(sprintf(__('%s: Call to undefined function'),
                $what));
        // BEWARE: DynamicFormField has a __call() which will create a new
        //      FormField instance and invoke __call() on it or bounce
        //      immediately back
        return call_user_func_array(
            array($this->parent, $what), $args);
    }

    function getAnswer() { return $this->answer; }
    function setAnswer($ans) { $this->answer = $ans; }

    function setValue($value) {
        $this->reset();
        $this->getWidget()->value = $value;
    }

    /**
     * Fetch a pseudo-random id for this form field. It is used when
     * rendering the widget in the @name attribute emitted in the resulting
     * HTML. The form element is based on the form id, field id and name,
     * and the current user's session id. Therefore, the same form fields
     * will yield differing names for different users. This is used to ward
     * off bot attacks as it makes it very difficult to predict and
     * correlate the form names to the data they represent.
     */
    function getFormName() {
        $default = $this->get('name') ?: $this->get('id');
        if ($this->_form && is_numeric($fid = $this->_form->getFormId()))
            return substr(md5(
                session_id() . "-form-field-id-$fid-$default-" . SECRET_SALT), -14);
        elseif (is_numeric($this->get('id')))
            return substr(md5(
                session_id() . '-field-id-'.$this->get('id') . '-' . SECRET_SALT), -16);

        return $default;
    }

    function getFormNames() {

        // All possible names - this is important for inline data injection
        $names = array_filter([
                'hash' => $this->getFormName(),
                'name' => $this->get('name'),
                'id' => $this->get('id')]);

        // Force pseudo-random name for Dynamicforms on POST (Web Tickets)
        if (0 && $_POST
                && !defined('APICALL')
                && isset($this->ht['form'])
                && ($this->ht['form'] instanceof DynamicForm))
            return [$names['hash']];

        return $names;
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

    function render($options=array()) {
        $rv = $this->getWidget()->render($options);
        if ($v = $this->get('visibility')) {
            $v->emitJavascript($this);
        }
        return $rv;
    }

    function renderExtras($options=array()) {
        return;
    }

    function getMedia() {
        $widget = $this->getWidget();
        return $widget::$media;
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
        if (!isset($this->_config)) {
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

    /**
     * Indicates if the field has subfields accessible via getSubFields()
     * method. Useful for filter integration. Should connect with
     * getFilterData()
     */
    function hasSubFields() {
        return false;
    }
    function getSubFields() {
        return null;
    }

    function getConfigurationForm($source=null) {
        if (!$this->_cform) {
            $type = static::getFieldType($this->get('type'));
            $clazz = $type[1];
            $T = new $clazz($this->ht);
            $config = $this->getConfiguration();
            $this->_cform = new SimpleForm($T->getConfigurationOptions(), $source);
            if (!$source) {
                foreach ($this->_cform->getFields() as $name=>$f) {
                    if ($config && isset($config[$name]))
                        $f->value = $config[$name];
                    elseif ($f->get('default'))
                        $f->value = $f->get('default');
                }
            }
        }
        return $this->_cform;
    }

    function configure($prop, $value) {
        $this->getConfiguration();
        $this->_config[$prop] = $value;
    }

    function getWidget($widgetClass=false) {
        if (!static::$widget)
            throw new Exception(__('Widget not defined for this field'));
        if (!isset($this->_widget)) {
            $wc = $widgetClass ?: $this->get('widget') ?: static::$widget;
            $this->_widget = new $wc($this);
            $this->_widget->parseValue();
        }
        return $this->_widget;
    }

    function getSelectName() {
        $name = $this->get('name') ?: 'field_'.$this->get('id');
        if ($this->hasIdValue())
            $name .= '_id';

        return $name;
    }

    function getTranslateTag($subtag) {
        return _H(sprintf('field.%s.%s%s', $subtag, $this->get('id'),
            $this->get('form_id') ? '' : '*internal*'));
    }

    function getLocal($subtag, $default=false) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : ($default ?: $this->get($subtag));
    }

    function getEditForm($source=null) {
        $fields = array(
                'field' => $this,
                'comments' => new TextareaField(array(
                        'id' => 2,
                        'label'=> '',
                        'required' => false,
                        'default' => '',
                        'configuration' => array(
                            'html' => true,
                            'size' => 'small',
                            'placeholder' => __('Optional reason for the update'),
                            )
                        ))
                );

        return new SimpleForm($fields, $source);
    }

    function getChanges() {
        $new = $this->getValue();
        $old = $this->answer ? $this->answer->getValue() : $this->get('default');
        return ($old != $new) ? array($this->to_database($old), $this->to_database($new)) : false;
    }


    function save() {

        if (!($changes=$this->getChanges()))
            return true;

        if (!($a = $this->answer))
            return false;

        $val = $changes[1];
        if (is_array($val)) {
            $a->set('value', $val[0]);
            $a->set('value_id', $val[1]);
        } else {
            $a->set('value', $val);
        }

        if (!$a->save(true))
            return false;

        $this->_clean = $this->_widget = null;
        return $this->parent->save();
    }


    static function init($config) {
        return new Static($config);
    }
}

class TextboxField extends FormField {
    static $widget = 'TextboxWidget';

    function getConfigurationOptions() {
        return array(
            'size'  =>  new TextboxField(array(
                'id'=>1, 'label'=>__('Size'), 'required'=>false, 'default'=>16,
                    'validator' => 'number')),
            'length' => new TextboxField(array(
                'id'=>2, 'label'=>__('Max Length'), 'required'=>false, 'default'=>30,
                    'validator' => 'number')),
            'validator' => new ChoiceField(array(
                'id'=>3, 'label'=>__('Validator'), 'required'=>false, 'default'=>'',
                'choices' => array('phone'=>__('Phone Number'),'email'=>__('Email Address'),
                    'ip'=>__('IP Address'), 'number'=>__('Number'),
                    'regex'=>__('Custom (Regular Expression)'), ''=>__('None')))),
            'regex' => new TextboxField(array(
                'id'=>6, 'label'=>__('Regular Expression'), 'required'=>true,
                'configuration'=>array('size'=>40, 'length'=>100),
                'visibility' => new VisibilityConstraint(
                    new Q(array('validator__eq'=>'regex')),
                    VisibilityConstraint::HIDDEN
                ),
                'cleaners' => function ($self, $value) {
                    $wrapped = "/".$value."/iu";
                    if (false === @preg_match($value, ' ')
                            && false !== @preg_match($wrapped, ' ')) {
                        $value = $wrapped;
                    }
                    if ($value == '//iu')
                        return '';

                    return $value;
                },
                'validators' => function($self, $v) {
                    if (false === @preg_match($v, ' '))
                        $self->addError(__('Cannot compile this regular expression'));
                })),
            'validator-error' => new TextboxField(array(
                'id'=>4, 'label'=>__('Validation Error'), 'default'=>'',
                'configuration'=>array('size'=>40, 'length'=>60,
                    'translatable'=>$this->getTranslateTag('validator-error')
                ),
                'hint'=>__('Message shown to user if the input does not match the validator'))),
            'placeholder' => new TextboxField(array(
                'id'=>5, 'label'=>__('Placeholder'), 'required'=>false, 'default'=>'',
                'hint'=>__('Text shown in before any input from the user'),
                'configuration'=>array('size'=>40, 'length'=>40,
                    'translatable'=>$this->getTranslateTag('placeholder')
                ),
            )),
        );
    }

    function validateEntry($value) {
        //check to see if value is the string '0'
        $value = ($value === '0') ? '&#48' : Format::htmlchars($this->toString($value ?: $this->value));
        parent::validateEntry($value);
        $config = $this->getConfiguration();
        $validators = array(
            '' => '',
            'noop' => array(
                function($a, &$b) { return true; }
            ),
            'formula' => array(array('Validator', 'is_formula'),
                __('Content cannot start with the following characters: = - + @')),
            'email' =>  array(array('Validator', 'is_valid_email'),
                __('Enter a valid email address')),
            'phone' =>  array(array('Validator', 'is_phone'),
                __('Enter a valid phone number')),
            'ip' =>     array(array('Validator', 'is_ip'),
                __('Enter a valid IP address')),
            'number' => array(array('Validator', 'is_numeric'),
                __('Enter a number')),
            'password' => array(array('Validator', 'check_passwd'),
                __('Invalid Password')),
            'regex' => array(
                function($v) use ($config) {
                    $regex = $config['regex'];
                    return @preg_match($regex, $v);
                }, $config['validator-error'] ?? __('Value does not match required pattern')
            ),
        );
        // Support configuration forms, as well as GUI-based form fields
        $valid = $this->get('validator');
        if (!$valid) {
            $valid = $config['validator'];
        }
        if (!$value || !isset($validators[$valid]))
            return;
        // If no validators are set and not an instanceof AdvancedSearchForm
        // force formula validation
        if (!$valid && !($this->getForm() instanceof AdvancedSearchForm))
            $valid = 'formula';
        $func = $validators[$valid];
        $error = $err = null;
        // If validator is number and the value is &#48 set to 0 (int) for is_numeric
        if ($valid == 'number' && $value == '&#48')
            $value = 0;
        if ($config['validator-error'])
            $error = $this->getLocal('validator-error', $config['validator-error']);
        if (is_array($func) && is_callable($func[0]))
            if (!call_user_func_array($func[0], array($value, &$err)))
                $this->_errors[] =  $error ?: $err ?: $func[1];
    }

    function parse($value) {
        return Format::strip_emoticons(Format::striptags($value));
    }

    function display($value) {
        return ($value === '0') ? '&#48;' : Format::htmlchars($this->toString($value ?: $this->value), true);
    }
}

class PasswordField extends TextboxField {
    static $widget = 'PasswordWidget';

    function __construct($options=array()) {
        parent::__construct($options);
        if (!isset($options['validator']))
            $this->set('validator', 'password');
    }

    protected function getMasterKey() {
        return SECRET_SALT;
    }

    protected function getSubKey() {
        $config = $this->getConfiguration();
        return $config['key'] ?: 'pwfield';
    }

    function parse($value) {
        // Don't trim the value
        return $value;
    }

    function to_database($value) {
        // If not set in UI, don't save the empty value
        if (!$value)
            throw new FieldUnchanged();
        return Crypto::encrypt($value, $this->getMasterKey(),
                $this->getSubKey());
    }

    function to_php($value) {
        return Crypto::decrypt($value, $this->getMasterKey(),
                $this->getSubKey());
    }
}

class TextareaField extends FormField {
    static $widget = 'TextareaWidget';

    function getConfigurationOptions() {
        return array(
            'cols'  =>  new TextboxField(array(
                'id'=>1, 'label'=>__('Width').' '.__('(chars)'), 'required'=>true, 'default'=>40)),
            'rows'  =>  new TextboxField(array(
                'id'=>2, 'label'=>__('Height').' '.__('(rows)'), 'required'=>false, 'default'=>4)),
            'length' => new TextboxField(array(
                'id'=>3, 'label'=>__('Max Length'), 'required'=>false, 'default'=>0)),
            'html' => new BooleanField(array(
                'id'=>4, 'label'=>__('HTML'), 'required'=>false, 'default'=>true,
                'configuration'=>array('desc'=>__('Allow HTML input in this box')))),
            'placeholder' => new TextboxField(array(
                'id'=>5, 'label'=>__('Placeholder'), 'required'=>false, 'default'=>'',
                'hint'=>__('Text shown in before any input from the user'),
                'configuration'=>array('size'=>40, 'length'=>40,
                    'translatable'=>$this->getTranslateTag('placeholder')),
            )),
        );
    }

    function validateEntry($value) {
        parent::validateEntry($value);
        if (!$value)
            return;
        $config = $this->getConfiguration();
        $validators = array(
            '' =>       array(array('Validator', 'is_formula'),
                __('Content cannot start with the following characters: = - + @')),
            'choices' => array(
                function($val) {
                    $val = str_replace('"', '', JsonDataEncoder::encode($val));
                    $regex = "/^(?! )[A-z0-9 _-]+:{1}[^\n]+$/";
                    foreach (explode('\r\n', $val) as $v) {
                        if (!preg_match($regex, $v))
                            return false;
                    }
                    return true;
                }, __('Each choice requires a key and has to be on a new line. (eg. key:value)')
            ),
        );
        // Support configuration forms, as well as GUI-based form fields
        if (!($valid = $this->get('validator')) && isset($config['validator']))
            $valid = $config['validator'];

        if (!isset($validators[$valid]))
            return;

        $func = $validators[$valid];
        $error = $func[1];
        if ($config['validator-error'])
            $error = $this->getLocal('validator-error', $config['validator-error']);
        if (is_array($func) && is_callable($func[0]))
            if (!call_user_func($func[0], $value))
                $this->_errors[] = $error;
    }

    function display($value) {
        $config = $this->getConfiguration();
        if ($config['html'])
            return Format::safe_html($value);
        else
            return nl2br(Format::htmlchars($value, true));
    }

    function searchable($value) {
        $body = new HtmlThreadEntryBody($value);
        return $body->getSearchable();
    }

    function export($value) {
        return (!$value) ? $value : Format::html2text($value);
    }

    function parse($value) {
        $config = $this->getConfiguration();
        if ($config['html'])
            return Format::sanitize($value);
        else
            return $value;
    }

}

class PhoneField extends FormField {
    static $widget = 'PhoneNumberWidget';

    function getConfigurationOptions() {
        return array(
            'ext' => new BooleanField(array(
                'label'=>__('Extension'), 'default'=>true,
                'configuration'=>array(
                    'desc'=>__('Add a separate field for the extension'),
                ),
            )),
            'digits' => new TextboxField(array(
                'label'=>__('Minimum length'), 'default'=>7,
                'hint'=>__('Fewest digits allowed in a valid phone number'),
                'configuration'=>array('validator'=>'number', 'size'=>5),
            )),
            'format' => new ChoiceField(array(
                'label'=>__('Display format'), 'default'=>'us',
                'choices'=>array(''=>'-- '.__('Unformatted').' --',
                    'us'=>__('United States')),
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
            $this->_errors[] = __("Enter a valid phone number");
        if ($ext && $config['ext']) {
            if (!is_numeric($ext))
                $this->_errors[] = __("Enter a valid phone extension");
            elseif (!$phone)
                $this->_errors[] = __("Enter a phone number for the extension");
        }
    }

    function parse($value) {
        // NOTE: Value may have a legitimate 'X' to separate the number and
        // extension parts. Don't remove the 'X'
        $val = preg_replace('/[^\dX]/', '', $value);
        // Pass completely-incorrect string for validation error
        return $val ?: $value;
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
                'id'=>1, 'label'=>__('Description'), 'required'=>false, 'default'=>'',
                'hint'=>__('Text shown inline with the widget'),
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
        return ($value) ? __('Yes') : __('No');
    }

    function getClean($validate=true) {
        if (!isset($this->_clean)) {
            $this->_clean = (isset($this->value))
                ? $this->value : $this->getValue();

            if ($this->isVisible() && $validate)
                $this->validateEntry($this->_clean);
        }
        return $this->_clean;
    }

    function getSearchMethods() {
        return array(
            'set' =>        __('checked'),
            'nset' =>    __('unchecked'),
        );
    }

    function describeSearchMethod($method) {

        $methods = $this->get('descsearchmethods');
        if (isset($methods[$method]))
            return $methods[$method];

        return parent::describeSearchMethod($method);
    }

    function getSearchMethodWidgets() {
        return array(
            'set' => null,
            'nset' => null,
        );
    }

    function getSearchQ($method, $value, $name=false) {
        $name = $name ?: $this->get('name');
        switch ($method) {
        case 'set':
            return new Q(array($name => '1'));
        case 'nset':
            return new Q(array($name => '0'));
        default:
            return parent::getSearchQ($method, $value, $name);
        }
    }

    function supportsQuickFilter() {
        return true;
    }

    function getQuickFilterChoices() {
        return array(
            true => __('Checked'),
            false => __('Not Checked'),
        );
    }

    function applyQuickFilter($query, $qf_value, $name=false) {
        return $query->filter(array(
            $name ?: $this->get('name') => (int) $qf_value,
        ));
    }
}

class ChoiceField extends FormField {
    static $widget = 'ChoicesWidget';
    var $_choices;

    function getConfigurationOptions() {
        return array(
            'choices'  =>  new TextareaField(array(
                'id'=>1, 'label'=>__('Choices'), 'required'=>false, 'default'=>'',
                'hint'=>__('List choices, one per line. To protect against spelling changes, specify key:value names to preserve entries if the list item names change.</br><b>Note:</b> If you have more than two choices, use a List instead.'),
                'validator'=>'choices',
                'configuration'=>array(
                    'html' => false,
                    'disabled' => false,
                    )
            )),
            'default' => new TextboxField(array(
                'id'=>3, 'label'=>__('Default'), 'required'=>false, 'default'=>'',
                'hint'=>__('(Enter a key). Value selected from the list initially'),
                'configuration'=>array('size'=>20, 'length'=>40),
            )),
            'prompt' => new TextboxField(array(
                'id'=>2, 'label'=>__('Prompt'), 'required'=>false, 'default'=>'',
                'hint'=>__('Leading text shown before a value is selected'),
                'configuration'=>array('size'=>40, 'length'=>40,
                    'translatable'=>$this->getTranslateTag('prompt'),
                ),
            )),
            'multiselect' => new BooleanField(array(
                'id'=>1, 'label'=>'Multiselect', 'required'=>false, 'default'=>false,
                'configuration'=>array(
                    'desc'=>'Allow multiple selections')
            )),
        );
    }

    function parse($value) {
        return $this->to_php($value ?: null);
    }

    function to_database($value) {
        if (!is_array($value)) {
            $choices = $this->getChoices();
            if (isset($choices[$value]))
                $value = array($value => $choices[$value]);
        }
        if (is_array($value))
            $value = JsonDataEncoder::encode($value);

        return $value;
    }

    function to_php($value) {
        if (is_string($value))
            $value = JsonDataParser::parse($value) ?: $value;

        // CDATA table may be built with comma-separated key,value,key,value
        if (is_string($value) && strpos($value, ',')) {
            $values = array();
            $choices = $this->getChoices();
            $vals = array_map('trim', explode(',', $value));
            foreach ($vals as $V) {
                if (isset($choices[$V]))
                    $values[$V] = $choices[$V];
            }
            if (array_filter($values))
                $value = $values;
            elseif($vals)
                list($value) = $vals;

        }
        $config = $this->getConfiguration();
        if (!$config['multiselect'] && is_array($value) && count($value) < 2) {
            reset($value);
            $value = key($value);
        }
        return $value;
    }

    function toString($value) {
        if (!is_array($value))
            $value = $this->getChoice($value);
        if (is_array($value))
            return implode(', ', $value);
        return (string) $value;
    }

    function getKeys($value) {
        if (!is_array($value))
            $value = $this->getChoice($value);
        if (is_array($value))
            return implode(', ', array_keys($value));
        return (string) $value;
    }

    function asVar($value, $id=false) {
        $value = $this->to_php($value);
        return $this->toString($this->getChoice($value));
    }

    function getChanges() {
        $new = $this->to_database($this->getValue());
        $old = $this->to_database($this->answer ? $this->answer->getValue()
                : $this->get('default'));
        // Compare old and new
        return ($old == $new)
            ? false
            : array($old, $new);
    }

    function whatChanged($before, $after) {
        $B = (array) $before;
        $A = (array) $after;
        $added = array_diff($A, $B);
        $deleted = array_diff($B, $A);
        $added = array_map(array($this, 'display'), $added);
        $deleted = array_map(array($this, 'display'), $deleted);
        $added = array_filter($added);
        $deleted = array_filter($deleted);

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
                __('changed from <strong>%1$s</strong> to <strong>%2$s</strong>'),
                $this->display($before), $this->display($after));
        }
        return $desc;
    }

    /*
     Return criteria to which the choice should be filtered by
     */
    function getCriteria() {
        $config = $this->getConfiguration();
        $criteria = array();
        if (isset($config['criteria']))
            $criteria = $config['criteria'];

        return $criteria;
    }

    function getChoice($value) {

        $choices = $this->getChoices();
        $selection = array();
        if ($value && is_array($value)) {
            $selection = $value;
        } elseif (isset($choices[$value]))
            $selection[$value] = $choices[$value];
        elseif (($v=$this->get('default')) && isset($choices[$v]))
            $selection[$v] = $choices[$v];

        return $selection;
    }

    function getChoices($verbose=false, $options=array()) {
        if ($this->_choices === null || $verbose) {
            // Allow choices to be set in this->ht (for configurationOptions)
            $this->_choices = $this->get('choices');
            if (!$this->_choices) {
                $this->_choices = array();
                $config = $this->getConfiguration();
                $choices = explode("\n", $config['choices']);
                foreach ($choices as $choice) {
                    // Allow choices to be key: value
                    list($key, $val) = explode(':', $choice, 2);
                    if ($val == null)
                        $val = $key;
                    $this->_choices[trim($key)] = trim($val);
                }
                // Add old selections if nolonger available
                // This is necessary so choices made previously can be
                // retained
                $values = ($a=$this->getAnswer()) ? $a->getValue() : array();
                if ($values && is_array($values)) {
                    foreach ($values as $k => $v) {
                        if (!isset($this->_choices[$k])) {
                            if ($verbose) $v .= ' (retired)';
                            $this->_choices[$k] = $v;
                        }
                    }
                }
            }
        }
        return $this->_choices;
    }

    function lookupChoice($value) {
        return null;
    }

    function getSearchMethods() {
        return array(
            'set' =>        __('has a value'),
            'nset' =>     __('does not have a value'),
            'includes' =>   __('includes'),
            '!includes' =>  __('does not include'),
        );
    }

    function getSearchMethodWidgets() {
        return array(
            'set' => null,
            'nset' => null,
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
        $val = $value;
        if ($value && is_array($value))
            $val = '"?(?<![0-9])'.implode('("|,|$)|"?(?<![0-9])', array_keys($value)).'("|,|$)';
        switch ($method) {
        case '!includes':
            return Q::not(array("{$name}__regex" => $val));
        case 'includes':
            return new Q(array("{$name}__regex" => $val));
        default:
            return parent::getSearchQ($method, $value, $name);
        }
    }

    function describeSearchMethod($method) {
        switch ($method) {
        case 'includes':
            return __('%s includes %s' /* includes -> if a list includes a selection */);
        case '!includes':
            return __('%s does not include %s' /* includes -> if a list includes a selection */);
        default:
            return parent::describeSearchMethod($method);
        }
    }

    function supportsQuickFilter() {
        return true;
    }

    function getQuickFilterChoices() {
        return $this->getChoices();
    }

    function applyQuickFilter($query, $qf_value, $name=false) {
        global $thisstaff;

        $field = new AssigneeChoiceField();
        //special assignment quick filters
        switch (true) {
            case ($qf_value == 'assigned'):
            case ($qf_value == '!assigned'):
                $result = $field->getSearchQ($qf_value, $qf_value);
                return $query->filter($result);
            case (strpos($qf_value, 's') !== false):
            case (strpos($qf_value, 't') !== false):
            case ($qf_value == 'M'):
            case ($qf_value == 'T'):
                $value = array($qf_value => $qf_value);
                $result = $field->getSearchQ('includes', $value);
                return $query->filter($result);
                break;
        }

        return $query->filter(array(
            $name ?: $this->get('name') => $qf_value,
        ));
    }
}

class NumericField extends FormField {

    function getSearchMethods() {
        return array(
            'equal' =>   __('Equal'),
            'greater' =>  __('Greater Than'),
            'less' =>  __('Less Than'),
        );
    }

    function getSearchMethodWidgets() {
        return array(
            'equal' => array('TextboxField', array(
                    'configuration' => array(
                        'validator' => 'number',
                        'size' => 6
                        ),
            )),
            'greater' => array('TextboxField', array(
                    'configuration' => array(
                        'validator' => 'number',
                        'size' => 6
                        ),
            )),
            'less' => array('TextboxField', array(
                    'configuration' => array(
                        'validator' => 'number',
                        'size' => 6
                        ),
            )),
        );
    }

    function getSearchQ($method, $value, $name=false) {
        switch ($method) {
        case 'equal':
            return new Q(array(
                "{$name}__exact" => intval($value)
            ));
        break;
        case 'greater':
            return Q::any(array(
                "{$name}__gt" => intval($value)
            ));
        break;
        case 'less':
            return Q::any(array(
                "{$name}__lt" => intval($value)
            ));
        break;
        default:
            return parent::getSearchQ($method, $value, $name);
        }
    }
}

class DatetimeField extends FormField {
    static $widget = 'DatetimePickerWidget';

    var $min = null;
    var $max = null;

    static function intervals($count=2, $i='') {
        $intervals = array(
            'i' => _N('minute', 'minutes', $count),
            'h' => _N('hour', 'hours', $count),
            'd' => _N('day','days', $count),
            'w' => _N('week', 'weeks', $count),
            'm' => _N('month', 'months', $count),
        );
        return $i ? $intervals[$i] : $intervals;
    }

    static function periods($period='') {
        $periods = array(
                'td' => __('Today'),
                'yd' => __('Yesterday'),
                'tw' => __('This Week'),
                'tm' => __('This Month'),
                'tq' => __('This Quarter'),
                'ty' => __('This Year'),
                'lw' => __('Last Week'),
                'lm' => __('Last Month'),
                'lq' => __('Last Quarter'),
                'ly' => __('Last Year'),
        );
        return $period ? $periods[$period] : $periods;
    }

    // Get php DatateTime object of the field  - null if value is empty
    function getDateTime($value=null) {
        return Format::parseDateTime($value ?: $this->value);
    }

    // Get effective timezone for the field
    function getTimeZone() {
        global $cfg;

        $config = $this->getConfiguration();
        $timezone = new DateTimeZone($config['timezone'] ?:
                $cfg->getTimezone());

        return $timezone;
    }

    function getMinDateTime() {

        if (!isset($this->min)) {
            $config = $this->getConfiguration();
            $this->min = $config['min']
                ? Format::parseDateTime($config['min']) : false;
        }

        return $this->min;
    }

    function getMaxDateTime() {

        if (!isset($this->max)) {
            $config = $this->getConfiguration();
            $this->max = $config['max']
                ? Format::parseDateTime($config['max']) : false;
        }

        return $this->max;
    }

    static function getPastPresentLabels() {
      return array(__('Create Date'), __('Reopen Date'),
                    __('Close Date'), __('Last Update'));
    }

    function to_database($value) {
        // Store time in format given by Date Picker (DateTime::W3C)
        return $value;
    }

    function to_php($value) {

        if (!is_numeric($value) && strtotime($value) <= 0)
            return 0;

        return $value;
    }

    function display($value) {
        global $cfg;

        if (!$value || !($datetime = Format::parseDateTime($value)))
            return '';

        $config = $this->getConfiguration();
        $format = $config['format'] ?: false;
        if ($config['gmt'])
            return $this->format((int) $datetime->format('U'), $format);

        // Force timezone if field has one.
        if ($config['timezone']) {
            $timezone = new DateTimezone($config['timezone']);
            $datetime->setTimezone($timezone);
        }

        $value = $this->format($datetime->format('U'),
                $datetime->getTimezone()->getName(),
                $format);
        // No need to show timezone
        if (!$config['time'] || $format)
            return $value;

        // Display is NOT timezone aware show entry's timezone.
        return sprintf('%s (%s)',
                $value, $datetime->format('T'));
    }

    function from_query($row, $name=false) {
        $value = parent::from_query($row, $name);
        $timestamp = is_int($value) ? $value : (int) strtotime($value);
        return ($timestamp > 0) ? $timestamp : '';
    }

    function format($timestamp, $timezone=false, $format=false) {

        if (!$timestamp || $timestamp <= 0)
            return '';

        $config = $this->getConfiguration();
        if ($config['time'])
            $formatted = Format::datetime($timestamp, false, $format,  $timezone);
        else
            $formatted = Format::date($timestamp, false, $format, $timezone);

        return $formatted;
    }

    function toString($value) {
        if (is_array($value))
            return '';

        $timestamp = is_int($value) ? $value : (int) strtotime($value);
        if ($timestamp <= 0)
            return '';

        return $this->format($timestamp);
    }

    function asVar($value, $id=false) {
        global $cfg;

        if (!$value)
            return null;

        $datetime = $this->getDateTime($value);
        $config = $this->getConfiguration();
        if (!$config['gmt'] || !$config['time'])
            $timezone  = $datetime->getTimezone()->getName();
        else
            $timezone  = false;

        return  new FormattedDate($value, array(
                    'timezone'  =>  $timezone,
                    'format'    =>  $config['time'] ? 'long' : 'short'
                    )
                );
    }

    function asVarType() {
        return 'FormattedDate';
    }

    function getConfigurationOptions() {
        return array(
            'time' => new BooleanField(array(
                'id'=>1, 'label'=>__('Time'), 'required'=>false, 'default'=>false,
                'configuration'=>array(
                    'desc'=>__('Show time selection with date picker')))),
            'timezone' => new TimezoneField(array(
                'id'=>2, 'label'=>__('Timezone'), 'required'=>false,
                'hint'=>__('Timezone of the date time selection'),
                'configuration' => array('autodetect'=>false,
                    'prompt' => __("User's timezone")),
               'visibility' => new VisibilityConstraint(
                    new Q(array('time__eq'=> true)),
                    VisibilityConstraint::HIDDEN
                ),
                )),
            'gmt' => new BooleanField(array(
                'id'=>3, 'label'=>__('Timezone Aware'), 'required'=>false,
                'configuration'=>array(
                    'desc'=>__("Show date/time relative to user's timezone")))),
            'min' => new DatetimeField(array(
                'id'=>4, 'label'=>__('Earliest'), 'required'=>false,
                'hint'=>__('Earliest date selectable'))),
            'max' => new DatetimeField(array(
                'id'=>5, 'label'=>__('Latest'), 'required'=>false,
                'default'=>null, 'hint'=>__('Latest date selectable'))),
            'future' => new BooleanField(array(
                'id'=>6, 'label'=>__('Allow Future Dates'), 'required'=>false,
                'default'=>true, 'configuration'=>array(
                    'desc'=>__('Allow entries into the future' /* Used in the date field */)),
            )),
        );
    }

    function validateEntry($value) {
        global $cfg;

        $config = $this->getConfiguration();
        parent::validateEntry($value);
        if (!$value || !($datetime = Format::parseDateTime($value)))
            return;

        // Get configured min/max (if any)
        $min = $this->getMinDateTime();
        $max = $this->getMaxDateTime();

        if (!$datetime) {
            $this->_errors[] = __('Enter a valid date');
        } elseif ($min and $datetime < $min) {
            $this->_errors[] = sprintf('%s (%s)',
                    __('Selected date is earlier than permitted'),
                     Format::date($min->getTimestamp(), false, false,
                         $min->getTimezone()->getName() ?: 'UTC')
                     );
        } elseif ($max and $datetime > $max) {
            $this->_errors[] = sprintf('%s (%s)',
                    __('Selected date is later than permitted'),
                    Format::date($max->getTimestamp(), false, false,
                        $max->getTimezone()->getName() ?: 'UTC')
                    );
        }
    }

    // SearchableField interface ------------------------------
    function getSearchMethods() {
        return array(
            'set' =>        __('has a value'),
            'nset' =>       __('does not have a value'),
            'equal' =>      __('on'),
            'nequal' =>     __('not on'),
            'before' =>     __('before'),
            'after' =>      __('after'),
            'between' =>    __('between'),
            'period' =>     __('period'),
            'ndaysago' =>   __('in the last n days'),
            'ndays' =>      __('in the next n days'),
            'future' =>     __('in the future'),
            'past' =>       __('in the past'),
            'distfut' =>    __('more than n days from now'),
            'distpast' =>   __('more than n days ago'),
        );
    }

    function getSearchMethodWidgets() {
        $config_notime = $config = $this->getConfiguration();
        $config_notime['time'] = false;
        $nday_form = function($x=5) {
            return array(
                'until' => new TextboxField(array(
                    'configuration' => array('validator'=>'number', 'size'=>4))
                ),
                'int' => new ChoiceField(array(
                    'default' => 'd',
                    'choices' => self::intervals($x),
                )),
            );
        };
        return array(
            'set' => null,
            'nset' => null,
            'past' => null,
            'future' => null,
            'equal' => array('DatetimeField', array(
                'configuration' => $config_notime,
            )),
            'nequal' => array('DatetimeField', array(
                'configuration' => $config_notime,
            )),
            'before' => array('DatetimeField', array(
                'configuration' => $config,
            )),
            'after' => array('DatetimeField', array(
                'configuration' => $config,
            )),
            'between' => array('InlineformField', array(
                'form' => array(
                    'left' => new DatetimeField($config + array('required' => true)),
                    'text' => new FreeTextField(array(
                        'configuration' => array('content' => __('and')))
                    ),
                    'right' => new DatetimeField($config + array('required' => true)),
                ),
                'configuration' => array(
                    'error' => '',
                ),
            )),
            'period' => array('ChoiceField', array(
                'choices' => self::periods(),
            )),
            'ndaysago' => array('InlineformField', array('form'=>$nday_form())),
            'ndays' => array('InlineformField', array('form'=>$nday_form())),
            'distfut' => array('InlineformField', array('form'=>$nday_form())),
            'distpast' => array('InlineformField', array('form'=>$nday_form())),
        );
    }

    function getSearchQ($method, $value, $name=false) {
        global $cfg;

        static $intervals = array(
            'm' => 'MONTH',
            'w' => 'WEEK',
            'd' => 'DAY',
            'h' => 'HOUR',
            'i' => 'MINUTE',
        );
        $name = $name ?: $this->get('name');
        $now = SqlFunction::NOW();
        $config = $this->getConfiguration();
       if (is_int($value))
          $value = DateTime::createFromFormat('U', !$config['gmt'] ? Misc::gmtime($value) : $value) ?: $value;
       elseif (is_string($value) && strlen($value) > 2)
           $value = Format::parseDateTime($value) ?: $value;

        switch ($method) {
        case 'equal':
            $l = clone $value;
            $r = $value->add(new DateInterval('P1D'));
            return new Q(array(
                "{$name}__gte" => $l,
                "{$name}__lt" => $r
            ));
        case 'nequal':
            $l = clone $value;
            $r = $value->add(new DateInterval('P1D'));
            return Q::any(array(
                "{$name}__lt" => $l,
                "{$name}__gte" => $r,
            ));
        case 'future':
            $value = $now;
        case 'after':
            return new Q(array("{$name}__gte" => $value));
        case 'past':
            $value = $now;
        case 'before':
            return new Q(array("{$name}__lt" => $value));
        case 'between':
            $left = Format::parseDateTime($value['left']);
            $right = Format::parseDateTime($value['right']);
            if (!$left || !$right)
                return null;

            // TODO: allow time selection for between
            $left = $left->setTime(00, 00, 00);
            $right = $right->setTime(23, 59, 59);
            // Convert time to db timezone
            $dbtz = new DateTimeZone($cfg->getDbTimezone());
            $left->setTimezone($dbtz);
            $right->setTimezone($dbtz);
            return new Q(array(
                "{$name}__gte" =>  $left->format('Y-m-d H:i:s'),
                "{$name}__lte" =>  $right->format('Y-m-d H:i:s'),
            ));
        case 'ndaysago':
            $int = $intervals[$value['int'] ?: 'd'] ?: 'DAY';
            $interval = new SqlInterval($int, $value['until']);
            return new Q(array(
                "{$name}__range" => array($now->minus($interval), $now),
            ));
        case 'ndays':
            $int = $intervals[$value['int'] ?: 'd'] ?: 'DAY';
            $interval = new SqlInterval($int, $value['until']);
            return new Q(array(
                "{$name}__range" => array($now, $now->plus($interval)),
            ));
        // Distant past and future ranges
        case 'distpast':
            $int = $intervals[$value['int'] ?: 'd'] ?: 'DAY';
            $interval = new SqlInterval($int, $value['until']);
            return new Q(array(
                "{$name}__lte" => $now->minus($interval),
            ));
        case 'distfut':
            $int = $intervals[$value['int'] ?: 'd'] ?: 'DAY';
            $interval = new SqlInterval($int, $value['until']);
            return new Q(array(
                "{$name}__gte" => $now->plus($interval),
            ));
        case 'period':
            // User's effective timezone
            $tz = new DateTimeZone($cfg->getTimezone());
            // Get the period range boundaries in user's tz
            $period = Misc::date_range($value, Misc::gmtime('now'), $tz);
            // Convert boundaries to db time
            $dbtz = new DateTimeZone($cfg->getDbTimezone());
            $start = $period->start->setTimezone($dbtz);
            $end = $period->end->setTimezone($dbtz);
            // Set the range
            return new Q(array(
                "{$name}__range" => array(
                    $start->format('Y-m-d H:i:s'),
                    $end->format('Y-m-d H:i:s')
                    )
                ));
            break;
        default:
            return parent::getSearchQ($method, $value, $name);
        }
    }

    function describeSearchMethod($method) {
        switch ($method) {
        case 'before':
            return __('%1$s before %2$s' /* occurs before a date and time */);
        case 'after':
            return __('%1$s after %2$s' /* occurs after a date and time */);
        case 'ndays':
            return __('%1$s in the next %2$s' /* occurs within a window (like 3 days) */);
        case 'ndaysago':
            return __('%1$s in the last %2$s' /* occurs within a recent window (like 3 days) */);
        case 'distfut':
            return __('%1$s after %2$s from now' /* occurs after a window (like 3 days) */);
        case 'distpast':
            return __('%1$s before %2$s ago' /* occurs previous to a window (like 3 days) */);
        case 'between':
            return __('%1$s between %2$s and %3$s');
        case 'future':
            return __('%1$s is in the future');
        case 'past':
            return __('%1$s is in the past');
        case 'period':
            return __('%1$s is %2$s');
        default:
            return parent::describeSearchMethod($method);
        }
    }

    function describeSearch($method, $value, $name=false) {

        $name = $name ?: $this->get('name');
        $desc = $this->describeSearchMethod($method);
        switch ($method) {
            case 'between':
                return sprintf($desc, $name,
                        $this->toString($value['left']),
                        $this->toString($value['right']));
            case 'ndays':
            case 'ndaysago':
            case 'distfut':
            case 'distpast':
                $interval = sprintf('%s %s', $value['until'],
                        self::intervals($value['until'], $value['int']));
                return sprintf($desc, $name, $interval);
                break;
            case 'future':
            case 'past':
                return sprintf($desc, $name);
            case 'before':
            case 'after':
                return sprintf($desc, $name, $this->toString($value));
            case 'period':
                return sprintf($desc, $name, self::periods($value) ?: $value);
            default:
                return parent::describeSearch($method, $value, $name);
        }
    }

    function supportsQuickFilter() {
        return true;
    }

    function getQuickFilterChoices() {
        return array(
            'h' => __('Today'),
            'm' => __('Tomorrow'),
            'g' => __('Yesterday'),
            'l7' => __('Last 7 days'),
            'l30' => __('Last 30 days'),
            'n7' => __('Next 7 days'),
            'n30' => __('Next 30 days'),
            /* Ugh. These boundaries are so difficult in SQL
            'w' =>  __('This Week'),
            'm' =>  __('This Month'),
            'lw' => __('Last Week'),
            'lm' => __('Last Month'),
            'nw' => __('Next Week'),
            'nm' => __('Next Month'),
            */
        );
    }

    function applyQuickFilter($query, $qf_value, $name=false) {
        $name = $name ?: $this->get('name');
        $now = SqlFunction::NOW();
        $midnight = Misc::dbtime(time() - (time() % 86400));
        switch ($qf_value) {
        case 'l7':
            return $query->filter([
                "{$name}__range" => array($now->minus(SqlInterval::DAY(7)), $now),
            ]);
        case 'l30':
            return $query->filter([
                "{$name}__range" => array($now->minus(SqlInterval::DAY(30)), $now),
            ]);
        case 'n7':
            return $query->filter([
                "{$name}__range" => array($now, $now->plus(SqlInterval::DAY(7))),
            ]);
        case 'n30':
            return $query->filter([
                "{$name}__range" => array($now, $now->plus(SqlInterval::DAY(30))),
            ]);
        case 'g':
            $midnight -= 86400;
             // Fall through to the today case
        case 'm':
            if ($qf_value === 'm') $midnight += 86400;
             // Fall through to the today case
        case 'h':
            $midnight = DateTime::createFromFormat('U', $midnight);
            return $query->filter([
                "{$name}__range" => array($midnight,
                    SqlExpression::plus($midnight, SqlInterval::DAY(1))),
            ]);
        }
    }
}


/**
 * TimeField for time selection
 *
 */
class TimeField extends FormField {
    static $widget = 'TimePickerWidget';

    var $start = null;
    var $end = null;

    function getTimeZone() {
        global $cfg;
        $config = $this->getConfiguration();
        return new DateTimeZone($config['timezone'] ?: $cfg->getTimezone());
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

    function isEditableToStaff() {
        return $this->isVisibleToStaff();
    }

    function isEditableToUsers() {
        return $this->isVisibleToUsers();
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
    function getMedia() {
        $config = $this->getConfiguration();
        $media = parent::getMedia() ?: array();
        if ($config['attachments'])
            $media = array_merge_recursive($media, FileUploadWidget::$media);
        return $media;
    }

    function getConfiguration() {
        global $cfg;
        $config = parent::getConfiguration();
        $config['html'] = (bool) ($cfg && $cfg->isRichTextEnabled());
        return $config;
    }

    function getConfigurationOptions() {
        global $cfg;

        $attachments = new FileUploadField();
        $fileupload_config = $attachments->getConfigurationOptions();
        if ($cfg->getAllowedFileTypes())
            $fileupload_config['extensions']->set('default', $cfg->getAllowedFileTypes());

        foreach ($fileupload_config as $C) {
            $C->set('visibility', new VisibilityConstraint(new Q(array(
                'attachments__eq'=>true,
            )), VisibilityConstraint::HIDDEN));
        }
        return array(
            'attachments' => new BooleanField(array(
                'label'=>__('Enable Attachments'),
                'default'=>$cfg->allowAttachments(),
                'configuration'=>array(
                    'desc'=>__('Enables attachments, regardless of channel'),
                ),
                'validators' => function($self, $value) {
                    if (!ini_get('file_uploads'))
                        $self->addError(__('The "file_uploads" directive is disabled in php.ini'));
                }
            )),
        )
        + $fileupload_config;
    }

    function isAttachmentsEnabled() {
        $config = $this->getConfiguration();
        return $config['attachments'];
    }

    function getWidget($widgetClass=false) {
        if ($hint = $this->getLocal('hint'))
            $this->set('placeholder', Format::striptags($hint));
        $this->set('hint', null);
        $widget = parent::getWidget($widgetClass);
        return $widget;
    }
}

class TopicField extends ChoiceField {
    var $topics;
    var $_choices;

    function getTopics() {
        global $thisstaff;

        if (!isset($this->topics))
            $this->topics = $thisstaff->getTopicNames();

        return $this->topics;
    }

    function getTopic($id) {
        if ($this->getTopics() &&
                isset($this->topics[$id]))
            return Topic::lookup($id);
    }

    function getWidget($widgetClass=false) {
        $default = $this->get('default');
        $widget = parent::getWidget($widgetClass);
        if ($widget->value instanceof Topic)
            $widget->value = $widget->value->getId();
        elseif (!isset($widget->value) && $default)
            $widget->value = $default;
        return $widget;
    }

    function hasIdValue() {
        return true;
    }

    function getChoices($verbose=false, $options=array()) {
        if (!isset($this->_choices)) {
            $this->_choices = $this->getTopics();
        }

        return $this->_choices;
    }

    function parse($id) {
        return $this->to_php(null, $id);
    }

    function to_php($value, $id=false) {
        if ($value instanceof Topic)
            return $value;
        if (is_array($id)) {
            reset($id);
            $id = key($id);
        }
        elseif (is_array($value))
            list($value, $id) = $value;
        elseif ($id === false)
            $id = $value;

        return $this->getTopic($id);
    }

    function to_database($topic) {
        return ($topic instanceof Topic)
            ? array($topic->getName(), $topic->getId())
            : $topic;
    }

    function display($topic, &$styles=null) {
        if (!$topic instanceof Topic)
            return parent::display($topic);

        return Format::htmlchars($topic->getName());
    }

    function toString($value) {
        if (!($value instanceof Topic) && is_numeric($value))
            $value = $this->getTopic($value);

        return ($value instanceof Topic) ? $value->getName() : $value;
    }

    function whatChanged($before, $after) {
        return parent::whatChanged($before, $after);
    }

    function searchable($value) {
        return null;
    }

    function getKeys($value) {
        return ($value instanceof Topic) ? array($value->getId()) : null;
    }

    function asVar($value, $id=false) {
        return $this->to_php($value, $id);
    }

    function getConfiguration() {
        global $cfg;

        $config = parent::getConfiguration();
        if (!isset($config['default']))
            $config['default'] = $cfg->getDefaultTopicId();
        return $config;
    }
}

class SLAField extends ChoiceField {
    var $slas;
    var $_choices;

    function getSLAs() {
        if (!isset($this->slas))
            $this->slas = SLA::objects();

        return $this->slas;
    }

    function getSLA($id) {
        if ($this->getSLAs() &&
                ($s=$this->slas->findFirst(array('id' => $id))))
            return $s;

        return SLA::lookup($id);
    }

    function getWidget($widgetClass=false) {
        $default = $this->get('default');
        $widget = parent::getWidget($widgetClass);
        if ($widget->value instanceof SLA)
            $widget->value = $widget->value->getId();
        elseif (!isset($widget->value) && $default)
            $widget->value = $default;
        return $widget;
    }

    function hasIdValue() {
        return true;
    }

    function getChoices($verbose=false, $options=array()) {
        if (!isset($this->_choices)) {
            $choices = array();
            foreach ($this->getSLAs() as $s)
                $choices[$s->getId()] = $s->getName();
            $this->_choices = $choices;
        }

        return $this->_choices;
    }

    function parse($id) {
        return $this->to_php(null, $id);
    }

    function to_php($value, $id=false) {
        if ($value instanceof SLA)
            return $value;
        if (is_array($id)) {
            reset($id);
            $id = key($id);
        }
        elseif (is_array($value))
            list($value, $id) = $value;
        elseif ($id === false)
            $id = $value;

        return $this->getSLA($id);
    }

    function to_database($sla) {
        return ($sla instanceof SLA)
            ? array($sla->getName(), $sla->getId())
            : $sla;
    }

    function display($sla, &$styles=null) {
        if (!$sla instanceof SLA)
            return parent::display($sla);

        return Format::htmlchars($sla->getName());
    }

    function toString($value) {
        if (!($value instanceof SLA) && is_numeric($value))
            $value = $this->getSLA($value);
        return ($value instanceof SLA) ? $value->getName() : $value;
    }

    function whatChanged($before, $after) {
        return parent::whatChanged($before, $after);
    }

    function searchable($value) {
        return null;
    }

    function getKeys($value) {
        return ($value instanceof SLA) ? array($value->getId()) : null;
    }

    function asVar($value, $id=false) {
        return $this->to_php($value, $id);
    }

    function getConfiguration() {
        global $cfg;

        $config = parent::getConfiguration();
        if (!isset($config['default']))
            $config['default'] = $cfg->getDefaultSLAId();
        return $config;
    }
}

class PriorityField extends ChoiceField {

    var $priorities;
    var $_choices;

    function getPriorities() {
        if (!isset($this->priorities))
            $this->priorities = Priority::objects();

        return $this->priorities;
    }

    function getPriority($id) {

        if ($this->getPriorities() &&
                ($p=$this->priorities->findFirst(array('priority_id' =>
                                                       $id))))
            return $p;

        return Priority::lookup($id);
    }

    function getWidget($widgetClass=false) {
        $widget = parent::getWidget($widgetClass);
        if ($widget->value instanceof Priority)
            $widget->value = $widget->value->getId();
        return $widget;
    }

    function hasIdValue() {
        return true;
    }

    function getChoices($verbose=false, $options=array()) {
        if (!isset($this->_choices)) {
            $choices = array();
            foreach ($this->getPriorities() as $p)
                $choices[$p->getId()] = $p->getDesc();
            $this->_choices = $choices;
        }

        return $this->_choices;
    }

    function parse($id) {
        return $this->to_php(null, $id);
    }

    function to_php($value, $id=false) {
        if ($value instanceof Priority)
            return $value;

        if (is_array($id)) {
            reset($id);
            $id = key($id);
        } elseif (is_array($value)) {
            list($value, $id) = $value;
        } elseif ($id === false && is_numeric($value))
            $id = $value;

        if (is_numeric($id))
            return $this->getPriority($id);

        return $value;
    }

    function to_database($value) {
        if ($value instanceof Priority)
            return array($value->getDesc(), $value->getId());

        if (is_array($value))
            return array(current($value), key($value));

        return $value;
    }

    function display($prio, &$styles=null) {
        if (!$prio instanceof Priority)
            return parent::display($prio);
        if (is_array($styles))
            $styles += array(
                'background-color' => $prio->getColor()
            );
        return Format::htmlchars($prio->getDesc());
    }

    function toString($value) {
        return ($value instanceof Priority) ? $value->getDesc() : $value;
    }

    function whatChanged($before, $after) {
        return parent::whatChanged($before, $after);
    }

    function searchable($value) {
        // Priority isn't searchable this way
        return null;
    }

    function getKeys($value) {
        return ($value instanceof Priority) ? array($value->getId()) : null;
    }

    function asVar($value, $id=false) {
        return $this->to_php($value, $id);
    }

    function getConfigurationOptions() {
        $choices = $this->getChoices();
        return array(
            'prompt' => new TextboxField(array(
                'id'=>2, 'label'=>__('Prompt'), 'required'=>false, 'default'=>'',
                'hint'=>__('Leading text shown before a value is selected'),
                'configuration'=>array('size'=>40, 'length'=>40),
            )),
            'default' => new ChoiceField(array(
                'id'=>3, 'label'=>__('Default'), 'required'=>false, 'default'=>'',
                'choices' => $choices,
                'hint'=>__('Default selection for this field'),
                'configuration'=>array('size'=>20, 'length'=>40),
            )),
        );
    }

    function getConfiguration() {
        global $cfg;

        $config = parent::getConfiguration();
        if (!isset($config['default']))
            $config['default'] = $cfg->getDefaultPriorityId();
        return $config;
    }

    function applyOrderBy($query, $reverse=false, $name=false) {
        if ($query->model == 'Ticket' && $name == 'cdata__priority') {
            // Order by the priority urgency field
            $col = 'cdata__:priority__priority_urgency';
            $reverse = !$reverse;
        }
        else {
            $col = $name ?: CustomQueue::getOrmPath($this->get('name'), $query);
        }
        if ($reverse)
            $col = "-$col";
        return $query->order_by($col);
    }
}
FormField::addFieldTypes(/*@trans*/ 'Dynamic Fields', function() {
    return array(
        'priority' => array(__('Priority Level'), 'PriorityField'),
    );
});


class TimezoneField extends ChoiceField {
    static $widget = 'TimezoneWidget';

    function hasIdValue() {
        return false;
    }

    function getChoices($verbose=false, $options=array()) {
        global $cfg;

        $choices = array();
        foreach (DateTimeZone::listIdentifiers() as $zone)
            $choices[$zone] =  str_replace('/',' / ',$zone);

        return $choices;
    }

    function whatChanged($before, $after) {
        return parent::whatChanged($before, $after);
    }

    function searchable($value) {
        return null;
    }

    function getConfigurationOptions() {
        return array(
            'autodetect' => new BooleanField(array(
                'id'=>1, 'label'=>__('Auto Detect'), 'required'=>false, 'default'=>true,
                'configuration'=>array(
                    'desc'=>__('Add Auto Detect Button'))
            )),
            'prompt' => new TextboxField(array(
                'id'=>2, 'label'=>__('Prompt'), 'required'=>false, 'default'=>'',
                'hint'=>__('Leading text shown before a value is selected'),
                'configuration'=>array('size'=>40, 'length'=>40),
            )),
        );
    }
}


class DepartmentField extends ChoiceField {
    function getWidget($widgetClass=false) {
        $widget = $this->_widget ?: parent::getWidget($widgetClass);
        if (is_object($widget->value))
            $widget->value = $widget->value->getId();
        return $widget;
    }

    function hasIdValue() {
        return true;
    }

    function getChoices($verbose=false, $options=array()) {
        global $cfg, $thisstaff;

        $config = $this->getConfiguration();
        $staff = $config['staff'] ?: $thisstaff;
        $selected = $this->getWidget();
        if($selected && $selected->value) {
          if(is_array($selected->value)) {
            foreach ($selected->value as $k => $v) {
              $current_id = $k;
              $current_name = $v;
            }
          }
          else {
            $current_id = $selected->value;
            $current_name = Dept::getNameById($current_id);
          }
        }

        $choices = array();

        //get all depts unfiltered
        $depts = $config['hideDisabled'] ? Dept::getDepartments(array('activeonly' => true)) :
            Dept::getDepartments(null, true, Dept::DISPLAY_DISABLED);

        //get staff depts based on permissions
        if ($staff) {
            $active = $staff->getDepartmentNames(true);

            if ($staff->hasPerm(Dept::PERM_DEPT))
                return $depts;
        }
        //filter custom department fields when there is no staff
        else {
            $userDepts = Dept::getDepartments(array('publiconly' => true, 'activeonly' => true));

            return $userDepts;
        }

         //add selected dept to list
         if($current_id)
            $active[$current_id] = $current_name;
         else
            return $active;

         foreach ($depts as $id => $name) {
            $choices[$id] = $name;
            if(!array_key_exists($id, $active) && $current_id)
                unset($choices[$id]);
         }

        return $choices;
    }

    function display($dept, &$styles=null) {
        if (!is_numeric($dept) && is_string($dept))
            return Format::htmlchars($dept);
        elseif ($dept instanceof Dept)
            return Format::htmlchars($dept->getName());

        return parent::display($dept);
    }

    function parse($id) {
        return $this->to_php(null, $id);
    }

    function getValue() {
         if (($value = parent::getValue()) && ($id=$this->getClean()))
            return $value[$id];
     }

    function to_php($value, $id=false) {
        if ($id) {
            if (is_array($id)) {
                reset($id);
                $id = key($id);
            }
            return $id;
        } else {
            return $value;
        }
    }

    function to_database($dept) {
        if ($dept instanceof Dept)
            return array($dept->getName(), $dept->getId());

        if (!is_array($dept)) {
            $choices = $this->getChoices();
            if (in_array($dept, $choices)) {
                $deptId = array_search($dept, $choices);
                $dept = array($dept, $deptId);
            }
         }

        return $dept ?: array();
    }

    function toString($value) {
        if (!is_array($value))
            $value = $this->getChoice($value);
        if (is_array($value))
            return implode(', ', $value);
        return (string) $value;
    }

    function getChoice($value) {
        $choices = $this->getChoices();
        $selection = array();
        if ($value && is_array($value)) {
            $selection = $value;
        } elseif (isset($choices[$value])) {
            $selection[] = $choices[$value];
        }

        return $selection;
    }

    function searchable($value) {
        return null;
    }

    function getConfigurationOptions() {
        return array(
            'prompt' => new TextboxField(array(
                'id'=>2, 'label'=>__('Prompt'), 'required'=>false, 'default'=>'',
                'hint'=>__('Leading text shown before a value is selected'),
                'configuration'=>array('size'=>40, 'length'=>40),
            )),
        );
    }
}
FormField::addFieldTypes(/*@trans*/ 'Dynamic Fields', function() {
    return array(
        'department' => array(__('Department'), 'DepartmentField'),
    );
});

class AssigneeField extends ChoiceField {
    var $_choices = null;
    var $_criteria = null;

    function getWidget($widgetClass=false) {
        $widget = parent::getWidget($widgetClass);
        $value = $widget->value;
        if (is_object($value)) {
            $id = $value->getId();
            if ($value instanceof Staff)
                $widget->value = 's'.$id;
            elseif ($value instanceof Team)
                $widget->value = 't'.$id;
        }
        return $widget;
    }

    function getCriteria() {
        if (!isset($this->_criteria)) {
            $this->_criteria = array('available' => true, 'namesOnly' => true);
            if (($c=parent::getCriteria()))
                $this->_criteria = array_merge($this->_criteria, $c);
        }

        return $this->_criteria;
    }

    function hasIdValue() {
        return true;
    }

    function setChoices($choices) {
        $this->_choices = $choices;
    }

    function display($value) {
        if ($this->getAnswer() && is_string($this->getAnswer()->value)) {
            $v = JsonDataParser::parse($this->getAnswer()->value);
            if (is_array($v))
                $value = $v[key($v)];
        }
        return $value;
    }

    function getAssignees($options=array()) {
        global $thisstaff;

        $config = $this->getConfiguration();
        $criteria = $this->getCriteria();
        $dept = $config['dept'] ?: null;
        $staff = $config['staff'] ?: $thisstaff;
        $assignees = array();
        switch (strtolower($config['target'])) {
        case 'agents':
            if ($dept)
                foreach ($dept->getAssignees(array('staff' => $staff)) as $a)
                    $assignees['s'.$a->getId()] = $a;
            else
                foreach (Staff::getStaffMembers(array('staff' => $staff)) as $id => $name)
                    $assignees['s'.$id] = $name;
            break;
        case 'teams':
            foreach (Team::getActiveTeams() ?: array() as $id => $name)
                $assignees['t'.$id] = $name;
            break;
        default:
            // both agents and teams
            $assignees = array(
                    __('Agents') => new ArrayObject(),
                    __('Teams') => new ArrayObject());
            $A = current($assignees);
            $criteria = $this->getCriteria();
            $agents = array();
            if ($dept)
                foreach ($dept->getAssignees(array('staff' => $staff)) as $a)
                    $A['s'.$a->getId()] = $a;
            else
                foreach (Staff::getStaffMembers(array('staff' => $staff)) as $a => $name)
                    $A['s'.$a] = $name;

            next($assignees);
            $T = current($assignees);
            if (($teams = Team::getActiveTeams()))
                foreach ($teams as $id => $name)
                    $T['t'.$id] = $name;
            break;
        }

        return $assignees;
    }

    function getChoices($verbose=false, $options=array()) {
        if (!isset($this->_choices))
            $this->_choices = $this->getAssignees($options);

        return $this->_choices;
    }

    function getChoice($value) {
        $choices = $this->getChoices();
        $selection = array();
        if ($value && is_object($value)) {
            $keys = null;
            if ($value instanceof Staff)
                $keys = array('Agents', 's'.$value->getId());
            elseif ($value instanceof Team)
                $keys = array('Teams', 't'.$value->getId());
            if ($keys && isset($choices[$keys[0]]))
                $selection = $choices[$keys[0]][$keys[1]];

            if (!empty($selection))
                return $selection;
        }

        return parent::getChoice($value);
    }

    function getQuickFilterChoices() {
        $choices = $this->getChoices();
        $names = array();
        foreach ($choices as $value) {
            foreach ($value as $key => $value)
                $names[$key] = is_object($value) ? $value->name : $value;
        }

        return $names;
    }

    function getValue() {
        if (($value = parent::getValue()) && ($id=$this->getClean())) {
            $name = (is_object($value[key($value)]) && get_class($value[key($value)]) == 'AgentsName') ?
                $value[key($value)]->name : $value[key($value)];
            $key = (($value[key($value)] instanceof AgentsName) ? 's' : 't').substr(key($value), 1);
            return array(array($key => $name), substr(key($value), 1));
        } else
            return null;
    }

    function parse($id) {
        return $this->to_php(null, $id);
    }

    function to_php($value, $id=false) {
        if (is_string($value))
            $value = JsonDataParser::parse($value) ?: $value;

        if (is_string($value) && strpos($value, ',')) {
            $values = array();
            list($key, $V) = array_map('trim', explode(',', $value));

            $values[$key] = $V;
            $value = $values;
        }

        $type = '';
        if (is_array($id)) {
            reset($id);
            $id = key($id);
            $type = $id[0];
            $id = substr($id, 1);
        }
        if (is_array($value)) {
            $type = key($value)[0];
            if (!$id)
                $id = substr(key($value), 1);
        }

        if (!$type && is_numeric($value))
            return Staff::lookup($value);

        switch ($type) {
        case 's':
            return Staff::lookup($id);
        case 't':
            return Team::lookup($id);
        case 'd':
            return Dept::lookup($id);
        default:
            return $id;
        }
    }

    function to_database($value) {
        if (is_object($value)) {
            $id = $value->getId();
            if ($value instanceof Staff) {
                $key = 's'.$id;
                $name = $value->getName()->name;
            } elseif ($value instanceof Team) {
                $key = 't'.$id;
                $name = $value->getName();
            }

            return JsonDataEncoder::encode(array($key => $name));
        }
        if (is_array($value)) {
            return JsonDataEncoder::encode($value[0]);
        }
        return $value;
    }

    function toString($value) {
        return (string) $value;
    }

    function searchable($value) {
        return null;
    }

    function getKeys($value) {
        $value = $this->to_database($value);
        if (is_array($value))
            return $value[0];

        return (string) $value;
    }

    function asVar($value, $id=false) {
        $v = $this->to_php($value, $id);
        return $v ? $v->getName() : null;
    }

    function getChanges() {
        $new = $this->to_database($this->getValue());
        $old = $this->to_database($this->answer ? $this->answer->getValue()
                : $this->get('default'));
        // Compare old and new
        return ($old == $new)
            ? false
            : array($old, $new);
    }

    function whatChanged($before, $after) {
        if ($before)
            $before = array($before->getName());
        if ($after)
            $after = array($after->getName());

        return parent::whatChanged($before, $after);
    }

    function getConfigurationOptions() {
        return array(
            'prompt' => new TextboxField(array(
                'id'=>2, 'label'=>__('Prompt'), 'required'=>false, 'default'=>'',
                'hint'=>__('Leading text shown before a value is selected'),
                'configuration'=>array('size'=>40, 'length'=>40),
            )),
        );
    }
}
FormField::addFieldTypes(/*@trans*/ 'Dynamic Fields', function() {
    return array(
        'assignee' => array(__('Assignee'), 'AssigneeField'),
    );
});


class TicketStateField extends ChoiceField {

    static $_states = array(
            'open' => array(
                'name' => /* @trans, @context "ticket state name" */ 'Open',
                'verb' => /* @trans, @context "ticket state action" */ 'Open'
                ),
            'closed' => array(
                'name' => /* @trans, @context "ticket state name" */ 'Closed',
                'verb' => /* @trans, @context "ticket state action" */ 'Close'
                )
            );
    // Private states
    static $_privatestates = array(
            'archived' => array(
                'name' => /* @trans, @context "ticket state name" */ 'Archived',
                'verb' => /* @trans, @context "ticket state action" */ 'Archive'
                ),
            'deleted'  => array(
                'name' => /* @trans, @context "ticket state name" */ 'Deleted',
                'verb' => /* @trans, @context "ticket state action" */ 'Delete'
                )
            );

    function hasIdValue() {
        return true;
    }

    function isChangeable() {
        return false;
    }

    function getChoices($verbose=false, $options=array()) {
        static $_choices;

        $states = static::$_states;
        if ($this->options['private_too'])
            $states += static::$_privatestates;

        if (!isset($_choices)) {
            // Translate and cache the choices
            foreach ($states as $k => $v)
                $_choices[$k] =  _P('ticket state name', $v['name']);

            $this->ht['default'] =  '';
        }

        return $_choices;
    }

    function getChoice($state) {

        if ($state && is_array($state))
            $state = key($state);

        if (isset(static::$_states[$state]))
            return _P('ticket state name', static::$_states[$state]['name']);

        if (isset(static::$_privatestates[$state]))
            return _P('ticket state name', static::$_privatestates[$state]['name']);

        return $state;
    }

    function getConfigurationOptions() {
        return array(
            'prompt' => new TextboxField(array(
                'id'=>2, 'label'=> __('Prompt'), 'required'=>false, 'default'=>'',
                'hint'=> __('Leading text shown before a value is selected'),
                'configuration'=>array('size'=>40, 'length'=>40),
            )),
        );
    }

    static function getVerb($state) {

        if (isset(static::$_states[$state]))
            return _P('ticket state action', static::$_states[$state]['verb']);

        if (isset(static::$_privatestates[$state]))
            return _P('ticket state action', static::$_privatestates[$state]['verb']);
    }
}
FormField::addFieldTypes('Dynamic Fields', function() {
    return array(
        'state' => array('Ticket State', 'TicketStateField', false),
    );
});

class TicketFlagField extends ChoiceField {

    // Supported flags (TODO: move to configurable custom list)
    static $_flags = array(
            'onhold' => array(
                'flag' => 1,
                'name' => 'Onhold',
                'states' => array('open'),
                ),
            'overdue' => array(
                'flag' => 2,
                'name' => 'Overdue',
                'states' => array('open'),
                ),
            'answered' => array(
                'flag' => 4,
                'name' => 'Answered',
                'states' => array('open'),
                )
            );

    var $_choices;

    function hasIdValue() {
        return true;
    }

    function isChangeable() {
        return true;
    }

    function getChoices($verbose=false, $options=array()) {
        $this->ht['default'] =  '';

        if (!$this->_choices) {
            foreach (static::$_flags as $k => $v)
                $this->_choices[$k] = $v['name'];
        }

        return $this->_choices;
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

FormField::addFieldTypes('Dynamic Fields', function() {
    return array(
        'flags' => array('Ticket Flags', 'TicketFlagField', false),
    );
});

class FileUploadField extends FormField {
    static $widget = 'FileUploadWidget';

    protected $attachments;
    protected $files;

    static function getFileTypes() {
        static $filetypes;

        if (!isset($filetypes)) {
            if (function_exists('apcu_fetch')) {
                $key = md5(SECRET_SALT . GIT_VERSION . 'filetypes');
                $filetypes = apcu_fetch($key);
            }
            if (!$filetypes)
                $filetypes = YamlDataParser::load(INCLUDE_DIR . '/config/filetype.yaml');
            if ($key)
                apcu_store($key, $filetypes, 7200);
        }
        return $filetypes;
    }

    function getConfigurationOptions() {
        // Compute size selections
        $sizes = array('262144' => '— '.__('Small').' —');
        $next = 512 << 10;
        $max = strtoupper(ini_get('upload_max_filesize'));
        $limit = (int) $max;
        if (!$limit) $limit = 2 << 20; # 2M default value
        elseif (strpos($max, 'K')) $limit <<= 10;
        elseif (strpos($max, 'M')) $limit <<= 20;
        elseif (strpos($max, 'G')) $limit <<= 30;
        while ($next <= $limit) {
            // Select the closest, larger value (in case the
            // current value is between two)
            $sizes[$next] = Format::file_size($next);
            $next *= 2;
        }
        // Add extra option if top-limit in php.ini doesn't fall
        // at a power of two
        if ($next < $limit * 2)
            $sizes[$limit] = Format::file_size($limit);

        $types = array();
        foreach (self::getFileTypes() as $type=>$info) {
            $types[$type] = $info['description'];
        }

        global $cfg;
        return array(
            'size' => new ChoiceField(array(
                'label'=>__('Maximum File Size'),
                'hint'=>__('Choose maximum size of a single file uploaded to this field'),
                'default'=>$cfg->getMaxFileSize(),
                'choices'=>$sizes
            )),
            'mimetypes' => new ChoiceField(array(
                'label'=>__('Restrict by File Type'),
                'hint'=>__('Optionally, choose acceptable file types.'),
                'required'=>false,
                'choices'=>$types,
                'configuration'=>array('multiselect'=>true,'prompt'=>__('No restrictions'))
            )),
            'extensions' => new TextareaField(array(
                'label'=>__('Additional File Type Filters'),
                'hint'=>__('Optionally, enter comma-separated list of additional file types, by extension. (e.g .doc, .pdf).'),
                'configuration'=>array('html'=>false, 'rows'=>2),
            )),
            'strictmimecheck' => new BooleanField([
                'id' => 4, 'label'=>__('Strict Mime Type Check'), 'required' => false, 'default' => false,
                'hint' => 'File Mime Type associations is OS dependent',
                'configuration' => ['desc' => __('Enable strict Mime Type check')]]),
            'max' => new TextboxField(array(
                'label'=>__('Maximum Files'),
                'hint'=>__('Users cannot upload more than this many files.'),
                'default'=>false,
                'required'=>false,
                'validator'=>'number',
                'configuration'=>array('size'=>8, 'length'=>4, 'placeholder'=>__('No limit')),
            ))
        );
    }

    /**
     * Called from the ajax handler for async uploads via web clients.
     */
    function ajaxUpload($bypass=false) {
        $config = $this->getConfiguration();

        $files = AttachmentFile::format($_FILES['upload'],
            // For numeric fields assume configuration exists
            !is_numeric($this->get('id')));
        if (count($files) != 1)
            Http::response(400, 'Send one file at a time');
        $file = array_shift($files);
        $file['name'] = urldecode($file['name']);
        $config = $this->getConfiguration();

        if (!self::isValidFile($file, $config['strictmimecheck']))
            Http::response(413, 'Invalid File');

        if (!$bypass && !$this->isValidFileType($file['name'], $file['type']))
            Http::response(415, 'File type is not allowed');

        $config = $this->getConfiguration();
        if (!$bypass && $file['size'] > $config['size'])
            Http::response(413, 'File is too large');

        if (!($F = AttachmentFile::upload($file)))
            Http::response(500, 'Unable to store file: '. $file['error']);

        $id = $F->getId();

        // This file is allowed for attachment in this session
        $_SESSION[':uploadedFiles'][$id] = $F->getName();

        return $id;
    }

    /**
     * Called from FileUploadWidget::getValue() when manual upload is used
     * for browsers which do not support the HTML5 way of uploading async.
     */
    function uploadFile($file) {
        if (!$this->isValidFileType($file['name'], $file['type']))
            throw new FileUploadError(__('File type is not allowed'));

        $config = $this->getConfiguration();

        if (!self::isValidFile($file, $config['strictmimecheck']))
             throw new FileUploadError(__('Invalid File'));

        if ($file['size'] > $config['size'])
            throw new FileUploadError(__('File size is too large'));

        return AttachmentFile::upload($file);
    }

    /**
     * Called from API and email routines and such to handle attachments
     * sent other than via web upload
     */
    function uploadAttachment(&$file) {
        if (!$this->isValidFileType($file['name'], $file['type']))
            throw new FileUploadError(__('File type is not allowed'));

        if (!isset($file['data']) && isset($file['data_cbk'])
                && is_callable($file['data_cbk']))
            $file['data'] = $file['data_cbk']();

        if (!isset($file['size']) && isset($file['data'])) {
            // bootstrap.php include a compat version of mb_strlen
            if (extension_loaded('mbstring'))
                $file['size'] = mb_strlen($file['data'], '8bit');
            else
                $file['size'] = strlen($file['data']);
        }

        $config = $this->getConfiguration();
        if ($file['size'] > $config['size'])
            throw new FileUploadError(__('File size is too large'));

        if (!$F = AttachmentFile::create($file))
            throw new FileUploadError(__('Unable to save file'));

        return $F;
    }

    /**
     * Strict mode can be enabled in Admin Panel > Settings > Tickets
     *
     * PS: Please note that the a mismatch can happen if the mime types
     * database is not up to date or a little different compared to what the
     * browser reports.
     **/
    static function isValidFile($file, $strict = false) {
        // Strict mime check
        if ($strict
            && !empty($file['type'])
            && FileObject::mimecmp($file['tmp_name'], $file['type']))
            return false;

        // Check invalid image hacks
        if ($file['tmp_name']
                && stripos($file['type'], 'image/') === 0
                && !exif_imagetype($file['tmp_name']))
            return false;

        return true;
    }

    function isValidFileType($name, $type=false) {
        $config = $this->getConfiguration();

        // Check MIME type - file ext. shouldn't be solely trusted.
        if ($type && $config['__mimetypes']
                && in_array($type, $config['__mimetypes'], true))
            return true;

        // Return true if all file types are allowed (.*)
        if (!$config['__extensions'] || in_array('.*', $config['__extensions']))
            return true;

        $allowed = $config['__extensions'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return ($ext && is_array($allowed) && in_array(".$ext", $allowed));
    }

    function getAttachments() {
        if (!isset($this->attachments) && ($a = $this->getAnswer())
            && ($e = $a->getEntry()) && ($e->get('id'))
        ) {
            $this->attachments = GenericAttachments::forIdAndType(
                // Combine the field and entry ids to make the key
                sprintf('%u', abs(crc32('E'.$this->get('id').$e->get('id')))),
                'E');
        }
        return $this->attachments ?: array();
    }

    function setAttachments(GenericAttachments $att) {
        $this->attachments = $att;
    }

    function getFiles() {
        if (!isset($this->files)) {
            $files = array();
            foreach ($this->getAttachments() as $a) {
                if ($a && ($f=$a->getFile()))
                    $files[] = $f;
            }

            foreach ($this->getClean(false) ?: array() as $key => $value)
                $files[] = array('id' => $key, 'name' => $value);

            $this->files = $files;
        }
        return $this->files;
    }

    function getConfiguration() {
        global $cfg;

        $config = parent::getConfiguration();
        // If no size present default to system setting
        $config['size'] ??= $cfg->getMaxFileSize();
        $_types = self::getFileTypes();
        $mimetypes = array();
        $extensions = array();
        if (isset($config['mimetypes']) && is_array($config['mimetypes'])) {
            foreach ($config['mimetypes'] as $type=>$desc) {
                foreach ($_types[$type]['types'] as $mime=>$exts) {
                    $mimetypes[$mime] = true;
                    if (is_array($exts))
                        foreach ($exts as $ext)
                            $extensions['.'.$ext] = true;
                }
            }
        }
        if (strpos($config['extensions'], '.*') !== false)
            $config['extensions'] = '';

        if (is_string($config['extensions'])) {
            foreach (preg_split('/\s+/', str_replace(',',' ', $config['extensions'])) as $ext) {
                if (!$ext) {
                    continue;
                }
                elseif (strpos($ext, '/')) {
                    $mimetypes[$ext] = true;
                }
                else {
                    if ($ext[0] != '.')
                        $ext = '.' . $ext;

                    // Ensure that the extension is lower-cased for comparison latr
                    $ext = strtolower($ext);

                    // Add this to the MIME types list so it can be exported to
                    // the @accept attribute
                    if (!isset($extensions[$ext]))
                        $mimetypes[$ext] = true;

                    $extensions[$ext] = true;
                }
            }
            $config['__extensions'] = array_keys($extensions);
        }
        elseif (is_array($config['extensions'])) {
            $config['__extensions'] = $config['extensions'];
        }

        // 'mimetypes' is the array represented from the user interface,
        // '__mimetypes' is a complete list of supported MIME types.
        $config['__mimetypes'] = array_keys($mimetypes);
        return $config;
    }

    // When the field is saved to database, encode the ID listing as a json
    // array. Then, inspect the difference between the files actually
    // attached to this field
    function to_database($value) {
        $this->getAttachments();
        if (isset($this->attachments) && $this->attachments) {
            $this->attachments->keepOnlyFileIds($value);
        }
        return JsonDataEncoder::encode($value) ?? NULL;
    }

    function parse($value) {
        return $value;
    }

    function to_php($value) {
        return is_array($value) ? $value : JsonDataParser::decode($value);
    }

    function display($value) {
        $links = array();
        foreach ($this->getAttachments() as $a) {
            $links[] = sprintf('<a class="no-pjax" href="%s"><i class="icon-paperclip icon-flip-horizontal"></i> %s</a>',
                Format::htmlchars($a->file->getDownloadUrl()),
                Format::htmlchars($a->getFilename()));
        }
        return implode('<br/>', $links);
    }

    function toString($value) {
        $files = array();
        foreach ($this->getFiles() as $f) {
            $files[] = $f->name;
        }
        return implode(', ', $files);
    }

    function db_cleanup($field=false) {
        if ($this->getAttachments()) {
            $this->attachments->deleteAll();
        }
    }

    function asVar($value, $id=false) {
        if (($attachments = $this->getAttachments()))
            $attachments = $attachments->all();

        return new FileFieldAttachments($attachments ?: array());
    }
    function asVarType() {
        return 'FileFieldAttachments';
    }

    function whatChanged($before, $after) {
        $B = (array) $before;
        $A = (array) $after;
        $added = array_diff($A, $B);
        $deleted = array_diff($B, $A);
        $added = Format::htmlchars(array_values($added));
        $deleted = Format::htmlchars(array_values($deleted));

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
                __('changed from <strong>%1$s</strong> to <strong>%2$s</strong>'),
                $this->display($before), $this->display($after));
        }
        return $desc;
    }
}

class FileFieldAttachments {
    var $attachments;

    function __construct($attachments) {
        $this->attachments = $attachments;
    }

    function __toString() {
        $files = array();
        foreach ($this->getAttachments() as $a) {
            $files[] = $a->getFilename();
        }
        return implode(', ', $files);
    }

    function getAttachments() {
        return $this->attachments ?: array();
    }

    function getVar($tag) {
        switch ($tag) {
        case 'names':
            return $this->__toString();
        case 'files':
            throw new OOBContent(OOBContent::FILES, $this->getAttachments());
        }
    }

    static function getVarScope() {
        return array(
            'names' => __('List of file names'),
            'files' => __('Attached files'),
        );
    }
}

class ColorChoiceField extends FormField {
    static $widget = 'ColorPickerWidget';
}

class InlineFormData extends ArrayObject {
    var $_form;

    function __construct($form, array $data=array()) {
        parent::__construct($data);
        $this->_form = $form;
    }

    function getVar($tag) {
        foreach ($this->_form->getFields() as $f) {
            if ($f->get('name') == $tag)
                return $this[$f->get('id')];
        }
    }
}


class InlineFormField extends FormField {
    static $widget = 'InlineFormWidget';

    var $_iform = null;

    function validateEntry($value) {
        if (!$this->getInlineForm()->isValid()) {
            $config = $this->getConfiguration();
            $this->_errors[] = isset($config['error'])
                ? $config['error'] : __('Correct any errors below and try again.');
        }
    }

    function parse($value) {
        // The InlineFieldWidget returns an array of cleaned data
        return $value;
    }

    function to_database($value) {
        return JsonDataEncoder::encode($value);
    }

    function to_php($value) {
        $data = JsonDataParser::decode($value);
        // The InlineFormData helps with the variable replacer API
        return new InlineFormData($this->getInlineForm(), $data);
    }

    function display($data) {
        $form = $this->getInlineForm();
        ob_start(); ?>
        <div><?php
        foreach ($form->getFields() as $field) { ?>
            <span style="display:inline-block;padding:0 5px;vertical-align:top">
                <strong><?php echo Format::htmlchars($field->get('label')); ?></strong>
                <div><?php
                    $value = $data[$field->get('id')];
                    echo $field->display($value); ?></div>
            </span><?php
        } ?>
        </div><?php
        return ob_get_clean();
    }

    function getInlineForm($data=false) {
        $form = $this->get('form');
        if (is_array($form)) {
            $form = new SimpleForm($form, $data ?: $this->value ?: $this->getSource());
            // Ensure unique, but predictable form and field IDs
            $form->setId(sprintf('%u', crc32($this->get('name')) >> 1));
        }
        return $form;
    }
}

class InlineDynamicFormField extends FormField {
    function getInlineForm($data=false) {
        if (!isset($this->_iform) || $data) {
            $config = $this->getConfiguration();
            $this->_iform = DynamicForm::lookup($config['form']);
            if ($data)
                $this->_iform = $this->_iform->getForm($data);
        }
        return $this->_iform;
    }

    function getConfigurationOptions() {
        $forms = DynamicForm::objects()->filter(array('type'=>'G'))
            ->values_flat('id', 'title');
        $choices = array();
        foreach ($forms as $row) {
            list($id, $title) = $row;
            $choices[$id] = $title;
        }
        return array(
            'form' => new ChoiceField(array(
                'id'=>2, 'label'=>'Inline Form', 'required'=>true,
                'default'=>'', 'choices'=>$choices
            )),
        );
    }
}

class InlineFormWidget extends Widget {
    function render($mode=false) {
        $form = $this->field->getInlineForm();
        if (!$form)
            return;
        // Handle first-step edits -- load data from $this->value
        if ($form instanceof DynamicForm && !$form->getSource())
            $form = $form->getForm($this->value);
        $inc = ($mode == 'client') ? CLIENTINC_DIR : STAFFINC_DIR;
        include $inc . 'templates/inline-form.tmpl.php';
    }

    function getValue() {
        $data = $this->field->getSource();
        if (!$data)
            return null;
        $form = $this->field->getInlineForm($data);
        if (!$form)
            return null;
        return $form->getClean();
    }
}

class Widget {
    static $media = null;

    function __construct($field) {
        $this->field = $field;
        $this->name = $field->getFormName();
        $this->id = '_' . $this->name;
    }

    function parseValue() {
        $this->value = $this->getValue();
        if (!isset($this->value) && is_object($this->field->getAnswer()))
            $this->value = $this->field->getAnswer()->getValue();
        if (!isset($this->value) && isset($this->field->value))
            $this->value = $this->field->value;
    }

    function getValue() {
        $data = $this->field->getSource();
        foreach ($this->field->getFormNames() as $name)
            if (isset($data[$name]))
                return $data[$name];

        return null;
    }

    /**
     * getJsValueGetter
     *
     * Used with the dependent fields feature, this function should return a
     * single javascript expression which can be used in a larger expression
     * (<> == true, where <> is the result of this function). The %s token
     * will be replaced with a jQuery variable representing this widget.
     */
    function getJsValueGetter($id='%s') {
        return sprintf('%s.val()', $id);
    }

    /**
     * getJsComparator
     *
     * Used with the dependent fields to get comparison expression
     *
     */
    function getJsComparator($value, $id) {

        if (strpos($value, '|') !== false)
            return sprintf('$.inArray(%s, %s) !== -1',
                   $this->getJsValueGetter($id),
                   JsonDataEncoder::encode(explode('|', $value)));

        return sprintf('%s == %s',
                $this->getJsValueGetter($id),
                JsonDataEncoder::encode($value));
    }
}

class TextboxWidget extends Widget {
    static $input_type = 'text';

    function render($options=array(), $extraConfig=false) {
        $config = $this->field->getConfiguration();
        if (is_array($extraConfig)) {
            foreach ($extraConfig as $k=>$v)
                if (!isset($config[$k]) || !$config[$k])
                    $config[$k] = $v;
        }

        // Input attributes
        $attrs = array();
        foreach ($config as $k => $v) {
            switch ($k) {
                case 'autocomplete':
                    if (is_numeric($v))
                        $v = $v ? 'on' : 'off';
                    $attrs[$k] = '"'.$v.'"';
                    break;
                case 'disabled';
                    $attrs[$k] = '"disabled"';
                    break;
                case 'translatable':
                    if ($v)
                        $attrs['data-translate-tag'] =  '"'.$v.'"';
                    break;
                case 'length':
                    $k = 'maxlength';
                case 'size':
                case 'maxlength':
                    if ($v && is_numeric($v))
                        $attrs[$k] = '"'.$v.'"';
                    break;
                case 'class':
                case 'classes':
                    $attrs['class'] = '"'.$v.'"';
                    break;
                case 'inputmode':
                case 'pattern':
                    $attrs[$k] = '"'.$v.'"';
                    break;
            }
        }
        // autofocus
        $autofocus = '';
        if (isset($config['autofocus']))
            $autofocus = 'autofocus';
        // placeholder
        $attrs['placeholder'] = sprintf('"%s"',
                Format::htmlchars($this->field->getLocal('placeholder',
                    $config['placeholder'])));
        $type = static::$input_type;
        $types = array(
            'email' => 'email',
            'phone' => 'tel',
        );
        if ($type == 'text' && isset($types[$config['validator']]))
            $type = $types[$config['validator']];
        ?>
        <input type="<?php echo $type; ?>"
            id="<?php echo $this->id; ?>"
            <?php echo $autofocus .' '.Format::array_implode('=', ' ',
                    array_filter($attrs)); ?>
            name="<?php echo $this->name; ?>"
            value="<?php echo Format::htmlchars($this->value, true); ?>"/>
        <?php
    }
}


class TextboxSelectionWidget extends TextboxWidget {
    //TODO: Support multi-input e.g comma separated inputs
    function render($options=array(), $extraConfig=array()) {

        if ($this->value && is_array($this->value))
            $this->value = current($this->value);

        parent::render($options);
    }

    function getValue() {

        $value = parent::getValue();
        if ($value && ($item=$this->field->lookupChoice((string) $value)))
            $value = $item;

        return $value;
    }
}

class PasswordWidget extends TextboxWidget {
    static $input_type = 'password';

    function render($mode=false, $extra=false) {
        $extra = array();
        if (isset($this->field->value)) {
            $extra['placeholder'] = str_repeat('•',
                    strlen($this->field->value));
        }
        return parent::render($mode, $extra);
    }

    function parseValue() {
        parent::parseValue();
        // Show empty box unless failed POST
        if ($_SERVER['REQUEST_METHOD'] != 'POST'
                || !$this->field->getForm()->isValid())
            $this->value = '';
    }
}

class TextareaWidget extends Widget {
    function render($options=array()) {
        $config = $this->field->getConfiguration();
        // process textarea attributes
        $attrs = array();
        foreach ($config as $k => $v) {
            switch ($k) {
                case 'rows':
                case 'cols':
                case 'length':
                case 'maxlength':
                    if ($v && is_numeric($v))
                        $attrs[$k] = '"'.$v.'"';;
                    break;
                case 'context':
                    $attrs['data-root-context'] =  '"'.$v.'"';
                    break;
                case 'class':
                    // This might conflict with html attr below
                    $attrs[$k] = '"'.$v.'"';
                    break;
                case 'html':
                    if ($v) {
                        $class = array('richtext', 'no-bar');
                        $class[] = @$config['size'] ?: 'small';
                        $attrs['class'] =  '"'.implode(' ', $class).'"';
                        $this->value = Format::viewableImages($this->value);
                    }
                    break;
            }
        }
        // placeholder
        $attrs['placeholder'] = sprintf('"%s"',
                Format::htmlchars($this->field->getLocal('placeholder',
                $config['placeholder'])));
        ?>
        <span style="display:inline-block;width:100%">
        <textarea <?php echo Format::array_implode('=', ' ',
                array_filter($attrs)); ?>
            id="<?php echo $this->id; ?>"
            name="<?php echo $this->name; ?>"><?php
                echo Format::htmlchars($this->value, ($config['html']));
            ?></textarea>
        </span>
        <?php
    }

    function parseValue() {
        parent::parseValue();
        if (isset($this->value)) {
            $value = $this->value;
            $config = $this->field->getConfiguration();
            // Trim empty spaces based on text input type.
            // Preserve original input if not empty.
            if ($config['html'])
                $this->value = trim($value, " <>br/\t\n\r") ? $value : '';
            else
                $this->value = trim($value) ? $value : '';
        }
    }

}

class PhoneNumberWidget extends Widget {
    function render($options=array()) {
        $config = $this->field->getConfiguration();
        list($phone, $ext) = explode("X", $this->value);
        ?>
        <input id="<?php echo $this->id; ?>" type="tel" name="<?php echo $this->name; ?>" value="<?php
        echo Format::htmlchars($phone); ?>"/><?php
        // Allow display of extension field even if disabled if the phone
        // number being edited has an extension
        if ($ext || $config['ext']) { ?> <?php echo __('Ext'); ?>:
            <input type="text" name="<?php
            echo $this->name; ?>-ext" value="<?php echo Format::htmlchars($ext);
                ?>" size="5"/>
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
    function render($options=array()) {
        $mode = null;
        if (isset($options['mode']))
            $mode = $options['mode'];
        elseif (isset($this->field->options['render_mode']))
            $mode = $this->field->options['render_mode'];

        if ($mode == 'view') {
            $val = (string) $this->field;
            echo sprintf('<span id="field_%s" %s >%s</span>', $this->id,
                    $val ? '': 'class="faded"',
                    $val ?: __('None'));
            return;
        }

        $config = $this->field->getConfiguration();
        if ($mode == 'search') {
            $config['multiselect'] = true;
        }

        // Determine the value for the default (the one listed if nothing is
        // selected)
        $choices = $this->field->getChoices(true, $options);
        $prompt = ($config['prompt'])
            ? $this->field->getLocal('prompt', $config['prompt'])
            : __('Select'
            /* Used as a default prompt for a custom drop-down list */);

        $have_def = false;
        // We don't consider the 'default' when rendering in 'search' mode
        if (!strcasecmp($mode, 'search')) {
            $def_val = $prompt;
        } else {
            $showdefault = true;
            if ($mode != 'create')
                 $showdefault = false;
            $def_key = $this->field->get('default');
            if (!$def_key && isset($config['default']))
                $def_key = $config['default'];
            if (is_array($def_key))
                $def_key = key($def_key);
            $have_def = isset($choices[$def_key]);
            $def_val = ($have_def && !$showdefault) ? $choices[$def_key] : $prompt;
        }

        $values = $this->value;
        if (!is_array($values) && isset($values)) {
            $values = array($values => $this->field->getChoice($values));
        }

        if (!is_array($values))
            $values = $have_def ? array($def_key => $choices[$def_key]) : array();

        if (isset($config['classes']))
            $classes = 'class="'.$config['classes'].'"';
        ?>
        <select name="<?php echo $this->name; ?>[]"
            <?php echo implode(' ', array_filter(array($classes))); ?>
            id="<?php echo $this->id; ?>"
            <?php if (isset($config['data']))
              foreach ($config['data'] as $D=>$V)
                echo ' data-'.$D.'="'.Format::htmlchars($V).'"';
            ?>
            data-placeholder="<?php echo Format::htmlchars($prompt); ?>"
            <?php if ($config['disabled'])
                echo ' disabled="disabled"'; ?>
            <?php if ($config['multiselect'])
                echo ' multiple="multiple"'; ?>>
            <?php if ($showdefault || (!$have_def && !$config['multiselect'])) { ?>
            <option value="<?php echo $showdefault ? '' : $def_key; ?>">&mdash; <?php
                echo $def_val; ?> &mdash;</option>
<?php
        }
        $this->emitChoices($choices, $values, $have_def, $def_key); ?>
        </select>
        <?php
        if ($config['multiselect']) {
         ?>
        <script type="text/javascript">
        $(function() {
            $("#<?php echo $this->id; ?>")
            .select2({'minimumResultsForSearch':10, 'width': '350px'});
        });
        </script>
       <?php
        }
    }

    function emitChoices($choices, $values=array(), $have_def=false, $def_key=null) {
        reset($choices);
        if (is_array(current($choices)) || current($choices) instanceof Traversable)
            return $this->emitComplexChoices($choices, $values, $have_def, $def_key);

        foreach ($choices as $key => $name) {
            if (!$have_def && $key === $def_key)
                continue; ?>
            <option value="<?php echo $key; ?>" <?php
                if (isset($values[$key])) echo 'selected="selected"';
            ?>><?php echo Format::htmlchars($name); ?></option>
        <?php
        }
    }

    function emitComplexChoices($choices, $values=array(), $have_def=false, $def_key=null) {
        foreach ($choices as $label => $group) {
            if (!count($group)) continue;
            ?>
            <optgroup label="<?php echo $label; ?>"><?php
            foreach ($group as $key => $name) {
                if (!$have_def && $key == $def_key)
                    continue; ?>
            <option value="<?php echo $key; ?>" <?php
                if (isset($values[$key])) echo 'selected="selected"';
            ?>><?php echo Format::htmlchars($name); ?></option>
<?php       } ?>
            </optgroup><?php
        }
    }

    function getValue() {

        if (!($value = parent::getValue()))
            return null;

        if ($value && !is_array($value))
            $value = array($value);

        // Assume multiselect
        $values = array();
        $choices = $this->field->getChoices();

        if ($choices && is_array($value)) {
            // Complex choices
            if (is_array(current($choices))
                    || current($choices) instanceof Traversable) {
                foreach ($choices as $label => $group) {
                     foreach ($group as $k => $v)
                        if (in_array($k, $value))
                            $values[$k] = $v;
                }
            } else {
                foreach($value as $k => $v) {
                    if (isset($choices[$v]))
                        $values[$v] = $choices[$v];
                    elseif (($i=$this->field->lookupChoice($v)))
                        $values += $i;
                    elseif (!$k && $v)
                      return $v;
                }
            }
        }

        return $values;
    }

    function getJsValueGetter($id='%s') {
        return sprintf('%s.find(":selected").val()', $id);
    }

}

/**
 * A widget for the ChoiceField which will render a list of radio boxes or
 * checkboxes depending on the value of $config['multiple']. Complex choices
 * are also supported and will be rendered as divs.
 */
class BoxChoicesWidget extends Widget {
    function render($options=array()) {
        $this->emitChoices($this->field->getChoices());
    }

    function emitChoices($choices) {
      static $uid = 1;

      if (!isset($this->value))
          $this->value = $this->field->get('default');
      $config = $this->field->getConfiguration();
      $type = $config['multiple'] ? 'checkbox' : 'radio';

      $classes = array('checkbox');
      if (isset($config['classes']))
          $classes = array_merge($classes, (array) $config['classes']);

      foreach ($choices as $k => $v) {
          if (is_array($v)) {
              $this->renderSectionBreak($k);
              $this->emitChoices($v);
              continue;
          }
          $id = sprintf("%s-%s", $this->id, $uid++);
?>
        <label class="<?php echo implode(' ', $classes); ?>"
            for="<?php echo $id; ?>">
        <input id="<?php echo $id; ?>" type="<?php echo $type; ?>"
            name="<?php echo $this->name; ?>[]" <?php
            if ($this->value[$k]) echo 'checked="checked"'; ?> value="<?php
            echo Format::htmlchars($k); ?>"/>
        <?php
        if ($v) {
            echo Format::viewableImages($v);
        } ?>
        </label>
<?php   }
    }

    function renderSectionBreak($label) { ?>
        <div><?php echo Format::htmlchars($label); ?></div>
<?php
    }

    function getValue() {
        $data = $this->field->getSource();
        if (count($data)) {
            if (!isset($data[$this->name]))
                return array();
            return $this->collectValues($data[$this->name], $this->field->getChoices());
        }
        return parent::getValue();
    }

    function collectValues($data, $choices) {
        $value = array();
        foreach ($choices as $k => $v) {
            if (is_array($v))
                $value = array_merge($value, $this->collectValues($data, $v));
            elseif (@in_array($k, $data))
                $value[$k] = $v;
        }
        return $value;
    }
}

/**
 * An extension to the BoxChoicesWidget which will render complex choices in
 * tabs.
 */
class TabbedBoxChoicesWidget extends BoxChoicesWidget {
    function render($options=array()) {
        $tabs = array();
        foreach ($this->field->getChoices() as $label=>$group) {
            if (is_array($group)) {
                $tabs[$label] = $group;
            }
            else {
                $this->emitChoices(array($label=>$group));
            }
        }
        if ($tabs) {
            ?>
            <div>
            <ul class="alt tabs">
<?php       $i = 0;
            foreach ($tabs as $label => $group) {
                $active = $i++ == 0; ?>
                <li <?php if ($active) echo 'class="active"';
                  ?>><a href="#<?php echo sprintf('%s-%s', $this->name, Format::slugify($label));
                  ?>"><?php echo Format::htmlchars($label); ?></a></li>
<?php       } ?>
            </ul>
<?php       $i = 0;
            foreach ($tabs as $label => $group) {
                $first = $i++ == 0; ?>
                <div class="tab_content <?php if (!$first) echo 'hidden'; ?>" id="<?php
                  echo sprintf('%s-%s', $this->name, Format::slugify($label));?>">
<?php           $this->emitChoices($group); ?>
                </div>
<?php       } ?>
            </div>
<?php   }
    }
}

/**
* TimezoneWidget extends ChoicesWidget to add auto-detect and select2 search
* options
*
**/
class TimezoneWidget extends ChoicesWidget {

    function render($options=array()) {
        parent::render($options);
        $config = $this->field->getConfiguration();
        if (@$config['autodetect']) {
        ?>
        <button type="button" class="action-button" onclick="javascript:
            $('head').append($('<script>').attr('src', '<?php
            echo ROOT_PATH; ?>js/jstz.min.js'));
            var recheck = setInterval(function() {
                if (window.jstz !== undefined) {
                    clearInterval(recheck);
                    var zone = jstz.determine();
                    $('#<?php echo $this->id; ?>').val(zone.name()).trigger('change');

                }
            }, 100);
            return false;"
            style="vertical-align:middle">
            <i class="icon-map-marker"></i> <?php echo __('Auto Detect'); ?>
        </button>
        <?php
        } ?>
        <script type="text/javascript">
            $(function() {
                $('#<?php echo $this->id; ?>').select2({
                    allowClear: true,
                    width: '300px'
                });
            });
        </script>
      <?php
    }
}

class CheckboxWidget extends Widget {
    function __construct($field) {
        parent::__construct($field);
        $this->name = '_field-checkboxes';
    }

    function render($options=array()) {
        $config = $this->field->getConfiguration();
        if (!isset($this->value))
            $this->value = $this->field->get('default');
        $classes = array('checkbox');
        if (isset($config['classes']))
            $classes = array_merge($classes, (array) $config['classes']);
        ?>
        <label class="<?php echo implode(' ', $classes); ?>">
        <input id="<?php echo $this->id; ?>"
            type="checkbox" name="<?php echo $this->name; ?>[]" <?php
            if ($this->value) echo 'checked="checked"'; ?> value="<?php
            echo $this->field->get('id'); ?>"/>
        <?php
        if ($config['desc']) {
            echo Format::viewableImages($config['desc']);
        } ?>
        </label>
<?php
    }

    function getValue() {
        $data = $this->field->getSource();
        if (is_array($data)) {
            if (isset($data[$this->name]))
                return @in_array($this->field->get('id'),
                        $data[$this->name]);
            // initial value set on source
            if (isset($data[$this->field->get('id')]))
                return $data[$this->field->get('id')];
        }

        if (!$data && isset($this->value))
            return $this->value;


        return parent::getValue();
    }

    function getJsValueGetter($id='%s') {
        return sprintf('%s.is(":checked")', $id);
    }

}

class DatetimePickerWidget extends Widget {

    function render($options=array()) {
        global $cfg;

        $config = $this->field->getConfiguration();
        $timezone = $this->field->getTimezone();
        $dateFormat = $cfg->getDateFormat(true);
        $timeFormat = $cfg->getTimeFormat(true);
        if (!isset($this->value) && ($default=$this->field->get('default')))
            $this->value = $default;

        if ($this->value == 0)
            $this->value = '';

        if ($this->value) {
            $datetime = Format::parseDateTime($this->value);
            if ($config['time'])
                // Convert to user's timezone for update.
                $datetime->setTimezone($timezone);

            // Get formatted date
            $this->value = Format::date($datetime->getTimestamp(), false,
                        false, $timezone ? $timezone->getName() : 'UTC');
            // Get formatted time
            if ($config['time']) {
                 $this->value .=' '.Format::time($datetime->getTimestamp(),
                         false, $timeFormat, $timezone ?
                         $timezone->getName() : 'UTC');
            }

        } else {
            // For timezone display purposes
            $datetime = new DateTime('now');
            $datetime->setTimezone($timezone);
        }
        ?>
        <input type="text" name="<?php echo $this->name; ?>"
            id="<?php echo $this->id; ?>" style="display:inline-block;width:auto"
            value="<?php echo $this->value; ?>"
            size="<?php $config['time'] ? 20 : 12; ?>"
            autocomplete="off" class="dp" />
        <?php
        // Timezone hint
        // Show timzone hit by default but allow field to turn it off.
        $showtimezone = true;
        if (isset($config['showtimezone']))
            $showtimezone = $config['showtimezone'];

        if ($datetime && $showtimezone) {
            echo sprintf('&nbsp;<span class="faded">(<a href="#"
                        data-placement="top" data-toggle="tooltip"
                        title="%s">%s</a>)</span>',
                    $datetime->getTimezone()->getName(),
                    $datetime->format('T'));
        }
        ?>
        <script type="text/javascript">
            $(function() {
                $('input[name="<?php echo $this->name; ?>"]').<?php echo
                $config['time'] ? 'datetimepicker':'datepicker';?>({
                    <?php
                    if ($dt=$this->field->getMinDateTime())
                        echo sprintf("minDate: new Date(%s),\n", $dt->format('U')*1000);
                    if ($dt=$this->field->getMaxDateTime())
                        echo sprintf("maxDate: new Date(%s),\n", $dt->format('U')*1000);
                    elseif (!$config['future'])
                        echo "maxDate: new Date().getTime(),\n";

                    // Set time options
                    if ($config['time']) {
                        // Set Timezone
                        echo sprintf("timezone: %s,\n",
                                ($datetime->getOffset()/60));
                        echo sprintf("
                                controlType: 'select',\n
                                timeInput: true,\n
                                timeFormat: \"%s\",\n",
                                Format::dtfmt_php2js($timeFormat));
                    }
                    ?>
                    numberOfMonths: 2,
                    showButtonPanel: true,
                    buttonImage: './images/cal.png',
                    showOn:'both',
                    dateFormat: '<?php echo
                        Format::dtfmt_php2js($dateFormat); ?>'
                });
            });
        </script>
        <?php
    }

    /**
     * Function: getValue
     * Combines the datepicker date value and the time dropdown selected
     * time value into a single date and time string value in DateTime::W3C
     */
    function getValue() {
        global $cfg;

        if ($value = parent::getValue()) {
            if (($dt = Format::parseDateTime($value))) {
                // Effective timezone for the selection
                if (($timezone = $this->field->getTimezone()))
                    $dt->setTimezone($timezone);
                // Format date time to universal format
                $value = $dt->format('Y-m-d H:i:s T');
            }
        }

        return $value;
    }
}

class TimePickerWidget extends Widget {

    function render($options=array()) {
        $config = $this->field->getConfiguration();
        if (!isset($this->value) && ($default=$this->field->get('default')))
            $this->value = $default;

        // For timezone display purposes only - for now
        $datetime = new DateTime('now');
        // Selection timezone
        $datetime->setTimezone($this->field->getTimeZone());

        if ($this->value) {
            // TODO: Reformat time here to match settings
        }

        ?>
        <input type="text" name="<?php echo $this->name; ?>"
            id="<?php echo $this->id; ?>" style="display:inline-block;width:auto"
            value="<?php echo $this->value; ?>"
            size="10"
            autocomplete="off"  />
        <?php
        // Timezone hint
        // Show timzone hit by default but allow field to turn it off.
        $showtimezone = true;
        if (isset($config['showtimezone']))
            $showtimezone = $config['showtimezone'];

        if ($showtimezone) {
            echo sprintf('&nbsp;<span class="faded">(<a href="#"
                        data-placement="top" data-toggle="tooltip"
                        title="%s">%s</a>)</span>',
                    $datetime->getTimezone()->getName(),
                    $datetime->format('T'));
        }
        ?>
        <script type="text/javascript">
            $(function() {
                $('input[name="<?php echo $this->name; ?>"]').timepicker({
                    <?php
                    // Set time options
                    echo sprintf("
                            controlType: 'select',\n
                            timeInput: true,\n
                            timeFormat: \"%s\",\n",
                            "hh:mm tt");
                    echo sprintf("timezone: %s\n",
                            ($datetime->getOffset()/60));
                    ?>
                });
            });
        </script>
        <?php
    }

    /**
     * Function: getValue
     */
    function getValue() {
        global $cfg;

        if ($value = parent::getValue()) {
            // TODO: Return ISO format.
        }

        return $value;
    }
}
class SectionBreakWidget extends Widget {
    function render($options=array()) {
        ?><div class="form-header section-break"><h3><?php
        echo Format::htmlchars($this->field->getLocal('label'));
        ?></h3><em><?php echo Format::display($this->field->getLocal('hint'));
        ?></em></div>
        <?php
    }
}

class ThreadEntryWidget extends Widget {
    function render($options=array()) {

        $config = $this->field->getConfiguration();
        $object_id = false;
        if ($options['client']) {
            $namespace = $options['draft-namespace']
                ?: 'ticket.client';
             $object_id = substr(session_id(), -12);
        } else {
            $namespace = $options['draft-namespace'] ?: 'ticket.staff';
        }

        list($draft, $attrs) = Draft::getDraftAndDataAttrs($namespace, $object_id, $this->value);
        ?>
        <textarea style="width:100%;" name="<?php echo $this->name; ?>"
            placeholder="<?php echo Format::htmlchars($this->field->get('placeholder')); ?>"
            class="<?php if ($config['html']) echo 'richtext';
                ?> draft draft-delete" <?php echo $attrs; ?>
            cols="21" rows="8" style="width:80%;"><?php echo
            ThreadEntryBody::clean($this->value ?: $draft); ?></textarea>
    <?php
        if (!$config['attachments'])
            return;

        $attachments = $this->getAttachments($config);
        print $attachments->render($options);
        foreach ($attachments->getMedia() as $type=>$urls) {
            foreach ($urls as $url)
                Form::emitMedia($url, $type);
        }
    }

    function getAttachments($config=false) {
        if (!$config)
            $config = $this->field->getConfiguration();

        $field = new FileUploadField(array(
            'id'=>'attach',
            'name'=>'attach:' . $this->field->get('id'),
            'configuration'=>$config)
        );
        $field->setForm($this->field->getForm());
        return $field;
    }

    function parseValue() {
        parent::parseValue();
        if (isset($this->value)) {
            $value = $this->value;
            $config = $this->field->getConfiguration();
            // Trim spaces based on text input type.
            // Preserve original input if not empty.
            if ($config['html'])
                $this->value = trim($value, " <>br/\t\n\r") ? $value : '';
            else
                $this->value = trim($value) ? $value : '';
        }
    }

}

class FileUploadWidget extends Widget {
    static $media = array(
        'css' => array(
            '/css/filedrop.css',
        ),
    );

    function render($options=array()) {
        $config = $this->field->getConfiguration();
        $name = $this->field->getFormName();
        $id = substr(md5(spl_object_hash($this)), 10);
        $mimetypes = array_filter($config['__mimetypes'],
            function($t) { return strpos($t, '/') !== false; }
        );
        $maxfilesize = ($config['size'] ?: 1048576) / 1048576;
        $files = array();
        $new = $this->field->getClean(false);

        foreach ($this->field->getAttachments() as $att) {
            unset($new[$att->file_id]);
            $files[] = array(
                'id' => $att->file->getId(),
                'name' => $att->getFilename(),
                'type' => $att->file->getType(),
                'size' => $att->file->getSize(),
                'download_url' => $att->file->getDownloadUrl(),
            );
        }

        // Add in newly added files not yet saved (if redisplaying after an
        // error)
        if ($new) {
            $F = AttachmentFile::objects()
                ->filter(array('id__in' => array_keys($new)))
                ->all();

            foreach ($F as $f) {
                $f->tmp_name = $new[$f->getId()];
                $files[] = array(
                    'id' => $f->getId(),
                    'name' => $f->getName(),
                    'type' => $f->getType(),
                    'size' => $f->getSize(),
                    'download_url' => $f->getDownloadUrl(),
                );
            }
        }

        //see if the attachment is saved in the session for this specific field
        if ($sessionAttachment = $_SESSION[':form-data'][$this->field->get('name')]) {
            $F = AttachmentFile::objects()
                ->filter(array('id__in' => array_keys($sessionAttachment)))
                ->all();

            foreach ($F as $f) {
                $f->tmp_name = $sessionAttachment[$f->getId()];
                $files[] = array(
                    'id' => $f->getId(),
                    'name' => $f->getName(),
                    'type' => $f->getType(),
                    'size' => $f->getSize(),
                    'download_url' => $f->getDownloadUrl(),
                );
            }
        }

         // Set default $field_id
        $field_id = $this->field->get('id');
        // Get Form Type
        $type = $this->field->getForm()->type;
        // Determine if for Ticket/Task/Custom
        if ($type && !is_numeric($field_id)) {
            if ($type == 'T')
                $field_id = 'ticket/attach';
            elseif ($type == 'A')
                $field_id = 'task/attach';
        }

        ?><div id="<?php echo $id;
            ?>" class="filedrop"><div class="files"></div>
            <div class="dropzone"><i class="icon-upload"></i>
            <?php echo sprintf(
                __('Drop files here or %s choose them %s'),
                '<a href="#" class="manual">', '</a>'); ?>
        <input type="file" multiple="multiple"
            id="file-<?php echo $id; ?>" style="display:none;"
            accept="<?php echo implode(',', $config['__mimetypes']); ?>"/>
        </div></div>
        <script type="text/javascript">
        $(function(){$('#<?php echo $id; ?> .dropzone').filedropbox({
          url: 'ajax.php/form/upload/<?php echo $field_id; ?>',
          link: $('#<?php echo $id; ?>').find('a.manual'),
          paramname: 'upload[]',
          fallback_id: 'file-<?php echo $id; ?>',
          allowedfileextensions: <?php echo JsonDataEncoder::encode(
            $config['__extensions'] ?: array()); ?>,
          allowedfiletypes: <?php echo JsonDataEncoder::encode(
            $mimetypes); ?>,
          maxfiles: <?php echo $config['max'] ?: 20; ?>,
          maxfilesize: <?php echo str_replace(',', '.', $maxfilesize); ?>,
          name: '<?php echo $name; ?>[]',
          files: <?php echo JsonDataEncoder::encode($files); ?>
        });});
        </script>
<?php
    }

    function getValue() {
        $ids = array();
        // Handle manual uploads (IE<10)
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES[$this->name])) {
            foreach (AttachmentFile::format($_FILES[$this->name]) as $file) {
                try {
                    $F = $this->field->uploadFile($file);
                    $ids[$F->getId()] = $F->getName();
                }
                catch (FileUploadError $ex) {}
            }
            return $ids;
        }

        // Files uploaded here MUST have been uploaded by this user and
        // identified in the session
        //
        // If no value was sent, assume an empty list
        if (!($files = parent::getValue()))
            return array();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_files = array();
            foreach ($files as $info) {
                if (@list($id, $name) = explode(',', $info, 2))
                    $_files[$id] = $name;
            }
            $files = $_files;
        }

        $allowed = array();
        // Files already attached to the field are allowed
        foreach ($this->field->getFiles() as $F) {
            // FIXME: This will need special porting in v1.10
            $allowed[$F->id] = $F->getName();
        }

        // New files uploaded in this session are allowed
        if (isset($_SESSION[':uploadedFiles']))
            $allowed += $_SESSION[':uploadedFiles'];

        // Canned attachments initiated by this session
        if (isset($_SESSION[':cannedFiles']))
           $allowed += $_SESSION[':cannedFiles'];

        // Parse the files and make sure it's allowed.
        foreach ($files as $id => $name) {
            if (!isset($allowed[$id]))
                continue;

            // Keep the values as the IDs
            $ids[$id] = $name ?: $allowed[$id];
        }

        return $ids;
    }
}

class FileUploadError extends Exception {}

class FreeTextField extends FormField {
    static $widget = 'FreeTextWidget';
    protected $attachments;

    function getConfigurationOptions() {
        return array(
            'content' => new TextareaField(array(
                'configuration' => array('html' => true, 'size'=>'large'),
                'label'=>__('Content'), 'required'=>true, 'default'=>'',
                'hint'=>__('Free text shown in the form, such as a disclaimer'),
            )),
            'attachments' => new FileUploadField(array(
                'id'=>'attach',
                'label' => __('Attachments'),
                'name'=>'files',
                'configuration' => array('extensions'=>'')
            )),
        );
    }

    function hasData() {
        return false;
    }

    function isBlockLevel() {
        return true;
    }

    function isEditableToStaff() {
        return $this->isVisibleToStaff();
    }

    function isEditableToUsers() {
        return $this->isVisibleToUsers();
    }

    /* utils */

    function to_config($config) {
        if ($config && isset($config['attachments']))
            $keepers = $config['attachments'];
        $this->getAttachments()->keepOnlyFileIds($keepers);

        return $config;
    }

    function db_cleanup($field=false) {

        if ($field && $this->getFiles())
            $this->getAttachments()->deleteAll();
    }

    function getAttachments() {
        if (!isset($this->attachments))
            $this->attachments = GenericAttachments::forIdAndType($this->get('id'), 'I');

        return $this->attachments ?: array();
    }

    function getFiles() {
        if (!isset($this->files)) {
            $files = array();
            if (($attachments=$this->getAttachments()))
                foreach ($attachments->all() as $a)
                    $files[] = $a->getFile();
            $this->files = $files;
        }
        return $this->files;
    }
}

class FreeTextWidget extends Widget {
    function render($options=array()) {
        $config = $this->field->getConfiguration();
        $class = $config['classes'] ?: 'thread-body bleed';
        ?><div class="<?php echo $class; ?>"><?php
        if ($label = $this->field->getLocal('label')) { ?>
            <h3><?php
            echo Format::htmlchars($label);
        ?></h3><?php
        }
        if ($hint = $this->field->getLocal('hint')) { ?>
        <em><?php
            echo Format::display($hint);
        ?></em><?php
        } ?>
        <div><?php
            echo Format::viewableImages($config['content']); ?></div>
        </div>
        <?php
        if (($attachments = $this->field->getAttachments()) && count($attachments)) { ?>
            <section class="freetext-files">
            <div class="title"><?php echo __('Related Resources'); ?></div>
            <?php foreach ($attachments->all() as $attach) {
                $filename = Format::htmlchars($attach->getFilename());
                ?>
                <div class="file">
                <a href="<?php echo $attach->file->getDownloadUrl(); ?>"
                    target="_blank" download="<?php echo $filename; ?>"
                    class="truncate no-pjax">
                    <i class="icon-file"></i>
                    <?php echo $filename; ?>
                </a>
                </div>
            <?php } ?>
        </section>
        <?php }
    }
}

class ColorPickerWidget extends Widget {
    static $media = array(
        'css' => array(
            'css/spectrum.css',
        ),
        'js' => array(
            'js/spectrum.js',
        ),
    );

    function render($options=array()) {
        ?><input type="color"
            id="<?php echo $this->id; ?>"
            name="<?php echo $this->name; ?>"
            value="<?php echo Format::htmlchars($this->value); ?>"/><?php
    }
}

class VisibilityConstraint {
    static $operators = array(
        'eq' => 1,
        'neq' => 1,
    );

    const HIDDEN =      0x0001;
    const VISIBLE =     0x0002;

    var $initial;
    var $constraint;

    function __construct($constraint, $initial=self::VISIBLE) {
        $this->constraint = $constraint;
        $this->initial = $initial;
    }

    function emitJavascript($field) {

        if (!$this->constraint->constraints)
            return;

        $func = 'recheck_'.$field->getWidget()->id;
        $form = $field->getForm();
?>
    <script type="text/javascript">
      !(function() {
        var <?php echo $func; ?> = function() {
          var target = $('#field<?php echo $field->getWidget()->id; ?>');
<?php   $fields = $this->getAllFields($this->constraint);
        foreach ($fields as $f) {
            if (!($field = $form->getField($f)))
                continue;
            echo sprintf('var %1$s = x = $("#%1$s");',
                $field->getWidget()->id);
        }
        $expression = $this->compileQ($this->constraint, $form);
?>
          if (<?php echo $expression; ?>) {
            target.slideDown('fast', function (){
                $(this).trigger('show');
                });
          } else {
            target.slideUp('fast', function (){
                $(this).trigger('hide');
                });
          }
        };

<?php   foreach ($fields as $f) {
            if (!($field=$form->getField($f)))
                continue;
            $w = $field->getWidget();
?>
        $('#<?php echo $w->id; ?>').on('change', <?php echo $func; ?>);
        $('#field<?php echo $w->id; ?>').on('show hide', <?php
                echo $func; ?>);
<?php   } ?>
      })();
    </script><?php
    }

    /**
     * Determines if the field was visible when the form was submitted
     */
    function isVisible($field) {

        // Assume initial visibility if constraint is not provided.
        if (!$this->constraint->constraints)
            return $this->initial == self::VISIBLE;


        return $this->compileQPhp($this->constraint, $field);
    }

    static function splitFieldAndOp($field) {
        if (false !== ($last = strrpos($field, '__'))) {
            $op = substr($field, $last + 2);
            if (isset(static::$operators[$op]))
                $field = substr($field, 0, strrpos($field, '__'));
        }
        return array($field, $op);
    }

    function compileQPhp(Q $Q, $field) {
        if (!($form = $field->getForm())) {
            return $this->initial == self::VISIBLE;
        }
        $expr = array();
        foreach ($Q->constraints as $c=>$value) {
            if ($value instanceof Q) {
                $expr[] = $this->compileQPhp($value, $field);
            }
            else {
                @list($f, $op) = self::splitFieldAndOp($c);
                $field = $form->getField($f);
                $wval = $field ? $field->getClean() : null;
                $values = explode('|', $value);
                switch ($op) {
                case 'neq':
                    $expr[] = ($wval && !in_array($wval, $values) && $field->isVisible());
                    break;
                case 'eq':
                case null:
                    $expr[] = (in_array($wval, $values) && $field->isVisible());
                }
            }
        }
        $glue = $Q->isOred()
            ? function($a, $b) { return $a || $b; }
            : function($a, $b) { return $a && $b; };
        $initial = !$Q->isOred();
        $expression = array_reduce($expr, $glue, $initial);
        if ($Q->isNegated)
            $expression = !$expression;
        return $expression;
    }

    function getAllFields(Q $Q, &$fields=array()) {
        foreach ($Q->constraints as $c=>$value) {
            if ($c instanceof Q) {
                $this->getAllFields($c, $fields);
            }
            else {
                @list($f) = self::splitFieldAndOp($c);
                $fields[$f] = true;
            }
        }
        return array_keys($fields);
    }

    function compileQ($Q, $form) {
        $expr = array();
        foreach ($Q->constraints as $c=>$value) {
            if ($value instanceof Q) {
                $expr[] = $this->compileQ($value, $form);
            }
            else {
                list($f, $op) = self::splitFieldAndOp($c);
                if (!($field=$form->getField($f))) continue;
                $widget = $field->getWidget();
                $id = $widget->id;
                switch ($op) {
                case 'neq':
                    $expr[] = sprintf('(%s.is(":visible") && !(%s))',
                            $id, $widget->getJsComparator($value, $id));
                    break;
                case 'eq':
                case null:
                    $expr[] = sprintf('(%s.is(":visible") && (%s))',
                            $id, $widget->getJsComparator($value, $id));
                }
            }
        }
        $glue = $Q->isOred() ? ' || ' : ' && ';
        $expression = implode($glue, $expr);
        if (count($expr) > 1)
            $expression = '('.$expression.')';
        if ($Q->isNegated)
            $expression = '!'.$expression;
        return $expression;
    }
}

class AssignmentForm extends Form {

    static $id = 'assign';
    var $_assignee = null;
    var $_assignees = null;


    function getFields() {

        if ($this->fields)
            return $this->fields;

        $fields = array(
            'assignee' => new AssigneeField(array(
                    'id'=>1, 'label' => __('Assignee'),
                    'flags' => hexdec(0X450F3), 'required' => true,
                    'validator-error' => __('Assignee selection required'),
                    'configuration' => array(
                        'criteria' => array(
                            'available' => true,
                            ),
                       ),
                    )
                ),
            'refer' => new BooleanField(array(
                    'id'=>2, 'label'=>'', 'required'=>false,
                    'default'=>false,
                    'configuration'=>array(
                        'desc' => __('Maintain referral access to current assignees'))
                    )
                ),
            'comments' => new TextareaField(array(
                    'id' => 3, 'label'=> '', 'required'=>false, 'default'=>'',
                    'configuration' => array(
                        'html' => true,
                        'size' => 'small',
                        'placeholder' => __('Optional reason for the assignment'),
                        ),
                    )
                ),
            );


        if (isset($this->_assignees))
            $fields['assignee']->setChoices($this->_assignees);

        $this->setFields($fields);

        return $this->fields;
    }

    function getField($name) {

        if (($fields = $this->getFields())
                && isset($fields[$name]))
            return $fields[$name];
    }

    function isValid($include=false) {

        if (!parent::isValid($include) || !($f=$this->getField('assignee')))
            return false;

        // Do additional assignment validation
        if (!($assignee = $this->getAssignee())) {
            $f->addError(__('Unknown assignee'));
        } elseif ($assignee instanceof Staff) {
            // Make sure the agent is available
            if (!$assignee->isAvailable())
                $f->addError(__('Agent is unavailable for assignment'));
        } elseif ($assignee instanceof Team) {
            // Make sure the team is active and has members
            if (!$assignee->isActive())
                $f->addError(__('Team is disabled'));
            elseif (!$assignee->getNumMembers())
                $f->addError(__('Team does not have members'));
        }

        return !$this->errors();
    }

    function render($options=array()) {

        switch(strtolower($options['template'])) {
        case 'simple':
            $inc = STAFFINC_DIR . 'templates/dynamic-form-simple.tmpl.php';
            break;
        default:
            throw new Exception(sprintf(__('%s: Unknown template style %s'),
                        'FormUtils', $options['template']));
        }

        $form = $this;
        include $inc;
    }

    function setAssignees($assignees) {
        $this->_assignees = $assignees;
        $this->_fields = array();
    }

    function getAssignees() {
        return $this->_assignees;
    }

    function getAssignee() {

        if (!isset($this->_assignee))
            $this->_assignee = $this->getField('assignee')->getClean();

        return $this->_assignee;
    }

    function getComments() {
        return $this->getField('comments')->getClean();
    }

    function refer() {
        return $this->getField('refer')->getClean();
    }
}

class ClaimForm extends AssignmentForm {

    var $_fields;

    function setFields($fields) {
        $this->_fields = $fields;
        parent::setFields($fields);
    }

    function getFields() {

        if ($this->_fields)
            return $this->_fields;

        $fields = parent::getFields();

        // Disable && hide assignee field selection
        if (isset($fields['assignee'])) {
            $visibility = new VisibilityConstraint(
                    new Q(array()), VisibilityConstraint::HIDDEN);

            $fields['assignee']->set('visibility', $visibility);
        }

        // Change coments placeholder to reflect claim
        if (isset($fields['comments'])) {
            $fields['comments']->configure('placeholder',
                    __('Optional reason for the claim'));
        }


        $this->setFields($fields);

        return $this->fields;
    }

}

class ReleaseForm extends Form {
    static $id = 'unassign';

    function getFields() {
        if ($this->fields)
            return $this->fields;

        $fields = array(
            'comments' => new TextareaField(array(
                    'id' => 1, 'label'=> '', 'required'=>false, 'default'=>'',
                    'configuration' => array(
                        'html' => true,
                        'size' => 'small',
                        'placeholder' => __('Optional reason for releasing assignment'),
                        ),
                    )
                ),
            );


        $this->setFields($fields);

        return $this->fields;
    }

    function getField($name) {
        if (($fields = $this->getFields())
                && isset($fields[$name]))
            return $fields[$name];
    }

    function isValid($include=false) {
        if (!parent::isValid($include))
            return false;

        return !$this->errors();
    }

    function getComments() {
        return $this->getField('comments')->getClean();
    }
}

class MarkAsForm extends Form {
    static $id = 'markAs';

    function getFields() {
        if ($this->fields)
            return $this->fields;

        $fields = array(
            'comments' => new TextareaField(array(
                    'id' => 1, 'label'=> '', 'required'=>false, 'default'=>'',
                    'configuration' => array(
                        'html' => true,
                        'size' => 'small',
                        'placeholder' => __('Optional reason for marking ticket as (un)answered'),
                        ),
                    )
                ),
            );


        $this->setFields($fields);

        return $this->fields;
    }

    function getField($name) {
        if (($fields = $this->getFields())
                && isset($fields[$name]))
            return $fields[$name];
    }

    function isValid($include=false) {
        if (!parent::isValid($include))
            return false;

        return !$this->errors();
    }

    function getComments() {
        return $this->getField('comments')->getClean();
    }
}

class ReferralForm extends Form {

    static $id = 'refer';
    var $_target = null;
    var $_choices = null;
    var $_prompt = '';

    function getFields() {

        if ($this->fields)
            return $this->fields;

        $fields = array(
            'target' => new ChoiceField(array(
                    'id'=>1,
                    'label' => __('Referee'),
                    'flags' => hexdec(0X450F3),
                    'required' => true,
                    'validator-error' => __('Selection required'),
                    'choices' => array(
                    'agent' => __('Agent'),
                    'team'  => __('Team'),
                                'dept'  => __('Department'),
                               ),
                            )
                ),
            'agent' => new AgentSelectionField(array(
                    'id'=>2,
                    'label' => '',
                    'flags' => hexdec(0X450F3),
                    'required' => true,
                    'validator-error' => __('Agent selection required'),
                    'configuration'=>array('prompt'=>__('Select Agent')),
                            'visibility' => new VisibilityConstraint(
                                    new Q(array('target__eq'=>'agent')),
                                    VisibilityConstraint::HIDDEN
                              ),
                            )
                ),
            'team' => new ChoiceField(array(
                    'id'=>3,
                    'label' => '',
                    'flags' => hexdec(0X450F3),
                    'required' => true,
                    'validator-error' => __('Team selection required'),
                    'configuration'=>array('prompt'=>__('Select Team')),
                            'visibility' => new VisibilityConstraint(
                                    new Q(array('target__eq'=>'team')),
                                    VisibilityConstraint::HIDDEN
                              ),
                            )
                ),
            'dept' => new DepartmentField(array(
                    'id'=>4,
                    'label' => '',
                    'flags' => hexdec(0X450F3),
                    'required' => true,
                    'validator-error' => __('Dept. selection required'),
                    'configuration'=>array('prompt'=>__('Select Department')),
                            'visibility' => new VisibilityConstraint(
                                    new Q(array('target__eq'=>'dept')),
                                    VisibilityConstraint::HIDDEN
                              ),
                            )
                ),
            'comments' => new TextareaField(array(
                    'id' => 5,
                    'label'=> '',
                    'required'=>false,
                    'default'=>'',
                    'configuration' => array(
                        'html' => true,
                        'size' => 'small',
                        'placeholder' => __('Optional reason for the referral'),
                        ),
                    )
                ),
            );

        $this->setFields($fields);

        return $this->fields;
    }

    function getField($name) {

        if (($fields = $this->getFields())
                && isset($fields[$name]))
            return $fields[$name];
    }



    function isValid($include=false) {

        if (!parent::isValid($include) || !($f=$this->getField('target')))
            return false;

        // Do additional assignment validation
        $referee = $this->getReferee();
        switch (true) {
        case $referee instanceof Staff:
            // Make sure the agent is available
            if (!$referee->isAvailable())
                $f->addError(__('Agent is unavailable for assignment'));
        break;
        case $referee instanceof Team:
            // Make sure the team is active and has members
            if (!$referee->isActive())
                $f->addError(__('Team is disabled'));
            elseif (!$referee->getNumMembers())
                $f->addError(__('Team does not have members'));
        break;
        case $referee instanceof Dept:
        break;
        default:
            $f->addError(__('Unknown selection'));
        }

        return !$this->errors();
    }

    function render($options=array()) {

        switch(strtolower($options['template'])) {
        case 'simple':
            $inc = STAFFINC_DIR . 'templates/dynamic-form-simple.tmpl.php';
            break;
        default:
            throw new Exception(sprintf(__('%s: Unknown template style %s'),
                        'FormUtils', $options['template']));
        }

        $form = $this;
        include $inc;
    }

    function setChoices($field, $choices, $prompt='') {

        if (!($f= $this->getField($field)))
           return;

        $f->set('choices', $choices);

        return $f;
    }

    function getReferee() {

        $target = $this->getField('target')->getClean();
        if (!$target || !($f=$this->getField($target)))
            return null;

        $id = $f->getClean();
        switch($target) {
        case 'agent':
            return Staff::lookup($id);
        case 'team':
            return Team::lookup($id);
        case 'dept':
            return Dept::lookup($id);
        }
    }

    function getComments() {
        return $this->getField('comments')->getClean();
    }
}


class TransferForm extends Form {

    static $id = 'transfer';
    var $_dept = null;

    function __construct($source=null, $options=array()) {
        parent::__construct($source, $options);
    }

    function getFields() {

        if ($this->fields)
            return $this->fields;

        $fields = array(
            'dept' => new DepartmentField(array(
                    'id'=>1,
                    'label' => __('Department'),
                    'flags' => hexdec(0X450F3),
                    'required' => true,
                    'validator-error' => __('Department selection is required'),
                    )
                ),
            'refer' => new BooleanField(array(
                'id'=>2, 'label'=>'', 'required'=>false, 'default'=>false,
                'configuration'=>array(
                    'desc' => __('Maintain referral access to current department'))
            )),
            'comments' => new TextareaField(array(
                    'id' => 3,
                    'label'=> '',
                    'required'=>false,
                    'default'=>'',
                    'configuration' => array(
                        'html' => true,
                        'size' => 'small',
                        'placeholder' => __('Optional reason for the transfer'),
                        ),
                    )
                ),
            );

        $this->setFields($fields);

        return $this->fields;
    }

    function isValid($include=false) {

        if (!parent::isValid($include))
            return false;

        // Do additional validations
        if (!($dept = $this->getDept()))
            $this->getField('dept')->addError(
                    __('Unknown department'));

        return !$this->errors();
    }

    function render($options=array()) {

        switch(strtolower($options['template'])) {
        case 'simple':
            $inc = STAFFINC_DIR . 'templates/dynamic-form-simple.tmpl.php';
            break;
        default:
            throw new Exception(sprintf(__('%s: Unknown template style %s'),
                        get_class(), $options['template']));
        }

        $form = $this;
        include $inc;

    }

    function refer() {
        return $this->getField('refer')->getClean();
    }

    function getDept() {

        if (!isset($this->_dept)) {
            if (($id = $this->getField('dept')->getClean()))
                $this->_dept = Dept::lookup($id);
        }

        return $this->_dept;
    }

    function hideDisabled() {
        global $thisstaff;

        if ($f = $this->getField('dept')) {
            $f->configure('staff', $thisstaff);
            $f->configure('hideDisabled', true);
        }
    }
}

/**
 * FieldUnchanged
 *
 * Thrown in the to_database() method to indicate the value should not be
 * saved in the database (it wasn't changed in the request)
 */
class FieldUnchanged extends Exception {}
?>
