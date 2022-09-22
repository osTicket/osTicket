<?php

namespace Laminas\Validator;

use DateTimeZone;

use function array_key_exists;
use function array_search;
use function get_class;
use function gettype;
use function in_array;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function sprintf;

class Timezone extends AbstractValidator
{
    public const INVALID                       = 'invalidTimezone';
    public const INVALID_TIMEZONE_LOCATION     = 'invalidTimezoneLocation';
    public const INVALID_TIMEZONE_ABBREVIATION = 'invalidTimezoneAbbreviation';

    public const LOCATION     = 0b01;
    public const ABBREVIATION = 0b10;
    public const ALL          = 0b11;

    /** @var array */
    protected $constants = [
        self::LOCATION     => 'location',
        self::ABBREVIATION => 'abbreviation',
    ];

    /**
     * Default value for types; value = 3
     *
     * @var array
     */
    protected $defaultType = [
        self::LOCATION,
        self::ABBREVIATION,
    ];

    /** @var array */
    protected $messageTemplates = [
        self::INVALID                       => 'Invalid timezone given.',
        self::INVALID_TIMEZONE_LOCATION     => 'Invalid timezone location given.',
        self::INVALID_TIMEZONE_ABBREVIATION => 'Invalid timezone abbreviation given.',
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
     * @param array|int $options OPTIONAL
     */
    public function __construct($options = [])
    {
        $opts['type'] = $this->defaultType;

        if (is_array($options)) {
            if (array_key_exists('type', $options)) {
                $opts['type'] = $options['type'];
            }
        } elseif (! empty($options)) {
            $opts['type'] = $options;
        }

        // setType called by parent constructor then setOptions method
        parent::__construct($opts);
    }

    /**
     * Set the types
     *
     * @param int|array $type
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    public function setType($type = null)
    {
        $type = $this->calculateTypeValue($type);

        if (! is_int($type) || ($type < 1) || ($type > self::ALL)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Unknown type "%s" provided',
                is_string($type) || is_int($type)
                    ? $type
                    : (is_object($type) ? get_class($type) : gettype($type))
            ));
        }

        $this->options['type'] = $type;
    }

    /**
     * Returns true if timezone location or timezone abbreviations is correct.
     *
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if ($value !== null && ! is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $type = $this->options['type'];
        $this->setValue($value);

        switch (true) {
            // Check in locations and abbreviations
            case ($type & self::LOCATION) && ($type & self::ABBREVIATION):
                $abbrs     = DateTimeZone::listAbbreviations();
                $locations = DateTimeZone::listIdentifiers();

                if (! array_key_exists($value, $abbrs) && ! in_array($value, $locations)) {
                    $this->error(self::INVALID);
                    return false;
                }
                break;

            // Check only in locations
            case $type & self::LOCATION:
                $locations = DateTimeZone::listIdentifiers();

                if (! in_array($value, $locations)) {
                    $this->error(self::INVALID_TIMEZONE_LOCATION);
                    return false;
                }
                break;

            // Check only in abbreviations
            case $type & self::ABBREVIATION:
                $abbrs = DateTimeZone::listAbbreviations();

                if (! array_key_exists($value, $abbrs)) {
                    $this->error(self::INVALID_TIMEZONE_ABBREVIATION);
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * @param array|int|string $type
     * @return float|int
     */
    protected function calculateTypeValue($type)
    {
        $types    = (array) $type;
        $detected = 0;

        foreach ($types as $value) {
            if (is_int($value)) {
                $detected |= $value;
            } elseif (false !== array_search($value, $this->constants)) {
                $detected |= array_search($value, $this->constants);
            }
        }

        return $detected;
    }
}
