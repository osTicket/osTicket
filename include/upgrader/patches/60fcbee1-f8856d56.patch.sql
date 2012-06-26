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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket_history`;
DROP TABLE IF EXISTS `%TABLE_PREFIX%history`;

UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='f8856d56e51c5cc3416389de78b54515';
