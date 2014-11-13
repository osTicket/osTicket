/**
 * @version v1.9.5
 * @signature 00000000000000000000000000000000
 * @title Add sub-department support
 *
 */

ALTER TABLE `%TABLE_PREFIX%department`
  ADD `dept_pid` int(11) unsigned default NULL AFTER `dept_id`,
  ADD `path` varchar(128) NOT NULL default '/' AFTER `message_auto_response`;

UPDATE `%TABLE_PREFIX%department`
  SET `path` = CONCAT('/', dept_id, '/');

-- Set new schema signature
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '00000000000000000000000000000000'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
