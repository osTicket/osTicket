<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Exception;

use RuntimeException as SplRuntimeException;

/**
 * This exception is thrown when the service locator do not manage to create
 * the service (factory that has an error...)
 */
class ServiceNotCreatedException extends SplRuntimeException implements
    ExceptionInterface
{
}
