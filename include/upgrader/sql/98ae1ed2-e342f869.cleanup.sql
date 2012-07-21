ALTER TABLE `%TABLE_PREFIX%department` DROP `noreply_autoresp`;

ALTER TABLE `%TABLE_PREFIX%config`
    DROP `noreply_email`,
    DROP `alert_email`,
    DROP `api_whitelist`;

-- %email_pop3 migrated to %email table
TRUNCATE TABLE `%TABLE_PREFIX%email_pop3`;
DROP TABLE `%TABLE_PREFIX%email_pop3`;
