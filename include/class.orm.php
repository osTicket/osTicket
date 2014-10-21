<?php
/*********************************************************************
    class.orm.php

    Simple ORM (Object Relational Mapper) for PHP5 based on Django's ORM,
    except that complex filter operations are not supported. The ORM simply
    supports ANDed filter operations without any GROUP BY support.

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class OrmException extends Exception {}
class OrmConfigurationException extends Exception {}

class VerySimpleModel {
    static $meta = array(
        'table' => false,
        'ordering' => false,
        'pk' => false
    );

    var $ht;
    var $dirty;
    var $__new__ = false;

    function __construct($row) {
        $this->ht = $row;
        $this->__setupForeignLists();
        $this->dirty = array();
    }

    function get($field, $default=false) {
        if (array_key_exists($field, $this->ht))
            return $this->ht[$field];
        elseif (isset(static::$meta['joins'][$field])) {
            // TODO: Support instrumented lists and such
            $j = static::$meta['joins'][$field];
            // Make sure joins were inspected
            if (isset($j['fkey'])
                    && ($class = $j['fkey'][0])
                    && class_exists($class)) {
                $v = $this->ht[$field] = $class::lookup(
                    array($j['fkey'][1] => $this->ht[$j['local']]));
                return $v;
            }
        }
        if (isset($default))
            return $default;
        throw new OrmException(sprintf(__('%s: %s: Field not defined'),
            get_class($this), $field));
    }
    function __get($field) {
        return $this->get($field, null);
    }

    function __isset($field) {
        return array_key_exists($field, $this->ht)
            || isset(static::$meta['joins'][$field]);
    }
    function __unset($field) {
        unset($this->ht[$field]);
    }

    function set($field, $value) {
        // Update of foreign-key by assignment to model instance
        if (isset(static::$meta['joins'][$field])) {
            $j = static::$meta['joins'][$field];
            if ($j['list'] && ($value instanceof InstrumentedList)) {
                // Magic list property
                $this->ht[$field] = $value;
                return;
            }
            if ($value === null) {
                // Pass. Set local field to NULL in logic below
            }
            elseif ($value instanceof $j['fkey'][0]) {
                if ($value->__new__)
                    $value->save();
                // Capture the object under the object's field name
                $this->ht[$field] = $value;
                $value = $value->get($j['fkey'][1]);
                // Fall through to the standard logic below
            }
            else
                throw new InvalidArgumentException(
                    sprintf(__('Expecting NULL or instance of %s'), $j['fkey'][0]));

            // Capture the foreign key id value
            $field = $j['local'];
        }
        // XXX: Fully support or die if updating pk
        // XXX: The contents of $this->dirty should be the value after the
        // previous fetch or save. For instance, if the value is changed more
        // than once, the original value should be preserved in the dirty list
        // on the second edit.
        $old = isset($this->ht[$field]) ? $this->ht[$field] : null;
        if ($old != $value) {
            $this->dirty[$field] = $old;
            $this->ht[$field] = $value;
        }
    }
    function __set($field, $value) {
        return $this->set($field, $value);
    }

    function setAll($props) {
        foreach ($props as $field=>$value)
            $this->set($field, $value);
    }

    function __setupForeignLists() {
        // Construct related lists
        if (isset(static::$meta['joins'])) {
            foreach (static::$meta['joins'] as $name => $j) {
                if (isset($this->ht[$j['local']])
                        && isset($j['list']) && $j['list']) {
                    $fkey = $j['fkey'];
                    $this->set($name, new InstrumentedList(
                        // Send Model, Foriegn-Field, Local-Id
                        array($fkey[0], $fkey[1], $this->get($j['local'])))
                    );
                }
            }
        }
    }

    function __onload() {}

    static function _inspect() {
        if (!static::$meta['table'])
            throw new OrmConfigurationException(
                __('Model does not define meta.table'), get_called_class());

        // Break down foreign-key metadata
        foreach (static::$meta['joins'] as $field => &$j) {
            if (isset($j['reverse'])) {
                list($model, $key) = explode('.', $j['reverse']);
                $info = $model::$meta['joins'][$key];
                $constraint = array();
                if (!is_array($info['constraint']))
                    throw new OrmConfigurationException(sprintf(__(
                        // `reverse` here is the reverse of an ORM relationship
                        '%s: Reverse does not specify any constraints'),
                        $j['reverse']));
                foreach ($info['constraint'] as $foreign => $local) {
                    list(,$field) = explode('.', $local);
                    $constraint[$field] = "$model.$foreign";
                }
                $j['constraint'] = $constraint;
                if (!isset($j['list']))
                    $j['list'] = true;
            }
            // XXX: Make this better (ie. composite keys)
            $keys = array_keys($j['constraint']);
            $foreign = $j['constraint'][$keys[0]];
            $j['fkey'] = explode('.', $foreign);
            $j['local'] = $keys[0];
        }
    }

    static function objects() {
        return new QuerySet(get_called_class());
    }

    static function lookup($criteria) {
        if (!is_array($criteria))
            // Model::lookup(1), where >1< is the pk value
            $criteria = array(static::$meta['pk'][0] => $criteria);
        return static::objects()->filter($criteria)->one();
    }

    function delete($pk=false) {
        $table = static::$meta['table'];
        $sql = 'DELETE FROM '.$table;
        $filter = array();

        if (!$pk) $pk = static::$meta['pk'];
        if (!is_array($pk)) $pk=array($pk);

        foreach ($pk as $p)
            $filter[] = $p.' = '.db_input($this->get($p));
        $sql .= ' WHERE '.implode(' AND ', $filter).' LIMIT 1';
        if (!db_query($sql) || db_affected_rows() != 1)
            throw new Exception(db_error());
        Signal::send('model.deleted', $this);
        return true;
    }

    function save($refetch=false) {
        $pk = static::$meta['pk'];
        if (!is_array($pk)) $pk=array($pk);
        if ($this->__new__)
            $sql = 'INSERT INTO '.static::$meta['table'];
        else
            $sql = 'UPDATE '.static::$meta['table'];
        $filter = $fields = array();
        if (count($this->dirty) === 0)
            return true;
        foreach ($this->dirty as $field=>$old) {
            if ($this->__new__ or !in_array($field, $pk)) {
                if (@get_class($this->get($field)) == 'SqlFunction')
                    $fields[] = $field.' = '.$this->get($field)->toSql();
                else
                    $fields[] = $field.' = '.db_input($this->get($field));
            }
        }
        $sql .= ' SET '.implode(', ', $fields);
        if (!$this->__new__) {
            foreach ($pk as $p)
                $filter[] = $p.' = '.db_input($this->get($p));
            $sql .= ' WHERE '.implode(' AND ', $filter);
            $sql .= ' LIMIT 1';
        }
        if (!db_query($sql) || db_affected_rows() != 1)
            throw new Exception(db_error());
        if ($this->__new__) {
            if (count($pk) == 1)
                $this->ht[$pk[0]] = db_insert_id();
            $this->__new__ = false;
            // Setup lists again
            $this->__setupForeignLists();
            Signal::send('model.created', $this);
        }
        else {
            $data = array('dirty' => $this->dirty);
            Signal::send('model.updated', $this, $data);
        }
        # Refetch row from database
        # XXX: Too much voodoo
        if ($refetch) {
            # XXX: Support composite PK
            $criteria = array($pk[0] => $this->get($pk[0]));
            $self = static::lookup($criteria);
            $this->ht = $self->ht;
        }
        $this->dirty = array();
        return $this->get($pk[0]);
    }

    static function create($ht=false) {
        if (!$ht) $ht=array();
        $class = get_called_class();
        $i = new $class(array());
        $i->__new__ = true;
        foreach ($ht as $field=>$value)
            if (!is_array($value))
                $i->set($field, $value);
        return $i;
    }
}

class SqlFunction {
    function SqlFunction($name) {
        $this->func = $name;
        $this->args = array_slice(func_get_args(), 1);
    }

    function toSql($compiler=false) {
        $args = (count($this->args)) ? implode(',', db_input($this->args)) : "";
        return sprintf('%s(%s)', $this->func, $args);
    }

    static function __callStatic($func, $args) {
        $I = new static($func);
        $I->args = $args;
        return $I;
    }
}

class QuerySet implements IteratorAggregate, ArrayAccess {
    var $model;

    var $constraints = array();
    var $exclusions = array();
    var $ordering = array();
    var $limit = false;
    var $offset = 0;
    var $related = array();
    var $values = array();
    var $lock = false;

    const LOCK_EXCLUSIVE = 1;
    const LOCK_SHARED = 2;

    var $compiler = 'MySqlCompiler';
    var $iterator = 'ModelInstanceIterator';

    var $params;
    var $query;

    function __construct($model) {
        $this->model = $model;
    }

    function filter() {
        // Multiple arrays passes means OR
        $this->constraints[] = func_get_args();
        return $this;
    }

    function exclude() {
        $this->exclusions[] = func_get_args();
        return $this;
    }

    function order_by() {
        $this->ordering = array_merge($this->ordering, func_get_args());
        return $this;
    }

    function lock($how=false) {
        $this->lock = $how ?: self::LOCK_EXCLUSIVE;
        return $this;
    }

    function limit($count) {
        $this->limit = $count;
        return $this;
    }

    function offset($at) {
        $this->offset = $at;
        return $this;
    }

    function select_related() {
        $this->related = array_merge($this->related, func_get_args());
        return $this;
    }

    function values() {
        $this->values = func_get_args();
        $this->iterator = 'HashArrayIterator';
        return $this;
    }

    function values_flat() {
        $this->values = func_get_args();
        $this->iterator = 'FlatArrayIterator';
        return $this;
    }

    function all() {
        return $this->getIterator()->asArray();
    }

    function one() {
        $list = $this->limit(1)->all();
        // TODO: Throw error if more than one result from database
        return $this[0];
    }

    function count() {
        $class = $this->compiler;
        $compiler = new $class();
        return $compiler->compileCount($this);
    }

    function exists() {
        return $this->count() > 0;
    }

    function delete() {
        $class = $this->compiler;
        $compiler = new $class();
        $ex = $compiler->compileBulkDelete($this);
        $ex->execute();
        return $ex->affected_rows();
    }

    function update(array $what) {
        $class = $this->compiler;
        $compiler = new $class;
        $ex = $compiler->compileBulkUpdate($this, $what);
        $ex->execute();
        return $ex->affected_rows();
    }

    function __clone() {
        unset($this->_iterator);
        unset($this->query);
    }

    // IteratorAggregate interface
    function getIterator() {
        $class = $this->iterator;
        if (!isset($this->_iterator))
            $this->_iterator = new $class($this);
        return $this->_iterator;
    }

    // ArrayAccess interface
    function offsetExists($offset) {
        return $this->getIterator()->offsetExists($offset);
    }
    function offsetGet($offset) {
        return $this->getIterator()->offsetGet($offset);
    }
    function offsetUnset($a) {
        throw new Exception(__('QuerySet is read-only'));
    }
    function offsetSet($a, $b) {
        throw new Exception(__('QuerySet is read-only'));
    }

    function __toString() {
        return (string) $this->getQuery();
    }

    function getQuery($options=array()) {
        if (isset($this->query))
            return $this->query;

        // Load defaults from model
        $model = $this->model;
        if (!$this->ordering && isset($model::$meta['ordering']))
            $this->ordering = $model::$meta['ordering'];

        $class = $this->compiler;
        $compiler = new $class($options);
        $this->query = $compiler->compileSelect($this);

        return $this->query;
    }
}

class ModelInstanceIterator implements Iterator, ArrayAccess {
    var $model;
    var $resource;
    var $cache = array();
    var $position = 0;
    var $queryset;

    function __construct($queryset=false) {
        $this->queryset = $queryset;
        if ($queryset) {
            $this->model = $queryset->model;
            $this->resource = $queryset->getQuery();
        }
    }

    function buildModel($row) {
        // TODO: Traverse to foreign keys
        $model = new $this->model($row); # nolint
        $model->__onload();
        return $model;
    }

    function fillTo($index) {
        while ($this->resource && $index >= count($this->cache)) {
            if ($row = $this->resource->getArray()) {
                $this->cache[] = $this->buildModel($row);
            } else {
                $this->resource->close();
                $this->resource = null;
                break;
            }
        }
    }

    function asArray() {
        $this->fillTo(PHP_INT_MAX);
        return $this->cache;
    }

    // Iterator interface
    function rewind() {
        $this->position = 0;
    }
    function current() {
        $this->fillTo($this->position);
        return $this->cache[$this->position];
    }
    function key() {
        return $this->position;
    }
    function next() {
        $this->position++;
    }
    function valid() {
        $this->fillTo($this->position);
        return count($this->cache) > $this->position;
    }

    // ArrayAccess interface
    function offsetExists($offset) {
        $this->fillTo($offset);
        return $this->position >= $offset;
    }
    function offsetGet($offset) {
        $this->fillTo($offset);
        return $this->cache[$offset];
    }
    function offsetUnset($a) {
        throw new Exception(sprintf(__('%s is read-only'), get_class($this)));
    }
    function offsetSet($a, $b) {
        throw new Exception(sprintf(__('%s is read-only'), get_class($this)));
    }
}

class FlatArrayIterator extends ModelInstanceIterator {
    function __construct($queryset) {
        $this->resource = $queryset->getQuery();
    }
    function fillTo($index) {
        while ($this->resource && $index >= count($this->cache)) {
            if ($row = $this->resource->getRow()) {
                $this->cache[] = $row;
            } else {
                $this->resource->close();
                $this->resource = null;
                break;
            }
        }
    }
}

class InstrumentedList extends ModelInstanceIterator {
    var $key;
    var $id;
    var $model;

    function __construct($fkey, $queryset=false) {
        list($model, $this->key, $this->id) = $fkey;
        if (!$queryset)
            $queryset = $model::objects()->filter(array($this->key=>$this->id));
        parent::__construct($queryset);
        $this->model = $model;
        if (!$this->id)
            $this->resource = null;
    }

    function add($object, $at=false) {
        if (!$object || !$object instanceof $this->model)
            throw new Exception(__('Attempting to add invalid object to list'));

        $object->set($this->key, $this->id);
        $object->save();

        if ($at !== false)
            $this->cache[$at] = $object;
        else
            $this->cache[] = $object;
    }
    function remove($object) {
        $object->delete();
    }

    function reset() {
        $this->cache = array();
    }

    // QuerySet delegates
    function count() {
        return $this->queryset->count();
    }
    function exists() {
        return $this->queryset->exists();
    }
    function expunge() {
        if ($this->queryset->delete())
            $this->reset();
    }
    function update(array $what) {
        return $this->queryset->update($what);
    }

    // Fetch a new QuerySet
    function objects() {
        return clone $this->queryset;
    }

    function offsetUnset($a) {
        $this->fillTo($a);
        $this->cache[$a]->delete();
    }
    function offsetSet($a, $b) {
        $this->fillTo($a);
        $this->cache[$a]->delete();
        $this->add($b, $a);
    }
}

class SqlCompiler {
    var $options = array();
    var $params = array();
    var $joins = array();
    var $aliases = array();
    var $alias_num = 1;

    static $operators = array(
        'exact' => '%$1s = %$2s'
    );

    function __construct($options=false) {
        if ($options)
            $this->options = array_merge($this->options, $options);
    }

    /**
     * Handles breaking down a field or model search descriptor into the
     * model search path, field, and operator parts. When used in a queryset
     * filter, an expression such as
     *
     * user__email__hostname__contains => 'foobar'
     *
     * would be broken down to search from the root model (passed in,
     * perhaps a ticket) to the user and email models by inspecting the
     * model metadata 'joins' property. The 'constraint' value found there
     * will be used to build the JOIN sql clauses.
     *
     * The 'hostname' will be the field on 'email' model that should be
     * compared in the WHERE clause. The comparison should be made using a
     * 'contains' function, which in MySQL, might be implemented using
     * something like "<lhs> LIKE '%foobar%'"
     *
     * This function will rely heavily on the pushJoin() function which will
     * handle keeping track of joins made previously in the query and
     * therefore prevent multiple joins to the same table for the same
     * reason. (Self joins are still supported).
     *
     * Comparison functions supported by this function are defined for each
     * respective SqlCompiler subclass; however at least these functions
     * should be defined:
     *
     *      function    a__function => b
     *      ----------+------------------------------------------------
     *      exact     | a is exactly equal to b
     *      gt        | a is greater than b
     *      lte       | b is greater than a
     *      lt        | a is less than b
     *      gte       | b is less than a
     *      ----------+------------------------------------------------
     *      contains  | (string) b is contained within a
     *      statswith | (string) first len(b) chars of a are exactly b
     *      endswith  | (string) last len(b) chars of a are exactly b
     *      like      | (string) a matches pattern b
     *      ----------+------------------------------------------------
     *      in        | a is in the list or the nested queryset b
     *      ----------+------------------------------------------------
     *      isnull    | a is null (if b) else a is not null
     *
     * If no comparison function is declared in the field descriptor,
     * 'exact' is assumed.
     */
    function getField($field, $model, $options=array()) {
        $joins = array();

        // Break apart the field descriptor by __ (double-underbars). The
        // first part is assumed to be the root field in the given model.
        // The parts after each of the __ pieces are links to other tables.
        // The last item (after the last __) is allowed to be an operator
        // specifiction.
        $parts = explode('__', $field);
        $operator = static::$operators['exact'];
        if (!isset($options['table'])) {
            $field = array_pop($parts);
            if (array_key_exists($field, static::$operators)) {
                $operator = static::$operators[$field];
                $field = array_pop($parts);
            }
        }

        $path = array();
        $crumb = '';
        $alias = $this->quote($model::$meta['table']);

        // Traverse through the parts and establish joins between the tables
        // if the field is joined to a foreign model
        if (count($parts) && isset($model::$meta['joins'][$parts[0]])) {
            // Call pushJoin for each segment in the join path. A new
            // JOIN fragment will need to be emitted and/or cached
            foreach ($parts as $p) {
                $path[] = $p;
                $tip = implode('__', $path);
                $info = $model::$meta['joins'][$p];
                $alias = $this->pushJoin($crumb, $tip, $model, $info);
                // Roll to foreign model
                foreach ($info['constraint'] as $local => $foreign) {
                    list($model, $f) = explode('.', $foreign);
                    if (class_exists($model))
                        break;
                }
                $crumb = $tip;
            }
        }
        if (isset($options['table']) && $options['table'])
            $field = $alias;
        elseif ($alias)
            $field = $alias.'.'.$this->quote($field);
        else
            $field = $this->quote($field);
        return array($field, $operator);
    }

    /**
     * Uses the compiler-specific `compileJoin` function to compile the join
     * statement fragment, and caches the result in the local $joins list. A
     * new alias is acquired using the `nextAlias` function which will be
     * associated with the join. If the same path is requested again, the
     * algorithm is short-circuited and the originally-assigned table alias
     * is returned immediately.
     */
    function pushJoin($tip, $path, $model, $info) {
        // TODO: Build the join statement fragment and return the table
        // alias. The table alias will be useful where the join is used in
        // the WHERE and ORDER BY clauses

        // If the join already exists for the statement-being-compiled, just
        // return the alias being used.
        if (isset($this->joins[$path]))
            return $this->joins[$path]['alias'];

        // TODO: Support only using aliases if necessary. Use actual table
        // names for everything except oddities like self-joins

        $alias = $this->nextAlias();
        // Keep an association between the table alias and the model. This
        // will make model construction much easier when we have the data
        // and the table alias from the database.
        $this->aliases[$alias] = $model;

        // TODO: Stash joins and join constraints into local ->joins array.
        // This will be useful metadata in the executor to construct the
        // final models for fetching
        // TODO: Always use a table alias. This will further help with
        // coordination between the data returned from the database (where
        // table alias is available) and the corresponding data.
        $this->joins[$path] = array(
            'alias' => $alias,
            'sql'=> $this->compileJoin($tip, $model, $alias, $info),
        );
        return $alias;
    }

    function compileConstraints($where, $model) {
        $constraints = array();
        foreach ($where as $constraint) {
            $filter = array();
            foreach ($constraint as $field=>$value) {
                list($field, $op) = $this->getField($field, $model);
                // Allow operators to be callable rather than sprintf
                // strings
                if ($value === null)
                    $filter[] = sprintf('%s IS NULL', $field);
                elseif (is_callable($op))
                    $filter[] = call_user_func($op, $field, $value);
                else
                    $filter[] = sprintf($op, $field, $this->input($value));
            }
            // Multiple constraints here are ANDed together
            $constraints[] = implode(' AND ', $filter);
        }
        // Multiple constrains here are ORed together
        $filter = implode(' OR ', $constraints);
        if (count($constraints) > 1)
            $filter = '(' . $filter . ')';
        return $filter;
    }

    function getParams() {
        return $this->params;
    }

    function getJoins() {
        $sql = '';
        foreach ($this->joins as $j)
            $sql .= $j['sql'];
        return $sql;
    }

    function nextAlias() {
        // Use alias A1-A9,B1-B9,...
        $alias = chr(65 + (int)($this->alias_num / 9)) . $this->alias_num % 9;
        $this->alias_num++;
        return $alias;
    }
}

