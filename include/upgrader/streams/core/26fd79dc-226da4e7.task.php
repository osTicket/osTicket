<?php

class EventEnumRemoval extends MigrationTask {
    var $description = "Remove the Enum 'state' field from ThreadEvents";

    function run() {
        // Move states into the new table as events
        $states = array('created','closed','reopened','assigned', 'released', 'transferred', 'referred', 'overdue','edited','viewed','error','collab','resent', 'deleted');
        foreach ($states as $state) {
            $sql= "INSERT INTO ".EVENT_TABLE." (`name`, `description`)
                    VALUES('".
                    $state. "', '')";
            db_query($sql);
        }

        $sql = "UPDATE ".THREAD_EVENT_TABLE. " AS this
                INNER JOIN (
                    SELECT id, name
                    FROM ". EVENT_TABLE. ") AS that
                SET this.event_id = that.id
                WHERE this.state = that.name";
        db_query($sql);
    }

}
return 'EventEnumRemoval';

?>
