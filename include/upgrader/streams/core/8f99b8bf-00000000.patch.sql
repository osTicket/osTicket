/**
 * @version v1.9.3
 * @signature cbf8c933d6d2eaaa971042eb2efce247
 * @title Add custom ticket status support
 *
 */

ALTER TABLE  `%TABLE_PREFIX%list`
    ADD  `masks` INT UNSIGNED NOT NULL DEFAULT  '0' AFTER  `sort_mode`,
    ADD `type` VARCHAR( 16 ) NULL DEFAULT NULL AFTER `masks`,
    ADD INDEX ( `type` );

CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%ticket_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL DEFAULT '',
  `state` varchar(16) NOT NULL DEFAULT 'open',
  `mode` int(11) unsigned NOT NULL DEFAULT '0',
  `flags` int(10) unsigned NOT NULL DEFAULT '0',
  `sort` int(11) unsigned NOT NULL DEFAULT '0',
  `notes` text NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) DEFAULT CHARSET=utf8;

ALTER TABLE  `%TABLE_PREFIX%help_topic`
    ADD  `status_id` INT UNSIGNED NOT NULL DEFAULT  '0' AFTER  `noautoresp`;

ALTER TABLE  `%TABLE_PREFIX%filter`
    ADD  `status_id` INT UNSIGNED NOT NULL DEFAULT  '0' AFTER  `email_id`;

UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'cbf8c933d6d2eaaa971042eb2efce247'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
