-- Drop `tid` from thread (if it exists)
SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = '%TABLE_PREFIX%thread'
        AND table_schema = DATABASE()
        AND column_name = 'tid'
    ) > 0,
    "ALTER TABLE `%TABLE_PREFIX%thread` DROP COLUMN `tid`",
    "SELECT 1"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

DROP TABLE `%TABLE_PREFIX%ticket_attachment`;

OPTIMIZE TABLE `%TABLE_PREFIX%ticket`;
OPTIMIZE TABLE `%TABLE_PREFIX%thread`;
