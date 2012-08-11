#!/usr/bin/env php
<?php
if (php_sapi_name() != 'cli') exit();

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
echo "PHP Syntax Errors: ";
ob_start();
$scripts=glob_recursive("$root/*.php");
$exit=0;
$syntax_errors="";
foreach ($scripts as $s) {
    system("php -l $s", $exit);
    $line = ob_get_contents();
    ob_clean();
    if ($exit !== 0)
        $syntax_errors .= $line;
}
ob_end_clean();

if (strlen($syntax_errors)) {
    $syntax_errors=str_replace("$root/", '', $syntax_errors);
    echo "FAIL\n";
    echo "-------------------------------------------------------\n";
    echo "$syntax_errors";
    exit();
} else {
    echo "pass\n";
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
echo "Short open tags: ";
$fails = array();
foreach ($scripts as $s) {
    $matches = array();
    if (preg_match_all('/<\?\s*(?!php|xml).*$/m', file_get_contents($s), $matches,
            PREG_OFFSET_CAPTURE) > 0) {
        foreach ($matches[0] as $match)
            $fails[] = array(
                str_replace($root.'/', '', $s),
                $match[0],
                line_number_for_offset($s, $match[1]));
    }
}
if (count($fails)) {
    echo "FAIL\n";
    echo "-------------------------------------------------------\n";
    foreach ($fails as $f)
        echo sprintf("In %s, line %d: %s\n", $f[0], $f[2],
            str_replace("\n", " ", $f[1]));
    echo "\n";
} else {
    echo "pass\n";
}

# Run phplint across all php files
echo "Access to unitialized variables: ";
ob_start();
# XXX: This won't run well on Windoze
system("$root/setup/test/lib/phplint.tcl ".implode(" ", $scripts));
$lint_errors = ob_get_clean();

if (strlen($lint_errors)) {
    $lint_errors=str_replace("$root/", '', $lint_errors);
    echo "FAIL\n";
    echo "-------------------------------------------------------\n";
    echo "$lint_errors";
} else {
    echo "\n";
}

function find_function_calls($scripts) {
    $calls=array();
    foreach ($scripts as $s) {
        $lines = explode("\n", file_get_contents($s));
        $lineno=0;
        foreach (explode("\n", file_get_contents($s)) as $line) {
            $lineno++; $matches=array();
            preg_match_all('/-[>]([a-zA-Z0-9]*)\(/', $line, $matches,
                PREG_SET_ORDER);
            foreach ($matches as $m) {
                $calls[] = array($s, $lineno, $line, $m[1]);
            }
        }
    }
    return $calls;
}

$php_script_content='';
foreach ($scripts as $s) {
    $php_script_content .= file_get_contents($s);
}
echo "Access to undefined object methods: ";
ob_start();
foreach (find_function_calls($scripts) as $call) {
    list($file, $no, $line, $func) = $call;
    if (!preg_match('/^\s*(\/\*[^*]*\*\/)?'."\s*function\s+&?\s*$func\\(/m", 
            $php_script_content)) {
        print "$func: Definitely undefined, from $file:$no\n";
    }
}
$undef_func_errors = ob_get_clean();

if (strlen($undef_func_errors)) {
    $undef_func_errors=str_replace("$root/", '', $undef_func_errors);
    echo "FAIL\n";
    echo "-------------------------------------------------------\n";
    echo "$undef_func_errors";
    exit();
} else {
    echo "\n";
}
?>
