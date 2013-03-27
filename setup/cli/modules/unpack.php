<?php

require_once dirname(__file__) . "/class.module.php";

class Unpacker extends Module {

    var $prologue = "Unpacks osTicket into target install path";

    var $epilog =
        "Copies an unpacked osticket tarball or zipfile into a production
         location, optionally placing the include/ folder in a separate
         location if requested";

    var $options = array(
        'include' => array('-i','--include', 'metavar'=>'path', 'help'=>
            "The include/ folder, which contains the bulk of osTicket's source
             code can be located outside of the install path. This is recommended
             for better security. If you would like to install the include/
             folder somewhere else, give the path here. Note that the full
             path is assumed, so give path/to/include/ to unpack the source
             code in that folder. The folder will be automatically created if
             it doesn't already exist."
        ),
    );

    var $arguments = array(
        'install-path' =>
            "The destination for osTicket to reside. Use the --include
             option to specify destination of the include/ folder, if the
             administrator should chose to locate it separate from the
             main installation path.",
    );

    function find_upload_folder() {
        # Hop up to the root folder
        $start = dirname(__file__);
        for (;;) {
            if (is_dir($start . '/upload')) break;
            $start .= '/..';
        }
        return realpath($start.'/upload');
    }

    function change_include_dir($include_path) {
        # Read the main.inc.php script
        $main_inc_php = $this->destination . '/main.inc.php';
        $lines = explode("\n", file_get_contents($main_inc_php));
        # Try and use ROOT_PATH
        if (strpos($include_path, $this->destination) === 0)
            $include_path = "ROOT_PATH . '" .
                str_replace($this->destination, '', $include_path) . "'";
        else
            $include_path = "'$include_path'";
        # Find the line that defines INCLUDE_DIR
        foreach ($lines as &$line) {
            if (preg_match("/(\s*)define\s*\(\s*'INCLUDE_DIR'/", $line, $match)) {
                # Replace the definition with the new locatin
                $line = $match[1] . "define('INCLUDE_DIR', "
                    . $include_path
                    . "); // Set by installer";
                break;
            }
        }
        if (!file_put_contents($main_inc_php, implode("\n", $lines)))
            die("Unable to configure location of INCLUDE_DIR in main.inc.php\n");
    }

    function exclude($pattern, $match) {
        if (!$pattern) {
            return false;
        } elseif (is_array($pattern)) {
            foreach ($pattern as $p)
                if (fnmatch($p, $match))
                    return true;
        } else {
            return fnmatch($pattern, $match);
        }
        return false;
    }

    function unpackage($folder, $destination, $recurse=true, $exclude=false) {
        foreach (glob($folder, GLOB_BRACE|GLOB_NOSORT) as $file) {
            if ($this->exclude($exclude, $file))
                continue;
            if (is_file($file)) {
                if (!is_dir($destination))
                    mkdir($destination, 0751, true);
                copy($file, $destination . '/' . basename($file));
            }
        }
        if ($recurse) {
            foreach (glob(dirname($folder).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
                if ($this->exclude($exclude, $dir))
                    continue;
                $this->unpackage(
                    dirname($folder).'/'.basename($dir).'/'.basename($folder),
                    $destination.'/'.basename($dir),
                    $recurse - 1, $exclude);
            }
        }
    }

    function get_include_dir() {
        $main_inc_php = $this->destination . '/main.inc.php';
        $lines = preg_grep("/define\s*\(\s*'INCLUDE_DIR'/",
            explode("\n", file_get_contents($main_inc_php)));

        // NOTE: that this won't work for crafty folks who have a define or some
        //       variable in the value of their include path
        if (!defined('ROOT_DIR')) define('ROOT_DIR', $this->destination . '/');
        foreach ($lines as $line)
            eval($line);

        return INCLUDE_DIR;
    }

    function run() {
        $this->destination = $this->getArgument('install-path');
        if (!is_dir($this->destination))
            if (!mkdir($this->destination, 0751, true))
                die("Destination path does not exist and cannot be created");

        # Determine if this is an upgrade, and if so, where the include/
        # folder is currently located
        $upgrade = file_exists("{$this->destination}/main.inc.php");

        # Locate the upload folder
        $upload = $this->find_upload_folder();

        # Unpack the upload folder to the destination, except the include folder
        if ($upgrade)
            # Get the current value of the INCLUDE_DIR before overwriting
            # main.inc.php
            $include = $this->get_include_dir();
        $this->unpackage("$upload/*", $this->destination, -1, "*include");

        if (!$upgrade) {
            if ($this->getOption('include')) {
                $location = $this->getOption('include');
                if (!is_dir("$location/"))
                    if (!mkdir("$location/", 0751, true))
                        die("Unable to create folder for include/ files\n");
                $this->unpackage("$upload/include/*", $location, -1);
                $this->change_include_dir($location);
            }
            else
                $this->unpackage("$upload/include/*", "{$this->destination}/include", -1);
        }
        else {
            $this->unpackage("$upload/include/*", $include, -1);
            # Change the new main.inc.php to reflect the location of the
            # include/ directory
            $this->change_include_dir($include);
        }
    }
}

Module::register('unpack', 'Unpacker');
