<?php

namespace Laminas\Validator;

class ConfigProvider
{
    /**
     * Return configuration for this component.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    /**
     * Return dependency mappings for this component.
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'aliases'   => [
                Translator\TranslatorInterface::class => Translator\Translator::class,
                'ValidatorManager'                    => ValidatorPluginManager::class,

                // Legacy Zend Framework aliases
                'Zend\Validator\ValidatorPluginManager' => ValidatorPluginManager::class,
            ],
            'factories' => [
                Translator\Translator::class  => Translator\TranslatorFactory::class,
                ValidatorPluginManager::class => ValidatorPluginManagerFactory::class,
            ],
        ];
    }
}
