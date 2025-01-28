<?php

namespace Laminas\Validator\Barcode;

class Intelligentmail extends AbstractAdapter
{
    /**
     * Constructor
     *
     * Sets check flag to false.
     */
    public function __construct()
    {
        $this->setLength([20, 25, 29, 31]);
        $this->setCharacters('0123456789');
        $this->useChecksum(false);
    }
}
