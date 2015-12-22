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
        'joins' => array(
            'columns' => array(
                'reverse' => 'QueueColumnGlue.queue',
                'broker' => 'QueueColumnListBroker',
            ),
            'children' => array(
                'reverse' => 'CustomQueue.parent',
            ),
        )
    );

    static function queues() {
        return parent::objects()->filter(array(
            'flags__hasbit' => static::FLAG_QUEUE
        ));
    }

    function getColumns() {
        if ($this->parent_id
            && $this->hasFlag(self::FLAG_INHERIT_COLUMNS)
            && $this->parent
        ) {
            return $this->parent->getColumns();
        }
        elseif (count($this->columns)) {
            return $this->columns;
        }
        return parent::getColumns();
    }

    function addColumn(QueueColumn $col) {
        $this->columns->add($col);
        $col->queue = $this;
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
            $this->filter = @SavedSearch::getOrmPath($this->filter, $query);
            $query = $qf->applyQuickFilter($query, $quick_filter,
                $this->filter); 
        }

        // Apply column, annotations and conditions additions
        foreach ($this->getColumns() as $C) {
            $query = $C->mangleQuery($query, $this->getRoot());
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

    function update($vars, &$errors=array()) {
        if (!parent::update($vars, false, $errors))
            return false;

        // Set basic queue information
        $this->filter = $vars['filter'];
        $this->setFlag(self::FLAG_INHERIT_CRITERIA,
            $this->parent_id > 0 && isset($vars['inherit']));

        // Update queue columns (but without save)
        if (isset($vars['columns'])) {
            $new = $vars['columns'];
            $order = array_keys($new);
            foreach ($this->columns as $col) {
                $key = $col->column_id;
                if (!isset($vars['columns'][$key])) {
                    $this->columns->remove($col);
                    continue;
                }
                $info = $vars['columns'][$key];
                $col->set('sort', array_search($key, $order));
                $col->set('heading', $info['heading']);
                $col->set('width', $info['width']);
                $col->setSortable($info['sortable']);
                unset($new[$key]);
            }
            // Add new columns
            foreach ($new as $info) {
                $glue = QueueColumnGlue::create(array(
                    'column_id' => $info['column_id'], 
                    'sort' => array_search($info['column_id'], $order),
                    'heading' => $info['heading'],
                    'width' => $info['width'] ?: 100,
                    'bits' => $info['sortable'] ?  QueueColumn::FLAG_SORTABLE : 0,
                ));
                $glue->queue = $this;
                $this->columns->add(
                    QueueColumn::lookup($info['column_id']), $glue);
            }
            // Re-sort the in-memory columns array
            $this->columns->sort(function($c) { return $c->sort; });
        }
        else {
            // No columns -- imply column inheritance
            $this->setFlag(self::FLAG_INHERIT_COLUMNS);
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

    static function __create($vars) {
        $q = static::create($vars);
        $q->save();
        foreach ($vars['columns'] as $info) {
            $glue = QueueColumnGlue::create($info);
            $glue->queue_id = $q->getId();
            $glue->save();
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

    function isVisible($row) {
        return $row[static::$qname] > 1;
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

    function isVisible($row) {
        return $row[static::$qname] > 0;
    }
}

class ThreadCollaboratorCount
extends QueueColumnAnnotation {
    static $icon = 'group';
    static $qname = '_collabs';
    static $desc = /* @trans */ 'Collaborator Count';

    function annotate($query) {
        return $query->annotate(array(
        static::$qname => TicketThread::objects()
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

    function annotate($query) {
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

class LockDecoration
extends QueueColumnAnnotation {
    static $icon = "lock";
    static $desc = /* @trans */ 'Locked';

    function annotate($query) {
        global $thisstaff;

        return $query
            ->annotate(array(
                '_locked' => new SqlExpr(array(new Q(array(
                    'lock__expire__gt' => SqlFunction::NOW(),
                    Q::not(array('lock__staff_id' => $thisstaff->getId())),
                ))))
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
        $Q = $this->getSearchQ($query);

        // Add an annotation to the query
        return $query->annotate(array(
            $this->getAnnotationName() => new SqlExpr(array($Q))
        ));
    }

    function getField($name=null) {
        // FIXME
        #$root = $this->getColumn()->getRoot();
        $root = 'Ticket';
        $searchable = SavedSearch::getSearchableFields($root);

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
        $name = @SavedSearch::getOrmPath($name, $query);

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

    function render($row, $text, &$styles=array()) {
        $annotation = $this->getAnnotationName();
        if ($V = $row[$annotation]) {
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
        return substr(base64_encode($this->getHash(true)), -10);
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

    function getChoices() {
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

    function getId() {
        return $this->id;
    }

    function getFilter() {
         if ($this->filter)
             return QueueColumnFilter::getInstance($this->filter);
     }

    function getName() {
        return $this->name;
    }

    // These getters fetch data from the annotated overlay from the
    // queue_column table
    function getQueue() {
        return $this->queue;
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
        if ($filter = $this->getFilter()) {
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
        $root = ($q = $this->getQueue()) ? $q->getRoot() : 'Ticket';
        $fields = SavedSearch::getSearchableFields($root);
        $primary = SavedSearch::getOrmPath($this->primary);
        $secondary = SavedSearch::getOrmPath($this->secondary);

        // Return a lazily ::display()ed value so that the value to be
        // rendered by the field could be changed or display()ed when
        // converted to a string.

        if (($F = $fields[$primary])
            && (list(,$field) = $F)
            && ($T = $field->from_query($row, $primary))
        ) {
            return new LazyDisplayWrapper($field, $T);
        }
        if (($F = $fields[$secondary])
            && (list(,$field) = $F)
            && ($T = $field->from_query($row, $secondary))
        ) {
            return new LazyDisplayWrapper($field, $T);
        }
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
            $class[] = $linfo['direction'] == 'rtl' ? 'ltr' : 'rtl';
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
        $fields = SavedSearch::getSearchableFields($root ?: $this->getQueue()->getRoot());
        if ($primary = $fields[$this->primary]) {
            list(,$field) = $primary;
            $query = $this->addToQuery($query, $field,
                SavedSearch::getOrmPath($this->primary, $query));
        }
        if ($secondary = $fields[$this->secondary]) {
            list(,$field) = $secondary;
            $query = $this->addToQuery($query, $field,
                SavedSearch::getOrmPath($this->secondary, $query));
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

    function getConditions() {
        if (!isset($this->_conditions)) {
            $this->_conditions = array();
            if ($this->conditions
                && ($conds = JsonDataParser::decode($this->conditions))
            ) {
                foreach ($conds as $C)
                    if ($T = QueueColumnCondition::fromJson($C))
                        $this->_conditions[] = $T;
            }
        }
        return $this->_conditions;
    }

    static function __create($vars) {
        $c = static::create($vars);
        $c->save();
        return $c;
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
            foreach (@$vars['conditions'] as $i=>$id) {
                if ($vars['condition_column'][$i] != $this->id)
                    // Not a condition for this column
                    continue;
                // Determine the criteria
                $name = $vars['condition_field'][$i];
                $fields = SavedSearch::getSearchableFields($root);
                if (!isset($fields[$name]))
                    // No such field exists for this queue root type
                    continue;
                $parts = SavedSearch::getSearchField($fields[$name], $name);
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
}

class QueueColumnGlue
extends VerySimpleModel {
    static $meta = array(
        'table' => QUEUE_COLUMN_TABLE,
        'pk' => array('queue_id', 'column_id'),
        'joins' => array(
            'column' => array(
                'constraint' => array('column_id' => 'QueueColumn.id'),
            ),
            'queue' => array(
                'constraint' => array('queue_id' => 'CustomQueue.id'),
            ),
        ),
        'select_related' => array('column', 'queue'),
        'ordering' => array('sort'),
    );
}

class QueueColumnListBroker
extends InstrumentedList {
    function __construct($fkey, $queryset=false) {
        parent::__construct($fkey, $queryset);
        $this->queryset->select_related('column');
    }

    function getOrBuild($modelClass, $fields, $cache=true) {
        $m = parent::getOrBuild($modelClass, $fields, $cache);
        if ($m && $modelClass === 'QueueColumnGlue') {
            // Instead, yield the QueueColumn instance with the local fields
            // in the association table as annotations
            $m = AnnotatedModel::wrap($m->column, $m, 'QueueColumn');
        }
        return $m;
    }

    function add($column, $glue=null) {
        $glue = $glue ?: QueueColumnGlue::create();
        $glue->column = $column;
        $anno = AnnotatedModel::wrap($column, $glue);
        parent::add($anno);
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
            list(, $class) = static::$registry[$id];
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
        return $text->changeTo(Format::datetime($text->value));
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
