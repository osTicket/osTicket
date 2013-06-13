<?php
require_once "class.test.php";

class ExtraWhitespace extends Test {
    var $name = "PHP Leading and Trailing Whitespace";
    
    function testFindWhitespace() {
        foreach ($this->getAllScripts() as $s) {
            $matches = array();
            if (preg_match_all('/^\s+<\?php|\?>\n\s+$/s',
                    file_get_contents($s), $matches,
                    PREG_OFFSET_CAPTURE) > 0) {
                foreach ($matches[0] as $match)
                    $this->fail(
                        $s, $this->line_number_for_offset($s, $match[1]),
                        (strpos('?>', $match[0]) !== false)
                            ? 'Leading whitespace'
                            : 'Trailing whitespace');
            }
            else $this->pass();
        }
    }
}
return 'ExtraWhitespace';

?>
