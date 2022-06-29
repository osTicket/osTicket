<?php

/**
 * @see       https://github.com/laminas/laminas-crypt for the canonical source repository
 * @copyright https://github.com/laminas/laminas-crypt/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-crypt/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Crypt\PublicKey;

use Laminas\Crypt\PublicKey\Rsa\Exception;
use Laminas\Stdlib\AbstractOptions;

use function array_replace;
use function constant;
use function defined;
use function openssl_error_string;
use function openssl_pkey_export;
use function openssl_pkey_get_details;
use function openssl_pkey_new;
use function strtolower;
use function strtoupper;

use const OPENSSL_KEYTYPE_RSA;

/**
 * RSA instance options
 */
class RsaOptions extends AbstractOptions
{
    /**
     * @var Rsa\PrivateKey
     */
    protected $privateKey = null;

    /**
     * @var Rsa\PublicKey
     */
    protected $publicKey = null;

    /**
     * @var string
     */
    protected $hashAlgorithm = 'sha1';

    /**
     * Signature hash algorithm defined by openss constants
     *
     * @var int
     */
    protected $opensslSignatureAlgorithm = null;

    /**
     * @var string
     */
    protected $passPhrase = null;

    /**
     * Output is binary
     *
     * @var bool
     */
    protected $binaryOutput = true;

    /**
     * OPENSSL padding
     *
     * @var int|null
     */
    protected $opensslPadding;

    /**
     * Set private key
     *
     * @param  Rsa\PrivateKey $key
     * @return RsaOptions Provides a fluent interface
     */
    public function setPrivateKey(Rsa\PrivateKey $key)
    {
        $this->privateKey = $key;
        $this->publicKey  = $this->privateKey->getPublicKey();
        return $this;
    }

    /**
     * Get private key
     *
     * @return null|Rsa\PrivateKey
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * Set public key
     *
     * @param  Rsa\PublicKey $key
     * @return RsaOptions Provides a fluent interface
     */
    public function setPublicKey(Rsa\PublicKey $key)
    {
        $this->publicKey = $key;
        return $this;
    }

    /**
     * Get public key
     *
     * @return null|Rsa\PublicKey
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Set pass phrase
     *
     * @param string $phrase
     * @return RsaOptions Provides a fluent interface
     */
    public function setPassPhrase($phrase)
    {
        $this->passPhrase = (string) $phrase;
        return $this;
    }

    /**
     * Get pass phrase
     *
     * @return string
     */
    public function getPassPhrase()
    {
        return $this->passPhrase;
    }

    /**
     * Set hash algorithm
     *
     * @param  string $hash
     * @return RsaOptions Provides a fluent interface
     * @throws Rsa\Exception\RuntimeException
     * @throws Rsa\Exception\InvalidArgumentException
     */
    public function setHashAlgorithm($hash)
    {
        $hashUpper = strtoupper($hash);
        if (! defined('OPENSSL_ALGO_' . $hashUpper)) {
            throw new Exception\InvalidArgumentException(
                "Hash algorithm '{$hash}' is not supported"
            );
        }

        $this->hashAlgorithm = strtolower($hash);
        $this->opensslSignatureAlgorithm = constant('OPENSSL_ALGO_' . $hashUpper);
        return $this;
    }

    /**
     * Get hash algorithm
     *
     * @return string
     */
    public function getHashAlgorithm()
    {
        return $this->hashAlgorithm;
    }

    public function getOpensslSignatureAlgorithm()
    {
        if (! isset($this->opensslSignatureAlgorithm)) {
            $this->opensslSignatureAlgorithm = constant('OPENSSL_ALGO_' . strtoupper($this->hashAlgorithm));
        }
        return $this->opensslSignatureAlgorithm;
    }

    /**
     * Enable/disable the binary output
     *
     * @param  bool $value
     * @return RsaOptions Provides a fluent interface
     */
    public function setBinaryOutput($value)
    {
        $this->binaryOutput = (bool) $value;
        return $this;
    }

    /**
     * Get the value of binary output
     *
     * @return bool
     */
    public function getBinaryOutput()
    {
        return $this->binaryOutput;
    }

    /**
     * Get the OPENSSL padding
     *
     * @return int|null
     */
    public function getOpensslPadding()
    {
        return $this->opensslPadding;
    }

    /**
     * Set the OPENSSL padding
     *
     * @param int|null $opensslPadding
     * @return RsaOptions Provides a fluent interface
     */
    public function setOpensslPadding($opensslPadding)
    {
        $this->opensslPadding = (int) $opensslPadding;
        return $this;
    }

    /**
     * Generate new private/public key pair
     *
     * @param  array $opensslConfig
     * @return RsaOptions Provides a fluent interface
     * @throws Rsa\Exception\RuntimeException
     */
    public function generateKeys(array $opensslConfig = [])
    {
        $opensslConfig = array_replace(
            [
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => Rsa\PrivateKey::DEFAULT_KEY_SIZE,
                'digest_alg'       => $this->getHashAlgorithm()
            ],
            $opensslConfig
        );

        // generate
        $resource = openssl_pkey_new($opensslConfig);
        if (false === $resource) {
            throw new Exception\RuntimeException(
                'Can not generate keys; openssl ' . openssl_error_string()
            );
        }

        // export key
        $passPhrase = $this->getPassPhrase();
        $result     = openssl_pkey_export($resource, $private, $passPhrase, $opensslConfig);
        if (false === $result) {
            throw new Exception\RuntimeException(
                'Can not export key; openssl ' . openssl_error_string()
            );
        }

        $details          = openssl_pkey_get_details($resource);
        $this->privateKey = new Rsa\PrivateKey($private, $passPhrase);
        $this->publicKey  = new Rsa\PublicKey($details['key']);

        return $this;
    }
}
