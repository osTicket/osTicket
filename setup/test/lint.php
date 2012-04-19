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
    return $start;
}

$root = get_osticket_root_path();

# Check PHP syntax across all php files
function glob_recursive($pattern, $flags = 0) {
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) 
            as $dir) {
        $files = array_merge($files, 
            glob_recursive($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}
echo "PHP Syntax Errors: ";
ob_start();
$scripts=glob_recursive("$root/*/*.php");
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
    echo "\n";
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
?>
