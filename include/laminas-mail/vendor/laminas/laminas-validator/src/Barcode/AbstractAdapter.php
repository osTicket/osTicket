<?php

namespace Laminas\Validator\Barcode;

use function chr;
use function is_array;
use function is_string;
use function method_exists;
use function str_replace;
use function str_split;
use function strlen;
use function substr;

abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * Allowed options for this adapter
     *
     * @var array{
     *     length: int|array|'even'|'odd'|null,
     *     characters: int|string|array|null,
     *     checksum: null|string,
     *     useChecksum: null|bool,
     * }
     */
    protected $options = [
        'length'      => null, // Allowed barcode lengths, integer, array, string
        'characters'  => null, // Allowed barcode characters
        'checksum'    => null, // Callback to checksum function
        'useChecksum' => true, // Is a checksum value included?, boolean
    ];

    /**
     * Checks the length of a barcode
     *
     * @param  string $value The barcode to check for proper length
     * @return bool
     */
    public function hasValidLength($value)
    {
        if (! is_string($value)) {
            return false;
        }

        $fixum  = strlen($value);
        $length = $this->getLength();

        if (is_array($length)) {
            foreach ($length as $value) {
                if ($fixum === $value) {
                    return true;
                }

                if ($value === -1) {
                    return true;
                }
            }

            return false;
        }

        if ($fixum === $length) {
            return true;
        }

        if ($length === -1) {
            return true;
        }

        if ($length === 'even') {
            $count = $fixum % 2;
            return 0 === $count;
        }

        if ($length === 'odd') {
            $count = $fixum % 2;
            return 1 === $count;
        }

        return false;
    }

    /**
     * Checks for allowed characters within the barcode
     *
     * @param  string $value The barcode to check for allowed characters
     * @return bool
     */
    public function hasValidCharacters($value)
    {
        if (! is_string($value)) {
            return false;
        }

        $characters = $this->getCharacters();
        if ($characters === 128) {
            for ($x = 0; $x < 128; ++$x) {
                $value = str_replace(chr($x), '', $value);
            }
        } else {
            $chars = str_split($characters);
            foreach ($chars as $char) {
                $value = str_replace($char, '', $value);
            }
        }

        if (strlen($value) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Validates the checksum
     *
     * @param  string $value The barcode to check the checksum for
     * @return bool
     */
    public function hasValidChecksum($value)
    {
        $checksum = $this->getChecksum();
        if ($checksum !== null) {
            if (method_exists($this, $checksum)) {
                return $this->$checksum($value);
            }
        }

        return false;
    }

    /**
     * Returns the allowed barcode length
     *
     * @return int|array|string|null
     */
    public function getLength()
    {
        return $this->options['length'];
    }

    /**
     * Returns the allowed characters
     *
     * @return int|string|array|null
     */
    public function getCharacters()
    {
        return $this->options['characters'];
    }

    /**
     * Returns the checksum function name
     *
     * @return string|null
     */
    public function getChecksum()
    {
        return $this->options['checksum'];
    }

    /**
     * Sets the checksum validation method
     *
     * @param string $checksum Checksum method to call
     * @return $this
     */
    protected function setChecksum($checksum)
    {
        $this->options['checksum'] = $checksum;
        return $this;
    }

    /**
     * Sets the checksum validation, if no value is given, the actual setting is returned
     *
     * @inheritDoc
     */
    public function useChecksum($check = null)
    {
        if ($check === null) {
            return $this->options['useChecksum'];
        }

        $this->options['useChecksum'] = (bool) $check;
        return $this;
    }

    /**
     * Sets the length of this barcode
     *
     * @param int|array $length
     * @return $this
     */
    protected function setLength($length)
    {
        $this->options['length'] = $length;
        return $this;
    }

    /**
     * Sets the allowed characters of this barcode
     *
     * @param int|string|array $characters
     * @return $this
     */
    protected function setCharacters($characters)
    {
        $this->options['characters'] = $characters;
        return $this;
    }

    /**
     * Validates the checksum (Modulo 10)
     * GTIN implementation factor 3
     *
     * @param  string $value The barcode to validate
     * @return bool
     */
    protected function gtin($value)
    {
        $barcode = substr($value, 0, -1);
        $sum     = 0;
        $length  = strlen($barcode) - 1;

        for ($i = 0; $i <= $length; $i++) {
            if (($i % 2) === 0) {
                $sum += $barcode[$length - $i] * 3;
            } else {
                $sum += $barcode[$length - $i];
            }
        }

        $calc     = $sum % 10;
        $checksum = $calc === 0 ? 0 : 10 - $calc;

        return $value[$length + 1] === (string) $checksum;
    }

    /**
     * Validates the checksum (Modulo 10)
     * IDENTCODE implementation factors 9 and 4
     *
     * @param  string $value The barcode to validate
     * @return bool
     */
    protected function identcode($value)
    {
        $barcode = substr($value, 0, -1);
        $sum     = 0;
        $length  = strlen($value) - 2;

        for ($i = 0; $i <= $length; $i++) {
            if (($i % 2) === 0) {
                $sum += $barcode[$length - $i] * 4;
            } else {
                $sum += $barcode[$length - $i] * 9;
            }
        }

        $calc     = $sum % 10;
        $checksum = $calc === 0 ? 0 : 10 - $calc;

        return $value[$length + 1] === (string) $checksum;
    }

    /**
     * Validates the checksum (Modulo 10)
     * CODE25 implementation factor 3
     *
     * @param  string $value The barcode to validate
     * @return bool
     */
    protected function code25($value)
    {
        $barcode = substr($value, 0, -1);
        $sum     = 0;
        $length  = strlen($barcode) - 1;

        for ($i = 0; $i <= $length; $i++) {
            if (($i % 2) === 0) {
                $sum += $barcode[$i] * 3;
            } else {
                $sum += $barcode[$i];
            }
        }

        $calc     = $sum % 10;
        $checksum = $calc === 0 ? 0 : 10 - $calc;

        return $value[$length + 1] === (string) $checksum;
    }

    /**
     * Validates the checksum ()
     * POSTNET implementation
     *
     * @param  string $value The barcode to validate
     * @return bool
     */
    protected function postnet($value)
    {
        $checksum = substr($value, -1, 1);
        $values   = str_split(substr($value, 0, -1));

        $check = 0;
        foreach ($values as $row) {
            $check += $row;
        }

        $check %= 10;
        $check  = 10 - $check;

        return (string) $check === $checksum;
    }
}
