<?php
require_once dirname(__file__) . "/unpack.php";

class Deployment extends Unpacker {
    var $prologue = "Deploys osTicket into target install path";

    var $epilog =
        "Deployment is used from the continuous development model. If you
        are following the upstream git repo, then you can use the deploy
        script to deploy changes made by you or upstream development to your
        installation target";

    function __construct() {
        $this->options['dry-run'] = array('-t','--dry-run',
            'action'=>'store_true',
            'help'=>'Don\'t actually deploy new code. Just show the files
                that would be copied');
        $this->options['setup'] = array('-s','--setup',
            'action'=>'store_true',
            'help'=>'Deploy the setup folder. Useful for deploying for new
                installations.');
        $this->options['clean'] = array('-C','--clean',
            'action'=>'store_true',
            'help'=>'Remove files from the destination that are no longer
                included in this repository');
        $this->options['git'] = array('-g','--git',
            'action'=>'store_true',
            'help'=>'Use `git ls-files -s` as files source. Eliminates
                possibility of deploying untracked files');
        $this->options['force'] = array('-f', '--force',
            'action'=>'store_true',
            'help'=>'Deploy all files, even if they have not changed');
        # super(*args);
        call_user_func_array(array('parent', '__construct'), func_get_args());
    }

    function find_root_folder() {
        # Hop up to the root folder of this repo
        $start = dirname(__file__);
        for (;;) {
            if (is_file($start . '/main.inc.php')) break;
            $start .= '/..';
        }
        return self::realpath($start);
    }

    /**
     * Removes files from the deployment location that no longer exist in
     * the local repository
     */
    function clean($local, $destination, $root, $recurse=0, $exclude=false) {
        $dryrun = $this->getOption('dry-run', false);
        $verbose = $dryrun || $this->getOption('verbose');
        $destination = rtrim($destination, '/') . '/';
        $contents = glob($destination.'{,.}*', GLOB_BRACE|GLOB_NOSORT);
        foreach ($contents as $i=>$file) {
            $relative = str_replace($root, "", $file);
            if ($this->exclude($exclude, $relative))
                continue;
            if (is_file($file)) {
                $ltarget = $local . '/' . basename($file);
                if (is_file($ltarget))
                    continue;
                if ($verbose)
                    $this->stdout->write("(delete): $file\n");
                if (!$dryrun)
                    unlink($file);
                unset($contents[$i]);
            }
            elseif (in_array(basename($file), array('.','..'))) {
                // Doesn't indicate that the folder has contents
                unset($contents[$i]);
            }
        }
        if ($recurse) {
            $folders = glob(dirname($destination).'/'.basename($destination).'/*',
                GLOB_BRACE|GLOB_ONLYDIR|GLOB_NOSORT);
            foreach ($folders as $dir) {
                if (in_array(basename($dir), array('.','..')))
                    continue;
                $relative = str_replace($root, "", $dir);
                if ($this->exclude($exclude, "$relative/"))
                    continue;
                $this->clean(
                    $local.'/'.basename($dir),
                    $destination.basename($dir),
                    $root, $recurse - 1, $exclude);
            }
        }
        if (!$contents || !empty(glob($destination.'{,.}*', GLOB_BRACE|GLOB_NOSORT))) {
            if ($verbose)
                $this->stdout->write("(delete-folder): $destination\n");
            if (!$dryrun)
                rmdir($destination);
        }
    }

    function writeManifest($root) {
        $lines = array();
        foreach ($this->manifest as $F=>$H)
            $lines[] = "$H $F";

        return file_put_contents($this->include_path.'/.MANIFEST', implode("\n", $lines));
    }

    function hashContents($file) {
        $md5 = md5($file);
        $sha1 = sha1($file);
        return substr($md5, -20) . substr($sha1, -20);
    }

    function getEditedContents($src) {
        static $short = false;
        static $version = false;

        if (substr($src, -4) != '.php')
            return false;

        if (!$short) {
            $hash = exec('git rev-parse HEAD');
            $short = substr($hash, 0, 7);
        }

        if (!$version)
            $version = exec('git describe');

        if (!$short || !$version)
            return false;

        $source = file_get_contents($src);
        $original = crc32($source);
        $source = preg_replace(':<script(.*) src="([^"]+)\.js"></script>:',
            '<script$1 src="$2.js?'.$short.'"></script>',
            $source);
        $source = preg_replace(':<link(.*) href="([^"]+)\.css"([^/>]*)/?>:', # <?php
            '<link$1 href="$2.css?'.$short.'"$3/>',
            $source);
        // Set THIS_VERSION
        $source = preg_replace("/^(\s*)define\s*\(\s*'THIS_VERSION'.*$/m",
            "$1define('THIS_VERSION', '".$version."'); // Set by installer",
            $source);
        // Set GIT_VERSION
        $source = preg_replace("/^(\s*)define\s*\(\s*'GIT_VERSION'.*$/m",
            "$1define('GIT_VERSION', '".$short."'); // Set by installer",
            $source);
        // Disable error display
        $source = preg_replace("/^(\s*)ini_set\s*\(\s*'(display_errors|display_startup_errors)'.*$/m",
            "$1ini_set('$2', '0'); // Set by installer",
            $source);

        // return FALSE if the edited contents do not differ from the
        // original contents
        return $original != crc32($source) ? $source : false;
    }

    function isChanged($source, $hash=false) {
        $local = str_replace($this->source.'/', '', $source);
        $hash = $hash ?: $this->hashFile($source);
        list($shash, $flag) = explode(':', $this->readManifest($local));
        return ($flag === 'rewrite') ? $flag : $shash != $hash;
    }

    function copyFile($source, $dest, $hash=false, $mode=0644, $contents=false) {
        $contents = $contents ?: $this->getEditedContents($source);
        if ($contents === false)
            // Regular file
            return parent::copyFile($source, $dest, $hash, $mode);

        if (!file_put_contents($dest, $contents))
            $this->fail($dest.": Unable to apply rewrite rules");

        $this->updateManifest($source, "$hash:rewrite");
        return chmod($dest, $mode);
    }

    function unpackage($folder, $destination, $recurse=0, $exclude=false) {
        $use_git = $this->getOption('git', false);
        if (!$use_git)
            return parent::unpackage($folder, $destination, $recurse, $exclude);

        // Attempt to read from git using `git ls-files` for deployment
        if (substr($destination, -1) !== '/')
            $destination .= '/';
        $source = $this->source;
        if (substr($source, -1) != '/')
            $source .= '/';
        $local = str_replace(array($source, '{,.}*'), array('',''), $folder);

        $pipes = array();
        $patterns = array();
        foreach ((array) $exclude as $x) {
            $patterns[] = str_replace($source, '', $x);
        }
        $X = implode(' --exclude-per-directory=', $patterns);
        chdir($source.$local);
        if (!($files = proc_open(
            "git ls-files -zs --exclude-standard --exclude-per-directory=$X -- .",
            array(1 => array('pipe', 'w')),
            $pipes
        ))) {
            return parent::unpackage($folder, $destination, $recurse, $exclude);
        }

        $dryrun = $this->getOption('dry-run', false);
        $verbose = $this->getOption('verbose') || $dryrun;
        $force = $this->getOption('force');
        while ($line = stream_get_line($pipes[1], 255, "\x00")) {
            list($mode, $hash, , $path, $pathx, $pathy, $pathz) = preg_split('/\s+/', $line);
            if (isset($pathx))
                $path = "$path $pathx";
            if (isset($pathy))
                $path = "$path $pathy";
            if (isset($pathz))
                $path = "$path $pathz";
            $src = $source.$local.$path;
            if ($this->exclude($exclude, $src))
                continue;
            if (!$force && false === ($flag = $this->isChanged($src, $hash)))
                continue;
            $dst = $destination.$path;
            if ($verbose) {
                $msg = $dst;
                if (is_string($flag))
                    $msg = "$msg ({$flag})";
                $this->stdout->write("$msg\n");
            }
            if ($dryrun)
                continue;
            if (!is_dir(dirname($dst)))
                mkdir(dirname($dst), 0755, true);
            $this->copyFile($src, $dst, $hash, octdec($mode));
        }
    }

    function run($args, $options) {
        $this->destination = $args['install-path'];
        if (!is_dir($this->destination))
            if (!@mkdir($this->destination, 0751, true))
                die("Destination path does not exist and cannot be created");
        $this->destination = self::realpath($this->destination).'/';

        # Determine if this is an upgrade, and if so, where the include/
        # folder is currently located
        $upgrade = file_exists("{$this->destination}/main.inc.php");

        # Get the current value of the INCLUDE_DIR before overwriting
        # bootstrap.php
        $include = ($upgrade) ? $this->get_include_dir()
            : ($options['include'] ? $options['include']
                : rtrim($this->destination, '/')."/include");
        $this->include_path = $include = rtrim($include, '/').'/';

        # Locate the upload folder
        $root = $this->source = $this->find_root_folder();
        $rootPattern = str_replace("\\","\\\\", $root); //need for windows case

        # Prime the manifest system
        $this->readManifest($this->destination.'/.MANIFEST');

        $exclusions = array("$rootPattern/include/*", "$rootPattern/.git*",
            "*.sw[a-z]","*.md", "*.txt");
        if (!$options['setup'])
            $exclusions[] = "$rootPattern/setup/*";

        # Unpack everything but the include/ folder
        $this->unpackage("$root/{,.}*", $this->destination, -1,
            $exclusions);
        # Unpack the include folder
        $this->unpackage("$root/include/{,.}*", $include, -1,
            array("*/include/ost-config.php", "*.sw[a-z]"));
        if (!$options['dry-run']) {
            if ($include != "{$this->destination}/include/")
                $this->change_include_dir($include);
        }

        if ($options['clean']) {
            // Clean everything but include folder first
            $local_include = str_replace($this->destination, "", $include);
            $this->clean($root, $this->destination, $this->destination, -1,
                array($local_include, "setup/"));
            $this->clean("$root/include", $include, $include, -1,
                array("ost-config.php","settings.php","plugins/",
                "*/.htaccess", ".MANIFEST"));
        }

        if (!$options['dry-run'])
            $this->writeManifest($this->destination);
    }
}

Module::register('deploy', 'Deployment');
?>
