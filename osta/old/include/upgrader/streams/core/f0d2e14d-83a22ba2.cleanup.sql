-- remove username and passwd fields
ALTER TABLE `%TABLE_PREFIX%email_account`
    DROP COLUMN `username`,
    DROP COLUMN `passwd`;
-- remove plugin config field
ALTER TABLE `%TABLE_PREFIX%plugin_instance`
    DROP COLUMN `config`;
