/**
 * @version v1.8.1
 * @signature f1ccd3bb620e314b0ae1dbd0a1a99177
 * @title Pluggable Storage Backends
 *
 * This patch will allow attachments to be stored outside the database (like
 * on the filesystem)
 */

ALTER TABLE `%TABLE_PREFIX%file`
    -- RFC 4288, Section 4.2 declares max MIMEType at 255 ascii chars
    CHANGE `type` `type` varchar(255) collate ascii_general_ci NOT NULL default '',
    CHANGE `size` `size` BIGINT(20) NOT NULL DEFAULT 0,
    CHANGE `hash` `key` VARCHAR(86) COLLATE ascii_general_ci,
    ADD `signature` VARCHAR(86) COLLATE ascii_bin AFTER `key`,
    ADD INDEX (`signature`);

-- dd0022fb14892c0bb6a9700392df2de7 added `bk` and `attrs` to facilitate
-- upgrading from osTicket 1.6 without loading files into the database
SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = '%TABLE_PREFIX%file'
        AND table_schema = DATABASE()
        AND column_name = 'bk'
    ) > 0,
    "SELECT 1",
    "ALTER TABLE `%TABLE_PREFIX%file`
        ADD `bk` CHAR(1) NOT NULL DEFAULT 'D' AFTER `ft`,
        ADD `attrs` VARCHAR(255) AFTER `name`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'f1ccd3bb620e314b0ae1dbd0a1a99177'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
