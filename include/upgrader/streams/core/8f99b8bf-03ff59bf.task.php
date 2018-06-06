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
            Sequence::__create($s);
        }
        db_query('UPDATE '.SEQUENCE_TABLE.' SET `next`= '
            .'(SELECT MAX(ticket_id)+1 FROM '.TICKET_TABLE.') '
            .'WHERE `id`=1');

        // list.yaml and ticket_status.yaml import moved to
        // core/b26f29a6-1ee831c8.task.php

        // Initialize MYSQL search backend
        MysqlSearchBackend::__init();
    }
}

return 'SequenceLoader';

?>
