/**
* @signature 226da4e7298917160c7499cb63370f83
* @version v1.11.0
* @title Database Optimization
*
* This patch is for optimizing our database to handle large amounts of data
* more smoothly.
*
* 1. remove states in thread_event table and add them to their own event table
*/

-- Create a new table to store events
CREATE TABLE `%TABLE_PREFIX%event` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `description` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- Add event_id column to thread_events
ALTER TABLE `%TABLE_PREFIX%thread_event`
    ADD `event_id` int(11) unsigned AFTER `thread_id`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
   SET `value` = '226da4e7298917160c7499cb63370f83', `updated` = NOW()
   WHERE `key` = 'schema_signature' AND `namespace` = 'core';
