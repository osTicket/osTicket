<?php
require_once "class.test.php";
require_once INCLUDE_DIR."class.crypto.php";

class TestCrypto extends Test {
    var $name = "Tests des librairies crypto";

    var $test_data = 'supercalifragilisticexpialidocious'; # notrans
    var $master = 'master'; # notrans

    var $passwords = array(
        'mot-de-passe-français',
        'mot-de-passe sensible à la casse',
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
                "{$subject}: Echec du chiffrage de la boucle");
            $this->assertNotEqual($enc, $subject,
                'Les données n\'étaient pas chiffrées');
            $this->assertNotEqual($enc, false, 'Échec du chiffrage');
            $this->assertNotEqual($dec, false, 'Échec du déchiffrage');

            $dec = Crypto::decrypt($enc, $this->master, 'faux');
            $this->assertNotEqual($dec, $this->test_data, 'Les clés secondaires sont cassées');
        }
    }

    function _testLibrary($c, $tests) {
        $name = get_class($c);
        foreach ($tests as $id => $subject) {
            $dec = $c->decrypt(base64_decode($subject));
            $this->assertEqual($dec, $this->test_data, "$name: erreur de déchiffrement");
            $this->assertNotEqual($dec, false, "$name: Échec du déchiffrement");
        }
        $enc = $c->encrypt($this->test_data);
        $this->assertNotEqual($enc, $this->test_data,
            "$name: Chiffrement non réalisé");
        $this->assertNotEqual($enc, false, "$name: Échec du chiffrement");

        $c->setKeys($this->master, 'Faux');
        $dec = $c->decrypt(base64_decode($subject));
        $this->assertEqual($dec, false, "$name: Les clés secondaires sont cassées");

    }

    function testMcrypt() {
        $tests = array(
            1 => 'JDEkIEDoeaSiOUEGE5KQ3UmJpQ5+pUaX91HyLMG58GmNU9pZXAdiXXJsfl+7TSDlLczGD98UCD6tLuDIwI9XJLEwew==',
        );
        if (!CryptoMcrypt::exists())
            return $this->warn('Pas de test du chiffrement mcrypt');

        $c = new CryptoMcrypt(0);
        $c->setKeys($this->master, 'simple');
        $this->_testLibrary($c, $tests);
    }

    function testOpenSSL() {
        $tests = array(
            1 => 'JDEkRiLtWBgRN68kJjp4jsM6xKJY+XFYwMeaQIHJXKW8v3fEZzs3gCq3hKevgvAjvdgwx5ZUYLFPsFehLtkzAw8IlQ==',
        );
        if (!CryptoOpenSSL::exists())
            return $this->warn('Pas de test du chiffrement openssl');

        $c = new CryptoOpenSSL(0);
        $c->setKeys($this->master, 'simple');
        $this->_testLibrary($c, $tests);
    }

    function testPHPSecLib() {
        $tests = array(
            1 => 'JDEkvH/es2Drdsmc8pU2UBnBxhiPavtvst2Sl9jOYVXTRjHsgPmv8+8mIgwnA1nQ6EI2AoTq2gMZtoBoqK3Mzpw8IQ==',
        );
        if (!CryptoPHPSecLib::exists())
            return $this->warn('Pas de test du chiffrement PHPSecLib');

        $c = new CryptoPHPSecLib(0);
        $c->setKeys($this->master, 'simple');
        $this->_testLibrary($c, $tests);
    }

    function testRandom() {
        for ($i=1; $i<128; $i+=4) {
            $data = Crypto::random($i);
            $this->assertNotEqual($data, '', 'Les données aléatoires générées sont vides');
            $this->assert(strlen($data) == $i,
                'Les données aléatoires reçues ne font pas la longueur nécessaire');
        }
    }
}
return 'TestCrypto';
?>