class DbEngine {

    function __construct($info) {
    }

    function connect() {
    }

    // Gets a compiler compatible with this database engine that can compile
    // and execute a queryset or DML request.
    function getCompiler() {
    }
}

class MySqlCompiler extends SqlCompiler {

    static $operators = array(
        'exact' => '%1$s = %2$s',
        'contains' => array('self', '__contains'),
        'gt' => '%1$s > %2$s',
        'lt' => '%1$s < %2$s',
        'gte' => '%1$s >= %2$s',
        'lte' => '%1$s <= %2$s',
        'isnull' => array('self', '__isnull'),
        'like' => '%1$s LIKE %2$s',
        'hasbit' => '%1$s & %2$s != 0',
        'in' => array('self', '__in'),
    );

    function __contains($a, $b) {
        # {%a} like %{$b}%
        return sprintf('%s LIKE %s', $a, $this->input($b = "%$b%"));
    }

    function __in($a, $b) {
        if (is_array($b)) {
            $vals = array_map(array($this, 'input'), $b);
            $b = implode(', ', $vals);
        }
        else {
            $b = $this->input($b);
        }
        return sprintf('%s IN (%s)', $a, $b);
    }

    function __isnull($a, $b) {
        return $b
            ? sprintf('%s IS NULL', $a)
            : sprintf('%s IS NOT NULL', $a);
    }

