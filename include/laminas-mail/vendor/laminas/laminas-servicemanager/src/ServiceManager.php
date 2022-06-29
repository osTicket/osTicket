<?php

/**
 * @see       https://github.com/laminas/laminas-servicemanager for the canonical source repository
 * @copyright https://github.com/laminas/laminas-servicemanager/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-servicemanager/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ServiceManager;

use Exception;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ContainerModificationsNotAllowedException;
use Laminas\ServiceManager\Exception\CyclicAliasException;
use Laminas\ServiceManager\Exception\InvalidArgumentException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use ProxyManager\Configuration as ProxyConfiguration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;

/**
 * Service Manager.
 *
 * Default implementation of the ServiceLocatorInterface, providing capabilities
 * for object creation via:
 *
 * - factories
 * - abstract factories
 * - delegator factories
 * - lazy service factories (generated proxies)
 * - initializers (interface injection)
 *
 * It also provides the ability to inject specific service instances and to
 * define aliases.
 */
class ServiceManager implements ServiceLocatorInterface
{
    /**
     * @var Factory\AbstractFactoryInterface[]
     */
    protected $abstractFactories = [];

    /**
     * A list of aliases
     *
     * Should map one alias to a service name, or another alias (aliases are recursively resolved)
     *
     * @var string[]
     */
    protected $aliases = [];

    /**
     * Whether or not changes may be made to this instance.
     *
     * @param bool
     */
    protected $allowOverride = false;

    /**
     * @var ContainerInterface
     */
    protected $creationContext;

    /**
     * @var string[][]|Factory\DelegatorFactoryInterface[][]
     */
    protected $delegators = [];

    /**
     * A list of factories (either as string name or callable)
     *
     * @var string[]|callable[]
     */
    protected $factories = [];

    /**
     * @var Initializer\InitializerInterface[]|callable[]
     */
    protected $initializers = [];

    /**
     * @var array
     */
    protected $lazyServices = [];

    /**
     * @var null|Proxy\LazyServiceFactory
     */
    private $lazyServicesDelegator;

    /**
     * @var string[]
     */
    private $resolvedAliases = [];

    /**
     * A list of already loaded services (this act as a local cache)
     *
     * @var array
     */
    protected $services = [];

    /**
     * Enable/disable shared instances by service name.
     *
     * Example configuration:
     *
     * 'shared' => [
     *     MyService::class => true, // will be shared, even if "sharedByDefault" is false
     *     MyOtherService::class => false // won't be shared, even if "sharedByDefault" is true
     * ]
     *
     * @var boolean[]
     */
    protected $shared = [];

    /**
     * Should the services be shared by default?
     *
     * @var bool
     */
    protected $sharedByDefault = true;

    /**
     * Service manager was already configured?
     *
     * @var bool
     */
    protected $configured = false;

    /**
     * Cached abstract factories from string.
     *
     * @var array
     */
    private $cachedAbstractFactories = [];

    /**
     * Constructor.
     *
     * See {@see \Laminas\ServiceManager\ServiceManager::configure()} for details
     * on what $config accepts.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->creationContext = $this;
        $this->configure($config);
    }

    /**
     * Implemented for backwards compatibility with previous plugin managers only.
     *
     * Returns the creation context.
     *
     * @deprecated since 3.0.0. Factories using 3.0 should use the container
     *     instance passed to the factory instead.
     * @return ContainerInterface
     */
    public function getServiceLocator()
    {
        trigger_error(sprintf(
            'Usage of %s is deprecated since v3.0.0; please use the container passed to the factory instead',
            __METHOD__
        ), E_USER_DEPRECATED);
        return $this->creationContext;
    }

