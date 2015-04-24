/**
 *  Move dept_access from group table to group_dept_access table.
 *
 * @version 1.7-rc1 Dept_Access
 */

-- Group department access table
DROP TABLE IF EXISTS `%TABLE_PREFIX%group_dept_access`;
CREATE TABLE `%TABLE_PREFIX%group_dept_access` (
  `group_id` int(10) unsigned NOT NULL default '0',
  `dept_id` int(10) unsigned NOT NULL default '0',
  UNIQUE KEY `group_dept`  (`group_id`,`dept_id`),
  KEY `dept_id` (`dept_id`)
) DEFAULT CHARSET=utf8;

-- Extend membership to groups
ALTER TABLE `%TABLE_PREFIX%department`
    ADD `group_membership` tinyint( 1 ) unsigned NOT NULL DEFAULT '0' AFTER `ispublic`;

-- Fix teams dates...
UPDATE `%TABLE_PREFIX%team`
    SET `created`=IF(TO_DAYS(`created`), `created`, IF(TO_DAYS(`updated`), `updated`, NOW())),
        `updated`=IF(TO_DAYS(`updated`), `updated`, NOW());

-- Fix groups dates...
UPDATE `%TABLE_PREFIX%groups`
    SET `created`=IF(TO_DAYS(`created`), `created`, IF(TO_DAYS(`updated`), `updated`, NOW())),
        `updated`=IF(TO_DAYS(`updated`), `updated`, NOW());

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='2e7531a201b5b8650dcd43681a832ebd';