    function compileJoin($tip, $model, $alias, $info) {
        $constraints = array();
        $join = ' JOIN ';
        if (isset($info['null']) && $info['null'])
            $join = ' LEFT'.$join;
        if (isset($this->joins[$tip]))
            $table = $this->joins[$tip]['alias'];
        else
            $table = $this->quote($model::$meta['table']);
        foreach ($info['constraint'] as $local => $foreign) {
            list($rmodel, $right) = explode('.', $foreign);
            // TODO: Support a constant constraint
            $constraints[] = sprintf("%s.%s = %s.%s",
                $table, $this->quote($local), $alias,
                $this->quote($right)
            );
        }
        return $join.$this->quote($rmodel::$meta['table'])
            .' '.$alias.' ON ('.implode(' AND ', $constraints).')';
    }

    function input(&$what) {
        if ($what instanceof QuerySet) {
            $q = $what->getQuery(array('nosort'=>true));
            $this->params += $q->params;
            return (string)$q;
        }
        elseif ($what instanceof SqlFunction) {
            return $what->toSql($this);
        }
        else {
            $this->params[] = $what;
            return '?';
        }
    }

    function quote($what) {
        return "`$what`";
    }

    /**
     * getWhereClause
     *
     * This builds the WHERE ... part of a DML statement. This should be
     * called before ::getJoins(), because it may add joins into the
     * statement based on the relationships used in the where clause
     */
    protected function getWhereClause($queryset) {
        $model = $queryset->model;
        $where_pos = array();
        $where_neg = array();
        foreach ($queryset->constraints as $where) {
            $where_pos[] = $this->compileConstraints($where, $model);
        }
        foreach ($queryset->exclusions as $where) {
            $where_neg[] = $this->compileConstraints($where, $model);
        }

        $where = '';
        if ($where_pos || $where_neg) {
            $where = ' WHERE '.implode(' AND ', $where_pos)
                .implode(' AND NOT ', $where_neg);
        }
        return $where;
    }

