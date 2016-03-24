<?php
/*********************************************************************
    class.crypto.php

    Crypto wrapper - provides encryption/decryption utility

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Credit:
    *https://defuse.ca/secure-php-encryption.htm
    *Interwebz

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

//Top level encryption  options.
define('CRYPT_MCRYPT', 1);
define('CRYPT_OPENSSL', 2);
define('CRYPT_PHPSECLIB', 3);

define('CRYPT_IS_WINDOWS', !strncasecmp(PHP_OS, 'WIN', 3));


require_once INCLUDE_DIR.'class.base32.php';
require_once PEAR_DIR.'Crypt/Hash.php';
require_once PEAR_DIR.'Crypt/AES.php';

/**
 * Class: Crypto
 *
 * Pluggable encryption/decryption utility which allows for automatic
 * algorithm detection of encrypted data as well as automatic upgrading (on
 * encrypt()) of existing data.
 *
 * The utility makes use of a subkey tecnhique where the master key used to
 * encrypt and decrypt data is not used by itself the encrypt the data.
 * Another key, called the subkey, is mixed with the master key to generate
 * the key used to perform the encryption. This means that the same key is
 * not used to encrypt all data stored in the database.
 *
 * The encryption process will select a library to perform the low-level
 * encryption automatically based on the PHP extensions currently available.
 * Therefore, the best encryption library, algorithm, and configuration will
 * be used to perform the encryption.
 */
class Crypto {

    /**
     * Encrypt data using two keys. The first key is considered a 'Master
     * Key' which might be used to encrypt different kinds of data in your
     * system. Using a subkey allows the master key to be reused in a way
     * that two encrypted messages can be encrypted with the same master key
     * but have different namespaces as it were. It allows various parts of
     * the application to encrypt things using a common master key that are
     * not recoverable by other parts of the application.
     *
     * Parameters:
     * input - (string) text subject that is to be encrypted
     * key - (string) master key used for the encryption
     * skey - (string:optional) sub-key or namespace of the encryption
     *      context
     * crypt - (int:optional) Cryto tag id used for the encryption. This
     *      is only really useful for testing. The crypto library will be
     *      automatically selected based on the available PHP extensions.
     */
    function encrypt($input, $key, $skey='encryption', $crypt=null) {

        //Gets preffered crypto.
        if(!($crypto =  Crypto::get($crypt)))
            return false;

        //Set master and subkeys
        $crypto->setKeys($key, $skey);

        if(!($ciphertext=$crypto->encrypt($input)))
            return false;

        return sprintf('$%d$%s',
                $crypto->getTagNumber(),
                base64_encode($ciphertext));
    }

    /**
     * Decrypt data originally returned from ::encrypt() using the two keys
     * that were originially passed into the ::encrypt() method.
     *
     * Parameters:
     * ciphertext - (string<binary>) Unencoded data returned from the
     *      ::encrypt() method
     * key - (string) master key used for the encryption originally
     * skey - (string_ sub key or namespace used originally for the
     *      encryption
     */
    function decrypt($ciphertext, $key, $skey='encryption') {

        if(!$key || !$ciphertext || $ciphertext[0] != '$')
            return false;

        list(, $crypt, $ciphertext) = explode('$', $ciphertext, 3);
        //Get crypto....  based on crypt tag.
        if(!$crypt || !($crypto=Crypto::get($crypt)) || !$crypto->exists())
            return false;

        $crypto->setKeys($key, $skey);

        return $crypto->decrypt(base64_decode($ciphertext));
    }

    function get($crypt) {

        $cryptos = self::cryptos();
        if(!$cryptos || ($crypt && !isset($cryptos[$crypt])))
            return null;

        //Requested crypto available??
        if($crypt) return $cryptos[$crypt];

        //cycle thru' available cryptos
        foreach($cryptos as $crypto)
            if(call_user_func(array($crypto, 'exists')))
                return  $crypto;

        return null;
    }

    /*
     *  Returns list of supported cryptos
     *
     */
    function cryptos() {

        static $cryptos = false;

        if ($cryptos === false) {
            $cryptos =  array();

            if(defined('CRYPT_OPENSSL') && class_exists('CryptoOpenSSL'))
                $cryptos[CRYPT_OPENSSL] = new CryptoOpenSSL(CRYPT_OPENSSL);

            if(defined('CRYPT_MCRYPT') && class_exists('CryptoMcrypt'))
                $cryptos[CRYPT_MCRYPT] = new CryptoMcrypt(CRYPT_MCRYPT);

            if(defined('CRYPT_PHPSECLIB') && class_exists('CryptoPHPSecLib'))
                $cryptos[CRYPT_PHPSECLIB] = new CryptoPHPSecLib(CRYPT_PHPSECLIB);
        }

        return $cryptos;
    }

