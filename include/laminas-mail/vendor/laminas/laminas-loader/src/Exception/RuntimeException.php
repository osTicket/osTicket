<?php

namespace Laminas\Loader\Exception;

require_once __DIR__ . '/ExceptionInterface.php';

class RuntimeException extends \RuntimeException implements ExceptionInterface
{
}
