<?php

/**
 * @see       https://github.com/laminas/laminas-crypt for the canonical source repository
 * @copyright https://github.com/laminas/laminas-crypt/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-crypt/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Crypt;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\NotFoundException;
use Laminas\Crypt\Key\Derivation\Pbkdf2;
use Laminas\Crypt\Symmetric\SymmetricInterface;
use Laminas\Math\Rand;

use function base64_decode;
use function base64_encode;
use function class_exists;
use function get_class;
use function gettype;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function is_subclass_of;
use function mb_substr;
use function sprintf;

/**
 * Encrypt using a symmetric cipher then authenticate using HMAC (SHA-256)
 */
class BlockCipher
{
    /**
     * Hash algorithm for Pbkdf2
     *
     * @var string
     */
    protected $pbkdf2Hash = 'sha256';

    /**
     * Symmetric cipher
     *
     * @var SymmetricInterface
     */
    protected $cipher;

    /**
     * Symmetric cipher plugin manager
     *
     * @var SymmetricPluginManager
     */
    protected static $symmetricPlugins = null;

    /**
     * Hash algorithm for HMAC
     *
     * @var string
     */
    protected $hash = 'sha256';

    /**
     * Check if the salt has been set
     *
     * @var bool
     */
    protected $saltSetted = false;

    /**
     * The output is binary?
     *
     * @var bool
     */
    protected $binaryOutput = false;

    /**
     * Number of iterations for Pbkdf2
     *
     * @var string
     */
    protected $keyIteration = 5000;

    /**
     * Key
     *
     * @var string
     */
    protected $key;

    /**
     * Constructor
     *
     * @param  SymmetricInterface $cipher
     */
    public function __construct(SymmetricInterface $cipher)
    {
        $this->cipher = $cipher;
    }

    /**
     * Factory
     *
     * @param  string      $adapter
     * @param  array       $options
     * @return BlockCipher
     */
    public static function factory($adapter, $options = [])
    {
        $plugins = static::getSymmetricPluginManager();
        try {
            $cipher = $plugins->get($adapter);
        } catch (NotFoundException $e) {
            throw new Exception\RuntimeException(sprintf(
                'The symmetric adapter %s does not exist',
                $adapter
            ));
        }
        $cipher->setOptions($options);
        return new static($cipher);
    }

    /**
     * Returns the symmetric cipher plugin manager.  If it doesn't exist it's created.
     *
     * @return ContainerInterface
     */
    public static function getSymmetricPluginManager()
    {
        if (static::$symmetricPlugins === null) {
            static::setSymmetricPluginManager(new SymmetricPluginManager());
        }

        return static::$symmetricPlugins;
    }

