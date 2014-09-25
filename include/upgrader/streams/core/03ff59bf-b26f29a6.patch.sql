/**
 * @version v1.9.4
 * @signature b26f29a6bb5dbb3510b057632182d138
 * @title Add properties filed and drop 'resolved' state
 *
 * This patch drops resolved state and any associated statuses
 *
 */

-- Move tickets in resolved state to the default closed status
SET @statusId = (
        SELECT id FROM  `%TABLE_PREFIX%ticket_status`
        WHERE  `state` =  'closed' ORDER BY id ASC LIMIT 1);

UPDATE  `%TABLE_PREFIX%ticket` t1
    JOIN `%TABLE_PREFIX%ticket_status` t2
        ON (t2.id = t1.status_id)
    SET t1.status_id = @statusId
    WHERE t2.state='resolved';

-- add properties field
ALTER TABLE  `%TABLE_PREFIX%ticket_status`
    ADD  `properties` TEXT NOT NULL AFTER  `sort`,
    DROP  `notes`;

UPDATE `%TABLE_PREFIX%ticket_status` s
    INNER JOIN `ost_config` c
        ON(c.namespace = CONCAT('TS.', s.id) AND c.key='properties')
    SET s.properties = c.value;

--  add default reopen settings to existing closed state statuses
UPDATE `%TABLE_PREFIX%ticket_status`
    SET `properties`= INSERT(`properties`, 2, 0, '"allowreopen":true,"reopenstatus":0,')
    WHERE `state` = 'closed';

-- change thread body text to 16Mb.
ALTER TABLE  `%TABLE_PREFIX%ticket_thread`
    CHANGE  `body`  `body` mediumtext NOT NULL;

-- index ext id
ALTER TABLE  `%TABLE_PREFIX%note`
    ADD INDEX (`ext_id`);

-- Set new schema signature
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'b26f29a6bb5dbb3510b057632182d138'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
