/**
 * Merge ticket thread tables into one
 *
 * Replace the ticket_{message,response,note} tables with a single
 * ticket_thread table that will contain data for all three current message
 * types. This simplifies much of the ticket thread code and paves the way
 * for other types of messages in the future.
 *
 * This patch automagically moves the data from the three federated tables
 * into the one combined table.
 */
DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket_thread`;
CREATE TABLE `%TABLE_PREFIX%ticket_thread` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `pid` int(11) unsigned NOT NULL default '0',
  `ticket_id` int(11) unsigned NOT NULL default '0',
  `staff_id` int(11) unsigned NOT NULL default '0',
  `thread_type` enum('M','R','N') NOT NULL,
  `poster` varchar(128) NOT NULL default '',
  `source` varchar(32) NOT NULL default '',
  `title` varchar(255),
  `body` text NOT NULL,
  `ip_address` varchar(64) NOT NULL default '',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `old_pk` int(11) unsigned NOT NULL,
  `old_pid` int(11) unsigned,
  PRIMARY KEY  (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `staff_id` (`staff_id`),
  KEY `old_pk` (`old_pk`),
  KEY `created` (`created`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket_email_info`;
CREATE TABLE `%TABLE_PREFIX%ticket_email_info` (
  `message_id` int(11) unsigned NOT NULL,
  `email_mid` varchar(255) NOT NULL,
  `headers` text,
  KEY `message_id` (`email_mid`)
) DEFAULT CHARSET=utf8;

-- Transfer messages
INSERT INTO `%TABLE_PREFIX%ticket_thread`
  (`ticket_id`, `thread_type`, `body`, `ip_address`,
    `created`, `updated`, `old_pk`)
  SELECT `ticket_id`, 'M', `message`, `ip_address`,
    `created`, COALESCE(`updated`, NOW()), `msg_id`
    FROM `%TABLE_PREFIX%ticket_message`;

-- Transfer responses
INSERT INTO `%TABLE_PREFIX%ticket_thread`
  (`ticket_id`, `staff_id`, `thread_type`, `poster`, `body`, `ip_address`,
    `created`, `updated`, `old_pk`, `old_pid`)
  SELECT `ticket_id`, `staff_id`, 'R', `staff_name`, `response`, `ip_address`,
    `created`, COALESCE(`updated`, NOW()), `response_id`, `msg_id`
    FROM `%TABLE_PREFIX%ticket_response`;

-- Connect responses to (new) messages
CREATE TABLE `%TABLE_PREFIX%T_resp_links`
    SELECT `id`, `old_pk`
      FROM `%TABLE_PREFIX%ticket_thread`
     WHERE `thread_type` = 'M';

-- Add an index to speed up the linking process
ALTER TABLE `%TABLE_PREFIX%T_resp_links` ADD KEY `old_pk` (`old_pk`, `id`);

UPDATE `%TABLE_PREFIX%ticket_thread`
    SET `pid` = ( SELECT T2.`id` FROM `%TABLE_PREFIX%T_resp_links` T2
                  WHERE `old_pid` = T2.`old_pk` )
    WHERE `thread_type` = 'R'
      AND `old_pid` IS NOT NULL;

DROP TABLE `%TABLE_PREFIX%T_resp_links`;

-- Transfer notes
INSERT INTO `%TABLE_PREFIX%ticket_thread`
 (`ticket_id`, `staff_id`, `thread_type`, `body`, `title`,
   `source`, `poster`, `created`, `updated`, `old_pk`)
 SELECT `ticket_id`, N.staff_id, 'N', `note`, `title`,
   `source`, CONCAT_WS(' ', S.`firstname`, S.`lastname`),
   N.created, NOW(), `note_id`
   FROM `%TABLE_PREFIX%ticket_note` N
   LEFT JOIN `%TABLE_PREFIX%staff` S ON(S.staff_id=N.staff_id);

-- Transfer email information from messages
INSERT INTO `%TABLE_PREFIX%ticket_email_info`
    (`message_id`, `email_mid`, `headers`)
    SELECT ( SELECT T2.`id` FROM `%TABLE_PREFIX%ticket_thread` T2
             WHERE `msg_id` = T2.`old_pk`
               AND `thread_type` = 'M' ),
         `messageId`, `headers`
    FROM `%TABLE_PREFIX%ticket_message`
    WHERE `messageId` IS NOT NULL AND `messageId` <>'';

-- Change collation to utf8_general_ci - to avoid Illegal mix of collations error
ALTER TABLE `%TABLE_PREFIX%ticket_attachment`
    CHANGE `ref_type` `ref_type` ENUM('M','R','N') CHARACTER
    SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'M';

-- Update attachment table
UPDATE `%TABLE_PREFIX%ticket_attachment`
    SET `ref_id` = ( SELECT T2.`id` FROM `%TABLE_PREFIX%ticket_thread` T2
                     WHERE `ref_id` = T2.`old_pk`
                       AND `ref_type` = T2.`thread_type` );

-- Drop temporary columns
ALTER TABLE `%TABLE_PREFIX%ticket_thread` DROP COLUMN `old_pk`;
ALTER TABLE `%TABLE_PREFIX%ticket_thread` DROP COLUMN `old_pid`;

-- Drop old tables
DROP TABLE `%TABLE_PREFIX%ticket_message`;
DROP TABLE `%TABLE_PREFIX%ticket_response`;
DROP TABLE `%TABLE_PREFIX%ticket_note`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='abe9c0cb845be52c10fcd7b3e626a589';
