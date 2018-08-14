/**
 * @signature 86707325fc571e56242fccc46fd24466
 * @version v1.11.0
 * @title Add ticket referral
 *
 * This patch adds a table for thread referral as well as thread event states of referred and deleted
 */

CREATE TABLE `%TABLE_PREFIX%thread_referral` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int(11) unsigned NOT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `object_type` char(1) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ref` (`object_id`,`object_type`,`thread_id`),
  KEY `thread_id` (`thread_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

 -- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '86707325fc571e56242fccc46fd24466'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
