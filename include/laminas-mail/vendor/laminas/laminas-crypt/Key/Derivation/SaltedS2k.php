<?php

namespace Laminas\Crypt\Key\Derivation;

use function array_keys;
use function ceil;
use function hash;
use function in_array;
use function intval;
use function mb_strlen;
use function range;
use function str_pad;
use function str_repeat;
use function strlen;
use function substr;

use const MHASH_ADLER32;
use const MHASH_CRC32;
use const MHASH_CRC32B;
use const MHASH_GOST;
use const MHASH_HAVAL128;
use const MHASH_HAVAL160;
use const MHASH_HAVAL192;
use const MHASH_HAVAL224;
use const MHASH_HAVAL256;
use const MHASH_MD2;
use const MHASH_MD4;
use const MHASH_MD5;
use const MHASH_RIPEMD128;
use const MHASH_RIPEMD256;
use const MHASH_RIPEMD320;
use const MHASH_SHA1;
use const MHASH_SHA224;
use const MHASH_SHA256;
use const MHASH_SHA384;
use const MHASH_SHA512;
use const MHASH_SNEFRU256;
use const MHASH_TIGER;
use const MHASH_TIGER128;
use const MHASH_TIGER160;
use const MHASH_WHIRLPOOL;
use const STR_PAD_RIGHT;

/**
 * Salted S2K key generation (OpenPGP document, RFC 2440)
 */
class SaltedS2k
{
    /** @var array<string, string> */
    protected static $supportedMhashAlgos = [
        'adler32'    => MHASH_ADLER32,
        'md2'        => MHASH_MD2,
        'md4'        => MHASH_MD4,
        'md5'        => MHASH_MD5,
        'sha1'       => MHASH_SHA1,
        'sha224'     => MHASH_SHA224,
        'sha256'     => MHASH_SHA256,
        'sha384'     => MHASH_SHA384,
        'sha512'     => MHASH_SHA512,
        'ripemd128'  => MHASH_RIPEMD128,
        'ripemd256'  => MHASH_RIPEMD256,
        'ripemd320'  => MHASH_RIPEMD320,
        'haval128,3' => MHASH_HAVAL128, // @deprecated use haval128 instead
        'haval128'   => MHASH_HAVAL128,
        'haval160,3' => MHASH_HAVAL160, // @deprecated use haval160 instead
        'haval160'   => MHASH_HAVAL160,
        'haval192,3' => MHASH_HAVAL192, // @deprecated use haval192 instead
        'haval192'   => MHASH_HAVAL192,
        'haval224,3' => MHASH_HAVAL224, // @deprecated use haval224 instead
        'haval224'   => MHASH_HAVAL224,
        'haval256,3' => MHASH_HAVAL256, // @deprecated use haval256 instead
        'haval256'   => MHASH_HAVAL256,
        'tiger'      => MHASH_TIGER,
        'tiger128,3' => MHASH_TIGER128, // @deprecated use tiger128 instead
        'tiger128'   => MHASH_TIGER128,
        'tiger160,3' => MHASH_TIGER160, // @deprecated use tiger160 instead
        'tiger160'   => MHASH_TIGER160,
        'whirpool'   => MHASH_WHIRLPOOL,
        'snefru256'  => MHASH_SNEFRU256,
        'gost'       => MHASH_GOST,
        'crc32'      => MHASH_CRC32,
        'crc32b'     => MHASH_CRC32B,
    ];

    /**
     * Generate the new key
     *
     * @param  string  $hash       The hash algorithm to be used by HMAC
     * @param  string  $password   The source password/key
     * @param  int $bytes      The output size in bytes
     * @param  string  $salt       The salt of the algorithm
     * @throws Exception\InvalidArgumentException
     * @return string
     */
    public static function calc($hash, $password, $salt, $bytes)
    {
        if (! in_array($hash, array_keys(static::$supportedMhashAlgos))) {
            throw new Exception\InvalidArgumentException("The hash algorithm $hash is not supported by " . self::class);
        }
        if (mb_strlen($salt, '8bit') < 8) {
            throw new Exception\InvalidArgumentException('The salt size must be at least of 8 bytes');
        }

        $result = '';

        foreach (range(0, ceil($bytes / strlen(hash($hash, '', true))) - 1) as $i) {
            $result .= hash(
                $hash,
                str_repeat("\0", $i) . str_pad(
                    substr($salt, 0, 8),
                    8,
                    "\0",
                    STR_PAD_RIGHT
                ) . $password,
                true
            );
        }

        return substr($result, 0, intval($bytes));
    }
}
