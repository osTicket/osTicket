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
        'team_member:staff_id',
        'ticket_status:id',
        'user:default_email_id',
        'user:id',
        'user:name'
    );

    function run() {
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
    }
}
return 'AddIndexMigration';

?>
