/**
 * @version v1.9.zzz
 * @signature 5b8240ab6023ed7ed266c8aae5e3f7cd
 *
 *  - Add extra SMTP settings
 */


-- Add extra SMTP settings
ALTER TABLE `%TABLE_PREFIX%email` ADD `smtp_userid` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `smtp_port`;
ALTER TABLE `%TABLE_PREFIX%email` ADD `smtp_userpass` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `smtp_userid`;


-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '5b8240ab6023ed7ed266c8aae5e3f7cd'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
