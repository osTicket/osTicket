<?php
require_once "class.test.php";

class GitConflicts extends Test {
    var $name = "Git Conflicts Checks";

    function testFindGitConflicts() {
        $regex = '/^[\t ]*?<{3,} ?HEAD[\t ]*?|^[\t ]*?>{3,}[\t ]*?/m';
        foreach (static::getAllScripts('*') as $s) {
            $matches = array();
            $content = file_get_contents($s);
            if (preg_match_all($regex,
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
return 'GitConflicts';
?>
