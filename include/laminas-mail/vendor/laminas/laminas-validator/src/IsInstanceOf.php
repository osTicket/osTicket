<?php

namespace Laminas\Validator;

use Traversable;

use function array_key_exists;
use function array_shift;
use function func_get_args;
use function is_array;
use function iterator_to_array;

class IsInstanceOf extends AbstractValidator
{
    public const NOT_INSTANCE_OF = 'notInstanceOf';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $messageTemplates = [
        self::NOT_INSTANCE_OF => "The input is not an instance of '%className%'",
    ];

    /**
     * Additional variables available for validation failure messages
     *
     * @var array
     */
    protected $messageVariables = [
        'className' => 'className',
    ];

    /** @var string */
    protected $className;

    /**
     * Sets validator options
     *
     * @param  array|Traversable $options
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($options = null)
    {
        if ($options instanceof Traversable) {
            $options = iterator_to_array($options);
        }

        // If argument is not an array, consider first argument as class name
        if (! is_array($options)) {
            $options = func_get_args();

            $tmpOptions              = [];
            $tmpOptions['className'] = array_shift($options);

            $options = $tmpOptions;
        }

        if (! array_key_exists('className', $options)) {
            throw new Exception\InvalidArgumentException('Missing option "className"');
        }

        parent::__construct($options);
    }

    /**
     * Get class name
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Set class name
     *
     * @param  string $className
     * @return $this
     */
    public function setClassName($className)
    {
        $this->className = $className;
        return $this;
    }

    /**
     * Returns true if $value is instance of $this->className
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        if ($value instanceof $this->className) {
            return true;
        }
        $this->error(self::NOT_INSTANCE_OF);
        return false;
    }
}
