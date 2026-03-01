-- Rename old thread_event table
RENAME TABLE `%TABLE_PREFIX%thread_entry_email` TO `%TABLE_PREFIX%thread_entry_email_old`;

-- Change tmp_table to thread_event
RENAME TABLE `%TABLE_PREFIX%thread_entry_email_new` TO `%TABLE_PREFIX%thread_entry_email`;

-- Drop old thread_event table
DROP TABLE `%TABLE_PREFIX%thread_entry_email_old`;
