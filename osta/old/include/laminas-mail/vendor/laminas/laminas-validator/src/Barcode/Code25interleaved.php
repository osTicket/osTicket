<?php

namespace Laminas\Validator\Barcode;

class Code25interleaved extends AbstractAdapter
{
    /**
     * Constructor
     *
     * Sets check flag to false.
     */
    public function __construct()
    {
        $this->setLength('even');
        $this->setCharacters('0123456789');
        $this->setChecksum('code25');
        $this->useChecksum(false);
    }
}
