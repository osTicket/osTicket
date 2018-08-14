/**
* @signature 26fd79dc5443f37779f9d2c4108058f4
* @version v1.11.0
* @title Final Revisions
*
* This patch is for final revisions needed for v1.11
*/
ALTER TABLE `%TABLE_PREFIX%attachment`
    ADD INDEX `file_object` (`file_id`,`object_id`);

UPDATE `%TABLE_PREFIX%role`
    SET `permissions` = REPLACE(`permissions`, '"ticket.transfer":1,', '"ticket.transfer":1,"ticket.refer":1,')
    WHERE `permissions` IS NOT NULL;

UPDATE `%TABLE_PREFIX%role`
    SET `permissions` = REPLACE(`permissions`, '"ticket.assign":1,', '"ticket.assign":1,"ticket.release":1,')
    WHERE `permissions` IS NOT NULL AND `permissions` LIKE '%"ticket.assign":1,%';

-- Ticket Notice Template
UPDATE `%TABLE_PREFIX%email_template`
    SET `body` = REPLACE(`body`, '%{message}', '%{message}<br><br>%{response}')
    WHERE `code_name` = 'ticket.notice';

 -- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '26fd79dc5443f37779f9d2c4108058f4', `updated` = NOW()
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
