/**
* @signature cce1ba439ea7e50fcb845fd067779088
* @version v1.11.0
* @title Archive Departments and Help Topics
*
* This patch replaces the isactive field on Help Topics with using the flags field to add a new status called 'archived'.
* It also adds a status field to Departments, allowing Agents to activate, disable, and archive Departments
* through using the flags field
*
* Finally, a flag field is added to the filter table
*/
-- Help Topics
UPDATE `%TABLE_PREFIX%help_topic`
     SET `flags` = `flags` + 2
     WHERE `isactive` = 1;

ALTER TABLE `%TABLE_PREFIX%help_topic`
  DROP COLUMN `isactive`;

-- Departments
UPDATE `%TABLE_PREFIX%department`
    SET `flags` = `flags` + 4
    WHERE `ispublic` = 1;

-- Ticket Filters
ALTER TABLE `%TABLE_PREFIX%filter`
    ADD `flags` int(10) unsigned DEFAULT '0' AFTER  `isactive`;

 -- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'cce1ba439ea7e50fcb845fd067779088'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
