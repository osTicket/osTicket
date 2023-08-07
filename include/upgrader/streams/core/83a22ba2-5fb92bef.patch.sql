/**
 * @version v1.18
 * @signature 5fb92bef17f3b603659e024c01cc7a59
 * @title Modify Plugin / Instance name to varchar(255)
 *
 */

ALTER TABLE `%TABLE_PREFIX%plugin`
    MODIFY COLUMN `name` VARCHAR(255);

ALTER TABLE `%TABLE_PREFIX%plugin_instance`
    MODIFY COLUMN `name` VARCHAR(255);

UPDATE `%TABLE_PREFIX%config`
    SET `value` = '5fb92bef17f3b603659e024c01cc7a59', updated = NOW()
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
