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
        return realpath($start);
    }

    function run($args, $options) {
        $this->destination = $args['install-path'];
        if (!is_dir($this->destination))
            if (!@mkdir($this->destination, 0751, true))
                die("Destination path does not exist and cannot be created");
        $this->destination = realpath($this->destination).'/';

        # Determine if this is an upgrade, and if so, where the include/
        # folder is currently located
        $upgrade = file_exists("{$this->destination}/main.inc.php");

        # Get the current value of the INCLUDE_DIR before overwriting
        # main.inc.php
        $include = ($upgrade) ? $this->get_include_dir()
            : ($options['include'] ? $options['include']
                : "{$this->destination}/include");
        if (substr($include, -1) !== '/')
            $include .= '/';

        # Locate the upload folder
        $root = $this->find_root_folder();

        # Unpack everything but the include/ folder
        $this->unpackage("$root/{,.}*", $this->destination, -1,
            array("$root/setup", "$root/include", "$root/.git*",
                "*.sw[a-z]","*.md", "*.txt"));
        # Unpack the include folder
        $this->unpackage("$root/include/{,.}*", $include, -1,
            array("*/include/ost-config.php"));
        if (!$options['dry-run'] && !$upgrade
                 && $include != "{$this->destination}/include")
            $this->change_include_dir($include);
    }
}

Module::register('deploy', 'Deployment');
?>
