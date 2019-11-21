<?php
/*********************************************************************
    class.queue.php

    Custom (ticket) queues for osTicket

    Jared Hancock <jared@osticket.com>
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2015 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class CustomQueue extends VerySimpleModel {
    static $meta = array(
        'table' => QUEUE_TABLE,
        'pk' => array('id'),
        'ordering' => array('sort'),
        'select_related' => array('parent', 'default_sort'),
        'joins' => array(
            'children' => array(
                'reverse' => 'CustomQueue.parent',
                'constrain' => ['children__id__gt' => 0],
            ),
            'columns' => array(
                'reverse' => 'QueueColumnGlue.queue',
                'constrain' => array('staff_id' =>'QueueColumnGlue.staff_id'),
                'broker' => 'QueueColumnListBroker',
            ),
            'sorts' => array(
                'reverse' => 'QueueSortGlue.queue',
                'broker' => 'QueueSortListBroker',
            ),
            'default_sort' => array(
                'constraint' => array('sort_id' => 'QueueSort.id'),
                'null' => true,
            ),
            'exports' => array(
                'reverse' => 'QueueExport.queue',
            ),
            'parent' => array(
                'constraint' => array(
                    'parent_id' => 'CustomQueue.id',
                ),
                'null' => true,
            ),
            'staff' => array(
                'constraint' => array(
                    'staff_id' => 'Staff.staff_id',
                )
            ),
        )
    );

    const FLAG_PUBLIC =           0x0001; // Shows up in e'eryone's saved searches
    const FLAG_QUEUE =            0x0002; // Shows up in queue navigation
    const FLAG_DISABLED =         0x0004; // NOT enabled
    const FLAG_INHERIT_CRITERIA = 0x0008; // Include criteria from parent
    const FLAG_INHERIT_COLUMNS =  0x0010; // Inherit column layout from parent
    const FLAG_INHERIT_SORTING =  0x0020; // Inherit advanced sorting from parent
    const FLAG_INHERIT_DEF_SORT = 0x0040; // Inherit default selected sort
    const FLAG_INHERIT_EXPORT  =  0x0080; // Inherit export fields from parent


    const FLAG_INHERIT_EVERYTHING = 0x158; // Maskf or all INHERIT flags

    var $criteria;
    var $_conditions;

    static function queues() {
        return parent::objects()->filter(array(
            'flags__hasbit' => static::FLAG_QUEUE
        ));
    }

    function __onload() {
        // Ensure valid state
        if ($this->hasFlag(self::FLAG_INHERIT_COLUMNS) && !$this->parent_id)
            $this->clearFlag(self::FLAG_INHERIT_COLUMNS);

       if ($this->hasFlag(self::FLAG_INHERIT_EXPORT) && !$this->parent_id)
            $this->clearFlag(self::FLAG_INHERIT_EXPORT);
    }

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->title;
    }

    function getHref() {
        // TODO: Get base page from getRoot();
        $root = $this->getRoot();
        return 'tickets.php?queue='.$this->getId();
    }

    function getRoot() {
        switch ($this->root) {
        case 'T':
        default:
            return 'Ticket';
        }
    }

    function getPath() {
        return $this->path ?: $this->buildPath();
    }

    function criteriaRequired() {
        return true;
    }

    function getCriteria($include_parent=false) {
        if (!isset($this->criteria)) {
            $this->criteria = is_string($this->config)
                ? JsonDataParser::decode($this->config)
                : $this->config;
            // XXX: Drop this block in v1.12
            // Auto-upgrade v1.10 saved-search criteria to new format
            // But support new style with `conditions` support
            $old = @$this->config[0] === '{';
            if ($old && is_array($this->criteria)
                && !isset($this->criteria['conditions'])
            ) {
                // TODO: Upgrade old ORM path names
                // Parse criteria out of JSON if any.
                $this->criteria = self::isolateCriteria($this->criteria,
                        $this->getRoot());
            }
        }
        $criteria = $this->criteria ?: array();
        // Support new style with `conditions` support
        if (isset($criteria['criteria']))
            $criteria = $criteria['criteria'];
        if ($include_parent && $this->parent_id && $this->parent) {
            $criteria = array_merge($this->parent->getCriteria(true),
                $criteria);
        }
        return $criteria;
    }

    function describeCriteria($criteria=false){
        $all = $this->getSupportedMatches($this->getRoot());
        $items = array();
        $criteria = $criteria ?: $this->getCriteria(true);
        foreach ($criteria ?: array() as $C) {
            list($path, $method, $value) = $C;
            if ($path === ':keywords') {
                $items[] = Format::htmlchars("\"{$value}\"");
                continue;
            }
            if (!isset($all[$path]))
                continue;
            list($label, $field) = $all[$path];
            $items[] = $field->describeSearch($method, $value, $label);
        }
        return implode("\nAND ", $items);
    }

    /**
     * Fetch an AdvancedSearchForm instance for use in displaying or
     * configuring this search in the user interface.
     *
     * Parameters:
     * $search - <array> Request parameters ($_POST) used to update the
     *      search beyond the current configuration of the search criteria
     * $searchables - search fields - default to current if not provided
     */
    function getForm($source=null, $searchable=null) {
        $fields = array();
        if (!isset($searchable)) {
            $fields = array(
                ':keywords' => new TextboxField(array(
                    'id' => 3001,
                    'configuration' => array(
                        'size' => 40,
                        'length' => 400,
                        'autofocus' => true,
                        'classes' => 'full-width headline',
                        'placeholder' => __('Keywords — Optional'),
                    ),
                    'validators' => function($self, $v) {
                        if (mb_str_wc($v) > 3)
                            $self->addError(__('Search term cannot have more than 3 keywords'));
                    },
                )),
            );

            $searchable = $this->getCurrentSearchFields($source);
        }

        foreach ($searchable ?: array() as $path => $field)
            $fields = array_merge($fields, static::getSearchField($field, $path));

        $form = new AdvancedSearchForm($fields, $source);

        // Field selection validator
        if ($this->criteriaRequired()) {
            $form->addValidator(function($form) {
                    if (!$form->getNumFieldsSelected())
                        $form->addError(__('No fields selected for searching'));
                });
        }

        // Load state from current configuraiton
        if (!$source) {
            foreach ($this->getCriteria() as $I) {
                list($path, $method, $value) = $I;
                if ($path == ':keywords' && $method === null) {
                    if ($F = $form->getField($path))
                        $F->value = $value;
                    continue;
                }

                if (!($F = $form->getField("{$path}+search")))
                    continue;
                $F->value = true;

                if (!($F = $form->getField("{$path}+method")))
                    continue;
                $F->value = $method;

                if ($value && ($F = $form->getField("{$path}+{$method}")))
                    $F->value = $value;
            }
        }
        return $form;
    }

    /**
     * Fetch a bucket of fields for a custom search. The fields should be
     * added to a form before display. One searchable field may encompass 10
     * or more actual fields because fields are expanded to support multiple
     * search methods along with the fields for each search method. This
     * method returns all the FormField instances for all the searchable
     * model fields currently in use.
     *
     * Parameters:
     * $source - <array> data from a request. $source['fields'] is expected
     *      to contain a list extra fields by ORM path, of newly added
     *      fields not yet saved in this object's getCriteria().
     */
    function getCurrentSearchFields($source=array(), $criteria=array()) {
        static $basic = array(
            'Ticket' => array(
                'status__id',
                'status__state',
                'dept_id',
                'assignee',
                'topic_id',
                'created',
                'est_duedate',
                'duedate',
            )
        );

        $all = $this->getSupportedMatches();
        $core = array();

        // Include basic fields for new searches
        if (!isset($this->id))
            foreach ($basic[$this->getRoot()] as $path)
                if (isset($all[$path]))
                    $core[$path] = $all[$path];

        // Add others from current configuration
        foreach ($criteria ?: $this->getCriteria() as $C) {
            list($path) = $C;
            if (isset($all[$path]))
                $core[$path] = $all[$path];
        }

        if (isset($source['fields']))
            foreach ($source['fields'] as $path)
                if (isset($all[$path]))
                    $core[$path] = $all[$path];

        return $core;
    }

    /**
    * Fetch all supported ORM fields filterable by this search object.
    */
    function getSupportedFilters() {
        return static::getFilterableFields($this->getRoot());
    }


    /**
     * Get get supplemental matches for public queues.
     *
     */

    function getSupplementalMatches() {
        return array();
    }

    function getSupplementalCriteria() {
        return array();
    }

    /**
     * Fetch all supported ORM fields searchable by this search object. The
     * returned list represents searchable fields, keyed by the ORM path.
     * Use ::getCurrentSearchFields() or ::getSearchField() to retrieve for
     * use in the user interface.
     */
    function getSupportedMatches() {
        return static::getSearchableFields($this->getRoot());
    }

    /**
     * Trace ORM fields from a base object and retrieve a complete list of
     * fields which can be used in an ORM query based on the base object.
     * The base object must implement Searchable interface and extend from
     * VerySimpleModel. Then all joins from the object are also inspected,
     * and any which implement the Searchable interface are traversed and
     * automatically added to the list. The resulting list is cached based
     * on the $base class, so multiple calls for the same $base return
     * quickly.
     *
     * Parameters:
     * $base - Class, name of a class implementing Searchable
     * $recurse - int, number of levels to recurse, default is 2
     * $cache - bool, cache results for future class for the same base
     * $customData - bool, include all custom data fields for all general
     *      forms
     */
    static function getSearchableFields($base, $recurse=2,
        $customData=true, $exclude=array()
    ) {
        static $cache = array(), $otherFields;

        // Early exit if already cached
        $fields = &$cache[$base];
        if ($fields)
            return $fields;

        if (!in_array('Searchable', class_implements($base)))
            return array();

        $fields = $fields ?: array();
        foreach ($base::getSearchableFields() as $path=>$F) {
            if (is_array($F)) {
                list($label, $field) = $F;
            }
            else {
                $label = $F->getLocal('label');
                $field = $F;
            }
            $fields[$path] = array($label, $field);
        }

        if ($customData && $base::supportsCustomData()) {
            if (!isset($otherFields)) {
                $otherFields = array();
                $dfs = DynamicFormField::objects()
                    ->filter(array('form__type' => 'G'))
                    ->select_related('form');
                foreach ($dfs as $field) {
                    $otherFields[$field->getId()] = array($field->form,
                        $field->getImpl());
                }
            }
            foreach ($otherFields as $id=>$F) {
                list($form, $field) = $F;
                $label = sprintf("%s / %s",
                    $form->getTitle(), $field->getLocal('label'));
                $fields["entries__answers!{$id}__value"] = array(
                    $label, $field);
            }
        }

        if ($recurse) {
            $exclude[$base] = 1;
            foreach ($base::getMeta('joins') as $path=>$j) {
                $fc = $j['fkey'][0];
                if (isset($exclude[$fc]) || $j['list'])
                    continue;
                foreach (static::getSearchableFields($fc, $recurse-1,
                    true, $exclude)
                as $path2=>$F) {
                    list($label, $field) = $F;
                    $fields["{$path}__{$path2}"] = array(
                        sprintf("%s / %s", $fc, $label),
                        $field);
                }
            }
        }

        // Sort the field listing by the (localized) label name
        if (function_exists('collator_create')) {
            $coll = Collator::create(Internationalization::getCurrentLanguage());
            $keys = array_map(function($a) use ($coll) {
                return $coll->getSortKey($a[0]); #nolint
            }, $fields);
        }
        else {
            // Fall back to 8-bit string sorting
            $keys = array_map(function($a) { return $a[0]; }, $fields);
        }
        array_multisort($keys, $fields);

        return $fields;
    }

  /**
     * Fetch all searchable fileds, for the base object  which support quick filters.
     */
    function getFilterableFields($object) {
        $filters = array();
        foreach (static::getSearchableFields($object) as $p => $f) {
            list($label, $field) = $f;
            if ($field && $field->supportsQuickFilter())
                $filters[$p] = $f;
        }

        return $filters;
    }

    /**
     * Fetch the FormField instances used when for configuring a searchable
     * field in the user interface. This is the glue between a field
     * representing a searchable model field and the configuration of that
     * search in the user interface.
     *
     * Parameters:
     * $F - <array<string, FormField>> the label and the FormField instance
     *      representing the configurable search
     * $name - <string> ORM path for the search
     */
    static function getSearchField($F, $name) {
        list($label, $field) = $F;

        $pieces = array();
        $pieces["{$name}+search"] = new BooleanField(array(
            'id' => sprintf('%u', crc32($name)) >> 1,
            'configuration' => array(
                'desc' => $label ?: $field->getLocal('label'),
                'classes' => 'inline',
            ),
        ));
        $methods = $field->getSearchMethods();

        //remove future options for datetime fields that can't be in the future
        if (in_array($field->getLabel(), DateTimeField::getPastPresentLabels()))
          unset($methods['ndays'], $methods['future'], $methods['distfut']);

        $pieces["{$name}+method"] = new ChoiceField(array(
            'choices' => $methods,
            'default' => key($methods),
            'visibility' => new VisibilityConstraint(new Q(array(
                "{$name}+search__eq" => true,
            )), VisibilityConstraint::HIDDEN),
        ));
        $offs = 0;
        foreach ($field->getSearchMethodWidgets() as $m=>$w) {
            if (!$w)
                continue;
            list($class, $args) = $w;
            $args['required'] = true;
            $args['__searchval__'] = true;
            $args['visibility'] = new VisibilityConstraint(new Q(array(
                    "{$name}+method__eq" => $m,
                )), VisibilityConstraint::HIDDEN);
            $pieces["{$name}+{$m}"] = new $class($args);
        }
        return $pieces;
    }

    function getField($path) {
        $searchable = $this->getSupportedMatches();
        return $searchable[$path];
    }

    // Remove this and adjust advanced-search-criteria template to use the
    // getCriteria() list and getField()
    function getSearchFields($form=false) {
        $form = $form ?: $this->getForm();
        $searchable = $this->getCurrentSearchFields();
        $info = array();
        foreach ($form->getFields() as $f) {
            if (substr($f->get('name'), -7) == '+search') {
                $name = substr($f->get('name'), 0, -7);
                $value = null;
                // Determine the search method and fetch the original field
                if (($M = $form->getField("{$name}+method"))
                    && ($method = $M->getClean())
                    && (list(,$field) = $searchable[$name])
                ) {
                    // Request the field to generate a search Q for the
                    // search method and given value
                    if ($value = $form->getField("{$name}+{$method}"))
                        $value = $value->getClean();
                }
                $info[$name] = array(
                    'field' => $field,
                    'method' => $method,
                    'value' => $value,
                    'active' =>  $f->getClean(),
                );
            }
        }
        return $info;
    }

    /**
     * Take the criteria from the SavedSearch fields setup and isolate the
     * field name being search, the method used for searhing, and the method-
     * specific data entered in the UI.
     */
    static function isolateCriteria($criteria, $base='Ticket') {

        if (!is_array($criteria))
            return null;

        $items = array();
        $searchable = static::getSearchableFields($base);
        foreach ($criteria as $k=>$v) {
            if (substr($k, -7) === '+method') {
                list($name,) = explode('+', $k, 2);
                if (!isset($searchable[$name]))
                    continue;

                // Require checkbox to be checked too
                if (!$criteria["{$name}+search"])
                    continue;

                // Lookup the field to search this condition
                list($label, $field) = $searchable[$name];
                // Get the search method
                $method = is_array($v) ? key($v) : $v;
                // Not all search methods require a value
                $value = $criteria["{$name}+{$method}"];

                $items[] = array($name, $method, $value);
            }
        }
        if (isset($criteria[':keywords'])
            && ($kw = $criteria[':keywords'])
        ) {
            $items[] = array(':keywords', null, $kw);
        }
        return $items;
    }

    function getConditions() {
        if (!isset($this->_conditions)) {
            $this->getCriteria();
            $conds = array();
            if (is_array($this->criteria)
                && isset($this->criteria['conditions'])
            ) {
                $conds = $this->criteria['conditions'];
            }
            foreach ($conds as $C)
                if ($T = QueueColumnCondition::fromJson($C))
                    $this->_conditions[] = $T;
        }
        return $this->_conditions;
    }

    function getExportableFields() {
        $cdata = $fields = array();
        foreach (TicketForm::getInstance()->getFields() as $f) {
            // Ignore core fields
            if (in_array($f->get('name'), array('priority')))
                continue;
            // Ignore non-data fields
            elseif (!$f->hasData() || $f->isPresentationOnly())
                continue;
            // Ignore disabled fields
            elseif (!$f->hasFlag(DynamicFormField::FLAG_ENABLED))
                continue;

            $name = $f->get('name') ?: 'field_'.$f->get('id');
            $key = 'cdata__'.$name;
            $cdata[$key] = $f->getLocal('label');
        }

        // Standard export fields if none is provided.
        $fields = array(
                'number' =>         __('Ticket Number'),
                'created' =>        __('Date Created'),
                'cdata__subject' =>  __('Subject'),
                'user__name' =>      __('From'),
                'user__emails__address' => __('From Email'),
                'cdata__priority' => __('Priority'),
                'dept_id' => __('Department'),
                'topic_id' => __('Help Topic'),
                'source' =>         __('Source'),
                'status__id' =>__('Current Status'),
                'lastupdate' =>     __('Last Updated'),
                'est_duedate' =>    __('SLA Due Date'),
                'duedate' =>        __('Due Date'),
                'closed' =>         __('Closed Date'),
                'isoverdue' =>      __('Overdue'),
                'merged' =>       __('Merged'),
                'linked' =>       __('Linked'),
                'isanswered' =>     __('Answered'),
                'staff_id' => __('Agent Assigned'),
                'team_id' =>  __('Team Assigned'),
                'thread_count' =>   __('Thread Count'),
                'reopen_count' =>   __('Reopen Count'),
                'attachment_count' => __('Attachment Count'),
                'task_count' => __('Task Count'),
                ) + $cdata;

        return $fields;
    }

    function getExportFields($inherit=true) {

        $fields = array();
        if ($inherit
            && $this->parent_id
            && $this->hasFlag(self::FLAG_INHERIT_EXPORT)
            && $this->parent
        ) {
            $fields = $this->parent->getExportFields();
        }
        elseif (count($this->exports)) {
            foreach ($this->exports as $f)
                $fields[$f->path] = $f->getHeading();
        }
        elseif ($this->isAQueue())
            $fields = $this->getExportableFields();

        if (!count($fields))
            $fields = $this->getExportableFields();

        return $fields;
    }

    function getExportColumns($fields=array()) {
        $columns = array();
        $fields = $fields ?: $this->getExportFields();
        $i = 0;
        foreach ($fields as $path => $label) {
            $c = QueueColumn::placeholder(array(
                        'id' => $i++,
                        'heading' => $label,
                        'primary' => $path,
                        ));
            $c->setQueue($this);
            $columns[$path] = $c;
        }
        return $columns;
    }

    function getStandardColumns() {
        return $this->getColumns();
    }

    function getColumns($use_template=false) {
        if ($this->columns_id
            && ($q = CustomQueue::lookup($this->columns_id))
        ) {
            // Use columns from cited queue
            return $q->getColumns();
        }
        elseif ($this->parent_id
            && $this->hasFlag(self::FLAG_INHERIT_COLUMNS)
            && $this->parent
        ) {
            $columns = $this->parent->getColumns();
            foreach ($columns as $c)
                $c->setQueue($this);
            return $columns;
        }
        elseif (count($this->columns)) {
            return $this->columns;
        }

        // Use the columns of the "Open" queue as a default template
        if ($use_template && ($template = CustomQueue::lookup(1)))
            return $template->getColumns();

        // Last resort — use standard columns
        foreach (array(
            QueueColumn::placeholder(array(
                "id" => 1,
                "heading" => "Number",
                "primary" => 'number',
                "width" => 85,
                "bits" => QueueColumn::FLAG_SORTABLE,
                "filter" => "link:ticketP",
                "annotations" => '[{"c":"TicketSourceDecoration","p":"b"}, {"c":"MergedFlagDecoration","p":">"}]',
                "conditions" => '[{"crit":["isanswered","nset",null],"prop":{"font-weight":"bold"}}]',
            )),
            QueueColumn::placeholder(array(
                "id" => 2,
                "heading" => "Created",
                "primary" => 'created',
                "filter" => 'date:full',
                "truncate" =>'wrap',
                "width" => 120,
                "bits" => QueueColumn::FLAG_SORTABLE,
            )),
            QueueColumn::placeholder(array(
                "id" => 3,
                "heading" => "Subject",
                "primary" => 'cdata__subject',
                "width" => 250,
                "bits" => QueueColumn::FLAG_SORTABLE,
                "filter" => "link:ticket",
                "annotations" => '[{"c":"TicketThreadCount","p":">"},{"c":"ThreadAttachmentCount","p":"a"},{"c":"OverdueFlagDecoration","p":"<"}]',
                "conditions" => '[{"crit":["isanswered","nset",null],"prop":{"font-weight":"bold"}}]',
                "truncate" => 'ellipsis',
            )),
            QueueColumn::placeholder(array(
                "id" => 4,
                "heading" => "From",
                "primary" => 'user__name',
                "width" => 150,
                "bits" => QueueColumn::FLAG_SORTABLE,
            )),
            QueueColumn::placeholder(array(
                "id" => 5,
                "heading" => "Priority",
                "primary" => 'cdata__priority',
                "width" => 120,
                "bits" => QueueColumn::FLAG_SORTABLE,
            )),
            QueueColumn::placeholder(array(
                "id" => 8,
                "heading" => "Assignee",
                "primary" => 'assignee',
                "width" => 100,
                "bits" => QueueColumn::FLAG_SORTABLE,
            )),
        ) as $col)
            $this->addColumn($col);

        return $this->getColumns();
    }

    function addColumn(QueueColumn $col) {
        $this->columns->add($col);
        $col->queue = $this;
    }

    function getColumn($id) {
        // TODO: Got to be easier way to search instrumented list.
        foreach ($this->getColumns() as $C)
            if ($C->getId() == $id)
                return $C;
    }

    function getSortOptions() {
        if ($this->inheritSorting() && $this->parent) {
            return $this->parent->getSortOptions();
        }
        return $this->sorts;
    }

    function getDefaultSortId() {
        if ($this->isDefaultSortInherited() && $this->parent
            && ($sort_id = $this->parent->getDefaultSortId())
        ) {
            return $sort_id;
        }
        return $this->sort_id;
    }

    function getDefaultSort() {
        if ($this->isDefaultSortInherited() && $this->parent
            && ($sort = $this->parent->getDefaultSort())
        ) {
            return $sort;
        }
        return $this->default_sort;
    }

    function getStatus() {
        return $this->hasFlag(self::FLAG_DISABLED)
            ? __('Disabled') : __('Active');
    }

    function getChildren() {
        return $this->children;
    }

    function getPublicChildren() {
        return $this->children->findAll(array(
            'flags__hasbit' => self::FLAG_QUEUE
        ));
    }

    function getMyChildren() {
        global $thisstaff;
        if (!$thisstaff instanceof Staff)
            return array();

        return $this->children->findAll(array(
            'staff_id' => $thisstaff->getId(),
            Q::not(array(
                'flags__hasbit' => self::FLAG_PUBLIC
            ))
        ));
    }

    function export(CsvExporter $exporter, $options=array()) {
        global $thisstaff;

        if (!$thisstaff
                || !($query=$this->getQuery())
                || !($fields=$this->getExportFields()))
            return false;

        // See if we have cached export preference
        if (isset($_SESSION['Export:Q'.$this->getId()])) {
            $opts = $_SESSION['Export:Q'.$this->getId()];
            if (isset($opts['fields'])) {
                $fields = array_intersect_key($fields,
                        array_flip($opts['fields']));
                $exportableFields = CustomQueue::getExportableFields();
                foreach ($opts['fields'] as $key => $name) {
                    if (is_null($fields[$name]) && isset($exportableFields)) {
                        $fields[$name] = $exportableFields[$name];
                    }
                 }
            }
        }

        // Apply columns
        $columns = $this->getExportColumns($fields);
        $headers = array(); // Reset fields based on validity of columns
        foreach ($columns as $column) {
            $query = $column->mangleQuery($query, $this->getRoot());
            $headers[] = $column->getHeading();
        }

        // Apply visibility
        if (!$this->ignoreVisibilityConstraints($thisstaff))
            $query->filter($thisstaff->getTicketsVisibility());

        // Get stashed sort or else get the default
        if (!($sort = $_SESSION['sort'][$this->getId()]))
            $sort = $this->getDefaultSort();

        // Apply sort
        if ($sort instanceof QueueSort)
            $sort->applySort($query);
        elseif ($sort && isset($sort['queuesort']))
            $sort['queuesort']->applySort($query, $sort['dir']);
        elseif ($sort && $sort['col'] &&
                ($C=$this->getColumn($sort['col'])))
            $query = $C->applySort($query, $sort['dir']);
        else
            $query->order_by('-created');

        // Render Util
        $render = function ($row) use($columns) {
            if (!$row) return false;

            $record = array();
            foreach ($columns as $path => $column) {
                $record[] = (string) $column->from_query($row) ?:
                    $row[$path] ?: '';
            }
            return $record;
        };

        $exporter->write($headers);
        foreach ($query as $row)
            $exporter->write($render($row));
    }

    /**
     * Add critiera to a query based on the constraints configured for this
     * queue. The criteria of the parent queue is also automatically added
     * if the queue is configured to inherit the criteria.
     */
    function getBasicQuery() {
        if ($this->parent && $this->inheritCriteria()) {
            $query = $this->parent->getBasicQuery();
        }
        else {
            $root = $this->getRoot();
            $query = $root::objects();
        }
        return $this->mangleQuerySet($query);
    }

    /**
     * Retrieve a QuerySet instance based on the type of object (root) of
     * this Q, which is automatically configured with the data and criteria
     * of the queue and its columns.
     *
     * Returns:
     * <QuerySet> instance
     */
    function getQuery($form=false, $quick_filter=null) {
        // Start with basic criteria
        $query = $this->getBasicQuery($form);

        // Apply quick filter
        if (isset($quick_filter)
            && ($qf = $this->getQuickFilterField($quick_filter))
        ) {
            $filter = @self::getOrmPath($this->getQuickFilter(), $query);
            $query = $qf->applyQuickFilter($query, $quick_filter,
                $filter);
        }

        // Apply column, annotations and conditions additions
        foreach ($this->getColumns() as $C) {
            $C->setQueue($this);
            $query = $C->mangleQuery($query, $this->getRoot());
        }
        return $query;
    }

    function getQuickFilter() {
        if ($this->filter == '::' && $this->parent) {
            return $this->parent->getQuickFilter();
        }
        return $this->filter;
    }

    function getQuickFilterField($value=null) {
        if ($this->filter == '::') {
            if ($this->parent) {
                return $this->parent->getQuickFilterField($value);
            }
        }
        elseif ($this->filter
            && ($fields = self::getSearchableFields($this->getRoot()))
            && (list(,$f) = @$fields[$this->filter])
            && $f->supportsQuickFilter()
        ) {
            $f->value = $value;
            return $f;
        }
    }

    /**
     * Get a description of a field in a search. Expects an entry from the
     * array retrieved in ::getSearchFields()
     */
    function describeField($info, $name=false) {
        $name = $name ?: $info['field']->get('label');
        return $info['field']->describeSearch($info['method'], $info['value'], $name);
    }

    function mangleQuerySet(QuerySet $qs, $form=false) {
        $qs = clone $qs;
        $searchable = $this->getSupportedMatches();

        // Figure out fields to search on
        foreach ($this->getCriteria() as $I) {
            list($name, $method, $value) = $I;

            // Consider keyword searching
            if ($name === ':keywords') {
                global $ost;
                $qs = $ost->searcher->find($value, $qs, false);
            }
            else {
                // XXX: Move getOrmPath to be more of a utility
                // Ensure the special join is created to support custom data joins
                $name = @static::getOrmPath($name, $qs);

                if (preg_match('/__answers!\d+__/', $name)) {
                    $qs->annotate(array($name => SqlAggregate::MAX($name)));
                }

                // Fetch a criteria Q for the query
                if (list(,$field) = $searchable[$name])
                    if ($q = $field->getSearchQ($method, $value, $name))
                        $qs = $qs->filter($q);
            }
        }

        return $qs;
    }

    function applyDefaultSort($qs) {
        // Apply default sort
        if ($sorter = $this->getDefaultSort()) {
            $qs = $sorter->applySort($qs, false, $this->getRoot());
        }
        return $qs;
    }

    function checkAccess(Staff $agent) {
        return $this->isPublic() || $this->checkOwnership($agent);
    }

    function checkOwnership(Staff $agent) {

        return ($agent->getId() == $this->staff_id &&
                !$this->isAQueue());
    }

    function isOwner(Staff $agent) {
        return $agent && $this->isPrivate() && $this->checkOwnership($agent);
    }

    function isSaved() {
        return true;
    }

    function ignoreVisibilityConstraints(Staff $agent) {
        // For searches (not queues), some staff can have a permission to
        // see all records
        return ($this->isASearch()
                && $this->isOwner($agent)
                && $agent->canSearchEverything());
    }

    function inheritCriteria() {
        return $this->flags & self::FLAG_INHERIT_CRITERIA &&
            $this->parent_id;
    }

    function inheritColumns() {
        return $this->hasFlag(self::FLAG_INHERIT_COLUMNS);
    }

    function useStandardColumns() {
        return !count($this->columns);
    }

    function inheritExport() {
        return ($this->hasFlag(self::FLAG_INHERIT_EXPORT) ||
                !count($this->exports));
    }

    function inheritSorting() {
        return $this->hasFlag(self::FLAG_INHERIT_SORTING);
    }

    function isDefaultSortInherited() {
        return $this->hasFlag(self::FLAG_INHERIT_DEF_SORT);
    }

    function buildPath() {
        if (!$this->id)
            return;

        $path = $this->parent ? $this->parent->buildPath() : '';
        return rtrim($path, "/") . "/{$this->id}/";
    }

    function getFullName() {
        $base = $this->getName();
        if ($this->parent)
            $base = sprintf("%s / %s", $this->parent->getFullName(), $base);
        return $base;
    }

    function isASubQueue() {
        return $this->parent ? $this->parent->isASubQueue() :
            $this->isAQueue();
    }

    function isAQueue() {
        return $this->hasFlag(self::FLAG_QUEUE);
    }

    function isASearch() {
        return !$this->isAQueue() || !$this->isSaved();
    }

    function isPrivate() {
        return !$this->isAQueue() && $this->staff_id;
    }

    function isPublic() {
        return $this->hasFlag(self::FLAG_PUBLIC);
    }

    protected function hasFlag($flag) {
        return ($this->flags & $flag) !== 0;
    }

    protected function clearFlag($flag) {
        return $this->flags &= ~$flag;
    }

    protected function setFlag($flag, $value=true) {
        return $value
            ? $this->flags |= $flag
            : $this->clearFlag($flag);
    }

    function disable() {
        $this->setFlag(self::FLAG_DISABLED);
    }

    function enable() {
        $this->clearFlag(self::FLAG_DISABLED);
    }

    function getRoughCount() {
        if (($count = $this->getRoughCountAPC()) !== false)
            return $count;

        $query = Ticket::objects();
        $Q = $this->getBasicQuery();
        $expr = SqlCase::N()->when(new SqlExpr(new Q($Q->constraints)),
            new SqlField('ticket_id'));
        $query = $query->aggregate(array(
            "ticket_count" => SqlAggregate::COUNT($expr)
        ));

        $row = $query->values()->one();
        return $row['ticket_count'];
    }

    function getRoughCountAPC() {
        if (!function_exists('apcu_store'))
            return false;

        $key = "rough.counts.".SECRET_SALT;
        $cached = false;
        $counts = apcu_fetch($key, $cached);
        if ($cached === true && isset($counts["q{$this->id}"]))
            return $counts["q{$this->id}"];

        // Fetch rough counts of all queues. That is, fetch a total of the
        // counts based on the queue criteria alone. Do no consider agent
        // access. This should be fast and "rought"
        $queues = static::objects()
            ->filter(['flags__hasbit' => CustomQueue::FLAG_PUBLIC])
            ->exclude(['flags__hasbit' => CustomQueue::FLAG_DISABLED]);

        $query = Ticket::objects();
        $prefix = "";

        foreach ($queues as $queue) {
            $Q = $queue->getBasicQuery();
            $expr = SqlCase::N()->when(new SqlExpr(new Q($Q->constraints)),
                new SqlField('ticket_id'));
            $query = $query->aggregate(array(
                "q{$queue->id}" => SqlAggregate::COUNT($expr)
            ));
        }

        $counts = $query->values()->one();

        apcu_store($key, $counts, 900);
        return @$counts["q{$this->id}"];
    }

    function updateExports($fields, $save=true) {

        if (!$fields)
            return false;

        $order = array_keys($fields);

        $new = $fields;
        foreach ($this->exports as $f) {
            $heading = $f->getHeading();
            $key = $f->getPath();
            if (!isset($fields[$key])) {
                $this->exports->remove($f);
                continue;
            }

            $f->set('heading', $heading);
            $f->set('sort', array_search($key, $order)+1);
            unset($new[$key]);
        }

        $exportableFields = CustomQueue::getExportableFields();
        foreach ($new as $k => $field) {
            if (isset($exportableFields[$k]))
                $heading = $exportableFields[$k];
            elseif (is_array($field))
                $heading = $field['heading'];
            else
                $heading = $field;

            $f = QueueExport::create(array(
                        'path' => $k,
                        'heading' => $heading,
                        'sort' => array_search($k, $order)+1));
            $this->exports->add($f);
        }

        $this->exports->sort(function($f) { return $f->sort; });

        if (!count($this->exports) && $this->parent)
            $this->hasFlag(self::FLAG_INHERIT_EXPORT);

        if ($save)
            $this->exports->saveAll();

        return true;
    }

    function update($vars, &$errors=array()) {

        // Set basic search information
        if (!$vars['queue-name'])
            $errors['queue-name'] = __('A title is required');
        elseif (($q=CustomQueue::lookup(array(
                        'title' => $vars['queue-name'],
                        'parent_id' => $vars['parent_id'] ?: 0,
                        'staff_id'  => $this->staff_id)))
                && $q->getId() != $this->id
                )
            $errors['queue-name'] = __('Saved queue with same name exists');

        $this->title = $vars['queue-name'];
        $this->parent_id = @$vars['parent_id'] ?: 0;
        if ($this->parent_id && !$this->parent)
            $errors['parent_id'] = __('Select a valid queue');

        // Try to avoid infinite recursion determining ancestry
        if ($this->parent_id && isset($this->id)) {
            $P = $this;
            while ($P = $P->parent)
                if ($P->parent_id == $this->id)
                    $errors['parent_id'] = __('Cannot be a descendent of itself');
        }

        // Configure quick filter options
        $this->filter = $vars['filter'];
        if ($vars['sort_id']) {
            if ($vars['filter'] === '::') {
                if (!$this->parent)
                    $errors['filter'] = __('No parent selected');
            }
            elseif ($vars['filter'] && !array_key_exists($vars['filter'],
                static::getSearchableFields($this->getRoot()))
            ) {
                $errors['filter'] = __('Select an item from the list');
            }
        }

        // Set basic queue information
        $this->path = $this->buildPath();
        $this->setFlag(self::FLAG_INHERIT_CRITERIA, $this->parent_id);
        $this->setFlag(self::FLAG_INHERIT_COLUMNS,
            $this->parent_id > 0 && isset($vars['inherit-columns']));
        $this->setFlag(self::FLAG_INHERIT_EXPORT,
            $this->parent_id > 0 && isset($vars['inherit-exports']));
        $this->setFlag(self::FLAG_INHERIT_SORTING,
            $this->parent_id > 0 && isset($vars['inherit-sorting']));

        // Update queue columns (but without save)
        if (!isset($vars['columns']) && $this->parent) {
            // No columns -- imply column inheritance
            $this->setFlag(self::FLAG_INHERIT_COLUMNS);
        }


        if ($this->getId()
                && isset($vars['columns'])
                && !$this->hasFlag(self::FLAG_INHERIT_COLUMNS)) {


            if ($this->columns->updateColumns($vars['columns'], $errors, array(
                                'queue_id' => $this->getId(),
                                'staff_id' => $this->staff_id)))
                $this->columns->reset();
        }

        // Update export fields for the queue
        if (isset($vars['exports']) &&
                 !$this->hasFlag(self::FLAG_INHERIT_EXPORT)) {
            $this->updateExports($vars['exports'], false);
        }

        if (!count($this->exports) && $this->parent)
            $this->hasFlag(self::FLAG_INHERIT_EXPORT);

        // Update advanced sorting options for the queue
        if (isset($vars['sorts']) && !$this->hasFlag(self::FLAG_INHERIT_SORTING)) {
            $new = $order = $vars['sorts'];
            foreach ($this->sorts as $sort) {
                $key = $sort->sort_id;
                $idx = array_search($key, $vars['sorts']);
                if (false === $idx) {
                    $this->sorts->remove($sort);
                }
                else {
                    $sort->set('sort', $idx);
                    unset($new[$idx]);
                }
            }
            // Add new columns
            foreach ($new as $id) {
                if (!$sort = QueueSort::lookup($id))
                    continue;
                $glue = new QueueSortGlue(array(
                    'sort_id' => $id,
                    'queue' => $this,
                    'sort' => array_search($id, $order),
                ));
                $this->sorts->add($sort, $glue);
            }
            // Re-sort the in-memory columns array
            $this->sorts->sort(function($c) { return $c->sort; });
        }
        if (!count($this->sorts) && $this->parent) {
            // No sorting -- imply sorting inheritance
            $this->setFlag(self::FLAG_INHERIT_SORTING);
        }

        // Configure default sorting
        $this->setFlag(self::FLAG_INHERIT_DEF_SORT,
            $this->parent && $vars['sort_id'] === '::');
        if ($vars['sort_id']) {
            if ($vars['sort_id'] === '::') {
                if (!$this->parent)
                    $errors['sort_id'] = __('No parent selected');
                else
                     $this->sort_id = 0;
            }
            elseif ($qs = QueueSort::lookup($vars['sort_id'])) {
                $this->sort_id = $vars['sort_id'];
            }
            else {
                $errors['sort_id'] = __('Select an item from the list');
            }
        } else
             $this->sort_id = 0;

        list($this->_conditions, $conditions)
            = QueueColumn::getConditionsFromPost($vars, $this->id, $this->getRoot());

        // TODO: Move this to SavedSearch::update() and adjust
        //       AjaxSearch::_saveSearch()
        $form = $form ?: $this->getForm($vars);
        if (!$vars) {
            $errors['criteria'] = __('No criteria specified');
        }
        elseif (!$form->isValid()) {
            $errors['criteria'] = __('Validation errors exist on criteria');
        }
        else {
            $this->criteria = static::isolateCriteria($form->getClean(),
                $this->getRoot());
            $this->config = JsonDataEncoder::encode([
                'criteria' => $this->criteria,
                'conditions' => $conditions,
            ]);
            // Clear currently set criteria.and conditions.
             $this->criteria = $this->_conditions = null;
        }

        return 0 === count($errors);
    }

    function psave() {
        return parent::save();
    }

    function save($refetch=false) {

        $nopath = !isset($this->path);
        $path_changed = isset($this->dirty['parent_id']);

        if ($this->dirty)
            $this->updated = SqlFunction::NOW();

        $clearCounts = ($this->dirty || $this->__new__);
        if (!($rv = parent::save($refetch || $this->dirty)))
            return $rv;

        if ($nopath) {
            $this->path = $this->buildPath();
            $this->save();
        }
        if ($path_changed) {
            $this->children->reset();
            $move_children = function($q) use (&$move_children) {
                foreach ($q->children as $qq) {
                    $qq->path = $qq->buildPath();
                    $qq->save();
                    $move_children($qq);
                }
            };
            $move_children($this);
        }

        // Refetch the queue counts
        if ($clearCounts)
            SavedQueue::clearCounts();

        return $this->columns->saveAll()
            && $this->exports->saveAll()
            && $this->sorts->saveAll();
    }

    /**
     * Fetch a tree-organized listing of the queues. Each queue is listed in
     * the tree exactly once, and every visible queue is represented. The
     * returned structure is an array where the items are two-item arrays
     * where the first item is a CustomQueue object an the second is a list
     * of the children using the same pattern (two-item arrays of a CustomQueue
     * and its children). Visually:
     *
     * [ [ $queue, [ [ $child, [] ], [ $child, [] ] ], [ $queue, ... ] ]
     *
     * Parameters:
     * $staff - <Staff> staff object which should be used to determine
     *      visible queues.
     * $pid - <int> parent_id of root queue. Default is zero (top-level)
     */
    static function getHierarchicalQueues(Staff $staff, $pid=0,
            $primary=true) {
        $query = static::objects()
            ->annotate(array('_sort' =>  SqlCase::N()
                        ->when(array('sort' => 0), 999)
                        ->otherwise(new SqlField('sort'))))
            ->filter(Q::any(array(
                'flags__hasbit' => self::FLAG_PUBLIC,
                'flags__hasbit' => static::FLAG_QUEUE,
                'staff_id' => $staff->getId(),
            )))
            ->exclude(['flags__hasbit' => self::FLAG_DISABLED])
            ->order_by('parent_id', '_sort', 'title');
        $all = $query->asArray();
        // Find all the queues with a given parent
        $for_parent = function($pid) use ($primary, $all, &$for_parent) {
            $results = [];
            foreach (new \ArrayIterator($all) as $q) {
                if ($q->parent_id != $pid)
                    continue;

                if ($pid == 0 && (
                            ($primary &&  !$q->isAQueue())
                            || (!$primary && $q->isAQueue())))
                    continue;

                $results[] = [ $q, $for_parent($q->getId()) ];
            }

            return $results;
        };

        return $for_parent($pid);
    }

    static function getOrmPath($name, $query=null) {
        // Special case for custom data `__answers!id__value`. Only add the
        // join and constraint on the query the first pass, when the query
        // being mangled is received.
        $path = array();
        if ($query && preg_match('/^(.+?)__(answers!(\d+))/', $name, $path)) {
            // Add a join to the model of the queryset where the custom data
            // is forked from — duplicate the 'answers' join and add the
            // constraint to the query based on the field_id
            // $path[1] - part before the answers (user__org__entries)
            // $path[2] - answers!xx join part
            // $path[3] - the `xx` part of the answers!xx join component
            $root = $query->model;
            $meta = $root::getMeta()->getByPath($path[1]);
            $joins = $meta['joins'];
            if (!isset($joins[$path[2]])) {
                $meta->addJoin($path[2], $joins['answers']);
            }
            // Ensure that the query join through answers!xx is only for the
            // records which match field_id=xx
            $query->constrain(array("{$path[1]}__{$path[2]}" =>
                array("{$path[1]}__{$path[2]}__field_id" => (int) $path[3])
            ));
            // Leave $name unchanged
        }
        return $name;
    }


    static function create($vars=false) {

        $queue = new static($vars);
        $queue->created = SqlFunction::NOW();
        if (!isset($vars['flags'])) {
            $queue->setFlag(self::FLAG_PUBLIC);
            $queue->setFlag(self::FLAG_QUEUE);
        }

        return $queue;
    }

    static function __create($vars) {
        $q = static::create($vars);
        $q->psave();
        foreach ($vars['columns'] ?: array() as $info) {
            $glue = new QueueColumnGlue($info);
            $glue->queue_id = $q->getId();
            $glue->save();
        }
        if (isset($vars['sorts'])) {
            foreach ($vars['sorts'] as $info) {
                $glue = new QueueSortGlue($info);
                $glue->queue_id = $q->getId();
                $glue->save();
            }
        }
        return $q;
    }
}

