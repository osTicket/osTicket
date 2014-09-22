/**
 * @version v1.9.5
 * @signature 2257f6f22ca4b31bea6045b8b7d59d56
 * @title Threads revisited
 *
 * This patch adds ability to thread anything
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

-- convert ticket_attachment to thread_entry_attachment
ALTER TABLE  `%TABLE_PREFIX%ticket_attachment`
    CHANGE  `attach_id`  `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
    CHANGE  `ref_id`  `thread_entry_id` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0';
    DROP  `ticket_id`;

RENAME TABLE `%TABLE_PREFIX%ticket_attachment` TO  `%TABLE_PREFIX%thread_entry_attachment`;

-- convert ticket_email_info to thread_entry_mid
ALTER TABLE  `%TABLE_PREFIX%ticket_email_info`
    CHANGE  `thread_id`  `thread_entry_id` INT( 11 ) UNSIGNED NOT NULL,
    CHANGE  `email_mid`  `mid` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    ADD INDEX (  `thread_entry_id` );

RENAME TABLE `%TABLE_PREFIX%ticket_email_info` TO  `%TABLE_PREFIX%thread_entry_email`;

-- Set new schema signature
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '2257f6f22ca4b31bea6045b8b7d59d56'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
