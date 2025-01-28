<?php

namespace Laminas\Mail\Header;

interface StructuredInterface extends HeaderInterface
{
    /**
     * Return the delimiter at which a header line should be wrapped
     *
     * @return string
     */
    public function getDelimiter();
}
