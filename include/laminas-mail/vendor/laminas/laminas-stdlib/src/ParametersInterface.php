<?php

declare(strict_types=1);

namespace Laminas\Stdlib;

use ArrayAccess;
use Countable;
use Serializable;
use Traversable;

/*
 * Basically, an ArrayObject. You could simply define something like:
 *     class QueryParams extends ArrayObject implements Parameters {}
 * and have 90% of the functionality
 */
interface ParametersInterface extends ArrayAccess, Countable, Serializable, Traversable
{
    /**
     * Constructor
     *
     * @param array $values
     */
    public function __construct(?array $values = null);

    /**
     * From array
     *
     * Allow deserialization from standard array
     *
     * @param array $values
     * @return mixed
     */
    public function fromArray(array $values);

    /**
     * From string
     *
     * Allow deserialization from raw body; e.g., for PUT requests
     *
     * @param string $string
     * @return mixed
     */
    public function fromString($string);

    /**
     * To array
     *
     * Allow serialization back to standard array
     *
     * @return mixed
     */
    public function toArray();

    /**
     * To string
     *
     * Allow serialization to query format; e.g., for PUT or POST requests
     *
     * @return mixed
     */
    public function toString();

    /**
     * Get
     *
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function get($name, $default = null);

    /**
     * Set
     *
     * @param string $name
     * @param mixed $value
     * @return ParametersInterface
     */
    public function set($name, $value);
}
