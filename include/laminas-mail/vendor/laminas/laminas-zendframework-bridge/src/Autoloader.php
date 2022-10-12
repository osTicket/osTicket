<?php

namespace Laminas\ZendFrameworkBridge;

use ArrayObject;
use Composer\Autoload\ClassLoader;
use RuntimeException;

use function array_values;
use function class_alias;
use function class_exists;
use function explode;
use function file_exists;
use function getenv;
use function interface_exists;
use function spl_autoload_register;
use function strlen;
use function strtr;
use function substr;
use function trait_exists;

/**
 * Alias legacy Zend Framework project classes/interfaces/traits to Laminas equivalents.
 */
class Autoloader
{
    private const UPSTREAM_COMPOSER_VENDOR_DIRECTORY = __DIR__ . '/../../..';
    private const LOCAL_COMPOSER_VENDOR_DIRECTORY = __DIR__ . '/../vendor';

    /**
     * Attach autoloaders for managing legacy ZF artifacts.
     *
     * We attach two autoloaders:
     *
     * - The first is _prepended_ to handle new classes and add aliases for
     *   legacy classes. PHP expects any interfaces implemented, classes
     *   extended, or traits used when declaring class_alias() to exist and/or
     *   be autoloadable already at the time of declaration. If not, it will
     *   raise a fatal error. This autoloader helps mitigate errors in such
     *   situations.
     *
     * - The second is _appended_ in order to create aliases for legacy
     *   classes.
     * @return void
     */
    public static function load()
    {
        $loaded = new ArrayObject([]);
        $classLoader = self::getClassLoader();

        if ($classLoader === null) {
            return;
        }

        spl_autoload_register(self::createPrependAutoloader(
            RewriteRules::namespaceReverse(),
            $classLoader,
            $loaded
        ), true, true);

        spl_autoload_register(self::createAppendAutoloader(
            RewriteRules::namespaceRewrite(),
            $loaded
        ));
    }

    private static function getClassLoader(): ?ClassLoader
    {
        $composerVendorDirectory = getenv('COMPOSER_VENDOR_DIR');
        if (is_string($composerVendorDirectory)) {
            return self::getClassLoaderFromVendorDirectory($composerVendorDirectory);
        }

        return self::getClassLoaderFromVendorDirectory(self::UPSTREAM_COMPOSER_VENDOR_DIRECTORY)
            ?? self::getClassLoaderFromVendorDirectory(self::LOCAL_COMPOSER_VENDOR_DIRECTORY);
    }

    /**
     * @param array<string,string> $namespaces
     * @return callable(string): void
     */
    private static function createPrependAutoloader(array $namespaces, ClassLoader $classLoader, ArrayObject $loaded)
    {
        /**
         * @param string $class Class name to autoload
         * @return void
         */
        return static function ($class) use ($namespaces, $classLoader, $loaded): void {
            if (isset($loaded[$class])) {
                return;
            }

            $segments = explode('\\', $class);

            $i = 0;
            $check = '';

            while (isset($segments[$i + 1], $namespaces[$check . $segments[$i] . '\\'])) {
                $check .= $segments[$i] . '\\';
                ++$i;
            }

            if ($check === '') {
                return;
            }

            if ($classLoader->loadClass($class)) {
                $legacy = $namespaces[$check]
                    . strtr(substr($class, strlen($check)), [
                        'ApiTools' => 'Apigility',
                        'Mezzio' => 'Expressive',
                        'Laminas' => 'Zend',
                    ]);
                class_alias($class, $legacy);
            }
        };
    }

    /**
     * @param array<string,string> $namespaces
     * @return callable(string): void
     */
    private static function createAppendAutoloader(array $namespaces, ArrayObject $loaded)
    {
        /**
         * @param  string $class Class name to autoload
         * @return void
         */
        return static function ($class) use ($namespaces, $loaded) {
            $segments = explode('\\', $class);

            if ($segments[0] === 'ZendService' && isset($segments[1])) {
                $segments[0] .= '\\' . $segments[1];
                unset($segments[1]);
                /** @psalm-suppress RedundantFunctionCall */
                $segments = array_values($segments);
            }

            $i = 0;
            $check = '';

            // We are checking segments of the namespace to match quicker
            while (isset($segments[$i + 1], $namespaces[$check . $segments[$i] . '\\'])) {
                $check .= $segments[$i] . '\\';
                ++$i;
            }

            if ($check === '') {
                return;
            }

            $alias = $namespaces[$check]
                . strtr(substr($class, strlen($check)), [
                    'Apigility' => 'ApiTools',
                    'Expressive' => 'Mezzio',
                    'Zend' => 'Laminas',
                    'AbstractZendServer' => 'AbstractZendServer',
                    'ZendServerDisk' => 'ZendServerDisk',
                    'ZendServerShm' => 'ZendServerShm',
                    'ZendMonitor' => 'ZendMonitor',
                ]);

            $loaded[$alias] = true;
            if (class_exists($alias) || interface_exists($alias) || trait_exists($alias)) {
                class_alias($alias, $class);
            }
        };
    }

    private static function getClassLoaderFromVendorDirectory(string $composerVendorDirectory): ?ClassLoader
    {
        $filename = rtrim($composerVendorDirectory, '/') . '/autoload.php';
        if (!file_exists($filename)) {
            return null;
        }

        /** @psalm-suppress MixedAssignment */
        $loader = include $filename;
        if (!$loader instanceof ClassLoader) {
            return null;
        }

        return $loader;
    }
}
