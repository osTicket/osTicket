<?php

namespace Laminas\Validator;

/**
 * @psalm-type ValidatorSpecification = array{
 *     name: string|class-string<ValidatorInterface>,
 *     priority?: int,
 *     break_chain_on_failure?: bool,
 *     options?: array<string, mixed>,
 * }
 */
interface ValidatorInterface
{
    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @return bool
     * @throws Exception\RuntimeException If validation of $value is impossible.
     */
    public function isValid($value);

    /**
     * Returns an array of messages that explain why the most recent isValid()
     * call returned false. The array keys are validation failure message identifiers,
     * and the array values are the corresponding human-readable message strings.
     *
     * If isValid() was never called or if the most recent isValid() call
     * returned true, then this method returns an empty array.
     *
     * @return array<string, string>
     */
    public function getMessages();
}
