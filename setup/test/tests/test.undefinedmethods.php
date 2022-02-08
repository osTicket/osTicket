<?php
require_once "class.test.php";

class UndefinedMethods extends Test {
    var $name = "Access to undefined object methods";

    static function ignore3rdparty() {
        return false;
    }

    function testUndefinedMethods() {
        $scripts = static::getAllScripts();
        $function_defs = array();
        foreach ($scripts as $s) {
            $matches = array();
            preg_match_all('/^\s*(?:\/\*[^*]*\*\/)?\s*'
                    .'(?:(?:private|public|protected|static|abstract)\s+)*'
                    .'function\s+&?\s*([^(\s]+)\s*\(/m',
                file_get_contents($s), $matches);
            $function_defs = array_merge($function_defs, $matches[1]);
        }
        foreach (static::find_function_calls($scripts) as $call) {
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

return 'UndefinedMethods';
?>
