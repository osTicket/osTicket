<?php

namespace Laminas\Validator\Barcode;

use function strlen;

class Ean8 extends AbstractAdapter
{
    /**
     * Constructor for this barcode adapter
     */
    public function __construct()
    {
        $this->setLength([7, 8]);
        $this->setCharacters('0123456789');
        $this->setChecksum('gtin');
    }

    /**
     * Overrides parent checkLength
     *
     * @param string $value Value
     * @return bool
     */
    public function hasValidLength($value)
    {
        if (strlen($value) === 7) {
            $this->useChecksum(false);
        } else {
            $this->useChecksum(true);
        }

        return parent::hasValidLength($value);
    }
}
