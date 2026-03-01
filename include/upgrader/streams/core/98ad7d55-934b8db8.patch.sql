/**
 * @version v1.11
 * @signature 934b8db8f97d6859d013b6219957724f
 * @title Custom Queues, Columns
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
  `flags` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(64) NOT NULL DEFAULT '',
  `primary` varchar(64) NOT NULL DEFAULT '',
  `secondary` varchar(64) DEFAULT NULL,
  `filter` varchar(32) DEFAULT NULL,
  `truncate` varchar(16) DEFAULT NULL,
  `annotations` text,
  `conditions` text,
  `extra` text,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%queue_columns`;
CREATE TABLE `%TABLE_PREFIX%queue_columns` (
  `queue_id` int(11) unsigned NOT NULL,
  `column_id` int(11) unsigned NOT NULL,
  `bits` int(10) unsigned NOT NULL DEFAULT '0',
  `sort` int(10) unsigned NOT NULL DEFAULT '1',
  `heading` varchar(64) DEFAULT NULL,
  `width` int(10) unsigned NOT NULL DEFAULT '100',
  PRIMARY KEY (`queue_id`, `column_id`)
) DEFAULT CHARSET=utf8;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '934b8db8f97d6859d013b6219957724f'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
