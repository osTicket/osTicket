/**
* @signature 9b5550dad8d3f036d62eb1340f230317
* @version v1.14.0
* @title Ticket Merge
*
* This patch adds new fields to the database to allow us to merge Tickets
*
*/

-- Add ticket_pid column to tickets
ALTER TABLE `%TABLE_PREFIX%ticket`
    ADD `ticket_pid` int(11) unsigned DEFAULT NULL AFTER `ticket_id`;

-- Add sort column to tickets
ALTER TABLE `%TABLE_PREFIX%ticket`
    ADD `sort` int(11) unsigned NOT NULL DEFAULT '0' AFTER `flags`;

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
