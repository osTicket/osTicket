/**
 * @signature 0d6099a650cc7884eb59a040feab2ce8
 * @version v1.10.0
 * @title Add events to the ticket thread
 *
 */

ALTER TABLE `%TABLE_PREFIX%ticket_event`
  ADD `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
  CHANGE `ticket_id` `thread_id` int(11) unsigned NOT NULL default '0',
  CHANGE `staff` `username` varchar(128) NOT NULL default 'SYSTEM',
  CHANGE `state` `state` enum('created','closed','reopened','assigned','transferred','overdue','edited','viewed','error','collab','resent') NOT NULL,
  ADD `data` varchar(1024) DEFAULT NULL COMMENT 'Encoded differences' AFTER `state`,
  ADD `uid` int(11) unsigned DEFAULT NULL AFTER `username`,
  ADD `uid_type` char(1) NOT NULL DEFAULT 'S' AFTER `uid`,
  RENAME TO `%TABLE_PREFIX%thread_event`;

-- Change the `ticket_id` column to the values in `%thread`.`id`
CREATE TABLE `%TABLE_PREFIX%_ticket_thread_evt`
    (PRIMARY KEY (`object_id`))
    SELECT `object_id`, `id` FROM `%TABLE_PREFIX%thread`
    WHERE `object_type` = 'T';

UPDATE `%TABLE_PREFIX%thread_event` A1
    LEFT JOIN `%TABLE_PREFIX%_ticket_thread_evt` A2 ON (A1.`thread_id` = A2.`object_id`)
    SET A1.`thread_id` = A2.`id`;

DROP TABLE `%TABLE_PREFIX%_ticket_thread_evt`;

-- Attempt to connect the `username` to the staff_id
UPDATE `%TABLE_PREFIX%thread_event` A1
    LEFT JOIN `%TABLE_PREFIX%staff` A2 ON (A2.`username` = A1.`username`)
    SET A1.`uid` = A2.`staff_id`
    WHERE A1.`username` != 'SYSTEM';

ALTER TABLE `%TABLE_PREFIX%user_email`
  ADD `flags` int(10) unsigned NOT NULL DEFAULT 0 AFTER `user_id`;

ALTER TABLE `%TABLE_PREFIX%form`
  CHANGE `deletable` `flags` int(10) unsigned NOT NULL DEFAULT 1;

-- Previous versions did not correctly mark the internal forms as NOT deletable
UPDATE `%TABLE_PREFIX%form`
  SET `flags` = 0 WHERE `type` IN ('T','U','C','O','A');

ALTER TABLE `%TABLE_PREFIX%team`
  ADD `flags` int(10) unsigned NOT NULL default 1 AFTER `lead_id`;

UPDATE `%TABLE_PREFIX%team`
  SET `flags` = CASE WHEN `isenabled` THEN 1 ELSE 0 END
              + CASE WHEN `noalerts` THEN 2 ELSE 0 END;

-- Migrate %config[namespace=dept.x, key=alert_members_only]
ALTER TABLE `%TABLE_PREFIX%department`
  ADD `flags` int(10) unsigned NOT NULL default 0 AFTER `manager_id`;

UPDATE `%TABLE_PREFIX%department` A1
  JOIN `%TABLE_PREFIX%config` A2
    ON (A2.`namespace` = CONCAT('dept.', A1.`id`) AND A2.`key` = 'assign_members_only')
  SET A1.`flags` = 1 WHERE A2.`value` != '';

-- Migrate %config[namespace=sla.x, key=transient]
ALTER TABLE `%TABLE_PREFIX%sla`
  ADD `flags` int(10) unsigned NOT NULL default 3 AFTER `id`;

UPDATE `%TABLE_PREFIX%sla` A1
  SET A1.`flags` =
      (CASE WHEN A1.`isactive` THEN 1 ELSE 0 END)
    | (CASE WHEN A1.`enable_priority_escalation` THEN 2 ELSE 0 END)
    | (CASE WHEN A1.`disable_overdue_alerts` THEN 4 ELSE 0 END)
    | (CASE WHEN (SELECT `value` FROM `%TABLE_PREFIX%config` `config`
            WHERE`config`.`namespace` = CONCAT('sla.', A1.`id`) AND `config`.`key` = 'transient')
            = '1' THEN 8 ELSE 0 END);

ALTER TABLE `%TABLE_PREFIX%ticket`
  ADD `source_extra` varchar(40) NULL default NULL AFTER `source`;

-- Retire %config[namespace=list.x, key=configuration]
ALTER TABLE `%TABLE_PREFIX%list`
  ADD `configuration` text NOT NULL DEFAULT '' AFTER `type`;

UPDATE `%TABLE_PREFIX%list` A1
  JOIN `%TABLE_PREFIX%config` A2
    ON (A2.`namespace` = CONCAT('list.', A1.`id`) AND A2.`key` = 'configuration')
  SET A1.`configuration` = A2.`value`;

-- Rebuild %ticket__cdata as UTF8
DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket__cdata`;

-- Move `enable_html_thread` to `enable_richtext`
UPDATE `%TABLE_PREFIX%config`
  SET `key` = 'enable_richtext'
  WHERE `namespace` = 'core' AND `key` = 'enable_html_thread';

SET @name_format = (SELECT `value` FROM `%TABLE_PREFIX%config` A1
    WHERE A1.`namespace` = 'core' AND A1.`key` = 'name_format');
INSERT INTO `%TABLE_PREFIX%config`
    (`namespace`, `key`, `value`) VALUES
    ('core', 'agent_name_format', @name_format),
    ('core', 'client_name_format', @name_format);

-- Drop search table and turn on reindexing
DROP TABLE IF EXISTS `%TABLE_PREFIX%_search`;

UPDATE `%TABLE_PREFIX%config` SET `value` = '1'
  WHERE `key` = 'reindex' and `namespace` = 'mysqlsearch';

-- Support varying names for duplicated content
ALTER TABLE `%TABLE_PREFIX%attachment`
  ADD `name` varchar(255) NULL default NULL AFTER `file_id`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '0d6099a650cc7884eb59a040feab2ce8'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
