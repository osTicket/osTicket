/**
 * @version v1.8.0-dpr1 Dynamic Forms
 * @signature 61c9d5d71093d3595b3d41855386a905
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
    `edit_mask` tinyint(1) NOT NULL DEFAULT 0,
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
    KEY `entry_lookup` (`object_type`, `object_id`)
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
) DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%user_email`;
CREATE TABLE `%TABLE_PREFIX%user_email` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL,
  `address` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `address` (`address`),
  KEY `user_email_lookup` (`user_id`)
) DEFAULT CHARSET=utf8;

ALTER TABLE `%TABLE_PREFIX%filter_rule`
    CHANGE `what` `what` varchar(32) NOT NULL;

ALTER TABLE `%TABLE_PREFIX%help_topic`
    ADD `form_id` int(11) unsigned NOT NULL default '0' AFTER `sla_id`;

ALTER TABLE `%TABLE_PREFIX%ticket`
    ADD `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `ticket_id`,
    ADD `user_email_id` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `user_id`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '61c9d5d71093d3595b3d41855386a905'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
