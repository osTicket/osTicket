/**
 * @version v1.7
 *
 * @schema c00511c7c1db65c0cfad04b4842afc57
 */

-- Add a table to contain the attachment file contents
DROP TABLE IF EXISTS `%TABLE_PREFIX%file`;
CREATE TABLE `%TABLE_PREFIX%file` (
  `id` int(11) NOT NULL auto_increment,
  `type` varchar(255) NOT NULL default '',
  `size` varchar(25) NOT NULL default '',
  `hash` varchar(125) NOT NULL,
  `name` varchar(255) NOT NULL default '',
  `filedata` longblob NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `hash` (`hash`)
) DEFAULT CHARSET=utf8;

-- update ticket attachments ref. table.
ALTER TABLE `%TABLE_PREFIX%ticket_attachment`
    CHANGE `ref_type` `ref_type` ENUM( 'M', 'R', 'N' ) NOT NULL DEFAULT 'M',
    ADD `file_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `ticket_id`,
    ADD INDEX ( `file_id` );

-- Add Team ID and 'API' as a valid ticket source
ALTER TABLE `%TABLE_PREFIX%ticket`
    ADD `team_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `staff_id`,
    ADD INDEX ( `team_id` ),
    CHANGE `source` `source` ENUM(
        'Web', 'Email', 'Phone', 'API', 'Other') NOT NULL DEFAULT 'Other';

-- Add table for ticket history (statistics) tracking
DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket_event`;
CREATE TABLE `%TABLE_PREFIX%ticket_event` (
  `ticket_id` int(11) unsigned NOT NULL default '0',
  `staff_id` int(11) unsigned NOT NULL,
  `team_id` int(11) unsigned NOT NULL,
  `dept_id` int(11) unsigned NOT NULL,
  `topic_id` int(11) unsigned NOT NULL,
  `state` enum('created','closed','reopened','assigned','transferred','overdue') NOT NULL,
  `staff` varchar(255) NOT NULL default 'SYSTEM',
  `timestamp` datetime NOT NULL,
  KEY `ticket_state` (`ticket_id`, `state`, `timestamp`),
  KEY `ticket_stats` (`timestamp`, `state`)
) DEFAULT CHARSET=utf8;

ALTER TABLE `%TABLE_PREFIX%config`
    ADD `passwd_reset_period` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `staff_session_timeout`,
    ADD `default_timezone_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `default_template_id`,
    ADD `default_sla_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `default_dept_id`,
    CHANGE `spoof_default_smtp` `allow_email_spoofing` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    CHANGE `enable_mail_fetch` `enable_mail_polling` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    ADD `max_user_file_uploads` TINYINT UNSIGNED NOT NULL AFTER `max_file_size`,
    ADD `max_staff_file_uploads` TINYINT UNSIGNED NOT NULL AFTER `max_user_file_uploads`,
    ADD `assigned_alert_active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' AFTER `overdue_alert_dept_members`,
    ADD `assigned_alert_staff` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' AFTER `assigned_alert_active`,
    ADD `assigned_alert_team_lead` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `assigned_alert_staff`,
    ADD `assigned_alert_team_members` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `assigned_alert_team_lead`,
    ADD `transfer_alert_active` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `note_alert_dept_manager` ,
    ADD `transfer_alert_assigned` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `transfer_alert_active` ,
    ADD `transfer_alert_dept_manager` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `transfer_alert_assigned` ,
    ADD `transfer_alert_dept_members` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `transfer_alert_dept_manager`,
    ADD `send_sys_errors` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' AFTER `enable_email_piping`,
    ADD `enable_kb` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `use_email_priority`,
    ADD `enable_premade` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' AFTER `enable_kb`,
    ADD `show_related_tickets` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' AFTER `auto_assign_reopened_tickets`,
    ADD `schema_signature` CHAR( 32 ) NOT NULL AFTER `ostversion`;

