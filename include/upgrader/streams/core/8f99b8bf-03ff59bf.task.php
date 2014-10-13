<?php

/*
 * Loads the initial sequence from the inital data files
 */

class SequenceLoader extends MigrationTask {
    var $description = "Loading initial data for sequences";

    function run($max_time) {
        global $cfg;

        $i18n = new Internationalization($cfg->get('system_language', 'en_US'));
        $sequences = $i18n->getTemplate('sequence.yaml')->getData();
        foreach ($sequences as $s) {
            Sequence::create($s)->save();
        }
        db_query('UPDATE '.SEQUENCE_TABLE.' SET `next`= '
            .'(SELECT MAX(ticket_id)+1 FROM '.TICKET_TABLE.') '
            .'WHERE `id`=1');

        require_once(INCLUDE_DIR . 'class.list.php');

        $lists = $i18n->getTemplate('list.yaml')->getData();
        foreach ($lists as $l) {
            DynamicList::create($l);
        }

        $statuses = $i18n->getTemplate('ticket_status.yaml')->getData();
        foreach ($statuses as $s) {
            TicketStatus::__create($s);
        }

        // Initialize MYSQL search backend
        MysqlSearchBackend::__init();
    }
}

return 'SequenceLoader';

?>
