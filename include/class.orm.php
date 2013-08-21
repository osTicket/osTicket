<?php
/*********************************************************************
    class.orm.php

    Simple ORM (Object Relational Mapper) for PHPv4 based on Django's ORM,
    except that complex filter operations are not supported. The ORM simply
    supports ANDed filter operations without any GROUP BY support.

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

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
        $this->dirty = array();
    }

    function get($field) {
        return $this->ht[$field];
    }
    function __get($field) {
        if (array_key_exists($field, $this->ht))
            return $this->ht[$field];
        return $this->{$field};
    }

    function set($field, $value) {
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

    function _inspect() {
        if (!static::$meta['table'])
            throw new OrmConfigurationError(
                'Model does not define meta.table', $this);
    }

    static function objects() {
        return new QuerySet(get_called_class());
    }

    static function lookup($criteria) {
        if (!is_array($criteria))
            // Model::lookup(1), where >1< is the pk value
            $criteria = array(static::$meta['pk'][0] => $criteria);
        $list = static::objects()->filter($criteria)->limit(1);
        // TODO: Throw error if more than one result from database
        return $list[0];
    }

    function delete($pk=false) {
        $table = static::$meta['table'];
        $sql = 'DELETE FROM '.$table;

        if (!$pk) $pk = static::$meta['pk'];
        if (!is_array($pk)) $pk=array($pk);

        foreach ($pk as $p)
            $filter[] = $p.' = '.$this->input($this->get($p));
        $sql .= ' WHERE '.implode(' AND ', $filter).' LIMIT 1';
        return db_affected_rows(db_query($sql)) == 1;
    }

    function save($refetch=false) {
        $pk = static::$meta['pk'];
        if (!$this->isValid())
            return false;
        if (!is_array($pk)) $pk=array($pk);
        if ($this->__new__)
            $sql = 'INSERT INTO '.static::$meta['table'];
        else
            $sql = 'UPDATE '.static::$meta['table'];
        $filter = $fields = array();
        if (count($this->dirty) === 0)
            return;
        foreach ($this->dirty as $field=>$old)
            if ($this->__new__ or !in_array($field, $pk))
                if (@get_class($this->get($field)) == 'SqlFunction')
                    $fields[] = $field.' = '.$this->get($field)->toSql();
                else
                    $fields[] = $field.' = '.db_input($this->get($field));
        foreach ($pk as $p)
            $filter[] = $p.' = '.db_input($this->get($p));
        $sql .= ' SET '.implode(', ', $fields);
        if (!$this->__new__) {
            $sql .= ' WHERE '.implode(' AND ', $filter);
            $sql .= ' LIMIT 1';
        }
        if (db_affected_rows(db_query($sql)) != 1) {
            throw new Exception(db_error());
            return false;
        }
        if ($this->__new__) {
            if (count($pk) == 1)
                $this->ht[$pk[0]] = db_insert_id();
            $this->__new__ = false;
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

    /**
     * isValid
     *
     * Validates the contents of $this->ht before the model should be
     * committed to the database. This is the validation for the field
     * template -- edited in the admin panel for a form section.
     */
    function isValid() {
        return true;
    }
}

class SqlFunction {
    function SqlFunction($name) {
        $this->func = $name;
        $this->args = array_slice(func_get_args(), 1);
    }

