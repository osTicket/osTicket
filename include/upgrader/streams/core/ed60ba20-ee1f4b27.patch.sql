/**
 * @version v1.8.1
 * @signature ee1f4b2752ee7b4be24a4d9dfe96185a
 * @title Pluggable Storage
 *
 * This patch will allow attachments to be stored outside the database (like
 * on the filesystem)
 */

ALTER TABLE `%TABLE_PREFIX%file`
    ADD `bk` CHAR(1) NOT NULL DEFAULT 'D' AFTER `ft`,
    CHANGE `hash` `key` VARCHAR(125) COLLATE ascii_general_ci,
    ADD `signature` VARCHAR(125) COLLATE ascii_bin AFTER `key`,
    ADD INDEX (`signature`);

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'ee1f4b2752ee7b4be24a4d9dfe96185a'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
