/**
* @signature e69781546e08be96d787199a911d0ffe
* @version v1.14.0
* @title Thread Type
*
* This patch adds a new field to the Thread Event table called thread_type
* it allows us to be able to delete threads and thread entries when a ticket
* is deleted while still maintaining dashboard statistics
*
*/

-- Add thread_type column to tickets
ALTER TABLE `%TABLE_PREFIX%thread_event`
    ADD `thread_type` char(1) NOT NULL DEFAULT '' AFTER `thread_id`;

-- Update thread_type column
UPDATE `%TABLE_PREFIX%thread_event` A1
JOIN `%TABLE_PREFIX%thread` A2 ON A1.thread_id = A2.id
SET A1.thread_type = A2.object_type;

-- Delete unneeded threads
DELETE A1
FROM `%TABLE_PREFIX%thread` A1
JOIN `%TABLE_PREFIX%thread_event` A2 ON A2.thread_id = A1.id
WHERE A2.event_id = 14;

-- Delete unneeded thread entries
DELETE A1
FROM `%TABLE_PREFIX%thread_entry` A1
LEFT JOIN `%TABLE_PREFIX%thread` A2 ON(A2.id=A1.thread_id)
WHERE A2.id IS NULL;

-- Set deleted threads to 0
UPDATE `%TABLE_PREFIX%thread_event` A1
JOIN `%TABLE_PREFIX%thread_event` A2 ON A2.thread_id = A1.thread_id
SET A2.thread_id = 0
WHERE A1.event_id = 14;

-- Update Org. Form Name field `flags` and `type`
UPDATE `ost_form_field` A1
JOIN `ost_form` A2 ON (A1.`form_id` = A2.`id`)
SET A1.`flags` = A1.`flags` + 524288, A1.`type` = 'text'
WHERE A2.`type` = 'O' AND (A1.`name` = 'name' OR A1.`label` = 'Name');

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
   SET `value` = 'e69781546e08be96d787199a911d0ffe', `updated` = NOW()
   WHERE `key` = 'schema_signature' AND `namespace` = 'core';
