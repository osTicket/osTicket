<?php

/**
 * @see       https://github.com/laminas/laminas-mail for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mail/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mail/blob/master/LICENSE.md New BSD License
 */

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
