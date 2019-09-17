/**
* @signature 9b5550dad8d3f036d62eb1340f230317
* @version v1.14.0
* @title Ticket Merge
*
* This patch adds new fields to the database to allow us to merge Tickets
*
*/

-- At some point, a flags field was added to the Ticket table in the
-- installer, but it was never added to a patch, so for some people
-- that try to upgrade, they get an error that says it is unable to add
-- the sort field after flags in the ticket table.
-- Since MySQL has no concept of `ADD COLUMN IF NOT EXISTS`, this
-- dynamic query will assist with adding the column if it doesn't exist.
SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = '%TABLE_PREFIX%ticket'
        AND table_schema = DATABASE()
        AND column_name = 'flags'
    ) > 0,
    "SELECT 1",
    "ALTER TABLE `%TABLE_PREFIX%ticket` ADD `flags` int(11) unsigned NOT NULL default '0' AFTER `lock_id`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

-- Add sort and ticket_pid column to tickets
ALTER TABLE `%TABLE_PREFIX%ticket`
    ADD `sort` int(11) unsigned NOT NULL DEFAULT '0' AFTER `flags`,
    ADD `ticket_pid` int(11) unsigned DEFAULT NULL AFTER `ticket_id`;

-- Add extra column to thread entries
ALTER TABLE `%TABLE_PREFIX%thread_entry`
    ADD `extra` text AFTER `ip_address`;

-- Insert new events
INSERT INTO `%TABLE_PREFIX%event` (`id`, `name`, `description`)
VALUES
    ('','merged',''),
	('','unlinked',''),
    ('','linked','');

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
   SET `value` = '9b5550dad8d3f036d62eb1340f230317', `updated` = NOW()
   WHERE `key` = 'schema_signature' AND `namespace` = 'core';
