<?php

require_once INCLUDE_DIR.'class.validator.php';

class TestValidation extends Test {
    var $name = "Validation checks";

    function testValidUsernames() {
        // Ascii
        $this->assert(Validator::is_username('jared'));
        $this->assert(Validator::is_username('jared12'));
        // Unicode
        $this->assert(Validator::is_username('järed'));
        $this->assert(Validator::is_username('järed12'));
        $this->assert(Validator::is_username('中国期刊全文数据'));
        // Non-letters
        $this->assert(!Validator::is_username('j®red'));
        // Special chars
        $this->assert(Validator::is_username('jar.ed'));
        $this->assert(Validator::is_username('jar_ed'));
        $this->assert(Validator::is_username('jar-ed'));
        // Illegals
        $this->assert(!Validator::is_username('j red'));
        $this->assert(!Validator::is_username('jared '));
        $this->assert(!Validator::is_username(' jared'));
    }

    function testValidEmail() {
        // Common emails
        $this->assert(Validator::is_email('jared@domain.tld'));
        $this->assert(Validator::is_email('jared12@domain.tld'));
        $this->assert(Validator::is_email('jared.12@domain.tld'));
        $this->assert(Validator::is_email('jared_12@domain.tld'));
        $this->assert(Validator::is_email('jared-12@domain.tld'));
        $this->assert(Validator::is_email('jared+ost@domain.tld'));

        // Illegal or unsupported
        $this->assert(!Validator::is_email('jared r@domain.tld'));
        $this->assert(!Validator::is_email('jared'));
        $this->assert(!Validator::is_email('jared@'));
        $this->assert(!Validator::is_email('@domain.tld'));
        $this->assert(!Validator::is_email('@domain.tld, @domain2.tld'));

        // Odd cases, but legal
        $this->assert(Validator::is_email('jared@host'));
        $this->assert(Validator::is_email('jared@[127.0.0.1]'));
        $this->assert(Validator::is_email('jared@[ipv6:::1]'));
        $this->assert(Validator::is_email('*@domain.tld'));
        $this->assert(Validator::is_email("'@domain.tld"));
        $this->assert(Validator::is_email('"jared r"@domain.tld'));

        // RFC 6530
        #$this->assert(Validator::is_email('Pelé@example.com'));
        #$this->assert(Validator::is_email('δοκιμή@παράδειγμα.δοκιμή'));
        #$this->assert(Validator::is_email('甲斐@黒川.日本'));
    }

    function testIPAddresses() {

        // Validate IP Addreses
        $this->assert(Validator::is_ip('127.0.0.1'));
        $this->assert(Validator::is_ip('192.168.129.74'));

        // Test IP check
        $this->assert(Validator::check_ip('127.0.0.1', '127.0.0.0/24'));
        $this->assert(Validator::check_ip('192.168.129.42',
                    ['127.0.0.0/24', '192.168.129.0/24']));
        $this->assert(!Validator::check_ip('10.0.5.15', '127.0.0.0/24'));
    }
}
return 'TestValidation';
?>
