<?php
/*
 * Import initial form for task
 *
 */

class TaskFormLoader extends MigrationTask {
    var $description = "Loading initial data for tasks";

    function run($max_time) {
        global $cfg;

        // Load task form
        require_once INCLUDE_DIR.'class.task.php';
        Task::__loadDefaultForm();
        // Load sequence for the task
        $i18n = new Internationalization($cfg->get('system_language', 'en_US'));
        $sequences = $i18n->getTemplate('sequence.yaml')->getData();
        foreach ($sequences as $s) {
            if ($s['id'] != 2) continue;
            unset($s['id']);
            $sq=Sequence::create($s);
            $sq->save();
            $sql= 'INSERT INTO '.CONFIG_TABLE
                .' (`namespace`, `key`, `value`) '
                .' VALUES
                    ("core", "task_number_format", "###"),
                    ("core", "task_sequence_id",'.db_input($sq->id).')';
            db_query($sql);
            break;
        }

    }
}

return 'TaskFormLoader';

?>
