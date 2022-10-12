<?php

namespace Laminas\Loader\Exception;

use Exception;

require_once __DIR__ . '/ExceptionInterface.php';

class InvalidPathException extends Exception implements ExceptionInterface
{
}
