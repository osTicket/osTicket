<?php

namespace Laminas\Crypt;

use Laminas\Crypt\Key\Derivation\Pbkdf2;
use Laminas\Math\Rand;

use function fclose;
use function file_exists;
use function filesize;
use function fopen;
use function fread;
use function fseek;
use function fwrite;
use function mb_strlen;
use function mb_substr;
use function sprintf;
use function str_repeat;
use function unlink;

/**
 * Encrypt/decrypt a file using a symmetric cipher in CBC mode
 * then authenticate using HMAC
 */
class FileCipher
{
    public const BUFFER_SIZE = 1048576; // 16 * 65536 bytes = 1 Mb

    /**
     * Hash algorithm for Pbkdf2
     *
     * @var string
     */
    protected $pbkdf2Hash = 'sha256';

    /**
     * Hash algorithm for HMAC
     *
     * @var string
     */
    protected $hash = 'sha256';

    /**
     * Number of iterations for Pbkdf2
     *
     * @var int
     */
    protected $keyIteration = 10000;

    /**
     * Key
     *
     * @var string
     */
    protected $key;

    /**
     * Cipher
     *
     * @var SymmetricInterface
     */
    protected $cipher;

    /**
     * Constructor
     *
     * @param SymmetricInterface $cipher
     */
    public function __construct(?Symmetric\SymmetricInterface $cipher = null)
    {
        if (null === $cipher) {
            $cipher = new Symmetric\Openssl();
        }
        $this->cipher = $cipher;
    }

    /**
     * Set the cipher object
     *
     * @param SymmetricInterface $cipher
     */
    public function setCipher(Symmetric\SymmetricInterface $cipher)
    {
        $this->cipher = $cipher;
    }

