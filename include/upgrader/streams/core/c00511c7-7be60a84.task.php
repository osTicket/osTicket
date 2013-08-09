<?php
require_once INCLUDE_DIR.'class.migrater.php';

class MigrateDbSession extends MigrationTask {
    var $description = "Migrate to database-backed sessions";

    function run() {
        # How about 'dis for a hack?
        osTicketSession::write(session_id(), session_encode());
    }
}

return 'MigrateDbSession';
?>
