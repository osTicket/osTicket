<?php
/*********************************************************************
    class.queue.php

    Custom (ticket) queues for osTicket

    Jared Hancock <jared@osticket.com>
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once INCLUDE_DIR . 'class.search.php';

class CustomQueue extends SavedSearch {
    static $meta = array(
        'joins' => array(
            'columns' => array(
                'reverse' => 'QueueColumn.queue',
            ),
        ),
    );

    static function objects() {
        return parent::objects()->filter(array(
            'flags__hasbit' => static::FLAG_QUEUE
        ));
    }

    static function getDecorations($root) {
        // Ticket decorations
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

    function getRoot() {
        return 'Ticket';
    }

    function getBasicQuery($form=false) {
        $root = $this->getRoot();
        $query = $root::objects();
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
    function getQuery($form=false) {
        $query = $this->getBasicQuery($form);
        foreach ($this->getColumns() as $C) {
            $query = $C->mangleQuery($query);
        }
        return $query;
    }
}

abstract class QueueDecoration {
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
    // text of the cell before decorations were applied.
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
}

class TicketThreadCount
extends QueueDecoration {
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
extends QueueDecoration {
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
extends QueueDecoration {
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
extends QueueDecoration {
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
            $fields[$path] = $f->get('label');
        }
        return $fields;
    }
}

class QueueColumnCondition {
    var $config;
    var $properties = array();

    function __construct($config) {
        $this->config = $config;
        if (is_array($config['prop']))
            $this->properties = $config['prop'];
    }

    function getProperties() {
        return $this->properties;
    }

    function getField() {
    }

    // Add the annotation to a QuerySet
    function annotate($query) {
        $criteria = $this->config['crit'];
        $searchable = SavedSearch::getSearchableFields('Ticket');

        // Setup a dummy form with a source for field setup
        $form = new Form($criteria);
        $fields = array();
        foreach ($criteria as $k=>$v) {
            if (substr($k, -7) === '+method') {
                list($name,) = explode('+', $k, 2);
                if (!isset($searchable[$name]))
                    continue;

                // Lookup the field to search this condition
                $field = $searchable[$name];

                // Get the search method and value
                $breakout = SavedSearch::getSearchField($field, $name);
                $method = $breakout["{$name}+method"];
                $method->setForm($form);
                if (!($method = $method->getClean()))
                    continue;

                if (!($value = $breakout["{$name}+{$method}"]))
                    continue;

                // Fetch a criteria Q for the query
                $value = $value->getClean();
                $Q = $field->getSearchQ($method, $value, $name);

                // Add an annotation to the query
                $query = $query->annotate(array(
                    $this->getAnnotationName() => new SqlExpr($Q)
                ));

                // Only one field can be considered in the condition
                break;
            }
        }
        return $query;
    }

    function render($row, $text) {
        $field = $this->getAnnotationName();
        if ($V = $row[$field]) {
            $style = array();
            foreach ($this->getProperties() as $css=>$value) {
                $style[] = "{$css}:{$value}";
            }
            $text = sprintf('<span style="%s">%s</span>',
                implode(' ', $style), $text);
        }
        return $text;
    }

    function getAnnotationName() {
        return 'howdy';
    }

    static function fromJson($config) {
        if (is_string($config))
            $config = JsonDataParser::decode($cnofig);
        if (!is_array($config))
            throw new BadMethodCallException('$config must be string or array');

        return new static($config);
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
        return array_keys(static::$properties[$this->property]);
    }

    static function getField($prop) {
        $choices = static::$properties[$prop];
        if (is_array($choices))
            return new ChoiceField(array(
                'choices' => array_combine($choices, $choices),
            ));
        elseif (class_exists($choices))
            return new $choices();
    }

    function getChoices() {
        if (isset($this->property))
            return static::$properties[$this->property];

        $keys = array_keys(static::$properties);
        return array_combine($keys, $keys);
    }
}


/**
 * Object version of JSON-serialized column array which has several
 * properties:
 *
 * {
 *   "heading": "Header Text",
 *   "primary": "user__name",
 *   "secondary": null,
 *   "width": 100,
 *   "link": 'ticket',
 *   "truncate": "wrap",
 *   "filter": "UsersName"
 *   "annotations": [
 *     {
 *       "c": "ThreadCollabCount",
 *       "p": ">"
 *     }
 *   ],
 *   "conditions": [
 *     {
 *       "crit": {
 *         "created+method": {"ndaysago": "in the last n days"}, "created+ndaysago": {"until":"7"}
 *       },
 *       "prop": {
 *         "font-weight": "bold"
 *       }
 *     }
 *   ]
 * }
 */
