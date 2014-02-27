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
            $this->touch_version();
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

    function touch_version($version=false) {
        if (!$version)
            $version = exec('git describe');
        if (!$version)
            return false;

        $bootstrap_php = $this->destination . '/bootstrap.php';
        $lines = explode("\n", file_get_contents($bootstrap_php));
        # Find the line that defines INCLUDE_DIR
        $match = array();
        foreach ($lines as &$line) {
            // TODO: Change THIS_VERSION inline to be current `git describe`
            if (preg_match("/(\s*)define\s*\(\s*'THIS_VERSION'/", $line, $match)) {
                # Replace the definition with the new locatin
                $line = $match[1] . "define('THIS_VERSION', '"
                    . $version
                    . "'); // Set by installer";
                break;
            }
        }
        if (!file_put_contents($bootstrap_php, implode("\n", $lines)))
            die("Unable to write version information to bootstrap.php\n");
    }
}

Module::register('deploy', 'Deployment');
?>
