/**
 * @version v1.11
 * @signature a099b35b5ed141de6213f165b197e623
 * @title Custom Queues, Advanced Sorting
 *
 * Add advanced sorting configuration to custom queues
 */

ALTER TABLE `%TABLE_PREFIX%queue`
  ADD `sort_id` int(11) unsigned AFTER `columns_id`;

DROP TABLE IF EXISTS `%TABLE_PREFIX%queue_sort`;
CREATE TABLE `%TABLE_PREFIX%queue_sort` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `root` varchar(32) DEFAULT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `columns` text,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%queue_sorts`;
CREATE TABLE `%TABLE_PREFIX%queue_sorts` (
  `queue_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sort_id` int(11) unsigned NOT NULL,
  `bits` int(11) unsigned NOT NULL DEFAULT '0',
  `sort` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`queue_id`)
) DEFAULT CHARSET=utf8;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'a099b35b5ed141de6213f165b197e623'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';

