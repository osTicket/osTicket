<?php

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
     * Retrieve name, if any
     *
     * @return null|string
     */
    public function getName();

    /**
     * String representation of address
     *
     * @return string
     */
    public function toString();
}
