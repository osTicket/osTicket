/**
 * @version v1.7.1
 * @signature 0000000000000000000000000000000
 *
 * Adds the database structure for the dynamic forms feature and migrates
 * the database from the legacy <=1.7 format to the new format with the
 * dynamic forms feature. Basically, a default form is installed with the
 * fields found in the legacy version of osTicket, the data is migrated from
 * the fields in the ticket table to the new forms tables, and then the
 * fields are dropped from the ticket table.
 */

DROP TABLE IF EXISTS `%TABLE_PREFIX%form`;
CREATE TABLE `%TABLE_PREFIX%form` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `type` char(1) NOT NULL DEFAULT 'G',
    `deletable` tinyint(1) NOT NULL DEFAULT 1,
    `title` varchar(255) NOT NULL,
    `instructions` varchar(512),
    `notes` text,
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%form_field`;
CREATE TABLE `%TABLE_PREFIX%form_field` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `form_id` int(11) unsigned NOT NULL,
    `type` varchar(255) NOT NULL DEFAULT 'text',
    `label` varchar(255) NOT NULL,
    `required` tinyint(1) NOT NULL DEFAULT 0,
    `private` tinyint(1) NOT NULL DEFAULT 0,
    `edit_mask` tinyint(1) NOT NULL DEFAULT 1,
    `name` varchar(64) NOT NULL,
    `configuration` text,
    `sort` int(11) unsigned NOT NULL,
    `hint` varchar(512),
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%form_entry`;
CREATE TABLE `%TABLE_PREFIX%form_entry` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `form_id` int(11) unsigned NOT NULL,
    `object_id` int(11) unsigned,
    `object_type` char(1) NOT NULL DEFAULT 'T',
    `sort` int(11) unsigned NOT NULL DEFAULT 1,
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `ticket_dyn_form_lookup` (`ticket_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%form_entry_values`;
CREATE TABLE `%TABLE_PREFIX%form_entry_values` (
    -- references form_entry.id
    `entry_id` int(11) unsigned NOT NULL,
    `field_id` int(11) unsigned NOT NULL,
    `value` text,
    `value_id` int(11),
    PRIMARY KEY (`entry_id`, `field_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%list`;
CREATE TABLE `%TABLE_PREFIX%list` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `name_plural` varchar(255),
    `sort_mode` enum('Alpha', '-Alpha', 'SortCol') NOT NULL DEFAULT 'Alpha',
    `notes` text,
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%list_items`;
CREATE TABLE `%TABLE_PREFIX%list_items` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `list_id` int(11),
    `value` varchar(255) NOT NULL,
    -- extra value such as abbreviation
    `extra` varchar(255),
    `sort` int(11) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `list_item_lookup` (`list_id`)
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%user`;
CREATE TABLE `%TABLE_PREFIX%user` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `default_email_id` int(10) NOT NULL,
  `name` varchar(128) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`id`)
);

DROP TABLE IF EXISTS `%TABLE_PREFIX%user_email`;
CREATE TABLE `%TABLE_PREFIX%user_email` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL,
  `address` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `address` (`address`)
);

ALTER TABLE `%TABLE_PREFIX%filter_rule`
    CHANGE `what` `what` varchar(32) NOT NULL;

ALTER TABLE `%TABLE_PREFIX%help_topic`
  ADD `formset_id` int(11) unsigned NOT NULL default '0' AFTER `sla_id`;


-- Port data from the ticket table
-- 1. Create form entries for each ticket
INSERT INTO `%TABLE_PREFIX%form_entry` (
    `section_id`, `ticket_id`, `sort`, `created`, `updated`)
    SELECT 1, `ticket_id`, 10, `created`, `updated`
    FROM `%TABLE_PREFIX%ticket`;

-- 2. Copy subject lines from the ticket table into section #2
INSERT INTO `%TABLE_PREFIX%form_entry_values` (
    `field_id`, `entry_id`, `value`)
    SELECT A3.`field_id`, A2.`id`, A1.`subject`
    FROM `%TABLE_PREFIX%ticket` A1
        INNER JOIN `%TABLE_PREFIX%form_entry` A2 ON (A1.`ticket_id`
                = A2.`ticket_id` AND A2.`section_id` = 2),
        INNER JOIN `%TABLE_PREFIX%form_field` A3 ON (A2.`section_id`
                = A3.`section_id`)
    WHERE A3.`name` = 'subject';

-- TODO: Move this to a client info dynamic entry
-- 3. Copy Phone from the ticket table to section #1
INSERT INTO `%TABLE_PREFIX%form_entry_values` (
    `field_id`, `entry_id`, `value`)
    SELECT A3.`field_id`, A2.`id`, CONCAT(A1.`phone`, 'X', A1.`phone_ext`)
    FROM `%TABLE_PREFIX%ticket` A1
        INNER JOIN `%TABLE_PREFIX%form_entry` A2 ON (A1.`ticket_id`
                = A2.`ticket_id` AND A2.`section_id` = 1),
        INNER JOIN `%TABLE_PREFIX%form_field` A3 ON (A2.`section_id`
                = A3.`section_id`)
    WHERE A3.`name` = 'phone' AND LENGTH(A1.`phone`);

-- 4. Create <user> accounts for everybody
--      - Start with creating email addresses for the accounts
INSERT INTO `%TABLE_PREFIX%user_email` (`address`)
    SELECT DISTINCT `email` FROM `%TABLE_PREFIX%ticket`;

--      - Then create the accounts and link the `default_email`s
INSERT INTO `%TABLE_PREFIX%user` (`first`, `default_email_id`)
    SELECT MAX(`name`), A2.`id`
    FROM `%TABLE_PREFIX%ticket` A1
        INNER JOIN `%TABLE_PREFIX%user_email` A2 ON (A1.`email` = A2.`address`);
    GROUP BY A2.`id`

--      - Now link the user and user_email tables
ALTER TABLE `%TABLE_PREFIX%user` ADD KEY `def_eml_id` (`default_email_id`, `id`);
UPDATE `%TABLE_PREFIX%user_email` A1
    SET user_id = (
        SELECT A2.`id` FROM `%TABLE_PREFIX%user` A2
        WHERE `default_email_id` = A1.`id`);
ALTER TABLE `%TABLE_PREFIX%user` DROP INDEX `def_eml_id`;

--      - Update the ticket table
ALTER TABLE `%TABLE_PREFIX%ticket`
    ADD `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `ticket_id`,
    ADD `user_email_id` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `user_id`;

UPDATE `%TABLE_PREFIX%ticket` A1
    JOIN `%TABLE_PREFIX%user_email` A2 ON A2.`address` = A1.`email`
    SET `user_id` = A2.`user_id`,
        `user_email_id` = A2.`id`;

-- 4. Remove columns from ticket table
ALTER TABLE `%TABLE_PREFIX%ticket`
    DROP COLUMN `name`,
    DROP COLUMN `email`,
    DROP COLUMN `phone`,
    DROP COLUMN `phone_ext`,
    DROP COLUMN `subject`;

-- 5. Cleanup ticket table with dropped varchar columns
OPTIMIZE TABLE `%TABLE_PREFIX%ticket`;

-- update schema signature.
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='0000000000000000000000000000000';
