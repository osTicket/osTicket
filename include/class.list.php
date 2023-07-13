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
require_once(INCLUDE_DIR .'class.variable.php');

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
    function isItemUnique($vars);

    function getForm(); // Config form
    function hasProperties();
    function getConfigurationForm();
    function getSummaryFields();

    function getSortModes();
    function getSortMode();
    function getListOrderBy();

    function allowAdd();
    function hasAbbrev();

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

    function getList();
    function getListId();

    function getConfiguration();

    function hasProperties();
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
 * Base class for Custom List handlers
 *
 * Custom list handler extends custom list and might store data outside the
 * typical dynamic list store.
 *
 */

abstract class CustomListHandler {

    var $_list;

    function __construct($list) {
        $this->_list = $list;
    }

    function __call($name, $args) {

        $rv = null;
        if ($this->_list && is_callable(array($this->_list, $name)))
            $rv = $args
                ? call_user_func_array(array($this->_list, $name), $args)
                : call_user_func(array($this->_list, $name));

        return $rv;
    }

    function __get($field) {
        return $this->_list->{$field};
    }

    function update($vars, &$errors) {
        return $this->_list->update($vars, $errors);
    }

    abstract function getListOrderBy();
    abstract function getNumItems();
    abstract function getAllItems();
    abstract function getItems($criteria);
    abstract function getItem($id);
    abstract function addItem($vars, &$errors);

    static protected $registry = array();
    static function forList(/* CustomList */ $list) {
        if ($list->type && ($handler = static::$registry[$list->type]))
            return new $handler($list);
        return $list;
    }
    static function register($type, $handler) {
        static::$registry[$type] = $handler;
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
        'joins' => array(
            'items' => array(
                'reverse' => 'DynamicListItem.list',
            ),
        ),
    );

    // Required fields
    static $fields = array('name', 'name_plural', 'sort_mode', 'notes');

    // Supported masks
    const MASK_EDIT     = 0x0001;
    const MASK_ADD      = 0x0002;
    const MASK_DELETE   = 0x0004;
    const MASK_ABBREV   = 0x0008;

    var $_items;
    var $_form;

    function getId() {
        return $this->get('id');
    }

    function getInfo() {
        return $this->ht;
    }

    function hasProperties() {
        return ($this->getForm() && $this->getForm()->getFields());
    }

    static function sortModes() {
        return array(
            'Alpha'     => __('Alphabetical'),
            '-Alpha'    => __('Alphabetical (Reversed)'),
            'SortCol'   => __('Manually Sorted')
        );
    }

    function getSortModes() {
        return self::sortModes();
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
        return $this->getLocal('name');
    }