    function compileCount($queryset) {
        $model = $queryset->model;
        $table = $model::$meta['table'];
        $where = $this->getWhereClause($queryset);
        $joins = $this->getJoins();
        $sql = 'SELECT COUNT(*) AS count FROM '.$this->quote($table).$joins.$where;
        $exec = new MysqlExecutor($sql, $this->params);
        $row = $exec->getArray();
        return $row['count'];
    }

    function compileSelect($queryset) {
        $model = $queryset->model;
        $where = $this->getWhereClause($queryset);

        $sort = '';
        if ($queryset->ordering && !isset($this->options['nosort'])) {
            $orders = array();
            foreach ($queryset->ordering as $sort) {
                $dir = 'ASC';
                if (substr($sort, 0, 1) == '-') {
                    $dir = 'DESC';
                    $sort = substr($sort, 1);
                }
                list($field) = $this->getField($sort, $model);
                $orders[] = $field.' '.$dir;
            }
            $sort = ' ORDER BY '.implode(', ', $orders);
        }

        // Include related tables
        $fields = array();
        $table = $model::$meta['table'];
        if ($queryset->related) {
            $fields = array($this->quote($table).'.*');
            foreach ($queryset->related as $rel) {
                // XXX: This is ugly
                list($t) = $this->getField($rel, $model,
                    array('table'=>true));
                $fields[] = $t.'.*';
            }
        // Support only retrieving a list of values rather than a model
        } elseif ($queryset->values) {
            foreach ($queryset->values as $v) {
                list($fields[]) = $this->getField($v, $model);
            }
        } else {
            $fields[] = $this->quote($table).'.*';
        }

        $joins = $this->getJoins();
        $sql = 'SELECT '.implode(', ', $fields).' FROM '
            .$this->quote($table).$joins.$where.$sort;
        if ($queryset->limit)
            $sql .= ' LIMIT '.$queryset->limit;
        if ($queryset->offset)
            $sql .= ' OFFSET '.$queryset->offset;
        switch ($queryset->lock) {
        case QuerySet::LOCK_EXCLUSIVE:
            $sql .= ' FOR UPDATE';
            break;
        case QuerySet::LOCK_SHARED:
            $sql .= ' LOCK IN SHARE MODE';
            break;
        }

        return new MysqlExecutor($sql, $this->params);
    }

