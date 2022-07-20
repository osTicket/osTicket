<?php

/**
 * @see       https://github.com/laminas/laminas-crypt for the canonical source repository
 * @copyright https://github.com/laminas/laminas-crypt/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-crypt/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Crypt\Symmetric\Padding;

/**
 * No Padding
 */
class NoPadding implements PaddingInterface
{
    /**
     * Pad a string, do nothing and return the string
     *
     * @param  string $string
     * @param  int    $blockSize
     * @return string
     */
    public function pad($string, $blockSize = 32)
    {
        return $string;
    }

    /**
     * Unpad a string, do nothing and return the string
     *
     * @param  string $string
     * @return string
     */
    public function strip($string)
    {
        return $string;
    }
}
