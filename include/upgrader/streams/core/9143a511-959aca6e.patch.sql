/**
 * @signature 959aca6ed189cd918d227a3ea8a135a3
 * @version v1.9.6
 * @title Retire `private`, `required`, and `edit_mask` for fields
 *
 */

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '959aca6ed189cd918d227a3ea8a135a3'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