abstract class QueueColumnAnnotation {
    static $icon = false;
    static $desc = '';

    var $config;

    function __construct($config) {
        $this->config = $config;
    }

    static function fromJson($config) {
        $class = $config['c'];
        if (class_exists($class))
            return new $class($config);
    }

    static function getDescription() {
        return __(static::$desc);
    }
    static function getIcon() {
        return static::$icon;
    }
    static function getPositions() {
        return array(
            "<" => __('Start'),
            "b" => __('Before'),
            "a" => __('After'),
            ">" => __('End'),
        );
    }

    function decorate($text, $dec) {
        static $positions = array(
            '<' => '<span class="pull-left">%2$s</span>%1$s',
            '>' => '<span class="pull-right">%2$s</span>%1$s',
            'a' => '%1$s%2$s',
            'b' => '%2$s%1$s',
        );

        $pos = $this->getPosition();
        if (!isset($positions[$pos]))
            return $text;

        return sprintf($positions[$pos], $text, $dec);
    }

    // Render the annotation with the database record $row. $text is the
    // text of the cell before annotations were applied.
    function render($row, $cell) {
        if ($decoration = $this->getDecoration($row, $cell))
            return $this->decorate($cell, $decoration);

        return $cell;
    }

    // Add the annotation to a QuerySet
    abstract function annotate($query, $name);

