<?php // phpcs:disable WebimpressCodingStandard.PHP.CorrectClassNameCase.Invalid


declare(strict_types=1);

use Interop\Container\Containerinterface as InteropContainerInterface;
use Interop\Container\Exception\ContainerException as InteropContainerException;
use Interop\Container\Exception\NotFoundException as InteropNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

if (! interface_exists(InteropContainerInterface::class, false)) {
    class_alias(ContainerInterface::class, InteropContainerInterface::class);
}
if (! interface_exists(InteropContainerException::class, false)) {
    class_alias(ContainerExceptionInterface::class, InteropContainerException::class);
}
if (! interface_exists(InteropNotFoundException::class, false)) {
    class_alias(NotFoundExceptionInterface::class, InteropNotFoundException::class);
}
