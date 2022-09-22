<?php

namespace Laminas\Config\Reader;

interface ReaderInterface
{
    /**
     * Read from a file and create an array
     *
     * @param  string $filename
     * @return array
     */
    public function fromFile($filename);

    /**
     * Read from a string and create an array
     *
     * @param  string $string
     * @return array|bool
     */
    public function fromString($string);
}
