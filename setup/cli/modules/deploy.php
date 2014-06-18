<?php
require_once dirname(__file__) . "/class.module.php";
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
    function clean($local, $destination, $recurse=0, $exclude=false) {
        $dryrun = $this->getOption('dry-run', false);
        $verbose = $dryrun || $this->getOption('verbose');
        $destination = rtrim($destination, '/') . '/';
        $contents = glob($destination.'{,.}*', GLOB_BRACE|GLOB_NOSORT);
        foreach ($contents as $i=>$file) {
            if ($this->exclude($exclude, $file))
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
                elseif ($this->exclude($exclude, $dir))
                    continue;
                $this->clean(
                    $local.'/'.basename($dir),
                    $destination.basename($dir),
                    $recurse - 1, $exclude);
            }
        }
        if (!$contents || !glob($destination.'{,.}*', GLOB_BRACE|GLOB_NOSORT)) {
            if ($verbose)
                $this->stdout->write("(delete-folder): $destination\n");
            if (!$dryrun)
                rmdir($destination);
        }
    }

    function copyFile($src, $dest) {
        static $short = false;
        static $version = false;

        if (substr($src, -4) != '.php')
            return parent::copyFile($src, $dest);

        if (!$short) {
            $hash = exec('git rev-parse HEAD');
            $short = substr($hash, 0, 7);
        }

        if (!$version)
            $version = exec('git describe');

        if (!$short || !$version)
            return parent::copyFile($src, $dest);

        $source = file_get_contents($src);
        $source = preg_replace(':<script(.*) src="(.*).js"></script>:',
            '<script$1 src="$2.js?'.$short.'"></script>',
            $source);
        $source = preg_replace(':<link(.*) href="(.*).css"([^/>]*)/?>:', # <?php
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

        if (!file_put_contents($dest, $source))
            die("Unable to apply rewrite rules to ".$dest);

        return true;
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
        $include = rtrim($include, '/').'/';

        # Locate the upload folder
        $root = $this->find_root_folder();

        $exclusions = array("$root/include", "$root/.git*",
            "*.sw[a-z]","*.md", "*.txt");
        if (!$options['setup'])
            $exclusions[] = "$root/setup";

        # Unpack everything but the include/ folder
        $this->unpackage("$root/{,.}*", $this->destination, -1,
            $exclusions);
        # Unpack the include folder
        $this->unpackage("$root/include/{,.}*", $include, -1,
            array("*/include/ost-config.php"));
        if (!$options['dry-run']) {
            if ($include != "{$this->destination}/include/")
                $this->change_include_dir($include);
        }

        if ($options['clean']) {
            // Clean everything but include folder first
            $this->clean($root, $this->destination, -1,
                array($include, "setup/"));
            $this->clean("$root/include", $include, -1,
                array("ost-config.php","settings.php","plugins/",
                "*/.htaccess"));
        }
    }
}

Module::register('deploy', 'Deployment');
?>