    function compileUpdate() {
    }

    function compileInsert() {
    }

    function compileBulkDelete($queryset) {
        $model = $queryset->model;
        $table = $model::$meta['table'];
        $where = $this->getWhereClause($queryset);
        $joins = $this->getJoins();
        $sql = 'DELETE '.$this->quote($table).'.* FROM '
            .$this->quote($table).$joins.$where;
        return new MysqlExecutor($sql, $this->params);
    }

    function compileBulkUpdate($queryset, array $what) {
        $model = $queryset->model;
        $table = $model::$meta['table'];
        $set = array();
        foreach ($what as $field=>$value)
            $set[] = sprintf('%s = %s', $this->quote($field), $this->input($value));
        $set = implode(', ', $set);
        $where = $this->getWhereClause($queryset);
        $joins = $this->getJoins();
        $sql = 'UPDATE '.$this->quote($table).' SET '.$set.$joins.$where;
        return new MysqlExecutor($sql, $this->params);
    }

    // Returns meta data about the table used to build queries
    function inspectTable($table) {
    }
}

class MysqlExecutor {

    var $stmt;
    var $fields = array();

    var $sql;
    var $params;

    function __construct($sql, $params) {
        $this->sql = $sql;
        $this->params = $params;
    }

    function _prepare() {
        $this->execute();
        $this->_setup_output();
    }

