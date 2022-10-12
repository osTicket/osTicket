<?php

namespace Laminas\Validator;

use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use Traversable;

use function explode;
use function is_array;
use function is_string;
use function sprintf;

/**
 * @psalm-import-type ValidatorSpecification from ValidatorInterface
 */
class Explode extends AbstractValidator implements ValidatorPluginManagerAwareInterface
{
    public const INVALID = 'explodeInvalid';

    /** @var null|ValidatorPluginManager */
    protected $pluginManager;

    /** @var array */
    protected $messageTemplates = [
        self::INVALID => 'Invalid type given',
    ];

    /** @var array */
    protected $messageVariables = [];

    /** @var string */
    protected $valueDelimiter = ',';

    /** @var ValidatorInterface|null */
    protected $validator;

    /** @var bool */
    protected $breakOnFirstFailure = false;

    /**
     * Sets the delimiter string that the values will be split upon
     *
     * @param string $delimiter
     * @return $this
     */
    public function setValueDelimiter($delimiter)
    {
        $this->valueDelimiter = $delimiter;
        return $this;
    }

    /**
     * Returns the delimiter string that the values will be split upon
     *
     * @return string
     */
    public function getValueDelimiter()
    {
        return $this->valueDelimiter;
    }

    /**
     * Set validator plugin manager
     *
     * @return void
     */
    public function setValidatorPluginManager(ValidatorPluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    /**
     * Get validator plugin manager
     *
     * @return ValidatorPluginManager
     */
    public function getValidatorPluginManager()
    {
        if (! $this->pluginManager) {
            $this->pluginManager = new ValidatorPluginManager(new ServiceManager());
        }

        return $this->pluginManager;
    }

    /**
     * Sets the Validator for validating each value
     *
     * @param ValidatorInterface|ValidatorSpecification $validator
     * @throws Exception\RuntimeException
     * @return $this
     */
    public function setValidator($validator)
    {
        if (is_array($validator)) {
            if (! isset($validator['name'])) {
                throw new Exception\RuntimeException(
                    'Invalid validator specification provided; does not include "name" key'
                );
            }
            $name    = $validator['name'];
            $options = $validator['options'] ?? [];
            /** @psalm-suppress MixedAssignment $validator */
            $validator = $this->getValidatorPluginManager()->get($name, $options);
        }

        if (! $validator instanceof ValidatorInterface) {
            throw new Exception\RuntimeException(
                'Invalid validator given'
            );
        }

        $this->validator = $validator;
        return $this;
    }

    /**
     * Gets the Validator for validating each value
     *
     * @return ValidatorInterface|null
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Set break on first failure setting
     *
     * @param  bool $break
     * @return $this
     */
    public function setBreakOnFirstFailure($break)
    {
        $this->breakOnFirstFailure = (bool) $break;
        return $this;
    }

    /**
     * Get break on first failure setting
     *
     * @return bool
     */
    public function isBreakOnFirstFailure()
    {
        return $this->breakOnFirstFailure;
    }

    /**
     * Defined by Laminas\Validator\ValidatorInterface
     *
     * Returns true if all values validate true
     *
     * @param  mixed $value
     * @param  mixed $context Extra "context" to provide the composed validator
     * @return bool
     * @throws Exception\RuntimeException
     */
    public function isValid($value, $context = null)
    {
        $this->setValue($value);

        if ($value instanceof Traversable) {
            $value = ArrayUtils::iteratorToArray($value);
        }

        if (is_array($value)) {
            $values = $value;
        } elseif (is_string($value)) {
            $delimiter = $this->getValueDelimiter();
            // Skip explode if delimiter is null,
            // used when value is expected to be either an
            // array when multiple values and a string for
            // single values (ie. MultiCheckbox form behavior)
            $values = null !== $delimiter
                      ? explode($this->valueDelimiter, $value)
                      : [$value];
        } else {
            $values = [$value];
        }

        $validator = $this->getValidator();

        if (! $validator) {
            throw new Exception\RuntimeException(sprintf(
                '%s expects a validator to be set; none given',
                __METHOD__
            ));
        }

        foreach ($values as $value) {
            if (! $validator->isValid($value, $context)) {
                $this->abstractOptions['messages'][] = $validator->getMessages();

                if ($this->isBreakOnFirstFailure()) {
                    return false;
                }
            }
        }

        return ! $this->abstractOptions['messages'];
    }
}
