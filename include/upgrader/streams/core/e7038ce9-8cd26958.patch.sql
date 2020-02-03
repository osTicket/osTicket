/**
 * @signature 8cd26958b50da5f037d722a172cb8e1f
 * @version v1.15
 * @title Add indexes accumulated over time
 *
 * This patch adds all indexes we've implemented over time
 */

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '8cd26958b50da5f037d722a172cb8e1f'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
