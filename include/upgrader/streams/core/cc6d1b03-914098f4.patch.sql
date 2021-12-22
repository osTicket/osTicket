/**
 * @signature 914098f4a7022c558038471e7f9eec62
 * @version v1.14-rc3
 * @title Add mail folder to emails
 *
 */
ALTER TABLE `%TABLE_PREFIX%email` ADD `mail_folder` varchar(255) DEFAULT NULL AFTER `mail_encryption`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '914098f4a7022c558038471e7f9eec62'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
