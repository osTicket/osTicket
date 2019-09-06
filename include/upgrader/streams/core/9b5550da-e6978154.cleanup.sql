-- Use the temporary table we created as the real thread_event table
-- Drop the old thread_event table

-- Rename old thread_event table
RENAME TABLE `%TABLE_PREFIX%thread_event` TO `%TABLE_PREFIX%thread_event_old`;

-- Change tmp_table to thread_event
RENAME TABLE `tmp_table` TO `%TABLE_PREFIX%thread_event`;

-- Drop old thread_event table
DROP TABLE `%TABLE_PREFIX%thread_event_old`;
