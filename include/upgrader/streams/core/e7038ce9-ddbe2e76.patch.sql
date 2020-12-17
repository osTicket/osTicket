/**
 * @signature ddbe2e76ec38a2e58bdbff9109c07930
 * @version v1.15
 * @title Add indexes accumulated over time
 *
 * This patch adds all indexes we've implemented over time and adds the
 * `version` column to the plugin table (if not exists)
 */

-- Add FLAG_AGENT_REQUIRED For Task Title
UPDATE `%TABLE_PREFIX%form_field`
SET `flags` = IF((`flags` & 16384) != 16384, `flags` | 16384, `flags`)
WHERE `form_id` = (SELECT `id` FROM `%TABLE_PREFIX%form` WHERE `type` = 'A') AND `name` = 'title';

-- Add FLAG_AGENT_VIEW for Task Title/Description
UPDATE `%TABLE_PREFIX%form_field`
SET `flags` = IF((`flags` & 4096) != 4096, `flags` | 4096, `flags`)
WHERE `form_id` = (SELECT `id` FROM `%TABLE_PREFIX%form` WHERE `type` = 'A') AND `name` IN ('title', 'description');

-- Add FLAG_MASK_REQUIRE for Task Title
UPDATE `%TABLE_PREFIX%form_field`
SET `flags` = IF((`flags` & 65536) != 65536, `flags` | 65536, `flags`)
WHERE `form_id` = (SELECT `id` FROM `%TABLE_PREFIX%form` WHERE `type` = 'A') AND `name` = 'title';

-- Add FLAG_MASK_VIEW for Task Title/Description
UPDATE `%TABLE_PREFIX%form_field`
SET `flags` = IF((`flags` & 131072) != 131072, `flags` | 131072, `flags`)
WHERE `form_id` = (SELECT `id` FROM `%TABLE_PREFIX%form` WHERE `type` = 'A') AND `name` IN ('title', 'description');

-- Add `version` column to the plugin table (if not exists)
SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = '%TABLE_PREFIX%plugin'
        AND table_schema = DATABASE()
        AND column_name = 'version'
    ) > 0,
    "SELECT 1",
    "ALTER TABLE `%TABLE_PREFIX%plugin` ADD `version` VARCHAR(64) DEFAULT NULL AFTER `isactive`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'ddbe2e76ec38a2e58bdbff9109c07930'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
