/**
 * @signature 2d590ffab4a6a928f08cc97aace1399e
 * @version v1.10.0
 * @title Make fields disable-able per help topic
 *
 * This patch adds the ability to associate more than one extra form with a
 * help topic, allows specifying the sort order of each form, including the
 * main ticket details forms, and also allows disabling any of the fields on
 * any of the associated forms, including the issue details field.
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

ALTER TABLE `%TABLE_PREFIX%form`
    ADD `pid` int(10) unsigned DEFAULT NULL AFTER `id`,
    ADD `name` varchar(64) NOT NULL DEFAULT '' AFTER `instructions`;

ALTER TABLE `%TABLE_PREFIX%form_entry`
    ADD `extra` text AFTER `sort`;

CREATE TABLE `%TABLE_PREFIX%help_topic_form` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `topic_id` int(11) unsigned NOT NULL default 0,
  `form_id` int(10) unsigned NOT NULL default 0,
  `sort` int(10) unsigned NOT NULL default 1,
  `extra` text,
  PRIMARY KEY (`id`),
  KEY `topic-form` (`topic_id`, `form_id`)
) DEFAULT CHARSET=utf8;

-- Handle A4 / A3 / A2 / A1 help topics. For these, consider the forms
-- associated with each, which should sort above the ticket details form, as
-- the graphical interface rendered it suchly. Then, consider cascaded
-- forms, where the parent form was specified on a child.
insert into `%TABLE_PREFIX%help_topic_form`
    (`topic_id`, `form_id`, `sort`)
    select A1.topic_id, case
        when A3.form_id = 4294967295 then A4.form_id
        when A2.form_id = 4294967295 then A3.form_id
        when A1.form_id = 4294967295 then A2.form_id
        else COALESCE(A4.form_id, A3.form_id, A2.form_id, A1.form_id) end as form_id, 1 as `sort`
    from `%TABLE_PREFIX%help_topic` A1
    left join `%TABLE_PREFIX%help_topic` A2 on (A2.topic_id = A1.topic_pid)
    left join `%TABLE_PREFIX%help_topic` A3 on (A3.topic_id = A2.topic_pid)
    left join `%TABLE_PREFIX%help_topic` A4 on (A4.topic_id = A3.topic_pid)
    having `form_id` > 0
    union
    select A2.topic_id, id as `form_id`, 2 as `sort`
    from `%TABLE_PREFIX%form` A1
    join `%TABLE_PREFIX%help_topic` A2
    where A1.`type` = 'T';

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '2d590ffab4a6a928f08cc97aace1399e'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
