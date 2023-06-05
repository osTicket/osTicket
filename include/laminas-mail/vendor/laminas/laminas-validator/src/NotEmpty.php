<?php

namespace Laminas\Validator;

use Countable;
use Laminas\Stdlib\ArrayUtils;
use Traversable;

use function array_search;
use function array_shift;
use function count;
use function func_get_args;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function method_exists;
use function preg_match;

class NotEmpty extends AbstractValidator
{
    public const BOOLEAN       = 0b000000000001;
    public const INTEGER       = 0b000000000010;
    public const FLOAT         = 0b000000000100;
    public const STRING        = 0b000000001000;
    public const ZERO          = 0b000000010000;
    public const EMPTY_ARRAY   = 0b000000100000;
    public const NULL          = 0b000001000000;
    public const PHP           = 0b000001111111;
    public const SPACE         = 0b000010000000;
    public const OBJECT        = 0b000100000000;
    public const OBJECT_STRING = 0b001000000000;
    public const OBJECT_COUNT  = 0b010000000000;
    public const ALL           = 0b011111111111;

    public const INVALID  = 'notEmptyInvalid';
    public const IS_EMPTY = 'isEmpty';

    /** @var array<int, string> */
    protected $constants = [
        self::BOOLEAN       => 'boolean',
        self::INTEGER       => 'integer',
        self::FLOAT         => 'float',
        self::STRING        => 'string',
        self::ZERO          => 'zero',
        self::EMPTY_ARRAY   => 'array',
        self::NULL          => 'null',
        self::PHP           => 'php',
        self::SPACE         => 'space',
        self::OBJECT        => 'object',
        self::OBJECT_STRING => 'objectstring',
        self::OBJECT_COUNT  => 'objectcount',
        self::ALL           => 'all',
    ];

    /**
     * Default value for types; value = 0b000111101001
     *
     * @var array
     */
    protected $defaultType = [
        self::OBJECT,
        self::SPACE,
        self::NULL,
        self::EMPTY_ARRAY,
        self::STRING,
        self::BOOLEAN,
    ];

    /** @var array */
    protected $messageTemplates = [
        self::IS_EMPTY => "Value is required and can't be empty",
        self::INVALID  => 'Invalid type given. String, integer, float, boolean or array expected',
    ];

    /**
     * Options for this validator
     *
     * @var array
     */
    protected $options = [];

    /**
     * Constructor
     *
     * @param  array|Traversable|int $options OPTIONAL
     */
    public function __construct($options = null)
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (! is_array($options)) {
            $options = func_get_args();
            $temp    = [];
            if (! empty($options)) {
                $temp['type'] = array_shift($options);
            }

            $options = $temp;
        }

        if (! isset($options['type'])) {
            if (($type = $this->calculateTypeValue($options)) !== 0) {
                $options['type'] = $type;
            } else {
                $options['type'] = $this->defaultType;
            }
        }

        parent::__construct($options);
    }

    /**
     * Returns the set types
     *
     * @return int
     */
    public function getType()
    {
        return $this->options['type'];
    }

    /**
     * @return false|int|string
     */
    public function getDefaultType()
    {
        return $this->calculateTypeValue($this->defaultType);
    }

    /**
     * @param array|int|string $type
     * @return false|int|string
     */
    protected function calculateTypeValue($type)
    {
        if (is_array($type)) {
            $detected = 0;
            foreach ($type as $value) {
                if (is_int($value)) {
                    $detected |= $value;
                } elseif (in_array($value, $this->constants, true)) {
                    $detected |= (int) array_search($value, $this->constants, true);
                }
            }

            $type = $detected;
        } elseif (is_string($type) && in_array($type, $this->constants, true)) {
            $type = array_search($type, $this->constants, true);
        }

        return $type;
    }

    /**
     * Set the types
     *
     * @param  int|int[] $type
     * @throws Exception\InvalidArgumentException
     * @return $this
     */
    public function setType($type = null)
    {
        $type = $this->calculateTypeValue($type);

        if (! is_int($type) || ($type < 0) || ($type > self::ALL)) {
            throw new Exception\InvalidArgumentException('Unknown type');
        }

        $this->options['type'] = $type;

        return $this;
    }

    /**
     * Returns true if and only if $value is not an empty value.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (
            $value !== null
            && ! is_string($value)
            && ! is_int($value)
            && ! is_float($value)
            && ! is_bool($value)
            && ! is_array($value)
            && ! is_object($value)
        ) {
            $this->error(self::INVALID);
            return false;
        }

        $type = $this->getType();
        $this->setValue($value);
        $object = false;

        // OBJECT_COUNT (countable object)
        if ($type & self::OBJECT_COUNT) {
            $object = true;

            if (is_object($value) && $value instanceof Countable && (count($value) === 0)) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // OBJECT_STRING (object's toString)
        if ($type & self::OBJECT_STRING) {
            $object = true;

            if (
                (is_object($value) && ! method_exists($value, '__toString'))
                || (is_object($value) && method_exists($value, '__toString') && (string) $value === '')
            ) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // OBJECT (object)
        // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
        if ($type & self::OBJECT) {
            // fall through, objects are always not empty
        } elseif ($object === false) {
            // object not allowed but object given -> return false
            if (is_object($value)) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // SPACE ('   ')
        if ($type & self::SPACE) {
            if (is_string($value) && (preg_match('/^\s+$/s', $value))) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // NULL (null)
        if ($type & self::NULL) {
            if ($value === null) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // EMPTY_ARRAY (array())
        if ($type & self::EMPTY_ARRAY) {
            if ($value === []) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // ZERO ('0')
        if ($type & self::ZERO) {
            if ($value === '0') {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // STRING ('')
        if ($type & self::STRING) {
            if ($value === '') {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // FLOAT (0.0)
        if ($type & self::FLOAT) {
            if ($value === 0.0) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // INTEGER (0)
        if ($type & self::INTEGER) {
            if ($value === 0) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // BOOLEAN (false)
        if ($type & self::BOOLEAN) {
            if ($value === false) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        return true;
    }
}
