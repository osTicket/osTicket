<?php

/**
 * @see       https://github.com/laminas/laminas-crypt for the canonical source repository
 * @copyright https://github.com/laminas/laminas-crypt/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-crypt/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Crypt\Key\Derivation;

use Laminas\Crypt\Hmac;

use function ceil;
use function hash_hmac;
use function mb_substr;
use function pack;

/**
 * PKCS #5 v2.0 standard RFC 2898
 */
class Pbkdf2
{
    /**
     * Generate the new key
     *
     * @param  string  $hash       The hash algorithm to be used by HMAC
     * @param  string  $password   The source password/key
     * @param  string  $salt
     * @param  int $iterations The number of iterations
     * @param  int $length     The output size
     * @throws Exception\InvalidArgumentException
     * @return string
     */
    public static function calc($hash, $password, $salt, $iterations, $length)
    {
        if (! Hmac::isSupported($hash)) {
            throw new Exception\InvalidArgumentException("The hash algorithm $hash is not supported by " . __CLASS__);
        }

        $num    = ceil($length / Hmac::getOutputSize($hash, Hmac::OUTPUT_BINARY));
        $result = '';
        for ($block = 1; $block <= $num; $block++) {
            $hmac = hash_hmac($hash, $salt . pack('N', $block), $password, Hmac::OUTPUT_BINARY);
            $mix  = $hmac;
            for ($i = 1; $i < $iterations; $i++) {
                $hmac = hash_hmac($hash, $hmac, $password, Hmac::OUTPUT_BINARY);
                $mix ^= $hmac;
            }
            $result .= $mix;
        }
        return mb_substr($result, 0, $length, '8bit');
    }
}
