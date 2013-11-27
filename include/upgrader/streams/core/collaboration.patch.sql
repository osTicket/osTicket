/**
 * @version v1.8.1 Collaboration (CC/BCC support)
 * @signature f353145f8f4f48ea7f0d8e87083bb57c
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
  `role` char(1) NOT NULL DEFAULT 'E',
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `collab` (`ticket_id`,`user_id`)
) DEFAULT CHARSET=utf8;


--  Finish
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'f353145f8f4f48ea7f0d8e87083bb57c'
        WHERE `key` = 'schema_signature' AND `namespace` = 'core';
