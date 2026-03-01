/**
 * @signature 526c601bc1748febb192f854699f170a
 * @version v1.11.0
 * @title Add Custom Export for Queues
 *
 * This patch adds a table for custom export for custom queues
 */

CREATE TABLE `%TABLE_PREFIX%queue_export` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `queue_id` int(11) unsigned NOT NULL,
  `path` varchar(64) NOT NULL DEFAULT '',
  `heading` varchar(64) DEFAULT NULL,
  `sort` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `queue_id` (`queue_id`)
) DEFAULT CHARSET=utf8;


 -- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '526c601bc1748febb192f854699f170a'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
