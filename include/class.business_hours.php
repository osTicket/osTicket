<?php
/*
 * Business Hours List
 * Used in SLA Plan
 * TODO: find out what this does... and then leave a comment about it
 * TODO: Split businesshour and businesshourlist to it's own file
 */
require_once(INCLUDE_DIR .'class.list.php');

/*
Flags based on ticket status flags, but not required
0000011000000000001 <- correct but user editable
0000111000000000001 <- incorrect and usereditable
1110111000011110001 <- Incorrect but internal
1110011000011110001 <- correct
*/
class BusinessHoursList extends CustomListHandler {

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
        return BusinessHours::objects()->count();
    }

    function getAllItems() {
        if (!$this->_items)
            $this->_items = BusinessHours::objects()->order_by($this->getListOrderBy());

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
            $filters['mode__hasbit'] = BusinessHours::ENABLED;
        if ($criteria['states'] && is_array($criteria['states']))
            $filters['state__in'] = $criteria['states'];
        else
            $filters['state__isnull'] = false;

        $items = BusinessHours::objects();
        if ($filters)
            $items->filter($filters);
        if ($criteria['limit'])
            $items->limit($criteria['limit']);
        if ($criteria['offset'])
            $items->offset($criteria['offset']);

        $items->order_by($this->getListOrderBy());

        return $items;
    }

    function getItem($val) {

        if (!is_int($val))
            $val = array('name' => $val);

         return BusinessHours::lookup($val, $this);
    }

    function addItem($vars, &$errors) {
        $item = BusinessHours::create(array(
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
                        array('type' => 'business-hours'))))
            $statuses = $list->getItems($criteria);

        return $statuses;
    }

    static function __load() {
        require_once(INCLUDE_DIR.'class.i18n.php');

        $i18n = new Internationalization();
        $tpl = $i18n->getTemplate('list.yaml');
        foreach ($tpl->getData() as $f) {
            if ($f['type'] == 'business-hours') {
                $list = DynamicList::create($f);
                $list->save();
                break;
            }
        }

        if (!$list || !($o=DynamicForm::objects()->filter(
                        array('type'=>'L'.$list->getId()))))
            return false;

        // Create default statuses
        if (($statuses = $i18n->getTemplate('business_hours.yaml')->getData()))
            foreach ($statuses as $status)
                BusinessHours::__create($status);

        return $o[0];
    }

    function getExtraConfigOptions($source=null) {
        $status_choices = array( 0 => __('System Default'));
        if (($statuses=BusinessHoursList::getStatuses(
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
            $extras=$this->getExtraConfigOptions($source);
            if ($f->get('name') == 'state' //TODO: check if editable.
                    && ($extras)) {
                foreach ($extras as $extra) {
                    $extra->setForm($form);
                    $fields->insert(++$k, $extra);
                }
            }

            if (!isset($f->ht['editable']))
                $f->ht['editable'] = true;
        }

        // Enable selection and display of private states
        //$form->getField('state')->options['private_too'] = true; we have no state field
        // TODO: We have no state field

        return $form;
    }

    function getListItemBasicForm($source=null, $item=false) {
        return new SimpleForm(array(
            'name' => new TextboxField(array(
                'required' => true,
                'label' => __('Label'),
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
CustomListHandler::register('business-hours', 'BusinessHoursList');

class BusinessHours
extends VerySimpleModel
implements CustomListItem, TemplateVariable {

    static $meta = array(
        'table' => BUSINESS_HOURS_TABLE,
        'ordering' => array('name'),
        'pk' => array('id'),
        'joins' => array(
            'tickets' => array(
                'reverse' => 'TicketModel.status',
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

    function getName() {
        return $this->get('name');
    }

    function getState() {
        return $this->get('state');
    }

    function getValue() {
        return $this->getName();
    }
    function getLocalName() {
        return $this->getLocal('value', $this->getName());
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

    // general method used on DynamicList, we're borrowing for buiseness hours temporarily
    // name is equivalent to the variable column in Manage -> Lists -> Property -> Variable
    function getVar($name) {
        $config = $this->getConfiguration();
        $name = mb_strtolower($name);
        foreach ($this->getConfigurationForm()->getFields() as $field) {
            if (mb_strtolower($field->get('name')) == $name)
                return $field->asVar($config[$field->get('id')]);
        }
    }

    // name is equivalent to the variable column in Manage -> Lists -> Property -> Variable
    // returns an array of business hours where index 0 is sunday
    function getUnixMtF() {
        $config = $this->getConfiguration();
        $days = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
        $result = [];
        foreach ($this->getConfigurationForm()->getFields() as $field) {
            $key = array_search($field->get('name'), $days);
            if ( $key !== false) {
                $result[$key] = $field->asVar($config[$field->get('id')]); // TODO: what is asVar? it seems to get the value.
            }
        }
        $result = count($result) === 7 ? $result : null;
        ksort($result);
        return $result;
    }



    // TemplateVariable interface
    static function getVarScope() {
        $base = array(
            'name' => __('Status label'),
            'state' => __('State name (e.g. open or closed)'),
        );
        return $base;
    }

    function getList() {
        if (!isset($this->_list))
            $this->_list = DynamicList::lookup(array('type' => 'business-hours'));
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

            /* if ($this->isInternal() We don't have a state field, sir.
                    && ($f=$this->_form->getField('state'))) {
                $f->ht['required'] = $f->ht['editable'] = false;
                $f->options['render_mode'] = 'view';
            }*/

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
        return sprintf('<a class="preview" href="#"
                data-preview="#list/%d/items/%d/preview">%s</a>',
                $this->getListId(),
                $this->getId(),
                $this->getLocalName()
                );
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
        if (!$this->isDeletable())
            return false;

        return parent::delete();
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
