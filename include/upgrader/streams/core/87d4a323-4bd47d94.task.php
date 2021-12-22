<?php

/*
 * Populates thread_entry_merge for helpdesks if they
 * have the field 'extra' in their thread_entry table
 */

class PopulateThreadEntryMerge extends MigrationTask {
    var $description = "Populates thread_entry_merge";

    function run($max_time) {
        $sql = sprintf('SHOW COLUMNS FROM %s LIKE %s;', THREAD_ENTRY_TABLE, '\'extra\'');
        $res = db_query($sql);
        if ($res && $res->num_rows > 0) {
            $extras = ThreadEntry::objects()
                ->filter(array('extra__isnull' => false))
                ->values_flat('id', 'extra');
            foreach ($extras as $row) {
                list($id, $extra) = $row;
                $mergeInfo = new ThreadEntryMergeInfo(array(
                    'thread_entry_id' => $id,
                    'data' => $extra,
                ));
                $mergeInfo->save();
            }
        }
    }
}

return 'PopulateThreadEntryMerge';

?>
