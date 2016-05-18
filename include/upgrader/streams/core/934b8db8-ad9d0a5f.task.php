<?php

class QueueSortCreator extends MigrationTask {
    var $description = "Load customziable sorting for ticket queues";

    function run($time) {
        $i18n = new Internationalization('en_US');
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
    }
}
return 'QueueSortCreator';
