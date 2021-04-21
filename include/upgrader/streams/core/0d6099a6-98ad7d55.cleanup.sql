/**
 * @signature 4e9f2e2441e82ba393df94647a1ec9ea
 * @version v1.10.0
 * @title Access Control 2.0
 *
 */

DROP TABLE IF EXISTS `%TABLE_PREFIX%group_dept_access`;
DROP TABLE IF EXISTS `%TABLE_PREFIX%group`;

-- Drop `updated` if it exists (it stayed in the install script after it was
-- removed from the update path
SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = '%TABLE_PREFIX%team_member'
        AND table_schema = DATABASE()
        AND column_name = 'updated'
    ) > 0,
    "ALTER TABLE `%TABLE_PREFIX%team_member` DROP `updated`",
    "SELECT 1"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

-- Drop `views` and `score` from 1ee831c8 as it cannot handle translations
SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = '%TABLE_PREFIX%faq'
        AND table_schema = DATABASE()
        AND column_name = 'views'
    ) > 0,
    "ALTER TABLE `%TABLE_PREFIX%faq` DROP `views`, DROP `score`",
    "SELECT 1"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

ALTER TABLE `%TABLE_PREFIX%ticket` DROP `lastmessage`, DROP `lastresponse`;
