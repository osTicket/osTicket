/**
 * @version v1.9.4 RC4
 * @signature 731b6166edd67b4db8f8a9380309ff62b7feb145
 * @file name 731b6166.patch.sql
 *
 *  - Adds required fields to ticket table
 *  - Adds required fields to ticket_thread table
 *  - Inserts Time Type into Custom Lists
 */

-- Adds time_spent field to ticket table
ALTER TABLE `%TABLE_PREFIX%ticket` ADD COLUMN `time_spent` FLOAT(4,2)  NOT NULL DEFAULT '0.00' AFTER `closed`;


-- Adds time_spent & time_type field to ticket_thread table
ALTER TABLE `%TABLE_PREFIX%ticket_thread` ADD COLUMN `time_spent` FLOAT(4,2) NOT NULL DEFAULT '0.00' AFTER `thread_type`;
ALTER TABLE `%TABLE_PREFIX%ticket_thread` ADD COLUMN `time_type` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `time_spent`;


-- Add Custom List for Time Types
INSERT INTO `%TABLE_PREFIX%list` (
`name` ,
`name_plural` ,
`sort_mode` ,
`masks` ,
`type` ,
`notes` ,
`created` ,
`updated`
)
VALUES (
'Time Type', 'Time Types', 'SortCol', '13', 'time-type', 'Time Type', '2014-10-04 04:02:00', '2014-10-04 04:02:00'
);


-- OLD AND NO LONGER USED
/**
-- Create new table for time type and populate it with default data
CREATE TABLE `%TABLE_PREFIX%time_type` (
`id` int( 11 ) NOT NULL AUTO_INCREMENT ,
`name` varchar( 60 ) NOT NULL default '',
`mode` int( 11 ) unsigned NOT NULL default '0',
`flags` int( 11 ) unsigned NOT NULL default '0',
`sort` int( 11 ) unsigned NOT NULL default '0',
`properties` text NOT NULL ,
`notes` text NOT NULL ,
`created` datetime NOT NULL ,
`updated` datetime NOT NULL ,
PRIMARY KEY ( `id` ) ,
UNIQUE KEY `name` ( `name` )
) ENGINE = MYISAM DEFAULT CHARSET = utf8;

INSERT INTO `%TABLE_PREFIX%time_type` (`id`, `name`, `mode`, `flags`, `sort`, `properties`, `notes`, `created`, `updated`) VALUES
(1, 'Telephone', 3, 0, 1, '{"description":"Time and support over the telephone."}', '', '2014-10-04 05:00:00', '0000-00-00 00:00:00'),
(2, 'Email', 3, 0, 2, '{"description":"Support provided via email or ticket."}', '', '2014-10-04 05:00:00', '0000-00-00 00:00:00'),
(3, 'Remote', 3, 0, 3, '{"description":"Remote control of device to provide support."}', '', '2014-10-04 05:00:00', '0000-00-00 00:00:00'),
(4, 'Workshop', 3, 0, 4, '{"description":"Time spent in workshop working on issue."}', '', '2014-10-04 05:00:00', '0000-00-00 00:00:00'),
(5, 'Onsite', 3, 0, 5, '{"description":"Time spent onsite working on issue."}', '', '2014-10-04 05:00:00', '0000-00-00 00:00:00');
*/