/**
 * @version v1.11.0
 * @title Add recipients field/collaborator flags
 * @signature b2ce8ba794a40ed5380d7cdf30bca233
 *
 * This patch adds a new field called recipients to the thread entry table
 * allowing agents to see a list of recipients for any thread entry where
 * an email was involved (agent or user generated)
 *
 * It also adds a flags field to the thread_collaborator table which
 * tracks whether a collaborator is a CC or BCC collaborator as well as
 * storing whether or not the collaborator is active. As a result, we can
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
    SET `value` = 'b2ce8ba794a40ed5380d7cdf30bca233'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
