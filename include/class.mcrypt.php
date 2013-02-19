<?php
/*********************************************************************
    class.mcrypt.php

    Mcrypt wrapper.... nothing special at all.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
class Mcrypt {
    
    function encrypt($text, $salt) {
        global $ost;
        
        //if mcrypt extension is not installed--simply return unencryted text and log a warning (if enabled).
        if(!function_exists('mcrypt_encrypt') || !function_exists('mcrypt_decrypt')) {
            if($ost) {
                $msg='Cryptography extension mcrypt is not enabled or installed. Important text/data is being stored as plain text in database.';
                $ost->logWarning('mcrypt module missing', $msg);
            }

            return $text;
        }

        return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $salt, $text, MCRYPT_MODE_ECB,
                         mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
    }

    function decrypt($text, $salt) {

        if(!function_exists('mcrypt_encrypt') || !function_exists('mcrypt_decrypt'))
            return $text;

        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $salt, base64_decode($text), MCRYPT_MODE_ECB,
                        mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
    }

    function exists(){
        return (function_exists('mcrypt_encrypt') && function_exists('mcrypt_decrypt'));
    }
}
?>
