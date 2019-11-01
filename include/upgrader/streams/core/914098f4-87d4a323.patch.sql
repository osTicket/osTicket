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

-- Add Default MarkAnswered Permission For Roles w/ Reply Permission
UPDATE `%TABLE_PREFIX%role` A1
    SET A1.`permissions` = REPLACE(A1.`permissions`, '"ticket.reply":1,', '"ticket.reply":1,"ticket.markanswered":1,')
    WHERE A1.`permissions` LIKE '%"ticket.reply":1%';

-- Add Default Domains For Embed Whitelist
INSERT INTO `%TABLE_PREFIX%config` (`id`, `namespace`, `key`, `value`, `updated`) VALUES
    ('', 'core', 'embedded_domain_whitelist', 'youtube.com, dailymotion.com, vimeo.com, player.vimeo.com, web.microsoftstream.com', NOW())
ON DUPLICATE KEY UPDATE
    `value` = 'youtube.com, dailymotion.com, vimeo.com, player.vimeo.com, web.microsoftstream.com', `updated` = NOW();

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '87d4a3233469728d83b86d3ee2f066e1'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
