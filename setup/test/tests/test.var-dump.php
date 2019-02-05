<?php
require_once "class.test.php";

class VarDump extends Test {
    var $name = "var_dump Checks";

    function testFindShortOpens() {
        $re = '/^(([\t ]*?)var_dump\(.*[\)|,|;])((?!nolint).)*$/m';
        foreach (static::getAllScripts() as $s) {
            $matches = array();
            $content = file_get_contents($s);
            if (preg_match_all($re,
                    $content, $matches,
                    PREG_OFFSET_CAPTURE) > 0) {
                foreach ($matches[0] as $match) {
                    $this->fail(
                        $s,
                        $this->line_number_for_offset($content, $match[1]),
                        trim($match[0]));
                }
            }
            else $this->pass();
        }
    }
}
return 'VarDump';
?>
