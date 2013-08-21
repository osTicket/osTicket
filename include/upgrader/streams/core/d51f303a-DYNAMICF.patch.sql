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

DROP TABLE IF EXISTS `%TABLE_PREFIX%dynamic_formset`;
CREATE TABLE `%TABLE_PREFIX%dynamic_formset` (
    `id` int(11) unsigned auto_increment,
    `title` varchar(255) NOT NULL,
    `instructions` varchar(512),
    `notes` text,
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%dynamic_formset_sections`;
CREATE TABLE `%TABLE_PREFIX%dynamic_formset_sections` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `formset_id` int(11) NOT NULL,
    `section_id` int(11) NOT NULL,
    `title` varchar(255),
    `instructions` text,
    -- Allow more than one form, sorted in this order
    `sort` int(11) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%dynamic_form_section`;
CREATE TABLE `%TABLE_PREFIX%dynamic_form` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `title` varchar(255) NOT NULL,
    `instructions` varchar(512),
    `notes` text,
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%dynamic_form_field`;
CREATE TABLE `%TABLE_PREFIX%dynamic_form_field` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `section_id` int(11) unsigned NOT NULL,
    `type` varchar(255) NOT NULL DEFAULT 'text',
    `label` varchar(255) NOT NULL,
    `required` tinyint(1) NOT NULL DEFAULT 0,
    `private` tinyint(1) NOT NULL DEFAULT 0,
    `name` varchar(64) NOT NULL,
    `configuration` text,
    `sort` int(11) unsigned NOT NULL,
    `hint` varchar(512),
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- Create a default form to mimic the previous default form of osTicket < 1.7.1
INSERT INTO `%TABLE_PREFIX%dynamic_form_section` SET
    `id` = 1, `title` = 'User Information', `created` = NOW(),
    `updated` = NOW();
INSERT INTO `%TABLE_PREFIX%dynamic_form_section` SET
    `id` = 2, `title` = 'Ticket Details', `created` = NOW(),
    `updated` = NOW();

INSERT INTO `%TABLE_PREFIX%dynamic_formset` SET
    `id` = 1, `title` = 'Default', `created` = NOW(), `updated` = NOW();

INSERT INTO `%TABLE_PREFIX%dynamic_formset_sections` SET
    `formset_id` = 1, `section_id` = 1, `sort` = 10;
INSERT INTO `%TABLE_PREFIX%dynamic_formset_sections` SET
    `formset_id` = 1, `section_id` = 2, `sort` = 20;

INSERT INTO `%TABLE_PREFIX%dynamic_form_field` SET
    `section_id` = 1, `type` = 'text', `label` = 'Email Address',
    `required` = 1, `configuration` = '{"size":40,"length":120,"validator":"email"}',
    `name` = 'email', `sort` = 10, `created` = NOW(), `updated` = NOW();
INSERT INTO `%TABLE_PREFIX%dynamic_form_field` SET
    `section_id` = 1, `type` = 'text', `label` = 'Full Name',
    `required` = 1, `configuration` = '{"size":40,"length":32}',
    `name` = 'name', `sort` = 20, `created` = NOW(), `updated` = NOW();
INSERT INTO `%TABLE_PREFIX%dynamic_form_field` SET
    `section_id` = 1, `type` = 'phone', `label` = 'Phone Number',
    `name` = 'phone', `sort` = 30, `created` = NOW(), `updated` = NOW();

INSERT INTO `%TABLE_PREFIX%dynamic_form_field` SET
    `section_id` = 2, `type` = 'text', `label` = 'Subject',
    `hint` = 'Issue summary', `required` = 1,
    `configuration` = '{"size":40,"length":64}',
    `name` = 'subject', `sort` = 10, `created` = NOW(), `updated` = NOW();

DROP TABLE IF EXISTS `%TABLE_PREFIX%dynamic_form_entry`;
CREATE TABLE `%TABLE_PREFIX%dynamic_form_entry` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `section_id` int(11) unsigned NOT NULL,
    `ticket_id` int(11) unsigned,
    `sort` int(11) unsigned NOT NULL DEFAULT 1,
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `ticket_dyn_form_lookup` (`ticket_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%dynamic_form_entry_values`;
CREATE TABLE `%TABLE_PREFIX%dynamic_form_entry_values` (
    -- references dynamic_form_entry.id
    `entry_id` int(11) unsigned NOT NULL,
    `field_id` int(11) unsigned NOT NULL,
    `value` text,
    PRIMARY KEY (`entry_id`, `field_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%dynamic_list`;
CREATE TABLE `%TABLE_PREFIX%dynamic_list` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `name_plural` varchar(255),
    `sort_mode` enum('Alpha', '-Alpha', 'SortCol') NOT NULL DEFAULT 'Alpha',
    `notes` text,
    `created` datetime NOT NULL,
    `updated` datetime NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%dynamic_list_items`;
CREATE TABLE `%TABLE_PREFIX%dynamic_list_items` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `list_id` int(11),
    `value` varchar(255) NOT NULL,
    -- extra value such as abbreviation
    `extra` varchar(255),
    `sort` int(11) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `dynamic_list_item_lookup` (`list_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

ALTER TABLE `%TABLE_PREFIX%help_topic`
  ADD `formset_id` int(11) unsigned NOT NULL default '0' AFTER `sla_id`;

-- All help topics will link to the default formset
UPDATE `%TABLE_PREFIX%help_topic` SET `formset_id` = 1;

-- Port data from the ticket table
-- 1. Create form entries for each ticket
INSERT INTO `%TABLE_PREFIX%dynamic_form_entry` (
    `section_id`, `ticket_id`, `sort`, `created`, `updated`)
    SELECT 1, `ticket_id`, 10, `created`, `updated`
    FROM `%TABLE_PREFIX%ticket`;

INSERT INTO `%TABLE_PREFIX%dynamic_form_entry` (
    `section_id`, `ticket_id`, `sort`, `created`, `updated`)
    SELECT 2, `ticket_id`, 20, `created`, `updated`
    FROM `%TABLE_PREFIX%ticket`;

-- 2. Copy Name, Email, and Phone from the ticket table to section #1
INSERT INTO `%TABLE_PREFIX%dynamic_form_entry_values` (
    `field_id`, `entry_id`, `value`)
    SELECT A3.`field_id`, A2.`id`, A1.`name`
    FROM `%TABLE_PREFIX%ticket` A1
        INNER JOIN `%TABLE_PREFIX%dynamic_form_entry` A2 ON (A1.`ticket_id`
                = A2.`ticket_id` AND A2.`section_id` = 1),
        INNER JOIN `%TABLE_PREFIX%dynamic_form_field` A3 ON (A2.`section_id`
                = A3.`section_id`)
    WHERE A3.`name` = 'name' AND LENGTH(A1.`name`);

INSERT INTO `%TABLE_PREFIX%dynamic_form_entry_values` (
    `field_id`, `entry_id`, `value`)
    SELECT A3.`field_id`, A2.`id`, A1.`email`
    FROM `%TABLE_PREFIX%ticket` A1
        INNER JOIN `%TABLE_PREFIX%dynamic_form_entry` A2 ON (A1.`ticket_id`
                = A2.`ticket_id` AND A2.`section_id` = 1),
        INNER JOIN `%TABLE_PREFIX%dynamic_form_field` A3 ON (A2.`section_id`
                = A3.`section_id`)
    WHERE A3.`name` = 'email' AND LENGTH(A1.`email`);

INSERT INTO `%TABLE_PREFIX%dynamic_form_entry_values` (
    `field_id`, `entry_id`, `value`)
    SELECT A3.`field_id`, A2.`id`, CONCAT(A1.`phone`, 'X', A1.`phone_ext`)
    FROM `%TABLE_PREFIX%ticket` A1
        INNER JOIN `%TABLE_PREFIX%dynamic_form_entry` A2 ON (A1.`ticket_id`
                = A2.`ticket_id` AND A2.`section_id` = 1),
        INNER JOIN `%TABLE_PREFIX%dynamic_form_field` A3 ON (A2.`section_id`
                = A3.`section_id`)
    WHERE A3.`name` = 'phone' AND LENGTH(A1.`phone`);

-- 3. Copy subject lines from the ticket table into section #2
INSERT INTO `%TABLE_PREFIX%dynamic_form_entry_values` (
    `field_id`, `entry_id`, `value`)
    SELECT A3.`field_id`, A2.`id`, A1.`subject`
    FROM `%TABLE_PREFIX%ticket` A1
        INNER JOIN `%TABLE_PREFIX%dynamic_form_entry` A2 ON (A1.`ticket_id`
                = A2.`ticket_id` AND A2.`section_id` = 2),
        INNER JOIN `%TABLE_PREFIX%dynamic_form_field` A3 ON (A2.`section_id`
                = A3.`section_id`)
    WHERE A3.`name` = 'subject';

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
