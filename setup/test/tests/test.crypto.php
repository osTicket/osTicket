<?php
require_once "class.test.php";
require_once INCLUDE_DIR."class.crypto.php";

class TestCrypto extends Test {
    var $name = "Crypto library tests";

    var $test_data = 'supercalifragilisticexpialidocious'; # notrans
    var $master = 'master'; # notrans

    var $passwords = array(
        'english-password',
        'CaseSensitive Password',
        '«ταБЬℓσ»',
        '٩(-̮̮̃-̃)۶ ٩(●̮̮̃•̃)۶ ٩(͡๏̯͡๏)۶ ٩(-̮̮̃•̃).',
        '发同讲说宅电的手机告的世全所回广讲说跟',
    );

    function testSimple() {
        $tests = array_merge(array($this->test_data), $this->passwords);
        foreach ($tests as $subject) {
            $enc = Crypto::encrypt($subject, $this->master, 'simple');
            $dec = Crypto::decrypt($enc, $this->master, 'simple');
            $this->assertEqual($dec, $subject,
                "{$subject}: Encryption failed closed loop");
            $this->assertNotEqual($enc, $subject,
                'Data was not encrypted');
            $this->assertNotEqual($enc, false, 'Encryption failed');
            $this->assertNotEqual($dec, false, 'Decryption failed');

            $dec = Crypto::decrypt($enc, $this->master, 'wrong');
            $this->assertNotEqual($dec, $this->test_data, 'Subkeys are broken');
        }
    }

    function _testLibrary($c, $tests) {
        $name = get_class($c);
        foreach ($tests as $id => $subject) {
            $dec = $c->decrypt(base64_decode($subject));
            $this->assertEqual($dec, $this->test_data, "$name: decryption incorrect");
            $this->assertNotEqual($dec, false, "$name: decryption FAILED");
        }
        $enc = $c->encrypt($this->test_data);
        $this->assertNotEqual($enc, $this->test_data,
            "$name: encryption cheaped out");
        $this->assertNotEqual($enc, false, "$name: encryption failed");

        $c->setKeys($this->master, 'wrong');
        $dec = $c->decrypt(base64_decode($subject));
        $this->assertEqual($dec, false, "$name: Subkeys are broken");

    }

    function testMcrypt() {
        $tests = array(
            1 => 'JDEkIEDoeaSiOUEGE5KQ3UmJpQ5+pUaX91HyLMG58GmNU9pZXAdiXXJsfl+7TSDlLczGD98UCD6tLuDIwI9XJLEwew==',
        );
        if (!CryptoMcrypt::exists())
            return $this->warn('Not testing mcrypt encryption');

        $c = new CryptoMcrypt(0);
        $c->setKeys($this->master, 'simple');
        $this->_testLibrary($c, $tests);
    }

    function testOpenSSL() {
        $tests = array(
            1 => 'JDEkRiLtWBgRN68kJjp4jsM6xKJY+XFYwMeaQIHJXKW8v3fEZzs3gCq3hKevgvAjvdgwx5ZUYLFPsFehLtkzAw8IlQ==',
        );
        if (!CryptoOpenSSL::exists())
            return $this->warn('Not testing openssl encryption');

        $c = new CryptoOpenSSL(0);
        $c->setKeys($this->master, 'simple');
        $this->_testLibrary($c, $tests);
    }

    function testPHPSecLib() {
        $tests = array(
            1 => 'JDEkvH/es2Drdsmc8pU2UBnBxhiPavtvst2Sl9jOYVXTRjHsgPmv8+8mIgwnA1nQ6EI2AoTq2gMZtoBoqK3Mzpw8IQ==',
        );
        if (!CryptoPHPSecLib::exists())
            return $this->warn('Not testing PHPSecLib encryption');

        $c = new CryptoPHPSecLib(0);
        $c->setKeys($this->master, 'simple');
        $this->_testLibrary($c, $tests);
    }

    function testRandom() {
        for ($i=1; $i<128; $i+=4) {
            $data = Crypto::random($i);
            $this->assertNotEqual($data, '', 'Empty random data generated');
            $this->assert(strlen($data) == $i,
                'Random data received was not the length requested');
        }
    }
}
return 'TestCrypto';
?>
