<?php

declare(strict_types=1);

namespace Laminas\Stdlib;

use AllowDynamicProperties;
use ArrayAccess;
use ArrayIterator;
use Countable;
use Iterator;
use IteratorAggregate;
use ReturnTypeWillChange;
use Serializable;
use UnexpectedValueException;

use function array_key_exists;
use function array_keys;
use function asort;
use function class_exists;
use function count;
use function get_debug_type;
use function get_object_vars;
use function gettype;
use function in_array;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function ksort;
use function natcasesort;
use function natsort;
use function serialize;
use function sprintf;
use function str_starts_with;
use function uasort;
use function uksort;
use function unserialize;

/**
 * Custom framework ArrayObject implementation
 *
 * Extends version-specific "abstract" implementation.
 *
 * @template TKey of array-key
 * @template TValue
 * @template-implements IteratorAggregate<TKey, TValue>
 * @template-implements ArrayAccess<TKey, TValue>
 */
#[AllowDynamicProperties]
class ArrayObject implements IteratorAggregate, ArrayAccess, Serializable, Countable
{
    /**
     * Properties of the object have their normal functionality
     * when accessed as list (var_dump, foreach, etc.).
     */
    public const STD_PROP_LIST = 1;

    /**
     * Entries can be accessed as properties (read and write).
     */
    public const ARRAY_AS_PROPS = 2;

    /** @var array<TKey, TValue> */
    protected $storage;

    /** @var self::STD_PROP_LIST|self::ARRAY_AS_PROPS */
    protected $flag;

    /** @var class-string<Iterator> */
    protected $iteratorClass;

    /** @var list<string> */
    protected $protectedProperties;

    /**
     * @param array<TKey, TValue>|object               $input Object values must act like ArrayAccess
     * @param self::STD_PROP_LIST|self::ARRAY_AS_PROPS $flags
     * @param class-string<Iterator>                   $iteratorClass
     */
    public function __construct($input = [], $flags = self::STD_PROP_LIST, $iteratorClass = ArrayIterator::class)
    {
        $this->setFlags($flags);
        $this->storage = $input;
        $this->setIteratorClass($iteratorClass);
        $this->protectedProperties = array_keys(get_object_vars($this));
    }

    /**
     * Returns whether the requested key exists
     *
     * @param TKey $key
     * @return bool
     */
    public function __isset(mixed $key)
    {
        if ($this->flag === self::ARRAY_AS_PROPS) {
            return $this->offsetExists($key);
        }

        if (in_array($key, $this->protectedProperties)) {
            throw new Exception\InvalidArgumentException("$key is a protected property, use a different key");
        }

        return isset($this->$key);
    }

    /**
     * Sets the value at the specified key to value
     *
     * @param TKey $key
     * @param TValue $value
     * @return void
     */
    public function __set(mixed $key, mixed $value)
    {
        if ($this->flag === self::ARRAY_AS_PROPS) {
            $this->offsetSet($key, $value);
            return;
        }

        if (in_array($key, $this->protectedProperties)) {
            throw new Exception\InvalidArgumentException("$key is a protected property, use a different key");
        }

        $this->$key = $value;
    }

    /**
     * Unsets the value at the specified key
     *
     * @param TKey $key
     * @return void
     */
    public function __unset(mixed $key)
    {
        if ($this->flag === self::ARRAY_AS_PROPS) {
            $this->offsetUnset($key);
            return;
        }

        if (in_array($key, $this->protectedProperties)) {
            throw new Exception\InvalidArgumentException("$key is a protected property, use a different key");
        }

        unset($this->$key);
    }

    /**
     * Returns the value at the specified key by reference
     *
     * @param TKey $key
     * @return TValue|null
     */
    public function &__get(mixed $key)
    {
        if ($this->flag === self::ARRAY_AS_PROPS) {
            $ret = &$this->offsetGet($key);

            return $ret;
        }

        if (in_array($key, $this->protectedProperties, true)) {
            throw new Exception\InvalidArgumentException("$key is a protected property, use a different key");
        }

        return $this->$key;
    }

    /**
     * Appends the value
     *
     * @param TValue $value
     * @return void
     */
    public function append(mixed $value)
    {
        $this->storage[] = $value;
    }

    /**
     * Sort the entries by value
     *
     * @return void
     */
    public function asort()
    {
        asort($this->storage);
    }

    /**
     * Get the number of public properties in the ArrayObject
     *
     * @return positive-int|0
     */
    #[ReturnTypeWillChange]
    public function count()
    {
        return count($this->storage);
    }

    /**
     * Exchange the array for another one.
     *
     * @param array<TKey, TValue>|ArrayObject<TKey, TValue>|ArrayIterator<TKey, TValue>|object $data
     * @return array<TKey, TValue>
     */
    public function exchangeArray($data)
    {
        if (! is_array($data) && ! is_object($data)) {
            throw new Exception\InvalidArgumentException(
                'Passed variable is not an array or object, using empty array instead'
            );
        }

        if (is_object($data) && ($data instanceof self || $data instanceof \ArrayObject)) {
            $data = $data->getArrayCopy();
        }
        if (! is_array($data)) {
            $data = (array) $data;
        }

        $storage = $this->storage;

        $this->storage = $data;

        return $storage;
    }

    /**
     * Creates a copy of the ArrayObject.
     *
     * @return array<TKey, TValue>
     */
    public function getArrayCopy()
    {
        return $this->storage;
    }

