<?php
require_once "deploy.php";

class Packager extends Deployment {
    var $prologue = "Creates an osTicket distribution ZIP file";

    var $epilog =
        "Packaging is based on the `deploy` and `test` cli apps. After
        running the tests, the system is deployed into a temporary staging
        area using the files tracked in git if supported. Afterwards, the
        staging area is packaged as a ZIP file.";

    var $options = array(
        'format' => array('-F','--format',
            'default'=>'zip',
            'help'=>'Output the package in this format. Supported formats are
                "zip" (the default), and "targz"'
        ),
        'skip-test' => array('-S','--skip-test',
            'action'=>'store_true', 'default'=>false,
            'help'=>'Skip regression testing (NOT RECOMMENDED)',
        ),
        'version' => array('', '--dns',
            'action'=>'store_true', 'default'=>false,
            'help'=>'Print current version tag for DNS'
        ),
    );
    var $arguments = array();

    function __construct() {
        // Skip options added to the deploy — options and arguments are
        // forced in this module
        call_user_func_array(array('Module', '__construct'), func_get_args());
    }

    function run($args, $options) {
        if ($options['dns'])
            return $this->print_dns();

        // Set some forced args and options
        $temp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $stage_path = $temp . 'osticket'
            . substr(md5('osticket-stage'.getmypid().getcwd()), -8);
        $args['install-path'] = $stage_path . '/upload';

        // Deployment will auto-create the staging area

        // Ensure that the staging path is cleaned up on exit
        register_shutdown_function(function() use ($stage_path) {
            $delTree = function($dir) use (&$delTree) {
                $files = array_diff(scandir($dir), array('.','..'));
                foreach ($files as $file) {
                    (is_dir("$dir/$file")) ? $delTree("$dir/$file") : unlink("$dir/$file");
                }
                return rmdir($dir);
            };
            return $delTree($stage_path);
        });

        $options['setup'] = true;
        $options['git'] = true;
        $options['verbose'] = true;

        $options['clean'] = false;
        $options['dry-run'] = false;
        $options['include'] = false;

        $this->_args = $args;
        $this->_options = $options;

        // TODO: Run the testing applet first
        $root = $this->find_root_folder();
        if (!$this->getOption('skip-test') && $this->run_tests($root) > 0)
            $this->fail("Regression tests failed. Cowardly refusing to package");

        // Run the deployment
        // NOTE: The deployment will change the working directory
        parent::run($args, $options);

        // Deploy the `setup/scripts` folder to `/scripts`
        $root = $this->source;
        Unpacker::unpackage("$root/setup/scripts/{,.}*", "$stage_path/scripts", -1);

        // Package up the staging area
        $version = exec('git describe');
        switch (strtolower($this->getOption('format'))) {
        case 'zip':
        default:
            $this->packageZip("$root/osTicket-$version.zip", $stage_path);
        }
    }

    function run_tests($root) {
        return (require "$root/setup/test/run-tests.php");
    }

    function print_dns() {
        $streams = DatabaseMigrater::getUpgradeStreams(INCLUDE_DIR.'upgrader/streams/');
        $this->stdout->write(sprintf(
            '"v=1; m=%s; V=%s; c=%s; s=%s"',
            MAJOR_VERSION, trim(`git describe`), substr(`git rev-parse HEAD`, 0, 7),
            substr($streams['core'], 0, 8)
        ));
    }

    function packageZip($name, $path) {
        $zip = new ZipArchive();
        if (!$zip->open($name, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true)
            return false;

        $php56 = version_compare(phpversion(), '5.6.0', '>');
        $addFiles = function($dir) use (&$addFiles, $zip, $path, $php56) {
            $files = array_diff(scandir($dir), array('.','..'));
            $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            foreach ($files as $file) {
                $full = "$dir/$file";
                $local = str_replace($path, '', $full);
                if (is_dir($full))
                    $addFiles($full);
                else
                    // XXX: AddFile() will keep the file open and run
                    //      out of OS open file handles
                    $zip->addFromString($local, file_get_contents($full));
                    // This only works on PHP >= v5.6
                    if ($php56) {
                        // Set the Unix mode of the file
                        $stat = stat($full);
                        $zip->setExternalAttributesName($local, ZipArchive::OPSYS_UNIX, $stat['mode']);
                    }
            }
        };
        $addFiles($path);
        return $zip->close();

    }
}
Module::register('package', 'Packager');
