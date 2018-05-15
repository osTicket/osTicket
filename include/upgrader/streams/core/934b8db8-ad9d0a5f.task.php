<?php

class QueueSortCreator extends MigrationTask {
    var $description = "Load customziable ticket queues";

    function run($time) {
        $i18n = new Internationalization('en_US');
        $columns = $i18n->getTemplate('queue_column.yaml')->getData();
        foreach ($columns as $C) {
            QueueColumn::__create($C);
        }
        // Make room for the new queues starting at ID 1
        $id = new SqlField('id');
        CustomQueue::objects()->update(['id' => $id->plus(30)]);
        $queues = $i18n->getTemplate('queue.yaml')->getData();
        foreach ($queues as $C) {
            CustomQueue::__create($C);
        }

        $columns = $i18n->getTemplate('queue_sort.yaml')->getData();
        foreach ($columns as $C) {
            QueueSort::__create($C);
        }

        $open = CustomQueue::lookup(1);
        foreach (QueueSort::forQueue($open) as $qs) {
            $open->sorts->add($qs);
        }
        $open->sorts->saveAll();

        foreach ($open->getChildren() as $q) {
            $q->flags |= CustomQueue::FLAG_INHERIT_SORTING;
            $q->save();
        }

        // Set default queue to 'open'
        global $cfg;
        $cfg->set('default_ticket_queue', 1);
    }
}
return 'QueueSortCreator';
