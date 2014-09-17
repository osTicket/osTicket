/**
 * @version v1.9.4
 * @signature 519d98cd885f060e220da7b30a6f78ae
 * @title Add properties filed and drop 'resolved' state
 *
 * This patch drops resolved state and any associated statuses
 *
 */

-- add properties field
ALTER TABLE  `%TABLE_PREFIX%ticket_status`
    ADD  `properties` TEXT NOT NULL AFTER  `sort`,
    DROP  `notes`;

UPDATE `%TABLE_PREFIX%ticket_status` s
    INNER JOIN `ost_config` c
        ON(c.namespace = CONCAT('TS.', s.id) AND c.key='properties')
    SET s.properties = c.value;


-- Set new schema signature
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '519d98cd885f060e220da7b30a6f78ae'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
