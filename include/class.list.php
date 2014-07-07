<?php
/*********************************************************************
    class.list.php

    Custom List utils

    Jared Hancock <jared@osticket.com>
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2014 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require_once(INCLUDE_DIR .'class.dynamic_forms.php');

/**
 * Interface for Custom Lists
 *
 * Custom lists are used to represent list of arbitrary data that can be
 * used as dropdown or typeahead selections in dynamic forms. This model
 * defines a list. The individual items are stored in the "Item" model.
 *
 */

interface CustomList {

    function getId();
    function getName();
    function getPluralName();

    function getNumItems();
    function getAllItems();
    function getItems($criteria);

    function getItem($id);
    function addItem($vars, &$errors);

    function getForm(); // Config form
    function hasProperties();

    function getSortModes();
    function getSortMode();
    function getListOrderBy();

    function isBuiltIn();

    function update($vars, &$errors);
    function delete();

    static function create($vars, &$errors);
    static function lookup($id);
}

/*
 * Custom list item interface
 */
interface CustomListItem {
    function getId();
    function getValue();
    function getAbbrev();
    function getSortOrder();

    function getConfiguration();
    function getConfigurationForm();


    function isEnabled();
    function isDeletable();
    function isEnableable();
    function isInternal();

    function enable();
    function disable();

    function update($vars, &$errors);
    function delete();
}


/*
 * Base class for Built-in Custom Lists
 *
 * Built-in custom lists are lists that can be extended but within the
 * constrains of system defined parameters.
 *
 */

abstract class BuiltInCustomList implements CustomList {
    static $sort_modes = array(
            'Alpha'     => 'Alphabetical',
            '-Alpha'    => 'Alphabetical (Reversed)',
            'SortCol'   => 'Manually Sorted'
    );

    var $config = null;

    function __construct() {
        $this->config = new Config('CL'.$this->getId());
    }

    abstract function getId();
    abstract function getName();
    abstract function getPluralName();

    abstract  function getInfo();

    abstract function getNumItems();
    abstract function getAllItems();
    abstract function getItems($criteria);

    abstract function addItem($vars, &$errors);

    abstract function getForm(); // Config form
    abstract function hasProperties();

    abstract function getListOrderBy();

    abstract function getSortMode();

    function getSortModes() {
        return static::$sort_modes;
    }

    function isBuiltIn() {
        return true;
    }

    function set($field, $value) {

        if (!$this->config)
            return false;

        return $this->config->set($field, $value);
    }

    abstract function update($vars, &$errors);

    // Built-in list cannot be deleted
    function delete() {
        return false;
    }

    // Built-in list is defined - not created.
    static function create($vars, &$errors) {
        return false;
    }

    static function lookup($id) {

        if (!($list=static::getLists())
                // Built-in list exists
                || !isset($list[$id])
                // Handler exits
                || !($handler = $list[$id]['handler'])
                // It's a collable handler
                || !class_exists($handler))
           return null;

        return new $handler();
    }

    static function getLists() {

        $list['status'] = array ( //Ticket statuses
                'name' => 'Ticket Status',
                'handler' => 'TicketStatusList',
                'icon' => 'icon-flag',
                );

        return $list;
    }

}

/**
 * Dynamic lists are Custom Lists solely defined by the user.
 *
 */
class DynamicList extends VerySimpleModel implements CustomList {

    static $meta = array(
        'table' => LIST_TABLE,
        'ordering' => array('name'),
        'pk' => array('id'),
    );

    static $sort_modes = array(
            'Alpha'     => 'Alphabetical',
            '-Alpha'    => 'Alphabetical (Reversed)',
            'SortCol'   => 'Manually Sorted'
            );

    // Required fields
    static $fields = array('name', 'name_plural', 'sort_mode', 'notes');


    var $_items;
    var $_form;

    function getId() {
        return $this->get('id');
    }

    function isBuiltIn() {
        return false;
    }

    function getInfo() {
        return $this->ht;
    }

    function hasProperties() {
        return ($this->getForm() && $this->getForm()->getFields());
    }

