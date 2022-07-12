/**
 * @version v1.17
 * @signature f0d2e14d0d653b856be20ffeff46da32
 * @title Add Modern Email Authenticatin (OAuth2) Support
 *
 */

-- Drop old unused email_account table
DROP TABLE IF EXISTS `%TABLE_PREFIX%email_account`;
-- Add new email_account table
CREATE TABLE `%TABLE_PREFIX%email_account` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email_id` int(11) unsigned NOT NULL,
  `type` enum('mailbox','smtp') NOT NULL DEFAULT 'mailbox',
  `auth_bk` varchar(128) NOT NULL,
  `auth_id` varchar(16) DEFAULT NULL,
  `active` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `host` varchar(128) NOT NULL DEFAULT '',
  `port` int(11) NOT NULL,
  `folder` varchar(255) DEFAULT NULL,
  `protocol` enum('IMAP','POP','SMTP','OTHER') NOT NULL DEFAULT 'OTHER',
  `encryption` enum('NONE','AUTO','SSL') NOT NULL DEFAULT 'AUTO',
  `fetchfreq` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `fetchmax` tinyint(4) unsigned DEFAULT 30,
  `postfetch` enum('archive','delete','nothing') NOT NULL DEFAULT 'nothing',
  `archivefolder` varchar(255) DEFAULT NULL,
  `allow_spoofing` tinyint(1) unsigned DEFAULT 0,
  `username` varchar(128) DEFAULT NULL,
  `passwd` varchar(255) DEFAULT NULL,
  `num_errors` int(11) unsigned NOT NULL DEFAULT 0,
  `last_error_msg` tinytext DEFAULT NULL,
  `last_error` datetime DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `email_id` (`email_id`),
  KEY `type` (`type`)
) DEFAULT CHARSET=utf8;

UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'f0d2e14d0d653b856be20ffeff46da32', updated = NOW()
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