-- copy over timezone id - based on offset.
UPDATE `%TABLE_PREFIX%config` SET default_timezone_id =
    (SELECT id FROM `%TABLE_PREFIX%timezone` WHERE offset = `%TABLE_PREFIX%config`.timezone_offset);

ALTER TABLE `%TABLE_PREFIX%staff`
    ADD `passwdreset` DATETIME NULL DEFAULT NULL AFTER `lastlogin`;

DROP TABLE IF EXISTS `%TABLE_PREFIX%sla`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%sla` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `isactive` tinyint(1) unsigned NOT NULL default '1',
    `enable_priority_escalation` tinyint(1) unsigned NOT NULL default '1',
    `disable_overdue_alerts` tinyint(1) unsigned NOT NULL default '0',
    `grace_period` int(10) unsigned NOT NULL default '0',
    `name` varchar(64) NOT NULL default '',
    `notes` text,
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    PRIMARY KEY  (`id`),
    UNIQUE KEY `name` (`name`)
) DEFAULT CHARSET=utf8;

-- Create a default SLA
INSERT INTO `%TABLE_PREFIX%sla` (`isactive`, `enable_priority_escalation`,
        `disable_overdue_alerts`, `grace_period`, `name`, `notes`, `created`, `updated`)
    VALUES (1, 1, 0, 48, 'Default SLA', NULL, NOW(), NOW());

-- Create a TEAM table
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%team` (
    `team_id` int(10) unsigned NOT NULL auto_increment,
    `lead_id` int(10) unsigned NOT NULL default '0',
    `isenabled` tinyint(1) unsigned NOT NULL default '1',
    `noalerts` tinyint(1) unsigned NOT NULL default '0',
    `name` varchar(125) NOT NULL default '',
    `notes` text,
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    PRIMARY KEY  (`team_id`),
    UNIQUE KEY `name` (`name`),
    KEY `isnabled` (`isenabled`),
    KEY `lead_id` (`lead_id`)
) DEFAULT CHARSET=utf8;

-- Create a default TEAM
INSERT INTO `%TABLE_PREFIX%team` (`lead_id`, `isenabled`, `noalerts`, `name`, `notes`, `created`, `updated`)
    VALUES (0, 1, 0, 'Level I Support', '', NOW(), NOW());

DROP TABLE IF EXISTS `%TABLE_PREFIX%team_member`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%team_member` (
  `team_id` int(10) unsigned NOT NULL default '0',
  `staff_id` int(10) unsigned NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`team_id`,`staff_id`)
) DEFAULT CHARSET=utf8;

ALTER TABLE `%TABLE_PREFIX%department`
    ADD sla_id INT UNSIGNED NOT NULL DEFAULT '0' AFTER tpl_id;

ALTER TABLE `%TABLE_PREFIX%staff`
    ADD `notes` TEXT NULL DEFAULT NULL AFTER `signature`,
    ADD `assigned_only` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `onvacation`,
    ADD `show_assigned_tickets` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `assigned_only`,
    ADD `timezone_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `dept_id`,
    ADD `default_signature_type` ENUM( 'none', 'mine', 'dept' ) NOT NULL DEFAULT 'none' AFTER `auto_refresh_rate`;

-- Copy over time zone offet to tz_id
UPDATE `%TABLE_PREFIX%staff` SET timezone_id =
    (SELECT id FROM `%TABLE_PREFIX%timezone` WHERE offset = `%TABLE_PREFIX%staff`.timezone_offset);

ALTER TABLE `%TABLE_PREFIX%groups`
    CHANGE `can_manage_kb` `can_manage_premade` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    ADD `can_manage_faq` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `can_manage_premade`,
    ADD `can_assign_tickets` TINYINT( 1 ) UNSIGNED NOT NULL default '1' AFTER `can_close_tickets`,
    ADD notes TEXT NULL AFTER can_manage_faq;