    function hash($string, $key) {
        $hash = new Crypt_Hash('sha512');
        $hash->setKey($key);
        return $hash->hash($string);
    }

    /*
      Random String Generator
      Credit: The routine borrows heavily from PHPSecLib's Crypt_Random
      package.
     */
    function random($len) {

        if(CRYPT_IS_WINDOWS) {
            if (function_exists('openssl_random_pseudo_bytes')
                    && version_compare(PHP_VERSION, '5.3.4', '>='))
                return openssl_random_pseudo_bytes($len);

            // Looks like mcrypt_create_iv with MCRYPT_DEV_RANDOM is still
            // unreliable on 5.3.6:
            // https://bugs.php.net/bug.php?id=52523
            if (function_exists('mcrypt_create_iv')
                    && version_compare(PHP_VERSION, '5.3.7', '>='))
                return mcrypt_create_iv($len);

        } else {

            if (function_exists('openssl_random_pseudo_bytes'))
                return openssl_random_pseudo_bytes($len);

            static $fp = null;
            if ($fp == null)
                $fp = @fopen('/dev/urandom', 'rb');

            if ($fp)
                return fread($fp, $len);

            if (function_exists('mcrypt_create_iv'))
                return mcrypt_create_iv($len, MCRYPT_DEV_URANDOM);
        }

        $seed = session_id().microtime().getmypid();
        $key = pack('H*', sha1($seed . 'A'));
        $iv = pack('H*', sha1($seed . 'C'));
        $crypto = new Crypt_AES(CRYPT_AES_MODE_CTR);
        $crypto->setKey($key);
        $crypto->setIV($iv);
        $crypto->enableContinuousBuffer(); //Sliding iv.
        $start = mt_rand(5, PHP_INT_MAX);
        $output ='';
        for($i=$start; strlen($output)<$len; $i++)
            $output.= $crypto->encrypt($i);

        return substr($output, 0, $len);
    }
}

/**
 * Class: CryptoAlgo
 *
 * Abstract cryptographic library implementation. This class is intended to
 * be extended and implemented for a particular PHP extension, such as
 * mcrypt, openssl, etc.
 *
 * The class implies but does not define abstract methods for encrypt() and
 * decrypt() which will perform the respective operations on the text
 * subjects using a specific library.
 */
/* abstract */
class CryptoAlgo {

    var $master_key;
    var $sub_key;

    var $tag_number;

    var $ciphers = null;

    function __construct($tag) {
        $this->tag_number = $tag;
    }

    function getTagNumber() {
        return $this->tag_number;
    }

    function getCipher($cid, $callback=null) {

        if(!$this->ciphers)
            return null;

        $cipher = null;
        if($cid)
            $cipher =  isset($this->ciphers[$cid]) ? $this->ciphers[$cid] : null;
        elseif($this->ciphers) { // search best available.
            foreach($this->ciphers as $k => $c) {
                if(!$callback
                        || (is_callable($callback)
                            && call_user_func($callback, $c))) {
                    $cid = $k;
                    $cipher = $c;
                    break;
                }
            }
        }

        return $cipher ?
            array_merge($cipher, array('cid' => $cid)) : null;
    }

    function getMasterKey() {
        return $this->master_key;
    }

    function getSubKey() {
        return $this->sub_key;
    }

    function setKeys($master, $sub) {
        $this->master_key = $master;
        $this->sub_key = $sub;
    }

    /**
     * Function: getKeyHash
     *
     * Utility function to retrieve the encryption key used by the
     * encryption algorithm based on the master key, the sub-key, and some
     * known, random seed. The hash returned should be used as the binary key
     * for the encryption algorithm.
     *
     * Parameters:
     * seed - (string) third-level seed for the encryption key. This will
     *      likely an IV or salt value
     * len - (int) length of the desired hash
     */
    function getKeyHash($seed, $len=32) {

        $hash = Crypto::hash($this->getMasterKey().md5($this->getSubKey()), $seed);
        return substr($hash, 0, $len);
    }

    /**
     * Determines if the library used to implement encryption is currently
     * available and supported. This method is abstract and should be
     * defined in extension classes.
     */
    /* abstract */
    function exists() { return false; }


}


/**
 * Class: CryptoMcrypt
 *
 * Mcrypt library encryption implementation. This allows for encrypting and
 * decrypting text using the php_mcrypt extension.
 *
 * NOTE: Don't instanciate this class directly. Use Crypt::encrypt() and
 * Crypt::decrypt() to encrypt data.
 */

define('CRYPTO_CIPHER_MCRYPT_RIJNDAEL_128', 1);
if(!defined('MCRYPT_RIJNDAEL_128')):
define('MCRYPT_RIJNDAEL_128', '');
endif;

