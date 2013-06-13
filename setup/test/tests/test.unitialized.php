<?php
require_once "class.test.php";

class UnitializedVars extends Test {
    var $name = "Access to unitialized variables";

    function testUnitializedUsage() {
        $scripts = $this->getAllScripts();
        $matches = array();
        foreach (range(0, count($scripts), 40) as $start) {
            $slice = array_slice($scripts, $start, 40);
            ob_start();
            # XXX: This won't run well on Windoze
            system(dirname(__file__)."/lib/phplint.tcl ".implode(" ", $slice));
            $lint_errors = ob_get_clean();
            preg_match_all("/\* In (.*) line (\d+): access to uninitialized var '([^']+)'/m",
                    $lint_errors, $matches, PREG_SET_ORDER);
            foreach ($matches as $match)
                $this->fail($match[1], $match[2], "'\${$match[3]}'");
        }
    }
}

return 'UnitializedVars';
?>
