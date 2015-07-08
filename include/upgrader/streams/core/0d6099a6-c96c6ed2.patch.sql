/**
 * @signature c96c6ed2a9702f0f6cb35c00bf5d5353
 * @version v1.10.0
 * @title Access Control 2.0
 *
 */

DROP TABLE IF EXISTS `%TABLE_PREFIX%staff_dept_access`;
CREATE TABLE `%TABLE_PREFIX%staff_dept_access` (
  `staff_id` int(10) unsigned NOT NULL DEFAULT 0,
  `dept_id` int(10) unsigned NOT NULL DEFAULT 0,
  `role_id` int(10) unsigned NOT NULL DEFAULT 0,
  `flags` int(10) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY `staff_dept` (`staff_id`,`dept_id`),
  KEY `dept_id` (`dept_id`)
) DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%staff_dept_access`
  (`staff_id`, `dept_id`, `role_id`)
  SELECT A1.`staff_id`, A2.`dept_id`, A2.`role_id`
  FROM `%TABLE_PREFIX%staff` A1
  JOIN `%TABLE_PREFIX%group_dept_access` A2 ON (A1.`group_id` = A2.`group_id`);

ALTER TABLE `%TABLE_PREFIX%staff`
  DROP `group_id`,
  ADD `permissions` text AFTER `extra`;

ALTER TABLE `%TABLE_PREFIX%team_member`
  ADD `flags` int(10) unsigned NOT NULL DEFAULT 1 AFTER `staff_id`;

ALTER TABLE `%TABLE_PREFIX%thread_collaborator`
  ADD KEY `user_id` (`user_id`);

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'c96c6ed2a9702f0f6cb35c00bf5d5353'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
