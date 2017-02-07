
DROP TABLE IF EXISTS `%TABLE_PREFIX%api_key`;
CREATE TABLE `%TABLE_PREFIX%api_key` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `isactive` tinyint(1) NOT NULL default '1',
  `ipaddr` varchar(64) NOT NULL,
  `apikey` varchar(255) NOT NULL,
  `can_create_tickets` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '1',
  `can_exec_cron` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '1',
  `notes` text,
  `updated` datetime NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `ipaddr` (`ipaddr`),
  UNIQUE KEY `apikey` (`apikey`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%attachment`;
CREATE TABLE `%TABLE_PREFIX%attachment` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `object_id` int(11) unsigned NOT NULL,
  `type` char(1) NOT NULL,
  `file_id` int(11) unsigned NOT NULL,
  `name` varchar(255) NULL default NULL,
  `inline` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `lang` varchar(16),
  PRIMARY KEY  (`id`),
  UNIQUE KEY `file-type` (`object_id`,`file_id`,`type`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%faq`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%faq` (
  `faq_id` int(10) unsigned NOT NULL auto_increment,
  `category_id` int(10) unsigned NOT NULL default '0',
  `ispublished` tinyint(1) unsigned NOT NULL default '0',
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `keywords` tinytext,
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`faq_id`),
  UNIQUE KEY `question` (`question`),
  KEY `category_id` (`category_id`),
  KEY `ispublished` (`ispublished`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%faq_category`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%faq_category` (
  `category_id` int(10) unsigned NOT NULL auto_increment,
  `ispublic` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(125) default NULL,
  `description` TEXT NOT NULL,
  `notes` tinytext NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`category_id`),
  KEY (`ispublic`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%faq_topic`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%faq_topic` (
  `faq_id` int(10) unsigned NOT NULL,
  `topic_id` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`faq_id`,`topic_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%sequence`;
CREATE TABLE `%TABLE_PREFIX%sequence` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `flags` int(10) unsigned DEFAULT NULL,
  `next` bigint(20) unsigned NOT NULL DEFAULT '1',
  `increment` int(11) DEFAULT '1',
  `padding` char(1) DEFAULT '0',
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
-- InnoDB is intended here because transaction support is required for row
-- locking
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%sla`;
CREATE TABLE `%TABLE_PREFIX%sla` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `flags` int(10) unsigned NOT NULL default 3,
  `grace_period` int(10) unsigned NOT NULL default '0',
  `name` varchar(64) NOT NULL default '',
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%config`;
CREATE TABLE `%TABLE_PREFIX%config` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `namespace` varchar(64) NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY (`namespace`, `key`)
) DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%config` (`namespace`, `key`, `value`) VALUES
  ('core', 'admin_email', ''),
  ('core', 'helpdesk_url', ''),
  ('core', 'helpdesk_title', ''),
  ('core', 'schema_signature', '');

DROP TABLE IF EXISTS `%TABLE_PREFIX%form`;
CREATE TABLE `%TABLE_PREFIX%form` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `pid` int(10) unsigned DEFAULT NULL,
    `type` varchar(8) NOT NULL DEFAULT 'G',
    `flags` int(10) unsigned NOT NULL DEFAULT 1,
    `title` varchar(255) NOT NULL,
    `instructions` varchar(512),
    `name` varchar(64) NOT NULL DEFAULT '',
    `notes` text,
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%form_field`;
CREATE TABLE `%TABLE_PREFIX%form_field` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `form_id` int(11) unsigned NOT NULL,
    `flags` int(10) unsigned DEFAULT 1,
    `type` varchar(255) NOT NULL DEFAULT 'text',
    `label` varchar(255) NOT NULL,
    `name` varchar(64) NOT NULL,
    `configuration` text,
    `sort` int(11) unsigned NOT NULL,
    `hint` varchar(512),
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%form_entry`;
CREATE TABLE `%TABLE_PREFIX%form_entry` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `form_id` int(11) unsigned NOT NULL,
    `object_id` int(11) unsigned,
    `object_type` char(1) NOT NULL DEFAULT 'T',
    `sort` int(11) unsigned NOT NULL DEFAULT 1,
    `extra` text,
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `entry_lookup` (`object_type`, `object_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%form_entry_values`;
CREATE TABLE `%TABLE_PREFIX%form_entry_values` (
    -- references form_entry.id
    `entry_id` int(11) unsigned NOT NULL,
    `field_id` int(11) unsigned NOT NULL,
    `value` text,
    `value_id` int(11),
    PRIMARY KEY (`entry_id`, `field_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%list`;
CREATE TABLE `%TABLE_PREFIX%list` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `name_plural` varchar(255),
    `sort_mode` enum('Alpha', '-Alpha', 'SortCol') NOT NULL DEFAULT 'Alpha',
    `masks` int(11) unsigned NOT NULL DEFAULT 0,
    `type` VARCHAR( 16 ) NULL DEFAULT NULL,
    `configuration` text NOT NULL DEFAULT '',
    `notes` text,
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `type` (`type`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%list_items`;
CREATE TABLE `%TABLE_PREFIX%list_items` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `list_id` int(11),
    `status` int(11) unsigned NOT NULL DEFAULT 1,
    `value` varchar(255) NOT NULL,
    -- extra value such as abbreviation
    `extra` varchar(255),
    `sort` int(11) NOT NULL DEFAULT 1,
    `properties` text,
    PRIMARY KEY (`id`),
    KEY `list_item_lookup` (`list_id`)
) DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `%TABLE_PREFIX%department`;
CREATE TABLE `%TABLE_PREFIX%department` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `pid` int(11) unsigned default NULL,
  `tpl_id` int(10) unsigned NOT NULL default '0',
  `sla_id` int(10) unsigned NOT NULL default '0',
  `email_id` int(10) unsigned NOT NULL default '0',
  `autoresp_email_id` int(10) unsigned NOT NULL default '0',
  `manager_id` int(10) unsigned NOT NULL default '0',
  `flags` int(10) unsigned NOT NULL default 0,
  `name` varchar(128) NOT NULL default '',
  `signature` text NOT NULL,
  `ispublic` tinyint(1) unsigned NOT NULL default '1',
  `group_membership` tinyint(1) NOT NULL default '0',
  `ticket_auto_response` tinyint(1) NOT NULL default '1',
  `message_auto_response` tinyint(1) NOT NULL default '0',
  `path` varchar(128) NOT NULL default '/',
  `updated` datetime NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`, `pid`),
  KEY `manager_id` (`manager_id`),
  KEY `autoresp_email_id` (`autoresp_email_id`),
  KEY `tpl_id` (`tpl_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%draft`;
CREATE TABLE `%TABLE_PREFIX%draft` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) unsigned NOT NULL,
  `namespace` varchar(32) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `extra` text,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%email`;
CREATE TABLE `%TABLE_PREFIX%email` (
  `email_id` int(11) unsigned NOT NULL auto_increment,
  `noautoresp` tinyint(1) unsigned NOT NULL default '0',
  `priority_id` tinyint(3) unsigned NOT NULL default '2',
  `dept_id` tinyint(3) unsigned NOT NULL default '0',
  `topic_id` int(11) unsigned NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `userid` varchar(255) NOT NULL,
  `userpass` varchar(255) collate ascii_general_ci NOT NULL,
  `mail_active` tinyint(1) NOT NULL default '0',
  `mail_host` varchar(255) NOT NULL,
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
  `smtp_host` varchar(255) NOT NULL,
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
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%email_account`;
CREATE TABLE `%TABLE_PREFIX%email_account` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `protocol` varchar(64) NOT NULL DEFAULT '',
  `host` varchar(128) NOT NULL DEFAULT '',
  `port` int(11) NOT NULL,
  `username` varchar(128) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `options` varchar(512) DEFAULT NULL,
  `errors` int(11) unsigned DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `lastconnect` timestamp NULL DEFAULT NULL,
  `lasterror` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%filter`;
CREATE TABLE `%TABLE_PREFIX%filter` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `execorder` int(10) unsigned NOT NULL default '99',
  `isactive` tinyint(1) unsigned NOT NULL default '1',
  `status` int(11) unsigned NOT NULL DEFAULT '0',
  `match_all_rules` tinyint(1) unsigned NOT NULL default '0',
  `stop_onmatch` tinyint(1) unsigned NOT NULL default '0',
  `target` ENUM(  'Any',  'Web',  'Email',  'API' ) NOT NULL DEFAULT  'Any',
  `email_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `target` (`target`),
  KEY `email_id` (`email_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%filter_action`;
CREATE TABLE `%TABLE_PREFIX%filter_action` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `filter_id` int(10) unsigned NOT NULL,
  `sort` int(10) unsigned NOT NULL default 0,
  `type` varchar(24) NOT NULL,
  `configuration` text,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `filter_id` (`filter_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%filter_rule`;
CREATE TABLE `%TABLE_PREFIX%filter_rule` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `filter_id` int(10) unsigned NOT NULL default '0',
  `what` varchar(32) NOT NULL,
  `how` enum('equal','not_equal','contains','dn_contain','starts','ends','match','not_match') NOT NULL,
  `val` varchar(255) NOT NULL,
  `isactive` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `notes` tinytext NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `filter_id` (`filter_id`),
  UNIQUE `filter` (`filter_id`, `what`, `how`, `val`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%email_template_group`;
CREATE TABLE `%TABLE_PREFIX%email_template_group` (
  `tpl_id` int(11) NOT NULL auto_increment,
  `isactive` tinyint(1) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  `lang` varchar(16) NOT NULL default 'en_US',
  `notes` text,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL,
  PRIMARY KEY  (`tpl_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%email_template`;
CREATE TABLE `%TABLE_PREFIX%email_template` (
  `id` int(11) UNSIGNED NOT NULL auto_increment,
  `tpl_id` int(11) UNSIGNED NOT NULL,
  `code_name` varchar(32) NOT NULL,
  `subject` varchar(255) NOT NULL default '',
  `body` text NOT NULL,
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `template_lookup` (`tpl_id`, `code_name`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%file`;
CREATE TABLE `%TABLE_PREFIX%file` (
  `id` int(11) NOT NULL auto_increment,
  `ft` CHAR( 1 ) NOT NULL DEFAULT  'T',
  `bk` CHAR( 1 ) NOT NULL DEFAULT  'D',
  -- RFC 4288, Section 4.2 declares max MIMEType at 255 ascii chars
  `type` varchar(255) collate ascii_general_ci NOT NULL default '',
  `size` bigint(20) unsigned NOT NULL default 0,
  `key` varchar(86) collate ascii_general_ci NOT NULL,
  `signature` varchar(86) collate ascii_bin NOT NULL,
  `name` varchar(255) NOT NULL default '',
  `attrs` varchar(255),
  `created` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `ft` (`ft`),
  KEY `key` (`key`),
  KEY `signature` (`signature`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%file_chunk`;
CREATE TABLE `%TABLE_PREFIX%file_chunk` (
  `file_id` int(11) NOT NULL,
  `chunk_id` int(11) NOT NULL,
  `filedata` longblob NOT NULL,
  PRIMARY KEY (`file_id`, `chunk_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%group`;
CREATE TABLE `%TABLE_PREFIX%group` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `role_id` int(11) unsigned NOT NULL,
  `flags` int(11) unsigned NOT NULL default '1',
  `name` varchar(120) NOT NULL default '',
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `role_id` (`role_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%role`;
CREATE TABLE `%TABLE_PREFIX%role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `flags` int(10) unsigned NOT NULL DEFAULT '1',
  `name` varchar(64) DEFAULT NULL,
  `permissions` text,
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%help_topic`;
CREATE TABLE `%TABLE_PREFIX%help_topic` (
  `topic_id` int(11) unsigned NOT NULL auto_increment,
  `topic_pid` int(10) unsigned NOT NULL default '0',
  `isactive` tinyint(1) unsigned NOT NULL default '1',
  `ispublic` tinyint(1) unsigned NOT NULL default '1',
  `noautoresp` tinyint(3) unsigned NOT NULL default '0',
  `flags` int(10) unsigned DEFAULT '0',
  `status_id` int(10) unsigned NOT NULL default '0',
  `priority_id` int(10) unsigned NOT NULL default '0',
  `dept_id` int(10) unsigned NOT NULL default '0',
  `staff_id` int(10) unsigned NOT NULL default '0',
  `team_id` int(10) unsigned NOT NULL default '0',
  `sla_id` int(10) unsigned NOT NULL default '0',
  `page_id` int(10) unsigned NOT NULL default '0',
  `sequence_id` int(10) unsigned NOT NULL DEFAULT '0',
  `sort` int(10) unsigned NOT NULL default '0',
  `topic` varchar(32) NOT NULL default '',
  `number_format` varchar(32) DEFAULT NULL,
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`topic_id`),
  UNIQUE KEY `topic` ( `topic` ,  `topic_pid` ),
  KEY `topic_pid` (`topic_pid`),
  KEY `priority_id` (`priority_id`),
  KEY `dept_id` (`dept_id`),
  KEY `staff_id` (`staff_id`,`team_id`),
  KEY `sla_id` (`sla_id`),
  KEY `page_id` (`page_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%help_topic_form`;
CREATE TABLE `%TABLE_PREFIX%help_topic_form` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `topic_id` int(11) unsigned NOT NULL default 0,
  `form_id` int(10) unsigned NOT NULL default 0,
  `sort` int(10) unsigned NOT NULL default 1,
  `extra` text,
  PRIMARY KEY (`id`),
  KEY `topic-form` (`topic_id`, `form_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%organization`;
CREATE TABLE `%TABLE_PREFIX%organization` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `manager` varchar(16) NOT NULL DEFAULT '',
  `status` int(11) unsigned NOT NULL DEFAULT '0',
  `domain` varchar(256) NOT NULL DEFAULT '',
  `extra` text,
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%canned_response`;
CREATE TABLE `%TABLE_PREFIX%canned_response` (
  `canned_id` int(10) unsigned NOT NULL auto_increment,
  `dept_id` int(10) unsigned NOT NULL default '0',
  `isenabled` tinyint(1) unsigned NOT NULL default '1',
  `title` varchar(255) NOT NULL default '',
  `response` text NOT NULL,
  `lang` varchar(16) NOT NULL default 'en_US',
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`canned_id`),
  UNIQUE KEY `title` (`title`),
  KEY `dept_id` (`dept_id`),
  KEY `active` (`isenabled`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%note`;
CREATE TABLE `%TABLE_PREFIX%note` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned,
  `staff_id` int(11) unsigned NOT NULL DEFAULT 0,
  `ext_id` varchar(10),
  `body` text,
  `status` int(11) unsigned NOT NULL DEFAULT 0,
  `sort` int(11) unsigned NOT NULL DEFAULT 0,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ext_id` (`ext_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%session`;
CREATE TABLE `%TABLE_PREFIX%session` (
  `session_id` varchar(255) collate ascii_general_ci NOT NULL default '',
  `session_data` blob,
  `session_expire` datetime default NULL,
  `session_updated` datetime default NULL,
  `user_id` varchar(16) NOT NULL default '0' COMMENT 'osTicket staff/client ID',
  `user_ip` varchar(64) NOT NULL,
  `user_agent` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`session_id`),
  KEY `updated` (`session_updated`),
  KEY `user_id` (`user_id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `%TABLE_PREFIX%staff`;
CREATE TABLE `%TABLE_PREFIX%staff` (
  `staff_id` int(11) unsigned NOT NULL auto_increment,
  `dept_id` int(10) unsigned NOT NULL default '0',
  `role_id` int(10) unsigned NOT NULL default '0',
  `username` varchar(32) NOT NULL default '',
  `firstname` varchar(32) default NULL,
  `lastname` varchar(32) default NULL,
  `passwd` varchar(128) default NULL,
  `backend` varchar(32) default NULL,
  `email` varchar(128) default NULL,
  `phone` varchar(24) NOT NULL default '',
  `phone_ext` varchar(6) default NULL,
  `mobile` varchar(24) NOT NULL default '',
  `signature` text NOT NULL,
  `lang` varchar(16) DEFAULT NULL,
  `timezone` varchar(64) default NULL,
  `locale` varchar(16) DEFAULT NULL,
  `notes` text,
  `isactive` tinyint(1) NOT NULL default '1',
  `isadmin` tinyint(1) NOT NULL default '0',
  `isvisible` tinyint(1) unsigned NOT NULL default '1',
  `onvacation` tinyint(1) unsigned NOT NULL default '0',
  `assigned_only` tinyint(1) unsigned NOT NULL default '0',
  `show_assigned_tickets` tinyint(1) unsigned NOT NULL default '0',
  `change_passwd` tinyint(1) unsigned NOT NULL default '0',
  `max_page_size` int(11) unsigned NOT NULL default '0',
  `auto_refresh_rate` int(10) unsigned NOT NULL default '0',
  `default_signature_type` ENUM( 'none', 'mine', 'dept' ) NOT NULL DEFAULT 'none',
  `default_paper_size` ENUM( 'Letter', 'Legal', 'Ledger', 'A4', 'A3' ) NOT NULL DEFAULT 'Letter',
  `extra` text,
  `permissions` text,
  `created` datetime NOT NULL,
  `lastlogin` datetime default NULL,
  `passwdreset` datetime default NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`staff_id`),
  UNIQUE KEY `username` (`username`),
  KEY `dept_id` (`dept_id`),
  KEY `issuperuser` (`isadmin`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%staff_dept_access`;
CREATE TABLE `%TABLE_PREFIX%staff_dept_access` (
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0',
  `dept_id` int(10) unsigned NOT NULL DEFAULT '0',
  `role_id` int(10) unsigned NOT NULL DEFAULT '0',
  `flags` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY `staff_dept` (`staff_id`,`dept_id`),
  KEY `dept_id` (`dept_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%syslog`;
CREATE TABLE `%TABLE_PREFIX%syslog` (
  `log_id` int(11) unsigned NOT NULL auto_increment,
  `log_type` enum('Debug','Warning','Error') NOT NULL,
  `title` varchar(255) NOT NULL,
  `log` text NOT NULL,
  `logger` varchar(64) NOT NULL,
  `ip_address` varchar(64) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`log_id`),
  KEY `log_type` (`log_type`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%team`;
CREATE TABLE `%TABLE_PREFIX%team` (
  `team_id` int(10) unsigned NOT NULL auto_increment,
  `lead_id` int(10) unsigned NOT NULL default '0',
  `flags` int(10) unsigned NOT NULL default 1,
  `name` varchar(125) NOT NULL default '',
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`team_id`),
  UNIQUE KEY `name` (`name`),
  KEY `lead_id` (`lead_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%team_member`;
CREATE TABLE `%TABLE_PREFIX%team_member` (
  `team_id` int(10) unsigned NOT NULL default '0',
  `staff_id` int(10) unsigned NOT NULL,
  `flags` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY  (`team_id`,`staff_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%thread`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%thread` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int(11) unsigned NOT NULL,
  `object_type` char(1) NOT NULL,
  `extra` text,
  `lastresponse` datetime DEFAULT NULL,
  `lastmessage` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `object_id` (`object_id`),
  KEY `object_type` (`object_type`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%thread_entry`;
CREATE TABLE `%TABLE_PREFIX%thread_entry` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `pid` int(11) unsigned NOT NULL default '0',
  `thread_id` int(11) unsigned NOT NULL default '0',
  `staff_id` int(11) unsigned NOT NULL default '0',
  `user_id` int(11) unsigned not null default 0,
  `type` char(1) NOT NULL default '',
  `flags` int(11) unsigned NOT NULL default '0',
  `poster` varchar(128) NOT NULL default '',
  `editor` int(10) unsigned NULL,
  `editor_type` char(1) NULL,
  `source` varchar(32) NOT NULL default '',
  `title` varchar(255),
  `body` text NOT NULL,
  `format` varchar(16) NOT NULL default 'html',
  `ip_address` varchar(64) NOT NULL default '',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `pid` (`pid`),
  KEY `thread_id` (`thread_id`),
  KEY `staff_id` (`staff_id`),
  KEY `type` (`type`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%thread_entry_email`;
CREATE TABLE `%TABLE_PREFIX%thread_entry_email` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `thread_entry_id` int(11) unsigned NOT NULL,
  `mid` varchar(255) NOT NULL,
  `headers` text,
  PRIMARY KEY (`id`),
  KEY `thread_entry_id` (`thread_entry_id`),
  KEY `mid` (`mid`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket`;
CREATE TABLE `%TABLE_PREFIX%ticket` (
  `ticket_id` int(11) unsigned NOT NULL auto_increment,
  `number` varchar(20),
  `user_id` int(11) unsigned NOT NULL default '0',
  `user_email_id` int(11) unsigned NOT NULL default '0',
  `status_id` int(10) unsigned NOT NULL default '0',
  `dept_id` int(10) unsigned NOT NULL default '0',
  `sla_id` int(10) unsigned NOT NULL default '0',
  `topic_id` int(10) unsigned NOT NULL default '0',
  `staff_id` int(10) unsigned NOT NULL default '0',
  `team_id` int(10) unsigned NOT NULL default '0',
  `email_id` int(11) unsigned NOT NULL default '0',
  `lock_id` int(11) unsigned NOT NULL default '0',
  `flags` int(10) unsigned NOT NULL default '0',
  `ip_address` varchar(64) NOT NULL default '',
  `source` enum('Web','Email','Phone','API','Other') NOT NULL default 'Other',
  `source_extra` varchar(40) NULL default NULL,
  `isoverdue` tinyint(1) unsigned NOT NULL default '0',
  `isanswered` tinyint(1) unsigned NOT NULL default '0',
  `duedate` datetime default NULL,
  `est_duedate` datetime default NULL,
  `reopened` datetime default NULL,
  `closed` datetime default NULL,
  `lastupdate` datetime default NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`ticket_id`),
  KEY `user_id` (`user_id`),
  KEY `dept_id` (`dept_id`),
  KEY `staff_id` (`staff_id`),
  KEY `team_id` (`team_id`),
  KEY `status_id` (`status_id`),
  KEY `created` (`created`),
  KEY `closed` (`closed`),
  KEY `duedate` (`duedate`),
  KEY `topic_id` (`topic_id`),
  KEY `sla_id` (`sla_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%lock`;
CREATE TABLE `%TABLE_PREFIX%lock` (
  `lock_id` int(11) unsigned NOT NULL auto_increment,
  `staff_id` int(10) unsigned NOT NULL default '0',
  `expire` datetime default NULL,
  `code` varchar(20),
  `created` datetime NOT NULL,
  PRIMARY KEY  (`lock_id`),
  KEY `staff_id` (`staff_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%thread_event`;
CREATE TABLE `%TABLE_PREFIX%thread_event` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int(11) unsigned NOT NULL default '0',
  `staff_id` int(11) unsigned NOT NULL,
  `team_id` int(11) unsigned NOT NULL,
  `dept_id` int(11) unsigned NOT NULL,
  `topic_id` int(11) unsigned NOT NULL,
  `state` enum('created','closed','reopened','assigned','transferred','overdue','edited','viewed','error','collab','resent') NOT NULL,
  `data` varchar(1024) DEFAULT NULL COMMENT 'Encoded differences',
  `username` varchar(128) NOT NULL default 'SYSTEM',
  `uid` int(11) unsigned DEFAULT NULL,
  `uid_type` char(1) NOT NULL DEFAULT 'S',
  `annulled` tinyint(1) unsigned NOT NULL default '0',
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_state` (`thread_id`, `state`, `timestamp`),
  KEY `ticket_stats` (`timestamp`, `state`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket_status`;
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%ticket_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL DEFAULT '',
  `state` varchar(16) DEFAULT NULL,
  `mode` int(11) unsigned NOT NULL DEFAULT '0',
  `flags` int(11) unsigned NOT NULL DEFAULT '0',
  `sort` int(11) unsigned NOT NULL DEFAULT '0',
  `properties` text NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `state` (`state`)
) DEFAULT CHARSET=utf8;


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
) DEFAULT CHARSET=utf8;

CREATE TABLE `%TABLE_PREFIX%thread_collaborator` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `isactive` tinyint(1) NOT NULL DEFAULT '1',
  `thread_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  -- M => (message) clients, N => (note) 3rd-Party, R => (reply) external authority
  `role` char(1) NOT NULL DEFAULT 'M',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `collab` (`thread_id`,`user_id`),
  KEY `user_id` (`user_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%task`;
CREATE TABLE `%TABLE_PREFIX%task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int(11) NOT NULL DEFAULT '0',
  `object_type` char(1) NOT NULL,
  `number` varchar(20) DEFAULT NULL,
  `dept_id` int(10) unsigned NOT NULL DEFAULT '0',
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0',
  `team_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lock_id` int(11) unsigned NOT NULL DEFAULT '0',
  `flags` int(10) unsigned NOT NULL DEFAULT '0',
  `duedate` datetime DEFAULT NULL,
  `closed` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `dept_id` (`dept_id`),
  KEY `staff_id` (`staff_id`),
  KEY `team_id` (`team_id`),
  KEY `created` (`created`),
  KEY `object` (`object_id`,`object_type`)
) DEFAULT CHARSET=utf8;

-- pages
CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%content` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `isactive` tinyint(1) unsigned NOT NULL default '0',
  `type` varchar(32) NOT NULL default 'other',
  `name` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) DEFAULT CHARSET=utf8;

-- Plugins
DROP TABLE IF EXISTS `%TABLE_PREFIX%plugin`;
CREATE TABLE `%TABLE_PREFIX%plugin` (
  `id` int(11) unsigned not null auto_increment,
  `name` varchar(30) not null,
  `install_path` varchar(60) not null,
  `isphar` tinyint(1) not null default 0,
  `isactive` tinyint(1) not null default 0,
  `version` varchar(64),
  `installed` datetime not null,
  primary key (`id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%queue`;
CREATE TABLE `%TABLE_PREFIX%queue` (
  `id` int(11) unsigned not null auto_increment,
  `parent_id` int(11) unsigned not null default 0,
  `flags` int(11) unsigned not null default 0,
  `staff_id` int(11) unsigned not null default 0,
  `sort` int(11) unsigned not null default 0,
  `title` varchar(60),
  `config` text,
  `created` datetime not null,
  `updated` datetime not null,
  primary key (`id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%translation`;
CREATE TABLE `%TABLE_PREFIX%translation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `object_hash` char(16) CHARACTER SET ascii DEFAULT NULL,
  `type` enum('phrase','article','override') DEFAULT NULL,
  `flags` int(10) unsigned NOT NULL DEFAULT '0',
  `revision` int(11) unsigned DEFAULT NULL,
  `agent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lang` varchar(16) NOT NULL DEFAULT '',
  `text` mediumtext NOT NULL,
  `source_text` text,
  `updated` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `type` (`type`,`lang`),
  KEY `object_hash` (`object_hash`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%user`;
CREATE TABLE `%TABLE_PREFIX%user` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `org_id` int(10) unsigned NOT NULL,
  `default_email_id` int(10) NOT NULL,
  `status` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `org_id` (`org_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%user_email`;
CREATE TABLE `%TABLE_PREFIX%user_email` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL,
  `flags` int(10) unsigned NOT NULL DEFAULT 0,
  `address` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `address` (`address`),
  KEY `user_email_lookup` (`user_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%user_account`;
CREATE TABLE `%TABLE_PREFIX%user_account` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `status` int(11) unsigned NOT NULL DEFAULT '0',
  `timezone` varchar(64) DEFAULT NULL,
  `lang` varchar(16) DEFAULT NULL,
  `username` varchar(64) DEFAULT NULL,
  `passwd` varchar(128) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `backend` varchar(32) DEFAULT NULL,
  `extra` text,
  `registered` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  UNIQUE KEY `username` (`username`)
) DEFAULT CHARSET=utf8;
