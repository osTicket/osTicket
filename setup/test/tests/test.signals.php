<?php
require_once "class.test.php";

class SignalsTest extends Test {
    var $name = "Signals checks";

    /**
     * Ensures that each signal subscribed to has a sender somewhere else
     */
    function testFindSignalPublisher() {
        $scripts = $this->getAllScripts();
        $matches = $published_signals = array();
        foreach ($scripts as $s)
            if (preg_match_all("/^ *Signal::send\('([^']+)'/m",
                    file_get_contents($s), $matches, PREG_SET_ORDER))
                foreach ($matches as $match)
                    $published_signals[] = $match[1];
        foreach ($scripts as $s) {
            if (preg_match_all("/^ *Signal::connect\('([^']+)'/m",
                    file_get_contents($s), $matches,
                    PREG_OFFSET_CAPTURE|PREG_SET_ORDER) > 0) {
                foreach ($matches as $match) {
                    $match = $match[1];
                    if (!in_array($match[0], $published_signals))
                        $this->fail(
                            $s, self::line_number_for_offset($s, $match[1]),
                            "Signal '{$match[0]}' is never sent");
                    else
                        $this->pass();
                }
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
}

return 'SignalsTest';
?>
