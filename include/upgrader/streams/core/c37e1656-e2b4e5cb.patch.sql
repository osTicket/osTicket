/**
 * @version v1.17
 * @signature e2b4e5cbb8b77e50a8f44bdcf09defc7
 * @title Add plugin multi-instance support
 *
 */

ALTER TABLE `%TABLE_PREFIX%plugin`
    ADD `notes` text AFTER `version`,
    ADD UNIQUE (`install_path`);

CREATE TABLE `%TABLE_PREFIX%plugin_instance` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` int(11) unsigned NOT NULL,
  `flags` int(10) NOT NULL DEFAULT 0,
  `name` varchar(128) NOT NULL DEFAULT '',
  `config` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_id` (`plugin_id`)
) DEFAULT CHARSET=utf8;

UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'e2b4e5cbb8b77e50a8f44bdcf09defc7', updated = NOW()
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
