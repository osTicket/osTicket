/**
 * @signature 8b923d61f0eea5216931c19aa591f4f2
 * @version v1.14.0
 * @title Add Schedules / Business Hours
 *
 * This patch adds tables and fields for schedule feature.
 */

DROP TABLE IF EXISTS `%TABLE_PREFIX%schedule`;
CREATE TABLE `%TABLE_PREFIX%schedule` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `flags` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `timezone` varchar(64) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%schedule_entry`;
CREATE TABLE `%TABLE_PREFIX%schedule_entry` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) unsigned NOT NULL DEFAULT '0',
  `flags` int(11) unsigned NOT NULL DEFAULT '0',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `repeats` varchar(16) NOT NULL DEFAULT 'never',
  `starts_on` date DEFAULT NULL,
  `starts_at` time DEFAULT NULL,
  `ends_on` date DEFAULT NULL,
  `ends_at` time DEFAULT NULL,
  `stops_on` datetime DEFAULT NULL,
  `day` tinyint(4) DEFAULT NULL,
  `week` tinyint(4) DEFAULT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `schedule_id` (`schedule_id`),
  KEY `repeats` (`repeats`)
) DEFAULT CHARSET=utf8;

-- Add schedule_id to department and sla tables
ALTER TABLE `%TABLE_PREFIX%department`
  ADD `schedule_id` int(10) unsigned NOT NULL default '0' AFTER `sla_id`;

ALTER TABLE `%TABLE_PREFIX%sla`
  ADD `schedule_id` int(10) unsigned NOT NULL default '0' AFTER `id`;

 -- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '8b923d61f0eea5216931c19aa591f4f2'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';

