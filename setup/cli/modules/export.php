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

define('OSTICKET_BACKUP_SIGNATURE', 'osTicket-Backup');
define('OSTICKET_BACKUP_VERSION', 'A');

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

    function run($args, $options) {
        require_once dirname(__file__) . '/../../../main.inc.php';
        require_once INCLUDE_DIR . 'class.export.php';

        global $ost;

        $stream = $options['stream'];
        if ($options['compress']) $stream = "compress.zlib://$stream";
        $stream = fopen($stream, 'w');

        $x = new DatabaseExporter($stream);
        $x->dump($this->stderr);
    }
}

Module::register('export', 'Exporter');
?>
