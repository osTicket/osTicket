/**
* @signature e69781546e08be96d787199a911d0ffe
* @version v1.14.0
* @title Thread Type
*
* This patch adds a new field to the Thread Event table called thread_type
* it allows us to be able to delete threads and thread entries when a ticket
* is deleted while still maintaining dashboard statistics
*
*/

-- Create a blank temporary table with thread_event indexes
CREATE TABLE `%TABLE_PREFIX%thread_event_new` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int(11) unsigned NOT NULL default '0',
  `thread_type` char(1) DEFAULT '',
  `event_id` int(11) unsigned DEFAULT NULL,
  `staff_id` int(11) unsigned NOT NULL,
  `team_id` int(11) unsigned NOT NULL,
  `dept_id` int(11) unsigned NOT NULL,
  `topic_id` int(11) unsigned NOT NULL,
  `data` varchar(1024) DEFAULT NULL COMMENT 'Encoded differences',
  `username` varchar(128) NOT NULL default 'SYSTEM',
  `uid` int(11) unsigned DEFAULT NULL,
  `uid_type` char(1) NOT NULL DEFAULT 'S',
  `annulled` tinyint(1) unsigned NOT NULL default '0',
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_state` (`thread_id`, `event_id`, `timestamp`),
  KEY `ticket_stats` (`timestamp`, `event_id`)
) DEFAULT CHARSET=utf8;

-- Insert thread_events into `%TABLE_PREFIX%thread_event_new`
INSERT `%TABLE_PREFIX%thread_event_new`
SELECT id, thread_id, ' ', event_id, staff_id, team_id, dept_id, topic_id, data, username, uid, uid_type, annulled, timestamp
FROM `%TABLE_PREFIX%thread_event`;

-- Update thread_type column
UPDATE `%TABLE_PREFIX%thread_event_new` A1
JOIN `%TABLE_PREFIX%thread` A2 ON A1.thread_id = A2.id
SET A1.thread_type = A2.object_type;

-- Delete unneeded threads
DELETE A1
FROM `%TABLE_PREFIX%thread` A1
JOIN `%TABLE_PREFIX%thread_event` A2 ON A2.thread_id = A1.id
WHERE A2.event_id = 14;

-- Delete unneeded thread entries
DELETE A1
FROM `%TABLE_PREFIX%thread_entry` A1
LEFT JOIN `%TABLE_PREFIX%thread` A2 ON(A2.id=A1.thread_id)
WHERE A2.id IS NULL;

-- Set deleted threads to 0 in `%TABLE_PREFIX%thread_event_new`
UPDATE `%TABLE_PREFIX%thread_event_new` A1
JOIN `%TABLE_PREFIX%thread_event_new` A2 ON A2.thread_id = A1.thread_id
SET A2.thread_id = 0
WHERE A1.event_id = 14;

-- Rename old thread_event table
RENAME TABLE `%TABLE_PREFIX%thread_event` TO `%TABLE_PREFIX%thread_event_old`;

-- Change tmp_table to thread_event
RENAME TABLE `%TABLE_PREFIX%thread_event_new` TO `%TABLE_PREFIX%thread_event`;

-- Drop old thread_event table
DROP TABLE `%TABLE_PREFIX%thread_event_old`;

-- Organization / Name
UPDATE `%TABLE_PREFIX%form_field` A1
JOIN `%TABLE_PREFIX%form` A2 ON (A1.`form_id` = A2.`id` AND A2.`type` = 'O')
SET A1.`flags` = A1.`flags` | 16, A1.`type` = 'text'
WHERE A1.`name` = 'name';

-- Contact Information / Name, Email
UPDATE `%TABLE_PREFIX%form_field` A1
JOIN `%TABLE_PREFIX%form` A2 ON (A1.`form_id` = A2.`id` AND A2.`type` = 'U')
SET A1.`flags` = A1.`flags` | 16
WHERE (A1.`name` = 'name' OR A1.`name` = 'email')
AND A1.`type` = 'text';

-- Ticket Details / Issue Summary
UPDATE `%TABLE_PREFIX%form_field` A1
JOIN `%TABLE_PREFIX%form` A2 ON (A1.`form_id` = A2.`id` AND A2.`type` = 'T')
SET A1.`flags` = A1.`flags` | 16
WHERE A1.`name` = 'subject' AND A1.`type` = 'text';

-- Task Details / Title
UPDATE `%TABLE_PREFIX%form_field` A1
JOIN `%TABLE_PREFIX%form` A2 ON (A1.`form_id` = A2.`id` AND A2.`type` = 'A')
SET A1.`flags` = A1.`flags` | 16
WHERE A1.`name` = 'title' AND A1.`type` = 'text';

-- Company Information / Name
UPDATE `%TABLE_PREFIX%form_field` A1
JOIN `%TABLE_PREFIX%form` A2 ON (A1.`form_id` = A2.`id` AND A2.`type` = 'C')
SET A1.`flags` = A1.`flags` | 16
WHERE A1.`name` = 'name' AND A1.`type` = 'text';

-- Company Information / Website, Phone Number
UPDATE `%TABLE_PREFIX%form_field` A1
JOIN `%TABLE_PREFIX%form` A2 ON (A1.`form_id` = A2.`id` AND A2.`type` = 'C')
SET A1.`flags` = A1.`flags` | 262160
WHERE (A1.`name` = 'website' AND A1.`type` = 'text') OR (A1.`name` = 'phone' AND A1.`type` = 'phone');

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
  SET `value` = 'e69781546e08be96d787199a911d0ffe', `updated` = NOW()
  WHERE `key` = 'schema_signature' AND `namespace` = 'core';
