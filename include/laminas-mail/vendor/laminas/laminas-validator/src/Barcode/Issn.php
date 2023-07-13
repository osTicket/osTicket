<?php

namespace Laminas\Validator\Barcode;

use function str_contains;
use function str_split;
use function strlen;
use function substr;

class Issn extends AbstractAdapter
{
    /**
     * Constructor for this barcode adapter
     */
    public function __construct()
    {
        $this->setLength([8, 13]);
        $this->setCharacters('0123456789X');
        $this->setChecksum('gtin');
    }

    /**
     * Allows X on length of 8 chars
     *
     * @param  string $value The barcode to check for allowed characters
     * @return bool
     */
    public function hasValidCharacters($value)
    {
        if (strlen($value) !== 8) {
            if (str_contains($value, 'X')) {
                return false;
            }
        }

        return parent::hasValidCharacters($value);
    }

    /**
     * Validates the checksum
     *
     * @param  string $value The barcode to check the checksum for
     * @return bool
     */
    public function hasValidChecksum($value)
    {
        if (strlen($value) === 8) {
            $this->setChecksum('issn');
        } else {
            $this->setChecksum('gtin');
        }

        return parent::hasValidChecksum($value);
    }

    /**
     * Validates the checksum ()
     * ISSN implementation (reversed mod11)
     *
     * @param  string $value The barcode to validate
     * @return bool
     */
    protected function issn($value)
    {
        $checksum = substr($value, -1, 1);
        $values   = str_split(substr($value, 0, -1));
        $check    = 0;
        $multi    = 8;
        foreach ($values as $token) {
            if ($token === 'X') {
                $token = 10;
            }

            $check += $token * $multi;
            --$multi;
        }

        $check %= 11;
        $check  = $check === 0 ? 0 : 11 - $check;

        if ((string) $check === $checksum) {
            return true;
        }

        if (($check === 10) && ($checksum === 'X')) {
            return true;
        }

        return false;
    }
}
