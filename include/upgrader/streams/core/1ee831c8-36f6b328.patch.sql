/**
 * @signature 36f6b32893c2b97c5104ab5302d2dd2e
 * @version v1.10.0
 * @title Add role-based access
 *
 * This patch adds support for role based access to group and departments
 *
 */

CREATE TABLE `%TABLE_PREFIX%role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `flags` int(10) unsigned NOT NULL DEFAULT '1',
  `name` varchar(64) DEFAULT NULL,
  `permissions` text,
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) DEFAULT CHARSET=utf8;

ALTER TABLE  `%TABLE_PREFIX%group_dept_access`
    ADD  `role_id` INT UNSIGNED NOT NULL DEFAULT  '0';

ALTER TABLE `%TABLE_PREFIX%staff`
    ADD `role_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `dept_id`;

ALTER TABLE  `%TABLE_PREFIX%groups`
    RENAME TO `%TABLE_PREFIX%group`,
    CHANGE  `group_id`  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ADD  `role_id` INT UNSIGNED NOT NULL DEFAULT  '0' AFTER  `id`;

-- department changes
ALTER TABLE  `%TABLE_PREFIX%department`
    CHANGE  `dept_id`  `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
    CHANGE  `dept_signature`  `signature` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    CHANGE  `dept_name`  `name` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '';

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value`='36f6b32893c2b97c5104ab5302d2dd2e'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
