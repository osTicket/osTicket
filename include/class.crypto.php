<?php
/*********************************************************************
    class.crypto.php

    Crypto wrapper - implements tag based encryption/descryption

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Credit:
    https://defuse.ca/secure-php-encryption.htm
    Interwebz

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

//Top level encryption  options.
define('CRYPT_MCRYPT', 1);
define('CRYPT_OPENSSL', 2);
define('CRYPT_PHPSECLIB', 3);

require_once PEAR_DIR.'Crypt/Hash.php';
require_once PEAR_DIR.'Crypt/Random.php';

class Crypto {


    function encrypt($input, $key, $skey='encryption', $crypt=null) {

        //Gets preffered crypto.
        if(!($crypto =  Crypto::get($crypt)))
            return false;

        //Set master and subkeys
        $crypto->setKeys($key, $skey);

        if(!($ciphertext=$crypto->encrypt($input)))
            return false;

        return sprintf('$%d$%s', $crypto->getTagNumber(), $ciphertext);
    }

    function decrypt($ciphertext, $key, $skey='encryption') {

        if(!preg_match('/^\\$(\d)\\$(.*)/', $ciphertext, $result))
            return false;

        $crypt = $result[1]; //Crypt used..
        $ciphertext = $result[2]; //encrypted  input.

        //Get crypto....  based on the tag.
        if(!($crypto=Crypto::get($crypt)) || !$crypto->exists())
            return false;

        $crypto->setKeys($key, $skey);

        return $crypto->decrypt($ciphertext);
    }

    function get($crypt) {

        $cryptos = self::cryptos();
        if(!$cryptos || ($crypt && !isset($cryptos[$crypt])))
            return null;

        //Requested crypto available??
        if($crypt &&  $cryptos[$crypt])
            return $cryptos[$crypt];

        //cycle thro' available cryptos
        foreach($cryptos as $crypto) {
            if(is_callable(array($crypto, 'exists'))
                    && call_user_func(array($crypto, 'exists')))
                return  $crypto;
        }

        return null;
    }

    function cryptos() {

        //list of available  && supported cryptos
        $cryptos =  array();
        if(defined('CRYPT_MCRYPT') && class_exists('CryptoMcrypt'))
            $cryptos[CRYPT_MCRYPT] = new CryptoMcrypt(CRYPT_MCRYPT);

        if(defined('CRYPT_OPENSSL') && class_exists('CryptoOpenSSL'))
            $cryptos[CRYPT_OPENSSL] = new CryptoOpenSSL(CRYPT_OPENSSL);

        if(defined('CRYPT_PHPSECLIB') && class_exists('CryptoPHPSecLib'))
            $cryptos[CRYPT_PHPSECLIB] = new CryptoPHPSecLib(CRYPT_PHPSECLIB);

        //var_dump($cryptos);

        return $cryptos;
    }

    /* Hash string  - sha1 is used by default */
    function hash($string, $key) {
        $hash = new Crypt_Hash();
        $hash->setKey($key);
        return base64_encode($hash->hash($string));
    }

    /* Generates random string of @len length */
    function randcode($len) {
        return crypt_random_string($len);
    }
}

class CryptoAlgo {

    var $master_key;
    var $sub_key;

    var $tag_number;

    function  CryptoAlgo($tag) {
        $this->tag_number = $tag;
    }

    function getTagNumber() {
        return $this->tag_number;
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

    function getKeyHash($seed) {

        $hash = Crypto::hash($this->getMasterKey().md5($this->getSubKey()), $seed);
        return $seed? substr($hash, 0, strlen($seed)) : $hash;
    }
}

define('CRYPTO_CIPHER_MCRYPT_RIJNDAEL_128', 1);

Class CryptoMcrypt extends CryptoAlgo {

    var $ciphers = array(
            CRYPTO_CIPHER_MCRYPT_RIJNDAEL_128 => array(
                'name' => MCRYPT_RIJNDAEL_128,
                'mode' => 'cbc',
                ),
            );

    function getCipher($id) {

        return ($id && isset($this->ciphers[$id]))
            ? $this->ciphers[$id] : null;
    }

    function encrypt($text, $cid=CRYPTO_CIPHER_MCRYPT_RIJNDAEL_128) {

        if(!$this->exists() || !$text || !($cipher=$this->getCipher($cid)))
            return false;

        $td = mcrypt_module_open($cipher['name'], '', $cipher['mode'], '');
        $keysize = mcrypt_enc_get_key_size($td);
        $ivsize = mcrypt_enc_get_iv_size($td);
        $iv = mcrypt_create_iv($ivsize, MCRYPT_DEV_RANDOM); //XXX: Windows??

        //Add padding
        $blocksize = mcrypt_enc_get_block_size($td);
        $pad = $blocksize - (strlen($text) % $blocksize);
        $text .= str_repeat(chr($pad), $pad);

        // Do the encryption.
        mcrypt_generic_init($td, $this->getKeyHash($iv), $iv);
        $ciphertext = $iv . mcrypt_generic($td, $text);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return sprintf('$%s$%s', $cid, $ciphertext);
    }

    function decrypt($ciphertext) {

         if(!$this->exists() || !$ciphertext)
             return false;

         if(!preg_match('/^\\$(\d)\\$(.*)/', $ciphertext, $result)
                 || !($cipher=$this->getCipher($result[1])))
             return false;

         $ciphertext = $result[2];

         $td = mcrypt_module_open($cipher['name'], '', $cipher['mode'], '');
         $keysize = mcrypt_enc_get_key_size($td);
         $ivsize = mcrypt_enc_get_iv_size($td);

         if(strlen($ciphertext) <= $ivsize)
             return false;

         $iv = substr($ciphertext, 0, $ivsize);
         $ciphertext = substr($ciphertext, $ivsize);

         // Do the decryption.
         mcrypt_generic_init($td, $this->getKeyHash($iv), $iv);
         $plaintext = mdecrypt_generic($td, $ciphertext);
         mcrypt_generic_deinit($td);
         mcrypt_module_close($td);

         // Remove the padding.
         $pad = ord($plaintext[strlen($plaintext) -1]);
         $plaintext = substr($plaintext, 0, strlen($plaintext) - $pad);

         return $plaintext;
    }

    function exists() {
        return extension_loaded('mcrypt');
    }
}

define('CRYPTO_CIPHER_OPENSSL_AES_128_CBC', 1);

class CryptoOpenSSL extends CryptoAlgo {

