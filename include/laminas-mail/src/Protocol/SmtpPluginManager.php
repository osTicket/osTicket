<?php

/**
 * @see       https://github.com/laminas/laminas-mail for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mail/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mail/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mail\Protocol;

use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\ServiceManager\Factory\InvokableFactory;

/**
 * Plugin manager implementation for SMTP extensions.
 *
 * Enforces that SMTP extensions retrieved are instances of Smtp. Additionally,
 * it registers a number of default extensions available.
 */
class SmtpPluginManager extends AbstractPluginManager
{
    /**
     * Service aliases
     */
    protected $aliases = [
        'crammd5' => Smtp\Auth\Crammd5::class,
        'cramMd5' => Smtp\Auth\Crammd5::class,
        'CramMd5' => Smtp\Auth\Crammd5::class,
        'cramMD5' => Smtp\Auth\Crammd5::class,
        'CramMD5' => Smtp\Auth\Crammd5::class,
        'login'   => Smtp\Auth\Login::class,
        'Login'   => Smtp\Auth\Login::class,
        'plain'   => Smtp\Auth\Plain::class,
        'Plain'   => Smtp\Auth\Plain::class,
        'smtp'    => Smtp::class,
        'Smtp'    => Smtp::class,
        'SMTP'    => Smtp::class,

        // Legacy Zend Framework aliases
        \Zend\Mail\Protocol\Smtp\Auth\Crammd5::class => Smtp\Auth\Crammd5::class,
        \Zend\Mail\Protocol\Smtp\Auth\Login::class => Smtp\Auth\Login::class,
        \Zend\Mail\Protocol\Smtp\Auth\Plain::class => Smtp\Auth\Plain::class,
        \Zend\Mail\Protocol\Smtp::class => Smtp::class,

        // v2 normalized FQCNs
        'zendmailprotocolsmtpauthcrammd5' => Smtp\Auth\Crammd5::class,
        'zendmailprotocolsmtpauthlogin' => Smtp\Auth\Login::class,
        'zendmailprotocolsmtpauthplain' => Smtp\Auth\Plain::class,
        'zendmailprotocolsmtp' => Smtp::class,
    ];

    /**
     * Service factories
     *
     * @var array
     */
    protected $factories = [
        Smtp\Auth\Crammd5::class => InvokableFactory::class,
        Smtp\Auth\Login::class   => InvokableFactory::class,
        Smtp\Auth\Plain::class   => InvokableFactory::class,
        Smtp::class              => InvokableFactory::class,

        // v2 normalized service names

        'laminasmailprotocolsmtpauthcrammd5' => InvokableFactory::class,
        'laminasmailprotocolsmtpauthlogin'   => InvokableFactory::class,
        'laminasmailprotocolsmtpauthplain'   => InvokableFactory::class,
        'laminasmailprotocolsmtp'            => InvokableFactory::class,
    ];

    /**
     * Plugins must be an instance of the Smtp class
     *
     * @var string
     */
    protected $instanceOf = Smtp::class;

    /**
     * Validate a retrieved plugin instance (v3).
     *
     * @param object $plugin
     * @throws InvalidServiceException
     */
    public function validate($plugin)
    {
        if (! $plugin instanceof $this->instanceOf) {
            throw new InvalidServiceException(sprintf(
                'Plugin of type %s is invalid; must extend %s',
                (is_object($plugin) ? get_class($plugin) : gettype($plugin)),
                Smtp::class
            ));
        }
    }

    /**
     * Validate a retrieved plugin instance (v2).
     *
     * @param object $plugin
     * @throws Exception\InvalidArgumentException
     */
    public function validatePlugin($plugin)
    {
        try {
            $this->validate($plugin);
        } catch (InvalidServiceException $e) {
            throw new Exception\InvalidArgumentException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
