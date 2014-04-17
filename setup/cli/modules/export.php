<?php
/*********************************************************************
    cli/export.php

    osTicket data exporter, used for migration and backup

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once dirname(__file__) . "/class.module.php";
require_once dirname(__file__) . "../../cli.inc.php";

class Exporter extends Module {
    var $prologue =
        "Dumps the osTicket database in formats suitable for the importer";

    var $options = array(
        'stream' => array('-o', '--output', 'default'=>'php://stdout',
            'help'=> "File or stream to receive the exported output. As a
            default, zlib compressed output is sent to standard out."),
        'compress' => array('-z', '--compress', 'action'=>'store_true',
            'help'=> "Send zlib compress data to the output stream"),
    );

    var $arguments = array(
        'module' => array(
            'required' => false,
            'help' => 'Module used for export (see help)'
        ),
    );

    var $autohelp = false;

    function run($args, $options) {
        require_once dirname(__file__) . '/../../../bootstrap.php';
        require_once INCLUDE_DIR . 'class.export.php';

        if (!$args['module']) {
            $exporter = 'DatabaseExporter';
        }
        else {
            $module = (include dirname(__file__)."/importer/{$args['module']}.php");
            if ($module) {
                $module = new $module();
                return $module->_run($args['module']);
            }
            else {
                $this->stderr->write("Unknown importer module given\n");
                $this->showHelp();
            }
        }
        if ($exporter)
            $this->dump($exporter);
    }

    function dump($module) {
        $stream = $this->getOption('stream');
        if ($this->getOption('compress')) $stream = "compress.zlib://$stream";
        $stream = fopen($stream, 'w');

        $x = new $module($stream, $this->_options);
        $x->dump($this->stderr);
    }

    function showHelp() {
        $modules = array();
        foreach (glob(dirname(__file__).'/importer/*.php') as $script) {
            $info = pathinfo($script);
            $modules[] = $info['filename'];
        }

        $this->epilog =
            "Currently available modules follow. Use 'manage.php export <module>
            --help' for usage regarding each respective module:";

        parent::showHelp();

        echo "\n";
        foreach ($modules as $name)
            echo str_pad($name, 20) . "\n";
    }
}

Module::register('export', 'Exporter');
?>
