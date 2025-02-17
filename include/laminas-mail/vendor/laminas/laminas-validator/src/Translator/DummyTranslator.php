<?php

declare(strict_types=1);

namespace Laminas\Validator\Translator;

use Laminas\I18n\Translator\TranslatorInterface as I18nTranslatorInterface;

/**
 * @internal
 */
final class DummyTranslator implements I18nTranslatorInterface
{
    /** @inheritDoc */
    public function translate($message, $textDomain = 'default', $locale = null)
    {
        return $message;
    }

    /** @inheritDoc */
    public function translatePlural($singular, $plural, $number, $textDomain = 'default', $locale = null)
    {
        return (int) $number === 1 ? $singular : $plural;
    }
}
