/**
 * @version v1.9.0
 * @signature 9ef33a062ca3a20190dfad594d594a69
 * @title Add organization features
 *
 */

ALTER TABLE `%TABLE_PREFIX%form`
    CHANGE `type` `type` varchar(8) NOT NULL DEFAULT 'G';

ALTER TABLE `%TABLE_PREFIX%list_items`
    ADD `status` int(11) unsigned NOT NULL DEFAULT 1 AFTER `list_id`,
    ADD `properties` text AFTER `sort`;

ALTER TABLE `%TABLE_PREFIX%organization`
    ADD `status` int(11) unsigned NOT NULL DEFAULT 0 AFTER `staff_id`,
    ADD `domain` varchar(128) NOT NULL DEFAULT '' AFTER `status`,
    ADD `extra` text AFTER `domain`;

ALTER TABLE `%TABLE_PREFIX%filter`
    ADD `status` int(11) unsigned NOT NULL DEFAULT '0' AFTER `isactive`,
    ADD `ext_id` varchar(11) AFTER `topic_id`;

DROP TABLE IF EXISTS `%TABLE_PREFIX%note`;
CREATE TABLE `%TABLE_PREFIX%note` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned,
  `staff_id` int(11) unsigned NOT NULL DEFAULT 0,
  `ext_id` varchar(10),
  `body` text,
  `status` int(11) unsigned NOT NULL DEFAULT 0,
  `sort` int(11) unsigned NOT NULL DEFAULT 0,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '9ef33a062ca3a20190dfad594d594a69'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
