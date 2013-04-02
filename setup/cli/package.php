#!/usr/bin/env php
<?php

if (php_sapi_name() != 'cli')
    die("Only command-line packaging is supported");

$stage_folder = "stage";
$stage_path = dirname(__file__) . '/' . $stage_folder;

function get_osticket_root_path() {
    # Hop up to the root folder
    $start = dirname(__file__);
    for (;;) {
        if (file_exists($start . '/main.inc.php')) break;
        $start .= '/..';
    }
    return realpath($start);
}

# Check PHP syntax across all php files
function glob_recursive($pattern, $flags = 0) {
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files,
            glob_recursive($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

$root = get_osticket_root_path();

function exclude($pattern, $match) {
    if (is_array($pattern)) {
        foreach ($pattern as $p)
            if (fnmatch($p, $match))
                return true;
    }
    else
        return fnmatch($pattern, $match);
    return false;
}

function package($pattern, $destination, $recurse=false, $exclude=false) {
    global $root, $stage_path;
    $search = $root . '/' . $pattern;
    echo "Packaging " . $search . "\n";
    foreach (glob($search, GLOB_BRACE|GLOB_NOSORT) as $file) {
        if (is_file($file)) {
            if ($exclude && exclude($exclude, $file))
                continue;
            if (!is_dir("$stage_path/$destination"))
                mkdir("$stage_path/$destination", 0777, true);
            copy($file, $stage_path . '/' . $destination . '/' . basename($file));
        }
    }
    if ($recurse) {
        foreach (glob(dirname($search).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
            if ($exclude && exclude($exclude, $dir))
                continue;
            package(dirname($pattern).'/'.basename($dir).'/'.basename($pattern),
                $destination.'/'.basename($dir),
                $recurse-1, $exclude);
        }
    }
}

# Create the stage folder for the install files
if (!is_dir($stage_path))
    mkdir($stage_path);
else {
    $dirs = array();
    foreach (glob_recursive($stage_path . '/*') as $file)
        if (is_dir($file))
            $dirs[] = $file;
        else
            unlink($file);
    sort($dirs);
    foreach (array_reverse($dirs) as $dir)
        rmdir($dir);
}

# Source code goes into 'upload'
mkdir($stage_path . '/upload');

# Load the root directory files
package("*.php", 'upload/');

# Load the client interface
foreach (array('assets','css','images','js') as $dir)
    package("$dir/*", "upload/$dir", -1, "*less");

# Load API
package('api/{,.}*', 'upload/api');

# Load the knowledgebase
package("kb/*.php", "upload/kb");

# Load the staff interface
package("scp/*.php", "upload/scp/", -1);
foreach (array('css','images','js') as $dir)
    package("scp/$dir/*", "upload/scp/$dir", -1);

# Load in the scripts
mkdir("$stage_path/scripts/");
package("setup/scripts/*", "scripts/", -1, "*stage");

# Load the heart of the system
package("include/{,.}*", "upload/include", -1, array('*ost-config.php', '*.sw[a-z]'));

# Include the installer
package("setup/*.{php,txt}", "upload/setup", -1, array("*scripts","*test","*stage"));
foreach (array('css','images','js') as $dir)
    package("setup/$dir/*", "upload/setup/$dir", -1);
package("setup/inc/sql/*.{sql,md5}", "upload/setup/inc/sql", -1);

# Load the license and documentation
package("*.{txt,md}", "");

#Rename markdown as text TODO: Do html version before rename.
if(($mds = glob("$stage_path/*.md"))) {
    foreach($mds as $md)
        rename($md, preg_replace('/\.md$/', '.txt', $md));
}

# Make an archive of the stage folder
$version_info = preg_grep('/THIS_VERSION/',
    explode("\n", file_get_contents("$root/main.inc.php")));

foreach ($version_info as $line)
    eval($line);

$pwd = getcwd();
chdir($stage_path);
shell_exec("tar cjf '$pwd/osTicket-".THIS_VERSION.".tar.bz2' *");
shell_exec("zip -r '$pwd/osTicket-".THIS_VERSION.".zip' *");

chdir($pwd);
?>