    /**
     * Get the cipher object
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
     * @param  int  $num
     */
    public function setKeyIteration($num)
    {
        $this->keyIteration = (int) $num;
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
     * Set the encryption/decryption key
     *
     * @param  string                             $key
     * @throws Exception\InvalidArgumentException
     */
    public function setKey($key)
    {
        if (empty($key)) {
            throw new Exception\InvalidArgumentException('The key cannot be empty');
        }
        $this->key = (string) $key;
    }

    /**
     * Get the key
     *
     * @return string|null
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set algorithm of the symmetric cipher
     *
     * @param  string                             $algo
     */
    public function setCipherAlgorithm($algo)
    {
        $this->cipher->setAlgorithm($algo);
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
     * @param  string                             $hash
     * @throws Exception\InvalidArgumentException
     */
    public function setHashAlgorithm($hash)
    {
        if (! Hash::isSupported($hash)) {
            throw new Exception\InvalidArgumentException(
                "The specified hash algorithm '{$hash}' is not supported by Laminas\Crypt\Hash"
            );
        }
        $this->hash = (string) $hash;
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
     * @param  string                             $hash
     * @throws Exception\InvalidArgumentException
     */
    public function setPbkdf2HashAlgorithm($hash)
    {
        if (! Hash::isSupported($hash)) {
            throw new Exception\InvalidArgumentException(
                "The specified hash algorithm '{$hash}' is not supported by Laminas\Crypt\Hash"
            );
        }
        $this->pbkdf2Hash = (string) $hash;
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
     * Encrypt then authenticate a file using HMAC
     *
     * @param  string                             $fileIn
     * @param  string                             $fileOut
     * @return bool
     * @throws Exception\InvalidArgumentException
     */
    public function encrypt($fileIn, $fileOut)
    {
        $this->checkFileInOut($fileIn, $fileOut);
        if (empty($this->key)) {
            throw new Exception\InvalidArgumentException('No key specified for encryption');
        }

        $read    = fopen($fileIn, "r");
        $write   = fopen($fileOut, "w");
        $iv      = Rand::getBytes($this->cipher->getSaltSize());
        $keys    = Pbkdf2::calc(
            $this->getPbkdf2HashAlgorithm(),
            $this->getKey(),
            $iv,
            $this->getKeyIteration(),
            $this->cipher->getKeySize() * 2
        );
        $hmac    = '';
        $size    = 0;
        $tot     = filesize($fileIn);
        $padding = $this->cipher->getPadding();

        $this->cipher->setKey(mb_substr($keys, 0, $this->cipher->getKeySize(), '8bit'));
        $this->cipher->setPadding(new Symmetric\Padding\NoPadding());
        $this->cipher->setSalt($iv);
        $this->cipher->setMode('cbc');

        $hashAlgo  = $this->getHashAlgorithm();
        $saltSize  = $this->cipher->getSaltSize();
        $algorithm = $this->cipher->getAlgorithm();
        $keyHmac   = mb_substr($keys, $this->cipher->getKeySize(), null, '8bit');

        while ($data = fread($read, self::BUFFER_SIZE)) {
            $size += mb_strlen($data, '8bit');
            // Padding if last block
            if ($size === $tot) {
                $this->cipher->setPadding($padding);
            }
            $result = $this->cipher->encrypt($data);
            if ($size <= self::BUFFER_SIZE) {
                // Write a placeholder for the HMAC and write the IV
                fwrite($write, str_repeat(0, Hmac::getOutputSize($hashAlgo)));
            } else {
                $result = mb_substr($result, $saltSize, null, '8bit');
            }
            $hmac = Hmac::compute(
                $keyHmac,
                $hashAlgo,
                $algorithm . $hmac . $result
            );
            $this->cipher->setSalt(mb_substr($result, -1 * $saltSize, null, '8bit'));
            if (fwrite($write, $result) !== mb_strlen($result, '8bit')) {
                return false;
            }
        }
        $result = true;
        // write the HMAC at the beginning of the file
        fseek($write, 0);
        if (fwrite($write, $hmac) !== mb_strlen($hmac, '8bit')) {
            $result = false;
        }
        fclose($write);
        fclose($read);

        return $result;
    }

    /**
     * Decrypt a file
     *
     * @param  string                             $fileIn
     * @param  string                             $fileOut
     * @return bool
     * @throws Exception\InvalidArgumentException
     */
    public function decrypt($fileIn, $fileOut)
    {
        $this->checkFileInOut($fileIn, $fileOut);
        if (empty($this->key)) {
            throw new Exception\InvalidArgumentException('No key specified for decryption');
        }

        $read     = fopen($fileIn, "r");
        $write    = fopen($fileOut, "w");
        $hmacRead = fread($read, Hmac::getOutputSize($this->getHashAlgorithm()));
        $iv       = fread($read, $this->cipher->getSaltSize());
        $tot      = filesize($fileIn);
        $hmac     = $iv;
        $size     = mb_strlen($iv, '8bit') + mb_strlen($hmacRead, '8bit');
        $keys     = Pbkdf2::calc(
            $this->getPbkdf2HashAlgorithm(),
            $this->getKey(),
            $iv,
            $this->getKeyIteration(),
            $this->cipher->getKeySize() * 2
        );
        $padding  = $this->cipher->getPadding();
        $this->cipher->setPadding(new Symmetric\Padding\NoPadding());
        $this->cipher->setKey(mb_substr($keys, 0, $this->cipher->getKeySize(), '8bit'));
        $this->cipher->setMode('cbc');

        $blockSize = $this->cipher->getBlockSize();
        $hashAlgo  = $this->getHashAlgorithm();
        $algorithm = $this->cipher->getAlgorithm();
        $saltSize  = $this->cipher->getSaltSize();
        $keyHmac   = mb_substr($keys, $this->cipher->getKeySize(), null, '8bit');

        while ($data = fread($read, self::BUFFER_SIZE)) {
            $size += mb_strlen($data, '8bit');
            // Unpadding if last block
            if ($size + $blockSize >= $tot) {
                $this->cipher->setPadding($padding);
                $data .= fread($read, $blockSize);
            }
            $result = $this->cipher->decrypt($iv . $data);
            $hmac   = Hmac::compute(
                $keyHmac,
                $hashAlgo,
                $algorithm . $hmac . $data
            );
            $iv     = mb_substr($data, -1 * $saltSize, null, '8bit');
            if (fwrite($write, $result) !== mb_strlen($result, '8bit')) {
                return false;
            }
        }
        fclose($write);
        fclose($read);

        // check for data integrity
        if (! Utils::compareStrings($hmac, $hmacRead)) {
            unlink($fileOut);
            return false;
        }

        return true;
    }

    /**
     * Check that input file exists and output file don't
     *
     * @param  string $fileIn
     * @param  string $fileOut
     * @throws Exception\InvalidArgumentException
     */
    protected function checkFileInOut($fileIn, $fileOut)
    {
        if (! file_exists($fileIn)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'I cannot open the %s file',
                $fileIn
            ));
        }
        if (file_exists($fileOut)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The file %s already exists',
                $fileOut
            ));
        }
    }
}
