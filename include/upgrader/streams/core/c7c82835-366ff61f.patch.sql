/**
 * @version v1.9.6
 * @signature  366ff61fbe023fe840a4a65138320d11
 * @title Add tasks
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

ALTER TABLE  `%TABLE_PREFIX%ticket_thread`
    ADD  `thread_id` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `pid` ,
    ADD INDEX (  `thread_id` );

UPDATE  `%TABLE_PREFIX%ticket_thread` t1
    LEFT JOIN  `%TABLE_PREFIX%thread` t2 ON ( t2.object_id = t1.ticket_id )
    SET t1.thread_id = t2.id;

-- convert ticket_thread to thread_entry
ALTER TABLE  `%TABLE_PREFIX%ticket_thread`
    CHANGE  `thread_type`  `type` CHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    ADD INDEX (  `type` );

RENAME TABLE `%TABLE_PREFIX%ticket_thread` TO  `%TABLE_PREFIX%thread_entry` ;

-- add thread id to ticket table
ALTER TABLE  `%TABLE_PREFIX%ticket`
    ADD  `thread_id` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `number` ,
    ADD INDEX (  `thread_id` );

UPDATE  `%TABLE_PREFIX%ticket` t1
    LEFT JOIN  `%TABLE_PREFIX%thread` t2 ON ( t2.object_id = t1.ticket_id )
    SET t1.thread_id = t2.id;

-- move records in ticket_attachment to generic attachment table
ALTER TABLE  `%TABLE_PREFIX%attachment`
    DROP PRIMARY KEY,
    ADD  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
    ADD UNIQUE  `file-type` (`object_id`, `file_id`, `type`);

INSERT INTO `%TABLE_PREFIX%attachment`
    (`object_id`, `type`, `file_id`, `inline`)
    SELECT `ref_id`, 'H', `file_id`, `inline`
    FROM `%TABLE_PREFIX%ticket_attachment`;

-- convert ticket_email_info to thread_entry_email
ALTER TABLE  `%TABLE_PREFIX%ticket_email_info`
    CHANGE  `thread_id`  `thread_entry_id` INT( 11 ) UNSIGNED NOT NULL,
    CHANGE  `email_mid`  `mid` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    ADD INDEX (  `thread_entry_id` );

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

-- Set new schema signature
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '366ff61fbe023fe840a4a65138320d11'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
