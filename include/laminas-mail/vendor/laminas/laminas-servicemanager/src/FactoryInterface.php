<?php

declare(strict_types=1);

namespace Laminas\ServiceManager;

/**
 * Backwards-compatibility shim for FactoryInterface.
 *
 * Implementations should update to implement only Laminas\ServiceManager\Factory\FactoryInterface.
 *
 * If upgrading from v2, take the following steps:
 *
 * - rename the method `createService()` to `__invoke()`, and:
 *   - rename the `$serviceLocator` argument to `$container`, and change the
 *     typehint to `Psr\Container\ContainerInterface`
 *   - add the `$requestedName` as a second argument
 *   - add the optional `array $options = null` argument as a final argument
 * - create a `createService()` method as defined in this interface, and have it
 *   proxy to `__invoke()`.
 *
 * Once you have tested your code, you can then update your class to only implement
 * Laminas\ServiceManager\Factory\FactoryInterface, and remove the `createService()`
 * method.
 *
 * @deprecated Use Laminas\ServiceManager\Factory\FactoryInterface instead.
 */
interface FactoryInterface extends Factory\FactoryInterface
{
    /**
     * Create service
     *
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator);
}
