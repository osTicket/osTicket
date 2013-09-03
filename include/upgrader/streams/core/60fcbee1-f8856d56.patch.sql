DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket_event`;
CREATE TABLE `%TABLE_PREFIX%ticket_event` (
  `ticket_id` int(11) unsigned NOT NULL default '0',
  `staff_id` int(11) unsigned NOT NULL,
  `team_id` int(11) unsigned NOT NULL,
  `dept_id` int(11) unsigned NOT NULL,
  `topic_id` int(11) unsigned NOT NULL,
  `state` enum('created','closed','reopened','assigned','transferred','overdue') NOT NULL,
  `staff` varchar(255) NOT NULL default 'SYSTEM',
  `timestamp` datetime NOT NULL,
  KEY `ticket_state` (`ticket_id`, `state`, `timestamp`),
  KEY `ticket_stats` (`timestamp`, `state`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket_history`;
DROP TABLE IF EXISTS `%TABLE_PREFIX%history`;

-- Transfer ticket statistics from the %ticket table (inaccurate)
-- REOPENED
INSERT INTO `%TABLE_PREFIX%ticket_event`
    (`ticket_id`, `staff_id`, `team_id`, `dept_id`, `topic_id`,
        `state`, `staff`, `timestamp`)
    SELECT `ticket_id`, T1.`staff_id`, `team_id`, T1.`dept_id`, `topic_id`,
        'reopened', T2.`username`, `reopened`
    FROM `%TABLE_PREFIX%ticket` T1
        INNER JOIN `%TABLE_PREFIX%staff` T2
        ON (T1.`staff_id` = T2.`staff_id`)
    WHERE `status` = 'open' and `reopened` is not null;

-- CLOSED
INSERT INTO `%TABLE_PREFIX%ticket_event`
    (`ticket_id`, `staff_id`, `team_id`, `dept_id`, `topic_id`,
        `state`, `staff`, `timestamp`)
    SELECT `ticket_id`, T1.`staff_id`, `team_id`, T1.`dept_id`, `topic_id`,
        'closed', COALESCE(T2.`username`,'unknown'), `closed`
    FROM `%TABLE_PREFIX%ticket` T1
        LEFT JOIN `%TABLE_PREFIX%staff` T2
        ON (T1.`staff_id` = T2.`staff_id`)
    WHERE `status` = 'closed' and `closed` is not null;

-- OVERDUE
INSERT INTO `%TABLE_PREFIX%ticket_event`
    (`ticket_id`, `staff_id`, `team_id`, `dept_id`, `topic_id`,
        `state`, `staff`, `timestamp`)
    SELECT `ticket_id`, T1.`staff_id`, `team_id`, T1.`dept_id`, `topic_id`,
        'overdue', 'SYSTEM', `duedate`
    FROM `%TABLE_PREFIX%ticket` T1
        INNER JOIN `%TABLE_PREFIX%staff` T2
        ON (T1.`staff_id` = T2.`staff_id`)
    WHERE `status` = 'open' and `isoverdue`;

-- OPENED
INSERT INTO `%TABLE_PREFIX%ticket_event`
    (`ticket_id`, `staff_id`, `team_id`, `dept_id`, `topic_id`,
        `state`, `staff`, `timestamp`)
    SELECT `ticket_id`, 0, 0, 0, `topic_id`,
        'created', 'SYSTEM', T1.`created`
    FROM `%TABLE_PREFIX%ticket` T1;

UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='f8856d56e51c5cc3416389de78b54515';
