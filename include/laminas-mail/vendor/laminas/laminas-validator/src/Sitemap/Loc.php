<?php

namespace Laminas\Validator\Sitemap;

use Laminas\Uri;
use Laminas\Validator\AbstractValidator;

use function is_string;

/**
 * Validates whether a given value is valid as a sitemap <loc> value
 *
 * @link       http://www.sitemaps.org/protocol.php Sitemaps XML format
 * @see        Laminas\Uri\Uri
 */
class Loc extends AbstractValidator
{
    /**
     * Validation key for not valid
     */
    public const NOT_VALID = 'sitemapLocNotValid';
    public const INVALID   = 'sitemapLocInvalid';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $messageTemplates = [
        self::NOT_VALID => 'The input is not a valid sitemap location',
        self::INVALID   => 'Invalid type given. String expected',
    ];

    /**
     * Validates if a string is valid as a sitemap location
     *
     * @link http://www.sitemaps.org/protocol.php#locdef <loc>
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
        $uri = Uri\UriFactory::factory($value);
        if (! $uri->isValid()) {
            $this->error(self::NOT_VALID);
            return false;
        }

        return true;
    }
}
