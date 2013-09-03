<?php
require_once INCLUDE_DIR.'class.migrater.php';

class CryptoMigrater extends MigrationTask {
    var $description = "Migrating encrypted password";
    var $status ='Making the world a better place!';

    function run() {

        $sql='SELECT email_id, userpass, userid FROM '.EMAIL_TABLE
            ." WHERE userpass <> ''";
        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($id, $passwd, $username) = db_fetch_row($res)) {
                if(!$passwd) continue;
                $ciphertext = Crypto::encrypt(self::_decrypt($passwd, SECRET_SALT), SECRET_SALT, $username);
                $sql='UPDATE '.EMAIL_TABLE
                    .' SET userpass='.db_input($ciphertext)
                    .' WHERE email_id='.db_input($id);
                db_query($sql);
            }
        }
    }

    /*
      XXX: This is not a  good way of decrypting data - use to descrypt old
      data.
     */
    function _decrypt($text, $salt) {

        if(!function_exists('mcrypt_encrypt') || !function_exists('mcrypt_decrypt'))
            return $text;

        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $salt, base64_decode($text), MCRYPT_MODE_ECB,
                        mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
    }
}
return 'CryptoMigrater';
?>
