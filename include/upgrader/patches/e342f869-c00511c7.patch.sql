/**
 * @version v1.6 ST
 * @signature c00511c7c1db65c0cfad04b4842afc57
 *
 *  Upgrade from 1.6 RC5 to 1.6 ST
 *  
 */

ALTER TABLE `%TABLE_PREFIX%ticket` 
    CHANGE `topic` `helptopic` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
    ADD `topic_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `priority_id`,
    ADD INDEX ( `topic_id` );

ALTER TABLE `%TABLE_PREFIX%groups` 
    ADD `can_create_tickets` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `dept_access`;

ALTER TABLE `%TABLE_PREFIX%staff` 
    ADD `auto_refresh_rate` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `max_page_size`,
    CHANGE `signature` `signature` TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `%TABLE_PREFIX%department`
    CHANGE `dept_signature` `dept_signature` TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `%TABLE_PREFIX%config`
    ADD `ticket_notice_active` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `message_autoresponder`,
    ADD `enable_captcha` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `use_email_priority`,
    ADD `log_ticket_activity` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `strip_quoted_reply`,
    ADD `staff_ip_binding` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `enable_daylight_saving`;


ALTER TABLE `%TABLE_PREFIX%email_template`
    ADD `ticket_notice_subj` VARCHAR( 255 ) NOT NULL AFTER `ticket_autoresp_body` ,
    ADD `ticket_notice_body` TEXT NOT NULL AFTER `ticket_notice_subj`;

UPDATE `%TABLE_PREFIX%email_template` SET updated=NOW(),
    `ticket_notice_subj` = '[#%ticket] %subject',
    `ticket_notice_body` = '%name,\r\n\r\nOur customer care team personnel has created a ticket #%ticket on your behalf, with the following message:\r\n\r\n%message\r\n\r\nIf you wish to provide additional comments or information regarding this issue, please don''t open a new ticket. You can update or view this ticket''s progress online here: %url/view.php?e=%email&t=%ticket.\r\n\r\n%signature';

UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_overlimit_subj` = REPLACE(`ticket_overlimit_subj`, '%id', '%ticket'),
        `ticket_overlimit_body` = REPLACE(`ticket_overlimit_body`, '%id', '%ticket');

INSERT INTO `%TABLE_PREFIX%kb_premade` (`premade_id`, `dept_id`, `isenabled`, `title`, `answer`, `created`, `updated`) 
    VALUES ('', 0, 1, 'Sample (with variables)', '\r\n%name,\r\n\r\nYour ticket #%ticket created on %createdate is in %dept department.\r\n\r\n', NOW(), NOW());

UPDATE `%TABLE_PREFIX%config`
    SET `ostversion`='1.6 ST';
