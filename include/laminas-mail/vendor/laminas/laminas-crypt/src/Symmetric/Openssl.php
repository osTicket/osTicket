<?php

/**
 * @see       https://github.com/laminas/laminas-crypt for the canonical source repository
 * @copyright https://github.com/laminas/laminas-crypt/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-crypt/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Crypt\Symmetric;

use Interop\Container\ContainerInterface;
use Laminas\Stdlib\ArrayUtils;
use Traversable;

use function class_exists;
use function extension_loaded;
use function get_class;
use function gettype;
use function in_array;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function is_subclass_of;
use function mb_strlen;
use function mb_substr;
use function openssl_cipher_iv_length;
use function openssl_decrypt;
use function openssl_encrypt;
use function openssl_error_string;
use function openssl_get_cipher_methods;
use function strtolower;

use const OPENSSL_RAW_DATA;
use const OPENSSL_ZERO_PADDING;
use const PHP_VERSION_ID;

/**
 * Symmetric encryption using the OpenSSL extension
 *
 * NOTE: DO NOT USE only this class to encrypt data.
 * This class doesn't provide authentication and integrity check over the data.
 * PLEASE USE Laminas\Crypt\BlockCipher instead!
 */
class Openssl implements SymmetricInterface
{
    const DEFAULT_PADDING = 'pkcs7';

    /**
     * Key
     *
     * @var string
     */
    protected $key;

    /**
     * IV
     *
     * @var string
     */
    protected $iv;

    /**
     * Encryption algorithm
     *
     * @var string
     */
    protected $algo = 'aes';

    /**
     * Encryption mode
     *
     * @var string
     */
    protected $mode = 'cbc';

    /**
     * Padding
     *
     * @var Padding\PaddingInterface
     */
    protected $padding;

    /**
     * Padding plugins
     *
     * @var Interop\Container\ContainerInterface
     */
    protected static $paddingPlugins = null;

    /**
     * The encryption algorithms to support
     *
     * @var array
     */
    protected $encryptionAlgos = [
        'aes'      => 'aes-256',
        'blowfish' => 'bf',
        'des'      => 'des',
        'camellia' => 'camellia-256',
        'cast5'    => 'cast5',
        'seed'     => 'seed',
    ];

    /**
     * Encryption modes to support
     *
     * @var array
     */
    protected $encryptionModes = [
        'cbc',
        'cfb',
        'ofb',
        'ecb',
        'ctr',
    ];

    /**
     * Block sizes (in bytes) for each supported algorithm
     *
     * @var array
     */
    protected $blockSizes = [
        'aes'      => 16,
        'blowfish' => 8,
        'des'      => 8,
        'camellia' => 16,
        'cast5'    => 8,
        'seed'     => 16,
    ];

    /**
     * Key sizes (in bytes) for each supported algorithm
     *
     * @var array
     */
    protected $keySizes = [
        'aes'      => 32,
        'blowfish' => 56,
        'des'      => 8,
        'camellia' => 32,
        'cast5'    => 16,
        'seed'     => 16,
    ];

    /**
     * The OpenSSL supported encryption algorithms
     *
     * @var array
     */
    protected $opensslAlgos = [];

    /**
     * Additional authentication data (only for PHP 7.1+)
     *
     * @var string
     */
    protected $aad;

    /**
     * Store the tag for authentication (only for PHP 7.1+)
     *
     * @var string
     */
    protected $tag;

    /**
     * Tag size for authenticated encryption modes (only for PHP 7.1+)
     *
     * @var int
     */
    protected $tagSize = 16;

    /**
     * Constructor
     *
     * @param  array|Traversable $options
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($options = [])
    {
        if (! extension_loaded('openssl')) {
            throw new Exception\RuntimeException(sprintf(
                'You cannot use %s without the OpenSSL extension',
                __CLASS__
            ));
        }
        // Add the GCM and CCM modes for PHP 7.1+
        if (PHP_VERSION_ID >= 70100) {
            array_push($this->encryptionModes, 'gcm', 'ccm');
        }
        $this->setOptions($options);
        $this->setDefaultOptions($options);
    }

    /**
     * Set default options
     *
     * @param  array $options
     * @return void
     *
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    public function setOptions($options)
    {
        if (empty($options)) {
            return;
        }

        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (! is_array($options)) {
            throw new Exception\InvalidArgumentException(
                'The options parameter must be an array or a Traversable'
            );
        }

        // The algorithm case is not included in the switch because must be
        // set before the others
        if (isset($options['algo'])) {
            $this->setAlgorithm($options['algo']);
        } elseif (isset($options['algorithm'])) {
            $this->setAlgorithm($options['algorithm']);
        }

        foreach ($options as $key => $value) {
            switch (strtolower($key)) {
                case 'mode':
                    $this->setMode($value);
                    break;
                case 'key':
                    $this->setKey($value);
                    break;
                case 'iv':
                case 'salt':
                    $this->setSalt($value);
                    break;
                case 'padding':
                    $plugins       = static::getPaddingPluginManager();
                    $padding       = $plugins->get($value);
                    $this->padding = $padding;
                    break;
                case 'aad':
                    $this->setAad($value);
                    break;
                case 'tag_size':
                    $this->setTagSize($value);
                    break;
            }
        }
    }

    /**
     * Set default options
     *
     * @param  array $options
     * @return void
     */
    protected function setDefaultOptions($options = [])
    {
        if (isset($options['padding'])) {
            return;
        }

        $plugins       = static::getPaddingPluginManager();
        $padding       = $plugins->get(self::DEFAULT_PADDING);
        $this->padding = $padding;
    }