    function getSortModes() {
       return static::$sort_modes;
    }

    function getSortMode() {
        return $this->sort_mode;
    }

    function getListOrderBy() {
        switch ($this->getSortMode()) {
            case 'Alpha':   return 'value';
            case '-Alpha':  return '-value';
            case 'SortCol': return 'sort';
        }
    }

    function getName() {
        return $this->get('name');
    }

    function getPluralName() {
        if ($name = $this->get('name_plural'))
            return $name;
        else
            return $this->get('name') . 's';
    }

    function getItemCount() {
        return DynamicListItem::objects()->filter(array('list_id'=>$this->id))
            ->count();
    }

    function getNumItems() {
        return $this->getItemCount();
    }

    function getAllItems() {
         return DynamicListItem::objects()->filter(
                array('list_id'=>$this->get('id')))
                ->order_by($this->getListOrderBy());
    }

    function getItems($limit=false, $offset=false) {
        if (!$this->_items) {
            $this->_items = DynamicListItem::objects()->filter(
                array('list_id'=>$this->get('id'),
                      'status__hasbit'=>DynamicListItem::ENABLED))
                ->order_by($this->getListOrderBy());
            if ($limit)
                $this->_items->limit($limit);
            if ($offset)
                $this->_items->offset($offset);
        }
        return $this->_items;
    }



    function getItem($id) {
         return DynamicListItem::lookup(array(
                     'id' => $id,
                     'list_id' => $this->getId()));
    }

    function addItem($vars, &$errors) {

        $item = DynamicListItem::create(array(
            'list_id' => $this->getId(),
            'sort'  => $vars['sort'],
            'value' => $vars['value'],
            'extra' => $vars['abbrev']
        ));

        $item->save();

        $this->_items = false;

        return $item;
    }

    function getConfigurationForm($autocreate=false) {
        if (!$this->_form) {
            $this->_form = DynamicForm::lookup(array('type'=>'L'.$this->getId()));
            if (!$this->_form
                    && $autocreate
                    && $this->createConfigurationForm())
                return $this->getConfigurationForm(false);
        }

        return $this->_form;
    }

    private function createConfigurationForm() {

        $form = DynamicForm::create(array(
                    'type' => 'L'.$this->getId(),
                    'title' => $this->getName() . ' Properties'
        ));

        return $form->save(true);
    }

    function getForm($autocreate=true) {
        return $this->getConfigurationForm($autocreate);
    }

