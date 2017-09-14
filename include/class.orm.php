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
// Database fields/tables do not match codebase
class InconsistentModelException extends OrmException {
    function __construct() {
        // Drop the model cache (just incase)
        ModelMeta::flushModelCache();
        call_user_func_array(array('parent', '__construct'), func_get_args());
    }
}

/**
 * Meta information about a model including edges (relationships), table
 * name, default sorting information, database fields, etc.
 *
 * This class is constructed and built automatically from the model's
 * ::getMeta() method using a class's ::$meta array.
 */
class ModelMeta implements ArrayAccess {

    static $base = array(
        'pk' => false,
        'table' => false,
        'defer' => array(),
        'select_related' => array(),
        'view' => false,
        'joins' => array(),
        'foreign_keys' => array(),
    );
    static $model_cache;

    var $model;
    var $meta = array();
    var $new;
    var $subclasses = array();
    var $fields;

    function __construct($model) {
        $this->model = $model;

        // Merge ModelMeta from parent model (if inherited)
        $parent = get_parent_class($this->model);
        $meta = $model::$meta;
        if ($model::$meta instanceof self)
            $meta = $meta->meta;
        if (is_subclass_of($parent, 'VerySimpleModel')) {
            $this->parent = $parent::getMeta();
            $meta = $this->parent->extend($this, $meta);
        }
        else {
            $meta = $meta + self::$base;
        }

        // Short circuit the meta-data processing if APCu is available.
        // This is preferred as the meta-data is unlikely to change unless
        // osTicket is upgraded, (then the upgrader calls the
        // flushModelCache method to clear this cache). Also, GIT_VERSION is
        // used in the APC key which should be changed if new code is
        // deployed.
        if (function_exists('apcu_store')) {
            $loaded = false;
            $apc_key = SECRET_SALT.GIT_VERSION."/orm/{$this->model}";
            $this->meta = apcu_fetch($apc_key, $loaded);
            if ($loaded)
                return;
        }

        if (!$meta['view']) {
            if (!$meta['table'])
                throw new OrmConfigurationException(
                    sprintf(__('%s: Model does not define meta.table'), $this->model));
            elseif (!$meta['pk'])
                throw new OrmConfigurationException(
                    sprintf(__('%s: Model does not define meta.pk'), $this->model));
        }

        // Ensure other supported fields are set and are arrays
        foreach (array('pk', 'ordering', 'defer', 'select_related') as $f) {
            if (!isset($meta[$f]))
                $meta[$f] = array();
            elseif (!is_array($meta[$f]))
                $meta[$f] = array($meta[$f]);
        }

        // Break down foreign-key metadata
        foreach ($meta['joins'] as $field => &$j) {
            $this->processJoin($j);
            if ($j['local'])
                $meta['foreign_keys'][$j['local']] = $field;
        }
        unset($j);
        $this->meta = $meta;

        if (function_exists('apcu_store')) {
            apcu_store($apc_key, $this->meta, 1800);
        }
    }

    function extend(ModelMeta $child, $meta) {
        $this->subclasses[$child->model] = $child;
        return $meta + $this->meta + self::$base;
    }

    function isSuperClassOf($model) {
        if (isset($this->subclasses[$model]))
            return true;
        foreach ($this->subclasses as $M=>$meta)
            if ($meta->isSuperClassOf($M))
                return true;
    }

    function isSubclassOf($model) {
        if (!isset($this->parent))
            return false;

        if ($this->parent->model === $model)
            return true;

        return $this->parent->isSubclassOf($model);
    }

    /**
     * Adds some more information to a declared relationship. If the
     * relationship is a reverse relation, then the information from the
     * reverse relation is loaded into the local definition
     *
     * Compiled-Join-Structure:
     * 'constraint' => array(local => array(foreign_field, foreign_class)),
     *      Constraint used to construct a JOIN in an SQL query
     * 'list' => boolean
     *      TRUE if an InstrumentedList should be employed to fetch a list
     *      of related items
     * 'broker' => Handler for the 'list' property. Usually a subclass of
     *      'InstrumentedList'
     * 'null' => boolean
     *      TRUE if relation is nullable
     * 'fkey' => array(class, pk)
     *      Classname and field of the first item in the constraint that
     *      points to a PK field of a foreign model
     * 'local' => string
     *      The local field corresponding to the 'fkey' property
     */
    function processJoin(&$j) {
        $constraint = array();
        if (isset($j['reverse'])) {
            list($fmodel, $key) = explode('.', $j['reverse']);
            // NOTE: It's ok if the forein meta data is not yet inspected.
            $info = $fmodel::$meta['joins'][$key];
            if (!is_array($info['constraint']))
                throw new OrmConfigurationException(sprintf(__(
                    // `reverse` here is the reverse of an ORM relationship
                    '%s: Reverse does not specify any constraints'),
                    $j['reverse']));
            foreach ($info['constraint'] as $foreign => $local) {
                list($L,$field) = is_array($local) ? $local : explode('.', $local);
                $constraint[$field ?: $L] = array($fmodel, $foreign);
            }
            if (!isset($j['list']))
                $j['list'] = true;
            if (!isset($j['null']))
                // By default, reverse releationships can be empty lists
                $j['null'] = true;
        }
        else {
            foreach ($j['constraint'] as $local => $foreign) {
                list($class, $field) = $constraint[$local]
                    = is_array($foreign) ? $foreign : explode('.', $foreign);
            }
        }
        if ($j['list'] && !isset($j['broker'])) {
            $j['broker'] = 'InstrumentedList';
        }
        if ($j['broker'] && !class_exists($j['broker'])) {
            throw new OrmException($j['broker'] . ': List broker does not exist');
        }
        foreach ($constraint as $local => $foreign) {
            list($class, $field) = $foreign;
            if ($local[0] == "'" || $field[0] == "'" || !class_exists($class))
                continue;
            $j['fkey'] = $foreign;
            $j['local'] = $local;
        }
        $j['constraint'] = $constraint;
    }

    function addJoin($name, array $join) {
        $this->meta['joins'][$name] = $join;
        $this->processJoin($this->meta['joins'][$name]);
    }

    function offsetGet($field) {
        return $this->meta[$field];
    }
    function offsetSet($field, $what) {
        $this->meta[$field] = $what;
    }
    function offsetExists($field) {
        return isset($this->meta[$field]);
    }
    function offsetUnset($field) {
        throw new Exception('Model MetaData is immutable');
    }

    /**
     * Fetch the column names of the table used to persist instances of this
     * model in the database.
     */
    function getFieldNames() {
        if (!isset($this->fields))
            $this->fields = self::inspectFields();
        return $this->fields;
    }

    function getByPath($path) {
        if (is_string($path))
            $path = explode('__', $path);
        $root = $this;
        foreach ($path as $P) {
            list($root, ) = $root['joins'][$P]['fkey'];
            $root = $root::getMeta();
        }
        return $root;
    }

    /**
     * Create a new instance of the model, optionally hydrating it with the
     * given hash table. The constructor is not called, which leaves the
     * default constructor free to assume new object status.
     *
     * Three methods were considered, with runtime for 10000 iterations
     *   * unserialze('O:9:"ModelBase":0:{}') - 0.0671s
     *   * new ReflectionClass("ModelBase")->newInstanceWithoutConstructor()
     *      - 0.0478s
     *   * and a hybrid by cloning the reflection class instance - 0.0335s
     */
    function newInstance($props=false) {
        if (!isset($this->new)) {
            $rc = new ReflectionClass($this->model);
            $this->new = $rc->newInstanceWithoutConstructor();
        }
        $instance = clone $this->new;
        // Hydrate if props were included
        if (is_array($props)) {
            $instance->ht = $props;
        }
        return $instance;
    }

    function inspectFields() {
        if (!isset(self::$model_cache))
            self::$model_cache = function_exists('apcu_fetch');
        if (self::$model_cache) {
            $key = SECRET_SALT.GIT_VERSION."/orm/{$this['table']}";
            if ($fields = apcu_fetch($key)) {
                return $fields;
            }
        }
        $fields = DbEngine::getCompiler()->inspectTable($this['table']);
        if (self::$model_cache) {
            apcu_store($key, $fields, 1800);
        }
        return $fields;
    }

    static function flushModelCache() {
        if (self::$model_cache)
            @apcu_clear_cache('user');
    }
}

class VerySimpleModel {
    static $meta = array(
        'table' => false,
        'ordering' => false,
        'pk' => false
    );

    var $ht = array();
    var $dirty = array();
    var $__new__ = false;
    var $__deleted__ = false;
    var $__deferred__ = array();

    function __construct($row=false) {
        if (is_array($row))
            foreach ($row as $field=>$value)
                if (!is_array($value))
                    $this->set($field, $value);
        $this->__new__ = true;
    }

    function get($field, $default=false) {
        if (array_key_exists($field, $this->ht))
            return $this->ht[$field];
        elseif (($joins = static::getMeta('joins')) && isset($joins[$field])) {
            $j = $joins[$field];
            // Support instrumented lists and such
            if (isset($j['list']) && $j['list']) {
                $class = $j['fkey'][0];
                $fkey = array();
                // Localize the foreign key constraint
                foreach ($j['constraint'] as $local=>$foreign) {
                    list($_klas,$F) = $foreign;
                    $fkey[$F ?: $_klas] = ($local[0] == "'")
                        ? trim($local, "'") : $this->ht[$local];
                }
                $v = $this->ht[$field] = new $j['broker'](
                    // Send Model, [Foriegn-Field => Local-Id]
                    array($class, $fkey)
                );
                return $v;
            }
            // Support relationships
            elseif (isset($j['fkey'])) {
                $criteria = array();
                foreach ($j['constraint'] as $local => $foreign) {
                    list($klas,$F) = $foreign;
                    if (class_exists($klas))
                        $class = $klas;
                    if ($local[0] == "'") {
                        $criteria[$F] = trim($local,"'");
                    }
                    elseif ($F[0] == "'") {
                        // Does not affect the local model
                        continue;
                    }
                    else {
                        $criteria[$F] = $this->ht[$local];
                    }
                }
                try {
                    $v = $this->ht[$field] = $class::lookup($criteria);
                }
                catch (DoesNotExist $e) {
                    $v = null;
                }
                return $v;
            }
        }
        elseif (isset($this->__deferred__[$field])) {
            // Fetch deferred field
            $row = static::objects()->filter($this->getPk())
                // XXX: Seems like all the deferred fields should be fetched
                ->values_flat($field)
                ->one();
            if ($row)
                return $this->ht[$field] = $row[0];
        }
        elseif ($field == 'pk') {
            return $this->getPk();
        }

        if (isset($default))
            return $default;

        // For new objects, assume the field is NULLable
        if ($this->__new__)
            return null;

        // Check to see if the column referenced is actually valid
        if (in_array($field, static::getMeta()->getFieldNames()))
            return null;

        throw new OrmException(sprintf(__('%s: %s: Field not defined'),
            get_class($this), $field));
    }
    function __get($field) {
        return $this->get($field, null);
    }

