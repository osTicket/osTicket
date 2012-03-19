ALTER TABLE `%TABLE_PREFIX%ticket` ADD `topic_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `priority_id`;

ALTER TABLE `%TABLE_PREFIX%ticket` ADD INDEX ( `topic_id` );

ALTER TABLE `%TABLE_PREFIX%ticket` CHANGE `topic` `helptopic` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `%TABLE_PREFIX%groups` ADD `can_create_tickets` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `dept_access`;

ALTER TABLE `%TABLE_PREFIX%staff` ADD `auto_refresh_rate` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `max_page_size`;

ALTER TABLE `%TABLE_PREFIX%config` ADD `ticket_notice_active` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `message_autoresponder`;

ALTER TABLE `%TABLE_PREFIX%config` ADD `enable_captcha` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `use_email_priority`;

ALTER TABLE `%TABLE_PREFIX%config` ADD `log_ticket_activity` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `strip_quoted_reply`;

ALTER TABLE `%TABLE_PREFIX%config` ADD `staff_ip_binding` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `enable_daylight_saving`;

ALTER TABLE `%TABLE_PREFIX%staff` CHANGE `signature` `signature` TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `%TABLE_PREFIX%department` CHANGE `dept_signature` `dept_signature` TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `%TABLE_PREFIX%email_template`
ADD `ticket_notice_subj` VARCHAR( 255 ) NOT NULL AFTER `ticket_autoresp_body` ,
ADD `ticket_notice_body` TEXT NOT NULL AFTER `ticket_notice_subj`;

INSERT INTO `%TABLE_PREFIX%kb_premade` (`premade_id`, `dept_id`, `isenabled`, `title`, `answer`, `created`, `updated`) VALUES
    ('', 0, 1, 'Sample (with variables)', '\r\n%name,\r\n\r\nYour ticket #%ticket created on %createdate is in %dept department.\r\n\r\n', NOW(), NOW());
