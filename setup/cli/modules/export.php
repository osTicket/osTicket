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

require_once dirname(__file__) . '/../../../main.inc.php';

require_once INCLUDE_DIR . 'class.json.php';

define('OSTICKET_BACKUP_SIGNATURE', 'osTicket-Backup');
define('OSTICKET_BACKUP_VERSION', 'A');

class Exporter extends Module {
    var $prologue =
        "Dumps the osTicket database in formats suitable for the importer";

    var $tables = array(CONFIG_TABLE, SYSLOG_TABLE, FILE_TABLE,
        FILE_CHUNK_TABLE, STAFF_TABLE, DEPT_TABLE, TOPIC_TABLE, GROUP_TABLE,
        GROUP_DEPT_TABLE, TEAM_TABLE, TEAM_MEMBER_TABLE, FAQ_TABLE,
        FAQ_ATTACHMENT_TABLE, FAQ_TOPIC_TABLE, FAQ_CATEGORY_TABLE,
        CANNED_TABLE, CANNED_ATTACHMENT_TABLE, TICKET_TABLE, TICKET_THREAD_TABLE,
        TICKET_ATTACHMENT_TABLE, TICKET_PRIORITY_TABLE, PRIORITY_TABLE,
        TICKET_LOCK_TABLE, TICKET_EVENT_TABLE, TICKET_EMAIL_INFO_TABLE,
        EMAIL_TABLE, EMAIL_TEMPLATE_TABLE, FILTER_TABLE, FILTER_RULE_TABLE,
        SLA_TABLE, API_KEY_TABLE, TIMEZONE_TABLE, PAGE_TABLE);

    var $options = array(
        'stream' => array('-o', '--output', 'default'=>'compress.zlib://php://stdout',
            "File or stream to receive the exported output. As a default,
            zlib compressed output is sent to standard out.")
    );

    var $stream;

    function write_block($what) {
        fwrite($this->stream, JsonDataEncoder::encode($what));
        fwrite($this->stream, "\x1e");
    }

    function run($args, $options) {
        global $ost;
        $this->stream = fopen($options['stream'], 'w');
        $header = array(
            array(OSTICKET_BACKUP_SIGNATURE, OSTICKET_BACKUP_VERSION),
            array(
                'version'=>THIS_VERSION,
                'table_prefix'=>TABLE_PREFIX,
                'salt'=>SECRET_SALT,
            ),
        );
        $this->write_block($header);

        foreach ($this->tables as $t) {
            $this->stderr->write("$t\n");
            // Inspect schema
            $table = $indexes = array();
            $res = db_query("show columns from $t");
            while ($field = db_fetch_array($res))
                $table[] = $field;

            $res = db_query("show indexes from $t");
            while ($col = db_fetch_array($res))
                $indexes[] = $col;

            $res = db_query("select * from $t");
            $types = array();

            $this->write_block(
                array('table', substr($t, strlen(TABLE_PREFIX)), $table,
                    $indexes));

            // Dump row data
            while ($row = db_fetch_row($res))
                $this->write_block($row);

            $this->write_block(array('end-table'));
        }
    }
}

Module::register('export', 'Exporter');
?>