    function getByPath($path) {
        if (is_string($path))
            $path = explode('__', $path);
        $root = $this;
        foreach ($path as $P)
            $root = $root->get($P);
        return $root;
    }

    function __isset($field) {
        return ($this->ht && array_key_exists($field, $this->ht))
            || isset(static::$meta['joins'][$field]);
    }

    function __unset($field) {
        if ($this->__isset($field))
            unset($this->ht[$field]);
        else
            unset($this->{$field});
    }

    function set($field, $value) {
        // Update of foreign-key by assignment to model instance
        $related = false;
        $joins = static::getMeta('joins');
        if (isset($joins[$field])) {
            $j = $joins[$field];
            if ($j['list'] && ($value instanceof InstrumentedList)) {
                // Magic list property
                $this->ht[$field] = $value;
                return;
            }
            if ($value === null) {
                $this->ht[$field] = $value;
                if (in_array($j['local'], static::$meta['pk'])) {
                    // Reverse relationship — don't null out local PK
                    return;
                }
                // Pass. Set local field to NULL in logic below
            }
            elseif ($value instanceof VerySimpleModel) {
                // Ensure that the model being assigned as a relationship is
                // an instance of the foreign model given in the
                // relationship, or is a super class thereof. The super
                // class case is used primary for the xxxThread classes
                // which all extend from the base Thread class.
                if (!$value instanceof $j['fkey'][0]
                    && !$value::getMeta()->isSuperClassOf($j['fkey'][0])
                ) {
                    throw new InvalidArgumentException(
                        sprintf(__('Expecting NULL or instance of %s. Got a %s instead'),
                        $j['fkey'][0], is_object($value) ? get_class($value) : gettype($value)));
                }
                // Capture the object under the object's field name
                $this->ht[$field] = $value;
                $value = $value->get($j['fkey'][1]);
                // Fall through to the standard logic below
            }
            // Capture the foreign key id value
            $field = $j['local'];
        }
        // elseif $field is in a relationship, adjust the relationship
        elseif (isset(static::$meta['foreign_keys'][$field])) {
            // meta->foreign_keys->{$field} points to the property of the
            // foreign object. For instance 'object_id' points to 'object'
            $related = static::$meta['foreign_keys'][$field];
        }
        $old = isset($this->ht[$field]) ? $this->ht[$field] : null;
        if ($old != $value) {
            // isset should not be used here, because `null` should not be
            // replaced in the dirty array
            if (!array_key_exists($field, $this->dirty))
                $this->dirty[$field] = $old;
            if ($related)
                // $related points to a foreign object propery. If setting a
                // new object_id value, the relationship to object should be
                // cleared and rebuilt
                unset($this->ht[$related]);
        }
        $this->ht[$field] = $value;
    }
    function __set($field, $value) {
        return $this->set($field, $value);
    }

    function setAll($props) {
        foreach ($props as $field=>$value)
            $this->set($field, $value);
    }

    function __onload() {}

    static function getMeta($key=false) {
        if (!static::$meta instanceof ModelMeta
            || get_called_class() != static::$meta->model
        ) {
            static::$meta = new ModelMeta(get_called_class());
        }
        $M = static::$meta;
        return ($key) ? $M->offsetGet($key) : $M;
    }

    /**
     * objects
     *
     * Retrieve a QuerySet for this model class which can be used to fetch
     * models from the connected database. Subclasses can override this
     * method to apply forced constraints on the QuerySet.
     */
    static function objects() {
        return new QuerySet(get_called_class());
    }

    /**
     * lookup
     *
     * Retrieve a record by its primary key. This method may be short
     * circuited by model caching if the record has already been loaded by
     * the database. In such a case, the database will not be consulted for
     * the model's data.
     *
     * This method can be called with an array of keyword arguments matching
     * the PK of the object or the values of the primary key. Both of these
     * usages are correct:
     *
     * >>> User::lookup(1)
     * >>> User::lookup(array('id'=>1))
     *
     * For composite primary keys and the first usage, pass the values in
     * the order they are given in the Model's 'pk' declaration in its meta
     * data.
     *
     * Parameters:
     * $criteria - (mixed) primary key for the sought model either as
     *      arguments or key/value array as the function's first argument
     *
     * Returns:
     * (Object<Model>|null) a single instance of the sought model or null if
     * no such instance exists.
     */
    static function lookup($criteria) {
        // Model::lookup(1), where >1< is the pk value
        $args = func_get_args();
        if (!is_array($criteria)) {
            $criteria = array();
            $pk = static::getMeta('pk');
            foreach ($args as $i=>$f)
                $criteria[$pk[$i]] = $f;

            // Only consult cache for PK lookup, which is assumed if the
            // values are passed as args rather than an array
            if ($cached = ModelInstanceManager::checkCache(get_called_class(),
                    $criteria))
                return $cached;
        }
        try {
            return static::objects()->filter($criteria)->one();
        }
        catch (DoesNotExist $e) {
            return null;
        }
    }

    function delete() {
        $ex = DbEngine::delete($this);
        try {
            $ex->execute();
            if ($ex->affected_rows() != 1)
                return false;

            $this->__deleted__ = true;
            Signal::send('model.deleted', $this);
        }
        catch (OrmException $e) {
            return false;
        }
        return true;
    }

    function save($refetch=false) {
        if ($this->__deleted__)
            throw new OrmException('Trying to update a deleted object');

        $pk = static::getMeta('pk');
        $wasnew = $this->__new__;

        // First, if any foreign properties of this object are connected to
        // another *new* object, then save those objects first and set the
        // local foreign key field values
        foreach (static::getMeta('joins') as $prop => $j) {
            if (isset($this->ht[$prop])
                && ($foreign = $this->ht[$prop])
                && $foreign instanceof VerySimpleModel
                && !in_array($j['local'], $pk)
                && null === $this->get($j['local'])
            ) {
                if ($foreign->__new__ && !$foreign->save())
                    return false;
                $this->set($j['local'], $foreign->get($j['fkey'][1]));
            }
        }

        // If there's nothing in the model to be saved, then we're done
        if (count($this->dirty) === 0)
            return true;

        $ex = DbEngine::save($this);
        try {
            $ex->execute();
            if ($ex->affected_rows() != 1) {
                // This doesn't really signify an error. It just means that
                // the database believes that the row did not change. For
                // inserts though, it's a deal breaker
                if ($this->__new__)
                    return false;
                else
                    // No need to reload the record if requested — the
                    // database didn't update anything
                    $refetch = false;
            }
        }
        catch (OrmException $e) {
            return false;
        }

        if ($wasnew) {
            if (count($pk) == 1)
                // XXX: Ensure AUTO_INCREMENT is set for the field
                $this->ht[$pk[0]] = $ex->insert_id();
            $this->__new__ = false;
            Signal::send('model.created', $this);
        }
        else {
            $data = array('dirty' => $this->dirty);
            Signal::send('model.updated', $this, $data);
        }
        # Refetch row from database
        if ($refetch) {
            // Preserve non database information such as list relationships
            // across the refetch
            $this->ht =
                static::objects()->filter($this->getPk())->values()->one()
                + $this->ht;
        }
        if ($wasnew) {
            // Attempt to update foreign, unsaved objects with the PK of
            // this newly created object
            foreach (static::getMeta('joins') as $prop => $j) {
                if (isset($this->ht[$prop])
                    && ($foreign = $this->ht[$prop])
                    && in_array($j['local'], $pk)
                ) {
                    if ($foreign instanceof VerySimpleModel
                        && null === $foreign->get($j['fkey'][1])
                    ) {
                        $foreign->set($j['fkey'][1], $this->get($j['local']));
                    }
                    elseif ($foreign instanceof InstrumentedList) {
                        foreach ($foreign as $item) {
                            if (null === $item->get($j['fkey'][1]))
                                $item->set($j['fkey'][1], $this->get($j['local']));
                        }
                    }
                }
            }
            $this->__onload();
        }
        $this->dirty = array();
        return true;
    }

    private function getPk() {
        $pk = array();
        foreach ($this::getMeta('pk') as $f)
            $pk[$f] = $this->ht[$f];
        return $pk;
    }
}

/**
 * AnnotatedModel
 *
 * Simple wrapper class which allows wrapping and write-protecting of
 * annotated fields retrieved from the database. Instances of this class
 * will delegate most all of the heavy lifting to the wrapped Model instance.
 */
class AnnotatedModel {

    var $model;
    var $annotations;

    function __construct($model, $annotations) {
        $this->model = $model;
        $this->annotations = $annotations;
    }

    function __get($what) {
        return $this->get($what);
    }
    function get($what) {
        if (isset($this->annotations[$what]))
            return $this->annotations[$what];
        return $this->model->get($what, null);
    }

    function __set($what, $to) {
        return $this->set($what, $to);
    }
    function set($what, $to) {
        if (isset($this->annotations[$what]))
            throw new OrmException('Annotated fields are read-only');
        return $this->model->set($what, $to);
    }

    function __isset($what) {
        return isset($this->annotations[$what]) || $this->model->__isset($what);
    }

    // Delegate everything else to the model
    function __call($what, $how) {
        return call_user_func_array(array($this->model, $what), $how);
    }
}

class SqlFunction {
    var $alias;

    function __construct($name) {
        $this->func = $name;
        $this->args = array_slice(func_get_args(), 1);
    }

    function input($what, $compiler, $model) {
        if ($what instanceof SqlFunction)
            $A = $what->toSql($compiler, $model);
        elseif ($what instanceof Q)
            $A = $compiler->compileQ($what, $model);
        else
            $A = $compiler->input($what);
        return $A;
    }

    function toSql($compiler, $model=false, $alias=false) {
        $args = array();
        foreach ($this->args as $A) {
            $args[] = $this->input($A, $compiler, $model);
        }
        return sprintf('%s(%s)%s', $this->func, implode(', ', $args),
            $alias && $this->alias ? ' AS '.$compiler->quote($this->alias) : '');
    }

    function getAlias() {
        return $this->alias;
    }
    function setAlias($alias) {
        $this->alias = $alias;
    }

    static function __callStatic($func, $args) {
        $I = new static($func);
        $I->args = $args;
        return $I;
    }

    function __call($operator, $other) {
        array_unshift($other, $this);
        return SqlExpression::__callStatic($operator, $other);
    }
}

class SqlCase extends SqlFunction {
    var $cases = array();
    var $else = false;

    static function N() {
        return new static('CASE');
    }

    function when($expr, $result) {
        if (is_array($expr))
            $expr = new Q($expr);
        $this->cases[] = array($expr, $result);
        return $this;
    }
    function otherwise($result) {
        $this->else = $result;
        return $this;
    }

