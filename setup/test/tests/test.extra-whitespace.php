<?php
require_once "class.test.php";

class ExtraWhitespace extends Test {
    var $name = "PHP Espaces avant et après";
    
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
                            ? 'Espace au début'
                            : 'Espace à la fin');
            }
            else $this->pass();
        }
    }
}
return 'ExtraWhitespace';

?>
