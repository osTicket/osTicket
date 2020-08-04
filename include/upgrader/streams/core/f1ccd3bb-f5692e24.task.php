<?php

if (!defined('TICKET_EMAIL_INFO_TABLE'))
    define('TICKET_EMAIL_INFO_TABLE', TABLE_PREFIX.'ticket_email_info');

/*
 * Drops the `thread_id` primary key on the ticket_email_info table if it
 * exists
 */

class DropTicketEmailInfoPk extends MigrationTask {
    var $description = "Reticulating splines";

    function run($max_time) {
        $sql = 'SELECT `INDEX_NAME` FROM information_schema.statistics
          WHERE table_schema = '.db_input(DBNAME)
           .' AND table_name = '.db_input(TICKET_EMAIL_INFO_TABLE)
           .' AND column_name = '.db_input('thread_id');
        if ($name = db_result(db_query($sql))) {
            if ($name == 'PRIMARY') {
                db_query('ALTER TABLE `'.TICKET_EMAIL_INFO_TABLE
                    .'` DROP PRIMARY KEY');
            }
        }
    }
}

return 'DropTicketEmailInfoPk';

?>
