<?php

declare(strict_types=1);

namespace Laminas\ServiceManager;

use Laminas\ServiceManager\Exception\ContainerModificationsNotAllowedException;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Psr\Container\ContainerInterface;

use function class_exists;
use function gettype;
use function is_object;
use function method_exists;
use function sprintf;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * Abstract plugin manager.
 *
 * Abstract PluginManagerInterface implementation providing:
 *
 * - creation context support. The constructor accepts the parent container
 *   instance, which is then used when creating instances.
 * - plugin validation. Implementations may define the `$instanceOf` property
 *   to indicate what class types constitute valid plugins, omitting the
 *   requirement to define the `validate()` method.
 *
 * The implementation extends `ServiceManager`, thus providing the same set
 * of capabilities as found in that implementation.
 *
 * @template InstanceType
 * @implements PluginManagerInterface<InstanceType>
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class AbstractPluginManager extends ServiceManager implements PluginManagerInterface
{
    /**
     * Whether or not to auto-add a FQCN as an invokable if it exists.
     *
     * @var bool
     */
    protected $autoAddInvokableClass = true;

    /**
     * An object type that the created instance must be instanced of
     *
     * @var null|string
     * @psalm-var null|class-string<InstanceType>
     */
    protected $instanceOf;

    /**
     * Sets the provided $parentLocator as the creation context for all
     * factories; for $config, {@see \Laminas\ServiceManager\ServiceManager::configure()}
     * for details on its accepted structure.
     *
     * @param null|ConfigInterface|ContainerInterface $configInstanceOrParentLocator
     * @param array $config
     * @psalm-param ServiceManagerConfiguration $config
     */
    public function __construct($configInstanceOrParentLocator = null, array $config = [])
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (
            null !== $configInstanceOrParentLocator
            && ! $configInstanceOrParentLocator instanceof ConfigInterface
            && ! $configInstanceOrParentLocator instanceof ContainerInterface
        ) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a ConfigInterface or ContainerInterface instance as the first argument; received %s',
                self::class,
                is_object($configInstanceOrParentLocator)
                    ? $configInstanceOrParentLocator::class
                    : gettype($configInstanceOrParentLocator)
            ));
        }

        if ($configInstanceOrParentLocator instanceof ConfigInterface) {
            trigger_error(sprintf(
                'Usage of %s as a constructor argument for %s is now deprecated',
                ConfigInterface::class,
                static::class
            ), E_USER_DEPRECATED);
            $config = $configInstanceOrParentLocator->toArray();
        }

        parent::__construct($config);

        if (! $configInstanceOrParentLocator instanceof ContainerInterface) {
            trigger_error(sprintf(
                '%s now expects a %s instance representing the parent container; please update your code',
                __METHOD__,
                ContainerInterface::class
            ), E_USER_DEPRECATED);
        }

        $this->creationContext = $configInstanceOrParentLocator instanceof ContainerInterface
            ? $configInstanceOrParentLocator
            : $this;
    }

    /**
     * Override configure() to validate service instances.
     *
     * @param  array $config
     * @psalm-param ServiceManagerConfiguration $config
     * @return self
     * @throws InvalidServiceException If an instance passed in the `services` configuration is invalid for the
     *                                 plugin manager.
     * @throws ContainerModificationsNotAllowedException If the allow override flag has been toggled off, and a
     *                                                   service instanceexists for a given service.
     */
    public function configure(array $config)
    {
        if (isset($config['services'])) {
            /** @psalm-suppress MixedAssignment */
            foreach ($config['services'] as $service) {
                $this->validate($service);
            }
        }

        parent::configure($config);

        return $this;
    }

    /**
     * Override setService for additional plugin validation.
     *
     * {@inheritDoc}
     *
     * @param string|class-string<InstanceType> $name
     * @param InstanceType $service
     */
    public function setService($name, $service)
    {
        $this->validate($service);
        parent::setService($name, $service);
    }

    /**
     * @param class-string<InstanceType>|string $name Service name of plugin to retrieve.
     * @param null|array<mixed> $options Options to use when creating the instance.
     * @return mixed
     * @psalm-return ($name is class-string<InstanceType> ? InstanceType : mixed)
     * @throws Exception\ServiceNotFoundException If the manager does not have
     *     a service definition for the instance, and the service is not
     *     auto-invokable.
     * @throws InvalidServiceException If the plugin created is invalid for the
     *     plugin context.
     */
    public function get($name, ?array $options = null)
    {
        if (! $this->has($name)) {
            if (! $this->autoAddInvokableClass || ! class_exists($name)) {
                throw new Exception\ServiceNotFoundException(sprintf(
                    'A plugin by the name "%s" was not found in the plugin manager %s',
                    $name,
                    static::class
                ));
            }

            $this->setFactory($name, Factory\InvokableFactory::class);
        }

        $instance = ! $options ? parent::get($name) : $this->build($name, $options);
        $this->validate($instance);
        return $instance;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-assert InstanceType $instance
     */
    public function validate(mixed $instance)
    {
        if (method_exists($this, 'validatePlugin')) {
            trigger_error(sprintf(
                '%s::validatePlugin() has been deprecated as of 3.0; please define validate() instead',
                static::class
            ), E_USER_DEPRECATED);
            $this->validatePlugin($instance);
            return;
        }

        if (empty($this->instanceOf) || $instance instanceof $this->instanceOf) {
            return;
        }

        throw new InvalidServiceException(sprintf(
            'Plugin manager "%s" expected an instance of type "%s", but "%s" was received',
            self::class,
            $this->instanceOf,
            is_object($instance) ? $instance::class : gettype($instance)
        ));
    }

    /**
     * Implemented for backwards compatibility only.
     *
     * Returns the creation context.
     *
     * @deprecated since 3.0.0. The creation context should be passed during
     *     instantiation instead.
     *
     * @return void
     */
    public function setServiceLocator(ContainerInterface $container)
    {
        trigger_error(sprintf(
            'Usage of %s is deprecated since v3.0.0; please pass the container to the constructor instead',
            __METHOD__
        ), E_USER_DEPRECATED);
        $this->creationContext = $container;
    }
}