    // Fetch some HTML to render the decoration on the page. This function
    // can return boolean FALSE to indicate no decoration should be applied
    abstract function getDecoration($row, $text);

    function getPosition() {
        return strtolower($this->config['p']) ?: 'a';
    }

    function getClassName() {
        return @$this->config['c'] ?: get_class();
    }

    static function getAnnotations($root) {
        // Ticket annotations
        static $annotations;
        if (!isset($annotations[$root])) {
            foreach (get_declared_classes() as $class)
                if (is_subclass_of($class, get_called_class()))
                    $annotations[$root][] = $class;
        }
        return $annotations[$root];
    }

    /**
     * Estimate the width of the rendered annotation in pixels
     */
    function getWidth($row) {
        return $this->isVisible($row) ? 25 : 0;
    }

    function isVisible($row) {
        return true;
    }

    static function addToQuery($query, $name=false) {
        $name = $name ?: static::$qname;
        $annotation = new Static(array());
        return $annotation->annotate($query, $name);
    }

    static function from_query($row, $name=false) {
        $name = $name ?: static::$qname;
        return $row[$name];
    }
}

class TicketThreadCount
extends QueueColumnAnnotation {
    static $icon = 'comments-alt';
    static $qname = '_thread_count';
    static $desc = /* @trans */ 'Thread Count';

    function annotate($query, $name=false) {
        $name = $name ?: static::$qname;
        return $query->annotate(array(
            $name => TicketThread::objects()
            ->filter(array('ticket__ticket_id' => new SqlField('ticket_id', 1)))
            ->exclude(array('entries__flags__hasbit' => ThreadEntry::FLAG_HIDDEN))
            ->aggregate(array('count' => SqlAggregate::COUNT('entries__id')))
        ));
    }

    function getDecoration($row, $text) {
        $threadcount = $row[static::$qname];
        if ($threadcount > 1) {
            return sprintf(
                '<small class="faded-more"><i class="icon-comments-alt"></i> %s</small>',
                $threadcount
            );
        }
    }

    function isVisible($row) {
        return $row[static::$qname] > 1;
    }
}

