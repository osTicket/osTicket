<?php

/**
 * @see       https://github.com/laminas/laminas-crypt for the canonical source repository
 * @copyright https://github.com/laminas/laminas-crypt/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-crypt/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Crypt\Exception;

use Interop\Container\Exception\NotFoundException as InteropNotFoundException;

/**
 * Runtime argument exception
 */
class NotFoundException extends \DomainException implements InteropNotFoundException
{
}
