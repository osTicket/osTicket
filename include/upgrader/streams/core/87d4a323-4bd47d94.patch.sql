/**
* @signature 4bd47d94b10bd8a6bab35c119dadf41f
* @version v1.14.0
* @title Ticket Merge Patch
*
* This patch adds a new table, thread_entry_merge, to helpdesks if they
* have the field 'extra' in their thread_entry table
*
*/
-- Create a new table for merge data
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%thread_entry_merge` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `thread_entry_id` int(11) unsigned NOT NULL,
  `data` text,
  PRIMARY KEY (`id`),
  KEY `thread_entry_id` (`thread_entry_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
   SET `value` = '4bd47d94b10bd8a6bab35c119dadf41f', `updated` = NOW()
   WHERE `key` = 'schema_signature' AND `namespace` = 'core';
