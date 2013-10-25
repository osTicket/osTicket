<?php
require_once "class.test.php";
require_once "class.php_analyze.php";

class UnitializedVars extends Test {
    var $name = "Access to unitialized variables";

    function testUnitializedUsage() {
        $scripts = $this->getAllScripts();
        $matches = array();
        foreach ($scripts as $s) {
            $a = new SourceAnalyzer($s);
            $a->parseFile();
            foreach ($a->bugs as $bug) {
                if ($bug['type'] == 'UNDEF_ACCESS') {
                    list($line, $file) = $bug['line'];
                    $this->fail($file, $line, "'{$bug['name']}'");
                }
                elseif ($bug['type'] == 'MAYBE_UNDEF_ACCESS') {
                    list($line, $file) = $bug['line'];
                    $this->warn("Possible access to NULL object @ $file : $line");
                }
            }
            if (!$a->bugs)
                $this->pass();
        }
    }
}

return 'UnitializedVars';
?>