class QueueColumn
extends VerySimpleModel {
    static $meta = array(
        'table' => QUEUE_COLUMN_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'queue' => array(
                'constraint' => array('queue_id' => 'CustomQueue.id'),
            ),
        ),
    );

    var $_decorations = array();
    var $_conditions = array();

    function __onload() {
        if ($this->annotations) {
            foreach ($this->annotations as $D)
                $this->_decorations[] = QueueDecoration::fromJson($D) ?: array();
        }
        if ($this->conditions) {
            foreach ($this->conditions as $C)
                $this->_conditions[] = QueueColumnCondition::fromJson($C) ?: array();
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

    function getLink($row) {
        $link = $this->link;
        switch (strtolower($link)) {
        case 'root':
        case 'ticket':
            return Ticket::getLink($row['ticket_id']);
        case 'user':
            return User::getLink($row['user_id']);
        case 'org':
            return Organization::getLink($row['user__org_id']);
        }
    }

    function render($row) {
        // Basic data
        $text = $this->renderBasicValue($row);

        // Truncate
        if ($class = $this->getTruncateClass()) {
            $text = sprintf('<span class="%s">%s</span>', $class, $text);
        }

        // Link
        if ($link = $this->getLink($row)) {
            $text = sprintf('<a href="%s">%s</a>', $link, $text);
        }

        // Decorations and conditions
        foreach ($this->_decorations as $D) {
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

        if (($F = $fields[$primary]) && ($T = $F->from_query($row, $primary)))
            return $F->display($F->to_php($T));

        if (($F = $fields[$secondary]) && ($T = $F->from_query($row, $secondary)))
            return $F->display($F->to_php($T));
    }

    function getTruncateClass() {
        switch ($this->truncate) {
        case 'ellipsis':
            return 'trucate';
        case 'clip':
            return 'truncate clip';
        default:
        case 'wrap':
            return false;
        }
    }

    function mangleQuery($query) {
        // Basic data
        $fields = SavedSearch::getSearchableFields($this->getQueue()->getRoot());
        if ($primary = $fields[$this->primary])
            $query = $primary->addToQuery($query,
                $this->getOrmPath($this->primary));

        if ($secondary = $fields[$this->secondary])
            $query = $secondary->addToQuery($query,
                $this->getOrmPath($this->secondary));

        switch ($this->link) {
        // XXX: Consider the ROOT of the related queue
        case 'ticket':
            $query = $query->values('ticket_id');
            break;
        case 'user':
            $query = $query->values('user_id');
            break;
        case 'org':
            $query = $query->values('user__org_id');
            break;
        }

        // Decorations
        foreach ($this->_decorations as $D) {
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

    function getOrmPath($name) {
        return $name;
    }

    function getDecorations() {
        return $this->_decorations;
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
        // TODO: Convert decorations and conditions
        return $inst;
    }

    function update($vars) {
        $form = $this->getDataConfigForm($vars);
        foreach ($form->getClean() as $k=>$v)
            $this->set($k, $v);

        // Do the decorations
        $this->_decorations = $this->decorations = array();
        foreach ($vars['decorations'] as $i=>$class) {
            if (!class_exists($class) || !is_subclass_of($class, 'QueueDecoration'))
                continue;
            if ($vars['deco_column'][$i] != $this->id)
                continue;
            $json = array('c' => $class, 'p' => $vars['deco_pos'][$i]);
            $this->_decorations[] = QueueDecoration::fromJson($json);
            $this->decorations[] = $json;
        }
        // Store as JSON array
        $this->decorations = JsonDataEncoder::encode($this->decorations);
    }
}

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
            'link' => new ChoiceField(array(
                'label' => __('Link'),
                'required' => false,
                'choices' => array(
                    'ticket' => __('Ticket'),
                    'user' => __('User'),
                    'org' => __('Organization'),
                ),
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
