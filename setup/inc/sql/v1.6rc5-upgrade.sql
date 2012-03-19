ALTER TABLE `%TABLE_PREFIX%email` 
ADD `userid` VARCHAR( 125 ) NOT NULL AFTER `name` ,
ADD `userpass` VARCHAR( 125 ) NOT NULL AFTER `userid`,
ADD `mail_active` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `userpass` ,
ADD `mail_host` VARCHAR( 125 ) NOT NULL AFTER `mail_active` ,
ADD `mail_protocol` ENUM( 'POP', 'IMAP' ) NOT NULL AFTER `mail_host` ,
ADD `mail_encryption` ENUM( 'NONE', 'SSL' ) NOT NULL AFTER `mail_protocol` ,
ADD `mail_port` INT( 6 ) NULL AFTER `mail_encryption` ,
ADD `mail_fetchfreq` TINYINT( 3 ) NOT NULL DEFAULT '5' AFTER `mail_port` ,
ADD `mail_fetchmax` TINYINT( 4 ) NOT NULL DEFAULT '30' AFTER `mail_fetchfreq` ,
ADD `mail_delete` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `mail_fetchmax` ,
ADD `mail_errors` TINYINT( 3 ) NOT NULL DEFAULT '0' AFTER `mail_delete` ,
ADD `mail_lasterror` DATETIME NULL AFTER `mail_errors` ,
ADD `mail_lastfetch` DATETIME NULL AFTER `mail_lasterror` ,
ADD `smtp_active` TINYINT( 1 ) NOT NULL AFTER `mail_lastfetch` ,
ADD `smtp_host` VARCHAR( 125 ) NOT NULL AFTER `smtp_active` ,
ADD `smtp_port` INT( 6 ) NULL AFTER `smtp_host` ,
ADD `smtp_auth` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `smtp_port` ;

ALTER TABLE `%TABLE_PREFIX%groups` ADD `can_edit_tickets` TINYINT UNSIGNED NOT NULL DEFAULT '0' AFTER `dept_access` ;

UPDATE `%TABLE_PREFIX%groups`  SET `can_edit_tickets`=1 WHERE `can_delete_tickets`=1;

ALTER TABLE `%TABLE_PREFIX%ticket` ADD `duedate` DATETIME NULL AFTER `isoverdue` ;

ALTER TABLE `%TABLE_PREFIX%ticket` ADD INDEX ( `duedate` ) ;