    function toSql($compiler, $model=false, $alias=false) {
        $cases = array();
        foreach ($this->cases as $A) {
            list($expr, $result) = $A;
            $expr = $this->input($expr, $compiler, $model);
            $result = $this->input($result, $compiler, $model);
            $cases[] = "WHEN {$expr} THEN {$result}";
        }
        if ($this->else) {
            $else = $this->input($this->else, $compiler, $model);
            $cases[] = "ELSE {$else}";
        }
        return sprintf('CASE %s END%s', implode(' ', $cases),
            $alias && $this->alias ? ' AS '.$compiler->quote($this->alias) : '');
    }
}

class SqlExpr extends SqlFunction {
    function __construct($args) {
        $this->args = $args;
    }

    function toSql($compiler, $model=false, $alias=false) {
        $O = array();
        foreach ($this->args as $field=>$value) {
            list($field, $op) = $compiler->getField($field, $model);
            if (is_callable($op))
                $O[] = call_user_func($op, $field, $value, $model);
            else
                $O[] = sprintf($op, $field, $compiler->input($value));
        }
        return implode(' ', $O) . ($alias ? ' AS ' . $alias : '');
    }
}

class SqlExpression extends SqlFunction {
    var $operator;
    var $operands;

    function toSql($compiler, $model=false, $alias=false) {
        $O = array();
        foreach ($this->args as $operand) {
            if ($operand instanceof SqlFunction)
                $O[] = $operand->toSql($compiler, $model);
            else
                $O[] = $compiler->input($operand);
        }
        return implode(' '.$this->func.' ', $O)
            . ($alias ? ' AS '.$compiler->quote($alias) : '');
    }

    static function __callStatic($operator, $operands) {
        switch ($operator) {
            case 'minus':
                $operator = '-'; break;
            case 'plus':
                $operator = '+'; break;
            case 'times':
                $operator = '*'; break;
            case 'bitand':
                $operator = '&'; break;
            case 'bitor':
                $operator = '|'; break;
            default:
                throw new InvalidArgumentException('Invalid operator specified');
        }
        return parent::__callStatic($operator, $operands);
    }
}

class SqlInterval extends SqlFunction {
    var $type;

    function toSql($compiler, $model=false, $alias=false) {
        $A = $this->args[0];
        if ($A instanceof SqlFunction)
            $A = $A->toSql($compiler, $model);
        else
            $A = $compiler->input($A);
        return sprintf('INTERVAL %s %s',
            $A,
            $this->func)
            . ($alias ? ' AS '.$compiler->quote($alias) : '');
    }

    static function __callStatic($interval, $args) {
        if (count($args) != 1) {
            throw new InvalidArgumentException("Interval expects a single interval value");
        }
        return parent::__callStatic($interval, $args);
    }
}

class SqlField extends SqlFunction {
    var $level;

    function __construct($field, $level=0) {
        $this->field = $field;
        $this->level = $level;
    }

    function toSql($compiler, $model=false, $alias=false) {
        $L = $this->level;
        while ($L--)
            $compiler = $compiler->getParent();
        list($field) = $compiler->getField($this->field, $model);
        return $field;
    }
}

class SqlCode extends SqlFunction {
    function __construct($code) {
        $this->code = $code;
    }

    function toSql($compiler, $model=false, $alias=false) {
        return $this->code.($alias ? ' AS '.$alias : '');
    }
}

class SqlAggregate extends SqlFunction {

    var $func;
    var $expr;
    var $distinct=false;
    var $constraint=false;

    function __construct($func, $expr, $distinct=false, $constraint=false) {
        $this->func = $func;
        $this->expr = $expr;
        $this->distinct = $distinct;
        if ($constraint instanceof Q)
            $this->constraint = $constraint;
        elseif ($constraint)
            $this->constraint = new Q($constraint);
    }

    static function __callStatic($func, $args) {
        $distinct = @$args[1] ?: false;
        $constraint = @$args[2] ?: false;
        return new static($func, $args[0], $distinct, $constraint);
    }

    function toSql($compiler, $model=false, $alias=false) {
        $options = array('constraint' => $this->constraint, 'model' => true);

        // For DISTINCT, require a field specification — not a relationship
        // specification.
        $E = $this->expr;
        if ($E instanceof SqlFunction) {
            $field = $E->toSql($compiler, $model);
        }
        else {
        list($field, $rmodel) = $compiler->getField($E, $model, $options);
        if ($this->distinct) {
            $pk = false;
            $fpk  = $rmodel::getMeta('pk');
            foreach ($fpk as $f) {
                $pk |= false !== strpos($field, $f);
            }
            if (!$pk) {
                // Try and use the foriegn primary key
                if (count($fpk) == 1) {
                    list($field) = $compiler->getField(
                        $this->expr . '__' . $fpk[0],
                        $model, $options);
                }
                else {
                    throw new OrmException(
                        sprintf('%s :: %s', $rmodel, $field) .
                        ': DISTINCT aggregate expressions require specification of a single primary key field of the remote model'
                    );
                }
            }
        }
        }

        return sprintf('%s(%s%s)%s', $this->func,
            $this->distinct ? 'DISTINCT ' : '', $field,
            $alias && $this->alias ? ' AS '.$compiler->quote($this->alias) : '');
    }

    function getFieldName() {
        return strtolower(sprintf('%s__%s', $this->args[0], $this->func));
    }
}

class QuerySet implements IteratorAggregate, ArrayAccess, Serializable, Countable {
    var $model;

    var $constraints = array();
    var $path_constraints = array();
    var $ordering = array();
    var $limit = false;
    var $offset = 0;
    var $related = array();
    var $values = array();
    var $defer = array();
    var $aggregated = false;
    var $annotations = array();
    var $extra = array();
    var $distinct = array();
    var $lock = false;
    var $chain = array();
    var $options = array();

    const LOCK_EXCLUSIVE = 1;
    const LOCK_SHARED = 2;

    const ASC = 'ASC';
    const DESC = 'DESC';

    const OPT_NOSORT    = 'nosort';
    const OPT_NOCACHE   = 'nocache';

    const ITER_MODELS   = 1;
    const ITER_HASH     = 2;
    const ITER_ROW      = 3;

    var $iter = self::ITER_MODELS;

    var $compiler = 'MySqlCompiler';

    var $query;
    var $count;

    function __construct($model) {
        $this->model = $model;
    }

    function filter() {
        // Multiple arrays passes means OR
        foreach (func_get_args() as $Q) {
            $this->constraints[] = $Q instanceof Q ? $Q : new Q($Q);
        }
        return $this;
    }

    function exclude() {
        foreach (func_get_args() as $Q) {
            $this->constraints[] = $Q instanceof Q ? $Q->negate() : Q::not($Q);
        }
        return $this;
    }

    /**
     * Add a path constraint for the query. This is different from ::filter
     * in that the constraint is added to a join clause which is normally
     * built from the model meta data. The ::filter() method on the other
     * hand adds the constraint to the where clause. This is generally useful
     * for aggregate queries and left join queries where multiple rows might
     * match a filter in the where clause and would produce incorrect results.
     *
     * Example:
     * Find users with personal email hosted with gmail.
     * >>> $Q = User::objects();
     * >>> $Q->constrain(['user__emails' => new Q(['type' => 'personal']))
     * >>> $Q->filter(['user__emails__address__contains' => '@gmail.com'])
     */
    function constrain() {
        foreach (func_get_args() as $I) {
            foreach ($I as $path => $Q) {
                if (!is_array($Q) && !$Q instanceof Q) {
                    // ->constrain(array('field__path__op' => val));
                    $Q = array($path => $Q);
                    list(, $path) = SqlCompiler::splitCriteria($path);
                    $path = implode('__', $path);
                }
                $this->path_constraints[$path][] = $Q instanceof Q ? $Q : Q::all($Q);
            }
        }
        return $this;
    }

