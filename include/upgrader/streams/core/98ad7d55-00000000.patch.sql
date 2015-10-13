/**
 * @version v1.11
 * @signature 00000000000000000000000000000000
 *
 * Add custom queues, custom columns, and quick filter capabilities to the
 * system.
 */

ALTER TABLE `%TABLE_PREFIX%queue`
  ADD `columns_id` int(11) unsigned AFTER `parent_id`,
  ADD `filter` varchar(64) AFTER `config`,
  ADD `root` varchar(32) DEFAULT NULL AFTER `filter`,
  ADD `path` varchar(80) NOT NULL DEFAULT '/' AFTER `root`;

DROP TABLE IF EXISTS `%TABLE_PREFIX%queue_column`;
CREATE TABLE `%TABLE_PREFIX%queue_column` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `queue_id` int(10) unsigned NOT NULL,
  `flags` int(10) unsigned NOT NULL DEFAULT '0',
  `sort` int(10) unsigned NOT NULL DEFAULT '0',
  `heading` varchar(64) NOT NULL DEFAULT '',
  `primary` varchar(64) NOT NULL DEFAULT '',
  `secondary` varchar(64) DEFAULT NULL,
  `width` int(10) unsigned DEFAULT NULL,
  `filter` varchar(32) DEFAULT NULL,
  `truncate` varchar(16) DEFAULT NULL,
  `annotations` text,
  `conditions` text,
  `extra` text,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

