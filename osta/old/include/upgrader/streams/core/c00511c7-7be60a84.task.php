<?php
require_once INCLUDE_DIR.'class.migrater.php';

class MigrateDbSession extends MigrationTask {
    var $description = "Migrate to database-backed sessions";

    function run($max_time) {
        # How about 'dis for a hack?
        $session = new DbSessionBackend();
        $session->write(session_id(), session_encode());
    }
}

return 'MigrateDbSession';
?>
