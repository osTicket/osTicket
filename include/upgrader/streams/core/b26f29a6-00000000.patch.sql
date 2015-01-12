/**
 * @version v1.9.5
 * @signature 00000000000000000000000000000000
 * @title Add flexible filter actions
 *
 * This patch migrates the columnar layout of the %filter table into a new
 * %filter_action table. The cleanup portion of the script will drop the old
 * columns from the %filter table.
 */

DROP TABLE IF EXISTS `%TABLE_PREFIX%filter_action`;
CREATE TABLE `%TABLE_PREFIX%filter_action` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `filter_id` int(10) unsigned NOT NULL,
  `sort` int(10) unsigned NOT NULL default 0,
  `type` varchar(24) NOT NULL,
  `configuration` text,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `filter_id` (`filter_id`)
) DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%filter_action`
    (`filter_id`, `type`, `configuration`, `updated`)
    SELECT `id`, 'reject', '', `updated`
    FROM `%TABLE_PREFIX%filter`
    WHERE `reject_ticket` != 0;

INSERT INTO `%TABLE_PREFIX%filter_action`
    (`filter_id`, `type`, `configuration`, `updated`)
    SELECT `id`, 'replyto', '{"enable":true}', `updated`
    FROM `%TABLE_PREFIX%filter`
    WHERE `use_replyto_email` != 0;

INSERT INTO `%TABLE_PREFIX%filter_action`
    (`filter_id`, `type`, `configuration`, `updated`)
    SELECT `id`, 'noresp', '{"enable":true}', `updated`
    FROM `%TABLE_PREFIX%filter`
    WHERE `disable_autoresponder` != 0;

INSERT INTO `%TABLE_PREFIX%filter_action`
    (`filter_id`, `type`, `configuration`, `updated`)
    SELECT `id`, 'canned', CONCAT('{"canned_id":',`canned_response_id`,'}'), `updated`
    FROM `%TABLE_PREFIX%filter`
    WHERE `canned_response_id` != 0;

INSERT INTO `%TABLE_PREFIX%filter_action`
    (`filter_id`, `type`, `configuration`, `updated`)
    SELECT `id`, 'dept', CONCAT('{"dept_id":',`dept_id`,'}'), `updated`
    FROM `%TABLE_PREFIX%filter`
    WHERE `dept_id` != 0;

INSERT INTO `%TABLE_PREFIX%filter_action`
    (`filter_id`, `type`, `configuration`, `updated`)
    SELECT `id`, 'pri', CONCAT('{"priority_id":',`priority_id`,'}'), `updated`
    FROM `%TABLE_PREFIX%filter`
    WHERE `priority_id` != 0;

INSERT INTO `%TABLE_PREFIX%filter_action`
    (`filter_id`, `type`, `configuration`, `updated`)
    SELECT `id`, 'sla', CONCAT('{"sla_id":',`sla_id`,'}'), `updated`
    FROM `%TABLE_PREFIX%filter`
    WHERE `sla_id` != 0;

INSERT INTO `%TABLE_PREFIX%filter_action`
    (`filter_id`, `type`, `configuration`, `updated`)
    SELECT `id`, 'team', CONCAT('{"team_id":',`team_id`,'}'), `updated`
    FROM `%TABLE_PREFIX%filter`
    WHERE `team_id` != 0;

INSERT INTO `%TABLE_PREFIX%filter_action`
    (`filter_id`, `type`, `configuration`, `updated`)
    SELECT `id`, 'agent', CONCAT('{"staff_id":',`staff_id`,'}'), `updated`
    FROM `%TABLE_PREFIX%filter`
    WHERE `staff_id` != 0;

INSERT INTO `%TABLE_PREFIX%filter_action`
    (`filter_id`, `type`, `configuration`, `updated`)
    SELECT `id`, 'topic', CONCAT('{"topic_id":',`topic_id`,'}'), `updated`
    FROM `%TABLE_PREFIX%filter`
    WHERE `topic_id` != 0;

INSERT INTO `%TABLE_PREFIX%filter_action`
    (`filter_id`, `type`, `configuration`, `updated`)
    SELECT `id`, 'status', CONCAT('{"status_id":',`status_id`,'}'), `updated`
    FROM `%TABLE_PREFIX%filter`
    WHERE `status_id` != 0;

-- Set new schema signature
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '00000000000000000000000000000000'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