    /**
     * {@inheritDoc}
     */
    public function get($name)
    {
        $requestedName = $name;

        // We start by checking if we have cached the requested service (this
        // is the fastest method).
        if (isset($this->services[$requestedName])) {
            return $this->services[$requestedName];
        }

        $name = isset($this->resolvedAliases[$name]) ? $this->resolvedAliases[$name] : $name;

        // Next, if the alias should be shared, and we have cached the resolved
        // service, use it.
        if ($requestedName !== $name
            && (! isset($this->shared[$requestedName]) || $this->shared[$requestedName])
            && isset($this->services[$name])
        ) {
            $this->services[$requestedName] = $this->services[$name];
            return $this->services[$name];
        }

        // At this point, we need to create the instance; we use the resolved
        // name for that.
        $object = $this->doCreate($name);

        // Cache it for later, if it is supposed to be shared.
        if (($this->sharedByDefault && ! isset($this->shared[$name]))
            || (isset($this->shared[$name]) && $this->shared[$name])
        ) {
            $this->services[$name] = $object;
        }

        // Also do so for aliases; this allows sharing based on service name used.
        if ($requestedName !== $name
            && (($this->sharedByDefault && ! isset($this->shared[$requestedName]))
                || (isset($this->shared[$requestedName]) && $this->shared[$requestedName]))
        ) {
            $this->services[$requestedName] = $object;
        }

        return $object;
    }

