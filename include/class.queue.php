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
require_once INCLUDE_DIR . 'class.search.php';

class CustomQueue extends SavedSearch {
    static $meta = array(
        'select_related' => array('parent'),
        'ordering' => array('title', 'path'),
        'joins' => array(
            'columns' => array(
                'reverse' => 'QueueColumn.queue',
            ),
            'staff' => array(
                'constraint' => array(
                    'staff_id' => 'Staff.staff_id',
                )
            ),
            'parent' => array(
                'constraint' => array(
                    'parent_id' => 'CustomQueue.id',
                ),
                'null' => true,
            ),
            'children' => array(
                'reverse' => 'CustomQueue.parent',
            )
        ),
    );

    static function objects() {
        return parent::objects()->filter(array(
            'flags__hasbit' => static::FLAG_QUEUE
        ));
    }

    static function getAnnotations($root) {
        // Ticket annotations
        return array(
            'TicketThreadCount',
            'ThreadAttachmentCount',
            'OverdueFlagDecoration',
            'TicketSourceDecoration'
        );
    }

    function getColumns() {
        if (!count($this->columns)) {
            foreach (array(
                new QueueColumn(array(
                    "id" => 1,
                    "heading" => "Number",
                    "primary" => 'number',
                    "width" => 100,
                )),
                new QueueColumn(array(
                    "id" => 2,
                    "heading" => "Created",
                    "primary" => 'created',
                    "width" => 100,
                )),
                new QueueColumn(array(
                    "id" => 3,
                    "heading" => "Subject",
                    "primary" => 'cdata__subject',
                    "width" => 250,
                )),
                new QueueColumn(array(
                    "id" => 4,
                    "heading" => "From",
                    "primary" => 'user__name',
                    "width" => 150,
                )),
                new QueueColumn(array(
                    "id" => 5,
                    "heading" => "Priority",
                    "primary" => 'cdata__priority',
                    "width" => 120,
                )),
                new QueueColumn(array(
                    "id" => 6,
                    "heading" => "Assignee",
                    "primary" => 'assignee',
                    "secondary" => 'team__name',
                    "width" => 100,
                )),
            ) as $c) {
                $this->addColumn($c);
            }
        }
        return $this->columns;
    }

    function addColumn(QueueColumn $col) {
        $this->columns->add($col);
        $col->queue = $this;
    }

    function getId() {
        return $this->id;
    }

    function getRoot() {
        return 'Ticket';
    }

