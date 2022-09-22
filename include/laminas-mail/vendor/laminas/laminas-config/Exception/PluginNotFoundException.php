<?php

namespace Laminas\Config\Exception;

use Psr\Container\NotFoundExceptionInterface;

class PluginNotFoundException extends RuntimeException implements
    NotFoundExceptionInterface
{
}
