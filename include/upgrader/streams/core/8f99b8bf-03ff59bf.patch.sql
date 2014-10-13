/**
 * @version v1.9.4
 * @signature 03ff59bf35a58a102e9b32ad33c2839f
 * @title Custom Ticket Numbers and Statuses
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
    WHERE `namespace` = 'core' AND `key` = 'random_ticket_ids');

-- Sequence (id=1) will be loaded in the task file
INSERT INTO `%TABLE_PREFIX%config` (`namespace`, `key`, `value`)
    VALUES
    ('core', 'number_format', IF(@random, '######', '#')),
    ('core', 'sequence_id', IF(@random, 0, 1));

ALTER TABLE `%TABLE_PREFIX%help_topic`
    ADD `flags` int(10) unsigned DEFAULT '0' AFTER `noautoresp`,
    ADD `sequence_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `form_id`,
    ADD `number_format` varchar(32) DEFAULT NULL AFTER `topic`;

ALTER TABLE  `%TABLE_PREFIX%list`
    ADD  `masks` INT UNSIGNED NOT NULL DEFAULT  '0' AFTER  `sort_mode`,
    ADD `type` VARCHAR( 16 ) NULL DEFAULT NULL AFTER `masks`,
    ADD INDEX ( `type` );

CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%ticket_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL DEFAULT '',
  `state` varchar(16) DEFAULT NULL,
  `mode` int(11) unsigned NOT NULL DEFAULT '0',
  `flags` int(11) unsigned NOT NULL DEFAULT '0',
  `sort` int(11) unsigned NOT NULL DEFAULT '0',
  `properties` text NOT NULL,
  `notes` text NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `state` ( `state` )
) DEFAULT CHARSET=utf8;

ALTER TABLE  `%TABLE_PREFIX%help_topic`
    ADD  `status_id` INT UNSIGNED NOT NULL DEFAULT  '0' AFTER  `noautoresp`;

ALTER TABLE  `%TABLE_PREFIX%filter`
    ADD  `status_id` INT UNSIGNED NOT NULL DEFAULT  '0' AFTER  `email_id`;

ALTER TABLE  `%TABLE_PREFIX%ticket`
    ADD  `status_id` INT UNSIGNED NOT NULL DEFAULT  '0' AFTER  `user_email_id`,
    ADD INDEX (`status_id`);

UPDATE `%TABLE_PREFIX%ticket` SET  `status_id` = 3
    WHERE  `status` = 'closed';

UPDATE `%TABLE_PREFIX%ticket` SET  `status_id` = 1
        WHERE  `status` = 'open';

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '03ff59bf35a58a102e9b32ad33c2839f'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
