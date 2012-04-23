
DROP TABLE IF EXISTS `%TABLE_PREFIX%api_key`;
CREATE TABLE `%TABLE_PREFIX%api_key` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `isactive` tinyint(1) NOT NULL default '1',
  `ipaddr` varchar(16) NOT NULL,
  `apikey` varchar(255) NOT NULL,
  `notes` text,
  `updated` datetime NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ipaddr` (`ipaddr`),
  UNIQUE KEY `apikey` (`apikey`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

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
  KEY `ispublished` (`ispublished`),
  FULLTEXT KEY `faq` (`question`,`answer`,`keywords`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%faq_attachment`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%faq_attachment` (
  `faq_id` int(10) unsigned NOT NULL,
  `file_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`faq_id`,`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%faq_topic`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%faq_topic` (
  `faq_id` int(10) unsigned NOT NULL,
  `topic_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`faq_id`,`topic_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%config`;
CREATE TABLE `%TABLE_PREFIX%config` (
  `id` tinyint(1) unsigned NOT NULL auto_increment,
  `isonline` tinyint(1) unsigned NOT NULL default '0',
  `timezone_offset` float(3,1) NOT NULL default '0.0',
  `enable_daylight_saving` tinyint(1) unsigned NOT NULL default '0',
  `staff_ip_binding` tinyint(1) unsigned NOT NULL default '1',
  `staff_max_logins` tinyint(3) unsigned NOT NULL default '4',
  `staff_login_timeout` int(10) unsigned NOT NULL default '2',
  `staff_session_timeout` int(10) unsigned NOT NULL default '30',
  `passwd_reset_period` int(10) unsigned NOT NULL default '0',
  `client_max_logins` tinyint(3) unsigned NOT NULL default '4',
  `client_login_timeout` int(10) unsigned NOT NULL default '2',
  `client_session_timeout` int(10) unsigned NOT NULL default '30',
  `max_page_size` tinyint(3) unsigned NOT NULL default '25',
  `max_open_tickets` tinyint(3) unsigned NOT NULL default '0',
  `max_file_size` int(11) unsigned NOT NULL default '1048576',
  `max_user_file_uploads` tinyint(3) unsigned NOT NULL,
  `max_staff_file_uploads` tinyint(3) unsigned NOT NULL,
  `autolock_minutes` tinyint(3) unsigned NOT NULL default '3',
  `overdue_grace_period` int(10) unsigned NOT NULL default '0',
  `alert_email_id` tinyint(4) unsigned NOT NULL default '0',
  `default_email_id` tinyint(4) unsigned NOT NULL default '0',
  `default_dept_id` tinyint(3) unsigned NOT NULL default '0',
  `default_sla_id` int(10) unsigned NOT NULL default '0',
  `default_priority_id` tinyint(2) unsigned NOT NULL default '2',
  `default_template_id` tinyint(4) unsigned NOT NULL default '1',
  `default_timezone_id` int(10) unsigned NOT NULL default '0',
  `default_smtp_id` tinyint(4) unsigned NOT NULL default '0',
  `allow_email_spoofing` tinyint(1) unsigned NOT NULL default '0',
  `clickable_urls` tinyint(1) unsigned NOT NULL default '1',
  `allow_priority_change` tinyint(1) unsigned NOT NULL default '0',
  `use_email_priority` tinyint(1) unsigned NOT NULL default '0',
  `enable_kb` tinyint(1) unsigned NOT NULL default '1',
  `enable_premade` tinyint(1) unsigned NOT NULL default '1',
  `enable_captcha` tinyint(1) unsigned NOT NULL default '0',
  `enable_auto_cron` tinyint(1) unsigned NOT NULL default '0',
  `enable_mail_polling` tinyint(1) unsigned NOT NULL default '0',
  `enable_email_piping` tinyint(1) unsigned NOT NULL default '0',
  `send_sys_errors` tinyint(1) unsigned NOT NULL default '1',
  `send_sql_errors` tinyint(1) unsigned NOT NULL default '1',
  `send_mailparse_errors` tinyint(1) unsigned NOT NULL default '1',
  `send_login_errors` tinyint(1) unsigned NOT NULL default '1',
  `save_email_headers` tinyint(1) unsigned NOT NULL default '1',
  `strip_quoted_reply` tinyint(1) unsigned NOT NULL default '1',
  `log_ticket_activity` tinyint(1) unsigned NOT NULL default '1',
  `ticket_autoresponder` tinyint(1) unsigned NOT NULL default '0',
  `message_autoresponder` tinyint(1) unsigned NOT NULL default '0',
  `ticket_notice_active` tinyint(1) unsigned NOT NULL default '0',
  `ticket_alert_active` tinyint(1) unsigned NOT NULL default '0',
  `ticket_alert_admin` tinyint(1) unsigned NOT NULL default '1',
  `ticket_alert_dept_manager` tinyint(1) unsigned NOT NULL default '1',
  `ticket_alert_dept_members` tinyint(1) unsigned NOT NULL default '0',
  `message_alert_active` tinyint(1) unsigned NOT NULL default '0',
  `message_alert_laststaff` tinyint(1) unsigned NOT NULL default '1',
  `message_alert_assigned` tinyint(1) unsigned NOT NULL default '1',
  `message_alert_dept_manager` tinyint(1) unsigned NOT NULL default '0',
  `note_alert_active` tinyint(1) unsigned NOT NULL default '0',
  `note_alert_laststaff` tinyint(1) unsigned NOT NULL default '1',
  `note_alert_assigned` tinyint(1) unsigned NOT NULL default '1',
  `note_alert_dept_manager` tinyint(1) unsigned NOT NULL default '0',
  `transfer_alert_active` tinyint(1) unsigned NOT NULL default '0',
  `transfer_alert_assigned` tinyint(1) unsigned NOT NULL default '0',
  `transfer_alert_dept_manager` tinyint(1) unsigned NOT NULL default '1',
  `transfer_alert_dept_members` tinyint(1) unsigned NOT NULL default '0',
  `overdue_alert_active` tinyint(1) unsigned NOT NULL default '0',
  `overdue_alert_assigned` tinyint(1) unsigned NOT NULL default '1',
  `overdue_alert_dept_manager` tinyint(1) unsigned NOT NULL default '1',
  `overdue_alert_dept_members` tinyint(1) unsigned NOT NULL default '0',
  `assigned_alert_active` tinyint(1) unsigned NOT NULL default '1',
  `assigned_alert_staff` tinyint(1) unsigned NOT NULL default '1',
  `assigned_alert_team_lead` tinyint(1) unsigned NOT NULL default '0',
  `assigned_alert_team_members` tinyint(1) unsigned NOT NULL default '0',
  `auto_assign_reopened_tickets` tinyint(1) unsigned NOT NULL default '1',
  `show_related_tickets` tinyint(1) unsigned NOT NULL default '1',
  `show_assigned_tickets` tinyint(1) unsigned NOT NULL default '0',
  `show_answered_tickets` tinyint(1) NOT NULL default '0',
  `hide_staff_name` tinyint(1) unsigned NOT NULL default '0',
  `overlimit_notice_active` tinyint(1) unsigned NOT NULL default '0',
  `email_attachments` tinyint(1) unsigned NOT NULL default '1',
  `allow_attachments` tinyint(1) unsigned NOT NULL default '0',
  `allow_email_attachments` tinyint(1) unsigned NOT NULL default '0',
  `allow_online_attachments` tinyint(1) unsigned NOT NULL default '0',
  `allow_online_attachments_onlogin` tinyint(1) unsigned NOT NULL default 
    '0',
  `random_ticket_ids` tinyint(1) unsigned NOT NULL default '1',
  `log_level` tinyint(1) unsigned NOT NULL default '2',
  `log_graceperiod` int(10) unsigned NOT NULL default '12',
  `upload_dir` varchar(255) NOT NULL default '',
  `allowed_filetypes` varchar(255) NOT NULL default '.doc, .pdf',
  `time_format` varchar(32) NOT NULL default ' h:i A',
  `date_format` varchar(32) NOT NULL default 'm/d/Y',
  `datetime_format` varchar(60) NOT NULL default 'm/d/Y g:i a',
  `daydatetime_format` varchar(60) NOT NULL default 'D, M j Y g:ia',
  `reply_separator` varchar(60) NOT NULL default '-- do not edit --',
  `admin_email` varchar(125) NOT NULL default '',
  `helpdesk_title` varchar(255) NOT NULL default 
    'osTicket Support Ticket System',
  `helpdesk_url` varchar(255) NOT NULL default '',
  `schema_signature` char(32) NOT NULL default '',
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `isoffline` (`isonline`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%department`;
CREATE TABLE `%TABLE_PREFIX%department` (
  `dept_id` int(11) unsigned NOT NULL auto_increment,
  `tpl_id` int(10) unsigned NOT NULL default '0',
  `sla_id` int(10) unsigned NOT NULL default '0',
  `email_id` int(10) unsigned NOT NULL default '0',
  `autoresp_email_id` int(10) unsigned NOT NULL default '0',
  `manager_id` int(10) unsigned NOT NULL default '0',
  `dept_name` varchar(32) NOT NULL default '',
  `dept_signature` tinytext NOT NULL,
  `ispublic` tinyint(1) unsigned NOT NULL default '1',
  `ticket_auto_response` tinyint(1) NOT NULL default '1',
  `message_auto_response` tinyint(1) NOT NULL default '0',
  `updated` datetime NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`dept_id`),
  UNIQUE KEY `dept_name` (`dept_name`),
  KEY `manager_id` (`manager_id`),
  KEY `autoresp_email_id` (`autoresp_email_id`),
  KEY `tpl_id` (`tpl_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%department` (`dept_id`, `tpl_id`, `sla_id`, `email_id`, `autoresp_email_id`, `manager_id`, `dept_name`, `dept_signature`, `ispublic`, `ticket_auto_response`, `message_auto_response`) VALUES
    (1, 0, 0, 1, 1, 0, 'Support', 'Support Dept', 1, 1, 1),
    (2, 0, 1, 1, 1, 0, 'Billing', 'Billing Dept', 1, 1, 1);

DROP TABLE IF EXISTS `%TABLE_PREFIX%email`;
CREATE TABLE `%TABLE_PREFIX%email` (
  `email_id` int(11) unsigned NOT NULL auto_increment,
  `noautoresp` tinyint(1) unsigned NOT NULL default '0',
  `priority_id` tinyint(3) unsigned NOT NULL default '2',
  `dept_id` tinyint(3) unsigned NOT NULL default '0',
  `email` varchar(125) NOT NULL default '',
  `name` varchar(32) NOT NULL default '',
  `userid` varchar(125) NOT NULL,
  `userpass` varchar(125) NOT NULL,
  `mail_active` tinyint(1) NOT NULL default '0',
  `mail_host` varchar(125) NOT NULL,
  `mail_protocol` enum('POP','IMAP') NOT NULL default 'POP',
  `mail_encryption` enum('NONE','SSL') NOT NULL,
  `mail_port` int(6) default NULL,
  `mail_fetchfreq` tinyint(3) NOT NULL default '5',
  `mail_fetchmax` tinyint(4) NOT NULL default '30',
  `mail_archivefolder` varchar(255) default NULL,
  `mail_delete` tinyint(1) NOT NULL default '0',
  `mail_errors` tinyint(3) NOT NULL default '0',
  `mail_lasterror` datetime default NULL,
  `mail_lastfetch` datetime default NULL,
  `smtp_active` tinyint(1) default '0',
  `smtp_host` varchar(125) NOT NULL,
  `smtp_port` int(6) default NULL,
  `smtp_secure` tinyint(1) NOT NULL default '1',
  `smtp_auth` tinyint(1) NOT NULL default '1',
  `smtp_spoofing` tinyint(1) unsigned NOT NULL default '0',
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`email_id`),
  UNIQUE KEY `email` (`email`),
  KEY `priority_id` (`priority_id`),
  KEY `dept_id` (`dept_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


INSERT INTO `%TABLE_PREFIX%email_filter` (
  `id`,`isactive`,`execorder`,`reject_email`,`name`,`notes`,`created`)
    VALUES (1, 1, 99, 1, 'SYSTEM BAN LIST', 'Internal list for email banning. Do not remove', NOW());

DROP TABLE IF EXISTS `%TABLE_PREFIX%email_filter_rule`;
CREATE TABLE `%TABLE_PREFIX%email_filter_rule` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `filter_id` int(10) unsigned NOT NULL default '0',
  `what` enum('name','email','subject','body','header') NOT NULL,
  `how` enum('equal','not_equal','contains','dn_contain') NOT NULL,
  `val` varchar(255) NOT NULL,
  `isactive` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `notes` tinytext NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `filter_id` (`filter_id`),
  UNIQUE `filter` (`filter_id`, `what`, `how`, `val`) 
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%email_filter_rule` (
  `id`, `filter_id`, `isactive`, `what`,`how`,`val`,`created`)
    VALUES (1, 1, 1, 'email', 'equal', 'test@example.com',NOW());

DROP TABLE IF EXISTS `%TABLE_PREFIX%email_template`;
CREATE TABLE `%TABLE_PREFIX%email_template` (
  `tpl_id` int(11) NOT NULL auto_increment,
  `cfg_id` int(10) unsigned NOT NULL default '0',
  `isactive` tinyint(1) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  `notes` text,
  `ticket_autoresp_subj` varchar(255) NOT NULL default '',
  `ticket_autoresp_body` text NOT NULL,
  `ticket_notice_subj` varchar(255) NOT NULL,
  `ticket_notice_body` text NOT NULL,
  `ticket_alert_subj` varchar(255) NOT NULL default '',
  `ticket_alert_body` text NOT NULL,
  `message_autoresp_subj` varchar(255) NOT NULL default '',
  `message_autoresp_body` text NOT NULL,
  `message_alert_subj` varchar(255) NOT NULL default '',
  `message_alert_body` text NOT NULL,
  `note_alert_subj` varchar(255) NOT NULL,
  `note_alert_body` text NOT NULL,
  `assigned_alert_subj` varchar(255) NOT NULL default '',
  `assigned_alert_body` text NOT NULL,
  `transfer_alert_subj` varchar(255) NOT NULL default '',
  `transfer_alert_body` text NOT NULL,
  `ticket_overdue_subj` varchar(255) NOT NULL default '',
  `ticket_overdue_body` text NOT NULL,
  `ticket_overlimit_subj` varchar(255) NOT NULL default '',
  `ticket_overlimit_body` text NOT NULL,
  `ticket_reply_subj` varchar(255) NOT NULL default '',
  `ticket_reply_body` text NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`tpl_id`),
  KEY `cfg_id` (`cfg_id`),
  FULLTEXT KEY `message_subj` (`ticket_reply_subj`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- TODO: Dump revised copy before release!!!
INSERT INTO `%TABLE_PREFIX%email_template` (`tpl_id`, `cfg_id`, `isactive`, `name`, `notes`, `ticket_autoresp_subj`, `ticket_autoresp_body`, `ticket_notice_subj`, `ticket_notice_body`, `ticket_alert_subj`, `ticket_alert_body`, `message_autoresp_subj`, `message_autoresp_body`, `message_alert_subj`, `message_alert_body`, `note_alert_subj`, `note_alert_body`, `assigned_alert_subj`, `assigned_alert_body`, `transfer_alert_subj`, `transfer_alert_body`, `ticket_overdue_subj`, `ticket_overdue_body`, `ticket_overlimit_subj`, `ticket_overlimit_body`, `ticket_reply_subj`, `ticket_reply_body`, `created`, `updated`) VALUES
(1, 1, 1, 'osTicket Default Template', 'Default osTicket templates', 'Support Ticket Opened [#%ticket]', '%name,\r\n\r\nA request for support has been created and assigned ticket #%ticket. A representative will follow-up with you as soon as possible.\r\n\r\nYou can view this ticket''s progress online here: %url/view.php?e=%email&t=%ticket.\r\n\r\nIf you wish to send additional comments or information regarding this issue, please don''t open a new ticket. Simply login using the link above and update the ticket.\r\n\r\n%signature', '[#%ticket] %subject', '%name,\r\n\r\nOur customer care team has created a ticket, #%ticket on your behalf, with the following message.\r\n\r\n%message\r\n\r\nIf you wish to provide additional comments or information regarding this issue, please don''t open a new ticket. You can update or view this ticket''s progress online here: %url/view.php?e=%email&t=%ticket.\r\n\r\n%signature', 'New Ticket Alert', '%staff,\r\n\r\nNew ticket #%ticket created.\r\n-------------------\r\nName: %name\r\nEmail: %email\r\nDept: %dept\r\n\r\n%message\r\n-------------------\r\n\r\nTo view/respond to the ticket, please login to the support ticket system.\r\n\r\n- Your friendly Customer Support System - powered by osTicket.', '[#%ticket] Message Added', '%name,\r\n\r\nYour reply to support request #%ticket has been noted.\r\n\r\nYou can view this support request progress online here: %url/view.php?e=%email&t=%ticket.\r\n\r\n%signature', 'New Message Alert', '%staff,\r\n\r\nNew message appended to ticket #%ticket\r\n\r\n----------------------\r\nName: %name\r\nEmail: %email\r\nDept: %dept\r\n\r\n%message\r\n-------------------\r\n\r\nTo view/respond to the ticket, please login to the support ticket system.\r\n\r\n- Your friendly Customer Support System - powered by osTicket.', 'New Internal Note Alert', '%staff,\r\n\r\nInternal note appended to ticket #%ticket\r\n\r\n----------------------\r\nName: %name\r\n\r\n%note\r\n-------------------\r\n\r\nTo view/respond to the ticket, please login to the support ticket system.\r\n\r\n- Your friendly Customer Support System - powered by osTicket.', 'Ticket #%ticket Assigned to you', '%assignee,\r\n\r\n%assigner has assigned ticket #%ticket to you or one of your teams!\r\n\r\n%note\r\n\r\nTo view complete details, simply login to the support system.\r\n\r\n%url/scp/tickets.php?id=%id\r\n\r\n- Your friendly Support Ticket System - powered by osTicket.', 'Ticket Transfer #%ticket - %dept', '%staff,\r\n\r\nTicket #%ticket has been transferred to %dept department\r\n\r\n----------------------\r\n\r\n%note\r\n\r\n-------------------\r\n\r\nTo view/respond to the ticket, please login to the support ticket system.\r\n\r\n%url/scp/ticket.php?id=%id\r\n\r\n- Your friendly Customer Support System - powered by osTicket.', 'Stale Ticket Alert', '%staff,\r\n\r\nA ticket, #%ticket assigned to you or in your department is seriously overdue.\r\n\r\n%url/scp/tickets.php?id=%id\r\n\r\nWe should all work hard to guarantee that all tickets are being addressed in a timely manner. Enough baby talk...please address the issue or you will hear from me again.\r\n\r\n\r\n- Your friendly (although with limited patience) Support Ticket System - powered by osTicket.', 'Open Tickets Limit Reached', '%name\r\n\r\nYou have reached the maximum number of open tickets allowed.\r\n\r\nTo be able to open another ticket, one of your pending tickets must be closed. To update or add comments to an open ticket simply login using the link below.\r\n\r\n%url/view.php?e=%email\r\n\r\nThank you.\r\n\r\nSupport Ticket System', '[#%ticket] %subject', '%name,\r\n\r\nA customer support staff member has replied to your support request, #%ticket with the following response:\r\n\r\n%response\r\n\r\nWe hope this response has sufficiently answered your questions. If not, please do not send another email. Instead, reply to this email or login to your account for a complete archive of all your support requests and responses.\r\n\r\n%url/view.php?e=%email&t=%ticket\r\n\r\n%signature', '2011-08-05 17:00:03', '2012-03-19 01:44:54');

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%file` (`id`, `type`, `size`, `hash`, `name`, `filedata`, `created`) VALUES
(1, 'text/plain', '25', '670c6cc1d1dfc97fad20e5470251b255', 'osTicket.txt', 0x43616e6e6564206174746163686d656e747320726f636b210a, NOW());


DROP TABLE IF EXISTS `%TABLE_PREFIX%groups`;
CREATE TABLE `%TABLE_PREFIX%groups` (
  `group_id` int(10) unsigned NOT NULL auto_increment,
  `group_enabled` tinyint(1) unsigned NOT NULL default '1',
  `group_name` varchar(50) NOT NULL default '',
  `dept_access` varchar(255) NOT NULL default '',
  `can_create_tickets` tinyint(1) unsigned NOT NULL default '1',
  `can_edit_tickets` tinyint(1) unsigned NOT NULL default '1',
  `can_delete_tickets` tinyint(1) unsigned NOT NULL default '0',
  `can_close_tickets` tinyint(1) unsigned NOT NULL default '1',
  `can_assign_tickets` tinyint(1) unsigned NOT NULL default '1',
  `can_transfer_tickets` tinyint(1) unsigned NOT NULL default '1',
  `can_ban_emails` tinyint(1) unsigned NOT NULL default '0',
  `can_manage_premade` tinyint(1) unsigned NOT NULL default '0',
  `can_manage_faq` tinyint(1) unsigned NOT NULL default '0',
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`group_id`),
  KEY `group_active` (`group_enabled`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%groups` (`group_id`, `group_enabled`, `group_name`, `dept_access`, `can_create_tickets`, `can_edit_tickets`, `can_delete_tickets`, `can_close_tickets`, `can_assign_tickets`, `can_transfer_tickets`, `can_ban_emails`, `can_manage_premade`, `can_manage_faq`, `notes`) VALUES
    (1, 1, 'Admins', '2,1', 1, 1, 1, 1, 1, 1, 1, 1, 1, 'overlords'),
    (2, 1, 'Managers', '2,1', 1, 1, 1, 1, 1, 1, 1, 1, 1, ''),
    (3, 1, 'Staff', '2,1', 1, 1, 0, 1, 1, 1, 0, 0, 0, '');

DROP TABLE IF EXISTS `%TABLE_PREFIX%help_topic`;
CREATE TABLE `%TABLE_PREFIX%help_topic` (
  `topic_id` int(11) unsigned NOT NULL auto_increment,
  `isactive` tinyint(1) unsigned NOT NULL default '1',
  `ispublic` tinyint(1) unsigned NOT NULL default '1',
  `noautoresp` tinyint(3) unsigned NOT NULL default '0',
  `priority_id` tinyint(3) unsigned NOT NULL default '0',
  `dept_id` tinyint(3) unsigned NOT NULL default '0',
  `staff_id` int(10) unsigned NOT NULL default '0',
  `team_id` int(10) unsigned NOT NULL default '0',
  `sla_id` int(10) unsigned NOT NULL default '0',
  `topic` varchar(32) NOT NULL default '',
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`topic_id`),
  UNIQUE KEY `topic` (`topic`),
  KEY `priority_id` (`priority_id`),
  KEY `dept_id` (`dept_id`),
  KEY `staff_id` (`staff_id`,`team_id`),
  KEY `sla_id` (`sla_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%help_topic` (`topic_id`, `isactive`, `ispublic`, `noautoresp`, `priority_id`, `dept_id`, `staff_id`, `team_id`, `sla_id`, `topic`, `notes`) VALUES
    (1, 1, 1, 0, 2, 1, 0, 0, 1, 'Support', NULL),
    (2, 1, 1, 0, 3, 1, 0, 0, 0, 'Billing', NULL);

DROP TABLE IF EXISTS `%TABLE_PREFIX%canned_response`;
CREATE TABLE `%TABLE_PREFIX%canned_response` (
  `canned_id` int(10) unsigned NOT NULL auto_increment,
  `dept_id` int(10) unsigned NOT NULL default '0',
  `isenabled` tinyint(1) unsigned NOT NULL default '1',
  `title` varchar(255) NOT NULL default '',
  `response` text NOT NULL,
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`canned_id`),
  UNIQUE KEY `title` (`title`),
  KEY `dept_id` (`dept_id`),
  KEY `active` (`isenabled`),
  FULLTEXT KEY `resp` (`title`,`response`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%canned_response` (`canned_id`, `dept_id`, `isenabled`, `title`, `response`) VALUES
    (1, 0, 1, 'What is osTicket (sample)?', '\r\nosTicket is a widely-used open source support ticket system, an attractive alternative to higher-cost and complex customer support systems - simple, lightweight, reliable, open source, web-based and easy to setup and use.'),
    (2, 0, 1, 'Sample (with variables)', '\r\n%name,\r\n\r\nYour ticket #%ticket created on %createdate is in %dept department.\r\n\r\n');

DROP TABLE IF EXISTS `%TABLE_PREFIX%canned_attachment`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%canned_attachment` (
  `canned_id` int(10) unsigned NOT NULL,
  `file_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`canned_id`,`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%canned_attachment` (`canned_id`, `file_id`) VALUES (1,1);

DROP TABLE IF EXISTS `%TABLE_PREFIX%session`;
CREATE TABLE `%TABLE_PREFIX%session` (
  `session_id` varchar(32) collate utf8_unicode_ci NOT NULL default '',
  `session_data` longtext collate utf8_unicode_ci,
  `session_expire` datetime default NULL,
  `session_updated` datetime default NULL,
  `user_id` int(10) unsigned NOT NULL default '0' COMMENT 'osTicket staff
ID',
  `user_ip` varchar(32) collate utf8_unicode_ci NOT NULL,
  `user_agent` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`session_id`),
  KEY `updated` (`session_updated`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `%TABLE_PREFIX%sla`;
CREATE TABLE `%TABLE_PREFIX%sla` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%sla` (`isactive`, `enable_priority_escalation`,
        `disable_overdue_alerts`, `grace_period`, `name`, `notes`, `created`, `updated`)
    VALUES (1, 1, 0, 48, 'Default SLA', NULL, NOW(), NOW());

DROP TABLE IF EXISTS `%TABLE_PREFIX%staff`;
CREATE TABLE `%TABLE_PREFIX%staff` (
  `staff_id` int(11) unsigned NOT NULL auto_increment,
  `group_id` int(10) unsigned NOT NULL default '0',
  `dept_id` int(10) unsigned NOT NULL default '0',
  `timezone_id` int(10) unsigned NOT NULL default '0',
  `username` varchar(32) NOT NULL default '',
  `firstname` varchar(32) default NULL,
  `lastname` varchar(32) default NULL,
  `passwd` varchar(128) default NULL,
  `email` varchar(128) default NULL,
  `phone` varchar(24) NOT NULL default '',
  `phone_ext` varchar(6) default NULL,
  `mobile` varchar(24) NOT NULL default '',
  `signature` tinytext NOT NULL,
  `notes` text,
  `isactive` tinyint(1) NOT NULL default '1',
  `isadmin` tinyint(1) NOT NULL default '0',
  `isvisible` tinyint(1) unsigned NOT NULL default '1',
  `onvacation` tinyint(1) unsigned NOT NULL default '0',
  `assigned_only` tinyint(1) unsigned NOT NULL default '0',
  `show_assigned_tickets` tinyint(1) unsigned NOT NULL default '0',
  `daylight_saving` tinyint(1) unsigned NOT NULL default '0',
  `change_passwd` tinyint(1) unsigned NOT NULL default '0',
  `max_page_size` int(11) unsigned NOT NULL default '0',
  `auto_refresh_rate` int(10) unsigned NOT NULL default '0',
  `default_signature_type` ENUM( 'none', 'mine', 'dept' ) NOT NULL DEFAULT 'none',
  `created` datetime NOT NULL,
  `lastlogin` datetime default NULL,
  `passwdreset` datetime default NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`staff_id`),
  UNIQUE KEY `username` (`username`),
  KEY `dept_id` (`dept_id`),
  KEY `issuperuser` (`isadmin`),
  KEY `group_id` (`group_id`,`staff_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%syslog`;
CREATE TABLE `%TABLE_PREFIX%syslog` (
  `log_id` int(11) unsigned NOT NULL auto_increment,
  `log_type` enum('Debug','Warning','Error') NOT NULL,
  `title` varchar(255) NOT NULL,
  `log` text NOT NULL,
  `logger` varchar(64) NOT NULL,
  `ip_address` varchar(16) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`log_id`),
  KEY `log_type` (`log_type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%team`;
CREATE TABLE `%TABLE_PREFIX%team` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%team` (`lead_id`, `isenabled`, `noalerts`, `name`, `notes`)
    VALUES (0, 1, 0, 'Level I Support', '');

DROP TABLE IF EXISTS `%TABLE_PREFIX%team_member`;
CREATE TABLE `%TABLE_PREFIX%team_member` (
  `team_id` int(10) unsigned NOT NULL default '0',
  `staff_id` int(10) unsigned NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`team_id`,`staff_id`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket`;
CREATE TABLE `%TABLE_PREFIX%ticket` (
  `ticket_id` int(11) unsigned NOT NULL auto_increment,
  `ticketID` int(11) unsigned NOT NULL default '0',
  `dept_id` int(10) unsigned NOT NULL default '1',
  `sla_id` int(10) unsigned NOT NULL default '0',
  `priority_id` int(10) unsigned NOT NULL default '2',
  `topic_id` int(10) unsigned NOT NULL default '0',
  `staff_id` int(10) unsigned NOT NULL default '0',
  `team_id` int(10) unsigned NOT NULL default '0',
  `email` varchar(120) NOT NULL default '',
  `name` varchar(32) NOT NULL default '',
  `subject` varchar(64) NOT NULL default '[no subject]',
  `phone` varchar(16) default NULL,
  `phone_ext` varchar(8) default NULL,
  `ip_address` varchar(16) NOT NULL default '',
  `status` enum('open','closed') NOT NULL default 'open',
  `source` enum('Web','Email','Phone','API','Other') NOT NULL default
'Other',
  `isoverdue` tinyint(1) unsigned NOT NULL default '0',
  `isanswered` tinyint(1) unsigned NOT NULL default '0',
  `duedate` datetime default NULL,
  `reopened` datetime default NULL,
  `closed` datetime default NULL,
  `lastmessage` datetime default NULL,
  `lastresponse` datetime default NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`ticket_id`),
  UNIQUE KEY `email_extid` (`ticketID`,`email`),
  KEY `dept_id` (`dept_id`),
  KEY `staff_id` (`staff_id`),
  KEY `team_id` (`staff_id`),
  KEY `status` (`status`),
  KEY `priority_id` (`priority_id`),
  KEY `created` (`created`),
  KEY `closed` (`closed`),
  KEY `duedate` (`duedate`),
  KEY `topic_id` (`topic_id`),
  KEY `sla_id` (`sla_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket_attachment`;
CREATE TABLE `%TABLE_PREFIX%ticket_attachment` (
  `attach_id` int(11) unsigned NOT NULL auto_increment,
  `ticket_id` int(11) unsigned NOT NULL default '0',
  `file_id` int(10) unsigned NOT NULL default '0',
  `ref_id` int(11) unsigned NOT NULL default '0',
  `ref_type` enum('M','R','N') NOT NULL default 'M',
  `created` datetime NOT NULL,
  PRIMARY KEY  (`attach_id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `ref_type` (`ref_type`),
  KEY `ref_id` (`ref_id`),
  KEY `file_id` (`file_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket_lock`;
CREATE TABLE `%TABLE_PREFIX%ticket_lock` (
  `lock_id` int(11) unsigned NOT NULL auto_increment,
  `ticket_id` int(11) unsigned NOT NULL default '0',
  `staff_id` int(10) unsigned NOT NULL default '0',
  `expire` datetime default NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`lock_id`),
  UNIQUE KEY `ticket_id` (`ticket_id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket_email_info`;
CREATE TABLE `%TABLE_PREFIX%ticket_email_info` (
  `message_id` int(11) unsigned NOT NULL,
  `email_mid` varchar(255) NOT NULL,
  `headers` text,
  KEY `message_id` (`email_mid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket_event`;
CREATE TABLE `%TABLE_PREFIX%ticket_event` (
  `ticket_id` int(11) unsigned NOT NULL default '0',
  `staff_id` int(11) unsigned NOT NULL,
  `team_id` int(11) unsigned NOT NULL,
  `dept_id` int(11) unsigned NOT NULL,
  `topic_id` int(11) unsigned NOT NULL,
  `state` enum('created','closed','reopened','assigned','transferred','overdue') NOT NULL,
  `staff` varchar(255) NOT NULL default 'SYSTEM',
  `annulled` tinyint(1) unsigned NOT NULL defalt '0',
  `timestamp` datetime NOT NULL,
  KEY `ticket_state` (`ticket_id`, `state`, `timestamp`),
  KEY `ticket_stats` (`timestamp`, `state`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket_priority`;
CREATE TABLE `%TABLE_PREFIX%ticket_priority` (
  `priority_id` tinyint(4) NOT NULL auto_increment,
  `priority` varchar(60) NOT NULL default '',
  `priority_desc` varchar(30) NOT NULL default '',
  `priority_color` varchar(7) NOT NULL default '',
  `priority_urgency` tinyint(1) unsigned NOT NULL default '0',
  `ispublic` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`priority_id`),
  UNIQUE KEY `priority` (`priority`),
  KEY `priority_urgency` (`priority_urgency`),
  KEY `ispublic` (`ispublic`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%ticket_priority` (`priority_id`, `priority`, `priority_desc`, `priority_color`, `priority_urgency`, `ispublic`) VALUES
    (1, 'low', 'Low', '#DDFFDD', 4, 1),
    (2, 'normal', 'Normal', '#FFFFF0', 3, 1),
    (3, 'high', 'High', '#FEE7E7', 2, 1),
    (4, 'emergency', 'Emergency', '#FEE7E7', 1, 0);

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
  PRIMARY KEY  (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `staff_id` (`staff_id`),
  FULLTEXT KEY `body` (`body`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%timezone`;
CREATE TABLE `%TABLE_PREFIX%timezone` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `offset` float(3,1) NOT NULL default '0.0',
  `timezone` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%timezone` (`id`, `offset`, `timezone`) VALUES
(1, -12.0, 'Eniwetok, Kwajalein'),
(2, -11.0, 'Midway Island, Samoa'),
(3, -10.0, 'Hawaii'),
(4, -9.0, 'Alaska'),
(5, -8.0, 'Pacific Time (US & Canada)'),
(6, -7.0, 'Mountain Time (US & Canada)'),
(7, -6.0, 'Central Time (US & Canada), Mexico City'),
(8, -5.0, 'Eastern Time (US & Canada), Bogota, Lima'),
(9, -4.0, 'Atlantic Time (Canada), Caracas, La Paz'),
(10, -3.5, 'Newfoundland'),
(11, -3.0, 'Brazil, Buenos Aires, Georgetown'),
(12, -2.0, 'Mid-Atlantic'),
(13, -1.0, 'Azores, Cape Verde Islands'),
(14, 0.0, 'Western Europe Time, London, Lisbon, Casablanca'),
(15, 1.0, 'Brussels, Copenhagen, Madrid, Paris'),
(16, 2.0, 'Kaliningrad, South Africa'),
(17, 3.0, 'Baghdad, Riyadh, Moscow, St. Petersburg'),
(18, 3.5, 'Tehran'),
(19, 4.0, 'Abu Dhabi, Muscat, Baku, Tbilisi'),
(20, 4.5, 'Kabul'),
(21, 5.0, 'Ekaterinburg, Islamabad, Karachi, Tashkent'),
(22, 5.5, 'Bombay, Calcutta, Madras, New Delhi'),
(23, 6.0, 'Almaty, Dhaka, Colombo'),
(24, 7.0, 'Bangkok, Hanoi, Jakarta'),
(25, 8.0, 'Beijing, Perth, Singapore, Hong Kong'),
(26, 9.0, 'Tokyo, Seoul, Osaka, Sapporo, Yakutsk'),
(27, 9.5, 'Adelaide, Darwin'),
(28, 10.0, 'Eastern Australia, Guam, Vladivostok'),
(29, 11.0, 'Magadan, Solomon Islands, New Caledonia'),
(30, 12.0, 'Auckland, Wellington, Fiji, Kamchatka');
