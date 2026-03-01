<?php
require_once "class.test.php";

class JsSyntaxTest extends Test {
    var $name = "JavaScript Syntax Checks";

    function testLintErrors() {
        $exit = 0;
        foreach (static::getAllScripts('*.js') as $s) {
            ob_start();
            system("jsl -process $s", $exit);
            $line = ob_get_contents();
            ob_end_clean();
            if ($exit == 3)
                $this->fail($s, 0, $line);
            else
                $this->pass();
        }
    }
}

return 'JsSyntaxTest';
?>
