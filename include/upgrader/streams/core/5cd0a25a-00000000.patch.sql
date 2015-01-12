/**
 * @signature 0e47d678f50874fa0d33e1e3759f657e
 * @version v1.9.6
 * @title Make fields disable-able per help topic
 */

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
  PRIMARY KEY  (`topic_id`, `form_id`)
) DEFAULT CHARSET=utf8;

insert into `%TABLE_PREFIX%help_topic_form`
    (`topic_id`, `form_id`, `sort`)
    select A1.topic_id, case
        when A2.form_id = 4294967295 then A3.form_id
        when A1.form_id = 4294967295 then A2.form_id
        else A1.form_id end as form_id, 1 as `sort`
    from `%TABLE_PREFIX%help_topic` A1
    left join `%TABLE_PREFIX%help_topic` A2 on (A2.topic_pid = A1.topic_id)
    left join `%TABLE_PREFIX%help_topic` A3 on (A3.topic_pid = A2.topic_id)
    having `form_id` > 0
    union
    select A2.topic_id, id as `form_id`, 2 as `sort`
    from `%TABLE_PREFIX%form` A1
    join `%TABLE_PREFIX%help_topic` A2
    where A1.`type` = 'T';

ALTER TABLE `%TABLE_PREFIX%help_topic`
    DROP `form_id` int(10) unsigned NOT NULL default '0';

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '0e47d678f50874fa0d33e1e3759f657e'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