    function toSql() {
        $args = (count($this->args)) ? implode(',', db_input($this->args)) : "";
        return sprintf('%s(%s)', $this->func, $args);
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

    function all() {
        return $this->getIterator()->asArray();
    }

    function count() {
        $compiler = new $this->compiler();
        return $compiler->compileCount($this);
    }

    // IteratorAggregate interface
    function getIterator() {
        if (!isset($this->_iterator))
            $this->_iterator = new $this->iterator($this);
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
        throw new Exception('QuerySet is read-only');
    }
    function offsetSet($a, $b) {
        throw new Exception('QuerySet is read-only');
    }

    function __toString() {
        return (string)$this->getQuery();
    }

    function getQuery() {
        if (isset($this->query))
            return $this->query;

        // Load defaults from model
        $model = $this->model;
        if (!$this->ordering && isset($model::$meta['ordering']))
            $this->ordering = $model::$meta['ordering'];

        $compiler = new $this->compiler();
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

    function __construct($queryset) {
        $this->model = $queryset->model;
        $this->resource = $queryset->getQuery();
    }

    function buildModel($row) {
        // TODO: Traverse to foreign keys
        return new $this->model($row);
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
        throw new Exception(sprintf('%s is read-only', get_class($this)));
    }
    function offsetSet($a, $b) {
        throw new Exception(sprintf('%s is read-only', get_class($this)));
    }
}

class MySqlCompiler {
    var $params = array();

    static $operators = array(
        'exact' => '%1$s = %2$s',
        'contains' => array('self', '__contains'),
        'gt' => '%1$s > %2$s',
        'lt' => '%1$s < %2$s',
        'isnull' => '%1$s IS NULL',
        'like' => '%1$s LIKE %2$s',
    );

    function __contains($a, $b) {
        # {%a} like %{$b}%
        return sprintf('%s LIKE %s', $a, $this->input("%$b%"));
    }

    function _get_joins_and_field($field, $model, $options=array()) {
        $joins = array();

        // Break apart the field descriptor by __ (double-underbars). The
        // first part is assumed to be the root field in the given model.
        // The parts after each of the __ pieces are links to other tables.
        // The last item (after the last __) is allowed to be an operator
        // specifiction.
        $parts = explode('__', $field);
        $field = array_pop($parts);
        if (array_key_exists($field, self::$operators)) {
            $operator = self::$operators[$field];
            $field = array_pop($parts);
        } else {
            $operator = self::$operators['exact'];
        }

        // Form the official join path (with the operator and foreign field
        // removed)
        $spec = implode('__', $parts);

        // TODO: If the join-spec already exists in the compiler, maybe use
        //       table aliases for the join a second time

        // Traverse through the parts and establish joins between the tables
        // if the field is joined to a foreign model
        if (count($parts) && isset($model::$meta['joins'][$parts[0]])) {
            foreach ($parts as $p) {
                $constraints = array();
                $info = $model::$meta['joins'][$p];
                $join = ' JOIN ';
                if (isset($info['null']) && $info['null'])
                    $join = ' LEFT'.$join;
                foreach ($info['constraint'] as $local => $foreign) {
                    $table = $model::$meta['table'];
                    list($model, $right) = explode('.', $foreign);
                    $constraints[] = sprintf("%s.%s = %s.%s",
                        $this->quote($table), $this->quote($local),
                        $this->quote($model::$meta['table']), $this->quote($right)
                    );
                }
                $joins[] = $join.$this->quote($model::$meta['table'])
                    .' ON ('.implode(' AND ', $constraints).')';
            }
        }
        if (isset($options['table']) && $options['table'])
            $field = $this->quote($model::$meta['table']);
        elseif ($table)
            $field = $this->quote($model::$meta['table']).'.'.$this->quote($field);
        else
            $field = $this->quote($field);
        return array($joins, $field, $operator);
    }

    function _compile_where($where, $model) {
        $joins = array();
        $constrints = array();
        foreach ($where as $constraint) {
            $filter = array();
            foreach ($constraint as $field=>$value) {
                list($js, $field, $op) = self::_get_joins_and_field($field, $model);
                $joins = array_merge($joins, $js);
                // Allow operators to be callable rather than sprintf
                // strings
                if (is_callable($op))
                    $filter[] = $op($field, $value);
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
        return array($joins, $filter);
    }

    function input($what) {
        $this->params[] = $what;
        return '?';
    }

    function quote($what) {
        return "`$what`";
    }

    function getParams() {
        return $this->params;
    }

    function compileCount($queryset) {
        $model = $queryset->model;
        $table = $model::$meta['table'];
        $where_pos = array();
        $where_neg = array();
        $joins = array();
        foreach ($queryset->constraints as $where) {
            list($_joins, $filter) = $this->_compile_where($where, $model);
            $where_pos[] = $filter;
            $joins = array_merge($joins, $_joins);
        }
        foreach ($queryset->exclusions as $where) {
            list($_joins, $filter) = $this->_compile_where($where, $model);
            $where_neg[] = $filter;
            $joins = array_merge($joins, $_joins);
        }

        $where = '';
        if ($where_pos || $where_neg) {
            $where = ' WHERE '.implode(' AND ', $where_pos)
                .implode(' AND NOT ', $where_neg);
        }
        $sql = 'SELECT COUNT(*) AS count FROM '.$this->quote($table).$joins.$where;
        $exec = new MysqlExecutor($sql, $this->params);
        $row = $exec->getArray();
        return $row['count'];
    }

    function compileSelect($queryset) {
        $model = $queryset->model;
        $where_pos = array();
        $where_neg = array();
        $joins = array();
        foreach ($queryset->constraints as $where) {
            list($_joins, $filter) = $this->_compile_where($where, $model);
            $where_pos[] = $filter;
            $joins = array_merge($joins, $_joins);
        }
        foreach ($queryset->exclusions as $where) {
            list($_joins, $filter) = $this->_compile_where($where, $model);
            $where_neg[] = $filter;
            $joins = array_merge($joins, $_joins);
        }

        $where = '';
        if ($where_pos || $where_neg) {
            $where = ' WHERE '.implode(' AND ', $where_pos)
                .implode(' AND NOT ', $where_neg);
        }

        $sort = '';
        if ($queryset->ordering) {
            $orders = array();
            foreach ($queryset->ordering as $sort) {
                $dir = 'ASC';
                if (substr($sort, 0, 1) == '-') {
                    $dir = 'DESC';
                    $sort = substr($sort, 1);
                }
                list($js, $field) = $this->_get_joins_and_field($sort, $model);
                $joins = ($joins) ? array_merge($joins, $js) : $js;
                $orders[] = $field.' '.$dir;
            }
            $sort = ' ORDER BY '.implode(', ', $orders);
        }

        // Include related tables
        $fields = array();
        $table = $model::$meta['table'];
        if ($queryset->related) {
            $tables = array($this->quote($table));
            foreach ($queryset->related as $rel) {
                list($js, $t) = $this->_get_joins_and_field($rel, $model,
                    array('table'=>true));
                $fields[] = $t.'.*';
                $joins = array_merge($joins, $js);
            }
        // Support only retrieving a list of values rather than a model
        } elseif ($queryset->values) {
            foreach ($queryset->values as $v) {
                list($js, $fields[]) = $this->_get_joins_and_field($v, $model);
                $joins = array_merge($joins, $js);
            }
        } else {
            $fields[] = $this->quote($table).'.*';
        }

        if (is_array($joins))
            # XXX: This will change the order of the joins
            $joins = implode('', array_unique($joins));
        $sql = 'SELECT '.implode(', ', $fields).' FROM '
            .$this->quote($table).$joins.$where.$sort;
        if ($queryset->limit)
            $sql .= ' LIMIT '.$queryset->limit;
        if ($queryset->offset)
            $sql .= ' OFFSET '.$queryset->offset;

        return new MysqlExecutor($sql, $this->params);
    }

    function compileUpdate() {
    }

    function compileInsert() {
    }

    function compileDelete() {
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
        if (!($this->stmt = db_prepare($this->sql)))
            throw new Exception('Unable to prepare query: '.db_error()
                .' '.$this->sql);
        if (count($this->params))
            $this->_bind($this->params);
        $this->stmt->execute();
        $this->_setup_output();
        $this->stmt->store_result();
    }

    function _bind($params) {
        if (count($params) != $this->stmt->param_count)
            throw new Exception('Parameter count does not match query');

        $types = '';
        $ps = array();
        foreach ($params as $p) {
            if (is_int($p))
                $types .= 'i';
            elseif (is_string($p))
                $types .= 's';
            $ps[] = &$p;
        }
        array_unshift($ps, $types);
        call_user_func_array(array($this->stmt,'bind_param'), $ps);
    }

    function _setup_output() {
        $meta = $this->stmt->result_metadata();
        while ($f = $meta->fetch_field())
            $this->fields[] = $f;
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
            throw new Exception($this->stmt->error_list . db_error());
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

    function __toString() {
        return $this->sql;
    }
}
?>
