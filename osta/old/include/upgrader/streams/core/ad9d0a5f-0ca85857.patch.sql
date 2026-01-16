/**
 * @signature 0ca8585781bc6656f3ca008212554441
 * @version v1.11.0
 * @title Add recipients field and collaborator flags
 *
 * This patch adds a new field called recipients to the thread entry table
 * allowing agents to see a list of recipients for any thread entry where
 * an email was involved (agent or user generated)
 *
 * It also adds a flags field to the thread_collaborator table which
 * tracks whether or not the collaborator is active. As a result, we can
 * remove the isactive field
 */

 ALTER TABLE `%TABLE_PREFIX%thread_entry`
  ADD `recipients` text AFTER `ip_address`;

 ALTER TABLE `%TABLE_PREFIX%thread_collaborator`
  ADD `flags` int(10) unsigned NOT NULL DEFAULT 1 AFTER `id`;

 UPDATE `%TABLE_PREFIX%thread_collaborator`
  SET `flags` = `isactive` + 2;

  ALTER TABLE `%TABLE_PREFIX%thread_collaborator`
   DROP COLUMN `isactive`;

 -- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '0ca8585781bc6656f3ca008212554441'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