class TicketReopenCount
extends QueueColumnAnnotation {
    static $icon = 'folder-open-alt';
    static $qname = '_reopen_count';
    static $desc = /* @trans */ 'Reopen Count';

    function annotate($query, $name=false) {
        $name = $name ?: static::$qname;
        return $query->annotate(array(
            $name => TicketThread::objects()
            ->filter(array('ticket__ticket_id' => new SqlField('ticket_id', 1)))
            ->filter(array('events__annulled' => 0, 'events__event_id' => Event::getIdByName('reopened')))
            ->aggregate(array('count' => SqlAggregate::COUNT('events__id')))
        ));
    }

    function getDecoration($row, $text) {
        $reopencount = $row[static::$qname];
        if ($reopencount) {
            return sprintf(
                '&nbsp;<small class="faded-more"><i class="icon-%s"></i> %s</small>',
                static::$icon,
                $reopencount > 1 ? $reopencount : ''
            );
        }
    }

    function isVisible($row) {
        return $row[static::$qname];
    }
}

class ThreadAttachmentCount
extends QueueColumnAnnotation {
    static $icon = 'paperclip';
    static $qname = '_att_count';
    static $desc = /* @trans */ 'Attachment Count';

    function annotate($query, $name=false) {
        // TODO: Convert to Thread attachments
        $name = $name ?: static::$qname;
        return $query->annotate(array(
            $name => TicketThread::objects()
            ->filter(array('ticket__ticket_id' => new SqlField('ticket_id', 1)))
            ->filter(array('entries__attachments__inline' => 0))
            ->aggregate(array('count' => SqlAggregate::COUNT('entries__attachments__id')))
        ));
    }

    function getDecoration($row, $text) {
        $count = $row[static::$qname];
        if ($count) {
            return sprintf(
                '<i class="small icon-paperclip icon-flip-horizontal" data-toggle="tooltip" title="%s"></i>',
                $count);
        }
    }

    function isVisible($row) {
        return $row[static::$qname] > 0;
    }
}

