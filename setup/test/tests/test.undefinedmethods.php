<?php
require_once "class.test.php";

class UndefinedMethods extends Test {
    var $name = "Access to undefined object methods";

    function testFindShortOpen() {
        $scripts = $this->getAllScripts();
        $php_script_content='';
        foreach ($scripts as $s) {
            $php_script_content .= file_get_contents($s);
        }
        foreach (find_function_calls($scripts) as $call) {
            list($file, $no, $line, $func) = $call;
            if (!preg_match('/^\s*(\/\*[^*]*\*\/)?'."\s*function\s+&?\s*$func\\(/m", 
                    $php_script_content))
                $this->fail($file, $no, "$func: Definitely undefined");
            else
                $this->pass();
        }
    }
}

function find_function_calls($scripts) {
    $calls=array();
    foreach ($scripts as $s) {
        $lines = explode("\n", file_get_contents($s));
        $lineno=0;
        foreach ($lines as $line) {
            $lineno++; $matches=array();
            preg_match_all('/-[>]([a-zA-Z0-9]*)\(/', $line, $matches,
                PREG_SET_ORDER);
            foreach ($matches as $m) {
                $calls[] = array($s, $lineno, $line, $m[1]);
            }
        }
    }
    return $calls;
}

return 'UndefinedMethods';
?>
