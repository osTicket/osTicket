#!/usr/bin/env php
<?php

require_once "modules/class.module.php";

class Manager extends Module {
    var $prologue =
        "Manage one or more osTicket installations";

    var $arguments = array(
        'action' => "Action to be managed"
    );

    var $usage = '$script action [options] [arguments]';

    var $autohelp = false;

    function showHelp() {
        foreach (glob(dirname(__file__).'/modules/*.php') as $script)
            include_once $script;

        global $registered_modules;
        $this->epilog = 
            "Currently available modules follow. Use 'manage.php <module>
            --help' for usage regarding each respective module:";

        parent::showHelp();
        
        echo "\n";
        foreach ($registered_modules as $name=>$mod)
            echo str_pad($name, 20) . $mod->prologue . "\n";
    }

    function run() {
        if ($this->getOption('help') && !$this->getArgument('action'))
            $this->showHelp();

        else {
            $action = $this->getArgument('action');

            global $argv;
            foreach ($argv as $idx=>$val)
                if ($val == $action)
                    unset($argv[$idx]);

            include_once dirname(__file__) . '/modules/' . $action . '.php';
            $module = Module::getInstance($action);
            $module->run();
        }
    }
}

if (php_sapi_name() != "cli")
    die("Management only supported from command-line\n");

$manager = new Manager();
$manager->parseOptions();
$manager->run();

?>
