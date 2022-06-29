<?php

/**
 * @see       https://github.com/laminas/laminas-mail for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mail/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mail/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mail\Address;

interface AddressInterface
{
    /**
     * Retrieve email
     *
     * @return string
     */
    public function getEmail();

    /**
     * Retrieve name
     *
     * @return string
     */
    public function getName();

    /**
     * String representation of address
     *
     * @return string
     */
    public function toString();
}