class TicketTasksCount
extends QueueColumnAnnotation {
    static $icon = 'list-ol';
    static $qname = '_task_count';
    static $desc = /* @trans */ 'Tasks Count';

    function annotate($query, $name=false) {
        $name = $name ?: static::$qname;
        return $query->annotate(array(
            $name => Task::objects()
            ->filter(array('ticket__ticket_id' => new SqlField('ticket_id', 1)))
            ->aggregate(array('count' => SqlAggregate::COUNT('id')))
        ));
    }

    function getDecoration($row, $text) {
        $count = $row[static::$qname];
        if ($count) {
            return sprintf(
                '<small class="faded-more"><i class="icon-%s"></i> %s</small>',
                static::$icon, $count);
        }
    }

    function isVisible($row) {
        return $row[static::$qname];
    }
}

class ThreadCollaboratorCount
extends QueueColumnAnnotation {
    static $icon = 'group';
    static $qname = '_collabs';
    static $desc = /* @trans */ 'Collaborator Count';

    function annotate($query, $name=false) {
        $name = $name ?: static::$qname;
        return $query->annotate(array(
            $name => TicketThread::objects()
            ->filter(array('ticket__ticket_id' => new SqlField('ticket_id', 1)))
            ->aggregate(array('count' => SqlAggregate::COUNT('collaborators__id')))
        ));
    }

    function getDecoration($row, $text) {
        $count = $row[static::$qname];
        if ($count) {
            return sprintf(
                '<span class="pull-right faded-more" data-toggle="tooltip" title="%d"><i class="icon-group"></i></span>',
                $count);
        }
    }

    function isVisible($row) {
        return $row[static::$qname] > 0;
    }
}

class OverdueFlagDecoration
extends QueueColumnAnnotation {
    static $icon = 'exclamation';
    static $desc = /* @trans */ 'Overdue Icon';

    function annotate($query, $name=false) {
        return $query->values('isoverdue');
    }

    function getDecoration($row, $text) {
        if ($row['isoverdue'])
            return '<span class="Icon overdueTicket"></span>';
    }

    function isVisible($row) {
        return $row['isoverdue'];
    }
}

class MergedFlagDecoration
extends QueueColumnAnnotation {
    static $icon = 'code-fork';
    static $desc = /* @trans */ 'Merged Icon';

    function annotate($query, $name=false) {
        return $query->values('ticket_pid', 'flags');
    }

    function getDecoration($row, $text) {
        $flags = $row['flags'];
        $combine = ($flags & Ticket::FLAG_COMBINE_THREADS) != 0;
        $separate = ($flags & Ticket::FLAG_SEPARATE_THREADS) != 0;
        $linked = ($flags & Ticket::FLAG_LINKED) != 0;

        if ($combine || $separate) {
            return sprintf('<a data-placement="bottom" data-toggle="tooltip" title="%s" <i class="icon-code-fork"></i></a>',
                           $combine ? __('Combine') : __('Separate'));
        } elseif ($linked)
            return '<i class="icon-link"></i>';
    }

    function isVisible($row) {
        return $row['ticket_pid'];
    }
}

class LinkedFlagDecoration
extends QueueColumnAnnotation {
    static $icon = 'link';
    static $desc = /* @trans */ 'Linked Icon';

    function annotate($query, $name=false) {
        return $query->values('ticket_pid', 'flags');
    }

    function getDecoration($row, $text) {
        $flags = $row['flags'];
        $linked = ($flags & Ticket::FLAG_LINKED) != 0;
        if ($linked && $_REQUEST['a'] == 'search')
            return '<i class="icon-link"></i>';
    }

    function isVisible($row) {
        return $row['ticket_pid'];
    }
}

class TicketSourceDecoration
extends QueueColumnAnnotation {
    static $icon = 'phone';
    static $desc = /* @trans */ 'Ticket Source';

    function annotate($query, $name=false) {
        return $query->values('source');
    }

    function getDecoration($row, $text) {
        return sprintf('<span class="Icon %sTicket"></span>',
            strtolower($row['source']));
    }
}

class LockDecoration
extends QueueColumnAnnotation {
    static $icon = "lock";
    static $desc = /* @trans */ 'Locked';

    function annotate($query, $name=false) {
        global $thisstaff;

        return $query
            ->annotate(array(
                '_locked' => new SqlExpr(new Q(array(
                    'lock__expire__gt' => SqlFunction::NOW(),
                    Q::not(array('lock__staff_id' => $thisstaff->getId())),
                )))
            ));
    }

    function getDecoration($row, $text) {
        if ($row['_locked'])
            return sprintf('<span class="Icon lockedTicket"></span>');
    }

    function isVisible($row) {
        return $row['_locked'];
    }
}

class AssigneeAvatarDecoration
extends QueueColumnAnnotation {
    static $icon = "user";
    static $desc = /* @trans */ 'Assignee Avatar';

    function annotate($query, $name=false) {
        return $query->values('staff_id', 'team_id');
    }

    function getDecoration($row, $text) {
        if ($row['staff_id'] && ($staff = Staff::lookup($row['staff_id'])))
            return sprintf('<span class="avatar">%s</span>',
                $staff->getAvatar(16));
        elseif ($row['team_id'] && ($team = Team::lookup($row['team_id']))) {
            $avatars = [];
            foreach ($team->getMembers() as $T)
                $avatars[] = $T->getAvatar(16);
            return sprintf('<span class="avatar group %s">%s</span>',
                count($avatars), implode('', $avatars));
        }
    }

    function isVisible($row) {
        return $row['staff_id'] + $row['team_id'] > 0;
    }

    function getWidth($row) {
        if (!$this->isVisible($row))
            return 0;

        // If assigned to a team with no members, return 0 width
        $width = 10;
        if ($row['team_id'] && ($team = Team::lookup($row['team_id'])))
            $width += (count($team->getMembers()) - 1) * 10;

        return $width ? $width + 10 : $width;
    }
}

class UserAvatarDecoration
extends QueueColumnAnnotation {
    static $icon = "user";
    static $desc = /* @trans */ 'User Avatar';

    function annotate($query, $name=false) {
        return $query->values('user_id');
    }

    function getDecoration($row, $text) {
        if ($row['user_id'] && ($user = User::lookup($row['user_id'])))
            return sprintf('<span class="avatar">%s</span>',
                $user->getAvatar(16));
    }

    function isVisible($row) {
        return $row['user_id'] > 0;
    }
}

class DataSourceField
extends ChoiceField {
    function getChoices($verbose=false) {
        $config = $this->getConfiguration();
        $root = $config['root'];
        $fields = array();
        foreach (CustomQueue::getSearchableFields($root) as $path=>$f) {
            list($label,) = $f;
            $fields[$path] = $label;
        }
        return $fields;
    }
}

class QueueColumnCondition {
    var $config;
    var $queue;
    var $properties = array();

    static $uid = 1;

    function __construct($config, $queue=null) {
        $this->config = $config;
        $this->queue = $queue;
        if (is_array($config['prop']))
            $this->properties = $config['prop'];
    }

    function getProperties() {
        return $this->properties;
    }

    // Add the annotation to a QuerySet
    function annotate($query) {
        if (!($Q = $this->getSearchQ($query)))
            return $query;

        // Add an annotation to the query
        return $query->annotate(array(
            $this->getAnnotationName() => new SqlExpr(array($Q))
        ));
    }

