<?php
/*
 * Import initial form for task
 *
 */

class TaskLoader extends MigrationTask {
    var $description = "Loading initial data for tasks";
    static $pmap = array(
            'ticket.create'     => 'task.create',
            'ticket.edit'       => 'task.edit',
            'ticket.reply'      => 'task.reply',
            'ticket.delete'     => 'task.delete',
            'ticket.close'      => 'task.close',
            'ticket.assign'     => 'task.assign',
            'ticket.transfer'   => 'task.transfer',
    );

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
            $sq=new Sequence($s);
            $sq->save();
            $sql= 'INSERT INTO '.CONFIG_TABLE
                .' (`namespace`, `key`, `value`) '
                .' VALUES
                    ("core", "task_number_format", "###"),
                    ("core", "task_sequence_id",'.db_input($sq->id).')';
            db_query($sql);
            break;
        }

        // Copy ticket permissions
        foreach (Role::objects() as $role) {
            $perms = $role->getPermissionInfo();
            foreach (self::$pmap as  $k => $v) {
                if (in_array($k, $perms))
                    $perms[] = $v;
            }
            $role->updatePerms($perms);
            $role->save();
        }

    }
}

return 'TaskLoader';

?>
