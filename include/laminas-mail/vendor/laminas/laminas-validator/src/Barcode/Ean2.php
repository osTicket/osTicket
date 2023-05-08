<?php

namespace Laminas\Validator\Barcode;

class Ean2 extends AbstractAdapter
{
    /**
     * Constructor for this barcode adapter
     */
    public function __construct()
    {
        $this->setLength(2);
        $this->setCharacters('0123456789');
        $this->useChecksum(false);
    }
}