    function defer() {
        foreach (func_get_args() as $f)
            $this->defer[$f] = true;
        return $this;
    }
    function order_by($order, $direction=false) {
        if ($order === false)
            return $this->options(array('nosort' => true));

        $args = func_get_args();
        if (in_array($direction, array(self::ASC, self::DESC))) {
            $args = array($args[0]);
        }
        else
            $direction = false;

        $new = is_array($order) ?  $order : $args;
        if ($direction) {
            foreach ($new as $i=>$x) {
                $new[$i] = array($x, $direction);
            }
        }
        $this->ordering = array_merge($this->ordering, $new);
        return $this;
    }
    function getSortFields() {
        $ordering = $this->ordering;
        if ($this->extra['order_by'])
            $ordering = array_merge($ordering, $this->extra['order_by']);
        return $ordering;
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

    function isWindowed() {
        return $this->limit || $this->offset || (count($this->values) + count($this->annotations) + @count($this->extra['select'])) > 1;
    }

    function select_related() {
        $this->related = array_merge($this->related, func_get_args());
        return $this;
    }

    function extra(array $extra) {
        foreach ($extra as $section=>$info) {
            $this->extra[$section] = array_merge($this->extra[$section] ?: array(), $info);
        }
        return $this;
    }

    function distinct() {
        foreach (func_get_args() as $D)
            $this->distinct[] = $D;
        return $this;
    }

    function models() {
        $this->iter = self::ITER_MODELS;
        $this->values = $this->related = array();
        return $this;
    }

    function values() {
        foreach (func_get_args() as $A)
            $this->values[$A] = $A;
        $this->iter = self::ITER_HASH;
        // This disables related models
        $this->related = false;
        return $this;
    }

    function values_flat() {
        $this->values = func_get_args();
        $this->iter = self::ITER_ROW;
        // This disables related models
        $this->related = false;
        return $this;
    }

    function copy() {
        return clone $this;
    }

    function all() {
        return $this->getIterator()->asArray();
    }

    function first() {
        $list = $this->limit(1)->all();
        return $list[0];
    }

    /**
     * one
     *
     * Finds and returns a single model instance based on the criteria in
     * this QuerySet instance.
     *
     * Throws:
     * DoesNotExist - if no such model exists with the given criteria
     * ObjectNotUnique - if more than one model matches the given criteria
     *
     * Returns:
     * (Object<Model>) a single instance of the sought model is guarenteed.
     * If no such model or multiple models exist, an exception is thrown.
     */
    function one() {
        $list = $this->all();
        if (count($list) == 0)
            throw new DoesNotExist();
        elseif (count($list) > 1)
            throw new ObjectNotUnique('One object was expected; however '
                .'multiple objects in the database matched the query. '
                .sprintf('In fact, there are %d matching objects.', count($list))
            );
        return $list[0];
    }

    function count() {
        // Defer to the iterator if fetching already started
        if (isset($this->_iterator)) {
            return $this->_iterator->count();
        }
        elseif (isset($this->count)) {
            return $this->count;
        }
        $class = $this->compiler;
        $compiler = new $class();
        return $this->count = $compiler->compileCount($this);
    }

    function toSql($compiler, $model, $alias=false) {
        // FIXME: Force root model of the compiler to $model
        $exec = $this->getQuery(array('compiler' => get_class($compiler),
             'parent' => $compiler, 'subquery' => true));
        // Rewrite the parameter numbers so they fit the parameter numbers
        // of the current parameters of the $compiler
        $sql = preg_replace_callback("/:(\d+)/",
        function($m) use ($compiler, $exec) {
            $compiler->params[] = $exec->params[$m[1]-1];
            return ':'.count($compiler->params);
        }, $exec->sql);
        return "({$sql})".($alias ? " AS {$alias}" : '');
    }

    /**
     * exists
     *
     * Determines if there are any rows in this QuerySet. This can be
     * achieved either by evaluating a SELECT COUNT(*) query or by
     * attempting to fetch the first row from the recordset and return
     * boolean success.
     *
     * Parameters:
     * $fetch - (bool) TRUE if a compile and fetch should be attempted
     *      instead of a SELECT COUNT(*). This would be recommended if an
     *      accurate count is not required and the records would be fetched
     *      if this method returns TRUE.
     *
     * Returns:
     * (bool) TRUE if there would be at least one record in this QuerySet
     */
    function exists($fetch=false) {
        if ($fetch) {
            return (bool) $this[0];
        }
        return $this->count() > 0;
    }

    function annotate($annotations) {
        if (!is_array($annotations))
            $annotations = func_get_args();
        foreach ($annotations as $name=>$A) {
            if ($A instanceof SqlAggregate) {
                if (is_int($name))
                    $name = $A->getFieldName();
                $A->setAlias($name);
            }
            $this->annotations[$name] = $A;
        }
        return $this;
    }

    function aggregate($annotations) {
        // Aggregate works like annotate, except that it sets up values
        // fetching which will disable model creation
        $this->annotate($annotations);
        $this->values();
        // Disable other fields from being fetched
        $this->aggregated = true;
        $this->related = false;
        return $this;
    }

    function options($options) {
        // Make an array with $options as the only key
        if (!is_array($options))
            $options = array($options => 1);

        $this->options = array_merge($this->options, $options);
        return $this;
    }

    function hasOption($option) {
        return isset($this->options[$option]);
    }

    function countSelectFields() {
        $count = count($this->values) + count($this->annotations);
        if (isset($this->extra['select']))
            foreach (@$this->extra['select'] as $S)
                $count += count($S);
        return $count;
    }

    function union(QuerySet $other, $all=true) {
        // Values and values_list _must_ match for this to work
        if ($this->countSelectFields() != $other->countSelectFields())
            throw new OrmException('Union queries must have matching values counts');

        // TODO: Clear OFFSET and LIMIT in the $other query

        $this->chain[] = array($other, $all);
        return $this;
    }

    function delete() {
        $class = $this->compiler;
        $compiler = new $class();
        // XXX: Mark all in-memory cached objects as deleted
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
        unset($this->count);
    }

    function __call($name, $args) {

        if (!is_callable(array($this->getIterator(), $name)))
            throw new OrmException('Call to undefined method QuerySet::'.$name);

        return $args
            ? call_user_func_array(array($this->getIterator(), $name), $args)
            : call_user_func(array($this->getIterator(), $name));
    }

    // IteratorAggregate interface
    function getIterator($iterator=false) {
        if (!isset($this->_iterator)) {
            $class = $iterator ?: $this->getIteratorClass();
            $it = new $class($this);
            if (!$this->hasOption(self::OPT_NOCACHE)) {
                if ($this->iter == self::ITER_MODELS)
                    // Add findFirst() and such
                    $it = new ModelResultSet($it);
                else
                    $it = new CachedResultSet($it);
            }
            else {
                $it = $it->getIterator();
            }
            $this->_iterator = $it;
        }
        return $this->_iterator;
    }

    function getIteratorClass() {
        switch ($this->iter) {
        case self::ITER_MODELS:
            return 'ModelInstanceManager';
        case self::ITER_HASH:
            return 'HashArrayIterator';
        case self::ITER_ROW:
            return 'FlatArrayIterator';
        }
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
        $meta = $model::getMeta();
        $query = clone $this;
        $options += $this->options;
        if ($options['nosort'])
            $query->ordering = array();
        elseif (!$query->ordering && $meta['ordering'])
            $query->ordering = $meta['ordering'];
        if (false !== $query->related && !$query->values && $meta['select_related'])
            $query->related = $meta['select_related'];
        if (!$query->defer && $meta['defer'])
            $query->defer = $meta['defer'];

        $class = $options['compiler'] ?: $this->compiler;
        $compiler = new $class($options);
        $this->query = $compiler->compileSelect($query);

        return $this->query;
    }

    /**
     * Fetch a model class which can be used to render the QuerySet as a
     * subquery to be used as a JOIN.
     */
    function asView() {
        $unique = spl_object_hash($this);
        $classname = "QueryView{$unique}";

        if (class_exists($classname))
            return $classname;

        $class = <<<EOF
class {$classname} extends VerySimpleModel {
    static \$meta = array(
        'view' => true,
    );
    static \$queryset;

    static function getQuery(\$compiler) {
        return ' ('.static::\$queryset->getQuery().') ';
    }

    static function getSqlAddParams(\$compiler) {
        return static::\$queryset->toSql(\$compiler, self::\$queryset->model);
    }
}
EOF;
        eval($class); // Ugh
        $classname::$queryset = $this;
        return $classname;
    }

    function serialize() {
        $info = get_object_vars($this);
        unset($info['query']);
        unset($info['limit']);
        unset($info['offset']);
        unset($info['_iterator']);
        unset($info['count']);
        return serialize($info);
    }

    function unserialize($data) {
        $data = unserialize($data);
        foreach ($data as $name => $val) {
            $this->{$name} = $val;
        }
    }
}

class DoesNotExist extends Exception {}
class ObjectNotUnique extends Exception {}

class CachedResultSet
implements IteratorAggregate, Countable, ArrayAccess {
    protected $inner;
    protected $eoi = false;
    protected $cache = array();

    function __construct(IteratorAggregate $iterator) {
        $this->inner = $iterator->getIterator();
    }

    function fillTo($level) {
        while (!$this->eoi && count($this->cache) < $level) {
            if (!$this->inner->valid()) {
                $this->eoi = true;
                break;
            }
            $this->cache[] = $this->inner->current();
            $this->inner->next();
        }
    }

    function reset() {
        $this->eoi = false;
        $this->cache = array();
        // XXX: Should the inner be recreated to refetch?
        $this->inner->rewind();
    }

    function asArray() {
        $this->fillTo(PHP_INT_MAX);
        return $this->getCache();
    }

    function getCache() {
        return $this->cache;
    }

    function getIterator() {
        $this->asArray();
        return new ArrayIterator($this->cache);
    }

    function offsetExists($offset) {
        $this->fillTo($offset+1);
        return count($this->cache) > $offset;
    }
    function offsetGet($offset) {
        $this->fillTo($offset+1);
        return $this->cache[$offset];
    }
    function offsetUnset($a) {
        throw new Exception(__('QuerySet is read-only'));
    }
    function offsetSet($a, $b) {
        throw new Exception(__('QuerySet is read-only'));
    }

    function count() {
        $this->asArray();
        return count($this->cache);
    }

    /**
     * Sort the instrumented list in place. This would be useful to change the
     * sorting order of the items in the list without fetching the list from
     * the database again.
     *
     * Parameters:
     * $key - (callable|int) A callable function to produce the sort keys
     *      or one of the SORT_ constants used by the array_multisort
     *      function
     * $reverse - (bool) true if the list should be sorted descending
     *
     * Returns:
     * This instrumented list for chaining and inlining.
     */
    function sort($key=false, $reverse=false) {
        // Fetch all records into the cache
        $this->asArray();
        if (is_callable($key)) {
            array_multisort(
                array_map($key, $this->cache),
                $reverse ? SORT_DESC : SORT_ASC,
                $this->cache);
        }
        elseif ($key) {
            array_multisort($this->cache,
                $reverse ? SORT_DESC : SORT_ASC, $key);
        }
        elseif ($reverse) {
            rsort($this->cache);
        }
        else
            sort($this->cache);
        return $this;
    }

    /**
     * Reverse the list item in place. Returns this object for chaining
     */
    function reverse() {
        $this->asArray();
        array_reverse($this->cache);
        return $this;
    }
}

class ModelResultSet
extends CachedResultSet {
    /**
     * Find the first item in the current set which matches the given criteria.
     * This would be used in favor of ::filter() which might trigger another
     * database query. The criteria is intended to be quite simple and should
     * not traverse relationships which have not already been fetched.
     * Otherwise, the ::filter() or ::window() methods would provide better
     * performance.
     *
     * Example:
     * >>> $a = new User();
     * >>> $a->roles->add(Role::lookup(['name' => 'administator']));
     * >>> $a->roles->findFirst(['roles__name__startswith' => 'admin']);
     * <Role: administrator>
     */
    function findFirst($criteria) {
        $records = $this->findAll($criteria, 1);
        return @$records[0];
    }

    /**
     * Find all the items in the current set which match the given criteria.
     * This would be used in favor of ::filter() which might trigger another
     * database query. The criteria is intended to be quite simple and should
     * not traverse relationships which have not already been fetched.
     * Otherwise, the ::filter() or ::window() methods would provide better
     * performance, as they can provide results with one more trip to the
     * database.
     */
    function findAll($criteria, $limit=false) {
        $records = array();
        foreach ($this as $record) {
            $matches = true;
            foreach ($criteria as $field=>$check) {
                if (!SqlCompiler::evaluate($record, $field, $check)) {
                    $matches = false;
                    break;
                }
            }
            if ($matches)
                $records[] = $record;
            if ($limit && count($records) == $limit)
                break;
        }
        return $records;
    }
}

class ModelInstanceManager
implements IteratorAggregate {
    var $queryset;
    var $model;
    var $map;

    static $objectCache = array();

    function __construct(QuerySet $queryset) {
        $this->queryset = $queryset;
        $this->model = $queryset->model;
    }

    function cache($model) {
        $key = sprintf('%s.%s',
            $model::$meta->model, implode('.', $model->get('pk')));
        self::$objectCache[$key] = $model;
    }

    /**
     * uncache
     *
     * Drop the cached reference to the model. If the model is deleted
     * database-side. Lookups for the same model should not be short
     * circuited to retrieve the cached reference.
     */
    static function uncache($model) {
        $key = sprintf('%s.%s',
            $model::$meta->model, implode('.', $model->pk));
        unset(self::$objectCache[$key]);
    }

    static function flushCache() {
        self::$objectCache = array();
    }

    static function checkCache($modelClass, $fields) {
        $key = $modelClass::$meta->model;
        foreach ($modelClass::getMeta('pk') as $f)
            $key .= '.'.$fields[$f];
        return @self::$objectCache[$key];
    }

    /**
     * getOrBuild
     *
     * Builds a new model from the received fields or returns the model
     * already stashed in the model cache. Caching helps to ensure that
     * multiple lookups for the same model identified by primary key will
     * fetch the exact same model. Therefore, changes made to the model
     * anywhere in the project will be reflected everywhere.
     *
     * For annotated models (models build from querysets with annotations),
     * the built or cached model is wrapped in an AnnotatedModel instance.
     * The annotated fields are in the AnnotatedModel instance and the
     * database-backed fields are managed by the Model instance.
     */
    function getOrBuild($modelClass, $fields, $cache=true) {
        // Check for NULL primary key, used with related model fetching. If
        // the PK is NULL, then consider the object to also be NULL
        foreach ($modelClass::getMeta('pk') as $pkf) {
            if (!isset($fields[$pkf])) {
                return null;
            }
        }
        $annotations = $this->queryset->annotations;
        $extras = array();
        // For annotations, drop them from the $fields list and add them to
        // an $extras list. The fields passed to the root model should only
        // be the root model's fields. The annotated fields will be wrapped
        // using an AnnotatedModel instance.
        if ($annotations && $modelClass == $this->model) {
            foreach ($annotations as $name=>$A) {
                if (array_key_exists($name, $fields)) {
                    $extras[$name] = $fields[$name];
                    unset($fields[$name]);
                }
            }
        }
        // Check the cache for the model instance first
        if (!($m = self::checkCache($modelClass, $fields))) {
            // Construct and cache the object
            $m = $modelClass::$meta->newInstance($fields);
            // XXX: defer may refer to fields not in this model
            $m->__deferred__ = $this->queryset->defer;
            $m->__onload();
            if ($cache)
                $this->cache($m);
        }
        // Wrap annotations in an AnnotatedModel
        if ($extras) {
            $m = new AnnotatedModel($m, $extras);
        }
        // TODO: If the model has deferred fields which are in $fields,
        // those can be resolved here
        return $m;
    }

    /**
     * buildModel
     *
     * This method builds the model including related models from the record
     * received. For related recordsets, a $map should be setup inside this
     * object prior to using this method. The $map is assumed to have this
     * configuration:
     *
     * array(array(<fieldNames>, <modelClass>, <relativePath>))
     *
     * Where $modelClass is the name of the foreign (with respect to the
     * root model ($this->model), $fieldNames is the number and names of
     * fields in the row for this model, $relativePath is the path that
     * describes the relationship between the root model and this model,
     * 'user__account' for instance.
     */
    function buildModel($row, $cache=true) {
        // TODO: Traverse to foreign keys
        if ($this->map) {
            if ($this->model != $this->map[0][1])
                throw new OrmException('Internal select_related error');

            $offset = 0;
            foreach ($this->map as $info) {
                @list($fields, $model_class, $path) = $info;
                $values = array_slice($row, $offset, count($fields));
                $record = array_combine($fields, $values);
                if (!$path) {
                    // Build the root model
                    $model = $this->getOrBuild($this->model, $record, $cache);
                }
                elseif ($model) {
                    $i = 0;
                    // Traverse the declared path and link the related model
                    $tail = array_pop($path);
                    $m = $model;
                    foreach ($path as $field) {
                        if (!($m = $m->get($field)))
                            break;
                    }
                    if ($m) {
                        // Only apply cache setting to the root model.
                        // Reference models should use caching
                        $m->set($tail, $this->getOrBuild($model_class, $record, $cache));
                    }
                }
                $offset += count($fields);
            }
        }
        else {
            $model = $this->getOrBuild($this->model, $row, $cache);
        }
        return $model;
    }

    function getIterator() {
        $this->resource = $this->queryset->getQuery();
        $this->map = $this->resource->getMap();
        $cache = !$this->queryset->hasOption(QuerySet::OPT_NOCACHE);
        $this->resource->setBuffered($cache);
        $func = ($this->map) ? 'getRow' : 'getArray';
        $func = array($this->resource, $func);

        return new CallbackSimpleIterator(function() use ($func, $cache) {
            global $StopIteration;

            if ($row = $func())
                return $this->buildModel($row, $cache);

            $this->resource->close();
            throw $StopIteration;
        });
    }
}

class CallbackSimpleIterator
implements Iterator {
    var $current;
    var $eoi;
    var $callback;
    var $key = -1;

    function __construct($callback) {
        assert(is_callable($callback));
        $this->callback = $callback;
    }

    function rewind() {
        $this->eoi = false;
        $this->next();
    }

    function key() {
        return $this->key;
    }

    function valid() {
        if (!isset($this->eoi))
            $this->rewind();
        return !$this->eoi;
    }

    function current() {
        if ($this->eoi) return false;
        return $this->current;
    }

    function next() {
        try {
            $cbk = $this->callback;
            $this->current = $cbk();
            $this->key++;
        }
        catch (StopIteration $x) {
            $this->eoi = true;
        }
    }
}

// Use a global variable, as constructing exceptions is expensive
class StopIteration extends Exception {}
$StopIteration = new StopIteration();

class FlatArrayIterator
implements IteratorAggregate {
    var $queryset;
    var $resource;

    function __construct(QuerySet $queryset) {
        $this->queryset = $queryset;
    }

    function getIterator() {
        $this->resource = $this->queryset->getQuery();
        return new CallbackSimpleIterator(function() {
            global $StopIteration;

            if ($row = $this->resource->getRow())
                return $row;

            $this->resource->close();
            throw $StopIteration;
        });
    }
}

class HashArrayIterator
implements IteratorAggregate {
    var $queryset;
    var $resource;

    function __construct(QuerySet $queryset) {
        $this->queryset = $queryset;
    }

    function getIterator() {
        $this->resource = $this->queryset->getQuery();
        return new CallbackSimpleIterator(function() {
            global $StopIteration;

            if ($row = $this->resource->getArray())
                return $row;

            $this->resource->close();
            throw $StopIteration;
        });
    }
}

class InstrumentedList
extends ModelResultSet {
    var $key;

    function __construct($fkey, $queryset=false) {
        list($model, $this->key) = $fkey;
        if (!$queryset) {
            $queryset = $model::objects()->filter($this->key);
            if ($related = $model::getMeta('select_related'))
                $queryset->select_related($related);
        }
        parent::__construct(new ModelInstanceManager($queryset));
        $this->model = $model;
        $this->queryset = $queryset;
    }

    function add($object, $at=false) {
        if (!$object || !$object instanceof $this->model)
            throw new Exception(sprintf(
                'Attempting to add invalid object to list. Expected <%s>, but got <%s>',
                $this->model,
                get_class($object)
            ));

        foreach ($this->key as $field=>$value)
            $object->set($field, $value);

        if (!$object->__new__)
            $object->save();

        if ($at !== false)
            $this->cache[$at] = $object;
        else
            $this->cache[] = $object;

        return $object;
    }
    function remove($object, $delete=true) {
        if ($delete)
            $object->delete();
        else
            foreach ($this->key as $field=>$value)
                $object->set($field, null);
    }

    /**
     * Slight edit to the standard iteration method which will skip deleted
     * items.
     */
    function getIterator() {
        return new CallbackFilterIterator(parent::getIterator(),
            function($i) { return !$i->__deleted__; }
        );
    }

    /**
     * Reduce the list to a subset using a simply key/value constraint. New
     * items added to the subset will have the constraint automatically
     * added to all new items.
     */
    function window($constraint) {
        $model = $this->model;
        $fields = $model::getMeta()->getFieldNames();
        $key = $this->key;
        foreach ($constraint as $field=>$value) {
            if (!is_string($field) || false === in_array($field, $fields))
                throw new OrmException('InstrumentedList windowing must be performed on local fields only');
            $key[$field] = $value;
        }
        return new static(array($this->model, $key), $this->filter($constraint));
    }

    // Save all changes made to any list items
    function saveAll() {
        foreach ($this as $I)
            if (!$I->save())
                return false;
        return true;
    }

    // QuerySet delegates
    function count() {
        return $this->objects()->count();
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

    // QuerySet overriedes
    function __call($what, $how) {
        return call_user_func_array(array($this->objects(), $what), $how);
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
        if ($options['subquery'])
            $this->alias_num += 150;
    }

    function getParent() {
        return $this->options['parent'];
    }

    /**
     * Split a criteria item into the identifying pieces: path, field, and
     * operator.
     */
    static function splitCriteria($criteria) {
        static $operators = array(
            'exact' => 1, 'isnull' => 1,
            'gt' => 1, 'lt' => 1, 'gte' => 1, 'lte' => 1, 'range' => 1,
            'contains' => 1, 'like' => 1, 'startswith' => 1, 'endswith' => 1, 'regex' => 1,
            'in' => 1, 'intersect' => 1,
            'hasbit' => 1,
        );
        $path = explode('__', $criteria);
        if (!isset($options['table'])) {
            $field = array_pop($path);
            if (isset($operators[$field])) {
                $operator = $field;
                $field = array_pop($path);
            }
        }
        return array($field, $path, $operator ?: 'exact');
    }

    /**
     * Check if the values match given the operator.
     *
     * Throws:
     * OrmException - if $operator is not supported
     */
    static function evaluate($record, $field, $check) {
        static $ops; if (!isset($ops)) { $ops = array(
            'exact' => function($a, $b) { return is_string($a) ? strcasecmp($a, $b) == 0 : $a == $b; },
            'isnull' => function($a, $b) { return is_null($a) == $b; },
            'gt' => function($a, $b) { return $a > $b; },
            'gte' => function($a, $b) { return $a >= $b; },
            'lt' => function($a, $b) { return $a < $b; },
            'lte' => function($a, $b) { return $a <= $b; },
            'contains' => function($a, $b) { return stripos($a, $b) !== false; },
            'startswith' => function($a, $b) { return stripos($a, $b) === 0; },
            'hasbit' => function($a, $b) { return $a & $b == $b; },
        ); }
        list($field, $path, $operator) = self::splitCriteria($field);
        if (!isset($ops[$operator]))
            throw new OrmException($operator.': Unsupported operator');

        if ($path)
            $record = $record->getByPath($path);
        // TODO: Support Q expressions
        return $ops[$operator]($record->get($field), $check);
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
     *
     * Parameters:
     * $field - (string) name of the field to join
     * $model - (VerySimpleModel) root model for references in the $field
     *      parameter
     * $options - (array) extra options for the compiler
     *      'table' => return the table alias rather than the field-name
     *      'model' => return the target model class rather than the operator
     *      'constraint' => extra constraint for join clause
     *
     * Returns:
     * (mixed) Usually array<field-name, operator> where field-name is the
     * name of the field in the destination model, and operator is the
     * requestion comparison method.
     */
    function getField($field, $model, $options=array()) {
        // Break apart the field descriptor by __ (double-underbars). The
        // first part is assumed to be the root field in the given model.
        // The parts after each of the __ pieces are links to other tables.
        // The last item (after the last __) is allowed to be an operator
        // specifiction.
        list($field, $parts, $op) = static::splitCriteria($field);
        $operator = static::$operators[$op];
        $path = '';
        $rootModel = $model;

        // Call pushJoin for each segment in the join path. A new JOIN
        // fragment will need to be emitted and/or cached
        $joins = array();
        $null = false;
        $push = function($p, $model) use (&$joins, &$path, &$null) {
            $J = $model::getMeta('joins');
            if (!($info = $J[$p])) {
                throw new OrmException(sprintf(
                   'Model `%s` does not have a relation called `%s`',
                    $model, $p));
            }
            // Propogate LEFT joins through other joins. That is, if a
            // multi-join expression is used, the first LEFT join should
            // result in further joins also being LEFT
            if (isset($info['null']))
                $null = $null || $info['null'];
            $info['null'] = $null;
            $crumb = $path;
            $path = ($path) ? "{$path}__{$p}" : $p;
            $joins[] = array($crumb, $path, $model, $info);
            // Roll to foreign model
            return $info['fkey'];
        };

        foreach ($parts as $p) {
            list($model) = $push($p, $model);
        }

        // If comparing a relationship, join the foreign table
        // This is a comparison with a relationship — use the foreign key
        $J = $model::getMeta('joins');
        if (isset($J[$field])) {
            list($model, $field) = $push($field, $model);
        }

        // Apply the joins list to $this->pushJoin
        $last = count($joins) - 1;
        $constraint = false;
        foreach ($joins as $i=>$A) {
            // Add the conststraint as the last arg to the last join
            if ($i == $last)
                $constraint = $options['constraint'];
            $alias = $this->pushJoin($A[0], $A[1], $A[2], $A[3], $constraint);
        }

        if (!isset($alias)) {
            // Determine the alias for the root model table
            $alias = (isset($this->joins['']))
                ? $this->joins['']['alias']
                : $this->quote($rootModel::getMeta('table'));
        }

        if (isset($options['table']) && $options['table'])
            $field = $alias;
        elseif (isset($this->annotations[$field]))
            $field = $this->annotations[$field];
        elseif ($alias)
            $field = $alias.'.'.$this->quote($field);
        else
            $field = $this->quote($field);

        if (isset($options['model']) && $options['model'])
            $operator = $model;
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
    function pushJoin($tip, $path, $model, $info, $constraint=false) {
        // TODO: Build the join statement fragment and return the table
        // alias. The table alias will be useful where the join is used in
        // the WHERE and ORDER BY clauses

        // If the join already exists for the statement-being-compiled, just
        // return the alias being used.
        if (!$constraint && isset($this->joins[$path]))
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
        $T = array('alias' => $alias);
        $this->joins[$path] = $T;
        $this->joins[$path]['sql'] = $this->compileJoin($tip, $model, $alias, $info, $constraint);
        return $alias;
    }

    /**
     * compileQ
     *
     * Build a constraint represented in an arbitrarily nested Q instance.
     * The placement of the compiled constraint is also considered and
     * represented in the resulting CompiledExpression instance.
     *
     * Parameters:
     * $Q - (Q) constraint represented in a Q instance
     * $model - (VerySimpleModel) root model for all the field references in
     *      the Q instance
     * $slot - (int) slot for inputs to be placed. Useful to differenciate
     *      inputs placed in the joins and where clauses for SQL engines
     *      which do not support named parameters.
     *
     * Returns:
     * (CompiledExpression) object containing the compiled expression (with
     * AND, OR, and NOT operators added). Furthermore, the $type attribute
     * of the CompiledExpression will allow the compiler to place the
     * constraint properly in the WHERE or HAVING clause appropriately.
     */
    function compileQ(Q $Q, $model, $slot=false) {
        $filter = array();
        $type = CompiledExpression::TYPE_WHERE;
        foreach ($Q->constraints as $field=>$value) {
            // Handle nested constraints
            if ($value instanceof Q) {
                $filter[] = $T = $this->compileQ($value, $model, $slot);
                // Bubble up HAVING constraints
                if ($T instanceof CompiledExpression
                        && $T->type == CompiledExpression::TYPE_HAVING)
                    $type = $T->type;
            }
            // Handle relationship comparisons with model objects
            elseif ($value instanceof VerySimpleModel) {
                $criteria = array();
                // Avoid a join if possible. Use the local side of the
                // relationship
                if (count($value->pk) === 1) {
                    $path = explode('__', $field);
                    $relationship = array_pop($path);
                    $lmodel = $model::getMeta()->getByPath($path);
                    $local = $lmodel['joins'][$relationship]['local'];
                    $path = $path ? (implode('__', $path) . '__') : '';
                    foreach ($value->pk as $v) {
                        $criteria["{$path}{$local}"] = $v;
                   }
                }
                else {
                    foreach ($value->pk as $f=>$v) {
                        $criteria["{$field}__{$f}"] = $v;
                    }
                }
                $filter[] = $this->compileQ(new Q($criteria), $model, $slot);
            }
            // Handle simple field = <value> constraints
            else {
                list($field, $op) = $this->getField($field, $model);
                if ($field instanceof SqlAggregate) {
                    // This constraint has to go in the HAVING clause
                    $field = $field->toSql($this, $model);
                    $type = CompiledExpression::TYPE_HAVING;
                }
                if ($value === null)
                    $filter[] = sprintf('%s IS NULL', $field);
                elseif ($value instanceof SqlField)
                    $filter[] = sprintf($op, $field, $value->toSql($this, $model));
                // Allow operators to be callable rather than sprintf
                // strings
                elseif (is_callable($op))
                    $filter[] = call_user_func($op, $field, $value, $model);
                else
                    $filter[] = sprintf($op, $field, $this->input($value));
            }
        }
        $glue = $Q->isOred() ? ' OR ' : ' AND ';
        $clause = implode($glue, $filter);
        if (count($filter) > 1)
            $clause = '(' . $clause . ')';
        if ($Q->isNegated())
            $clause = 'NOT '.$clause;
        return new CompiledExpression($clause, $type);
    }

    function compileConstraints($where, $model) {
        $constraints = array();
        foreach ($where as $Q) {
            $constraints[] = $this->compileQ($Q, $model);
        }
        return $constraints;
    }

    function getParams() {
        return $this->params;
    }

    function getJoins($queryset) {
        $sql = '';
        foreach ($this->joins as $path => $j) {
            if (!$j['sql'])
                continue;
            list($base, $constraints) = $j['sql'];
            // Add in path-specific constraints, if any
            if (isset($queryset->path_constraints[$path])) {
                foreach ($queryset->path_constraints[$path] as $Q) {
                    $constraints[] = $this->compileQ($Q, $queryset->model);
                }
            }
            $sql .= $base;
            if ($constraints)
                $sql .= ' ON ('.implode(' AND ', $constraints).')';
        }
        // Add extra items from QuerySet
        if (isset($queryset->extra['tables'])) {
            foreach ($queryset->extra['tables'] as $S) {
                $join = ' JOIN ';
                // Left joins require an ON () clause
                if ($lastparen = strrpos($S, '(')) {
                    if (preg_match('/\bon\b/i', substr($S, $lastparen - 4, 4)))
                        $join = ' LEFT' . $join;
                }
                $sql .= $join.$S;
            }
        }
        return $sql;
    }

    function nextAlias() {
        // Use alias A1-A9,B1-B9,...
        $alias = chr(65 + (int)($this->alias_num / 9)) . $this->alias_num % 9;
        $this->alias_num++;
        return $alias;
    }
}

class CompiledExpression /* extends SplString */ {
    const TYPE_WHERE =   0x0001;
    const TYPE_HAVING =  0x0002;

    var $text = '';

    function __construct($clause, $type=self::TYPE_WHERE) {
        $this->text = $clause;
        $this->type = $type;
    }

    function __toString() {
        return $this->text;
    }
}

class DbEngine {

    static $compiler = 'MySqlCompiler';

    function __construct($info) {
    }

    function connect() {
    }

    // Gets a compiler compatible with this database engine that can compile
    // and execute a queryset or DML request.
    static function getCompiler() {
        $class = static::$compiler;
        return new $class();
    }

    static function delete(VerySimpleModel $model) {
        ModelInstanceManager::uncache($model);
        return static::getCompiler()->compileDelete($model);
    }

    static function save(VerySimpleModel $model) {
        $compiler = static::getCompiler();
        if ($model->__new__)
            return $compiler->compileInsert($model);
        else
            return $compiler->compileUpdate($model);
    }
}

class MySqlCompiler extends SqlCompiler {

    static $operators = array(
        'exact' => '%1$s = %2$s',
        'contains' => array('self', '__contains'),
        'startswith' => array('self', '__startswith'),
        'endswith' => array('self', '__endswith'),
        'gt' => '%1$s > %2$s',
        'lt' => '%1$s < %2$s',
        'gte' => '%1$s >= %2$s',
        'lte' => '%1$s <= %2$s',
        'range' => array('self', '__range'),
        'isnull' => array('self', '__isnull'),
        'like' => '%1$s LIKE %2$s',
        'hasbit' => '%1$s & %2$s != 0',
        'in' => array('self', '__in'),
        'intersect' => array('self', '__find_in_set'),
        'regex' => array('self', '__regex'),
    );

    // Thanks, http://stackoverflow.com/a/3683868
    function like_escape($what, $e='\\') {
        return str_replace(array($e, '%', '_'), array($e.$e, $e.'%', $e.'_'), $what);
    }

    function __contains($a, $b) {
        # {%a} like %{$b}%
        # Escape $b
        $b = $this->like_escape($b);
        return sprintf('%s LIKE %s', $a, $this->input("%$b%"));
    }
    function __startswith($a, $b) {
        $b = $this->like_escape($b);
        return sprintf('%s LIKE %s', $a, $this->input("$b%"));
    }
    function __endswith($a, $b) {
        $b = $this->like_escape($b);
        return sprintf('%s LIKE %s', $a, $this->input("%$b"));
    }

    function __in($a, $b) {
        if (is_array($b)) {
            $vals = array_map(array($this, 'input'), $b);
            $b = '('.implode(', ', $vals).')';
        }
        // MySQL is almost always faster with a join. Use one if possible
        // MySQL doesn't support LIMIT or OFFSET in subqueries. Instead, add
        // the query as a JOIN and add the join constraint into the WHERE
        // clause.
        elseif ($b instanceof QuerySet
            && ($b->isWindowed() || $b->countSelectFields() > 1 || $b->chain)
        ) {
            $f1 = $b->values[0];
            $view = $b->asView();
            $alias = $this->pushJoin($view, $a, $view, array('constraint'=>array()));
            return sprintf('%s = %s.%s', $a, $alias, $this->quote($f1));
        }
        else {
            $b = $this->input($b);
        }
        return sprintf('%s IN %s', $a, $b);
    }

    function __isnull($a, $b) {
        return $b
            ? sprintf('%s IS NULL', $a)
            : sprintf('%s IS NOT NULL', $a);
    }

    function __find_in_set($a, $b) {
        if (is_array($b)) {
            $sql = array();
            foreach (array_map(array($this, 'input'), $b) as $b) {
                $sql[] = sprintf('FIND_IN_SET(%s, %s)', $b, $a);
            }
            $parens = count($sql) > 1;
            $sql = implode(' OR ', $sql);
            return $parens ? ('('.$sql.')') : $sql;
        }
        return sprintf('FIND_IN_SET(%s, %s)', $b, $a);
    }

    function __regex($a, $b) {
        // Strip slashes and options
        if ($b[0] == '/')
            $b = preg_replace('`/[^/]*$`', '', substr($b, 1));
        return sprintf('%s REGEXP %s', $a, $this->input($b));
    }

    function __range($a, $b) {
        // XXX: Crash if $b is not array of two items
        return sprintf('%s BETWEEN %s AND %s', $a, $b[0], $b[1]);
    }

    function compileJoin($tip, $model, $alias, $info, $extra=false) {
        $constraints = array();
        $join = ' JOIN ';
        if (isset($info['null']) && $info['null'])
            $join = ' LEFT'.$join;
        if (isset($this->joins[$tip]))
            $table = $this->joins[$tip]['alias'];
        else
            $table = $this->quote($model::getMeta('table'));
        foreach ($info['constraint'] as $local => $foreign) {
            list($rmodel, $right) = $foreign;
            // Support a constant constraint with
            // "'constant'" => "Model.field_name"
            if ($local[0] == "'") {
                $constraints[] = sprintf("%s.%s = %s",
                    $alias, $this->quote($right),
                    $this->input(trim($local, '\'"'))
                );
            }
            // Support local constraint
            // field_name => "'constant'"
            elseif ($rmodel[0] == "'" && !$right) {
                $constraints[] = sprintf("%s.%s = %s",
                    $table, $this->quote($local),
                    $this->input(trim($rmodel, '\'"'))
                );
            }
            else {
                $constraints[] = sprintf("%s.%s = %s.%s",
                    $table, $this->quote($local), $alias,
                    $this->quote($right)
                );
            }
        }
        // Support extra join constraints
        if ($extra instanceof Q) {
            $constraints[] = $this->compileQ($extra, $model);
        }
        if (!isset($rmodel))
            $rmodel = $model;
        // Support inline views
        $rmeta = $rmodel::getMeta();
        $table = ($rmeta['view'])
            // XXX: Support parameters from the nested query
            ? $rmodel::getSqlAddParams($this)
            : $this->quote($rmeta['table']);
        $base = "{$join}{$table} {$alias}";
        return array($base, $constraints);
    }

    /**
     * input
     *
     * Generate a parameterized input for a database query.
     *
     * Parameters:
     * $what - (mixed) value to be sent to the database. No escaping is
     *      necessary. Pass a raw value here.
     *
     * Returns:
     * (string) token to be placed into the compiled SQL statement. This
     * is a colon followed by a number
     */
    function input($what, $model=false) {
        if ($what instanceof QuerySet) {
            $q = $what->getQuery(array('nosort'=>!($what->limit || $what->offset)));
            // Rewrite the parameter numbers so they fit the parameter numbers
            // of the current parameters of the $compiler
            $self = $this;
            $sql = preg_replace_callback("/:(\d+)/",
            function($m) use ($self, $q) {
                $self->params[] = $q->params[$m[1]-1];
                return ':'.count($self->params);
            }, $q->sql);
            return "({$sql})";
        }
        elseif ($what instanceof SqlFunction) {
            return $what->toSql($this, $model);
        }
        elseif (!isset($what)) {
            return 'NULL';
        }
        else {
            $this->params[] = $what;
            return ':'.(count($this->params));
        }
    }

    function quote($what) {
        return sprintf("`%s`", str_replace("`", "``", $what));
    }

    /**
     * getWhereClause
     *
     * This builds the WHERE ... part of a DML statement. This should be
     * called before ::getJoins(), because it may add joins into the
     * statement based on the relationships used in the where clause
     */
    protected function getWhereHavingClause($queryset) {
        $model = $queryset->model;
        $constraints = $this->compileConstraints($queryset->constraints, $model);
        $where = $having = array();
        foreach ($constraints as $C) {
            if ($C->type == CompiledExpression::TYPE_WHERE)
                $where[] = $C;
            else
                $having[] = $C;
        }
        if (isset($queryset->extra['where'])) {
            foreach ($queryset->extra['where'] as $S) {
                $where[] = "($S)";
            }
        }
        if ($where)
            $where = ' WHERE '.implode(' AND ', $where);
        if ($having)
            $having = ' HAVING '.implode(' AND ', $having);
        return array($where ?: '', $having ?: '');
    }

    function compileCount($queryset) {
        $q = clone $queryset;
        // Drop extra fields from the queryset
        $q->related = $q->anotations = false;
        $model = $q->model;
        $q->values = $model::getMeta('pk');
        $exec = $q->getQuery(array('nosort' => true));
        $exec->sql = 'SELECT COUNT(*) FROM ('.$exec->sql.') __';
        $row = $exec->getRow();
        return is_array($row) ? (int) $row[0] : null;
    }

    function compileSelect($queryset) {
        $model = $queryset->model;
        // Use an alias for the root model table
        $this->joins[''] = array('alias' => ($rootAlias = $this->nextAlias()));

        // Compile the WHERE clause
        $this->annotations = $queryset->annotations ?: array();
        list($where, $having) = $this->getWhereHavingClause($queryset);

        // Compile the ORDER BY clause
        $sort = '';
        if ($columns = $queryset->getSortFields()) {
            $orders = array();
            foreach ($columns as $sort) {
                $dir = 'ASC';
                if (is_array($sort)) {
                    list($sort, $dir) = $sort;
                }
                if ($sort instanceof SqlFunction) {
                    $field = $sort->toSql($this, $model);
                }
                else {
                    if ($sort[0] == '-') {
                        $dir = 'DESC';
                        $sort = substr($sort, 1);
                    }
                    // If the field is already an annotation, then don't
                    // compile the annotation again below. It's included in
                    // the select clause, which is sufficient
                    if (isset($this->annotations[$sort]))
                        $field = $this->quote($sort);
                    else
                        list($field) = $this->getField($sort, $model);
                }
                if ($field instanceof SqlFunction)
                    $field = $field->toSql($this, $model);
                // TODO: Throw exception if $field can be indentified as
                //       invalid

                $orders[] = "{$field} {$dir}";
            }
            $sort = ' ORDER BY '.implode(', ', $orders);
        }

        // Compile the field listing
        $fields = $group_by = array();
        $meta = $model::getMeta();
        $table = $this->quote($meta['table']).' '.$rootAlias;
        // Handle related tables
        if ($queryset->related) {
            $count = 0;
            $fieldMap = $theseFields = array();
            $defer = $queryset->defer ?: array();
            // Add local fields first
            foreach ($meta->getFieldNames() as $f) {
                // Handle deferreds
                if (isset($defer[$f]))
                    continue;
                $fields[$rootAlias . '.' . $this->quote($f)] = true;
                $theseFields[] = $f;
            }
            $fieldMap[] = array($theseFields, $model);
            // Add the JOINs to this query
            foreach ($queryset->related as $sr) {
                // XXX: Sort related by the paths so that the shortest paths
                //      are resolved first when building out the models.
                $full_path = '';
                $parts = array();
                // Track each model traversal and fetch data for each of the
                // models in the path of the related table
                foreach (explode('__', $sr) as $field) {
                    $full_path .= $field;
                    $parts[] = $field;
                    $theseFields = array();
                    list($alias, $fmodel) = $this->getField($full_path, $model,
                        array('table'=>true, 'model'=>true));
                    foreach ($fmodel::getMeta()->getFieldNames() as $f) {
                        // Handle deferreds
                        if (isset($defer[$sr . '__' . $f]))
                            continue;
                        elseif (isset($fields[$alias.'.'.$this->quote($f)]))
                            continue;
                        $fields[$alias . '.' . $this->quote($f)] = true;
                        $theseFields[] = $f;
                    }
                    if ($theseFields) {
                        $fieldMap[] = array($theseFields, $fmodel, $parts);
                    }
                    $full_path .= '__';
                }
            }
        }
        // Support retrieving only a list of values rather than a model
        elseif ($queryset->values) {
            foreach ($queryset->values as $alias=>$v) {
                list($f) = $this->getField($v, $model);
                $unaliased = $f;
                if ($f instanceof SqlFunction)
                    $fields[$f->toSql($this, $model, $alias)] = true;
                else {
                    if (!is_int($alias))
                        $f .= ' AS '.$this->quote($alias);
                    $fields[$f] = true;
                }
                // If there are annotations, add in these fields to the
                // GROUP BY clause
                if ($queryset->annotations && !$queryset->distinct)
                    $group_by[] = $unaliased;
            }
        }
        // Simple selection from one table
        elseif (!$queryset->aggregated) {
            if ($queryset->defer) {
                foreach ($meta->getFieldNames() as $f) {
                    if (isset($queryset->defer[$f]))
                        continue;
                    $fields[$rootAlias .'.'. $this->quote($f)] = true;
                }
            }
            else {
                $fields[$rootAlias.'.*'] = true;
            }
        }
        $fields = array_keys($fields);
        // Add in annotations
        if ($queryset->annotations) {
            foreach ($queryset->annotations as $alias=>$A) {
                // The root model will receive the annotations, add in the
                // annotation after the root model's fields
                $T = $A->toSql($this, $model, $alias);
                if ($fieldMap) {
                    array_splice($fields, count($fieldMap[0][0]), 0, array($T));
                    $fieldMap[0][0][] = $alias;
                }
                else {
                    // No field map — just add to end of field list
                    $fields[] = $T;
                }
            }
            // If no group by has been set yet, use the root model pk
            if (!$group_by && !$queryset->aggregated && !$queryset->distinct) {
                foreach ($meta['pk'] as $pk)
                    $group_by[] = $rootAlias .'.'. $pk;
            }
        }
        // Add in SELECT extras
        if (isset($queryset->extra['select'])) {
            foreach ($queryset->extra['select'] as $name=>$expr) {
                if ($expr instanceof SqlFunction)
                    $expr = $expr->toSql($this, false, $name);
                else
                    $expr = sprintf('%s AS %s', $expr, $this->quote($name));
                $fields[] = $expr;
            }
        }
        if (isset($queryset->distinct)) {
            foreach ($queryset->distinct as $d)
                list($group_by[]) = $this->getField($d, $model);
        }
        $group_by = $group_by ? ' GROUP BY '.implode(', ', $group_by) : '';

        $joins = $this->getJoins($queryset);

        $sql = 'SELECT '.implode(', ', $fields).' FROM '
            .$table.$joins.$where.$group_by.$having.$sort;
        // UNIONS
        if ($queryset->chain) {
            // If the main query is sorted, it will need parentheses
            if ($parens = (bool) $sort)
                $sql = "($sql)";
            foreach ($queryset->chain as $qs) {
                list($qs, $all) = $qs;
                $q = $qs->getQuery(array('nosort' => true));
                // Rewrite the parameter numbers so they fit the parameter numbers
                // of the current parameters of the $compiler
                $self = $this;
                $S = preg_replace_callback("/:(\d+)/",
                function($m) use ($self, $q) {
                    $self->params[] = $q->params[$m[1]-1];
                    return ':'.count($self->params);
                }, $q->sql);
                // Wrap unions in parentheses if they are windowed or sorted
                if ($parens || $qs->isWindowed() || count($qs->getSortFields()))
                    $S = "($S)";
                $sql .= ' UNION '.($all ? 'ALL ' : '').$S;
            }
        }

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

        return new MysqlExecutor($sql, $this->params, $fieldMap);
    }

    function __compileUpdateSet($model, array $pk) {
        $fields = array();
        foreach ($model->dirty as $field=>$old) {
            if ($model->__new__ or !in_array($field, $pk)) {
                $fields[] = sprintf('%s = %s', $this->quote($field),
                    $this->input($model->get($field)));
            }
        }
        return ' SET '.implode(', ', $fields);
    }

    function compileUpdate(VerySimpleModel $model) {
        $pk = $model::getMeta('pk');
        $sql = 'UPDATE '.$this->quote($model::getMeta('table'));
        $sql .= $this->__compileUpdateSet($model, $pk);
        // Support PK updates
        $criteria = array();
        foreach ($pk as $f) {
            $criteria[$f] = @$model->dirty[$f] ?: $model->get($f);
        }
        $sql .= ' WHERE '.$this->compileQ(new Q($criteria), $model);
        $sql .= ' LIMIT 1';

        return new MySqlExecutor($sql, $this->params);
    }

    function compileInsert(VerySimpleModel $model) {
        $pk = $model::getMeta('pk');
        $sql = 'INSERT INTO '.$this->quote($model::getMeta('table'));
        $sql .= $this->__compileUpdateSet($model, $pk);

        return new MySqlExecutor($sql, $this->params);
    }

    function compileDelete($model) {
        $table = $model::getMeta('table');

        $where = ' WHERE '.implode(' AND ',
            $this->compileConstraints(array(new Q($model->pk)), $model));
        $sql = 'DELETE FROM '.$this->quote($table).$where.' LIMIT 1';
        return new MySqlExecutor($sql, $this->params);
    }

    function compileBulkDelete($queryset) {
        $model = $queryset->model;
        $table = $model::getMeta('table');
        list($where, $having) = $this->getWhereHavingClause($queryset);
        $joins = $this->getJoins($queryset);
        $sql = 'DELETE '.$this->quote($table).'.* FROM '
            .$this->quote($table).$joins.$where;
        return new MysqlExecutor($sql, $this->params);
    }

    function compileBulkUpdate($queryset, array $what) {
        $model = $queryset->model;
        $table = $model::getMeta('table');
        $set = array();
        foreach ($what as $field=>$value)
            $set[] = sprintf('%s = %s', $this->quote($field), $this->input($value, $model));
        $set = implode(', ', $set);
        list($where, $having) = $this->getWhereHavingClause($queryset);
        $joins = $this->getJoins($queryset);
        $sql = 'UPDATE '.$this->quote($table).$joins.' SET '.$set.$where;
        return new MysqlExecutor($sql, $this->params);
    }

    // Returns meta data about the table used to build queries
    function inspectTable($table) {
        static $cache = array();

        // XXX: Assuming schema is not changing — add support to track
        //      current schema
        if (isset($cache[$table]))
            return $cache[$table];

        $sql = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS '
            .'WHERE TABLE_NAME = '.db_input($table).' AND TABLE_SCHEMA = DATABASE() '
            .'ORDER BY ORDINAL_POSITION';
        $ex = new MysqlExecutor($sql, array());
        $columns = array();
        while (list($column) = $ex->getRow()) {
            $columns[] = $column;
        }
        return $cache[$table] = $columns;
    }
}

class MySqlPreparedExecutor {

    var $stmt;
    var $fields = array();
    var $sql;
    var $params;
    // Array of [count, model] values representing which fields in the
    // result set go with witch model.  Useful for handling select_related
    // queries
    var $map;

    var $unbuffered = false;

    function __construct($sql, $params, $map=null) {
        $this->sql = $sql;
        $this->params = $params;
        $this->map = $map;
    }

    function getMap() {
        return $this->map;
    }

    function setBuffered($buffered) {
        $this->unbuffered = !$buffered;
    }

    function fixupParams() {
        $self = $this;
        $params = array();
        $sql = preg_replace_callback("/:(\d+)/",
        function($m) use ($self, &$params) {
            $params[] = $self->params[$m[1]-1];
            return '?';
        }, $this->sql);
        return array($sql, $params);
    }

    function _prepare() {
        $this->execute();
        $this->_setup_output();
    }

    function execute() {
        list($sql, $params) = $this->fixupParams();
        if (!($this->stmt = db_prepare($sql)))
            throw new InconsistentModelException(
                'Unable to prepare query: '.db_error().' '.$sql);
        if (count($params))
            $this->_bind($params);
        if (!$this->stmt->execute() || !($this->unbuffered || $this->stmt->store_result())) {
            throw new OrmException('Unable to execute query: ' . $this->stmt->error);
        }
        return true;
    }

    function _bind($params) {
        if (count($params) != $this->stmt->param_count)
            throw new Exception(__('Parameter count does not match query'));

        $types = '';
        $ps = array();
        foreach ($params as $i=>&$p) {
            if (is_int($p) || is_bool($p))
                $types .= 'i';
            elseif (is_float($p))
                $types .= 'd';
            elseif (is_string($p))
                $types .= 's';
            elseif ($p instanceof DateTime) {
                $types .= 's';
                $p = $p->format('Y-m-d H:i:s');
            }
            elseif (is_object($p)) {
                $types .= 's';
                $p = (string) $p;
            }
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
        $this->fields = $meta->fetch_fields();
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
        $self = $this;
        return preg_replace_callback("/:(\d+)(?=([^']*'[^']*')*[^']*$)/",
        function($m) use ($self) {
            $p = $self->params[$m[1]-1];
            switch (true) {
            case is_bool($p):
                $p = (int) $p;
            case is_int($p):
            case is_float($p):
                return $p;
            case $p instanceof DateTime:
                $p = $p->format('Y-m-d H:i:s');
            default:
                return db_real_escape((string) $p, true);
           }
        }, $this->sql);
    }
}

/**
 * Simplified executor which uses the mysqli_query() function to process
 * queries. This method is faster on MySQL as it doesn't require the PREPARE
 * overhead, nor require two trips to the database per query. All parameters
 * are escaped and placed directly into the SQL statement. With this style,
 * it is possible that multiple parameters could compile a statement which
 * exceeds the MySQL max_allowed_packet setting.
 */
class MySqlExecutor
extends MySqlPreparedExecutor {
    function execute() {
        $sql = $this->__toString();
        if (!($this->stmt = db_query($sql, true, !$this->unbuffered)))
            throw new InconsistentModelException(
                'Unable to prepare query: '.db_error().' '.$sql);
        // mysqli_query() return TRUE for UPDATE queries and friends
        if ($this->stmt !== true)
            $this->_setupCast();
        return true;
    }

    function _setupCast() {
        $fields = $this->stmt->fetch_fields();
        $this->types = array();
        foreach ($fields as $F) {
            $this->types[] = $F->type;
        }
    }

    function _cast($record) {
        $i=0;
        foreach ($record as &$f) {
            switch ($this->types[$i++]) {
            case MYSQLI_TYPE_DECIMAL:
            case MYSQLI_TYPE_NEWDECIMAL:
            case MYSQLI_TYPE_LONGLONG:
            case MYSQLI_TYPE_FLOAT:
            case MYSQLI_TYPE_DOUBLE:
                $f = isset($f) ? (double) $f : $f;
                break;

            case MYSQLI_TYPE_BIT:
            case MYSQLI_TYPE_TINY:
            case MYSQLI_TYPE_SHORT:
            case MYSQLI_TYPE_LONG:
            case MYSQLI_TYPE_INT24:
                $f = isset($f) ? (int) $f : $f;
                break;

            default:
                // No change (leave as string)
            }
        }
        unset($f);
        return $record;
    }

    function getArray() {
        if (!isset($this->stmt))
            $this->execute();

        if (null === ($record = $this->stmt->fetch_assoc()))
            return false;
        return $this->_cast($record);
    }

    function getRow() {
        if (!isset($this->stmt))
            $this->execute();

        if (null === ($record = $this->stmt->fetch_row()))
            return false;
        return $this->_cast($record);
    }

    function affected_rows() {
        return db_affected_rows();
    }

    function insert_id() {
        return db_insert_id();
    }
}

class Q implements Serializable {
    const NEGATED = 0x0001;
    const ANY =     0x0002;

    var $constraints;
    var $negated = false;
    var $ored = false;

    function __construct($filter=array(), $flags=0) {
        if (!is_array($filter))
            $filter = array($filter);
        $this->constraints = $filter;
        $this->negated = $flags & self::NEGATED;
        $this->ored = $flags & self::ANY;
    }

    function isNegated() {
        return $this->negated;
    }

    function isOred() {
        return $this->ored;
    }

    function negate() {
        $this->negated = !$this->negated;
        return $this;
    }

    function union() {
        $this->ored = true;
    }

    function add($constraints) {
        if (is_array($constraints))
            $this->constraints = array_merge($this->constraints, $constraints);
        elseif ($constraints instanceof static)
            $this->constraints[] = $constraints;
        else
            throw new InvalidArgumentException('Expected an instance of Q or an array thereof');
        return $this;
    }

    static function not($constraints) {
        return new static($constraints, self::NEGATED);
    }

    static function any($constraints) {
        return new static($constraints, self::ANY);
    }

    static function all($constraints) {
        return new static($constraints);
    }

    function serialize() {
        return serialize(array($this->negated, $this->ored, $this->constraints));
    }

    function unserialize($data) {
        list($this->negated, $this->ored, $this->constraints) = unserialize($data);
    }
}
?>