    /**
     * {@inheritDoc}
     */
    public function build($name, array $options = null)
    {
        // We never cache when using "build"
        $name = isset($this->resolvedAliases[$name]) ? $this->resolvedAliases[$name] : $name;
        return $this->doCreate($name, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function has($name)
    {
        $name  = isset($this->resolvedAliases[$name]) ? $this->resolvedAliases[$name] : $name;
        $found = isset($this->services[$name]) || isset($this->factories[$name]);

        if ($found) {
            return $found;
        }

        // Check abstract factories
        foreach ($this->abstractFactories as $abstractFactory) {
            if ($abstractFactory->canCreate($this->creationContext, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicate whether or not the instance is immutable.
     *
     * @param bool $flag
     */
    public function setAllowOverride($flag)
    {
        $this->allowOverride = (bool) $flag;
    }

    /**
     * Retrieve the flag indicating immutability status.
     *
     * @return bool
     */
    public function getAllowOverride()
    {
        return $this->allowOverride;
    }

    /**
     * Configure the service manager
     *
     * Valid top keys are:
     *
     * - services: service name => service instance pairs
     * - invokables: service name => class name pairs for classes that do not
     *   have required constructor arguments; internally, maps the class to an
     *   InvokableFactory instance, and creates an alias if the service name
     *   and class name do not match.
     * - factories: service name => factory pairs; factories may be any
     *   callable, string name resolving to an invokable class, or string name
     *   resolving to a FactoryInterface instance.
     * - abstract_factories: an array of abstract factories; these may be
     *   instances of AbstractFactoryInterface, or string names resolving to
     *   classes that implement that interface.
     * - delegators: service name => list of delegator factories for the given
     *   service; each item in the list may be a callable, a string name
     *   resolving to an invokable class, or a string name resolving to a class
     *   implementing DelegatorFactoryInterface.
     * - shared: service name => flag pairs; the flag is a boolean indicating
     *   whether or not the service is shared.
     * - aliases: alias => service name pairs.
     * - lazy_services: lazy service configuration; can contain the keys:
     *   - class_map: service name => class name pairs.
     *   - proxies_namespace: string namespace to use for generated proxy
     *     classes.
     *   - proxies_target_dir: directory in which to write generated proxy
     *     classes; uses system temporary by default.
     *   - write_proxy_files: boolean indicating whether generated proxy
     *     classes should be written; defaults to boolean false.
     * - shared_by_default: boolean, indicating if services in this instance
     *   should be shared by default.
     *
     * @param  array $config
     * @return self
     * @throws ContainerModificationsNotAllowedException if the allow
     *     override flag has been toggled off, and a service instance
     *     exists for a given service.
     */
    public function configure(array $config)
    {
        $this->validateOverrides($config);

        if (isset($config['services'])) {
            $this->services = $config['services'] + $this->services;
        }

        if (isset($config['invokables']) && ! empty($config['invokables'])) {
            $aliases   = $this->createAliasesForInvokables($config['invokables']);
            $factories = $this->createFactoriesForInvokables($config['invokables']);

            if (! empty($aliases)) {
                $config['aliases'] = (isset($config['aliases']))
                    ? array_merge($config['aliases'], $aliases)
                    : $aliases;
            }

            $config['factories'] = (isset($config['factories']))
                ? array_merge($config['factories'], $factories)
                : $factories;
        }

        if (isset($config['factories'])) {
            $this->factories = $config['factories'] + $this->factories;
        }

        if (isset($config['delegators'])) {
            $this->delegators = array_merge_recursive($this->delegators, $config['delegators']);
        }

        if (isset($config['shared'])) {
            $this->shared = $config['shared'] + $this->shared;
        }

        if (isset($config['aliases'])) {
            $this->configureAliases($config['aliases']);
        } elseif (! $this->configured && ! empty($this->aliases)) {
            $this->resolveAliases($this->aliases);
        }

        if (isset($config['shared_by_default'])) {
            $this->sharedByDefault = $config['shared_by_default'];
        }

        // If lazy service configuration was provided, reset the lazy services
        // delegator factory.
        if (isset($config['lazy_services']) && ! empty($config['lazy_services'])) {
            $this->lazyServices          = array_merge_recursive($this->lazyServices, $config['lazy_services']);
            $this->lazyServicesDelegator = null;
        }

        // For abstract factories and initializers, we always directly
        // instantiate them to avoid checks during service construction.
        if (isset($config['abstract_factories'])) {
            $this->resolveAbstractFactories($config['abstract_factories']);
        }

        if (isset($config['initializers'])) {
            $this->resolveInitializers($config['initializers']);
        }

        $this->configured = true;

        return $this;
    }

    /**
     * @param string[] $aliases
     *
     * @return void
     */
    private function configureAliases(array $aliases)
    {
        if (! $this->configured) {
            $this->aliases = $aliases + $this->aliases;

            $this->resolveAliases($this->aliases);

            return;
        }

        // Performance optimization. If there are no collisions, then we don't need to recompute loops
        $intersecting  = $this->aliases && \array_intersect_key($this->aliases, $aliases);
        $this->aliases = $this->aliases ? \array_merge($this->aliases, $aliases) : $aliases;

        if ($intersecting) {
            $this->resolveAliases($this->aliases);

            return;
        }

        $this->resolveAliases($aliases);
        $this->resolveNewAliasesWithPreviouslyResolvedAliases($aliases);
    }

    /**
     * Add an alias.
     *
     * @param string $alias
     * @param string $target
     */
    public function setAlias($alias, $target)
    {
        $this->configure(['aliases' => [$alias => $target]]);
    }

    /**
     * Add an invokable class mapping.
     *
     * @param string $name Service name
     * @param null|string $class Class to which to map; if omitted, $name is
     *     assumed.
     */
    public function setInvokableClass($name, $class = null)
    {
        $this->configure(['invokables' => [$name => $class ?: $name]]);
    }

    /**
     * Specify a factory for a given service name.
     *
     * @param string $name Service name
     * @param string|callable|Factory\FactoryInterface $factory Factory to which
     *     to map.
     */
    public function setFactory($name, $factory)
    {
        $this->configure(['factories' => [$name => $factory]]);
    }

    /**
     * Create a lazy service mapping to a class.
     *
     * @param string $name Service name to map
     * @param null|string $class Class to which to map; if not provided, $name
     *     will be used for the mapping.
     */
    public function mapLazyService($name, $class = null)
    {
        $this->configure(['lazy_services' => ['class_map' => [$name => $class ?: $name]]]);
    }

    /**
     * Add an abstract factory for resolving services.
     *
     * @param string|Factory\AbstractFactoryInterface $factory Service name
     */
    public function addAbstractFactory($factory)
    {
        $this->configure(['abstract_factories' => [$factory]]);
    }

    /**
     * Add a delegator for a given service.
     *
     * @param string $name Service name
     * @param string|callable|Factory\DelegatorFactoryInterface $factory Delegator
     *     factory to assign.
     */
    public function addDelegator($name, $factory)
    {
        $this->configure(['delegators' => [$name => [$factory]]]);
    }

    /**
     * Add an initializer.
     *
     * @param string|callable|Initializer\InitializerInterface $initializer
     */
    public function addInitializer($initializer)
    {
        $this->configure(['initializers' => [$initializer]]);
    }

    /**
     * Map a service.
     *
     * @param string $name Service name
     * @param array|object $service
     */
    public function setService($name, $service)
    {
        $this->configure(['services' => [$name => $service]]);
    }

    /**
     * Add a service sharing rule.
     *
     * @param string $name Service name
     * @param boolean $flag Whether or not the service should be shared.
     */
    public function setShared($name, $flag)
    {
        $this->configure(['shared' => [$name => (bool) $flag]]);
    }

    /**
     * Instantiate abstract factories for to avoid checks during service construction.
     *
     * @param string[]|Factory\AbstractFactoryInterface[] $abstractFactories
     *
     * @return void
     */
    private function resolveAbstractFactories(array $abstractFactories)
    {
        foreach ($abstractFactories as $abstractFactory) {
            if (is_string($abstractFactory) && class_exists($abstractFactory)) {
                //Cached string
                if (! isset($this->cachedAbstractFactories[$abstractFactory])) {
                    $this->cachedAbstractFactories[$abstractFactory] = new $abstractFactory();
                }

                $abstractFactory = $this->cachedAbstractFactories[$abstractFactory];
            }

            if ($abstractFactory instanceof Factory\AbstractFactoryInterface) {
                $abstractFactoryObjHash = spl_object_hash($abstractFactory);
                $this->abstractFactories[$abstractFactoryObjHash] = $abstractFactory;
                continue;
            }

            // Error condition; let's find out why.

            // If we still have a string, we have a class name that does not resolve
            if (is_string($abstractFactory)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'An invalid abstract factory was registered; resolved to class "%s" ' .
                        'which does not exist; please provide a valid class name resolving ' .
                        'to an implementation of %s',
                        $abstractFactory,
                        AbstractFactoryInterface::class
                    )
                );
            }

            // Otherwise, we have an invalid type.
            throw new InvalidArgumentException(
                sprintf(
                    'An invalid abstract factory was registered. Expected an instance of "%s", ' .
                    'but "%s" was received',
                    AbstractFactoryInterface::class,
                    (is_object($abstractFactory) ? get_class($abstractFactory) : gettype($abstractFactory))
                )
            );
        }
    }

    /**
     * Instantiate initializers for to avoid checks during service construction.
     *
     * @param string[]|Initializer\InitializerInterface[]|callable[] $initializers
     *
     * @return void
     */
    private function resolveInitializers(array $initializers)
    {
        foreach ($initializers as $initializer) {
            if (is_string($initializer) && class_exists($initializer)) {
                $initializer = new $initializer();
            }

            if (is_callable($initializer)) {
                $this->initializers[] = $initializer;
                continue;
            }

            // Error condition; let's find out why.

            if (is_string($initializer)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'An invalid initializer was registered; resolved to class or function "%s" ' .
                        'which does not exist; please provide a valid function name or class ' .
                        'name resolving to an implementation of %s',
                        $initializer,
                        Initializer\InitializerInterface::class
                    )
                );
            }

            // Otherwise, we have an invalid type.
            throw new InvalidArgumentException(
                sprintf(
                    'An invalid initializer was registered. Expected a callable, or an instance of ' .
                    '(or string class name resolving to) "%s", ' .
                    'but "%s" was received',
                    Initializer\InitializerInterface::class,
                    (is_object($initializer) ? get_class($initializer) : gettype($initializer))
                )
            );
        }
    }

    /**
     * Resolve aliases to their canonical service names.
     *
     * @param string[] $aliases
     *
     * @return void
     */
    private function resolveAliases(array $aliases)
    {
        foreach ($aliases as $alias => $service) {
            $visited = [];
            $name    = $alias;

            while (isset($this->aliases[$name])) {
                if (isset($visited[$name])) {
                    throw CyclicAliasException::fromAliasesMap($aliases);
                }

                $visited[$name] = true;
                $name           = $this->aliases[$name];
            }

            $this->resolvedAliases[$alias] = $name;
        }
    }

    /**
     * Rewrites the map of aliases by resolving the given $aliases with the existing resolved ones.
     * This is mostly done for performance reasons.
     *
     * @param string[] $aliases
     *
     * @return void
     */
    private function resolveNewAliasesWithPreviouslyResolvedAliases(array $aliases)
    {
        foreach ($this->resolvedAliases as $name => $target) {
            if (isset($aliases[$target])) {
                $this->resolvedAliases[$name] = $this->resolvedAliases[$target];
            }
        }
    }

    /**
     * Get a factory for the given service name
     *
     * @param  string $name
     * @return callable
     * @throws ServiceNotFoundException
     */
    private function getFactory($name)
    {
        $factory = isset($this->factories[$name]) ? $this->factories[$name] : null;

        $lazyLoaded = false;
        if (is_string($factory) && class_exists($factory)) {
            $factory = new $factory();
            $lazyLoaded = true;
        }

        if (is_callable($factory)) {
            if ($lazyLoaded) {
                $this->factories[$name] = $factory;
            }
            // PHP 5.6 fails on 'class::method' callables unless we explode them:
            if (PHP_MAJOR_VERSION < 7
                && is_string($factory) && strpos($factory, '::') !== false
            ) {
                $factory = explode('::', $factory);
            }
            return $factory;
        }

        // Check abstract factories
        foreach ($this->abstractFactories as $abstractFactory) {
            if ($abstractFactory->canCreate($this->creationContext, $name)) {
                return $abstractFactory;
            }
        }

        throw new ServiceNotFoundException(sprintf(
            'Unable to resolve service "%s" to a factory; are you certain you provided it during configuration?',
            $name
        ));
    }

    /**
     * @param  string     $name
     * @param  null|array $options
     * @return object
     */
    private function createDelegatorFromName($name, array $options = null)
    {
        $creationCallback = function () use ($name, $options) {
            // Code is inlined for performance reason, instead of abstracting the creation
            $factory = $this->getFactory($name);
            return $factory($this->creationContext, $name, $options);
        };

        foreach ($this->delegators[$name] as $index => $delegatorFactory) {
            $delegatorFactory = $this->delegators[$name][$index];

            if ($delegatorFactory === Proxy\LazyServiceFactory::class) {
                $delegatorFactory = $this->createLazyServiceDelegatorFactory();
            }

            if (is_string($delegatorFactory) && class_exists($delegatorFactory)) {
                $delegatorFactory = new $delegatorFactory();
            }

            if (! is_callable($delegatorFactory)) {
                if (is_string($delegatorFactory)) {
                    throw new ServiceNotCreatedException(sprintf(
                        'An invalid delegator factory was registered; resolved to class or function "%s" '
                        . 'which does not exist; please provide a valid function name or class name resolving '
                        . 'to an implementation of %s',
                        $delegatorFactory,
                        DelegatorFactoryInterface::class
                    ));
                }

                throw new ServiceNotCreatedException(sprintf(
                    'A non-callable delegator, "%s", was provided; expected a callable or instance of "%s"',
                    is_object($delegatorFactory) ? get_class($delegatorFactory) : gettype($delegatorFactory),
                    DelegatorFactoryInterface::class
                ));
            }

            $this->delegators[$name][$index] = $delegatorFactory;

            $creationCallback = function () use ($delegatorFactory, $name, $creationCallback, $options) {
                return $delegatorFactory($this->creationContext, $name, $creationCallback, $options);
            };
        }

        return $creationCallback($this->creationContext, $name, $creationCallback, $options);
    }

    /**
     * Create a new instance with an already resolved name
     *
     * This is a highly performance sensitive method, do not modify if you have not benchmarked it carefully
     *
     * @param  string     $resolvedName
     * @param  null|array $options
     * @return mixed
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    private function doCreate($resolvedName, array $options = null)
    {
        try {
            if (! isset($this->delegators[$resolvedName])) {
                // Let's create the service by fetching the factory
                $factory = $this->getFactory($resolvedName);
                $object  = $factory($this->creationContext, $resolvedName, $options);
            } else {
                $object = $this->createDelegatorFromName($resolvedName, $options);
            }
        } catch (ContainerException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw new ServiceNotCreatedException(sprintf(
                'Service with name "%s" could not be created. Reason: %s',
                $resolvedName,
                $exception->getMessage()
            ), (int) $exception->getCode(), $exception);
        }

        foreach ($this->initializers as $initializer) {
            $initializer($this->creationContext, $object);
        }

        return $object;
    }

    /**
     * Create the lazy services delegator factory.
     *
     * Creates the lazy services delegator factory based on the lazy_services
     * configuration present.
     *
     * @return Proxy\LazyServiceFactory
     * @throws ServiceNotCreatedException when the lazy service class_map
     *     configuration is missing
     */
    private function createLazyServiceDelegatorFactory()
    {
        if ($this->lazyServicesDelegator) {
            return $this->lazyServicesDelegator;
        }

        if (! isset($this->lazyServices['class_map'])) {
            throw new ServiceNotCreatedException('Missing "class_map" config key in "lazy_services"');
        }

        $factoryConfig = new ProxyConfiguration();

        if (isset($this->lazyServices['proxies_namespace'])) {
            $factoryConfig->setProxiesNamespace($this->lazyServices['proxies_namespace']);
        }

        if (isset($this->lazyServices['proxies_target_dir'])) {
            $factoryConfig->setProxiesTargetDir($this->lazyServices['proxies_target_dir']);
        }

        if (! isset($this->lazyServices['write_proxy_files']) || ! $this->lazyServices['write_proxy_files']) {
            $factoryConfig->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        } else {
            $factoryConfig->setGeneratorStrategy(new FileWriterGeneratorStrategy(
                new FileLocator($factoryConfig->getProxiesTargetDir())
            ));
        }

        spl_autoload_register($factoryConfig->getProxyAutoloader());

        $this->lazyServicesDelegator = new Proxy\LazyServiceFactory(
            new LazyLoadingValueHolderFactory($factoryConfig),
            $this->lazyServices['class_map']
        );

        return $this->lazyServicesDelegator;
    }

    /**
     * Create aliases for invokable classes.
     *
     * If an invokable service name does not match the class it maps to, this
     * creates an alias to the class (which will later be mapped as an
     * invokable factory).
     *
     * @param array $invokables
     * @return array
     */
    private function createAliasesForInvokables(array $invokables)
    {
        $aliases = [];
        foreach ($invokables as $name => $class) {
            if ($name === $class) {
                continue;
            }
            $aliases[$name] = $class;
        }
        return $aliases;
    }

    /**
     * Create invokable factories for invokable classes.
     *
     * If an invokable service name does not match the class it maps to, this
     * creates an invokable factory entry for the class name; otherwise, it
     * creates an invokable factory for the entry name.
     *
     * @param array $invokables
     * @return array
     */
    private function createFactoriesForInvokables(array $invokables)
    {
        $factories = [];
        foreach ($invokables as $name => $class) {
            if ($name === $class) {
                $factories[$name] = Factory\InvokableFactory::class;
                continue;
            }

            $factories[$class] = Factory\InvokableFactory::class;
        }
        return $factories;
    }

    /**
     * Determine if one or more services already exist in the container.
     *
     * If the allow override flag is true or it's first time configured,
     * this method does nothing.
     *
     * Otherwise, it checks against each of the following service types,
     * if present, and validates that none are defining services that
     * already exist; if they do, it raises an exception indicating
     * modification is not allowed.
     *
     * @param array $config
     * @throws ContainerModificationsNotAllowedException if any services
     *     provided already have instances available.
     */
    private function validateOverrides(array $config)
    {
        if ($this->allowOverride || ! $this->configured) {
            return;
        }

        if (isset($config['services'])) {
            $this->validateOverrideSet(array_keys($config['services']), 'service');
        }

        if (isset($config['aliases'])) {
            $this->validateOverrideSet(array_keys($config['aliases']), 'alias');
        }

        if (isset($config['invokables'])) {
            $this->validateOverrideSet(array_keys($config['invokables']), 'invokable class');
        }

        if (isset($config['factories'])) {
            $this->validateOverrideSet(array_keys($config['factories']), 'factory');
        }

        if (isset($config['delegators'])) {
            $this->validateOverrideSet(array_keys($config['delegators']), 'delegator');
        }

        if (isset($config['shared'])) {
            $this->validateOverrideSet(array_keys($config['shared']), 'sharing rule');
        }

        if (isset($config['lazy_services']['class_map'])) {
            $this->validateOverrideSet(array_keys($config['lazy_services']['class_map']), 'lazy service');
        }
    }

    /**
     * Determine if one or more services already exist for a given type.
     *
     * Loops through the provided service names, checking if any have current
     * service instances; if not, it returns, but otherwise, it raises an
     * exception indicating modification is not allowed.
     *
     * @param string[] $services
     * @param string $type Type of service being checked.
     * @throws ContainerModificationsNotAllowedException if any services
     *     provided already have instances available.
     */
    private function validateOverrideSet(array $services, $type)
    {
        $detected = [];
        foreach ($services as $service) {
            if (isset($this->services[$service])) {
                $detected[] = $service;
            }
        }

        if (empty($detected)) {
            return;
        }

        throw new ContainerModificationsNotAllowedException(sprintf(
            'An updated/new %s is not allowed, as the container does not allow '
            . 'changes for services with existing instances; the following '
            . 'already exist in the container: %s',
            $type,
            implode(', ', $detected)
        ));
    }
}
