<?php

declare(strict_types=1);

namespace Laminas\Validator\Translator;

use Laminas\I18n\Translator\LoaderPluginManager;
use Laminas\I18n\Translator\Translator as I18nTranslator;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;
use Traversable;

use function array_key_exists;
use function assert;
use function extension_loaded;
use function is_array;

/**
 * Overrides the translator factory from the i18n component in order to
 * replace it with the bridge class from this namespace.
 */
final class TranslatorFactory
{
    public function __invoke(ContainerInterface $container): Translator
    {
        // Assume that if a user has registered a service for the
        // TranslatorInterface, it must be valid
        if ($container->has(TranslatorInterface::class)) {
            return new Translator($container->get(TranslatorInterface::class));
        }

        return $this->marshalTranslator($container);
    }

    /**
     * Marshal an Translator.
     *
     * If configuration exists, will pass it to the I18nTranslator::factory,
     * decorating the returned instance in an MvcTranslator.
     *
     * Otherwise:
     *
     * - returns an Translator decorating a DummyTranslator instance if
     *   ext/intl is not loaded.
     * - returns an Translator decorating an empty I18nTranslator instance.
     */
    private function marshalTranslator(ContainerInterface $container): Translator
    {
        // Load a translator from configuration, if possible
        $translator = $this->marshalTranslatorFromConfig($container);

        if ($translator instanceof Translator) {
            return $translator;
        }

        // If ext/intl is not loaded, return a dummy translator
        if (! extension_loaded('intl')) {
            return new Translator(new DummyTranslator());
        }

        return new Translator(new I18nTranslator());
    }

    /**
     * Attempt to marshal a translator from configuration.
     *
     * Returns:
     * - an Translator seeded with a DummyTranslator if "translator"
     *   configuration is available, and evaluates to boolean false.
     * - an Translator seed with an I18nTranslator if "translator"
     *   configuration is available, and is a non-empty array or a Traversable
     *   instance.
     * - null in all other cases, including absence of a configuration service.
     */
    private function marshalTranslatorFromConfig(ContainerInterface $container): ?Translator
    {
        if (! $container->has('config')) {
            return null;
        }

        $config = $container->get('config');

        if (! is_array($config) || ! array_key_exists('translator', $config)) {
            return null;
        }

        // 'translator' => false
        if ($config['translator'] === false) {
            return new Translator(new DummyTranslator());
        }

        // Empty translator configuration
        if (is_array($config['translator']) && empty($config['translator'])) {
            return null;
        }

        // Unusable translator configuration
        if (! is_array($config['translator']) && ! $config['translator'] instanceof Traversable) {
            return null;
        }

        // Create translator from configuration
        $i18nTranslator = I18nTranslator::factory($config['translator']);

        // Inject plugins, if present
        if ($container->has('TranslatorPluginManager')) {
            $loaderManager = $container->get('TranslatorPluginManager');

            assert($loaderManager instanceof LoaderPluginManager);

            $i18nTranslator->setPluginManager($loaderManager);
        }

        // Inject into service manager instances
        if ($container instanceof ServiceManager) {
            $container->setService(TranslatorInterface::class, $i18nTranslator);
        }

        return new Translator($i18nTranslator);
    }
}