    function getPluralName() {
        if ($name = $this->getLocal('name_plural'))
            return $name;
        else
            return $this->getName() . 's';
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

    function search($q) {
        $items = clone $this->getAllItems();
        return $items->filter(Q::any(array(
            'value__startswith' => $q,
            'extra__contains' => $q,
            'properties__contains' => '"'.$q,
        )));
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

    function getItem($val, $extra=false) {

        $items = DynamicListItem::objects()->filter(
                array('list_id' => $this->getId()));

        if (is_int($val))
            $items->filter(array('id' => $val));
        elseif ($extra)
            $items->filter(array('extra' => $val));
        else
            $items->filter(array('value' => $val));


        return $items->first();
    }

    function addItem($vars, &$errors) {
        if (($item=$this->getItem($vars['value'])))
            return $item;

        $item = DynamicListItem::create(array(
            'status' => 1,
            'list_id' => $this->getId(),
            'sort'  => $vars['sort'],
            'value' => $vars['value'],
            'extra' => $vars['extra']
        ));
        $this->_items = false;

        return $item;
    }

    function isItemUnique($data) {
        try {
            $this->getItems()->filter(array('value'=>$data['value']))->one();
            return false;
        }
        catch (DoesNotExist $e) {
            return true;
        }
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

    function getListItemBasicForm($source=null, $item=false) {
        return new SimpleForm(array(
            'value' => new TextboxField(array(
                'required' => true,
                'label' => __('Value'),
                'configuration' => array(
                    'translatable' => $item ? $item->getTranslateTag('value') : false,
                    'size' => 60,
                    'length' => 0,
                    'autofocus' => true,
                ),
            )),
            'extra' => new TextboxField(array(
                'label' => __('Abbreviation'),
                'configuration' => array(
                    'size' => 60,
                    'length' => 0,
                ),
            )),
        ), $source);
    }

    // Fields shown on the list items page
    function getSummaryFields() {
        $prop_fields = array();
        foreach ($this->getConfigurationForm()->getFields() as $f) {
            if (in_array($f->get('type'), array('text', 'datetime', 'phone')))
                $prop_fields[] = $f;
            if (strpos($f->get('type'), 'list-') === 0)
                $prop_fields[] = $f;

            // 4 property columns max
            if (count($prop_fields) == 4)
                break;
        }
        return $prop_fields;
    }

    function isDeleteable() {
        return !$this->hasMask(static::MASK_DELETE);
    }

    function isEditable() {
        return !$this->hasMask(static::MASK_EDIT);
    }

    function allowAdd() {
        return !$this->hasMask(static::MASK_ADD);
    }

    function hasAbbrev() {
        return !$this->hasMask(static::MASK_ABBREV);
    }

    protected function hasMask($mask) {
        return 0 !== ($this->get('masks') & $mask);
    }

    protected function clearMask($mask) {
        return $this->set('masks', $this->get('masks') & ~$mask);
    }

    protected function setFlag($mask) {
        return $this->set('mask', $this->get('mask') | $mask);
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

    function getConfiguration() {
        return JsonDataParser::parse($this->configuration);
    }

    function getTranslateTag($subtag) {
        return _H(sprintf('list.%s.%s', $subtag, $this->id));
    }
    function getLocal($subtag) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->get($subtag);
    }

    function update($vars, &$errors) {
        $vars = Format::htmlchars($vars);
        $required = array();
        if ($this->isEditable())
            $required = array('name');

        foreach (static::$fields as $f) {
            if (in_array($f, $required) && !$vars[$f])
                $errors[$f] = sprintf(__('%s is required'), mb_convert_case($f, MB_CASE_TITLE));
            elseif (isset($vars[$f])) {
                if ($vars[$f] != $this->get($f)) {
                    $type = array('type' => 'edited', 'key' => $f);
                    Signal::send('object.edited', $this, $type);
                    $this->set($f, $vars[$f]);
                }
            }

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

        // Refuse to delete lists that are in use by fields
        if ($fields != 0)
            return false;

        if (!parent::delete())
            return false;

            $type = array('type' => 'deleted');
            Signal::send('object.deleted', $this, $type);

        if (($form = $this->getForm(false))) {
            $form->delete(false);
            $form->fields->delete();
        }

        return true;
    }

    private function createForm() {

        $form = DynamicForm::create(array(
                    'type' => 'L'.$this->getId(),
                    'title' => $this->getName() . ' Properties'
        ));

        return $form->save(true);
    }

    static function add($vars, &$errors) {
        $vars = Format::htmlchars($vars);
        $required = array('name');
        $ht = array();
        foreach (static::$fields as $f) {
            if (in_array($f, $required) && !$vars[$f])
                $errors[$f] = sprintf(__('%s is required'), mb_convert_case($f, MB_CASE_TITLE));
            elseif(isset($vars[$f]))
                $ht[$f] = $vars[$f];
        }

        if (!$ht || $errors)
            return false;

        // Create the list && form
        if (!($list = self::create($ht, $errors, false))
                || !$list->save(true)
                || !$list->createConfigurationForm())
            return false;

        return $list;
    }

    static function create($ht=false, &$errors=array(), $sanitize=true) {
        if ($ht && $sanitize)
            $ht = Format::htmlchars($ht);

        if (isset($ht['configuration'])) {
            $ht['configuration'] = JsonDataEncoder::encode($ht['configuration']);
        }

        $inst = new static($ht);
        $inst->set('created', new SqlFunction('NOW'));

        if (isset($ht['properties'])) {
            $inst->save();
            $ht['properties']['type'] = 'L'.$inst->getId();
            $form = DynamicForm::create($ht['properties']);
            $form->save();
        }

        if (isset($ht['items'])) {
            $inst->save();
            foreach ($ht['items'] as $i) {
                $i['list_id'] = $inst->getId();
                $item = DynamicListItem::create($i);
                $item->save();
            }
        }

        return $inst;
    }

    static function lookup($id) {

        if (!($list = parent::lookup($id)))
            return null;

        if (($config = $list->getConfiguration())) {
            if (($lh=$config['handler']) && class_exists($lh))
                $list = new $lh($list);
        }

        return $list;
    }

    static function getSelections() {
        $selections = array();
        foreach (DynamicList::objects() as $list) {
            $selections['list-'.$list->id] =
                array($list->getPluralName(),
                    'SelectionField', $list->get('id'));
        }
        return $selections;
    }

   function importCsv($stream, $defaults=array()) {
        require_once INCLUDE_DIR . 'class.import.php';

        $form = $this->getConfigurationForm();
        $fields = array(
            'value' => new TextboxField(array(
                'label' => __('Value'),
                'name' => 'value',
                'configuration' => array(
                    'length' => 0,
                ),
            )),
            'abbrev' => new TextboxField(array(
                'name' => 'extra',
                'label' => __('Abbreviation'),
                'configuration' => array(
                    'length' => 0,
                ),
            )),
        );

        $form = $this->getConfigurationForm();
        if ($form && ($custom_fields = $form->getFields())
                && count($custom_fields)) {
            foreach ($custom_fields as $f)
                if ($f->get('name'))
                    $fields[$f->get('name')] = $f;
        }

        $importer = new CsvImporter($stream);
        $imported = 0;
        try {
            db_autocommit(false);
            $records = $importer->importCsv($fields, $defaults);
            foreach ($records as $data) {
                $errors = array();
                $item = $this->addItem($data, $errors);
                if ($item && $item->setConfiguration($data, $errors))
                    $imported++;
                else
                    echo sprintf(__('Unable to import item: %s'), print_r($data, true));
            }
            db_autocommit(true);
        }
        catch (Exception $ex) {
            db_rollback();
            return $ex->getMessage();
        }
        return $imported;
    }

    function importFromPost($stuff, $extra=array()) {
        if (is_array($stuff) && !$stuff['error']) {
            $stream = fopen($stuff['tmp_name'], 'r');
        }
        elseif ($stuff) {
            $stream = fopen('php://temp', 'w+');
            fwrite($stream, $stuff);
            rewind($stream);
        }
        else {
            return __('Unable to parse submitted items');
        }

        return self::importCsv($stream, $extra);
    }
}
FormField::addFieldTypes(/* @trans */ 'Custom Lists', array('DynamicList', 'getSelections'));

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

    const ENABLED   = 0x0001;
    const INTERNAL  = 0x0002;

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
        return  $this->hasStatus(self::INTERNAL);
    }

    function isEnableable() {
        return true;
    }

    function isDisableable() {
        return !$this->isInternal();
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

    function hasProperties() {
        return ($this->getForm() && $this->getForm()->getFields());
    }

    function getId() {
        return $this->get('id');
    }

    function getList() {
        return $this->list;
    }

    function getListId() {
        return $this->get('list_id');
    }

    function getValue() {
        return $this->getLocal('value');
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

    function setConfiguration($vars, &$errors=array()) {
        $config = array();
        foreach ($this->getConfigurationForm($vars)->getFields() as $field) {
            $config[$field->get('id')] = $field->to_database($field->getClean());
            $errors = array_merge($errors, $field->errors());
        }

        if ($errors)
            return false;

        $this->set('properties', JsonDataEncoder::encode($config));

        return $this->save();
    }

    function getConfigurationForm($source=null) {
        if (!$this->_form) {
            $config = $this->getConfiguration();
            $this->_form = $this->list->getForm()->getForm($source);
            if (!$source && $config) {
                $fields = $this->_form->getFields();
                foreach ($fields as $f) {
                    $name = $f->get('id');
                    if (isset($config[$name]))
                        $f->value = $f->to_php($config[$name]);
                    else if ($f->get('default'))
                        $f->value = $f->get('default');
                }
            }
        }

        return $this->_form;
    }

    function getForm() {
        return $this->getConfigurationForm();
    }

    function getFields() {
        return $this->getForm()->getFields();
    }

    function getVar($name) {
        $config = $this->getConfiguration();
        $name = mb_strtolower($name);
        foreach ($this->getConfigurationForm()->getFields() as $field) {
            if (mb_strtolower($field->get('name')) == $name)
                return $field->asVar($config[$field->get('id')]);
        }
    }

    function getFilterData() {
        $data = array();
        foreach ($this->getConfigurationForm()->getFields() as $F) {
            $data['.'.$F->get('id')] = $F->toString($F->value);
        }
        $data['.abb'] = (string) $this->get('extra');
        return $data;
    }

    function getTranslateTag($subtag) {
        return _H(sprintf('listitem.%s.%s', $subtag, $this->id));
    }
    function getLocal($subtag) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->get($subtag);
    }

    function toString() {
        return $this->get('value');
    }

    function __toString() {
        return $this->toString();
    }

    function display() {

        return $this->getValue();
        //TODO: Allow for display mode (edit, preview or both)
        return sprintf('<a class="preview" href="#"
                data-preview="#list/%d/items/%d/preview">%s</a>',
                $this->getListId(),
                $this->getId(),
                $this->getValue()
                );
    }

    function update($vars, &$errors=array()) {

        if (!$vars['value']) {
            $errors['value-'.$this->getId()] = __('Value required');
            return false;
        }

        foreach (array(
                    'sort' => 'sort',
                    'value' => 'value',
                    'abbrev' => 'extra') as $k => $v) {
            if ($k == 'abbrev' && empty($vars[$k])) {
                $vars[$k] = NULL;
                $this->set($v, $vars[$k]);
            } elseif (isset($vars[$k]))
                $this->set($v, $vars[$k]);
        }

        return $this->save();
    }

    function save($refetch=false) {
        $this->value = trim($this->value);

        return parent::save($refetch);
    }

    function delete() {
        # Don't really delete, just unset the list_id to un-associate it with
        # the list
        $this->set('list_id', null);
        return $this->save();
    }

    static function create($ht=false, &$errors=array()) {

        if (isset($ht['properties']) && is_array($ht['properties']))
            $ht['properties'] = JsonDataEncoder::encode($ht['properties']);

        $inst = new static($ht);

        // Auto-config properties if any
        if ($ht['configuration'] && is_array($ht['configuration'])) {
            $config = $inst->getConfiguration();
            if (($form = $inst->getConfigurationForm())) {
                foreach ($form->getFields() as $f) {
                    if (!isset($ht['configuration'][$f->get('name')]))
                        continue;

                    $config[$f->get('id')] =
                        $ht['configuration'][$f->get('name')];
                }
            }

            $inst->set('properties', JsonDataEncoder::encode($config));
        }

        return $inst;
    }
}


/*
 * Ticket status List
 *
 */

class TicketStatusList extends CustomListHandler {

    // Fields of interest we need to store
    static $config_fields = array('sort_mode', 'notes');

    var $_items;
    var $_form;

    function getListOrderBy() {
        switch ($this->getSortMode()) {
            case 'Alpha':   return 'name';
            case '-Alpha':  return '-name';
            case 'SortCol': return 'sort';
        }
    }

    function getNumItems() {
        return TicketStatus::objects()->count();
    }

    function getAllItems() {
        if (!$this->_items)
            $this->_items = TicketStatus::objects()->order_by($this->getListOrderBy());

        return $this->_items;
    }

    function search($q) {
        $items = clone $this->getAllItems();
        return $items->filter(Q::any(array(
            'name__startswith' => $q,
            'properties__contains' => '"'.$q,
        )));
    }

    function getItems($criteria = array()) {

        // Default to only enabled items
        if (!isset($criteria['enabled']))
            $criteria['enabled'] = true;

        $filters =  array();
        if ($criteria['enabled'])
            $filters['mode__hasbit'] = TicketStatus::ENABLED;
        if ($criteria['states'] && is_array($criteria['states']))
            $filters['state__in'] = $criteria['states'];
        else
            $filters['state__isnull'] = false;

        $items = TicketStatus::objects();
        if ($filters)
            $items->filter($filters);
        if (isset($criteria['limit']))
            $items->limit($criteria['limit']);
        if (isset($criteria['offset']))
            $items->offset($criteria['offset']);

        $items->order_by($this->getListOrderBy());

        return $items;
    }

    function getItem($val) {

        if (!is_int($val))
            $val = array('name' => $val);

         return TicketStatus::lookup($val, $this);
    }

    function addItem($vars, &$errors) {
        $item = TicketStatus::create(array(
            'mode' => 1,
            'flags' => 0,
            'sort'  => $vars['sort'],
            'name' => $vars['name'],
        ));
        $this->_items = false;

        return $item;
    }

    function isItemUnique($data) {
        try {
            $this->getItems()->filter(array('name'=>$data['name']))->one();
            return false;
        }
        catch (DoesNotExist $e) {
            return true;
        }
    }


    static function getStatuses($criteria=array()) {

        $statuses = array();
        if (($list = DynamicList::lookup(
                        array('type' => 'ticket-status'))))
            $statuses = $list->getItems($criteria);

        return $statuses;
    }

    static function __load() {
        require_once(INCLUDE_DIR.'class.i18n.php');

        $i18n = new Internationalization();
        $tpl = $i18n->getTemplate('list.yaml');
        foreach ($tpl->getData() as $f) {
            if ($f['type'] == 'ticket-status') {
                $list = DynamicList::create($f);
                $list->save();
                break;
            }
        }

        if (!$list || !($o=DynamicForm::objects()->filter(
                        array('type'=>'L'.$list->getId()))))
            return false;

        // Create default statuses
        if (($statuses = $i18n->getTemplate('ticket_status.yaml')->getData()))
            foreach ($statuses as $status)
                TicketStatus::__create($status);

        return $o[0];
    }

    function getExtraConfigOptions($source=null) {
        $status_choices = array( 0 => __('System Default'));
        if (($statuses=TicketStatusList::getStatuses(
                        array( 'enabled' => true, 'states' =>
                            array('open')))))
            foreach ($statuses as $s)
                $status_choices[$s->getId()] = $s->getName();

        return array(
            'allowreopen' => new BooleanField(array(
                'label' =>__('Allow Reopen'),
                'editable' => true,
                'default' => isset($source['allowreopen'])
                    ?  $source['allowreopen']: true,
                'id' => 'allowreopen',
                'name' => 'allowreopen',
                'configuration' => array(
                    'desc'=>__('Allow tickets on this status to be reopened by end users'),
                ),
                'visibility' => new VisibilityConstraint(
                    new Q(array('state__eq'=>'closed')),
                    VisibilityConstraint::HIDDEN
                ),
            )),
            'reopenstatus' => new ChoiceField(array(
                'label' => __('Reopen Status'),
                'editable' => true,
                'required' => false,
                'default' => isset($source['reopenstatus'])
                    ? $source['reopenstatus'] : 0,
                'id' => 'reopenstatus',
                'name' => 'reopenstatus',
                'choices' => $status_choices,
                'configuration' => array(
                    'widget' => 'dropdown',
                    'multiselect' =>false
                ),
                'visibility' => new VisibilityConstraint(
                    new Q(array('allowreopen__eq'=> true)),
                    VisibilityConstraint::HIDDEN
                ),
            ))
        );
    }

    function getConfigurationForm($source=null) {
        if (!($form = $this->getForm()))
            return null;

        $form = $form->getForm($source);
        $fields = $form->getFields();
        foreach ($fields as $k => $f) {
            if ($f->get('name') == 'state' //TODO: check if editable.
                    && ($extras=$this->getExtraConfigOptions($source))) {
                foreach ($extras as $extra) {
                    $extra->setForm($form);
                    $fields->insert(++$k, $extra);
                }
            }

            if (!isset($f->ht['editable']))
                $f->ht['editable'] = true;
        }

        // Enable selection and display of private states
        $form->getField('state')->options['private_too'] = true;

        return $form;
    }

    function getListItemBasicForm($source=null, $item=false) {
        return new SimpleForm(array(
            'name' => new TextboxField(array(
                'required' => true,
                'label' => __('Value'),
                'configuration' => array(
                    'translatable' => $item ? $item->getTranslateTag('value') : false,
                    'size' => 60,
                    'length' => 0,
                    'autofocus' => true,
                ),
            )),
            'extra' => new TextboxField(array(
                'label' => __('Abbreviation'),
                'configuration' => array(
                    'size' => 60,
                    'length' => 0,
                ),
            )),
        ), $source);
    }

    function getSummaryFields() {
        // Like the main one, except the description and state fields are
        // welcome on the screen
        $prop_fields = array();
        foreach ($this->getConfigurationForm()->getFields() as $f) {
            if (in_array($f->get('type'), array('state', 'text', 'datetime', 'phone')))
                $prop_fields[] = $f;
            elseif (strpos($f->get('type'), 'list-') === 0)
                $prop_fields[] = $f;
            elseif ($f->get('name') == 'description')
                $prop_fields[] = $f;

            // 4 property columns max
            if (count($prop_fields) == 4)
                break;
        }
        return $prop_fields;
    }
}
CustomListHandler::register('ticket-status', 'TicketStatusList');

class TicketStatus
extends VerySimpleModel
implements CustomListItem, TemplateVariable, Searchable {

    static $meta = array(
        'table' => TICKET_STATUS_TABLE,
        'ordering' => array('name'),
        'pk' => array('id'),
        'joins' => array(
            'tickets' => array(
                'reverse' => 'Ticket.status',
                )
        )
    );

    var $_list;
    var $_form;
    var $_settings;
    var $_properties;

    const ENABLED   = 0x0001;
    const INTERNAL  = 0x0002; // Forbid deletion or name and status change.

    protected function hasFlag($field, $flag) {
        return 0 !== ($this->get($field) & $flag);
    }

    protected function clearFlag($field, $flag) {
        return $this->set($field, $this->get($field) & ~$flag);
    }

    protected function setFlag($field, $flag) {
        return $this->set($field, $this->get($field) | $flag);
    }

    function hasProperties() {
        return ($this->get('properties'));
    }

    function isEnabled() {
        return $this->hasFlag('mode', self::ENABLED);
    }

    function isReopenable() {

        if (strcasecmp($this->get('state'), 'closed'))
            return true;

        if (($c=$this->getConfiguration())
                && $c['allowreopen']
                && isset($c['reopenstatus']))
            return true;

        return false;
    }

    function getReopenStatus() {
        global $cfg;

        $status = null;
        if ($this->isReopenable()
                && ($c = $this->getConfiguration())
                && isset($c['reopenstatus']))
            $status = TicketStatus::lookup(
                    $c['reopenstatus'] ?: $cfg->getDefaultTicketStatusId());

        return ($status
                && !strcasecmp($status->getState(), 'open'))
            ?  $status : null;
    }

    function enable() {

        // Ticket status without properties cannot be enabled!
        if (!$this->isEnableable())
            return false;

        return $this->setFlag('mode', self::ENABLED);
    }

    function disable() {
        return (!$this->isInternal()
                && !$this->isDefault()
                && $this->clearFlag('mode', self::ENABLED));
    }

    function isDefault() {
        global $cfg;

        return ($cfg
                && $cfg->getDefaultTicketStatusId() == $this->getId());
    }

    function isEnableable() {
        return ($this->getState());
    }

    function isDisableable() {
        return !($this->isInternal() || $this->isDefault());
    }

    function isDeletable() {

        return !($this->isInternal()
                || $this->isDefault()
                || $this->getNumTickets());
    }

    function isInternal() {
        return ($this->isDefault()
                || $this->hasFlag('mode', self::INTERNAL));
    }


    function getNumTickets() {
        return $this->tickets->count();
    }

    function getId() {
        return $this->get('id');
    }

    function getName($localize=true) {
        return $localize ? $this->getLocalName() : $this->get('name');
    }

    function getState() {
        return $this->get('state');
    }

    function getValue() {
        return $this->getName();
    }

    function getLocalName() {
        return $this->getLocal('value', $this->get('name'));
    }

    function getAbbrev() {
        return '';
    }

    function getSortOrder() {
        return $this->get('sort');
    }

    private function getProperties() {

        if (!isset($this->_properties)) {
            $this->_properties = $this->get('properties');
            if (is_string($this->_properties))
                $this->_properties = JsonDataParser::parse($this->_properties);
            elseif (!$this->_properties)
                $this->_properties = array();
        }

        return $this->_properties;
    }

    function getTranslateTag($subtag) {
        return _H(sprintf('status.%s.%s', $subtag, $this->id));
    }
    function getLocal($subtag, $default) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $default;
    }
    static function getLocalById($id, $subtag, $default) {
        $tag = _H(sprintf('status.%s.%s', $subtag, $id));
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $default;
    }

    // TemplateVariable interface
    static function getVarScope() {
        $base = array(
            'name' => __('Status label'),
            'state' => __('State name (e.g. open or closed)'),
        );
        return $base;
    }

    // Searchable interface
    static function getSearchableFields() {
        return array(
            'state' => new TicketStateChoiceField(array(
                'label' => __('State'),
            )),
            'id' => new TicketStatusChoiceField(array(
                'label' => __('Status Name'),
            )),
        );
    }

    static function supportsCustomData() {
        return false;
    }

    function getList() {
        if (!isset($this->_list))
            $this->_list = DynamicList::lookup(array('type' => 'ticket-status'));
        return $this->_list;
    }

    function getListId() {
        if (($list = $this->getList()))
            return $list->getId();
    }

    function getConfigurationForm($source=null) {
        if (!$this->_form) {
            $config = $this->getConfiguration();
            // Forcefully retain state for internal statuses
            if ($source && $this->isInternal())
                $source['state'] = $this->getState();
            $this->_form = $this->getList()->getConfigurationForm($source);
            if (!$source && $config) {
                $fields = $this->_form->getFields();
                foreach ($fields as $f) {
                    $val = $config[$f->get('id')] ?: $config[$f->get('name')];
                    if (isset($val))
                        $f->value = $f->to_php($val);
                    elseif ($f->get('default'))
                        $f->value = $f->get('default');
                }
            }

            if ($this->isInternal()
                    && ($f=$this->_form->getField('state'))) {
                $f->ht['required'] = $f->ht['editable'] = false;
                $f->options['render_mode'] = 'view';
            }

        }

        return $this->_form;
    }

    function getFields() {
        return $this->getConfigurationForm()->getFields();
    }

    function getConfiguration() {

        if (!$this->_settings) {
            $this->_settings = $this->getProperties();
            if (!$this->_settings)
                $this->_settings = array();

            if ($form = $this->getList()->getForm()) {
                foreach ($form->getFields() as $f)  {
                    $name = mb_strtolower($f->get('name'));
                    $id = $f->get('id');
                    switch($name) {
                        case 'flags':
                            foreach (TicketFlagField::$_flags as $k => $v)
                                if ($this->hasFlag('flags', $v['flag']))
                                    $this->_settings[$id][$k] = $v['name'];
                            break;
                        case 'state':
                            $this->_settings[$id][$this->get('state')] = $this->get('state');
                            break;
                        default:
                            if (!$this->_settings[$id] && $this->_settings[$name])
                                $this->_settings[$id] = $this->_settings[$name];
                    }
                }
            }
        }

        return $this->_settings;
    }

    function setConfiguration($vars, &$errors=array()) {
        $properties = array();
        foreach ($this->getConfigurationForm($vars)->getFields() as $f) {
            // Only bother with editable fields
            if (!$f->isEditable()) continue;

            $val = $f->getClean();
            $errors = array_merge($errors, $f->errors());
            if ($f->errors()) continue;
            $name = mb_strtolower($f->get('name'));
            switch ($name) {
                case 'flags':
                    if ($val && is_array($val)) {
                        $flags = 0;
                        foreach ($val as $k => $v) {
                            if (isset(TicketFlagField::$_flags[$k]))
                                $flags += TicketFlagField::$_flags[$k]['flag'];
                            elseif (!$f->errors())
                                $f->addError(__('Unknown or invalid flag'), $name);
                        }
                        $this->set('flags', $flags);
                    } elseif ($val && !$f->errors()) {
                        $f->addError(__('Unknown or invalid flag format'), $name);
                    }
                    break;
                case 'state':
                    // Internal statuses cannot change state
                    if ($this->isInternal())
                        break;

                    if ($val)
                        $this->set('state', $val);
                    else
                        $f->addError(__('Unknown or invalid state'), $name);
                    break;
                default: //Custom properties the user might add.
                    $properties[$f->get('id')] = $f->to_php($val);
            }
            // Add field specific validation errors (warnings)
            $errors = array_merge($errors, $f->errors());
        }

        if (count($errors) === 0) {
            if ($properties && is_array($properties))
                $properties = JsonDataEncoder::encode($properties);

            $this->set('properties', $properties);
            $this->save(true);
        }

        return count($errors) === 0;
    }

    function display() {
        return $this->getName();
    }

    function update($vars, &$errors) {
        $fields = array('name', 'sort');
        foreach($fields as $k) {
            if (isset($vars[$k]))
                $this->set($k, $vars[$k]);
        }
        return $this->save();
    }

    function delete() {

        // Statuses with tickets are not deletable
        if (!$this->isDeletable() || !parent::delete())
            return false;

        Signal::send('object.deleted', $this);

        return true;
    }

    function __toString() {
        return $this->getName();
    }

    static function create($ht=false) {
        if (!is_array($ht))
            return null;

        if (!isset($ht['mode']))
            $ht['mode'] = 1;

        $ht['created'] = new SqlFunction('NOW');

        return new static($ht);
    }

    static function lookup($var, $list=null) {

        if (!($item = parent::lookup($var)))
            return null;

        $item->_list = $list;

        return $item;
    }


    static function __create($ht, &$error=false) {
        global $ost;

        $ht['properties'] = JsonDataEncoder::encode($ht['properties']);
        if (($status = TicketStatus::create($ht)))
            $status->save(true);

        return $status;
    }

    static function status_options() {
        include(STAFFINC_DIR . 'templates/status-options.tmpl.php');
    }
}
?>
