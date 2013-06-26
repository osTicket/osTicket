/**
 * @version v1.7.1
 * @signature 00000000000000000000000000000000
 *
 *  - Migrates the email template table to two tables, groups and templates.
 *    Templates organized in a separate table by group will allow for a more
 *    extensible model for email templates.
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
  FROM `%TABLE_PREFIX%_email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'ticket.autoreply', ticket_autoreply_subj, ticket_autoreply_body, `created`, `updated`
  FROM `%TABLE_PREFIX%_email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'message.autoresp', message_autoresp_subj, message_autoresp_body, `created`, `updated`
  FROM `%TABLE_PREFIX%_email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'ticket.notice', ticket_notice_subj, ticket_notice_body, `created`, `updated`
  FROM `%TABLE_PREFIX%_email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'ticket.overlimit', ticket_overlimit_subj, ticket_overlimit_body, `created`, `updated`
  FROM `%TABLE_PREFIX%_email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'ticket.reply', ticket_reply_subj, ticket_reply_body, `created`, `updated`
  FROM `%TABLE_PREFIX%_email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'ticket.alert', ticket_alert_subj, ticket_alert_body, `created`, `updated`
  FROM `%TABLE_PREFIX%_email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'message.alert', message_alert_subj, message_alert_body, `created`, `updated`
  FROM `%TABLE_PREFIX%_email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'note.alert', note_alert_subj, note_alert_body, `created`, `updated`
  FROM `%TABLE_PREFIX%_email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'assigned.alert', assigned_alert_subj, assigned_alert_body, `created`, `updated`
  FROM `%TABLE_PREFIX%_email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'transfer.alert', transfer_alert_subj, transfer_alert_body, `created`, `updated`
  FROM `%TABLE_PREFIX%_email_template`;

INSERT INTO `%TABLE_PREFIX%_email_template`
  (`tpl_id`, `code_name`, `subject`, `body`, `created`, `updated`)
  SELECT `tpl_id`, 'ticket.overdue', ticket_overdue_subj, ticket_overdue_body, `created`, `updated`
  FROM `%TABLE_PREFIX%_email_template`;

DROP TABLE `%TABLE_PREFIX%email_template`;
ALTER TABLE `%TABLE_PREFIX%_email_template`
    RENAME TO `%TABLE_PREFIX%email_template`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '00000000000000000000000000000000'
	WHERE `key` = 'schema_signature' AND `namespace` = 'core';
