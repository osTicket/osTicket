/**
 * @version v1.8.2
 * @signature 4323a6a81c35efbf7722b7fc4e475440
 * @title Add client accounts table
 *
 */

ALTER TABLE `%TABLE_PREFIX%session`
  CHANGE `user_id` `user_id` varchar(16) NOT NULL default '0' COMMENT 'osTicket staff/client ID';

ALTER TABLE `%TABLE_PREFIX%staff`
  CHANGE `signature` `signature` text NOT NULL;

ALTER TABLE `%TABLE_PREFIX%department`
  CHANGE `dept_signature` `dept_signature` text NOT NULL;

DROP TABLE IF EXISTS `%TABLE_PREFIX%email_account`;
CREATE TABLE `%TABLE_PREFIX%email_account` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `protocol` varchar(64) NOT NULL DEFAULT '',
  `host` varchar(128) NOT NULL DEFAULT '',
  `port` int(11) NOT NULL,
  `username` varchar(128) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `options` varchar(512) DEFAULT NULL,
  `errors` int(11) unsigned DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `lastconnect` timestamp NULL DEFAULT NULL,
  `lasterror` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

ALTER TABLE `%TABLE_PREFIX%ticket_thread`
  ADD `format` varchar(16) NOT NULL default 'html' AFTER `body`;

ALTER TABLE `%TABLE_PREFIX%faq_category`
  CHANGE `created` `created` datetime NOT NULL,
  CHANGE `updated` `updated` datetime NOT NULL;

ALTER TABLE `%TABLE_PREFIX%filter`
  ADD `topic_id` int(11) unsigned NOT NULL default '0' AFTER `form_id`;

ALTER TABLE `%TABLE_PREFIX%email`
  ADD `topic_id` int(11) unsigned NOT NULL default '0' AFTER `dept_id`;

ALTER TABLE `%TABLE_PREFIX%help_topic`
  ADD `sort` int(10) unsigned NOT NULL default '0' AFTER `form_id`;

-- Add `content_id` to the content table to allow for translations
RENAME TABLE `%TABLE_PREFIX%page` TO `%TABLE_PREFIX%content`;
ALTER TABLE `%TABLE_PREFIX%content`
  CHANGE `type` `type` varchar(32) NOT NULL default 'other',
  ADD `content_id` int(10) unsigned NOT NULL default 0;

UPDATE `%TABLE_PREFIX%content`
  SET `content_id` = `id`;

DROP TABLE IF EXISTS `%TABLE_PREFIX%user_account`;
CREATE TABLE `%TABLE_PREFIX%user_account` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `org_id` int(11) unsigned NOT NULL,
  `status` int(11) unsigned NOT NULL DEFAULT '0',
  `timezone_id` int(11) NOT NULL DEFAULT '0',
  `dst` tinyint(1) NOT NULL DEFAULT '1',
  `lang` varchar(16) DEFAULT NULL,
  `username` varchar(64) DEFAULT NULL,
  `passwd` varchar(128) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `backend` varchar(32) DEFAULT NULL,
  `registered` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%organization`;
CREATE TABLE `%TABLE_PREFIX%organization` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

DELETE FROM `%TABLE_PREFIX%config` where `namespace`='core'
    AND `key` = 'show_related_tickets';

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '4323a6a81c35efbf7722b7fc4e475440'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
