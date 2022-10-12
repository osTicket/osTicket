<?php

namespace Laminas\Mail;

use Zend\Mail\Protocol\SmtpPluginManager;

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
                SmtpPluginManager::class => Protocol\SmtpPluginManager::class,
            ],
            'factories' => [
                Protocol\SmtpPluginManager::class => Protocol\SmtpPluginManagerFactory::class,
            ],
        ];
    }
}
