<?php

namespace Laminas\Validator\Barcode;

class Code25 extends AbstractAdapter
{
    /**
     * Constructor for this barcode adapter
     */
    public function __construct()
    {
        $this->setLength(-1);
        $this->setCharacters('0123456789');
        $this->setChecksum('code25');
        $this->useChecksum(false);
    }
}
