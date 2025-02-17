<?php

declare(strict_types=1);

namespace Laminas\Validator\Translator;

use Laminas\I18n\Translator\TranslatorInterface as I18nTranslatorInterface;
use Laminas\Validator\Translator\TranslatorInterface as ValidatorTranslatorInterface;

final class Translator implements
    I18nTranslatorInterface,
    ValidatorTranslatorInterface
{
    public function __construct(private readonly I18nTranslatorInterface $translator)
    {
    }

    /**
     * Translate a message using the given text domain and locale
     *
     * @param string $message
     * @param string $textDomain
     * @param string $locale
     * @return string
     */
    public function translate($message, $textDomain = 'default', $locale = null)
    {
        return $this->translator->translate($message, $textDomain, $locale);
    }

    /**
     * Provide a pluralized translation of the given string using the given text domain and locale
     *
     * @param string $singular
     * @param string $plural
     * @param int $number
     * @param string $textDomain
     * @param string $locale
     * @return string
     */
    public function translatePlural($singular, $plural, $number, $textDomain = 'default', $locale = null)
    {
        return $this->translator->translatePlural($singular, $plural, $number, $textDomain, $locale);
    }
}