    function getStatus() {
        return 'bogus';
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

    function getPath() {
        return $this->path ?: $this->buildPath();
    }

    function buildPath() {
        if (!$this->id)
            return;

        $path = '';
        if ($this->parent) {
            $path = rtrim($this->parent->getPath(), '/');
        }
        return $path . "/{$this->id}/";
    }

    function getFullName() {
        $base = $this->getName();
        if ($this->parent)
            $base = sprintf("%s / %s", $this->parent->getFullName(), $base);
        return $base;
    }

    function getHref() {
        // TODO: Get base page from getRoot();
        $root = $this->getRoot();
        return 'tickets.php?queue='.$this->getId();
    }

    function inheritCriteria() {
        return $this->flags & self::FLAG_INHERIT_CRITERIA;
    }

    function getBasicQuery($form=false) {
        if ($this->parent && $this->inheritCriteria()) {
            $query = $this->parent->getBasicQuery();
        }
        else {
            $root = $this->getRoot();
            $query = $root::objects();
        }
        $form = $form ?: $this->loadFromState($this->getCriteria());
        return $this->mangleQuerySet($query, $form);
    }

    /**
     * Retrieve a QuerySet instance based on the type of object (root) of
     * this Q, which is automatically configured with the data and criteria
     * of the queue and its columns.
     *
     * Returns:
     * <QuerySet> instance
     */
    function getQuery($form=false, $quick_filter=false) {
        // Start with basic criteria
        $query = $this->getBasicQuery($form);

        // Apply quick filter
        if (isset($quick_filter)
            && ($qf = $this->getQuickFilterField($quick_filter))
        ) {
            $this->filter = @QueueColumn::getOrmPath($this->filter, $query);
            $query = $qf->applyQuickFilter($query, $quick_filter,
                $this->filter); 
        }

        // Apply column, annotations and conditions additions
        foreach ($this->getColumns() as $C) {
            $query = $C->mangleQuery($query);
        }
        return $query;
    }

    function getQuickFilterField($value=null) {
        if ($this->filter == '::') {
            if ($this->parent) {
                return $this->parent->getQuickFilterField($value);
            }
        }
        elseif ($this->filter
            && ($fields = SavedSearch::getSearchableFields($this->getRoot()))
            && (list(,$f) = @$fields[$this->filter])
            && $f->supportsQuickFilter()
        ) {
            $f->value = $value;
            return $f;
        }
    }

    function update($vars, &$errors) {
        // TODO: Move this to SavedSearch::update() and adjust
        //       AjaxSearch::_saveSearch()
        $form = $this->getForm($vars);
        $form->setSource($vars);
        if (!$vars || !$form->isValid()) {
            $errors['criteria'] = __('Validation errors exist on criteria');
        }
        else {
            $this->config = JsonDataEncoder::encode($form->getState());
        }

        // Set basic queue information
        $this->title = $vars['name'];
        $this->parent_id = $vars['parent_id'];
        $this->filter = $vars['filter'];
        $this->path = $this->buildPath();
        $this->setFlag(self::FLAG_INHERIT_CRITERIA, isset($vars['inherit']));

        // Update queue columns (but without save)
        if (isset($vars['columns'])) {
            foreach ($vars['columns'] as $sort=>$colid) {
                // Try and find the column in this queue, if it's a new one,
                // add it to the columns list
                if (!($col = $this->columns->findFirst(array('id' => $colid)))) {
                    $col = QueueColumn::create(array("id" => $colid, "queue" => $this));
                    $this->addColumn($col);
                }
                $col->set('sort', $sort+1);
                $col->update($vars, $errors);
            }
            // Re-sort the in-memory columns array
            $this->columns->sort(function($c) { return $c->sort; });
        }
        return 0 === count($errors);
    }

    function save($refetch=false) {
        $wasnew = !isset($this->id);
        if (!($rv = parent::save($refetch)))
            return $rv;

        if ($wasnew) {
            $this->path = $this->buildPath();
            $this->save();
        }
        return $this->columns->saveAll();
    }

    static function create($vars=false) {
        global $thisstaff;

        $queue = parent::create($vars);
        $queue->setFlag(SavedSearch::FLAG_QUEUE);
        if ($thisstaff)
            $queue->staff_id = $thisstaff->getId();

        return $queue;
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
            'a' => '%1$s %2$s',
            'b' => '%2$s %1$s',
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
    abstract function annotate($query);

    // Fetch some HTML to render the decoration on the page. This function
    // can return boolean FALSE to indicate no decoration should be applied
    abstract function getDecoration($row, $text);

    function getPosition() {
        return strtolower($this->config['p']) ?: 'a';
    }

    function getClassName() {
        return @$this->config['c'] ?: get_class();
    }
}

class TicketThreadCount
extends QueueColumnAnnotation {
    static $icon = 'comments-alt';
    static $qname = '_thread_count';
    static $desc = /* @trans */ 'Thread Count';

    function annotate($query) {
        return $query->annotate(array(
        static::$qname => TicketThread::objects()
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
}

class ThreadAttachmentCount
extends QueueColumnAnnotation {
    static $icon = 'paperclip';
    static $qname = '_att_count';
    static $desc = /* @trans */ 'Attachment Count';

    function annotate($query) {
        // TODO: Convert to Thread attachments
        return $query->annotate(array(
        static::$qname => TicketThread::objects()
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
}

class OverdueFlagDecoration
extends QueueColumnAnnotation {
    static $icon = 'exclamation';
    static $desc = /* @trans */ 'Overdue Icon';

    function annotate($query) {
        return $query->values('isoverdue');
    }

    function getDecoration($row, $text) {
        if ($row['isoverdue'])
            return '<span class="Icon overdueTicket"></span>';
    }
}

class TicketSourceDecoration
extends QueueColumnAnnotation {
    static $icon = 'phone';
    static $desc = /* @trans */ 'Ticket Source';

    function annotate($query) {
        return $query->values('source');
    }

    function getDecoration($row, $text) {
        return sprintf('<span class="Icon %sTicket"></span>',
            strtolower($row['source']));
    }
}

class DataSourceField
extends ChoiceField {
    function getChoices() {
        $config = $this->getConfiguration();
        $root = $config['root'];
        $fields = array();
        foreach (SavedSearch::getSearchableFields($root) as $path=>$f) {
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
        $Q = $this->getSearchQ();

        // Add an annotation to the query
        return $query->annotate(array(
            $this->getAnnotationName() => new SqlExpr(array($Q))
        ));
    }

    function getField($name=null) {
        // FIXME
        #$root = $this->getColumn()->getQueue()->getRoot();
        $root = 'Ticket';
        $searchable = SavedSearch::getSearchableFields($root);

        if (!isset($name))
            list($name) = $this->config['crit'];

        // Lookup the field to search this condition
        if (isset($searchable[$name])) {
            list(,$field) = $searchable[$name];
            return $field;
        }
    }

    function getFieldName() {
        list($name) = $this->config['crit'];
        return $name;
    }

    function getSearchQ() {
        list($name, $method, $value) = $this->config['crit'];

        // Fetch a criteria Q for the query
        if ($field = $this->getField($name))
            return $field->getSearchQ($method, $value, $name);
    }

    /**
     * Take the criteria from the SavedSearch fields setup and isolate the
     * field name being search, the method used for searhing, and the method-
     * specific data entered in the UI.
     */
    static function isolateCriteria($criteria, $root='Ticket') {
        $searchable = SavedSearch::getSearchableFields($root);
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

    function render($row, $text) {
        $annotation = $this->getAnnotationName();
        if ($V = $row[$annotation]) {
            $style = array();
            foreach ($this->getProperties() as $css=>$value) {
                $field = QueueColumnConditionProperty::getField($css);
                $field->value = $value;
                $V = $field->getClean();
                if (is_array($V))
                    $V = current($V);
                $style[] = "{$css}:{$V}";
            }
            $text = sprintf('<span class="fill" style="%s">%s</span>',
                implode(';', $style), $text);
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
        return substr($this->getHash(), -10);
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

    function getChoices() {
        if (isset($this->property))
            return static::$properties[$this->property];

        $keys = array_keys(static::$properties);
        return array_combine($keys, $keys);
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
        'table' => QUEUE_COLUMN_TABLE,
        'pk' => array('id'),
        'ordering' => array('sort'),
        'joins' => array(
            'queue' => array(
                'constraint' => array('queue_id' => 'CustomQueue.id'),
            ),
        ),
    );

    var $_annotations = array();
    var $_conditions = array();

    function __onload() {
        if ($this->annotations
            && ($anns = JsonDataParser::decode($this->annotations))
        ) {
            foreach ($anns as $D)
                if ($T = QueueColumnAnnotation::fromJson($D))
                    $this->_annotations[] = $T;
        }
        if ($this->conditions
            && ($conds = JsonDataParser::decode($this->conditions))
        ) {
            foreach ($conds as $C)
                if ($T = QueueColumnCondition::fromJson($C))
                    $this->_conditions[] = $T;
        }
    }

    function getId() {
        return $this->id;
    }

    function getQueue() {
        return $this->queue;
    }

    function getHeading() {
        return $this->heading;
    }

    function getWidth() {
        return $this->width ?: 100;
    }

    function getFilter() {
         if ($this->filter)
             return QueueColumnFilter::getInstance($this->filter);
     }

    function render($row) {
        // Basic data
        $text = $this->renderBasicValue($row);

        // Truncate
        if ($text = $this->applyTruncate($text)) {
        }

        // Filter
        if ($filter = $this->getFilter()) {
            $text = $filter->filter($text, $row);
        }

        // annotations and conditions
        foreach ($this->_annotations as $D) {
            $text = $D->render($row, $text);
        }
        foreach ($this->_conditions as $C) {
            $text = $C->render($row, $text);
        }
        return $text;
    }

    function renderBasicValue($row) {
        $root = $this->getQueue()->getRoot();
        $fields = SavedSearch::getSearchableFields($root);
        $primary = $this->getOrmPath($this->primary);
        $secondary = $this->getOrmPath($this->secondary);

        // TODO: Consider data filter if configured

        if (($F = $fields[$primary])
            && (list(,$field) = $F)
            && ($T = $field->from_query($row, $primary))
        ) {
            return $field->display($field->to_php($T));
        }
        if (($F = $fields[$secondary])
            && (list(,$field) = $F)
            && ($T = $F->from_query($row, $secondary))
        ) {
            return $field->display($field->to_php($T));
        }
    }

    function applyTruncate($text) {
        switch ($this->truncate) {
        case 'ellipsis':
            return sprintf('<span class="%s" style="max-width:%dpx">%s</span>',
                'truncate', $this->width*1.1, $text);
        case 'clip':
            return sprintf('<span class="%s" style="max-width:%dpx">%s</span>',
                'truncate clip', $this->width*1.1, $text);
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

    function mangleQuery($query) {
        // Basic data
        $fields = SavedSearch::getSearchableFields($this->getQueue()->getRoot());
        if ($primary = $fields[$this->primary]) {
            list(,$field) = $primary;
            $query = $this->addToQuery($query, $field,
                $this->getOrmPath($this->primary, $query));
        }
        if ($secondary = $fields[$this->secondary]) {
            list(,$field) = $secondary;
            $query = $this->addToQuery($query, $field,
                $this->getOrmPath($this->secondary, $query));
        }

        if ($filter = $this->getFilter())
            $query = $filter->mangleQuery($query, $this);

        // annotations
        foreach ($this->_annotations as $D) {
            $query = $D->annotate($query);
        }

        // Conditions
        foreach ($this->_conditions as $C) {
            $query = $C->annotate($query);
        }

        return $query;
    }

    function getDataConfigForm($source=false) {
        return new QueueColDataConfigForm($source ?: $this->ht,
            array('id' => $this->id));
    }

    function getOrmPath($name, $query=null) {
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

    function getAnnotations() {
        return $this->_annotations;
    }

    function getConditions() {
        return $this->_conditions;
    }

    /**
     * Create a CustomQueueColumn from vars (_POST) received from an
     * update request.
     */
    static function create($vars=array()) {
        $inst = parent::create($vars);
        // TODO: Convert annotations and conditions
        return $inst;
    }

    function update($vars) {
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
            foreach (@$vars['conditions'] as $i=>$id) {
                if ($vars['condition_column'][$i] != $this->id)
                    // Not a condition for this column
                    continue;
                // Determine the criteria
                $name = $vars['condition_field'][$i];
                $fields = SavedSearch::getSearchableFields($this->getQueue()->getRoot());
                if (!isset($fields[$name]))
                    // No such field exists for this queue root type
                    continue;
                list(,$field) = $fields[$name];
                $parts = SavedSearch::getSearchField($field, $name);
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
                $this->_conditions[] = QueueColumnCondition::fromJson($json);
                $conditions[] = $json;
            }
        }

        // Store as JSON array
        $this->annotations = JsonDataEncoder::encode($annotations);
        $this->conditions = JsonDataEncoder::encode($conditions);
    }

    function save($refetch=false) {
        if ($this->__new__ && isset($this->id))
            // The ID is used to synchrize the POST data with the forms API.
            // It should not be assumed to be a valid or unique database ID
            // number
            unset($this->id);
        return parent::save($refetch);
    }
}

abstract class QueueColumnFilter {
    static $registry;

    static $id = null;
    static $desc = null;

    static function register($filter) {
        if (!isset($filter::$id))
            throw new Exception('QueueColumnFilter must define $id');
        if (isset(static::$registry[$filter::$id]))
            throw new Exception($filter::$id
                . ': QueueColumnFilter already registered under that id');
        if (!is_subclass_of($filter, get_called_class()))
            throw new Exception('Filter must extend QueueColumnFilter');

        static::$registry[$filter::$id] = $filter;
    }

    static function getFilters() {
        $base = static::$registry;
        foreach ($base as $id=>$class) {
            $base[$id] = __($class::$desc);
        }
        return $base;
    }

    static function getInstance($id) {
        if (isset(static::$registry[$id]))
            return new static::$registry[$id]();
    }

    function mangleQuery($query, $column) { return $query; }

    abstract function filter($value, $row);
}

class TicketLinkFilter
extends QueueColumnFilter {
    static $id = 'link:ticket';
    static $desc = /* @trans */ "Ticket Link";

    function filter($text, $row) {
        $link = $this->getLink($row);
        return sprintf('<a href="%s">%s</a>', $link, $text);
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
        return Organization::getLink($row['org_id']);
    }
}
QueueColumnFilter::register('TicketLinkFilter');
QueueColumnFilter::register('UserLinkFilter');
QueueColumnFilter::register('OrgLinkFilter');

class TicketLinkWithPreviewFilter
extends TicketLinkFilter {
    static $id = 'link:ticketP';
    static $desc = /* @trans */ "Ticket Link with Preview";

    function filter($text, $row) {
        $link = $this->getLink($row);
        return sprintf('<a class="preview" data-preview="#tickets/%d/preview" href="%s">%s</a>',
            $row['ticket_id'], $link, $text);
    }
}
QueueColumnFilter::register('TicketLinkWithPreviewFilter');

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
            'heading' => new TextboxField(array(
                'label' => __('Heading'),
                'required' => true,
                'layout' => new GridFluidCell(3),
            )),
            'filter' => new ChoiceField(array(
                'label' => __('Filter'),
                'required' => false,
                'choices' => QueueColumnFilter::getFilters(),
                'layout' => new GridFluidCell(3),
            )),
            'width' => new TextboxField(array(
                'label' => __('Width'),
                'default' => 75,
                'configuration' => array(
                    'validator' => 'number',
                ),
                'layout' => new GridFluidCell(3),
            )),
            'truncate' => new ChoiceField(array(
                'label' => __('Text Overflow'),
                'choices' => array(
                    'wrap' => __("Wrap Lines"),
                    'ellipsis' => __("Add Ellipsis"),
                    'clip' => __("Clip Text"),
                ),
                'default' => 'wrap',
                'layout' => new GridFluidCell(3),
            )),
        );
    }
}
