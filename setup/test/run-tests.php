#!/usr/bin/env php
<?php
if (php_sapi_name() != 'cli') exit();

//Allow user to select suite
$selected_test = (isset($argv[1])) ? $argv[1] : false;

require_once "tests/class.test.php";

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
$root = get_osticket_root_path();
define('INCLUDE_DIR', "$root/include/");
define('PEAR_DIR', INCLUDE_DIR."pear/");
ini_set('include_path', './'.PATH_SEPARATOR.INCLUDE_DIR.PATH_SEPARATOR.PEAR_DIR);

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

$fails = array();

function show_fails() {
    global $fails, $root;
    if ($fails) {
        echo count($fails) . " FAIL(s)\n";
        echo "-------------------------------------------------------\n";
        foreach ($fails as $f) {
            list($test, $script, $line, $message) = $f;
            $script = str_replace($root.'/', '', $script);
            print("$test: $message @ $script:$line\n");
        }
        return count($fails);
    }
}
if (function_exists('pcntl_signal')) {
    declare(ticks=1);
    function show_fails_on_ctrlc() {
        while (@ob_end_flush());
        print("\n");
        exit(show_fails());
    }
    pcntl_signal(SIGINT, 'show_fails_on_ctrlc');
}

foreach (glob_recursive(dirname(__file__)."/tests/test.*.php") as $t) {
    if (strpos($t,"class.") !== false)
        continue;
    $class = (include $t);
    if (!is_string($class))
        continue;
    if($selected_test && ($class != $selected_test))
    	continue;
    $test = new $class();
    echo "Running: " . $test->name . "\n";
    $test->run();
    $fails = array_merge($fails, $test->fails);
    echo " ok\n";
}
show_fails();

// If executed directly expose the fail count to a shell script
global $argv;
if (!strcasecmp(basename($argv[0]), basename(__file__)))
    exit(count($fails));
else
    return count($fails);
?>