    /**
     * Returns the padding plugin manager.
     *
     * Creates one if none is present.
     *
     * @return ContainerInterface
     */
    public static function getPaddingPluginManager()
    {
        if (static::$paddingPlugins === null) {
            self::setPaddingPluginManager(new PaddingPluginManager());
        }

        return static::$paddingPlugins;
    }

    /**
     * Set the padding plugin manager
     *
     * @param  string|ContainerInterface $plugins
     * @throws Exception\InvalidArgumentException
     * @return void
     */
    public static function setPaddingPluginManager($plugins)
    {
        if (is_string($plugins)) {
            if (! class_exists($plugins) || ! is_subclass_of($plugins, ContainerInterface::class)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Unable to locate padding plugin manager via class "%s"; '
                    . 'class does not exist or does not implement ContainerInterface',
                    $plugins
                ));
            }

            $plugins = new $plugins();
        }

        if (! $plugins instanceof ContainerInterface) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Padding plugins must implements %s; received "%s"',
                ContainerInterface::class,
                is_object($plugins) ? get_class($plugins) : gettype($plugins)
            ));
        }

        static::$paddingPlugins = $plugins;
    }

    /**
     * Get the key size for the selected cipher
     *
     * @return int
     */
    public function getKeySize()
    {
        return $this->keySizes[$this->algo];
    }

    /**
     * Set the encryption key
     * If the key is longer than maximum supported, it will be truncated by getKey().
     *
     * @param  string $key
     * @return Openssl Provides a fluent interface
     * @throws Exception\InvalidArgumentException
     */
    public function setKey($key)
    {
        $keyLen = mb_strlen($key, '8bit');

        if (! $keyLen) {
            throw new Exception\InvalidArgumentException('The key cannot be empty');
        }

        if ($keyLen < $this->getKeySize()) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The size of the key must be at least of %d bytes',
                $this->getKeySize()
            ));
        }

        $this->key = $key;
        return $this;
    }

    /**
     * Get the encryption key
     *
     * @return string
     */
    public function getKey()
    {
        if (empty($this->key)) {
            return;
        }
        return mb_substr($this->key, 0, $this->getKeySize(), '8bit');
    }

    /**
     * Set the encryption algorithm (cipher)
     *
     * @param  string $algo
     * @return Openssl Provides a fluent interface
     * @throws Exception\InvalidArgumentException
     */
    public function setAlgorithm($algo)
    {
        if (! in_array($algo, $this->getSupportedAlgorithms())) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The algorithm %s is not supported by %s',
                $algo,
                __CLASS__
            ));
        }
        $this->algo = $algo;
        return $this;
    }

    /**
     * Get the encryption algorithm
     *
     * @return string
     */
    public function getAlgorithm()
    {
        return $this->algo;
    }

    /**
     * Set the padding object
     *
     * @param  Padding\PaddingInterface $padding
     * @return Openssl Provides a fluent interface
     */
    public function setPadding(Padding\PaddingInterface $padding)
    {
        $this->padding = $padding;
        return $this;
    }

    /**
     * Get the padding object
     *
     * @return Padding\PaddingInterface
     */
    public function getPadding()
    {
        return $this->padding;
    }

    /**
     * Set Additional Authentication Data
     *
     * @param string $aad
     * @return self
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function setAad($aad)
    {
        if (! $this->isAuthEncAvailable()) {
            throw new Exception\RuntimeException(
                'You need PHP 7.1+ and OpenSSL with CCM or GCM mode to use AAD'
            );
        }

        if (! $this->isCcmOrGcm()) {
            throw new Exception\RuntimeException(
                'You can set Additional Authentication Data (AAD) only for CCM or GCM mode'
            );
        }

        if (! is_string($aad)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The provided $aad must be a string, %s given',
                gettype($aad)
            ));
        }

        $this->aad = $aad;

        return $this;
    }

    /**
     * Get the Additional Authentication Data
     *
     * @return string
     */
    public function getAad()
    {
        return $this->aad;
    }

    /**
     * Get the authentication tag
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set the tag size for CCM and GCM mode
     *
     * @param int $size
     * @return self
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function setTagSize($size)
    {
        if (! is_int($size)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The provided $size must be an integer, %s given',
                gettype($size)
            ));
        }

        if (! $this->isAuthEncAvailable()) {
            throw new Exception\RuntimeException(
                'You need PHP 7.1+ and OpenSSL with CCM or GCM mode to set the Tag Size'
            );
        }

        if (! $this->isCcmOrGcm()) {
            throw new Exception\RuntimeException(
                'You can set the Tag Size only for CCM or GCM mode'
            );
        }

        if ($this->getMode() === 'gcm' && ($size < 4 || $size > 16)) {
            throw new Exception\InvalidArgumentException(
                'The Tag Size must be between 4 to 16 for GCM mode'
            );
        }

        $this->tagSize = $size;

        return $this;
    }

    /**
     * Get the tag size for CCM and GCM mode
     *
     * @return int
     */
    public function getTagSize()
    {
        return $this->tagSize;
    }

    /**
     * Encrypt
     *
     * @param  string $data
     * @throws Exception\InvalidArgumentException
     * @return string
     */
    public function encrypt($data)
    {
        // Cannot encrypt empty string
        if (! is_string($data) || $data === '') {
            throw new Exception\InvalidArgumentException('The data to encrypt cannot be empty');
        }

        if (null === $this->getKey()) {
            throw new Exception\InvalidArgumentException('No key specified for the encryption');
        }

        if (null === $this->getSalt() && $this->getSaltSize() > 0) {
            throw new Exception\InvalidArgumentException('The salt (IV) cannot be empty');
        }

        if (null === $this->getPadding()) {
            throw new Exception\InvalidArgumentException('You must specify a padding method');
        }

        // padding
        $data = $this->padding->pad($data, $this->getBlockSize());
        $iv   = $this->getSalt();

        // encryption with GCM or CCM
        if ($this->isCcmOrGcm()) {
            $result = openssl_encrypt(
                $data,
                strtolower($this->encryptionAlgos[$this->algo] . '-' . $this->mode),
                $this->getKey(),
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                $iv,
                $tag,
                $this->getAad(),
                $this->getTagSize()
            );
            $this->tag = $tag;
        } else {
            $result = openssl_encrypt(
                $data,
                strtolower($this->encryptionAlgos[$this->algo] . '-' . $this->mode),
                $this->getKey(),
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                $iv
            );
        }

        if (false === $result) {
            $errMsg = '';
            while ($msg = openssl_error_string()) {
                $errMsg .= $msg;
            }
            throw new Exception\RuntimeException(sprintf(
                'OpenSSL error: %s',
                $errMsg
            ));
        }

        if ($this->isCcmOrGcm()) {
            return $tag . $iv . $result;
        }

        return $iv . $result;
    }

    /**
     * Decrypt
     *
     * @param  string $data
     * @throws Exception\InvalidArgumentException
     * @return string
     */
    public function decrypt($data)
    {
        if (empty($data)) {
            throw new Exception\InvalidArgumentException('The data to decrypt cannot be empty');
        }

        if (null === $this->getKey()) {
            throw new Exception\InvalidArgumentException('No decryption key specified');
        }
        if (null === $this->getPadding()) {
            throw new Exception\InvalidArgumentException('You must specify a padding method');
        }

        if ($this->isCcmOrGcm()) {
            $tag  = mb_substr($data, 0, $this->getTagSize(), '8bit');
            $data = mb_substr($data, $this->getTagSize(), null, '8bit');
            $this->tag = $tag;
        }

        $iv         = mb_substr($data, 0, $this->getSaltSize(), '8bit');
        $ciphertext = mb_substr($data, $this->getSaltSize(), null, '8bit');
        $result     = $this->attemptOpensslDecrypt($ciphertext, $iv, $this->tag);

        if (false === $result) {
            $errMsg = '';

            while ($msg = openssl_error_string()) {
                $errMsg .= $msg;
            }

            throw new Exception\RuntimeException(sprintf(
                'OpenSSL error: %s',
                $errMsg
            ));
        }

        // unpadding
        return $this->padding->strip($result);
    }

    /**
     * Get the salt (IV) size
     *
     * @return int
     */
    public function getSaltSize()
    {
        return openssl_cipher_iv_length(
            $this->encryptionAlgos[$this->algo] . '-' . $this->mode
        );
    }

    /**
     * Get the supported algorithms
     *
     * @return array
     */
    public function getSupportedAlgorithms()
    {
        if (empty($this->supportedAlgos)) {
            foreach ($this->encryptionAlgos as $name => $algo) {
                // CBC mode is supported by all the algorithms
                if (in_array($algo . '-cbc', $this->getOpensslAlgos())) {
                    $this->supportedAlgos[] = $name;
                }
            }
        }
        return $this->supportedAlgos;
    }


    /**
     * Set the salt (IV)
     *
     * @param  string $salt
     * @return Openssl Provides a fluent interface
     * @throws Exception\InvalidArgumentException
     */
    public function setSalt($salt)
    {
        if ($this->getSaltSize() <= 0) {
            throw new Exception\InvalidArgumentException(sprintf(
                'You cannot use a salt (IV) for %s in %s mode',
                $this->algo,
                $this->mode
            ));
        }

        if (empty($salt)) {
            throw new Exception\InvalidArgumentException('The salt (IV) cannot be empty');
        }

        if (mb_strlen($salt, '8bit') < $this->getSaltSize()) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The size of the salt (IV) must be at least %d bytes',
                $this->getSaltSize()
            ));
        }

        $this->iv = $salt;
        return $this;
    }

    /**
     * Get the salt (IV) according to the size requested by the algorithm
     *
     * @return string
     */
    public function getSalt()
    {
        if (empty($this->iv)) {
            return;
        }

        if (mb_strlen($this->iv, '8bit') < $this->getSaltSize()) {
            throw new Exception\RuntimeException(sprintf(
                'The size of the salt (IV) must be at least %d bytes',
                $this->getSaltSize()
            ));
        }

        return mb_substr($this->iv, 0, $this->getSaltSize(), '8bit');
    }

    /**
     * Get the original salt value
     *
     * @return string
     */
    public function getOriginalSalt()
    {
        return $this->iv;
    }

    /**
     * Set the cipher mode
     *
     * @param  string $mode
     * @return Openssl Provides a fluent interface
     * @throws Exception\InvalidArgumentException
     */
    public function setMode($mode)
    {
        if (empty($mode)) {
            return $this;
        }
        if (! in_array($mode, $this->getSupportedModes())) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The mode %s is not supported by %s',
                $mode,
                $this->algo
            ));
        }
        $this->mode = $mode;
        return $this;
    }

    /**
     * Get the cipher mode
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Return the OpenSSL supported encryption algorithms
     *
     * @return array
     */
    protected function getOpensslAlgos()
    {
        if (empty($this->opensslAlgos)) {
            $this->opensslAlgos = openssl_get_cipher_methods(true);
        }
        return $this->opensslAlgos;
    }

    /**
     * Get all supported encryption modes for the selected algorithm
     *
     * @return array
     */
    public function getSupportedModes()
    {
        $modes = [];
        foreach ($this->encryptionModes as $mode) {
            $algo = $this->encryptionAlgos[$this->algo] . '-' . $mode;
            if (in_array($algo, $this->getOpensslAlgos())) {
                $modes[] = $mode;
            }
        }
        return $modes;
    }

    /**
     * Get the block size
     *
     * @return int
     */
    public function getBlockSize()
    {
        return $this->blockSizes[$this->algo];
    }

    /**
     * Return true if authenticated encryption is available
     *
     * @return bool
     */
    public function isAuthEncAvailable()
    {
        // Counter with CBC-MAC
        $ccm = in_array('aes-256-ccm', $this->getOpensslAlgos());
        // Galois/Counter Mode
        $gcm = in_array('aes-256-gcm', $this->getOpensslAlgos());

        return PHP_VERSION_ID >= 70100 && ($ccm || $gcm);
    }

    /**
     * @return bool
     */
    private function isCcmOrGcm()
    {
        return in_array(strtolower($this->mode), ['gcm', 'ccm'], true);
    }

    /**
     * @param string $cipherText
     * @param string $iv
     * @param string $tag
     *
     * @return string|bool false on failure
     */
    private function attemptOpensslDecrypt($cipherText, $iv, $tag)
    {
        if ($this->isCcmOrGcm()) {
            return openssl_decrypt(
                $cipherText,
                strtolower($this->encryptionAlgos[$this->algo] . '-' . $this->mode),
                $this->getKey(),
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                $iv,
                $tag,
                $this->getAad()
            );
        }

        return openssl_decrypt(
            $cipherText,
            strtolower($this->encryptionAlgos[$this->algo] . '-' . $this->mode),
            $this->getKey(),
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv
        );
    }
}
