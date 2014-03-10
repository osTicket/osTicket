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
    }
}
return 'TestValidation';
?>
