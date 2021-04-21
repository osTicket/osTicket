/**
 * @version v1.8 - Collaboration
 * @signature ed60ba203a473f4f32ac49eb45db16c7
 * @title Add support for ticket collaborators
 *
 * Adds the database structure for collaboration table
 *
 */

DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket_collaborator`;
CREATE TABLE `%TABLE_PREFIX%ticket_collaborator` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `isactive` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `ticket_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  -- M => (message) clients, N => (note) 3rd-Party, R => (reply) external authority
  `role` char(1) NOT NULL DEFAULT 'M',
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `collab` (`ticket_id`,`user_id`)
) DEFAULT CHARSET=utf8;


--  Finish
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'ed60ba203a473f4f32ac49eb45db16c7'
        WHERE `key` = 'schema_signature' AND `namespace` = 'core';