ALTER TABLE `%TABLE_PREFIX%ticket` CHANGE `source` `source` ENUM( 'Web', 'Email', 'Phone', 'Other' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'Other' ;
 
ALTER TABLE `%TABLE_PREFIX%email_template` 
ADD `note_alert_subj` VARCHAR( 255 ) NOT NULL AFTER `message_alert_body` ,
ADD `note_alert_body` TEXT NOT NULL AFTER `note_alert_subj` ;

UPDATE `%TABLE_PREFIX%email_template`  SET  `note_alert_subj` = 'New Internal Note Alert',`note_alert_body` = '%staff,\r\n\r\nInternal note appended to ticket #%ticket\r\n\r\n----------------------\r\nName: %name\r\n\r\n%note\r\n-------------------\r\n\r\nTo view/respond to the ticket, please login to the support ticket system.\r\n\r\nYour friendly,\r\n\r\nCustomer Support System - powered by osTicket.';

ALTER TABLE `%TABLE_PREFIX%ticket_message` ADD `messageId` VARCHAR( 255 ) NULL AFTER `ticket_id` ;
ALTER TABLE `%TABLE_PREFIX%ticket_message` ADD INDEX ( `messageId` ) ;

ALTER TABLE `%TABLE_PREFIX%config` 
ADD `note_alert_active` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `message_alert_dept_manager` ,
ADD `note_alert_laststaff` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `note_alert_active` ,
ADD `note_alert_assigned` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `note_alert_laststaff` ,
ADD `note_alert_dept_manager` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `note_alert_assigned` ;

ALTER TABLE `%TABLE_PREFIX%department` ADD `autoresp_email_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `email_id` ;

ALTER TABLE `%TABLE_PREFIX%department` ADD INDEX ( `autoresp_email_id` ) ;

ALTER TABLE `%TABLE_PREFIX%config` CHANGE `default_priority` `default_priority_id` TINYINT( 2 ) UNSIGNED NOT NULL DEFAULT '2';

ALTER TABLE `%TABLE_PREFIX%config` CHANGE `default_template` `default_template_id` TINYINT( 4 ) UNSIGNED NOT NULL DEFAULT '1';

ALTER TABLE `%TABLE_PREFIX%config` CHANGE `default_email` `default_email_id` TINYINT( 4 ) UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `%TABLE_PREFIX%config` CHANGE `default_dept` `default_dept_id` TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `%TABLE_PREFIX%config` CHANGE `enable_pop3_fetch` `enable_mail_fetch` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `%TABLE_PREFIX%config` ADD `alert_email_id` TINYINT( 4 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `overdue_grace_period` ;

ALTER TABLE `%TABLE_PREFIX%config`
ADD `default_smtp_id` TINYINT( 4 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `default_template_id` ,
ADD `spoof_default_smtp` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `default_smtp_id` ;

ALTER TABLE `%TABLE_PREFIX%config` ADD `log_level` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '2' AFTER `random_ticket_ids` ;

ALTER TABLE `%TABLE_PREFIX%config` 
ADD `staff_max_logins` TINYINT UNSIGNED NOT NULL DEFAULT '4' AFTER `enable_daylight_saving` ,
ADD `staff_login_timeout` INT UNSIGNED NOT NULL DEFAULT '2' AFTER `staff_max_logins` ;

ALTER TABLE `%TABLE_PREFIX%config` 
ADD `client_max_logins` TINYINT UNSIGNED NOT NULL DEFAULT '4' AFTER `staff_session_timeout` ,
ADD `client_login_timeout` INT UNSIGNED NOT NULL DEFAULT '2' AFTER `client_max_logins` ;

ALTER TABLE `%TABLE_PREFIX%config` ADD `show_answered_tickets` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `show_assigned_tickets` ;

ALTER TABLE `%TABLE_PREFIX%ticket` ADD `isanswered` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `isoverdue` ;

ALTER TABLE `%TABLE_PREFIX%ticket` ADD `lastmessage` DATETIME NULL AFTER `closed` , ADD `lastresponse` DATETIME NULL AFTER `lastmessage` ;

ALTER TABLE `%TABLE_PREFIX%config` ADD `hide_staff_name` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `show_answered_tickets` ;

ALTER TABLE `%TABLE_PREFIX%config` ADD `log_graceperiod` INT UNSIGNED NOT NULL DEFAULT '12' AFTER `log_level` ;

ALTER TABLE `%TABLE_PREFIX%ticket` ADD `phone_ext` VARCHAR( 8 ) NULL DEFAULT NULL AFTER `phone` ;

ALTER TABLE `%TABLE_PREFIX%ticket` ADD `topic`  VARCHAR(64) NULL DEFAULT NULL AFTER `subject` ;

ALTER TABLE `%TABLE_PREFIX%ticket_message` ADD FULLTEXT (`message`);

ALTER TABLE `%TABLE_PREFIX%ticket_response` ADD FULLTEXT (`response`);

ALTER TABLE `%TABLE_PREFIX%ticket_note` ADD FULLTEXT (`note`);
  
ALTER TABLE `%TABLE_PREFIX%department` ADD `tpl_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `dept_id` ;

ALTER TABLE `%TABLE_PREFIX%department` ADD INDEX ( `tpl_id` ) ;

ALTER TABLE `%TABLE_PREFIX%email_template` ADD `notes` TEXT NULL AFTER `name` ;

ALTER TABLE `%TABLE_PREFIX%config` CHANGE `api_key` `api_passphrase` VARCHAR( 125 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;

DROP TABLE IF EXISTS `%TABLE_PREFIX%api_key`;
CREATE TABLE `%TABLE_PREFIX%api_key` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `isactive` tinyint(1) NOT NULL default '1',
  `ipaddr` varchar(16) NOT NULL,
  `apikey` varchar(255) NOT NULL,
  `updated` datetime NOT NULL default '0000-00-00 00:00:00',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ipaddr` (`ipaddr`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `%TABLE_PREFIX%syslog`;
CREATE TABLE `%TABLE_PREFIX%syslog` (
  `log_id` int(11) unsigned NOT NULL auto_increment,
  `log_type` enum('Debug','Warning','Error') NOT NULL,
  `title` varchar(255) NOT NULL,
  `log` text NOT NULL,
  `logger` varchar(64) NOT NULL,
  `ip_address` varchar(16) NOT NULL,
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `updated` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`log_id`),
  KEY `log_type` (`log_type`)
) ENGINE=MyISAM;

