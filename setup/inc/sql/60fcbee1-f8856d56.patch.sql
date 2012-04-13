ALTER TABLE `%TABLE_PREFIX%history`
    ADD `staff_id` int(11) unsigned NOT NULL AFTER `ticket_id`,
    ADD `team_id` int(11) unsigned NOT NULL AFTER `staff_id`,
    ADD `dept_id` int(11) unsigned NOT NULL AFTER `team_id`,
    ADD `topic_id` int(11) unsigned NOT NULL AFTER `dept_id`;

RENAME TABLE `%TABLE_PREFIX%history` TO `%TABLE_PREFIX%event`;

UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='f8856d56e51c5cc3416389de78b54515';
