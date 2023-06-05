<?php

namespace Laminas\Validator;

use Exception;
use Laminas\Validator\Exception\InvalidArgumentException;

use function array_merge;
use function call_user_func_array;
use function is_callable;

class Callback extends AbstractValidator
{
    /**
     * Invalid callback
     */
    public const INVALID_CALLBACK = 'callbackInvalid';

    /**
     * Invalid value
     */
    public const INVALID_VALUE = 'callbackValue';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $messageTemplates = [
        self::INVALID_VALUE    => 'The input is not valid',
        self::INVALID_CALLBACK => 'An exception has been raised within the callback',
    ];

    /**
     * Default options to set for the validator
     *
     * @var mixed
     */
    protected $options = [
        'callback'        => null, // Callback in a call_user_func format, string || array
        'callbackOptions' => [], // Options for the callback
    ];

    /**
     * Constructor
     *
     * @param array|callable $options
     */
    public function __construct($options = null)
    {
        if (is_callable($options)) {
            $options = ['callback' => $options];
        }

        parent::__construct($options);
    }

    /**
     * Returns the set callback
     *
     * @return mixed
     */
    public function getCallback()
    {
        return $this->options['callback'];
    }

    /**
     * Sets the callback
     *
     * @param  string|array|callable $callback
     * @return $this Provides a fluent interface
     * @throws InvalidArgumentException
     */
    public function setCallback($callback)
    {
        if (! is_callable($callback)) {
            throw new InvalidArgumentException('Invalid callback given');
        }

        $this->options['callback'] = $callback;
        return $this;
    }

    /**
     * Returns the set options for the callback
     *
     * @return mixed
     */
    public function getCallbackOptions()
    {
        return $this->options['callbackOptions'];
    }

    /**
     * Sets options for the callback
     *
     * @return $this Provides a fluent interface
     */
    public function setCallbackOptions(mixed $options)
    {
        $this->options['callbackOptions'] = (array) $options;
        return $this;
    }

    /**
     * Returns true if and only if the set callback returns
     * for the provided $value
     *
     * @param  mixed $value
     * @param  mixed $context Additional context to provide to the callback
     * @return bool
     * @throws InvalidArgumentException
     */
    public function isValid($value, $context = null)
    {
        $this->setValue($value);

        $options  = $this->getCallbackOptions();
        $callback = $this->getCallback();
        if (empty($callback)) {
            throw new InvalidArgumentException('No callback given');
        }

        $args = [$value];
        if (empty($options) && ! empty($context)) {
            $args[] = $context;
        }
        if (! empty($options) && empty($context)) {
            $args = array_merge($args, $options);
        }
        if (! empty($options) && ! empty($context)) {
            $args[] = $context;
            $args   = array_merge($args, $options);
        }

        try {
            if (! call_user_func_array($callback, $args)) {
                $this->error(self::INVALID_VALUE);
                return false;
            }
        } catch (Exception) {
            $this->error(self::INVALID_CALLBACK);
            return false;
        }

        return true;
    }
}
