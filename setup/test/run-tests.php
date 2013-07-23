#!/usr/bin/env php
<?php
if (php_sapi_name() != 'cli') exit();

require_once "tests/class.test.php";

function get_osticket_root_path() {
    # Hop up to the root folder
    $start = dirname(__file__);
    for (;;) {
        if (file_exists($start . '/main.inc.php')) break;
        $start .= '/..';
    }
    return realpath($start);
}
$root = get_osticket_root_path();

# Check PHP syntax across all php files
function glob_recursive($pattern, $flags = 0) {
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files,
            glob_recursive($dir.'/'.basename($pattern), $flags));
    }
    return $files;
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
    }
}
if (function_exists('pcntl_signal')) {
    declare(ticks=1);
    function show_fails_on_ctrlc() {
        while (@ob_end_flush());
        print("\n");
        show_fails();
        exit();
    }
    pcntl_signal(SIGINT, 'show_fails_on_ctrlc');
}

foreach (glob_recursive(dirname(__file__)."/tests/test.*.php") as $t) {
    if (strpos($t,"class.") !== false)
        continue;
    $class = (include $t);
    if (!is_string($class))
        continue;
    $test = new $class();
    echo "Running: " . $test->name . "\n";
    $test->run();
    $fails = array_merge($fails, $test->fails);
    echo " ok\n";
}
show_fails();

?>