-- Add new columns to the templates table
ALTER TABLE `%TABLE_PREFIX%email_template`
    ADD `isactive` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `cfg_id`,
    ADD `transfer_alert_subj` VARCHAR( 255 ) NOT NULL AFTER `assigned_alert_body`,
    ADD `transfer_alert_body` TEXT NOT NULL AFTER `transfer_alert_subj`;

-- Insert default text for the new messaage tpl + make templates active (all records are updated).
UPDATE `%TABLE_PREFIX%email_template` SET updated=NOW() ,isactive=1, transfer_alert_subj='Ticket Transfer #%ticket - %dept',transfer_alert_body='%staff,\r\n\r\nTicket #%ticket has been transferred to %dept department.\r\n\r\n----------------------\r\n\r\n%note\r\n\r\n-------------------\r\n\r\nTo view/respond to the ticket, please login to the support ticket system.\r\n\r\n%url/scp/ticket.php?id=%id\r\n\r\n- Your friendly Customer Support System - powered by osTicket.';

ALTER TABLE `%TABLE_PREFIX%help_topic`
    ADD ispublic TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' AFTER isactive,
    ADD notes TEXT NULL DEFAULT NULL AFTER topic,
    ADD staff_id INT UNSIGNED NOT NULL DEFAULT '0' AFTER dept_id,
    ADD team_id INT UNSIGNED NOT NULL DEFAULT '0' AFTER staff_id,
    ADD sla_id INT UNSIGNED NOT NULL DEFAULT '0' AFTER team_id,
    ADD INDEX ( staff_id , team_id ),
    ADD INDEX ( sla_id );

ALTER TABLE `%TABLE_PREFIX%email`
    ADD mail_archivefolder VARCHAR(255) NULL AFTER mail_fetchmax,
    ADD notes TEXT NULL DEFAULT NULL AFTER smtp_auth,
    ADD smtp_spoofing TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER smtp_auth;

ALTER TABLE `%TABLE_PREFIX%api_key`
    ADD notes TEXT NULL DEFAULT NULL AFTER apikey,
    ADD UNIQUE (apikey);

ALTER TABLE `%TABLE_PREFIX%ticket`
    ADD sla_id INT UNSIGNED NOT NULL DEFAULT '0' AFTER dept_id,
    ADD INDEX ( sla_id );

DROP TABLE IF EXISTS `%TABLE_PREFIX%email_filter`;
CREATE TABLE `%TABLE_PREFIX%email_filter` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `execorder` int(10) unsigned NOT NULL default '99',
  `isactive` tinyint(1) unsigned NOT NULL default '1',
  `match_all_rules` tinyint(1) unsigned NOT NULL default '0',
  `stop_onmatch` tinyint(1) unsigned NOT NULL default '0',
  `reject_email` tinyint(1) unsigned NOT NULL default '0',
  `use_replyto_email` tinyint(1) unsigned NOT NULL default '0',
  `disable_autoresponder` tinyint(1) unsigned NOT NULL default '0',
  `email_id` int(10) unsigned NOT NULL default '0',
  `priority_id` int(10) unsigned NOT NULL default '0',
  `dept_id` int(10) unsigned NOT NULL default '0',
  `staff_id` int(10) unsigned NOT NULL default '0',
  `team_id` int(10) unsigned NOT NULL default '0',
  `sla_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `email_id` (`email_id`)
) DEFAULT CHARSET=utf8;

-- Copy banlist to a new email filter
INSERT INTO `%TABLE_PREFIX%email_filter` (`execorder`, `isactive`,
    `match_all_rules`, `stop_onmatch`, `reject_email`, `use_replyto_email`,
    `disable_autoresponder`, `email_id`, `priority_id`, `dept_id`, `staff_id`,
    `team_id`, `sla_id`, `name`, `notes`) VALUES
    (99, 1, 0, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 'SYSTEM BAN LIST',
        'Internal list for email banning. Do not remove');

DROP TABLE IF EXISTS `%TABLE_PREFIX%email_filter_rule`;
CREATE TABLE `%TABLE_PREFIX%email_filter_rule` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `filter_id` int(10) unsigned NOT NULL default '0',
  `what` enum('name','email','subject','body','header') NOT NULL,
  `how` enum('equal','not_equal','contains','dn_contain') NOT NULL,
  `val` varchar(255) NOT NULL,
  `isactive` tinyint( 1 ) UNSIGNED NOT NULL DEFAULT '1',
  `notes` tinytext NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `filter_id` (`filter_id`),
  UNIQUE `filter` (`filter_id`, `what`, `how`, `val`)
) DEFAULT CHARSET=utf8;

-- SYSTEM BAN LIST was the first filter created, with ID of '1'
INSERT INTO `%TABLE_PREFIX%email_filter_rule` (`filter_id`, `what`, `how`, `val`)
    SELECT LAST_INSERT_ID(), 'email', 'equal', email FROM `%TABLE_PREFIX%email_banlist`;

-- Create table session
DROP TABLE IF EXISTS `%TABLE_PREFIX%session`;
CREATE TABLE `%TABLE_PREFIX%session` (
  `session_id` varchar(32) collate utf8_unicode_ci NOT NULL default '',
  `session_data` longtext collate utf8_unicode_ci,
  `session_expire` datetime default NULL,
  `session_updated` datetime default NULL,
  `user_id` int(10) unsigned NOT NULL default '0',
  `user_ip` varchar(32) collate utf8_unicode_ci NOT NULL,
  `user_agent` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`session_id`),
  KEY `updated` (`session_updated`),
  KEY `user_id` (`user_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Create tables for FAQ + attachments.
