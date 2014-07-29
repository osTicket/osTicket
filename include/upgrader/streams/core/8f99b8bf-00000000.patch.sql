/**
 * @version v1.10.0
 * @signature 00000000000000000000000000000000
 * @title Custom Ticket Numbers
 *
 * This patch adds support for ticket number sequences to the database
 * rather than the original implementation which had support for generating
 * random numbers or using the database-created ticket_id value.
 *
 * This script will also migrate the previous settings, namely the
 * use_random_ids config settings to the new system.
 */

DROP TABLE IF EXISTS `%TABLE_PREFIX%sequence`;
CREATE TABLE `%TABLE_PREFIX%sequence` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `flags` int(10) unsigned DEFAULT NULL,
  `next` bigint(20) unsigned NOT NULL DEFAULT '1',
  `increment` int(11) DEFAULT '1',
  `padding` char(1) DEFAULT '0',
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
-- InnoDB is intended here because transaction support is required for row
-- locking
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET @random = (SELECT `value` FROM `%TABLE_PREFIX%config`
    WHERE `namespace` = 'core' AND `key` = 'use_random_ids');

-- Sequence (id=1) will be loaded in the task file
INSERT INTO `%TABLE_PREFIX%config` (`namespace`, `key`, `value`)
    VALUES
    ('core', 'number_format', CASE WHEN @random THEN '######' ELSE '#' END),
    ('core', 'sequence_id', CASE WHEN @random THEN 0 ELSE 1 END);

DELETE FROM `%TABLE_PREFIX%config`
WHERE `namespace`='core' AND `key` = 'use_random_ids';

ALTER TABLE `%TABLE_PREFIX%help_topic`
  ADD `flags` int(10) unsigned DEFAULT '0' AFTER `noautoresp`,
  ADD `sequence_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `form_id`,
  ADD `number_format` varchar(32) DEFAULT NULL AFTER `topic`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '00000000000000000000000000000000'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
