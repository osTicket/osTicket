/**
 * @signature c7c828356c88b462ba2e3e1437dca0df
 * @version v1.9.6
 * @title Add role-based access
 *
 * This patch adds support for role based access to group and departments
 *
 */

CREATE TABLE `%TABLE_PREFIX%role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `flags` int(10) unsigned NOT NULL DEFAULT '1',
  `name` varchar(64) DEFAULT NULL,
  `notes` text,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) DEFAULT CHARSET=utf8;

ALTER TABLE  `%TABLE_PREFIX%group_dept_access`
    ADD  `role_id` INT UNSIGNED NOT NULL DEFAULT  '0';

ALTER TABLE  `%TABLE_PREFIX%groups`
    ADD  `role_id` INT UNSIGNED NOT NULL DEFAULT  '0' AFTER  `group_id` ,
    ADD  `flags` INT UNSIGNED NOT NULL DEFAULT  '1' AFTER  `role_id`,
    CHANGE  `group_name`  `name` VARCHAR(120) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '',
    CHANGE  `group_id`  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ADD INDEX (`role_id`);

RENAME TABLE  `%TABLE_PREFIX%groups` TO  `%TABLE_PREFIX%group`;

-- department changes
ALTER TABLE  `%TABLE_PREFIX%department`
    CHANGE  `dept_id`  `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
    CHANGE  `dept_signature`  `signature` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    CHANGE  `dept_name`  `name` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '';

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='c7c828356c88b462ba2e3e1437dca0df';
