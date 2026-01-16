<?php

namespace Laminas\Validator\Barcode;

class Postnet extends AbstractAdapter
{
    /**
     * Constructor for this barcode adapter
     */
    public function __construct()
    {
        $this->setLength([6, 7, 10, 12]);
        $this->setCharacters('0123456789');
        $this->setChecksum('postnet');
    }
}
