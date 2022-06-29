<?php

/**
 * @see       https://github.com/laminas/laminas-crypt for the canonical source repository
 * @copyright https://github.com/laminas/laminas-crypt/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-crypt/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Crypt\Password\Exception;

use Laminas\Crypt\Exception;

/**
 * Runtime argument exception
 */
class RuntimeException extends Exception\RuntimeException implements
    ExceptionInterface
{
}
