/**
 * @signature 87d4a3233469728d83b86d3ee2f066e1
 * @version v1.14-rc3
 * @title Add new SMTP Settings to emails
 *
 */
ALTER TABLE `%TABLE_PREFIX%email`
    ADD `smtp_auth_creds` int(11) DEFAULT '0' AFTER `smtp_auth`,
    ADD `smtp_userid` varchar(255) NOT NULL AFTER `smtp_auth_creds`,
    ADD `smtp_userpass` varchar(255) CHARACTER SET ascii NOT NULL AFTER `smtp_userid`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '87d4a3233469728d83b86d3ee2f066e1'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
