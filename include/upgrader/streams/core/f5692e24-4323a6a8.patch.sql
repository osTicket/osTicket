/**
 * @version v1.9.0
 * @signature 4323a6a81c35efbf7722b7fc4e475440
 * @title Add client login feature
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

ALTER TABLE `%TABLE_PREFIX%faq_category`
  CHANGE `created` `created` datetime NOT NULL,
  CHANGE `updated` `updated` datetime NOT NULL;

-- There was a major goof for osTicket 1.8.0 where the installer created a
-- `form_id` column in the `%filter` table; however, the upgrader neglected
-- to add the column. Therefore, users who have upgraded from a version
-- previous to 1.8.0 will not have the `form_id` column in their database
-- whereas users who installed osTicket >= v1.8.0 and upgraded will have the
-- column. Since MySQL has no concept of `ADD COLUMN IF NOT EXISTS`, this
-- dynamic query will assist with adding the column if it doesn't exist.
SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = '%TABLE_PREFIX%filter'
        AND table_schema = DATABASE()
        AND column_name = 'form_id'
    ) > 0,
    "SELECT 1",
    "ALTER TABLE `%TABLE_PREFIX%filter` ADD `form_id` int(11) unsigned NOT NULL default '0' AFTER `sla_id`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

ALTER TABLE `%TABLE_PREFIX%filter`
  ADD `topic_id` int(11) unsigned NOT NULL default '0' AFTER `form_id`;

ALTER TABLE `%TABLE_PREFIX%email`
  ADD `topic_id` int(11) unsigned NOT NULL default '0' AFTER `dept_id`;

ALTER TABLE `%TABLE_PREFIX%help_topic`
  ADD `sort` int(10) unsigned NOT NULL default '0' AFTER `form_id`;

RENAME TABLE `%TABLE_PREFIX%page` TO `%TABLE_PREFIX%content`;
ALTER TABLE `%TABLE_PREFIX%content`
  CHANGE `type` `type` varchar(32) NOT NULL default 'other';

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

-- Transfer access link template
INSERT INTO `%TABLE_PREFIX%content`
    (`name`, `body`, `type`, `isactive`, `created`, `updated`)
    SELECT A1.`subject`, A1.`body`, 'access-link', 1, A1.`created`, A1.`updated`
    FROM `%TABLE_PREFIX%email_template` A1
    WHERE A1.`tpl_id` = (SELECT `value` FROM `%TABLE_PREFIX%config` A3
        WHERE A3.`key` = 'default_template_id' and `namespace` = 'core')
    AND A1.`code_name` = 'user.accesslink';

-- Transfer staff password reset link
INSERT INTO `%TABLE_PREFIX%content`
    (`name`, `body`, `type`, `isactive`, `created`, `updated`)
    SELECT A1.`subject`, A1.`body`, 'pwreset-staff', 1, A1.`created`, A1.`updated`
    FROM `%TABLE_PREFIX%email_template` A1
    WHERE A1.`tpl_id` = (SELECT `value` FROM `%TABLE_PREFIX%config` A3
        WHERE A3.`key` = 'default_template_id' and `namespace` = 'core')
    AND A1.`code_name` = 'staff.pwreset';

-- No longer saved in the email_template table
DELETE FROM `%TABLE_PREFIX%email_template`
    WHERE `code_name` IN ('staff.pwreset', 'user.accesslink');

-- The original patch for d51f303a-dad45ca2.patch.sql migrated all the
-- thread entries from text to html. Now that the format column exists in
-- the ticket_thread table, we opted to retroactively add the format column
-- to the dad45ca2 patch. Therefore, anyone upgrading from osTicket < 1.8.0
-- to v1.9.0 and further will alreay have a `format` column when they arrive
-- at this patch. In such a case, we'll just change the default to 'html'
SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = '%TABLE_PREFIX%ticket_thread'
        AND table_schema = DATABASE()
        AND column_name = 'format'
    ) > 0,
    "ALTER TABLE `%TABLE_PREFIX%ticket_thread` CHANGE `format` `format` varchar(16) NOT NULL default 'html'",
    "ALTER TABLE `%TABLE_PREFIX%ticket_thread` ADD `format` varchar(16) NOT NULL default 'html' AFTER `body`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '4323a6a81c35efbf7722b7fc4e475440'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
