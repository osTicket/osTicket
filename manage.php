#!/usr/bin/env php
<?php
/*********************************************************************
    manage.php

    CLI (command line interface) for osTicket management. Use

    php manage.php --help

    for detailed and updated getting started information.

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2015 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
if (PHP_SAPI != "cli")
    die("Management only supported from command-line\n");

require_once 'bootstrap.php';
require_once CLI_DIR . 'cli.inc.php';

if (!function_exists('noop')) { function noop() {} }
session_set_save_handler('noop','noop','noop','noop','noop','noop');

class Manager extends Module {
    var $prologue =
        "Manage one or more osTicket installations";

    var $arguments = array(
        'action' => "Action to be managed"
    );

    var $usage = '$script action [options] [arguments]';

    var $autohelp = false;

    function showHelp() {
        foreach (glob(CLI_DIR.'modules/*.php') as $script)
            include_once $script;

        global $registered_modules;
        $this->epilog =
            "Currently available modules follow. Use 'manage.php <module>
            --help' for usage regarding each respective module:";

        parent::showHelp();

        echo "\n";
        ksort($registered_modules);
        $width = max(array_map('strlen', array_keys($registered_modules)));
        foreach ($registered_modules as $name=>$mod)
            echo str_pad($name, $width + 2) . $mod->prologue . "\n";
    }

    function run($args, $options) {
        if ($options['help'] && !$args['action'])
            $this->showHelp();

        else {
            $action = $args['action'];

            global $argv;
            foreach ($argv as $idx=>$val)
                if ($val == $action)
                    unset($argv[$idx]);

            require_once CLI_DIR . "modules/{$args['action']}.php";
            if (($module = Module::getInstance($action)))
                return $module->_run($args['action']);

            $this->stderr->write("Unknown action given\n");
            $this->showHelp();
        }
    }
}

$manager = new Manager();
$manager->_run(basename(__file__), false);
