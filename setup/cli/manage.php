#!/usr/bin/env php
<?php

require_once "modules/class.module.php";

if (!function_exists('noop')) { function noop() {} }
session_set_save_handler('noop','noop','noop','noop','noop','noop');

class Manager extends Module {
    var $prologue =
        "Gérer une installation ou plus d\'osTicket";

    var $arguments = array(
        'action' => "Action à gérer"
    );

    var $usage = '$script action [options] [arguments]';

    var $autohelp = false;

    function showHelp() {
        foreach (glob(dirname(__file__).'/modules/*.php') as $script)
            include_once $script;

        global $registered_modules;
        $this->epilog =
            "Les modules suivants sont actuellement disponibles. Utilisez 'manage.php <module>
            --help' pour plus d'informations sur l'usage respectif des modules :";

        parent::showHelp();

        echo "\n";
        foreach ($registered_modules as $name=>$mod)
            echo str_pad($name, 20) . $mod->prologue . "\n";
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

            require_once dirname(__file__)."/modules/{$args['action']}.php";
            if (($module = Module::getInstance($action)))
                return $module->_run($args['action']);

            $this->stderr->write("Action donnée inconnue\n");
            $this->showHelp();
        }
    }
}

if (php_sapi_name() != "cli")
    die("La gestion n'est prise en charge que depuis la ligne de commande\n");

$manager = new Manager();
$manager->parseOptions();
$manager->_run(basename(__file__));

?>
