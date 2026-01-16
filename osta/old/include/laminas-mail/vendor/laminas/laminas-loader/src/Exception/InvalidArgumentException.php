<?php

namespace Laminas\Loader\Exception;

require_once __DIR__ . '/ExceptionInterface.php';

class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}