Class CryptoMcrypt extends CryptoAlgo {

    # WARNING: Change and you will lose your passwords ...
    var $ciphers = array(
            CRYPTO_CIPHER_MCRYPT_RIJNDAEL_128 => array(
                'name' => MCRYPT_RIJNDAEL_128,
                'mode' => 'cbc',
                ),
            );

    function getCipher($cid=null, $callback=false) {
        return parent::getCipher($cid, $callback ?: array($this, '_checkCipher'));
    }

   function _checkCipher($c) {

       return ($c
               && $c['name']
               && $c['mode']
               && $this->exists()
               && mcrypt_module_open($c['name'], '', $c['mode'], '')
               );
    }

    /**
     * Encrypt clear-text data using the mycrpt library. Optionally, a
     * configuration tag-id can be passed as the second parameter to specify
     * the actual encryption algorithm to be used.
     *
     * Parameters:
     * text - (string) clear text subject to be encrypted
     * cid - (int) encryption configuration to be used. @see $this->ciphers
     */
    function encrypt($text, $cid=0) {

        if(!$this->exists()
                || !$text
                || !($cipher=$this->getCipher($cid))
                || !$cipher['cid'])
            return false;

        if(!($td = mcrypt_module_open($cipher['name'], '', $cipher['mode'], '')))
            return false;

        $keysize = mcrypt_enc_get_key_size($td);
        $ivsize = mcrypt_enc_get_iv_size($td);
        $iv = Crypto::random($ivsize);

        //Add padding
        $blocksize = mcrypt_enc_get_block_size($td);
        $pad = $blocksize - (strlen($text) % $blocksize);
        $text .= str_repeat(chr($pad), $pad);

        // Do the encryption.
        mcrypt_generic_init($td, $this->getKeyHash($iv, $ivsize), $iv);
        $ciphertext = $iv . mcrypt_generic($td, $text);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return sprintf('$%s$%s', $cipher['cid'], $ciphertext);
    }

    /**
     * Recover text that was originally encrypted using this library with
     * the ::encrypt() method.
     *
     * Parameters:
     * text - (string<binary>) Unencoded, binary string which is the result
     *      of the ::encrypt() method.
     */
    function decrypt($ciphertext) {

         if(!$this->exists()
                 || !$ciphertext
                 || $ciphertext[0] != '$'
                 )
             return false;

         list(, $cid, $ciphertext) = explode('$', $ciphertext, 3);

         if(!$cid
                 || !$ciphertext
                 || !($cipher=$this->getCipher($cid))
                 || $cipher['cid']!=$cid)
             return false;

         if(!($td = mcrypt_module_open($cipher['name'], '', $cipher['mode'], '')))
             return false;

         $keysize = mcrypt_enc_get_key_size($td);
         $ivsize = mcrypt_enc_get_iv_size($td);

         $iv = substr($ciphertext, 0, $ivsize);
         if (!($ciphertext = substr($ciphertext, $ivsize)))
            return false;

         // Do the decryption.
         mcrypt_generic_init($td, $this->getKeyHash($iv, $ivsize), $iv);
         $plaintext = mdecrypt_generic($td, $ciphertext);
         mcrypt_generic_deinit($td);
         mcrypt_module_close($td);

         // Remove the padding.
         $pad = ord($plaintext[strlen($plaintext) -1]);
         $plaintext = substr($plaintext, 0, strlen($plaintext) - $pad);

         return $plaintext;
    }

    function exists() {
        return (extension_loaded('mcrypt')
                && function_exists('mcrypt_module_open'));
    }
}


/**
 * Class: CryptoOpenSSL
 *
 * OpenSSL library encryption implementation. This allows for encrypting and
 * decrypting text using the php_openssl extension.
 *
 * NOTE: Don't instanciate this class directly. Use Crypt::encrypt() and
 * Crypt::decrypt() to encrypt data.
 */

define('CRYPTO_CIPHER_OPENSSL_AES_128_CBC', 1);

class CryptoOpenSSL extends CryptoAlgo {

    # WARNING: Change and you will lose your passwords ...
    var $ciphers = array(
            CRYPTO_CIPHER_OPENSSL_AES_128_CBC => array(
                'method' => 'aes-128-cbc',
                ),
            );

    function getMethod($cid) {

        return (($cipher=$this->getCipher($cid)))
            ? $cipher['method']: '';
    }

    function getCipher($cid=null, $callback=false) {
        return parent::getCipher($cid, $callback ?: array($this, '_checkCipher'));
    }

    function _checkCipher($c) {

        return ($c
                && $c['method']
                && $this->exists()
                && openssl_cipher_iv_length($c['method'])
                );
    }

