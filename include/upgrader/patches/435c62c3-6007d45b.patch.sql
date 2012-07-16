/**
 *  Move dept_access from group table to group_dept_access table.
 *
 * @version 1.7-rc1 Dept_Access
 */

-- Group department access table
CREATE TABLE `%TABLE_PREFIX%group_dept_access` (
  `group_id` int(10) unsigned NOT NULL default '0',
  `dept_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`group_id`,`dept_id`)
) ENGINE=MyISAM;

-- Extend membership to groups
ALTER TABLE `%TABLE_PREFIX%department`
    ADD `group_membership` tinyint( 1 ) unsigned NOT NULL DEFAULT '0' AFTER `ispublic`;

-- Fix teams create date 
UPDATE `%TABLE_PREFIX%team` 
    SET `created`=IFNULL(`created`, IFNULL(`updated`, NOW())), `updated`=IFNULL(`updated`, NOW());

-- Fix groups dates... 
UPDATE `%TABLE_PREFIX%groups` 
    SET `created`=IFNULL(`created`, IFNULL(`updated`, NOW())), `updated`=IFNULL(`updated`, NOW());

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='6007d45b580c6ac0206514dbed0f28a6';
