/**
 * @signature 98ad7d550c26ac44340350912296e673
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

-- Expand staff -> group -> dept_access to staff -> dept_access
-- At the same time, drop primary department from staff_dept_access
INSERT INTO `%TABLE_PREFIX%staff_dept_access`
  (`staff_id`, `dept_id`, `role_id`)
  SELECT A1.`staff_id`, A2.`dept_id`,
    CASE WHEN A2.`role_id` = 0 THEN A3.`role_id` ELSE A2.`role_id` END
  FROM `%TABLE_PREFIX%staff` A1
  JOIN `%TABLE_PREFIX%group_dept_access` A2 ON (A1.`group_id` = A2.`group_id`)
  JOIN `%TABLE_PREFIX%group` A3 ON (A3.`id` = A1.`group_id`)
  WHERE A2.`dept_id` != A1.`dept_id`;

ALTER TABLE `%TABLE_PREFIX%staff`
  DROP `group_id`,
  ADD `permissions` text AFTER `extra`;

ALTER TABLE `%TABLE_PREFIX%team_member`
  ADD `flags` int(10) unsigned NOT NULL DEFAULT 1 AFTER `staff_id`;

ALTER TABLE `%TABLE_PREFIX%task`
  ADD `closed` datetime DEFAULT NULL AFTER `duedate`;

ALTER TABLE `%TABLE_PREFIX%thread`
  ADD `lastresponse` datetime DEFAULT NULL AFTER `extra`,
  ADD `lastmessage` datetime DEFAULT NULL AFTER `lastresponse`;

UPDATE `%TABLE_PREFIX%thread` A1
  JOIN `%TABLE_PREFIX%ticket` A2 ON (A2.`ticket_id` = A1.`object_id` AND A1.`object_type` = 'T')
  SET A1.`lastresponse` = A2.`lastresponse`,
      A1.`lastmessage` = A2.`lastmessage`;

-- Mark `message` field as externally stored
-- DynamicFormField::FLAG_EXT_STORED = 0x00002;
UPDATE `%TABLE_PREFIX%form_field` A1
  JOIN `%TABLE_PREFIX%form` A2 ON (A2.`id` = A1.`form_id`)
  SET A1.`flags` = A1.`flags` | 0x00002
  WHERE A2.`type` = 'T' AND A1.`name` = 'message';

-- Change storage type for `DatetimeField` values to Y-m-d format
UPDATE `%TABLE_PREFIX%form_entry_values` A1
  JOIN `%TABLE_PREFIX%form_field` A2 ON (A2.`id` = A1.`field_id`)
  SET A1.`value` = DATE_FORMAT(FROM_UNIXTIME(A1.`value`), '%Y-%m-%d %H:%i:%s')
  WHERE A2.`type` = 'datetime';

-- Updates should happen in the %cdata tables too; however, those are more
-- complex and the tables are being dropped anyway

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '98ad7d550c26ac44340350912296e673'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
