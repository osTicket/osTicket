<?php

namespace Laminas\Validator;

use Traversable;

use function array_shift;
use function floor;
use function func_get_args;
use function is_array;
use function is_numeric;
use function iterator_to_array;
use function round;
use function strlen;
use function strpos;
use function substr;

class Step extends AbstractValidator
{
    public const INVALID  = 'typeInvalid';
    public const NOT_STEP = 'stepInvalid';

    /** @var array */
    protected $messageTemplates = [
        self::INVALID  => 'Invalid value given. Scalar expected',
        self::NOT_STEP => 'The input is not a valid step',
    ];

    /** @var mixed */
    protected $baseValue = 0;

    /** @var mixed */
    protected $step = 1;

    /**
     * Set default options for this instance
     *
     * @param iterable<string, mixed> $options
     */
    public function __construct($options = [])
    {
        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        } elseif (! is_array($options)) {
            $options           = func_get_args();
            $temp['baseValue'] = array_shift($options);
            if (! empty($options)) {
                $temp['step'] = array_shift($options);
            }

            $options = $temp;
        }

        if (isset($options['baseValue'])) {
            $this->setBaseValue($options['baseValue']);
        }
        if (isset($options['step'])) {
            $this->setStep($options['step']);
        }

        parent::__construct($options);
    }

    /**
     * Sets the base value from which the step should be computed
     *
     * @return $this
     */
    public function setBaseValue(mixed $baseValue)
    {
        $this->baseValue = $baseValue;
        return $this;
    }

    /**
     * Returns the base value from which the step should be computed
     *
     * @return string
     */
    public function getBaseValue()
    {
        return $this->baseValue;
    }

    /**
     * Sets the step value
     *
     * @return $this
     */
    public function setStep(mixed $step)
    {
        $this->step = (float) $step;
        return $this;
    }

    /**
     * Returns the step value
     *
     * @return string
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * Returns true if $value is a scalar and a valid step value
     *
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if (! is_numeric($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);

        $substract = $this->sub($value, $this->baseValue);

        $fmod = $this->fmod($substract, $this->step);

        if ($fmod !== 0.0 && $fmod !== $this->step) {
            $this->error(self::NOT_STEP);
            return false;
        }

        return true;
    }

    /**
     * replaces the internal fmod function which give wrong results on many cases
     *
     * @param int|float $x
     * @param int|float $y
     * @return float
     */
    protected function fmod($x, $y)
    {
        if ($y === 0.0 || $y === 0) {
            return 1.0;
        }

        //find the maximum precision from both input params to give accurate results
        $precision = $this->getPrecision($x) + $this->getPrecision($y);

        return round($x - $y * floor($x / $y), $precision);
    }

    /**
     * replaces the internal substraction operation which give wrong results on some cases
     *
     * @param float $x
     * @param float $y
     * @return float
     */
    private function sub($x, $y)
    {
        $precision = $this->getPrecision($x) + $this->getPrecision($y);
        return round($x - $y, $precision);
    }

    /**
     * @param float $float
     */
    private function getPrecision($float): int
    {
        $position = strpos((string) $float, '.');
        $segment  = $position === false
            ? null
            : substr((string) $float, $position + 1);

        return $segment !== null ? strlen($segment) : 0;
    }
}
