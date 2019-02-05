<?php
require_once "class.test.php";

class SignalsTest extends Test {
    var $name = "Signals checks";

    /**
     * Ensures that each signal subscribed to has a sender somewhere else
     */
    function testFindSignalPublisher() {
        $scripts = static::getAllScripts();
        $matches = $published_signals = array();
        foreach ($scripts as $s)
            if (preg_match_all("/^ *Signal::send\('([^']+)'/m",
                    file_get_contents($s), $matches, PREG_SET_ORDER))
                foreach ($matches as $match)
                    $published_signals[] = $match[1];
        foreach ($scripts as $s) {
            $content = file_get_contents($s);
            if (preg_match_all("/^ *Signal::connect\('([^']+)'/m",
                    $content, $matches,
                    PREG_OFFSET_CAPTURE|PREG_SET_ORDER) > 0) {
                foreach ($matches as $match) {
                    $match = $match[1];
                    if (!in_array($match[0], $published_signals))
                        $this->fail(
                            $s, $this->line_number_for_offset($content, $match[1]),
                            "Signal '{$match[0]}' is never sent");
                    else
                        $this->pass();
                }
            }
        }
    }
}

return 'SignalsTest';
?>
