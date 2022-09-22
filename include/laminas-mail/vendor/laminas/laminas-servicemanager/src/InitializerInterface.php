<?php

declare(strict_types=1);

namespace Laminas\ServiceManager;

/**
 * Backwards-compatibility shim for InitializerInterface.
 *
 * Implementations should update to implement only Laminas\ServiceManager\Initializer\InitializerInterface.
 *
 * If upgrading from v2, take the following steps:
 *
 * - rename the method `initialize()` to `__invoke()`, and:
 *   - rename the `$serviceLocator` argument to `$container`, and change the
 *     typehint to `Psr\Container\ContainerInterface`
 *   - swap the order of the arguments (so that `$instance` comes second)
 * - create an `initialize()` method as defined in this interface, and have it
 *   proxy to `__invoke()`, passing the arguments in the new order.
 *
 * Once you have tested your code, you can then update your class to only implement
 * Laminas\ServiceManager\Initializer\InitializerInterface, and remove the `initialize()`
 * method.
 *
 * @deprecated Use Laminas\ServiceManager\Initializer\InitializerInterface instead.
 */
interface InitializerInterface extends Initializer\InitializerInterface
{
    /**
     * Initialize
     *
     * @param mixed $instance
     * @return mixed
     */
    public function initialize($instance, ServiceLocatorInterface $serviceLocator);
}
