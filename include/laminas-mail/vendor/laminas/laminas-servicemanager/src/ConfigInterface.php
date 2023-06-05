<?php

declare(strict_types=1);

namespace Laminas\ServiceManager;

use ArrayAccess;
use Psr\Container\ContainerInterface;

/**
 * @deprecated Interface will be removed as of v4.0
 *
 * @see ContainerInterface
 * @see ArrayAccess
 *
 * @psalm-type AbstractFactoriesConfigurationType = array<
 *      array-key,
 *      (class-string<Factory\AbstractFactoryInterface>|Factory\AbstractFactoryInterface)
 * >
 * @psalm-type DelegatorsConfigurationType = array<
 *      string,
 *      array<
 *          array-key,
 *          (class-string<Factory\DelegatorFactoryInterface>|Factory\DelegatorFactoryInterface)
 *          |callable(ContainerInterface,string,callable():object,array<mixed>|null):object
 *      >
 * >
 * @psalm-type FactoriesConfigurationType = array<
 *      string,
 *      (class-string<Factory\FactoryInterface>|Factory\FactoryInterface)
 *      |callable(ContainerInterface,?string,?array<mixed>|null):object
 * >
 * @psalm-type InitializersConfigurationType = array<
 *      array-key,
 *      (class-string<Initializer\InitializerInterface>|Initializer\InitializerInterface)
 *      |callable(ContainerInterface,object):void
 * >
 * @psalm-type LazyServicesConfigurationType = array{
 *      class_map?:array<string,class-string>,
 *      proxies_namespace?:non-empty-string,
 *      proxies_target_dir?:non-empty-string,
 *      write_proxy_files?:bool
 * }
 * @psalm-type ServiceManagerConfigurationType = array{
 *     abstract_factories?: AbstractFactoriesConfigurationType,
 *     aliases?: array<string,string>,
 *     delegators?: DelegatorsConfigurationType,
 *     factories?: FactoriesConfigurationType,
 *     initializers?: InitializersConfigurationType,
 *     invokables?: array<string,string>,
 *     lazy_services?: LazyServicesConfigurationType,
 *     services?: array<string,object|array>,
 *     shared?:array<string,bool>,
 *     ...
 * }
 */
interface ConfigInterface
{
    /**
     * Configure a service manager.
     *
     * Implementations should pull configuration from somewhere (typically
     * local properties) and pass it to a ServiceManager's withConfig() method,
     * returning a new instance.
     *
     * @return ServiceManager
     */
    public function configureServiceManager(ServiceManager $serviceManager);

    /**
     * Return configuration for a service manager instance as an array.
     *
     * Implementations MUST return an array compatible with ServiceManager::configure,
     * containing one or more of the following keys:
     *
     * - abstract_factories
     * - aliases
     * - delegators
     * - factories
     * - initializers
     * - invokables
     * - lazy_services
     * - services
     * - shared
     *
     * In other words, this should return configuration that can be used to instantiate
     * a service manager or plugin manager, or pass to its `withConfig()` method.
     *
     * @return array
     * @psalm-return ServiceManagerConfigurationType
     */
    public function toArray();
}
