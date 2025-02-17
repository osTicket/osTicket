<?php

namespace Laminas\Validator;

use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;

use function is_array;

/**
 * @link ServiceManager
 *
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 */
class ValidatorPluginManagerFactory implements FactoryInterface
{
    /**
     * laminas-servicemanager v2 support for invocation options.
     *
     * @var null|ServiceManagerConfiguration
     */
    protected $creationOptions;

    /**
     * {@inheritDoc}
     *
     * @param string $name
     * @param ServiceManagerConfiguration|null $options
     * @return ValidatorPluginManager
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function __invoke(ContainerInterface $container, $name, ?array $options = null)
    {
        $pluginManager = new ValidatorPluginManager($container, $options ?? []);

        // If this is in a laminas-mvc application, the ServiceListener will inject
        // merged configuration during bootstrap.
        if ($container->has('ServiceListener')) {
            return $pluginManager;
        }

        // If we do not have a config service, nothing more to do
        if (! $container->has('config')) {
            return $pluginManager;
        }

        $config = $container->get('config');

        // If we do not have validators configuration, nothing more to do
        if (! isset($config['validators']) || ! is_array($config['validators'])) {
            return $pluginManager;
        }

        // Wire service configuration for validators
        (new Config($config['validators']))->configureServiceManager($pluginManager);

        return $pluginManager;
    }

    /**
     * {@inheritDoc}
     *
     * @param string|null $name
     * @param string|null $requestedName
     * @return ValidatorPluginManager
     */
    public function createService(ServiceLocatorInterface $container, $name = null, $requestedName = null)
    {
        return $this($container, $requestedName ?? ValidatorPluginManager::class, $this->creationOptions);
    }

    /**
     * laminas-servicemanager v2 support for invocation options.
     *
     * @param ServiceManagerConfiguration $options
     * @return void
     */
    public function setCreationOptions(array $options)
    {
        $this->creationOptions = $options;
    }
}