    /**
     * Gets the behavior flags.
     *
     * @return self::STD_PROP_LIST|self::ARRAY_AS_PROPS
     */
    public function getFlags()
    {
        return $this->flag;
    }

    /**
     * Create a new iterator from an ArrayObject instance
     *
     * @return Iterator<TKey, TValue>
     */
    #[ReturnTypeWillChange]
    public function getIterator()
    {
        $class = $this->iteratorClass;

        return new $class($this->storage);
    }

    /**
     * Gets the iterator classname for the ArrayObject.
     *
     * @return class-string<Iterator>
     */
    public function getIteratorClass()
    {
        return $this->iteratorClass;
    }

    /**
     * Sort the entries by key
     *
     * @return void
     */
    public function ksort()
    {
        ksort($this->storage);
    }

    /**
     * Sort an array using a case insensitive "natural order" algorithm
     *
     * @return void
     */
    public function natcasesort()
    {
        natcasesort($this->storage);
    }

    /**
     * Sort entries using a "natural order" algorithm
     *
     * @return void
     */
    public function natsort()
    {
        natsort($this->storage);
    }

    /**
     * Returns whether the requested key exists
     *
     * @param TKey $key
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function offsetExists(mixed $key)
    {
        return isset($this->storage[$key]);
    }

    /**
     * {@inheritDoc}
     *
     * @param TKey $key
     * @return TValue|null
     */
    #[ReturnTypeWillChange]
    public function &offsetGet(mixed $key)
    {
        $ret = null;
        if (! $this->offsetExists($key)) {
            return $ret;
        }
        $ret = &$this->storage[$key];

        return $ret;
    }

    /**
     * Sets the value at the specified key to value
     *
     * @param TKey $key
     * @param TValue $value
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetSet(mixed $key, mixed $value)
    {
        $this->storage[$key] = $value;
    }

    /**
     * Unsets the value at the specified key
     *
     * @param TKey $key
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetUnset(mixed $key)
    {
        if ($this->offsetExists($key)) {
            unset($this->storage[$key]);
        }
    }

    /**
     * Serialize an ArrayObject
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->__serialize());
    }

    /**
     * Magic method used for serializing of an instance.
     *
     * @return array<string, mixed>
     */
    public function __serialize()
    {
        return get_object_vars($this);
    }

    /**
     * Sets the behavior flags
     *
     * @param self::STD_PROP_LIST|self::ARRAY_AS_PROPS $flags
     * @return void
     */
    public function setFlags($flags)
    {
        $this->flag = $flags;
    }

    /**
     * Sets the iterator classname for the ArrayObject
     *
     * @param  class-string<Iterator> $class
     * @return void
     */
    public function setIteratorClass($class)
    {
        if (class_exists($class)) {
            $this->iteratorClass = $class;

            return;
        }

        if (str_starts_with($class, '\\')) {
            $class = '\\' . $class;
            if (class_exists($class)) {
                $this->iteratorClass = $class;

                return;
            }
        }

        throw new Exception\InvalidArgumentException('The iterator class does not exist');
    }

    /**
     * Sort the entries with a user-defined comparison function and maintain key association
     *
     * @param  callable(TValue, TValue): int $function
     * @return void
     */
    public function uasort($function)
    {
        if (is_callable($function)) {
            uasort($this->storage, $function);
        }
    }

    /**
     * Sort the entries by keys using a user-defined comparison function
     *
     * @param  callable(TKey, TKey): int $function
     * @return void
     */
    public function uksort($function)
    {
        if (is_callable($function)) {
            uksort($this->storage, $function);
        }
    }

    /**
     * Unserialize an ArrayObject
     *
     * @param  string $data
     * @return void
     */
    public function unserialize($data)
    {
        $toUnserialize = unserialize($data);
        if (! is_array($toUnserialize)) {
            throw new UnexpectedValueException(sprintf(
                'Cannot deserialize %s instance; corrupt serialization data',
                self::class
            ));
        }

        $this->__unserialize($toUnserialize);
    }

    /**
     * Magic method used to rebuild an instance.
     *
     * @param array $data Data array.
     * @return void
     */
    public function __unserialize($data)
    {
        $this->protectedProperties = array_keys(get_object_vars($this));

        // Unserialize protected internal properties first
        if (array_key_exists('flag', $data)) {
            $this->setFlags((int) $data['flag']);
            unset($data['flag']);
        }

        if (array_key_exists('storage', $data)) {
            if (! is_array($data['storage']) && ! is_object($data['storage'])) {
                throw new UnexpectedValueException(sprintf(
                    'Cannot deserialize %s instance: corrupt storage data; expected array or object, received %s',
                    self::class,
                    gettype($data['storage'])
                ));
            }
            $this->exchangeArray($data['storage']);
            unset($data['storage']);
        }

        if (array_key_exists('iteratorClass', $data)) {
            if (! is_string($data['iteratorClass'])) {
                throw new UnexpectedValueException(sprintf(
                    'Cannot deserialize %s instance: invalid iteratorClass; expected string, received %s',
                    self::class,
                    get_debug_type($data['iteratorClass'])
                ));
            }
            $this->setIteratorClass($data['iteratorClass']);
            unset($data['iteratorClass']);
        }

        unset($data['protectedProperties']);

        // Unserialize array keys after resolving protected properties to ensure configuration is used.
        foreach ($data as $k => $v) {
            $this->__set($k, $v);
        }
    }
}
