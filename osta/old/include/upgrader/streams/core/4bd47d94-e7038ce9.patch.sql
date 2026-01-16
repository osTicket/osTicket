/**
 * @signature e7038ce9762843b6cfbe9fa2d98feaf3
 * @version v1.15
 * @title Add email_id to thread_entry_email
 *
 * This patch adds an email_id column to the thread_entry_email table
 */
 -- Create a blank temporary table with the new email_id column
 CREATE TABLE `%TABLE_PREFIX%thread_entry_email_new` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `thread_entry_id` int(11) unsigned NOT NULL,
  `email_id` int(11) unsigned DEFAULT NULL,
  `mid` varchar(255) NOT NULL,
  `headers` text,
  PRIMARY KEY (`id`),
  KEY `thread_entry_id` (`thread_entry_id`),
  KEY `mid` (`mid`),
  KEY `email_id` (`email_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

 -- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'e7038ce9762843b6cfbe9fa2d98feaf3'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
