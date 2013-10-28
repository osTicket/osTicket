<?php
require_once "class.test.php";

class UndefinedMethods extends Test {
    var $name = "Access to undefined object methods";

    function testFindShortOpen() {
        $scripts = $this->getAllScripts(false);
        $function_defs = array();
        foreach ($scripts as $s) {
            $matches = array();
            preg_match_all('/^\s*(?:\/\*[^*]*\*\/)?\s*'
                    .'(?:(?:private|public|protected|static|abstract)\s+)*'
                    .'function\s+&?\s*([^(\s]+)\s*\(/m',
                file_get_contents($s), $matches);
            $function_defs = array_merge($function_defs, $matches[1]);
        }
        foreach (find_function_calls($scripts) as $call) {
            list($file, $no, $line, $func) = $call;
            if (!in_array($func, $function_defs)) {
                // We don't ship all of mdpf, so a bit of it looks undefined
                if (strpos($file, '/mpdf/') === false)
                    $this->fail($file, $no, "$func: Definitely undefined");
            }
            else {
                $this->pass();
            }
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
            preg_match_all('/^.*\w+(?:-[>]|::)([a-zA-Z0-9_]+)\(.*/', $line, $matches,
                PREG_SET_ORDER);
            foreach ($matches as $m)
                if (strpos($m[0], 'nolint') === false)
                    $calls[] = array($s, $lineno, $line, $m[1]);
        }
    }
    return $calls;
}

return 'UndefinedMethods';
?>
