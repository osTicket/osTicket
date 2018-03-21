<?php

class QueueSortCreator extends MigrationTask {
    var $description = "Load customziable ticket queues";

    function run($time) {
        $i18n = new Internationalization('en_US');
        $columns = $i18n->getTemplate('queue_column.yaml')->getData();
        foreach ($columns as $C) {
            QueueColumn::__create($C);
        }

        // Save old records
        $old = db_assoc_array(db_query('SELECT * FROM '.QUEUE_TABLE));
        // Truncate Queue table - make room for the new queues starting at ID 1
        db_query('TRUNCATE TABLE '.QUEUE_TABLE);
        $queues = $i18n->getTemplate('queue.yaml')->getData();
        foreach ($queues as $C) {
            CustomQueue::__create($C);
        }

        // Re-insert old saved searches
        foreach ($old ?: array() as $row) {
            // Only save entries with "valid" criteria
            if (!$row['title']
                    || !($config = JsonDataParser::parse($row['config'], true))
                    || !($criteria = CustomQueue::isolateCriteria($criteria)))
                continue;

            $row['config'] = JsonDataEncoder::encode(array(
                        'criteria' => $criteria, 'conditions' => array()));
            $row['root'] = 'T';
            CustomQueue::__create(array_intersect_key($row, array_flip(
                            array('staff_id', 'title', 'config', 'flags',
                                'root', 'created', 'updated'))));
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
            $q->psave();
        }

        // Set default queue to 'open'
        global $cfg;
        $cfg->set('default_ticket_queue', 1);
    }
}
return 'QueueSortCreator';
