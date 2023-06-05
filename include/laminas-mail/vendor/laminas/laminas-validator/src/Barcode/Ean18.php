<?php

namespace Laminas\Validator\Barcode;

class Ean18 extends AbstractAdapter
{
    /**
     * Constructor for this barcode adapter
     */
    public function __construct()
    {
        $this->setLength(18);
        $this->setCharacters('0123456789');
        $this->setChecksum('gtin');
    }
}
