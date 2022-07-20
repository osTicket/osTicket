<?php

/**
 * @see       https://github.com/laminas/laminas-crypt for the canonical source repository
 * @copyright https://github.com/laminas/laminas-crypt/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-crypt/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Crypt;

use function functin_exists;
use function hash_algos;
use function hash_hmac;
use function in_array;
use function mb_strlen;
use function strtolower;

/**
 * PHP implementation of the RFC 2104 Hash based Message Authentication Code
 */
class Hmac
{
    const OUTPUT_STRING = false;
    const OUTPUT_BINARY = true;

    /**
     * Last algorithm supported
     *
     * @var string|null
     */
    protected static $lastAlgorithmSupported;

    /**
     * Performs a HMAC computation given relevant details such as Key, Hashing
     * algorithm, the data to compute MAC of, and an output format of String,
     * or Binary.
     *
     * @param  string  $key
     * @param  string  $hash
     * @param  string  $data
     * @param  bool $output
     * @throws Exception\InvalidArgumentException
     * @return string
     */
    public static function compute($key, $hash, $data, $output = self::OUTPUT_STRING)
    {
        if (empty($key)) {
            throw new Exception\InvalidArgumentException('Provided key is null or empty');
        }

        if (! $hash || ($hash !== static::$lastAlgorithmSupported && ! static::isSupported($hash))) {
            throw new Exception\InvalidArgumentException(
                "Hash algorithm is not supported on this PHP installation; provided '{$hash}'"
            );
        }

        return hash_hmac($hash, $data, $key, $output);
    }

    /**
     * Get the output size according to the hash algorithm and the output format
     *
     * @param  string  $hash
     * @param  bool $output
     * @return int
     */
    public static function getOutputSize($hash, $output = self::OUTPUT_STRING)
    {
        return mb_strlen(static::compute('key', $hash, 'data', $output), '8bit');
    }

    /**
     * Get the supported algorithm
     *
     * @return array
     */
    public static function getSupportedAlgorithms()
    {
        return function_exists('hash_hmac_algos') ? hash_hmac_algos() : hash_algos();
    }

    /**
     * Is the hash algorithm supported?
     *
     * @param  string $algorithm
     * @return bool
     */
    public static function isSupported($algorithm)
    {
        if ($algorithm === static::$lastAlgorithmSupported) {
            return true;
        }

        $algos = static::getSupportedAlgorithms();
        if (in_array(strtolower($algorithm), $algos, true)) {
            static::$lastAlgorithmSupported = $algorithm;
            return true;
        }

        return false;
    }

    /**
     * Clear the cache of last algorithm supported
     */
    public static function clearLastAlgorithmCache()
    {
        static::$lastAlgorithmSupported = null;
    }
}