    function getField($name=null) {
        // FIXME
        #$root = $this->getColumn()->getRoot();
        $root = 'Ticket';
        $searchable = CustomQueue::getSearchableFields($root);

        if (!isset($name))
            list($name) = $this->config['crit'];

        // Lookup the field to search this condition
        if (isset($searchable[$name])) {
            return $searchable[$name];
        }
    }

    function getFieldName() {
        list($name) = $this->config['crit'];
        return $name;
    }

    function getCriteria() {
        return $this->config['crit'];
    }

    function getSearchQ($query) {
        list($name, $method, $value) = $this->config['crit'];

        // XXX: Move getOrmPath to be more of a utility
        // Ensure the special join is created to support custom data joins
        $name = @CustomQueue::getOrmPath($name, $query);

        $name2 = null;
        if (preg_match('/__answers!\d+__/', $name)) {
            // Ensure that only one record is returned from the join through
            // the entry and answers joins
            $name2 = $this->getAnnotationName().'2';
            $query->annotate(array($name2 => SqlAggregate::MAX($name)));
        }

        // Fetch a criteria Q for the query
        if (list(,$field) = $this->getField($name))
            return $field->getSearchQ($method, $value, $name2 ?: $name);
    }

    /**
     * Take the criteria from the SavedSearch fields setup and isolate the
     * field name being search, the method used for searhing, and the method-
     * specific data entered in the UI.
     */
    static function isolateCriteria($criteria, $base='Ticket') {
        $searchable = CustomQueue::getSearchableFields($base);
        foreach ($criteria as $k=>$v) {
            if (substr($k, -7) === '+method') {
                list($name,) = explode('+', $k, 2);
                if (!isset($searchable[$name]))
                    continue;

                // Lookup the field to search this condition
                list($label, $field) = $searchable[$name];

                // Get the search method and value
                $method = $v;
                // Not all search methods require a value
                $value = $criteria["{$name}+{$method}"];

                return array($name, $method, $value);
            }
        }
    }

    function render($row, $text, &$styles=array()) {
        if ($V = $row[$this->getAnnotationName()]) {
            foreach ($this->getProperties() as $css=>$value) {
                $field = QueueColumnConditionProperty::getField($css);
                $field->value = $value;
                $V = $field->getClean();
                if (is_array($V))
                    $V = current($V);
                $styles[$css] = $V;
            }
        }
        return $text;
    }

    function getAnnotationName() {
        // This should be predictable based on the criteria so that the
        // query can deduplicate the same annotations used in different
        // conditions
        if (!isset($this->annotation_name)) {
            $this->annotation_name = $this->getShortHash();
        }
        return $this->annotation_name;
    }

    function __toString() {
        list($name, $method, $value) = $this->config['crit'];
        if (is_array($value))
            $value = implode('+', $value);

        return "{$name} {$method} {$value}";
    }

    function getHash($binary=false) {
        return sha1($this->__toString(), $binary);
    }

    function getShortHash() {
        return substr(base64_encode($this->getHash(true)), 0, 7);
    }

    static function getUid() {
        return static::$uid++;
    }

    static function fromJson($config, $queue=null) {
        if (is_string($config))
            $config = JsonDataParser::decode($config);
        if (!is_array($config))
            throw new BadMethodCallException('$config must be string or array');

        return new static($config, $queue);
    }
}

class QueueColumnConditionProperty
extends ChoiceField {
    static $properties = array(
        'background-color' => 'ColorChoiceField',
        'color' => 'ColorChoiceField',
        'font-family' => array(
            'monospace', 'serif', 'sans-serif', 'cursive', 'fantasy',
        ),
        'font-size' => array(
            'small', 'medium', 'large', 'smaller', 'larger',
        ),
        'font-style' => array(
            'normal', 'italic', 'oblique',
        ),
        'font-weight' => array(
            'lighter', 'normal', 'bold', 'bolder',
        ),
        'text-decoration' => array(
            'none', 'underline',
        ),
        'text-transform' => array(
            'uppercase', 'lowercase', 'captalize',
        ),
    );

    function __construct($property) {
        $this->property = $property;
    }

    static function getProperties() {
        return array_keys(static::$properties);
    }

    static function getField($prop) {
        $choices = static::$properties[$prop];
        if (!isset($choices))
            return null;
        if (is_array($choices))
            return new ChoiceField(array(
                'name' => $prop,
                'choices' => array_combine($choices, $choices),
            ));
        elseif (class_exists($choices))
            return new $choices(array('name' => $prop));
    }

    function getChoices($verbose=false) {
        if (isset($this->property))
            return static::$properties[$this->property];

        $keys = array_keys(static::$properties);
        return array_combine($keys, $keys);
    }
}

class LazyDisplayWrapper {
    function __construct($field, $value) {
        $this->field = $field;
        $this->value = $value;
        $this->safe = false;
    }

    /**
     * Allow a filter to change the value of this to a "safe" value which
     * will not be automatically encoded with htmlchars()
     */
    function changeTo($what, $safe=false) {
        $this->field = null;
        $this->value = $what;
        $this->safe = $safe;
    }

    function __toString() {
        return $this->display();
    }

    function display(&$styles=array()) {
        if (isset($this->field))
            return $this->field->display(
                $this->field->to_php($this->value), $styles);
        if ($this->safe)
            return $this->value;
        return Format::htmlchars($this->value);
    }
}

/**
 * A column of a custom queue. Columns have many customizable features
 * including:
 *
 *   * Data Source (primary and secondary)
 *   * Heading
 *   * Link (to an object like the ticket)
 *   * Size and truncate settings
 *   * annotations (like counts and flags)
 *   * Conditions (which change the formatting like bold text)
 *
 * Columns are stored in a separate table from the queue itself, but other
 * breakout items for the annotations and conditions, for instance, are stored
 * as JSON text in the QueueColumn model.
 */
