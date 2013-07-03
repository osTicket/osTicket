/**
 * @version v1.7.1
 * @signature 740428f9986da6ad85f88ec841b57bfe
 *
 *  - Migrates the email template table to two tables, groups and templates.
 *    Templates organized in a separate table by group will allow for a more
 *    extensible model for email templates.
 *
 *  - Add site page table and default templates required by the system to function.
 *
 */

DROP TABLE IF EXISTS `%TABLE_PREFIX%email_template_group`;
CREATE TABLE `%TABLE_PREFIX%email_template_group` (
  `tpl_id` int(11) NOT NULL auto_increment,
  `isactive` tinyint(1) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  `notes` text,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL,
  PRIMARY KEY  (`tpl_id`)
) DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%email_template_group`
    SELECT `tpl_id`, `isactive`, `name`, `notes`, `created`, `updated`
    FROM `%TABLE_PREFIX%email_template`;

DROP TABLE IF EXISTS `%TABLE_PREFIX%_email_template`;
CREATE TABLE `%TABLE_PREFIX%_email_template` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `tpl_id` int(11) unsigned NOT NULL,
  `code_name` varchar(32) NOT NULL,
  `subject` varchar(255) NOT NULL default '',
  `body` text NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `template_lookup` (`tpl_id`, `code_name`)
) DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'ticket.autoresp', ticket_autoresp_subj, ticket_autoresp_body, `created`, `updated`
  FROM `%TABLE_PREFIX%email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'ticket.autoreply', ticket_autoreply_subj, ticket_autoreply_body, `created`, `updated`
  FROM `%TABLE_PREFIX%email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'message.autoresp', message_autoresp_subj, message_autoresp_body, `created`, `updated`
  FROM `%TABLE_PREFIX%email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'ticket.notice', ticket_notice_subj, ticket_notice_body, `created`, `updated`
  FROM `%TABLE_PREFIX%email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'ticket.overlimit', ticket_overlimit_subj, ticket_overlimit_body, `created`, `updated`
  FROM `%TABLE_PREFIX%email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'ticket.reply', ticket_reply_subj, ticket_reply_body, `created`, `updated`
  FROM `%TABLE_PREFIX%email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'ticket.alert', ticket_alert_subj, ticket_alert_body, `created`, `updated`
  FROM `%TABLE_PREFIX%email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'message.alert', message_alert_subj, message_alert_body, `created`, `updated`
  FROM `%TABLE_PREFIX%email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'note.alert', note_alert_subj, note_alert_body, `created`, `updated`
  FROM `%TABLE_PREFIX%email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'assigned.alert', assigned_alert_subj, assigned_alert_body, `created`, `updated`
  FROM `%TABLE_PREFIX%email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'transfer.alert', transfer_alert_subj, transfer_alert_body, `created`, `updated`
  FROM `%TABLE_PREFIX%email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'ticket.overdue', ticket_overdue_subj, ticket_overdue_body, `created`, `updated`
  FROM `%TABLE_PREFIX%email_template`;

DROP TABLE `%TABLE_PREFIX%email_template`;
ALTER TABLE `%TABLE_PREFIX%_email_template`
    RENAME TO `%TABLE_PREFIX%email_template`;

-- pages
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%page` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `isactive` tinyint(1) unsigned NOT NULL default '0',
  `type` enum('landing','offline','thank-you','other') NOT NULL default 'other',
  `name` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) DEFAULT CHARSET=utf8;


INSERT INTO `%TABLE_PREFIX%page` (`isactive`, `type`, `name`, `body`, `notes`, `created`, `updated`) VALUES
(1, 'offline', 'Offline', '<div>\r\n<h1><span style="font-size: medium">Support Ticket System Offline</span></h1>\r\n<p>Thank you for your interest in contacting us.</p>\r\n<p>Our helpdesk is offline at the moment, please check back at a later time.</p>\r\n</div>', 'Default offline page', NOW(), NOW()),
(1, 'thank-you', 'Thank you', '<div>%{ticket.name},<br />\r\n    \r\n<p>\r\nThank you for contacting us.</p><p> A support ticket request #%{ticket.number} has been created and a representative will be getting back to you shortly if necessary.</p>\r\n          \r\n<p>Support Team </p>\r\n</div>', 'Default "thank you" page displayed after the end-user creates a web ticket.', NOW(), NOW()),
(1, 'landing', 'Landing', '<h1>Welcome to the Support Center</h1>\r\n<p>In order to streamline support requests and better serve you, we utilize a support ticket system. Every support request is assigned a unique ticket number which you can use to track the progress and responses online. For your reference we provide complete archives and history of all your support requests. A valid email address is required to submit a ticket.\r\n</p>\r\n', 'Introduction text on the landing page.', NOW(), NOW());

INSERT INTO `%TABLE_PREFIX%config` (`key`, `value`, `namespace`) VALUES
  ('landing_page_id', (SELECT `id` FROM `%TABLE_PREFIX%page` WHERE `type` = 'landing'), 'core')
, ('offline_page_id', (SELECT `id` FROM `%TABLE_PREFIX%page` WHERE `type` = 'offline'), 'core')
, ('thank-you_page_id', (SELECT `id` FROM `%TABLE_PREFIX%page` WHERE `type` = 'thank-you'), 'core');

ALTER TABLE  `%TABLE_PREFIX%help_topic`
    ADD  `page_id` INT UNSIGNED NOT NULL DEFAULT  '0' AFTER  `sla_id` ,
    ADD INDEX (  `page_id` );

ALTER TABLE  `%TABLE_PREFIX%file`
    ADD  `ft` CHAR( 1 ) NOT NULL DEFAULT  'T' AFTER `id`,
    ADD INDEX (  `ft` );

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '740428f9986da6ad85f88ec841b57bfe'
	WHERE `key` = 'schema_signature' AND `namespace` = 'core';