    function execute() {
        if (!($this->stmt = db_prepare($this->sql)))
            throw new Exception('Unable to prepare query: '.db_error()
                .' '.$this->sql);
        if (count($this->params))
            $this->_bind($this->params);
        if (!$this->stmt->execute() || ! $this->stmt->store_result()) {
            throw new OrmException('Unable to execute query: ' . $this->stmt->error);
        }
        return true;
    }

    function _bind($params) {
        if (count($params) != $this->stmt->param_count)
            throw new Exception(__('Parameter count does not match query'));

        $types = '';
        $ps = array();
        foreach ($params as &$p) {
            if (is_int($p) || is_bool($p))
                $types .= 'i';
            elseif (is_float($p))
                $types .= 'd';
            elseif (is_string($p))
                $types .= 's';
            // TODO: Emit error if param is null
            $ps[] = &$p;
        }
        unset($p);
        array_unshift($ps, $types);
        call_user_func_array(array($this->stmt,'bind_param'), $ps);
    }

    function _setup_output() {
        if (!($meta = $this->stmt->result_metadata()))
            throw new OrmException('Unable to fetch statment metadata: ', $this->stmt->error);
        while ($f = $meta->fetch_field())
            $this->fields[] = $f;
        $meta->free_result();
    }

    // Iterator interface
    function rewind() {
        if (!isset($this->stmt))
            $this->_prepare();
        $this->stmt->data_seek(0);
    }

