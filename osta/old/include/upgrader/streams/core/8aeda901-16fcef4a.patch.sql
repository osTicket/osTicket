

/**
 * @version v1.7.1
 * @signature 16fcef4a13d6475a5f8bfef462b548e2
 *
 *  Change email password field to varchar 255  ASCII
 *
 *
 */

ALTER TABLE  `%TABLE_PREFIX%email`
    CHANGE  `userpass`  `userpass` VARCHAR( 255 ) CHARACTER SET ASCII COLLATE ascii_general_ci NOT NULL;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '16fcef4a13d6475a5f8bfef462b548e2'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
