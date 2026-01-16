<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Exception;

use RuntimeException as SplRuntimeException;

/**
 * This exception is thrown by plugin managers when the created object does not match
 * the plugin manager's conditions
 */
class InvalidServiceException extends SplRuntimeException implements ExceptionInterface
{
}
