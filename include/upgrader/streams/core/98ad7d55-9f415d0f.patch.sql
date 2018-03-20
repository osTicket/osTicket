/**
 * @version v1.10.0+
 * @signature 9f415d0f424fdf609c76a8ad4fffefae
 * @title Increase staff username field to 64
 *
 * Increases the ost_staff table username field length to 64
 */

ALTER TABLE `%TABLE_PREFIX%staff` MODIFY `username` VARCHAR(64);

UPDATE `%TABLE_PREFIX%config`
    SET `value` = '9f415d0f424fdf609c76a8ad4fffefae'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
