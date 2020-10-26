-- Patch to add support for Organization restrictions
-- for help topics. This table provides the necessary 
-- many to many relationships needed for this feature.
CREATE TABLE `%TABLE_PREFIX%help_topic_organization` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) unsigned NOT NULL DEFAULT '0',
  `organization_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `topic-organization` (`topic_id`,`organization_id`)
) DEFAULT CHARSET=utf8;

-- Adds the ability to restrict help topics to only the
-- primary contact of an organization. This is used for
-- allowing only managers, etc. access to certain topics.
ALTER TABLE `%TABLE_PREFIX%help_topic` ADD COLUMN orgpconly tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER ispublic;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'dd88e7c232587526e90f49273d1c15ca'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';