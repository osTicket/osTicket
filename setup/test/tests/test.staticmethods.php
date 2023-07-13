<?php
require_once "class.test.php";

class StaticMethods extends Test {
    var $name = "Methods that should be static";

    static function ignore3rdparty() {
        return false;
    }

    function testStaticMethods() {
        $scripts = static::getAllScripts();
        $static_functions = array();
        foreach ($scripts as $s) {
            $matches = array();
            preg_match_all('/^\s*(?:\/\*[^*]*\*\/)?\s*'
                    .'(?:(?:private|public|protected|static|abstract)\s+)+'
                    .'function\s+&?\s*([^(\s]+)\s*\(/m',
                file_get_contents($s), $matches);
            $static_functions = array_merge($static_functions, $matches[1]);
        }
        foreach (static::find_function_calls($scripts) as $call) {
            list($file, $no, $line, $func) = $call;
            if ((strpos($line, 'parent::'.$func) === false) &&
                (strpos($line, 'Unpacker::'.$func) === false) &&
                (strpos($line, '::'.$func) !== false) && !in_array($func, $static_functions)) {
                $this->fail($file, $no, "$func: Function should be static");
            }
            else {
                $this->pass();
            }
        }
    }
}

return 'StaticMethods';
?>
