/**
* @signature 70921d5c3920ab240b08bdd55bc894c8
* @version v1.11.0
* @title Make Public CustomQueues Configurable
*
* This patch adds staff_id to queue_columns table and queue_config table to
* allow for ability to customize public queue columns as well as additional
* settings
*
*/

-- Add staff_id to queue_columns table
ALTER TABLE `%TABLE_PREFIX%queue_columns`
    ADD `staff_id` int(11) unsigned NOT NULL AFTER  `column_id`;

-- Set staff_id to 0 for default columns
UPDATE `%TABLE_PREFIX%queue_columns`
    SET `staff_id` = 0;

-- Add staff_id to PRIMARY KEY
ALTER TABLE `%TABLE_PREFIX%queue_columns`
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (`queue_id`, `column_id`, `staff_id`);

-- Set staff_id to 0 for public queues
UPDATE `%TABLE_PREFIX%queue`
    SET `staff_id` = 0
    WHERE (`flags` & 1) >0;

-- Add bridge table for public Queues staff configuration & settings
DROP TABLE IF EXISTS `%TABLE_PREFIX%queue_config`;
CREATE TABLE `%TABLE_PREFIX%queue_config` (
  `queue_id` int(11) unsigned NOT NULL,
  `staff_id` int(11) unsigned NOT NULL,
  `setting` text,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`queue_id`,`staff_id`)
) DEFAULT CHARSET=utf8;

 -- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '70921d5c3920ab240b08bdd55bc894c8'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
