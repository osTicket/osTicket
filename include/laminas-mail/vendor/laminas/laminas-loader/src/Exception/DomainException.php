<?php

namespace Laminas\Loader\Exception;

require_once __DIR__ . '/ExceptionInterface.php';

class DomainException extends \DomainException implements ExceptionInterface
{
}