class QueueColumn
extends VerySimpleModel {
    static $meta = array(
        'table' => COLUMN_TABLE,
        'pk' => array('id'),
        'ordering' => array('name'),
    );

    const FLAG_SORTABLE = 0x0001;

    var $_annotations;
    var $_conditions;
    var $_queue;            // Apparent queue if being inherited
    var $_fields;

    function getId() {
        return $this->id;
    }

    function getFilter() {
         if ($this->filter
                && ($F = QueueColumnFilter::getInstance($this->filter)))
            return $F;
     }

    function getName() {
        return $this->name;
    }

    // These getters fetch data from the annotated overlay from the
    // queue_column table
    function getQueue() {
        if (!isset($this->_queue)) {
            $queue = $this->queue;

            if (!$queue && ($queue_id = $this->queue_id) && is_numeric($queue_id))
                $queue = CustomQueue::lookup($queue_id);

            $this->_queue = $queue;
        }

        return $this->_queue;
    }
    /**
     * If a column is inherited into a child queue and there are conditions
     * added to that queue, then the column will need to be linked at
     * run-time to the child queue rather than the parent.
     */
    function setQueue(CustomQueue $queue) {
        $this->_queue = $queue;
    }

    function getFields() {
        if (!isset($this->_fields)) {
            $root = ($q = $this->getQueue()) ? $q->getRoot() : 'Ticket';
            $fields = CustomQueue::getSearchableFields($root);
            $primary = CustomQueue::getOrmPath($this->primary);
            $secondary = CustomQueue::getOrmPath($this->secondary);
            if (($F = $fields[$primary]) && (list(,$field) = $F))
                $this->_fields[$primary] = $field;
            if (($F = $fields[$secondary]) && (list(,$field) = $F))
                $this->_fields[$secondary] = $field;
        }
        return $this->_fields;
    }

    function getField($path=null) {
        $fields = $this->getFields();
        return @$fields[$path ?: $this->primary];
    }

    function getWidth() {
        return $this->width ?: 100;
    }

    function getHeading() {
        return $this->heading;
    }

    function getTranslateTag($subtag) {
        return _H(sprintf('column.%s.%s.%s', $subtag, $this->queue_id, $this->id));
    }
    function getLocal($subtag) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->get($subtag);
    }
    function getLocalHeading() {
        return $this->getLocal('heading');
    }

    protected function setFlag($flag, $value=true, $field='flags') {
        return $value
            ? $this->{$field} |= $flag
            : $this->clearFlag($flag, $field);
    }

    protected function clearFlag($flag, $field='flags') {
        return $this->{$field} &= ~$flag;
    }

    function isSortable() {
        return $this->bits & self::FLAG_SORTABLE;
    }

    function setSortable($sortable) {
        $this->setFlag(self::FLAG_SORTABLE, $sortable, 'bits');
    }

    function render($row) {
        // Basic data
        $text = $this->renderBasicValue($row);

        // Filter
        if ($text && ($filter = $this->getFilter())) {
            $text = $filter->filter($text, $row) ?: $text;
        }

        $styles = array();
        if ($text instanceof LazyDisplayWrapper) {
            $text = $text->display($styles);
        }

        // Truncate
        $text = $this->applyTruncate($text, $row);

        // annotations and conditions
        foreach ($this->getAnnotations() as $D) {
            $text = $D->render($row, $text);
        }
        foreach ($this->getConditions() as $C) {
            $text = $C->render($row, $text, $styles);
        }
        $style = Format::array_implode(':', ';', $styles);
        return array($text, $style);
    }

    function renderBasicValue($row) {
        $fields = $this->getFields();
        $primary = CustomQueue::getOrmPath($this->primary);
        $secondary = CustomQueue::getOrmPath($this->secondary);

        // Return a lazily ::display()ed value so that the value to be
        // rendered by the field could be changed or display()ed when
        // converted to a string.
        if (($F = $fields[$primary])
            && ($T = $F->from_query($row, $primary))
        ) {
            return new LazyDisplayWrapper($F, $T);
        }
        if (($F = $fields[$secondary])
            && ($T = $F->from_query($row, $secondary))
        ) {
            return new LazyDisplayWrapper($F, $T);
        }

         return new LazyDisplayWrapper($F, '');
    }

    function from_query($row) {
        if (!($f = $this->getField($this->primary)))
            return '';

        $val = $f->to_php($f->from_query($row, $this->primary));
        if (!is_string($val))
            $val = $f->display($val);

        return $val;
    }

    function applyTruncate($text, $row) {
        $offset = 0;
        foreach ($this->getAnnotations() as $a)
            $offset += $a->getWidth($row);

        $width = $this->width - $offset;
        $class = array();
        switch ($this->truncate) {
        case 'lclip':
            $linfo = Internationalization::getCurrentLanguageInfo();
            // Use `rtl` class to cut the beginning of LTR text. But, wrap
            // the text with an appropriate direction so the ending
            // punctuation is not rearranged.
            $dir = $linfo['direction'] ?: 'ltr';
            $text = sprintf('<span class="%s">%s</span>', $dir, $text);
            $class[] = $dir == 'rtl' ? 'ltr' : 'rtl';
        case 'clip':
            $class[] = 'bleed';
        case 'ellipsis':
            $class[] = 'truncate';
            return sprintf('<span class="%s" style="max-width:%dpx">%s</span>',
                implode(' ', $class), $width, $text);
        default:
        case 'wrap':
            return $text;
        }
    }

    function addToQuery($query, $field, $path) {
        if (preg_match('/__answers!\d+__/', $path)) {
            // Ensure that only one record is returned from the join through
            // the entry and answers joins
            return $query->annotate(array(
                $path => SqlAggregate::MAX($path)
            ));
        }
        return $field->addToQuery($query, $path);
    }

    function mangleQuery($query, $root=null) {
        // Basic data
        $fields = $this->getFields();
        if ($field = $fields[$this->primary]) {
            $query = $this->addToQuery($query, $field,
                CustomQueue::getOrmPath($this->primary, $query));
        }
        if ($field = $fields[$this->secondary]) {
            $query = $this->addToQuery($query, $field,
                CustomQueue::getOrmPath($this->secondary, $query));
        }

        if ($filter = $this->getFilter())
            $query = $filter->mangleQuery($query, $this);

        // annotations
        foreach ($this->getAnnotations() as $D) {
            $query = $D->annotate($query);
        }

        // Conditions
        foreach ($this->getConditions() as $C) {
            $query = $C->annotate($query);
        }

        return $query;
    }

    function applySort($query, $reverse=false) {
	    $root = ($q = $this->getQueue()) ? $q->getRoot() : 'Ticket';
        $fields = CustomQueue::getSearchableFields($root);

        $keys = array();
        if ($primary = $fields[$this->primary]) {
            list(,$field) = $primary;
            $keys[] = array(CustomQueue::getOrmPath($this->primary, $query),
                    $field);
        }

        if ($secondary = $fields[$this->secondary]) {
            list(,$field) = $secondary;
            $keys[] = array(CustomQueue::getOrmPath($this->secondary,
                        $query), $field);
        }

        if (count($keys) > 1) {
            $fields = array();
            foreach ($keys as $key) {
                list($path, $field) = $key;
                foreach ($field->getSortKeys($path) as $field)
                    $fields[]  = new SqlField($field);
            }
            // Force nulls to the buttom.
            $fields[] = 'zzz';

            $alias = sprintf('C%d', $this->getId());
            $expr = call_user_func_array(array('SqlFunction', 'COALESCE'),
                    $fields);
            $query->annotate(array($alias => $expr));

            $reverse = $reverse ? '-' : '';
            $query = $query->order_by("{$reverse}{$alias}");
        } elseif($keys[0]) {
            list($path, $field) = $keys[0];
            $query = $field->applyOrderBy($query, $reverse, $path);
        }

        return $query;
    }

    function getDataConfigForm($source=false) {
        return new QueueColDataConfigForm($source ?: $this->getDbFields(),
            array('id' => $this->id));
    }

    function getAnnotations() {
        if (!isset($this->_annotations)) {
            $this->_annotations = array();
            if ($this->annotations
                && ($anns = JsonDataParser::decode($this->annotations))
            ) {
                foreach ($anns as $D)
                    if ($T = QueueColumnAnnotation::fromJson($D))
                        $this->_annotations[] = $T;
            }
        }
        return $this->_annotations;
    }

    function getConditions($include_queue=true) {
        if (!isset($this->_conditions)) {
            $this->_conditions = array();
            if ($this->conditions
                && ($conds = JsonDataParser::decode($this->conditions))
            ) {
                foreach ($conds as $C)
                    if ($T = QueueColumnCondition::fromJson($C))
                        $this->_conditions[] = $T;
            }
            // Support row-spanning conditions
            if ($include_queue && ($q = $this->getQueue())
                && ($q_conds = $q->getConditions())
            ) {
                $this->_conditions = array_merge($q_conds, $this->_conditions);
            }
        }
        return $this->_conditions;
    }

    static function __create($vars) {
        $c = new static($vars);
        $c->save();
        return $c;
    }

    static function placeholder($vars) {
        return static::__hydrate($vars);
    }

    function update($vars, $root='Ticket') {
        $form = $this->getDataConfigForm($vars);
        foreach ($form->getClean() as $k=>$v)
            $this->set($k, $v);

        // Do the annotations
        $this->_annotations = $annotations = array();
        if (isset($vars['annotations'])) {
            foreach (@$vars['annotations'] as $i=>$class) {
                if ($vars['deco_column'][$i] != $this->id)
                    continue;
                if (!class_exists($class) || !is_subclass_of($class, 'QueueColumnAnnotation'))
                    continue;
                $json = array('c' => $class, 'p' => $vars['deco_pos'][$i]);
                $annotations[] = $json;
                $this->_annotations[] = QueueColumnAnnotation::fromJson($json);
            }
        }

        // Do the conditions
        $this->_conditions = $conditions = array();
        if (isset($vars['conditions'])) {
            list($this->_conditions, $conditions)
                = self::getConditionsFromPost($vars, $this->id, $root);
        }

        // Store as JSON array
        $this->annotations = JsonDataEncoder::encode($annotations);
        $this->conditions = JsonDataEncoder::encode($conditions);
    }

    static function getConditionsFromPost(array $vars, $myid, $root='Ticket') {
        $condition_objects = $conditions = array();

        if (!isset($vars['conditions']))
            return array($condition_objects, $conditions);

        foreach (@$vars['conditions'] as $i=>$id) {
            if ($vars['condition_column'][$i] != $myid)
                // Not a condition for this column
                continue;
            // Determine the criteria
            $name = $vars['condition_field'][$i];
            $fields = CustomQueue::getSearchableFields($root);
            if (!isset($fields[$name]))
                // No such field exists for this queue root type
                continue;
            $parts = CustomQueue::getSearchField($fields[$name], $name);
            $search_form = new SimpleForm($parts, $vars, array('id' => $id));
            $search_form->getField("{$name}+search")->value = true;
            $crit = $search_form->getClean();
            // Check the box to enable searching on the field
            $crit["{$name}+search"] = true;

            // Isolate only the critical parts of the criteria
            $crit = QueueColumnCondition::isolateCriteria($crit);

            // Determine the properties
            $props = array();
            foreach ($vars['properties'] as $i=>$cid) {
                if ($cid != $id)
                    // Not a property for this condition
                    continue;

                // Determine the property configuration
                $prop = $vars['property_name'][$i];
                if (!($F = QueueColumnConditionProperty::getField($prop))) {
                    // Not a valid property
                    continue;
                }
                $prop_form = new SimpleForm(array($F), $vars, array('id' => $cid));
                $props[$prop] = $prop_form->getField($prop)->getClean();
            }
            $json = array('crit' => $crit, 'prop' => $props);
            $condition_objects[] = QueueColumnCondition::fromJson($json);
            $conditions[] = $json;
        }
        return array($condition_objects, $conditions);
    }
}


class QueueConfig
extends VerySimpleModel {
    static $meta = array(
        'table' => QUEUE_CONFIG_TABLE,
        'pk' => array('queue_id', 'staff_id'),
        'joins' => array(
            'queue' => array(
                'constraint' => array(
                    'queue_id' => 'CustomQueue.id'),
            ),
            'staff' => array(
                'constraint' => array(
                    'staff_id' => 'Staff.staff_id',
                )
            ),
            'columns' => array(
                'reverse' => 'QueueColumnGlue.config',
                'constrain' => array('staff_id' =>'QueueColumnGlue.staff_id'),
                'broker' => 'QueueColumnListBroker',
            ),
        ),
    );

    function getSettings() {
        return JsonDataParser::decode($this->setting);
    }


    function update($vars, &$errors) {

        // settings of interest
        $setting = array(
                'sort_id' => (int) $vars['sort_id'],
                'filter' => $vars['filter'],
                'inherit-sort' => ($vars['sort_id'] == '::'),
                'inherit-columns' => isset($vars['inherit-columns']),
                'criteria' => $vars['criteria'] ?: array(),
                );

        if (!$setting['inherit-columns'] && $vars['columns']) {
            if (!$this->columns->updateColumns($vars['columns'], $errors, array(
                                'queue_id' => $this->queue_id,
                                'staff_id' => $this->staff_id)))
                $setting['inherit-columns'] = true;
            $this->columns->reset();
        }

        $this->setting =  JsonDataEncoder::encode($setting);
        return $this->save(true);
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    static function create($vars=false) {
        $inst = new static($vars);
        return $inst;
    }
}


class QueueExport
extends VerySimpleModel {
    static $meta = array(
        'table' => QUEUE_EXPORT_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'queue' => array(
                'constraint' => array('queue_id' => 'CustomQueue.id'),
            ),
        ),
        'select_related' => array('queue'),
        'ordering' => array('sort'),
    );


    function getPath() {
        return $this->path;
    }

    function getField() {
        return $this->getPath();
    }

    function getHeading() {
        return $this->heading;
    }

    static function create($vars=false) {
        $inst = new static($vars);
        return $inst;
    }
}

class QueueColumnGlue
extends VerySimpleModel {
    static $meta = array(
        'table' => QUEUE_COLUMN_TABLE,
        'pk' => array('queue_id', 'staff_id', 'column_id'),
        'joins' => array(
            'column' => array(
                'constraint' => array('column_id' => 'QueueColumn.id'),
            ),
            'queue' => array(
                'constraint' => array(
                    'queue_id' => 'CustomQueue.id',
                    'staff_id' => 'CustomQueue.staff_id'),
            ),
            'config' => array(
                'constraint' => array(
                    'queue_id' => 'QueueConfig.queue_id',
                    'staff_id' => 'QueueConfig.staff_id'),
            ),
        ),
        'select_related' => array('column'),
        'ordering' => array('sort'),
    );
}

class QueueColumnGlueMIM
extends ModelInstanceManager {
    function getOrBuild($modelClass, $fields, $cache=true) {
        $m = parent::getOrBuild($modelClass, $fields, $cache);
        if ($m && $modelClass === 'QueueColumnGlue') {
            // Instead, yield the QueueColumn instance with the local fields
            // in the association table as annotations
            $m = AnnotatedModel::wrap($m->column, $m, 'QueueColumn');
        }
        return $m;
    }
}

class QueueColumnListBroker
extends InstrumentedList {
    function __construct($fkey, $queryset=false) {
        parent::__construct($fkey, $queryset, 'QueueColumnGlueMIM');
        $this->queryset->select_related('column');
    }

    function add($column, $glue=null, $php7_is_annoying=true) {
        $glue = $glue ?: new QueueColumnGlue();
        $glue->column = $column;
        $anno = AnnotatedModel::wrap($column, $glue);
        parent::add($anno, false);
        return $anno;
    }

    function updateColumns($columns, &$errors, $options=array()) {
        $new = $columns;
        $order = array_keys($new);
        foreach ($this as $col) {
            $key = $col->column_id;
            if (!isset($columns[$key])) {
                $this->remove($col);
                continue;
            }
            $info = $columns[$key];
            $col->set('sort', array_search($key, $order));
            $col->set('heading', $info['heading']);
            $col->set('width', $info['width']);
            $col->setSortable($info['sortable']);
            unset($new[$key]);
        }
        // Add new columns
        foreach ($new as $info) {
            $glue = new QueueColumnGlue(array(
                'staff_id' => $options['staff_id'] ?: 0 ,
                'queue_id' => $options['queue_id'] ?: 0,
                'column_id' => $info['column_id'],
                'sort' => array_search($info['column_id'], $order),
                'heading' => $info['heading'],
                'width' => $info['width'] ?: 100,
                'bits' => $info['sortable'] ?  QueueColumn::FLAG_SORTABLE : 0,
            ));

            $this->add(QueueColumn::lookup($info['column_id']), $glue);
        }
        // Re-sort the in-memory columns array
        $this->sort(function($c) { return $c->sort; });

        return $this->saveAll();
    }
}

