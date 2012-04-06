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

# Run phplint across all php files
ob_start();
# XXX: This won't run well on Windoze
system("$root/setup/test/lib/phplint.tcl $root/**/*.php 2>&1");
$lint_errors = ob_get_clean();
$lint_errors=str_replace("$root/", '', $lint_errors);

if (strlen($lint_errors)) {
    echo "FAIL: Access to unitialized variables\n";
    echo "-------------------------------------------------------\n";
    echo "$lint_errors";
}
?>
