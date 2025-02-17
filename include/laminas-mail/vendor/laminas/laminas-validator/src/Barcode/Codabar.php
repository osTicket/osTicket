<?php

namespace Laminas\Validator\Barcode;

use function strpbrk;
use function substr;

class Codabar extends AbstractAdapter
{
    /**
     * Constructor for this barcode adapter
     */
    public function __construct()
    {
        $this->setLength(-1);
        $this->setCharacters('0123456789-$:/.+ABCDTN*E');
        $this->useChecksum(false);
    }

    /**
     * Checks for allowed characters
     *
     * @see Laminas\Validator\Barcode.AbstractAdapter::checkChars()
     *
     * @param string $value
     * @return bool
     */
    public function hasValidCharacters($value)
    {
        if (strpbrk($value, 'ABCD') !== false) {
            $first = $value[0];
            if (strpbrk($first, 'ABCD') === false) {
                // Missing start char
                return false;
            }

            $last = substr($value, -1, 1);
            if (strpbrk($last, 'ABCD') === false) {
                // Missing stop char
                return false;
            }

            $value = substr($value, 1, -1);
        } elseif (strpbrk($value, 'TN*E') !== false) {
            $first = $value[0];
            if (strpbrk($first, 'TN*E') === false) {
                // Missing start char
                return false;
            }

            $last = substr($value, -1, 1);
            if (strpbrk($last, 'TN*E') === false) {
                // Missing stop char
                return false;
            }

            $value = substr($value, 1, -1);
        }

        $chars = $this->getCharacters();
        $this->setCharacters('0123456789-$:/.+');
        $result = parent::hasValidCharacters($value);
        $this->setCharacters($chars);
        return $result;
    }
}