    function next() {
        $status = $this->stmt->fetch();
        if ($status === false)
            throw new OrmException($this->stmt->error);
        elseif ($status === null) {
            $this->close();
            return false;
        }
        return true;
    }

    function getArray() {
        $output = array();
        $variables = array();

        if (!isset($this->stmt))
            $this->_prepare();

        foreach ($this->fields as $f)
            $variables[] = &$output[$f->name]; // pass by reference

        if (!call_user_func_array(array($this->stmt, 'bind_result'), $variables))
            throw new OrmException('Unable to bind result: ' . $this->stmt->error);

        if (!$this->next())
            return false;
        return $output;
    }

    function getRow() {
        $output = array();
        $variables = array();

        if (!isset($this->stmt))
            $this->_prepare();

        foreach ($this->fields as $f)
            $variables[] = &$output[]; // pass by reference

        if (!call_user_func_array(array($this->stmt, 'bind_result'), $variables))
            throw new OrmException('Unable to bind result: ' . $this->stmt->error);

        if (!$this->next())
            return false;
        return $output;
    }

    function getStruct() {
        $output = array();
        $variables = array();

        if (!isset($this->stmt))
            $this->_prepare();

        foreach ($this->fields as $f)
            $variables[] = &$output[$f->table][$f->name]; // pass by reference

        // TODO: Figure out what the table alias for the root model will be
        call_user_func_array(array($this->stmt, 'bind_result'), $variables);
        if (!$this->next())
            return false;
        return $output;
    }

    function close() {
        if (!$this->stmt)
            return;

        $this->stmt->close();
        $this->stmt = null;
    }

    function affected_rows() {
        return $this->stmt->affected_rows;
    }

    function insert_id() {
        return $this->stmt->insert_id;
    }

    function __toString() {
        return $this->sql;
    }
}
?>
