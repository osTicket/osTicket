<?php

namespace Laminas\Validator\Sitemap;

use Laminas\Stdlib\ErrorHandler;
use Laminas\Validator\AbstractValidator;

use function is_string;
use function preg_match;

/**
 * Validates whether a given value is valid as a sitemap <lastmod> value
 *
 * @link       http://www.sitemaps.org/protocol.php Sitemaps XML format
 */
class Lastmod extends AbstractValidator
{
    // phpcs:disable Generic.Files.LineLength.TooLong

    /**
     * Regular expression to use when validating
     */
    public const LASTMOD_REGEX = '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])(T([0-1][0-9]|2[0-3])(:[0-5][0-9])(:[0-5][0-9])?(\\+|-)([0-1][0-9]|2[0-3]):[0-5][0-9])?$/';

    // phpcs:enable

    /**
     * Validation key for not valid
     */
    public const NOT_VALID = 'sitemapLastmodNotValid';
    public const INVALID   = 'sitemapLastmodInvalid';

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected $messageTemplates = [
        self::NOT_VALID => 'The input is not a valid sitemap lastmod',
        self::INVALID   => 'Invalid type given. String expected',
    ];

    /**
     * Validates if a string is valid as a sitemap lastmod
     *
     * @link http://www.sitemaps.org/protocol.php#lastmoddef <lastmod>
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
        ErrorHandler::start();
        $result = preg_match(self::LASTMOD_REGEX, $value);
        ErrorHandler::stop();
        if ($result !== 1) {
            $this->error(self::NOT_VALID);
            return false;
        }

        return true;
    }
}