class QueueSort
extends VerySimpleModel {
    static $meta = array(
        'table' => QUEUE_SORT_TABLE,
        'pk' => array('id'),
        'ordering' => array('name'),
        'joins' => array(
            'queue' => array(
                'constraint' => array('queue_id' => 'CustomQueue.id'),
            ),
        ),
    );

    var $_columns;
    var $_extra;

    function getRoot($hint=false) {
        switch ($hint ?: $this->root) {
        case 'T':
        default:
            return 'Ticket';
        }
    }

    function getName() {
        return $this->name;
    }

    function getId() {
        return $this->id;
    }

    function getExtra() {
        if (isset($this->extra) && !isset($this->_extra))
            $this->_extra = JsonDataParser::decode($this->extra);
        return $this->_extra;
    }

    function applySort(QuerySet $query, $reverse=false, $root=false) {
        $fields = CustomQueue::getSearchableFields($this->getRoot($root));
        foreach ($this->getColumnPaths() as $path=>$descending) {
            $descending = $reverse ? !$descending : $descending;
            if (isset($fields[$path])) {
                list(,$field) = $fields[$path];
                $query = $field->applyOrderBy($query, $descending,
                    CustomQueue::getOrmPath($path, $query));
            }
        }
        // Add index hint if defined
        if (($extra = $this->getExtra()) && isset($extra['index'])) {
            $query->setOption(QuerySet::OPT_INDEX_HINT, $extra['index']);
        }
        return $query;
    }

    function getColumnPaths() {
        if (!isset($this->_columns)) {
            $columns = array();
            foreach (JsonDataParser::decode($this->columns) as $path) {
                if ($descending = $path[0] == '-')
                    $path = substr($path, 1);
                $columns[$path] = $descending;
            }
            $this->_columns = $columns;
        }
        return $this->_columns;
    }

    function getColumns() {
        $columns = array();
        $paths = $this->getColumnPaths();
        $everything = CustomQueue::getSearchableFields($this->getRoot());
        foreach ($paths as $p=>$descending) {
            if (isset($everything[$p])) {
                $columns[$p] = array($everything[$p], $descending);
            }
        }
        return $columns;
    }

    function getDataConfigForm($source=false) {
        return new QueueSortDataConfigForm($source ?: $this->getDbFields(),
            array('id' => $this->id));
    }

    function getAdvancedConfigForm($source=false) {
        return new QueueSortAdvancedConfigForm($source ?: $this->getExtra(),
            array('id' => $this->id));
    }

    static function forQueue(CustomQueue $queue) {
        return static::objects()->filter([
            'root' => $queue->root ?: 'T',
        ]);
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    function update($vars, &$errors=array()) {
        if (!isset($vars['name']))
            $errors['name'] = __('A title is required');

        $this->name = $vars['name'];
        if (isset($vars['root']))
            $this->root = $vars['root'];
        elseif (!isset($this->root))
            $this->root = 'T';

        $fields = CustomQueue::getSearchableFields($this->getRoot($vars['root']));
        $columns = array();
        if (@is_array($vars['columns'])) {
            foreach ($vars['columns']as $path=>$info) {
                $descending = (int) @$info['descending'];
                // TODO: Check if column is valid, stash in $columns
                if (!isset($fields[$path]))
                    continue;
                $columns[] = ($descending ? '-' : '') . $path;
            }
            $this->columns = JsonDataEncoder::encode($columns);
        }

        if ($this->getExtra() !== null) {
            $extra = $this->getAdvancedConfigForm($vars)->getClean();
            $this->extra = JsonDataEncoder::encode($extra);
        }

        if (count($errors))
            return false;

        return $this->save();
    }

    static function __create($vars) {
        $c = new static($vars);
        $c->save();
        return $c;
    }
}

class QueueSortGlue
extends VerySimpleModel {
    static $meta = array(
        'table' => QUEUE_SORTING_TABLE,
        'pk' => array('sort_id', 'queue_id'),
        'joins' => array(
            'ordering' => array(
                'constraint' => array('sort_id' => 'QueueSort.id'),
            ),
            'queue' => array(
                'constraint' => array('queue_id' => 'CustomQueue.id'),
            ),
        ),
        'select_related' => array('ordering', 'queue'),
        'ordering' => array('sort'),
    );
}

class QueueSortGlueMIM
extends ModelInstanceManager {
    function getOrBuild($modelClass, $fields, $cache=true) {
        $m = parent::getOrBuild($modelClass, $fields, $cache);
        if ($m && $modelClass === 'QueueSortGlue') {
            // Instead, yield the QueueColumn instance with the local fields
            // in the association table as annotations
            $m = AnnotatedModel::wrap($m->ordering, $m, 'QueueSort');
        }
        return $m;
    }
}

class QueueSortListBroker
extends InstrumentedList {
    function __construct($fkey, $queryset=false) {
        parent::__construct($fkey, $queryset, 'QueueSortGlueMIM');
        $this->queryset->select_related('ordering');
    }

    function add($ordering, $glue=null, $php7_is_annoying=true) {
        $glue = $glue ?: new QueueSortGlue();
        $glue->ordering = $ordering;
        $anno = AnnotatedModel::wrap($ordering, $glue);
        parent::add($anno, false);
        return $anno;
    }
}

abstract class QueueColumnFilter {
    static $registry;

    static $id = null;
    static $desc = null;

    static function register($filter, $group) {
        if (!isset($filter::$id))
            throw new Exception('QueueColumnFilter must define $id');
        if (isset(static::$registry[$filter::$id]))
            throw new Exception($filter::$id
                . ': QueueColumnFilter already registered under that id');
        if (!is_subclass_of($filter, get_called_class()))
            throw new Exception('Filter must extend QueueColumnFilter');

        static::$registry[$filter::$id] = array($group, $filter);
    }

    static function getFilters() {
        $list = static::$registry;
        $base = array();
        foreach ($list as $id=>$stuff) {
            list($group, $class) = $stuff;
            $base[$group][$id] = __($class::$desc);
        }
        return $base;
    }

    static function getInstance($id) {
        if (isset(static::$registry[$id])) {
            list(, $class) = @static::$registry[$id];
            if ($class && class_exists($class))
                return new $class();
        }
    }

    function mangleQuery($query, $column) { return $query; }

    abstract function filter($value, $row);
}

class TicketLinkFilter
extends QueueColumnFilter {
    static $id = 'link:ticket';
    static $desc = /* @trans */ "Ticket Link";

    function filter($text, $row) {
        if ($link = $this->getLink($row))
            return sprintf('<a style="display:inline" href="%s">%s</a>', $link, $text);
    }

    function mangleQuery($query, $column) {
        static $fields = array(
            'link:ticket'   => 'ticket_id',
            'link:ticketP'  => 'ticket_id',
            'link:user'     => 'user_id',
            'link:org'      => 'user__org_id',
        );

        if (isset($fields[static::$id])) {
            $query = $query->values($fields[static::$id]);
        }
        return $query;
    }

    function getLink($row) {
        return Ticket::getLink($row['ticket_id']);
    }
}

class UserLinkFilter
extends TicketLinkFilter {
    static $id = 'link:user';
    static $desc = /* @trans */ "User Link";

    function getLink($row) {
        return User::getLink($row['user_id']);
    }
}

class OrgLinkFilter
extends TicketLinkFilter {
    static $id = 'link:org';
    static $desc = /* @trans */ "Organization Link";

    function getLink($row) {
        return Organization::getLink($row['user__org_id']);
    }
}
QueueColumnFilter::register('TicketLinkFilter', __('Link'));
QueueColumnFilter::register('UserLinkFilter', __('Link'));
QueueColumnFilter::register('OrgLinkFilter', __('Link'));

class TicketLinkWithPreviewFilter
extends TicketLinkFilter {
    static $id = 'link:ticketP';
    static $desc = /* @trans */ "Ticket Link with Preview";

    function filter($text, $row) {
        $link = $this->getLink($row);
        return sprintf('<a style="display: inline" class="preview" data-preview="#tickets/%d/preview" href="%s">%s</a>',
            $row['ticket_id'], $link, $text);
    }
}
QueueColumnFilter::register('TicketLinkWithPreviewFilter', __('Link'));

class DateTimeFilter
extends QueueColumnFilter {
    static $id = 'date:full';
    static $desc = /* @trans */ "Date and Time";

    function filter($text, $row) {
        return $text ?
            $text->changeTo(Format::datetime($text->value)) : '';
    }
}

class HumanizedDateFilter
extends QueueColumnFilter {
    static $id = 'date:human';
    static $desc = /* @trans */ "Relative Date and Time";

    function filter($text, $row) {
        return sprintf(
            '<time class="relative" datetime="%s" title="%s">%s</time>',
            date(DateTime::W3C, Misc::db2gmtime($text->value)),
            Format::daydatetime($text->value),
            Format::relativeTime(Misc::db2gmtime($text->value))
        );
    }
}
QueueColumnFilter::register('DateTimeFilter', __('Date Format'));
QueueColumnFilter::register('HumanizedDateFilter', __('Date Format'));

class QueueColDataConfigForm
extends AbstractForm {
    function buildFields() {
        return array(
            'primary' => new DataSourceField(array(
                'label' => __('Primary Data Source'),
                'required' => true,
                'configuration' => array(
                    'root' => 'Ticket',
                ),
                'layout' => new GridFluidCell(6),
            )),
            'secondary' => new DataSourceField(array(
                'label' => __('Secondary Data Source'),
                'configuration' => array(
                    'root' => 'Ticket',
                ),
                'layout' => new GridFluidCell(6),
            )),
            'name' => new TextboxField(array(
                'label' => __('Name'),
                'required' => true,
                'layout' => new GridFluidCell(4),
            )),
            'filter' => new ChoiceField(array(
                'label' => __('Filter'),
                'required' => false,
                'choices' => QueueColumnFilter::getFilters(),
                'layout' => new GridFluidCell(4),
            )),
            'truncate' => new ChoiceField(array(
                'label' => __('Text Overflow'),
                'choices' => array(
                    'wrap' => __("Wrap Lines"),
                    'ellipsis' => __("Add Ellipsis"),
                    'clip' => __("Clip Text"),
                    'lclip' => __("Clip Beginning Text"),
                ),
                'default' => 'wrap',
                'layout' => new GridFluidCell(4),
            )),
        );
    }
}

class QueueSortDataConfigForm
extends AbstractForm {
    function getInstructions() {
        return __('Add, and remove the fields in this list using the options below. Sorting can be performed on any field, whether displayed in the queue or not.');
    }

    function buildFields() {
        return array(
            'name' => new TextboxField(array(
                'required' => true,
                'layout' => new GridFluidCell(12),
                'translatable' => isset($this->options['id'])
                    ? _H('queuesort.name.'.$this->options['id']) : false,
                'configuration' => array(
                    'placeholder' => __('Sort Criteria Title'),
                ),
            )),
        );
    }
}

class QueueSortAdvancedConfigForm
extends AbstractForm {
    function getInstructions() {
        return __('If unsure, leave these options blank and unset');
    }

    function buildFields() {
        return array(
            'index' => new TextboxField(array(
                'label' => __('Database Index'),
                'hint' => __('Use this index when sorting on this column'),
                'required' => false,
                'layout' => new GridFluidCell(12),
                'configuration' => array(
                    'placeholder' => __('Automatic'),
                ),
            )),
        );
    }
}
