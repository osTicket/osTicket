<?php

class Test {
    var $fails = array();
    var $warnings = array();
    var $name = "";

    static $third_party_paths = array(
        '/include/JSON.php',
        '/include/htmLawed.php',
        '/include/PasswordHash.php',
        '/include/pear/',
        '/include/Spyc.php',
        '/setup/cli/stage/',
        '/include/plugins/',
        '/include/h2o/',
        '/include/mpdf/',
        '/js/select2.min.js',
        '/js/redactor.min.js',
        '/js/jquery-ui-1.13.1.custom.min.js',
        '/js/fabric.min.js',

        # Includes in the core-plugins project
        '/lib/',
    );

    function __construct() {
        assert_options(ASSERT_CALLBACK, array($this, 'fail'));
        error_reporting(E_ALL & ~E_WARNING);
    }

    function setup() {
    }

    function teardown() {
    }

    static function ignore3rdparty() {
        return true;
    }

    static function getAllScripts($pattern='*.php', $root=false, $excludes=true) {
        $root = $root ?: get_osticket_root_path();
        $excludes = $excludes ?: static::ignore3rdparty();
        $scripts = array();
        foreach (glob_recursive("$root/$pattern") as $s) {
            $found = false;
            if ($excludes) {
                foreach (self::$third_party_paths as $p) {
                    if (strpos($s, $p) !== false) {
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found)
                $scripts[] = $s;
        }
        return $scripts;
    }

    static function find_function_calls($scripts) {
        $calls=array();
        foreach ($scripts as $s) {
            $lines = explode("\n", file_get_contents($s));
            $lineno=0;
            foreach ($lines as $line) {
                $lineno++; $matches=array();
                // Ignore what looks like within comments (#|/|*)
                if (preg_match('/^(\s*?)(#|\/|\*)/m', $line))
                    continue;

                preg_match_all('/^.*\w+(?:-[>]|::)([a-zA-Z0-9_]+)\(.*/', $line, $matches,
                    PREG_SET_ORDER);
                foreach ($matches as $m)
                    if (strpos($m[0], 'nolint') === false)
                        $calls[] = array($s, $lineno, $line, $m[1]);
            }
        }
        return $calls;
    }

    function fail($script, $line, $message) {
        $this->fails[] = array(get_class($this), $script, $line, $message);
        fputs(STDOUT, 'F');
    }

    function pass() {
        fputs(STDOUT, ".");
    }

    function warn($message) {
        $this->warnings[] = array(get_class($this), '', '', 'WARNING: '.$message);
        fputs(STDOUT, 'w');
    }

    function assert($expr, $message=false) {
        if ($expr)
            $this->pass();
        else
            $this->fail('', '', $message ?: 'Test case failed');
    }

    function assertEqual($a, $b, $message=false) {
        if (!$message)
            $message = "Assertion: {$a} != {$b}";
        return $this->assert($a == $b, $message);
    }

    function assertNotEqual($a, $b, $message=false) {
        if (!$message)
            $message = "Assertion: {$a} == {$b}";
        return $this->assert($a != $b, $message);
    }

    function run() {
        $rc = new ReflectionClass(get_class($this));
        foreach ($rc->getMethods() as $m) {
            if (stripos($m->name, 'test') === 0) {
                $this->setup();
                @call_user_func(array($this, $m->name));
                $this->teardown();
            }
        }
    }

    function line_number_for_offset($file, $offset) {

        if (is_file($file))
            $content = file_get_contents($file, false, null, 0, $offset);
        else
            $content = @substr($file, 0, $offset);

        return count(explode("\n", $content));
    }
}

if (!function_exists('glob_recursive')) {
    # Check PHP syntax across all php files
    function glob_recursive($pattern, $flags = 0) {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
            $files = array_merge($files,
                glob_recursive($dir.'/'.basename($pattern), $flags));
        }
        return $files;
    }
}

if (!function_exists('get_osticket_root_path')) {
    function get_osticket_root_path() {
        # Hop up to the root folder
        $start = dirname(__file__);
        for (;;) {
            if (file_exists($start . '/main.inc.php')) break;
            $start .= '/..';
        }
        return realpath($start);
    }
}
?>