    function update($vars, &$errors) {
        $required = array('name');
        foreach (static::$fields as $f) {
            if (in_array($f, $required) && !$vars[$f])
                $errors[$f] = sprintf('%s is required', mb_convert_case($f, MB_CASE_TITLE));
            elseif (isset($vars[$f]))
                $this->set($f, $vars[$f]);
        }

        if ($errors)
            return false;

        return $this->save(true);
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

    private function createForm() {

        $form = DynamicForm::create(array(
                    'type' => 'L'.$this->getId(),
                    'title' => $this->getName() . ' Properties'
        ));

        return $form->save(true);
    }

    static function add($vars, &$errors) {

        $required = array('name');
        $ht = array();
        foreach (static::$fields as $f) {
            if (in_array($f, $required) && !$vars[$f])
                $errors[$f] = sprintf('%s is required', mb_convert_case($f, MB_CASE_TITLE));
            elseif(isset($vars[$f]))
                $ht[$f] = $vars[$f];
        }

        if (!$ht || $errors)
            return false;

        // Create the list && form
        if (!($list = self::create($ht))
                || !$list->save(true)
                || !$list->createConfigurationForm())
            return false;

        return $list;
    }

    static function create($ht=false, &$errors=array()) {
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
class DynamicListItem extends VerySimpleModel implements CustomListItem {

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

    var $_config;
    var $_form;

    const ENABLED               = 0x0001;

    protected function hasStatus($flag) {
        return 0 !== ($this->get('status') & $flag);
    }

    protected function clearStatus($flag) {
        return $this->set('status', $this->get('status') & ~$flag);
    }

    protected function setStatus($flag) {
        return $this->set('status', $this->get('status') | $flag);
    }

    function isInternal() {
        return false;
    }

    function isEnableable() {
        return true;
    }

    function isDeletable() {
        return !$this->isInternal();
    }

    function isEnabled() {
        return $this->hasStatus(self::ENABLED);
    }

    function enable() {
        $this->setStatus(self::ENABLED);
    }
    function disable() {
        $this->clearStatus(self::ENABLED);
    }

    function getId() {
        return $this->get('id');
    }

    function getListId() {
        return $this->get('list_id');
    }

    function getValue() {
        return $this->get('value');
    }

    function getAbbrev() {
        return $this->get('extra');
    }

    function getSortOrder() {
        return $this->get('sort');
    }

    function getConfiguration() {
        if (!$this->_config) {
            $this->_config = $this->get('properties');
            if (is_string($this->_config))
                $this->_config = JsonDataParser::parse($this->_config);
            elseif (!$this->_config)
                $this->_config = array();
        }
        return $this->_config;
    }

    function setConfiguration(&$errors=array()) {
        $config = array();
        foreach ($this->getConfigurationForm()->getFields() as $field) {
            $val = $field->to_database($field->getClean());
            $config[$field->get('id')] = is_array($val) ? $val[1] : $val;
            $errors = array_merge($errors, $field->errors());
        }
        if (count($errors) === 0)
            $this->set('properties', JsonDataEncoder::encode($config));

        return count($errors) === 0;
    }

    function getConfigurationForm() {
        if (!$this->_form) {
            $this->_form = DynamicForm::lookup(
                array('type'=>'L'.$this->get('list_id')));
        }
        return $this->_form;
    }

    function getForm() {
        return $this->getConfigurationForm();
    }

    function getVar($name) {
        $config = $this->getConfiguration();
        $name = mb_strtolower($name);
        foreach ($this->getConfigurationForm()->getFields() as $field) {
            if (mb_strtolower($field->get('name')) == $name)
                return $config[$field->get('id')];
        }
    }

    function toString() {
        return $this->get('value');
    }

    function __toString() {
        return $this->toString();
    }

    function update($vars, &$errors=array()) {

        if (!$vars['value']) {
            $errors['value-'.$this->getId()] = 'Value required';
            return false;
        }

        foreach (array(
                    'sort' => 'sort',
                    'value' => 'value',
                    'abbrev' => 'extra') as $k => $v) {
            if (isset($vars[$k]))
                $this->set($v, $vars[$k]);
        }

        return $this->save();
    }

    function delete() {
        # Don't really delete, just unset the list_id to un-associate it with
        # the list
        $this->set('list_id', null);
        return $this->save();
    }
}


/*
 * Ticket status List
 *
 *
 */

class TicketStatusList extends BuiltInCustomList {

    var $ht = array(
            'id' => 'status',
            'name' => 'Status',
            'name_plural' => 'Statuses',
    );

    // Fields of interest we need to store
    static $config_fields = array('sort_mode', 'notes');

    var $_items;
    var $_form;

    function getId() {
        return $this->ht['id'];
    }

    function getName() {
        return $this->ht['name'];
    }

    function getPluralName() {
        return $this->ht['name_plural'];
    }

    function getSortMode() {
        return $this->ht['sort_mode'];
    }

    function getNotes() {
        return $this->ht['notes'];
    }

    function getInfo() {
        return $this->config->getInfo() + $this->ht;
    }

    function getNumItems() {
        return 0;
    }

    function getAllItems() {

    }

    function getItems($criteria) {

    }

    function hasProperties() {
        return true;
    }

    function getListOrderBy() {
        switch ($this->setSortMode()) {
            case 'Alpha':
                return 'name';
            case '-Alpha':
                return '-name';
            case 'SortCol':
            default:
                return 'sort';
        }
    }

    function getForm() {
       return null;
    }

    function update($vars, &$errors) {

        foreach (static::$config_fields as $f) {
            if (!isset($vars[$f])) continue;

            if (parent::set($f, $vars[$f]))
                $this->ht[$field] = $vars[$f];
        }

        return true;
    }
}
?>
