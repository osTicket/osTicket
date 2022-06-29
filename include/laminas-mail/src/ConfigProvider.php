<?php

/**
 * @see       https://github.com/laminas/laminas-mail for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mail/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mail/blob/master/LICENSE.md New BSD License
 */

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
            'aliases' => [
                \Zend\Mail\Protocol\SmtpPluginManager::class => Protocol\SmtpPluginManager::class,
            ],
            'factories' => [
                Protocol\SmtpPluginManager::class => Protocol\SmtpPluginManagerFactory::class,
            ],
        ];
    }
}
