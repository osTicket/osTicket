/**
 * @version 1.8-stable
 * @signature e6f326c8e9863cd0c63c3c78d344fc37
 *
 * Add isrevolving column to sla table
 */

ALTER TABLE `%TABLE_PREFIX%config` 
	ADD COLUMN `isrevolving` tinyint(1) unsigned NOT NULL default '0' 
	AFTER `isactive`; 

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'e6f326c8e9863cd0c63c3c78d344fc37'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
