/**
 * @version v1.9.3
 * @signature 9b9e2dc4551d448f081f180ca3829fa8
 * @title Add custom ticket status support
 *
 */

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

UPDATE `%TABLE_PREFIX%config`
    SET `value` = '9b9e2dc4551d448f081f180ca3829fa8'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