DROP TABLE IF EXISTS `%TABLE_PREFIX%faq`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%faq` (
  `faq_id` int(10) unsigned NOT NULL auto_increment,
  `category_id` int(10) unsigned NOT NULL default '0',
  `ispublished` tinyint(1) unsigned NOT NULL default '0',
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `keywords` tinytext,
  `notes` text,
  `created` date NOT NULL,
  `updated` date NOT NULL,
  PRIMARY KEY  (`faq_id`),
  UNIQUE KEY `question` (`question`),
  KEY `category_id` (`category_id`),
  KEY `ispublished` (`ispublished`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%faq_attachment`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%faq_attachment` (
  `faq_id` int(10) unsigned NOT NULL,
  `file_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`faq_id`,`file_id`)
) DEFAULT CHARSET=utf8;

-- Add support for attachments to canned responses
DROP TABLE IF EXISTS `%TABLE_PREFIX%canned_attachment`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%canned_attachment` (
  `canned_id` int(10) unsigned NOT NULL,
  `file_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`canned_id`,`file_id`)
) DEFAULT CHARSET=utf8;

-- Rename kb_premade to canned_response
ALTER TABLE `%TABLE_PREFIX%kb_premade`
  CHANGE `premade_id` `canned_id` int(10) unsigned NOT NULL auto_increment,
  CHANGE `title` `title` VARCHAR( 255 ) NOT NULL DEFAULT '',
  CHANGE `answer` `response` TEXT NOT NULL,
  ADD `notes` TEXT NOT NULL AFTER `response`,
  DROP INDEX `title`;

ALTER TABLE `%TABLE_PREFIX%kb_premade` RENAME TO `%TABLE_PREFIX%canned_response`;

DROP TABLE IF EXISTS `%TABLE_PREFIX%faq_category`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%faq_category` (
  `category_id` int(10) unsigned NOT NULL auto_increment,
  `ispublic` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(125) default NULL,
  `description` TEXT NOT NULL,
  `notes` tinytext NOT NULL,
  `created` date NOT NULL,
  `updated` date NOT NULL,
  PRIMARY KEY  (`category_id`),
  KEY (`ispublic`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%faq_topic`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%faq_topic` (
  `faq_id` int(10) unsigned NOT NULL,
  `topic_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`faq_id`,`topic_id`)
) DEFAULT CHARSET=utf8;


UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='7be60a8432e44989e782d5914ef784d2';
