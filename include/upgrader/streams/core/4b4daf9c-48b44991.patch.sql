/**
 * @version v1.9.5
 * @signature 48b4499152a6c11e714d4a40fc33332a
 * @title Add tasks
 *
 * This patch introduces the concept of tasks
 *
 */

-- create task task
CREATE TABLE `%TABLE_PREFIX%task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `object_id` int(11) NOT NULL DEFAULT '0',
  `object_type` char(1) NOT NULL,
  `number` varchar(20) DEFAULT NULL,
  `dept_id` int(10) unsigned NOT NULL DEFAULT '0',
  `sla_id` int(10) unsigned NOT NULL DEFAULT '0',
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0',
  `team_id` int(10) unsigned NOT NULL DEFAULT '0',
  `flags` int(10) unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `dept_id` (`dept_id`),
  KEY `staff_id` (`staff_id`),
  KEY `team_id` (`team_id`),
  KEY `created` (`created`),
  KEY `sla_id` (`sla_id`),
  KEY `object` (`object_id`,`object_type`)
) DEFAULT CHARSET=utf8;

-- Add flags field to form field
ALTER TABLE  `%TABLE_PREFIX%form_field`
    ADD  `flags` INT UNSIGNED NOT NULL DEFAULT  '1' AFTER  `form_id`;

-- Flag field stored in the system elsewhere as nonstorable locally.
UPDATE `%TABLE_PREFIX%form_field` A1 JOIN `%TABLE_PREFIX%form` A2 ON(A2.id=A1.form_id)
    SET A1.`flags` = 3
    WHERE A2.`type` = 'U' AND A1.`name` IN('name','email');

UPDATE `%TABLE_PREFIX%form_field` A1 JOIN `%TABLE_PREFIX%form` A2 ON(A2.id=A1.form_id)
    SET A1.`flags`=3
    WHERE A2.`type`='O' AND A1.`name` IN('name');

-- TODO: add ticket thread entry??


-- rename ticket sequence numbering

UPDATE `%TABLE_PREFIX%config`
    SET `key` = 'ticket_number_format'
    WHERE `key` = 'number_format'  AND `namespace` = 'core';

UPDATE `%TABLE_PREFIX%config`
    SET `key` = 'ticket_sequence_id'
    WHERE `key` = 'sequence_id'  AND `namespace` = 'core';

-- Set new schema signature
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '48b4499152a6c11e714d4a40fc33332a'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
