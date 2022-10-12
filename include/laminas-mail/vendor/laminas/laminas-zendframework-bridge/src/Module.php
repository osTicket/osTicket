<?php

namespace Laminas\ZendFrameworkBridge;

use Laminas\ModuleManager\Listener\ConfigMergerInterface;
use Laminas\ModuleManager\ModuleEvent;
use Laminas\ModuleManager\ModuleManager;

class Module
{
    /**
     * Initialize the module.
     *
     * Type-hinting deliberately omitted to allow unit testing
     * without dependencies on packages that do not exist yet.
     *
     * @param ModuleManager $moduleManager
     */
    public function init($moduleManager)
    {
        $moduleManager
            ->getEventManager()
            ->attach('mergeConfig', [$this, 'onMergeConfig']);
    }

    /**
     * Perform substitutions in the merged configuration.
     *
     * Rewrites keys and values matching known ZF classes, namespaces, and
     * configuration keys to their Laminas equivalents.
     *
     * Type-hinting deliberately omitted to allow unit testing
     * without dependencies on packages that do not exist yet.
     *
     * @param ModuleEvent $event
     */
    public function onMergeConfig($event)
    {
        /** @var ConfigMergerInterface */
        $configMerger = $event->getConfigListener();
        $processor    = new ConfigPostProcessor();
        $configMerger->setMergedConfig(
            $processor(
                $configMerger->getMergedConfig($returnAsObject = false)
            )
        );
    }
}
