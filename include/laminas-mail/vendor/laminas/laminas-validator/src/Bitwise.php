<?php // phpcs:disable WebimpressCodingStandard.Formatting.Reference.UnexpectedSpace

namespace Laminas\Validator;

use Traversable;

use function array_shift;
use function func_get_args;
use function is_array;
use function iterator_to_array;

class Bitwise extends AbstractValidator
{
    public const OP_AND = 'and';
    public const OP_XOR = 'xor';

    public const NOT_AND        = 'notAnd';
    public const NOT_AND_STRICT = 'notAndStrict';
    public const NOT_XOR        = 'notXor';
    public const NO_OP          = 'noOp';

    /** @var int */
    protected $control;

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected $messageTemplates = [
        self::NOT_AND        => "The input has no common bit set with '%control%'",
        self::NOT_AND_STRICT => "The input doesn't have the same bits set as '%control%'",
        self::NOT_XOR        => "The input has common bit set with '%control%'",
        self::NO_OP          => "No operator was present to compare '%control%' against",
    ];

    /**
     * Additional variables available for validation failure messages
     *
     * @var array<string, string>
     */
    protected $messageVariables = [
        'control' => 'control',
    ];

    /** @var null|int */
    protected $operator;

    /** @var bool */
    protected $strict = false;

    /**
     * Sets validator options
     * Accepts the following option keys:
     *   'control'  => int
     *   'operator' =>
     *   'strict'   => bool
     *
     * @param array|Traversable $options
     */
    public function __construct($options = null)
    {
        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }

        if (! is_array($options)) {
            $options = func_get_args();

            $temp['control'] = array_shift($options);

            if (! empty($options)) {
                $temp['operator'] = array_shift($options);
            }

            if (! empty($options)) {
                $temp['strict'] = array_shift($options);
            }

            $options = $temp;
        }

        parent::__construct($options);
    }

    /**
     * Returns the control parameter.
     *
     * @return integer
     */
    public function getControl()
    {
        return $this->control;
    }

    /**
     * Returns the operator parameter.
     *
     * @return null|int
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Returns the strict parameter.
     *
     * @return boolean
     */
    public function getStrict()
    {
        return $this->strict;
    }

    /**
     * Returns true if and only if $value is between min and max options, inclusively
     * if inclusive option is true.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        $this->setValue($value);

        if (self::OP_AND === $this->operator) {
            if ($this->strict) {
                // All the bits set in value must be set in control
                $result = ($this->control & $value) === $value;

                if (! $result) {
                    $this->error(self::NOT_AND_STRICT);
                }

                return $result;
            }

            // At least one of the bits must be common between value and control
            $result = (bool) ($this->control & $value);

            if (! $result) {
                $this->error(self::NOT_AND);
            }

            return $result;
        }

        if (self::OP_XOR === $this->operator) {
            // Parentheses are required due to order of operations with bitwise operations
            // phpcs:ignore WebimpressCodingStandard.Formatting.RedundantParentheses.SingleEquality
            $result = ($this->control ^ $value) === ($this->control | $value);

            if (! $result) {
                $this->error(self::NOT_XOR);
            }

            return $result;
        }

        $this->error(self::NO_OP);
        return false;
    }

    /**
     * Sets the control parameter.
     *
     * @param  integer $control
     * @return $this
     */
    public function setControl($control)
    {
        $this->control = (int) $control;

        return $this;
    }

    /**
     * Sets the operator parameter.
     *
     * @param  string  $operator
     * @return $this
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Sets the strict parameter.
     *
     * @param  boolean $strict
     * @return $this
     */
    public function setStrict($strict)
    {
        $this->strict = (bool) $strict;

        return $this;
    }
}
