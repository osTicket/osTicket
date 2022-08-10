/**
* @signature 00c949a623b82848baaf3480b51307e3
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

INSERT INTO `%TABLE_PREFIX%event` (`id`, `name`, `description`)
VALUES
	(1,'created',''),
	(2,'closed',''),
	(3,'reopened',''),
	(4,'assigned',''),
	(5,'released',''),
	(6,'transferred',''),
	(7,'referred',''),
	(8,'overdue',''),
	(9,'edited',''),
	(10,'viewed',''),
	(11,'error',''),
	(12,'collab',''),
	(13,'resent',''),
	(14,'deleted','');

-- Add event_id column to thread_events
ALTER TABLE `%TABLE_PREFIX%thread_event`
    ADD `event_id` int(11) unsigned AFTER `thread_id`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
   SET `value` = '00c949a623b82848baaf3480b51307e3', `updated` = NOW()
   WHERE `key` = 'schema_signature' AND `namespace` = 'core';
