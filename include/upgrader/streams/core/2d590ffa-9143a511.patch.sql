/**
 * @version v1.10.0
 * @title Add collaborators to tasks
 * @signature 9143a511719555e8f8f09b49523bd022
 *
 * This patch renames the %ticket_lock table to just %lock, which allows for
 * it to be considered more flexible. Instead, it joins the lock to the
 * ticket and task objects directly.
 *
 * It also redefines the collaborator table to link to a thread rather than
 * to a ticket, which allows any object in the system with a thread to
 * theoretically have collaborators.
 */

ALTER TABLE `%TABLE_PREFIX%ticket`
  ADD `lock_id` int(11) unsigned NOT NULL default '0' AFTER `email_id`;

RENAME TABLE `%TABLE_PREFIX%ticket_lock` TO `%TABLE_PREFIX%lock`;
ALTER TABLE `%TABLE_PREFIX%lock`
  DROP COLUMN `ticket_id`,
  ADD `code` varchar(20) AFTER `expire`;

-- Drop all the current locks as they do not point to anything now
TRUNCATE TABLE `%TABLE_PREFIX%lock`;

CREATE TABLE `%TABLE_PREFIX%thread_collaborator` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `isactive` tinyint(1) NOT NULL DEFAULT '1',
  `thread_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  -- M => (message) clients, N => (note) 3rd-Party, R => (reply) external authority
  `role` char(1) NOT NULL DEFAULT 'M',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `collab` (`thread_id`,`user_id`),
  KEY `user_id` (`user_id`)
) DEFAULT CHARSET=utf8;

-- Drop zombie collaborators from tickets which were deleted and had
-- collaborators and the collaborators were not removed
INSERT INTO `%TABLE_PREFIX%thread_collaborator`
  (`id`, `isactive`, `thread_id`, `user_id`, `role`, `created`, `updated`)
  SELECT t1.`id`, t1.`isactive`, t2.`id`, t1.`user_id`, t1.`role`, t2.`created`, t1.`updated`
  FROM `%TABLE_PREFIX%ticket_collaborator` t1
  JOIN `%TABLE_PREFIX%thread` t2 ON (t2.`object_id` = t1.`ticket_id`  and t2.`object_type` = 'T');

DROP TABLE `%TABLE_PREFIX%ticket_collaborator`;

ALTER TABLE `%TABLE_PREFIX%task`
  ADD `lock_id` int(11) unsigned NOT NULL DEFAULT '0' AFTER `team_id`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '9143a511719555e8f8f09b49523bd022'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
