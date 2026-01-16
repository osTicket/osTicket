<?php

namespace Laminas\Validator\Sitemap;

use Laminas\Validator\AbstractValidator;

use function in_array;
use function is_string;

/**
 * Validates whether a given value is valid as a sitemap <changefreq> value
 *
 * @link       http://www.sitemaps.org/protocol.php Sitemaps XML format
 */
class Changefreq extends AbstractValidator
{
    /**
     * Validation key for not valid
     */
    public const NOT_VALID = 'sitemapChangefreqNotValid';
    public const INVALID   = 'sitemapChangefreqInvalid';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $messageTemplates = [
        self::NOT_VALID => 'The input is not a valid sitemap changefreq',
        self::INVALID   => 'Invalid type given. String expected',
    ];

    /**
     * Valid change frequencies
     *
     * @var array
     */
    protected $changeFreqs = [
        'always',
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'yearly',
        'never',
    ];

    /**
     * Validates if a string is valid as a sitemap changefreq
     *
     * @link http://www.sitemaps.org/protocol.php#changefreqdef <changefreq>
     *
     * @param  string  $value  value to validate
     * @return bool
     */
    public function isValid($value)
    {
        if (! is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);

        if (! in_array($value, $this->changeFreqs, true)) {
            $this->error(self::NOT_VALID);
            return false;
        }

        return true;
    }
}
