-- Drop columns we nolonger need - (must be at the very bottom or after session table is created)
ALTER TABLE `%TABLE_PREFIX%config`
    DROP COLUMN `ostversion`,
    DROP COLUMN `timezone_offset`,
    DROP COLUMN `api_passphrase`;

-- Drop fields we no longer need in staff table.
ALTER TABLE `%TABLE_PREFIX%staff`
    DROP `append_signature`,
    DROP `timezone_offset`;

-- Drop fields we no longer need in department table.
ALTER TABLE `%TABLE_PREFIX%department`
    DROP `can_append_signature`;

-- Banlist table has been migrated to the email_filter_rule table
DROP TABLE `%TABLE_PREFIX%email_banlist`;
