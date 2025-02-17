<?php

declare(strict_types=1);

namespace Laminas\ServiceManager;

use Laminas\Stdlib\ArrayUtils;

use function array_keys;

/**
 * Object for defining configuration and configuring an existing service manager instance.
 *
 * In order to provide configuration merging capabilities, this class implements
 * the same functionality as `Laminas\Stdlib\ArrayUtils::merge()`. That routine
 * allows developers to specifically shape how values are merged:
 *
 * - A value which is an instance of `MergeRemoveKey` indicates the value should
 *   be removed during merge.
 * - A value that is an instance of `MergeReplaceKeyInterface` indicates that the
 *   value it contains should be used to replace any previous versions.
 *
 * These features are advanced, and not typically used. If you wish to use them,
 * you will need to require the laminas-stdlib package in your application.
 *
 * @deprecated Class will be removed as of v4.0
 *
 * @psalm-import-type ServiceManagerConfigurationType from ConfigInterface
 */
class Config implements ConfigInterface
{
    /** @var array<string,bool> */
    private array $allowedKeys = [
        'abstract_factories' => true,
        'aliases'            => true,
        'delegators'         => true,
        'factories'          => true,
        'initializers'       => true,
        'invokables'         => true,
        'lazy_services'      => true,
        'services'           => true,
        'shared'             => true,
    ];

    /**
     * @var array<string,array>
     * @psalm-var ServiceManagerConfigurationType
     */
    protected $config = [
        'abstract_factories' => [],
        'aliases'            => [],
        'delegators'         => [],
        'factories'          => [],
        'initializers'       => [],
        'invokables'         => [],
        'lazy_services'      => [],
        'services'           => [],
        'shared'             => [],
    ];

    /**
     * @psalm-param ServiceManagerConfigurationType $config
     */
    public function __construct(array $config = [])
    {
        // Only merge keys we're interested in
        foreach (array_keys($config) as $key) {
            if (! isset($this->allowedKeys[$key])) {
                unset($config[$key]);
            }
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->config = $this->merge($this->config, $config);
    }

    /**
     * @inheritDoc
     */
    public function configureServiceManager(ServiceManager $serviceManager)
    {
        return $serviceManager->configure($this->config);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return $this->config;
    }

    /**
     * @psalm-param ServiceManagerConfigurationType $a
     * @psalm-param ServiceManagerConfigurationType $b
     * @psalm-return ServiceManagerConfigurationType
     */
    private function merge(array $a, array $b)
    {
        return ArrayUtils::merge($a, $b);
    }
}
