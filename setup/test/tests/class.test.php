<?php

class Test {
    var $fails = array();
    var $name = "";

    var $third_party_paths = array(
        '/include/JSON.php',
        '/include/htmLawed.php',
        '/include/PasswordHash.php',
        '/include/pear/',
        '/include/Spyc.php',
    );

    function Test() {
        call_user_func_array(array($this, '__construct'), func_get_args());
    }
    function __construct() {
        assert_options(ASSERT_CALLBACK, array($this, 'fail'));
        error_reporting(E_ALL & ~E_WARNING);
    }

    function setup() {
    }

    function teardown() {
    }

    /*static*/ function getAllScripts() {
        $root = get_osticket_root_path();
        $scripts = array();
        foreach (glob_recursive("$root/*.php") as $s) {
            $found = false;
            foreach ($this->third_party_paths as $p) {
                if (strpos($s, $p) !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found)
                $scripts[] = $s;
        }
        return $scripts;
    }

    function fail($script, $line, $message) {
        $this->fails[] = array(get_class($this), $script, $line, $message);
        fputs(STDOUT, 'F');
    }

    function pass() {
        fputs(STDOUT, ".");
    }

    function run() {
        $rc = new ReflectionClass(get_class($this));
        foreach ($rc->getMethods() as $m) {
            if (stripos($m->name, 'test') === 0) {
                $this->setup();
                call_user_func(array($this, $m->name));
                $this->teardown();
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
?>