    /**
     * Encrypt clear-text data using the openssl library. Optionally, a
     * configuration tag-id can be passed as the second parameter to specify
     * the actual encryption algorithm to be used.
     *
     * Parameters:
     * text - (string) clear text subject to be encrypted
     * cid - (int) encryption configuration to be used. @see $this->ciphers
     */
    function encrypt($text, $cid=0) {

        if(!$this->exists()
                || !$text
                || !($cipher=$this->getCipher($cid)))
            return false;

        $ivlen  = openssl_cipher_iv_length($cipher['method']);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $key = $this->getKeyHash($iv, $ivlen);

        $options = (defined('OPENSSL_RAW_DATA')) ? OPENSSL_RAW_DATA : true;
        if(!($ciphertext = openssl_encrypt($text, $cipher['method'], $key,
                $options, $iv)))
            return false;

        return sprintf('$%s$%s%s', $cipher['cid'], $iv, $ciphertext);
    }

    /**
     * Decrypt and recover text originally encrypted using the ::encrypt()
     * method of this class.
     *
     * Parameters:
     * text - (string<binary>) Unencoded, binary string which is the result
     *      of the ::encrypt() method.
     */
    function decrypt($ciphertext) {


        if(!$this->exists() || !$ciphertext || $ciphertext[0] != '$')
            return false;

        list(, $cid, $ciphertext) = explode('$', $ciphertext, 3);

         if(!$cid
                 || !$ciphertext
                 || !($cipher=$this->getCipher($cid)))
             return false;

        $ivlen  = openssl_cipher_iv_length($cipher['method']);
        $iv = substr($ciphertext, 0, $ivlen);
        $ciphertext = substr($ciphertext, $ivlen);
        $key = $this->getKeyHash($iv, $ivlen);

        $options = (defined('OPENSSL_RAW_DATA')) ? OPENSSL_RAW_DATA : true;
        $plaintext = openssl_decrypt($ciphertext, $cipher['method'], $key,
            $options, $iv);

        return $plaintext;
    }

    function exists() {
        return  (extension_loaded('openssl') && function_exists('openssl_cipher_iv_length'));
    }
}


/**
 * Class: CryptoPHPSecLib
 *
 * Pure PHP source library encryption implementation using phpseclib. This
 * allows for encrypting and decrypting text when no compiled library is
 * available for use.
 *
 * NOTE: Don't instanciate this class directly. Use Crypt::encrypt() and
 * Crypt::decrypt() to encrypt data.
 */

define('CRYPTO_CIPHER_PHPSECLIB_AES_CBC', 1);

class CryptoPHPSecLib extends CryptoAlgo {

    var $ciphers = array(
            CRYPTO_CIPHER_PHPSECLIB_AES_CBC => array(
                'mode' => CRYPT_AES_MODE_CBC,
                'ivlen' => 16,  #WARNING: DO NOT CHANGE!
                'class' => 'Crypt_AES',
                ),
            );


    function getCrypto($cid) {
        if(!$cid
                || !($c=$this->getCipher($cid))
                || !$this->_checkCipher($c))
            return null;

        $class = $c['class'];

        return new $class($c['mode']);
    }

    function getCipher($cid=null, $callback=false) {
        return  parent::getCipher($cid, $callback ?: array($this, '_checkCipher'));
    }

    function _checkCipher($c) {

        return ($c
                && $c['mode']
                && $c['ivlen']
                && $c['class']
                && class_exists($c['class']));
    }

    function encrypt($text, $cid=0) {

        if(!$this->exists()
                || !$text
                || !($cipher=$this->getCipher($cid))
                || !($crypto=$this->getCrypto($cipher['cid']))
                )
            return false;

        $ivlen = $cipher['ivlen'];
        $iv = Crypto::random($ivlen);
        $crypto->setKey($this->getKeyHash($iv, $ivlen));
        $crypto->setIV($iv);

        return sprintf('$%s$%s%s', $cipher['cid'], $iv, $crypto->encrypt($text));
    }

    function decrypt($ciphertext) {

        if(!$this->exists() || !$ciphertext || $ciphertext[0] != '$')
            return false;

        list(, $cid, $ciphertext) = explode('$', $ciphertext, 3);
         if(!$cid
                 || !$ciphertext
                 || !($cipher=$this->getCipher($cid))
                 || !($crypto=$this->getCrypto($cipher['cid']))
                 )
             return false;

        $ivlen = $cipher['ivlen'];
        $iv = substr($ciphertext, 0, $ivlen);
        if (!($ciphertext = substr($ciphertext, $ivlen)))
            return false;

        $crypto->setKey($this->getKeyHash($iv, $ivlen));
        $crypto->setIV($iv);

        return $crypto->decrypt($ciphertext);
    }

    function exists() {
        return  class_exists('Crypt_AES');
    }
}
?>
