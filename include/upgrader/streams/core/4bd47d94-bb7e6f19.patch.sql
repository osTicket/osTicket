/**
 * @signature bb7e6f19e3c13ae3dc12dca13f46b4f1
 * @version v1.14.3
 * @title Two Factor Authentication
 *
 * This patch adds a 2FA Backend column to the Staff table
 */

-- Add 2FA Backend Column
ALTER TABLE `%TABLE_PREFIX%staff`
    ADD `backend2fa` varchar(128) default NULL AFTER `backend`;

 -- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'bb7e6f19e3c13ae3dc12dca13f46b4f1'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
