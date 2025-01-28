<?php

namespace Laminas\Mail\Header;

use function in_array;
use function strlen;

/**
 * @internal
 */
class ListParser
{
    public const CHAR_QUOTES = ['\'', '"'];
    public const CHAR_DELIMS = [',', ';'];
    public const CHAR_ESCAPE = '\\';

    /**
     * @param string $value
     * @param array $delims Delimiters allowed between values; parser will
     *     split on these, as long as they are not within quotes. Defaults
     *     to ListParser::CHAR_DELIMS.
     * @return array
     */
    public static function parse($value, array $delims = self::CHAR_DELIMS)
    {
        $values            = [];
        $length            = strlen($value);
        $currentValue      = '';
        $inEscape          = false;
        $inQuote           = false;
        $currentQuoteDelim = null;

        for ($i = 0; $i < $length; $i += 1) {
            $char = $value[$i];

            // If we are in an escape sequence, append the character and continue.
            if ($inEscape) {
                $currentValue .= $char;
                $inEscape      = false;
                continue;
            }

            // If we are not in a quoted string, and have a delimiter, append
            // the current value to the list, and reset the current value.
            if (in_array($char, $delims, true) && ! $inQuote) {
                $values []    = $currentValue;
                $currentValue = '';
                continue;
            }

            // Append the character to the current value
            $currentValue .= $char;

            // Escape sequence discovered.
            if (self::CHAR_ESCAPE === $char) {
                $inEscape = true;
                continue;
            }

            // If the character is not a quote character, we are done
            // processing it.
            if (! in_array($char, self::CHAR_QUOTES)) {
                continue;
            }

            // If the character matches a previously matched quote delimiter,
            // we reset our quote status and the currently opened quote
            // delimiter.
            if ($char === $currentQuoteDelim) {
                $inQuote           = false;
                $currentQuoteDelim = null;
                continue;
            }

            // If already in quote and the character does not match the previously
            // matched quote delimiter, we're done here.
            if ($inQuote) {
                continue;
            }

            // Otherwise, we're starting a quoted string.
            $inQuote           = true;
            $currentQuoteDelim = $char;
        }

        // If we reached the end of the string and still have a current value,
        // append it to the list (no delimiter was reached).
        if ('' !== $currentValue) {
            $values [] = $currentValue;
        }

        return $values;
    }
}
