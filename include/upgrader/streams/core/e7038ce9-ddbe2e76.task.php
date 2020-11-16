<?php

/*
 * Add all indexes we've implemented over time. Check if exists
 * to avoid errors.
 */
class AddIndexMigration extends MigrationTask {
    var $description = "Add indexes accumulated over time";
    var $indexes = array(
        'department:flags',
        'draft:staff_id',
        'draft:namespace',
        'file:type',
        'file:created',
        'file:size',
        'form:type',
        'form_field:form_id',
        'form_field:sort',
        'queue:staff_id',
        'queue:parent_id',
        'staff:isactive',
        'staff:onvacation',
        'task:flags',
        'ticket:ticket_pid',
        'team_member:staff_id',
        'user:default_email_id',
        'user:name'
    );

    function run($max_time) {
        global $ost;

        foreach ($this->indexes as $index) {
            list($t, $i) = explode(':', $index);

            // Check if INDEX already exists via SHOW INDEX
            $sql = sprintf("SHOW INDEX FROM `%s` WHERE `Column_name` = '%s' AND `Key_name` != 'PRIMARY'",
                    TABLE_PREFIX.$t, $i);

            // Hardfail if we cannot check if exists
            if(!($res=db_query($sql)))
                return $this->error('Unable to query DB for Add Index migration!');

            $count = db_num_rows($res);

            if (!$count || ($count && ($count == 0))) {
                // CREATE INDEX if not exists
                $create = sprintf('CREATE INDEX `%s` ON `%s`.`%s` (`%s`)',
                        $i, DBNAME, TABLE_PREFIX.$t, $i);

                if(!($res=db_query($create))) {
                    $message = "Unable to create index `$i` on `".TABLE_PREFIX.$t."`.";
                    // Log the error but don't send the alert email
                    $ost->logError('Upgrader: Add Index Migrater', $message, false);
                }
            }
        }

        //add permissions to staff
        foreach (Staff::objects() as $staff) {
            $perms = array();
            foreach ($staff->getPermissionInfo() as $value => $setting)
                $perms[] = $value;

            array_push($perms, 'visibility.departments', 'visibility.agents');
            $staff->updatePerms($perms);
            $staff->save();
        }

        //add 2fa template
        foreach (array('email2fa-staff') as $type) {
             $i18n = new Internationalization();
             $tpl = $i18n->getTemplate("templates/page/{$type}.yaml");
             if (!($page = $tpl->getData()))
                 // No such template on disk
                 continue;

              if ($id = db_result(db_query('select id from '.PAGE_TABLE
                     .' where `type`='.db_input($type))))
                 // Already have a template for the content type
                 continue;

              $sql = 'INSERT INTO '.PAGE_TABLE.' SET type='.db_input($type)
                 .', name='.db_input($page['name'])
                 .', body='.db_input($page['body'])
                 .', notes='.db_input($page['notes'])
                 .', created=NOW(), updated=NOW(), isactive=1';
             db_query($sql);
         }

         // See if there are missing events that should be added to the database
         $event_type = array('login', 'logout', 'message', 'note');
         foreach($event_type as $eType) {
             $sql = sprintf("SELECT * FROM `%s` WHERE name = '%s'",
                     TABLE_PREFIX.'event', $eType);

             $res=db_query($sql);
             $count = db_num_rows($res);

             if($count > 0) {
                 $message = "Event '$eType' already exists.";
                 $ost->logError('Upgrader: Add Events', $message, false);
             } else {
                 // Add event
                 $sql = sprintf("INSERT INTO `%s` (`id`, `name`, `description`)
                        VALUES
                        ('','%s',NULL)",
                         TABLE_PREFIX.'event', $eType);

                 if(!($res=db_query($sql))) {
                     $message = "Unable to add $eType event to `".TABLE_PREFIX.'event'."`.";
                     $ost->logError('Upgrader: Add Events', $message, false);
                 }
             }
         }
    }
}
return 'AddIndexMigration';

?>
