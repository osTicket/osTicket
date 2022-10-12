<?php

namespace Laminas\Mail\Protocol;

use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\ConfigInterface;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Zend\Mail\Protocol\Smtp\Auth\Crammd5;
use Zend\Mail\Protocol\Smtp\Auth\Login;
use Zend\Mail\Protocol\Smtp\Auth\Plain;

use function gettype;
use function is_object;
use function sprintf;

/**
 * Plugin manager implementation for SMTP extensions.
 *
 * Enforces that SMTP extensions retrieved are instances of Smtp. Additionally,
 * it registers a number of default extensions available.
 *
 * @link ConfigInterface
 *
 * @psalm-import-type FactoriesConfigurationType from ConfigInterface
 *
 * @template InstanceType of Smtp
 * @extends AbstractPluginManager<InstanceType>
 */
class SmtpPluginManager extends AbstractPluginManager
{
    /**
     * Service aliases
     *
     * @var array<array-key, class-string>
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
        Crammd5::class                  => Smtp\Auth\Crammd5::class,
        Login::class                    => Smtp\Auth\Login::class,
        Plain::class                    => Smtp\Auth\Plain::class,
        \Zend\Mail\Protocol\Smtp::class => Smtp::class,

        // v2 normalized FQCNs
        'zendmailprotocolsmtpauthcrammd5' => Smtp\Auth\Crammd5::class,
        'zendmailprotocolsmtpauthlogin'   => Smtp\Auth\Login::class,
        'zendmailprotocolsmtpauthplain'   => Smtp\Auth\Plain::class,
        'zendmailprotocolsmtp'            => Smtp::class,
    ];

    /**
     * Service factories
     *
     * @var FactoriesConfigurationType
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
     * {@inheritDoc}
     */
    public function validate($instance)
    {
        if (! $instance instanceof $this->instanceOf) {
            throw new InvalidServiceException(sprintf(
                'Plugin of type %s is invalid; must extend %s',
                is_object($instance) ? $instance::class : gettype($instance),
                $this->instanceOf
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
