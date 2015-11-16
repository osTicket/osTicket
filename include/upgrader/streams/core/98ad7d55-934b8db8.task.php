<?php

class QueueCreator extends MigrationTask {
    var $description = "Load customziable ticket queues";

    function run($time) {
        $i18n = new Internationalization('en_US');
        $columns = $i18n->getTemplate('queue_column.yaml')->getData();
        foreach ($columns as $C) {
            QueueColumn::__create($C);
        }
        $queues = $i18n->getTemplate('queue.yaml')->getData();
        foreach ($queues as $C) {
            CustomQueue::__create($C);
        }

        // Set default queue to 'open'
        global $cfg;
        $cfg->set('default_ticket_queue', 1);
    }
}
return 'QueueCreator';
