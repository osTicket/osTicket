<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Initializer;

use Psr\Container\ContainerInterface;

/**
 * Interface for an initializer
 *
 * An initializer can be registered to a service locator, and are run after an instance is created
 * to inject additional dependencies through setters
 */
interface InitializerInterface
{
    /**
     * Initialize the given instance
     *
     * @param  object             $instance
     * @return void
     */
    public function __invoke(ContainerInterface $container, $instance);
}
