-- Drop the state field from thread_events
ALTER TABLE `%TABLE_PREFIX%thread_event`
    DROP COLUMN `state`;
