/**
 * @version v1.10.0
 * @signature 5cd0a25a54fd27ed95f00d62edda4c6d
 * @title Add support for ticket tasks
 *
 * This patch adds ability to thread anything and introduces tasks
 *
 */

-- Add thread table
DROP TABLE IF EXISTS `%TABLE_PREFIX%thread`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%thread` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int(11) unsigned NOT NULL,
  `object_type` char(1) NOT NULL,
  `extra` text,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `object_id` (`object_id`),
  KEY `object_type` (`object_type`)
) DEFAULT CHARSET=utf8;

-- create threads
INSERT INTO `%TABLE_PREFIX%thread`
    (`object_id`, `object_type`, `created`)
    SELECT t1.ticket_id, 'T', t1.created
        FROM `%TABLE_PREFIX%ticket_thread` t1
        JOIN (
            SELECT ticket_id, MIN(id) as id
            FROM `%TABLE_PREFIX%ticket_thread`
            WHERE `thread_type` = 'M'
            GROUP BY ticket_id
    ) t2
    ON (t1.ticket_id=t2.ticket_id and t1.id=t2.id)
    ORDER BY t1.created;

-- convert ticket_thread to thread_entry
CREATE TABLE `%TABLE_PREFIX%thread_entry` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `pid` int(11) unsigned NOT NULL default '0',
  `thread_id` int(11) unsigned NOT NULL default '0',
  `staff_id` int(11) unsigned NOT NULL default '0',
  `user_id` int(11) unsigned not null default 0,
  `type` char(1) NOT NULL default '',
  `flags` int(11) unsigned NOT NULL default '0',
  `poster` varchar(128) NOT NULL default '',
  `editor` int(10) unsigned NULL,
  `editor_type` char(1) NULL,
  `source` varchar(32) NOT NULL default '',
  `title` varchar(255),
  `body` text NOT NULL,
  `format` varchar(16) NOT NULL default 'html',
  `ip_address` varchar(64) NOT NULL default '',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `pid` (`pid`),
  KEY `thread_id` (`thread_id`),
  KEY `staff_id` (`staff_id`),
  KEY `type` (`type`)
) DEFAULT CHARSET=utf8;

-- Set the ORIGINAL_MESSAGE flag to all the first messages of each thread
CREATE TABLE `%TABLE_PREFIX%_orig_msg_ids`
  (`id` INT NOT NULL, PRIMARY KEY (id))
  SELECT MIN(id) AS `id` FROM `%TABLE_PREFIX%ticket_thread`
  WHERE `thread_type` = 'M'
  GROUP BY `ticket_id`;

INSERT INTO `%TABLE_PREFIX%thread_entry`
  (`id`, `pid`, `thread_id`, `staff_id`, `user_id`, `type`, `flags`,
    `poster`, `source`, `title`, `body`, `format`, `ip_address`, `created`,
    `updated`)
  SELECT t1.`id`, t1.`pid`, t2.`id`, t1.`staff_id`, t1.`user_id`, t1.`thread_type`,
    CASE WHEN t3.`id` IS NULL THEN 0 ELSE 1 END,
    t1.`poster`, t1.`source`, t1.`title`, t1.`body`, t1.`format`, t1.`ip_address`,
    t1.`created`, t1.`updated`
  FROM `%TABLE_PREFIX%ticket_thread` t1
  LEFT JOIN `%TABLE_PREFIX%thread` t2 ON (t2.object_id = t1.ticket_id AND t2.object_type = 'T')
  LEFT JOIN `%TABLE_PREFIX%_orig_msg_ids` t3 ON (t1.id = t3.id);

DROP TABLE `%TABLE_PREFIX%ticket_thread`;
DROP TABLE `%TABLE_PREFIX%_orig_msg_ids`;

-- move records in ticket_attachment to generic attachment table
ALTER TABLE  `%TABLE_PREFIX%attachment`
    DROP PRIMARY KEY,
    ADD  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
    ADD UNIQUE  `file-type` (`object_id`, `file_id`, `type`);

INSERT INTO `%TABLE_PREFIX%attachment`
    (`object_id`, `type`, `file_id`, `inline`)
    SELECT `ref_id`, 'H', `file_id`, `inline`
    FROM `%TABLE_PREFIX%ticket_attachment` A
    WHERE A.file_id > 0;

-- convert ticket_email_info to thread_entry_email
ALTER TABLE  `%TABLE_PREFIX%ticket_email_info`
    ADD INDEX (  `thread_id` );

ALTER TABLE  `%TABLE_PREFIX%ticket_email_info`
    CHANGE  `thread_id`  `thread_entry_id` INT( 11 ) UNSIGNED NOT NULL,
    CHANGE  `email_mid`  `mid` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

RENAME TABLE `%TABLE_PREFIX%ticket_email_info` TO  `%TABLE_PREFIX%thread_entry_email`;

-- create task task
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int(11) NOT NULL DEFAULT '0',
  `object_type` char(1) NOT NULL,
  `number` varchar(20) DEFAULT NULL,
  `dept_id` int(10) unsigned NOT NULL DEFAULT '0',
  `sla_id` int(10) unsigned NOT NULL DEFAULT '0',
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0',
  `team_id` int(10) unsigned NOT NULL DEFAULT '0',
  `flags` int(10) unsigned NOT NULL DEFAULT '0',
  `duedate` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `dept_id` (`dept_id`),
  KEY `staff_id` (`staff_id`),
  KEY `team_id` (`team_id`),
  KEY `created` (`created`),
  KEY `sla_id` (`sla_id`),
  KEY `object` (`object_id`,`object_type`)
) DEFAULT CHARSET=utf8;

-- rename ticket sequence numbering

UPDATE `%TABLE_PREFIX%config`
    SET `key` = 'ticket_number_format'
    WHERE `key` = 'number_format'  AND `namespace` = 'core';

UPDATE `%TABLE_PREFIX%config`
    SET `key` = 'ticket_sequence_id'
    WHERE `key` = 'sequence_id'  AND `namespace` = 'core';

-- add parent department support
ALTER TABLE `%TABLE_PREFIX%department`
  DROP INDEX  `dept_name`,
  ADD `pid` int(11) unsigned default NULL AFTER `id`,
  ADD `path` varchar(128) NOT NULL default '/' AFTER `message_auto_response`,
  ADD UNIQUE  `name` (  `name` ,  `pid` );

UPDATE `%TABLE_PREFIX%department`
  SET `path` = CONCAT('/', id, '/');

-- Set new schema signature
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '5cd0a25a54fd27ed95f00d62edda4c6d'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