    /**
     * Set the symmetric cipher plugin manager
     *
     * @param  string|SymmetricPluginManager      $plugins
     * @throws Exception\InvalidArgumentException
     */
    public static function setSymmetricPluginManager($plugins)
    {
        if (is_string($plugins)) {
            if (! class_exists($plugins) || ! is_subclass_of($plugins, ContainerInterface::class)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Unable to locate symmetric cipher plugins using class "%s"; '
                    . 'class does not exist or does not implement ContainerInterface',
                    $plugins
                ));
            }
            $plugins = new $plugins();
        }
        if (! $plugins instanceof ContainerInterface) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Symmetric plugin must implements Interop\Container\ContainerInterface;; received "%s"',
                is_object($plugins) ? get_class($plugins) : gettype($plugins)
            ));
        }
        static::$symmetricPlugins = $plugins;
    }

    /**
     * Set the symmetric cipher
     *
     * @param  SymmetricInterface $cipher
     * @return BlockCipher Provides a fluent interface
     */
    public function setCipher(SymmetricInterface $cipher)
    {
        $this->cipher = $cipher;
        return $this;
    }

    /**
     * Get symmetric cipher
     *
     * @return SymmetricInterface
     */
    public function getCipher()
    {
        return $this->cipher;
    }

    /**
     * Set the number of iterations for Pbkdf2
     *
     * @param  int $num
     * @return BlockCipher Provides a fluent interface
     */
    public function setKeyIteration($num)
    {
        $this->keyIteration = (int) $num;

        return $this;
    }

    /**
     * Get the number of iterations for Pbkdf2
     *
     * @return int
     */
    public function getKeyIteration()
    {
        return $this->keyIteration;
    }

    /**
     * Set the salt (IV)
     *
     * @param  string $salt
     * @return BlockCipher Provides a fluent interface
     * @throws Exception\InvalidArgumentException
     */
    public function setSalt($salt)
    {
        try {
            $this->cipher->setSalt($salt);
        } catch (Symmetric\Exception\InvalidArgumentException $e) {
            throw new Exception\InvalidArgumentException("The salt is not valid: " . $e->getMessage());
        }
        $this->saltSetted = true;

        return $this;
    }

    /**
     * Get the salt (IV) according to the size requested by the algorithm
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->cipher->getSalt();
    }

    /**
     * Get the original salt value
     *
     * @return string
     */
    public function getOriginalSalt()
    {
        return $this->cipher->getOriginalSalt();
    }

    /**
     * Enable/disable the binary output
     *
     * @param  bool $value
     * @return BlockCipher Provides a fluent interface
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
     * Set the encryption/decryption key
     *
     * @param  string $key
     * @return BlockCipher Provides a fluent interface
     * @throws Exception\InvalidArgumentException
     */
    public function setKey($key)
    {
        if (empty($key)) {
            throw new Exception\InvalidArgumentException('The key cannot be empty');
        }
        $this->key = $key;

        return $this;
    }

    /**
     * Get the key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set algorithm of the symmetric cipher
     *
     * @param  string $algo
     * @return BlockCipher Provides a fluent interface
     * @throws Exception\InvalidArgumentException
     */
    public function setCipherAlgorithm($algo)
    {
        try {
            $this->cipher->setAlgorithm($algo);
        } catch (Symmetric\Exception\InvalidArgumentException $e) {
            throw new Exception\InvalidArgumentException($e->getMessage());
        }

        return $this;
    }

    /**
     * Get the cipher algorithm
     *
     * @return string|bool
     */
    public function getCipherAlgorithm()
    {
        return $this->cipher->getAlgorithm();
    }

    /**
     * Get the supported algorithms of the symmetric cipher
     *
     * @return array
     */
    public function getCipherSupportedAlgorithms()
    {
        return $this->cipher->getSupportedAlgorithms();
    }

    /**
     * Set the hash algorithm for HMAC authentication
     *
     * @param  string $hash
     * @return BlockCipher Provides a fluent interface
     * @throws Exception\InvalidArgumentException
     */
    public function setHashAlgorithm($hash)
    {
        if (! Hash::isSupported($hash)) {
            throw new Exception\InvalidArgumentException(
                "The specified hash algorithm '{$hash}' is not supported by Laminas\Crypt\Hash"
            );
        }
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get the hash algorithm for HMAC authentication
     *
     * @return string
     */
    public function getHashAlgorithm()
    {
        return $this->hash;
    }

    /**
     * Set the hash algorithm for the Pbkdf2
     *
     * @param  string $hash
     * @return BlockCipher Provides a fluent interface
     * @throws Exception\InvalidArgumentException
     */
    public function setPbkdf2HashAlgorithm($hash)
    {
        if (! Hash::isSupported($hash)) {
            throw new Exception\InvalidArgumentException(
                "The specified hash algorithm '{$hash}' is not supported by Laminas\Crypt\Hash"
            );
        }
        $this->pbkdf2Hash = $hash;

        return $this;
    }

    /**
     * Get the Pbkdf2 hash algorithm
     *
     * @return string
     */
    public function getPbkdf2HashAlgorithm()
    {
        return $this->pbkdf2Hash;
    }

    /**
     * Encrypt then authenticate using HMAC
     *
     * @param  string $data
     * @return string
     * @throws Exception\InvalidArgumentException
     */
    public function encrypt($data)
    {
        // 0 (as integer), 0.0 (as float) & '0' (as string) will return false, though these should be allowed
        // Must be a string, integer, or float in order to encrypt
        if ((is_string($data) && $data === '')
            || is_array($data)
            || is_object($data)
        ) {
            throw new Exception\InvalidArgumentException('The data to encrypt cannot be empty');
        }

        // Cast to string prior to encrypting
        if (! is_string($data)) {
            $data = (string) $data;
        }

        if (empty($this->key)) {
            throw new Exception\InvalidArgumentException('No key specified for the encryption');
        }
        $keySize = $this->cipher->getKeySize();
        // generate a random salt (IV) if the salt has not been set
        if (! $this->saltSetted) {
            $this->cipher->setSalt(Rand::getBytes($this->cipher->getSaltSize()));
        }

        if (in_array($this->cipher->getMode(), ['ccm', 'gcm'], true)) {
            return $this->encryptViaCcmOrGcm($data, $keySize);
        }

        // generate the encryption key and the HMAC key for the authentication
        $hash = Pbkdf2::calc(
            $this->getPbkdf2HashAlgorithm(),
            $this->getKey(),
            $this->getSalt(),
            $this->keyIteration,
            $keySize * 2
        );
        // set the encryption key
        $this->cipher->setKey(mb_substr($hash, 0, $keySize, '8bit'));
        // set the key for HMAC
        $keyHmac = mb_substr($hash, $keySize, null, '8bit');
        // encryption
        $ciphertext = $this->cipher->encrypt($data);
        // HMAC
        $hmac = Hmac::compute($keyHmac, $this->hash, $this->cipher->getAlgorithm() . $ciphertext);

        return $this->binaryOutput ? $hmac . $ciphertext : $hmac . base64_encode($ciphertext);
    }

    /**
     * Decrypt
     *
     * @param  string $data
     * @return string|bool
     * @throws Exception\InvalidArgumentException
     */
    public function decrypt($data)
    {
        if (! is_string($data)) {
            throw new Exception\InvalidArgumentException('The data to decrypt must be a string');
        }
        if ('' === $data) {
            throw new Exception\InvalidArgumentException('The data to decrypt cannot be empty');
        }
        if (empty($this->key)) {
            throw new Exception\InvalidArgumentException('No key specified for the decryption');
        }

        $keySize = $this->cipher->getKeySize();

        if (in_array($this->cipher->getMode(), ['ccm', 'gcm'], true)) {
            return $this->decryptViaCcmOrGcm($data, $keySize);
        }

        $hmacSize   = Hmac::getOutputSize($this->hash);
        $hmac       = mb_substr($data, 0, $hmacSize, '8bit');
        $ciphertext = mb_substr($data, $hmacSize, null, '8bit') ?: '';
        if (! $this->binaryOutput) {
            $ciphertext = base64_decode($ciphertext);
        }
        $iv = mb_substr($ciphertext, 0, $this->cipher->getSaltSize(), '8bit');
        // generate the encryption key and the HMAC key for the authentication
        $hash = Pbkdf2::calc(
            $this->getPbkdf2HashAlgorithm(),
            $this->getKey(),
            $iv,
            $this->keyIteration,
            $keySize * 2
        );
        // set the decryption key
        $this->cipher->setKey(mb_substr($hash, 0, $keySize, '8bit'));
        // set the key for HMAC
        $keyHmac = mb_substr($hash, $keySize, null, '8bit');
        $hmacNew = Hmac::compute($keyHmac, $this->hash, $this->cipher->getAlgorithm() . $ciphertext);
        if (! Utils::compareStrings($hmacNew, $hmac)) {
            return false;
        }

        return $this->cipher->decrypt($ciphertext);
    }

    /**
     * Note: CCM and GCM modes do not need HMAC
     *
     * @param string $data
     * @param int    $keySize
     *
     * @return string
     *
     * @throws Exception\InvalidArgumentException
     */
    private function encryptViaCcmOrGcm($data, $keySize)
    {
        $this->cipher->setKey(Pbkdf2::calc(
            $this->getPbkdf2HashAlgorithm(),
            $this->getKey(),
            $this->getSalt(),
            $this->keyIteration,
            $keySize
        ));

        $cipherText = $this->cipher->encrypt($data);

        return $this->binaryOutput ? $cipherText : base64_encode($cipherText);
    }

    /**
     * Note: CCM and GCM modes do not need HMAC
     *
     * @param string $data
     * @param int    $keySize
     *
     * @return string
     *
     * @throws Exception\InvalidArgumentException
     */
    private function decryptViaCcmOrGcm($data, $keySize)
    {
        $cipherText = $this->binaryOutput ? $data : base64_decode($data);
        $iv         = mb_substr($cipherText, $this->cipher->getTagSize(), $this->cipher->getSaltSize(), '8bit');

        $this->cipher->setKey(Pbkdf2::calc(
            $this->getPbkdf2HashAlgorithm(),
            $this->getKey(),
            $iv,
            $this->keyIteration,
            $keySize
        ));

        return $this->cipher->decrypt($cipherText);
    }
}
