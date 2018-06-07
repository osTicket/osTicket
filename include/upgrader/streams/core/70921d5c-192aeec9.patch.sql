/**
* @signature 192aeec968ad819b88c49d7e0fecd723
* @version v1.11.0
* @title Final Revisions
*
* This patch is for final revisions needed for v1.11
*/

ALTER TABLE `%TABLE_PREFIX%thread_event`
  CHANGE `state` `state` enum('created','closed','reopened','assigned','transferred', 'referred', 'overdue','edited','viewed','error','collab','resent', 'deleted') NOT NULL;

 -- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '192aeec968ad819b88c49d7e0fecd723'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
