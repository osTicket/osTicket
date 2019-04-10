<?php

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
        'verbose' => array('-v','--verbose', 'default'=>false, 'nargs'=>0,
            'action'=>'store_true', 'help'=>
            "Move verbose logging to stdout"),
    );

    var $arguments = array(
        'install-path' =>
            "The destination for osTicket to reside. Use the --include
             option to specify destination of the include/ folder, if the
             administrator should chose to locate it separate from the
             main installation path.",
    );

    var $manifest;
    var $source;
    var $destination;

    function realpath($path) {
        return ($p = realpath($path)) ? $p : $path;
    }

    function find_upload_folder() {
        # Hop up to the root folder
        $start = dirname(__file__);
        for (;;) {
            if (is_dir($start . '/upload')) break;
            $start .= '/..';
        }
        return self::realpath($start.'/upload');
    }

    function change_include_dir($include_path) {
        # Read the main.inc.php script
        $bootstrap_php = $this->destination . '/bootstrap.php';
        $lines = explode("\n", file_get_contents($bootstrap_php));
        $include_path = preg_replace('://+:', '/', $include_path);
        # Try and use ROOT_DIR
        if (strpos($include_path, $this->destination) === 0)
            $include_path = "ROOT_DIR . '" .
                str_replace($this->destination, '', $include_path) . "'";
        else
            $include_path = "'$include_path'";
        # Find the line that defines INCLUDE_DIR
        $match = array();
        foreach ($lines as &$line) {
            // TODO: Change THIS_VERSION inline to be current `git describe`
            if (preg_match("/(\s*)define\s*\(\s*'INCLUDE_DIR'/", $line, $match)) {
                # Replace the definition with the new locatin
                $line = $match[1] . "define('INCLUDE_DIR', "
                    . $include_path
                    . "); // Set by installer";
                break;
            }
        }
        if (!file_put_contents($bootstrap_php, implode("\n", $lines)))
            die("Unable to configure location of INCLUDE_DIR in bootstrap.php\n");
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

    function readManifest($file) {
        if (isset($this->manifest))
            return @$this->manifest[$file] ?: null;

        $this->manifest = $lines = array();
        $path = $this->get_include_dir() . '/.MANIFEST';
        if (!is_file($path))
            return null;

        if (!preg_match_all('/^([\w:,]+) (.+)$/mu', file_get_contents($path),
            $lines, PREG_PATTERN_ORDER)
        ) {
            return null;
        }

        $this->manifest = array_combine($lines[2], $lines[1]);
        return @$this->manifest[$file] ?: null;
    }

    function hashFile($file) {
        static $hashes = array();

        if (!isset($hashes[$file])) {
            $md5 = md5_file($file);
            $sha1 = sha1_file($file);
            $hash = substr($md5, -20) . substr($sha1, -20);
            $hashes[$file] = $hash;
        }
        return $hashes[$file];
    }

    function isChanged($source, $hash=false) {
        $local = str_replace($this->source.'/', '', $source);
        $hash = $hash ?: $this->hashFile($source);
        return $this->readManifest($local) != $hash;
    }

    function updateManifest($file, $hash=false) {
        $hash = $hash ?: $this->hashFile($file);
        $local = str_replace($this->source.'/', '', $file);
        $this->manifest[$local] = $hash;
    }

    function copyFile($src, $dest, $hash=false, $mode=0644) {
        $this->updateManifest($src, $hash);
        return copy($src, $dest) && chmod($dest, $mode);
    }

    /**
     * Copy from source to desination, perhaps recursing up to n folders.
     * Exclusions are also permitted. If any files match an MD5 sum, they
     * will be excluded from the copy operation.
     *
     * Parameters:
     * folder - (string) source folder root
     * destination - (string) destination folder root
     * recurse - (int) recuse up to this many folders. Use 0 or false to
     *      disable recursion, and -1 to recurse infinite folders.
     * exclude - (string | array<string>) patterns that will be matched
     *      using the PHP `fnmatch` function. If any file or folder matches,
     *      it will be excluded from the copy procedure. Omit or use false
     *      to disable exclusions
     */
    function unpackage($folder, $destination, $recurse=0, $exclude=false) {
        $dryrun = $this->getOption('dry-run', false);
        $verbose = $this->getOption('verbose') || $dryrun;
        $force = $this->getOption('force', false);
        if (substr($destination, -1) !== '/')
            $destination .= '/';
        foreach (glob($folder, GLOB_BRACE|GLOB_NOSORT) as $file) {
            if ($this->exclude($exclude, $file))
                continue;
            if (is_file($file)) {
                $target = $destination . basename($file);
                $hash = $this->hashFile($file);
                if (!$force && is_file($target)
                        && false === ($flag = $this->isChanged($file, $hash)))
                    continue;
                if ($verbose) {
                    $msg = $target;
                    if (is_string($flag))
                        $msg = "$msg ({$flag})";
                    $this->stdout->write("$msg\n");
                }
                if ($dryrun)
                    continue;
                if (!is_dir($destination))
                    mkdir($destination, 0755, true);
                $this->copyFile($file, $target, $hash);
            }
        }
        if ($recurse) {
            $folders = glob(dirname($folder).'/'.basename($folder),
                GLOB_BRACE|GLOB_ONLYDIR|GLOB_NOSORT);
            foreach ($folders as $dir) {
                if (in_array(basename($dir), array('.','..')))
                    continue;
                elseif ($this->exclude($exclude, $dir))
                    continue;
                $this->unpackage(
                    dirname($folder).'/'.basename($dir).'/'.basename($folder),
                    $destination.basename($dir),
                    $recurse - 1, $exclude);
            }
        }
    }

    function get_include_dir() {
        static $location;

        if (isset($location))
            return $location;

        $pipes = array();
        $php = proc_open('php', array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
        ), $pipes);

        fwrite($pipes[0], "<?php
        if (file_exists('{$this->destination}/bootstrap.php'))
            include '{$this->destination}/bootstrap.php';
        else
            include '{$this->source}/bootstrap.php';

        print INCLUDE_DIR;
        ");
        fclose($pipes[0]);

        $INCLUDE_DIR = fread($pipes[1], 8192);
        proc_close($php);

        return $location = rtrim($INCLUDE_DIR, '/').'/';
    }

    function bootstrap() {
        // Don't load config and frieds as that will likely crash if not yet
        // installed
    }

    function run($args, $options) {
        $this->destination = $args['install-path'];
        if (!is_dir($this->destination))
            if (!mkdir($this->destination, 0751, true))
                die("Destination path does not exist and cannot be created");

        # Determine if this is an upgrade, and if so, where the include/
        # folder is currently located
        $upgrade = file_exists("{$this->destination}/main.inc.php");

        # Locate the upload folder
        $upload = $this->source = $this->find_upload_folder();

        # Unpack the upload folder to the destination, except the include folder
        if ($upgrade)
            # Get the current value of the INCLUDE_DIR before overwriting
            # main.inc.php
            $include = $this->get_include_dir();
        $this->unpackage("$upload/{,.}*", $this->destination, -1, "*include");

        if (!$upgrade) {
            if ($this->getOption('include')) {
                $location = $this->getOption('include');
                if (!is_dir("$location/"))
                    if (!mkdir("$location/", 0751, true))
                        die("Unable to create folder for include/ files\n");
                $this->unpackage("$upload/include/{,.}*", $location, -1);
                $this->change_include_dir($location);
            }
            else
                $this->unpackage("$upload/include/{,.}*", "{$this->destination}/include", -1);
        }
        else {
            $this->unpackage("$upload/include/{,.}*", $include, -1);
            # Change the new main.inc.php to reflect the location of the
            # include/ directory
            $this->change_include_dir($include);
        }
    }
}

Module::register('unpack', 'Unpacker');
