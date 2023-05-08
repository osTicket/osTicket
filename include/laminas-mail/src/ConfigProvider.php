<?php

namespace Laminas\Mail;

class ConfigProvider
{
    /**
     * Retrieve configuration for laminas-mail package.
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
     * Retrieve dependency settings for laminas-mail package.
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            // Legacy Zend Framework aliases
            'aliases'   => [
                'Zend\Mail\Protocol\SmtpPluginManager' => Protocol\SmtpPluginManager::class,
            ],
            'factories' => [
                Protocol\SmtpPluginManager::class => Protocol\SmtpPluginManagerFactory::class,
            ],
        ];
    }
}
