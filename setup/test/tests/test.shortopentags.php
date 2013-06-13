<?php
require_once "class.test.php";

class ShortOpenTag extends Test {
    var $name = "PHP Short Open Checks";

    function testFindShortOpens() {
        foreach ($this->getAllScripts() as $s) {
            $matches = array();
            if (preg_match_all('/<\?\s*(?!php|xml).*$/m',
                    file_get_contents($s), $matches,
                    PREG_OFFSET_CAPTURE) > 0) {
                foreach ($matches[0] as $match)
                    $this->fail(
                        $s,
                        line_number_for_offset($s, $match[1]),
                        $match[0]);
            }
            else $this->pass();
        }
    }
}

function line_number_for_offset($filename, $offset) {
    $lines = file($filename);
    $bytes = $line = 0;
    while ($bytes < $offset) {
        $bytes += strlen(array_shift($lines));
        $line += 1;
    }
    return $line;
}

return 'ShortOpenTag';
?>
