<?php

/*
 * Loads the initial sequence from the inital data files
 */

class SequenceLoader extends MigrationTask {
    var $description = "Loading initial data for sequences";

    function run($max_time) {
        $i18n = new Internationalization('en_US');
        $sequences = $i18n->getTemplate('sequence.yaml')->getData();
        foreach ($sequences as $s) {
            Sequence::create($s);
            $s->save();
        }
        db_query('UPDATE '.SEQUENCE_TABLE.' SET `next`= '
            .'(SELECT MAX(ticket_id) FROM '.TICKET_TABLE.') '
            .'WHERE `id`=1');
    }
}

return 'SequenceLoader';

?>
