/**
 * @signature 8cd26958b50da5f037d722a172cb8e1f
 * @version v1.15
 * @title Add indexes accumulated over time
 *
 * This patch adds all indexes we've implemented over time
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

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '8cd26958b50da5f037d722a172cb8e1f'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