    var $ciphers = array(
            CRYPTO_CIPHER_OPENSSL_AES_128_CBC => array(
                'method' => 'aes-128-cbc',
                ),
            );

    function getMethod($cid) {
        return ($cid && isset($this->ciphers[$cid])) ?
            $this->ciphers[$cid]['method'] : null;
    }

    function encrypt($text, $cid=CRYPTO_CIPHER_OPENSSL_AES_128_CBC) {

        if(!$this->exists() || !$text || !($method=$this->getMethod($cid)))
            return false;

        $ivlen  = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivlen);

        if(!($ciphertext = openssl_encrypt($text, $method,
                        $this->getKeyHash($iv), 0, $iv)))
            return false;

        return sprintf('$%s$%s%s', $cid, $iv, $ciphertext);
    }

    function decrypt($ciphertext) {


        if(!$this->exists() || !$ciphertext)
            return false;

        if(!preg_match('/^\\$(\d)\\$(.*)/', $ciphertext, $result)
                ||
                !($method=$this->getMethod($result[1])))
            return false;

        $ciphertext = $result[2];

        $ivlen  = openssl_cipher_iv_length($method);
        $iv = substr($ciphertext, 0, $ivlen);
        $ciphertext = substr($ciphertext, $ivlen);

        $plaintext = openssl_decrypt($ciphertext, $method,
                $this->getKeyHash($iv), 0, $iv);

        return $plaintext;
    }

    function exists() {
        return  extension_loaded('openssl');
    }
}


require_once PEAR_DIR.'Crypt/AES.php';
define('CRYPTO_CIPHER_PHPSECLIB_AES_CBC', 1);

class CryptoPHPSecLib extends CryptoAlgo {
    var $ciphers = array(
            CRYPTO_CIPHER_PHPSECLIB_AES_CBC => array(
                'mode' => CRYPT_AES_MODE_CBC,
                'ivlen' => 16,
                ),
            );

    function getCrypto($cid) {

        if(!$cid || !isset($this->ciphers[$cid])
                || !($cipher=$this->ciphers[$cid]))
            return false;

        $crypto = null;
        switch($cid) {
            case CRYPTO_CIPHER_PHPSECLIB_AES_CBC:
                $crypto = new Crypt_AES($cipher['mode']);
                break;
        }

        return $crypto;
    }

    function getIVLen($cid) {
        return ($cid && !isset($this->ciphers[$cid]['ivlen'])) ?
            $this->ciphers[$cid]['ivlen'] : 16;
    }

    function encrypt($text, $cid=CRYPTO_CIPHER_PHPSECLIB_AES_CBC) {

        if(!$this->exists() || !$text || !($crypto=$this->getCrypto($cid)))
            return false;

        $iv = Crypto::randcode($this->getIVLen($cid));
        $crypto->setKey($this->getKeyHash($iv));
        $crypto->setIV($iv);

        return sprintf('$%s$%s%s', $cid, $iv, $crypto->encrypt($text));
    }

    function decrypt($ciphertext) {

        if(!$this->exists() || !$ciphertext)
            return false;

        if(!preg_match('/^\\$(\d)\\$(.*)/', $ciphertext, $result)
                || !($crypto=$this->getCrypto($result[1])))
            return false;

        $ciphertext = $result[2];
        $ivlen = $this->getIVLen($result[1]);
        $iv = substr($result[2], 0, $ivlen);
        $ciphertext = substr($result[2], $ivlen);
        $crypto->setKey($this->getKeyHash($iv));
        $crypto->setIV($iv);

        return $crypto->decrypt($ciphertext);
    }

    function exists() {
        return  class_exists('Crypt_AES');
    }
}
?>
