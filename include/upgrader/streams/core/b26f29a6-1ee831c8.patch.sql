/**
 * @signature 1ee831c854fe9f35115a3e672916bb91
 * @version v1.10.0
 * @title Make editable content translatable and add queues
 *
 * This patch adds support for translatable administratively editable
 * content, such as help topic names, department and group names, site page
 * and faq content, etc.
 *
 * This patch also transitions from the timezone table to the Olson timezone
 * database available in PHP 5.3.
 */

ALTER TABLE `%TABLE_PREFIX%attachment`
    ADD `lang` varchar(16) AFTER `inline`;

ALTER TABLE `%TABLE_PREFIX%staff`
    ADD `lang` varchar(16) DEFAULT NULL AFTER `signature`,
    ADD `timezone` varchar(64) default NULL AFTER `lang`,
    ADD `locale` varchar(16) DEFAULT NULL AFTER `timezone`,
    ADD `extra` text AFTER `default_paper_size`;

ALTER TABLE `%TABLE_PREFIX%user_account`
    ADD `timezone` varchar(64) DEFAULT NULL AFTER `status`,
    ADD `extra` text AFTER `backend`;

CREATE TABLE `%TABLE_PREFIX%translation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `object_hash` char(16) CHARACTER SET ascii DEFAULT NULL,
  `type` enum('phrase','article','override') DEFAULT NULL,
  `flags` int(10) unsigned NOT NULL DEFAULT '0',
  `revision` int(11) unsigned DEFAULT NULL,
  `agent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lang` varchar(16) NOT NULL DEFAULT '',
  `text` mediumtext NOT NULL,
  `source_text` text,
  `updated` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `type` (`type`,`lang`),
  KEY `object_hash` (`object_hash`)
) DEFAULT CHARSET=utf8;

-- Transition the current Timezone configuration to Olsen database

CREATE TABLE `%TABLE_PREFIX%_timezones` (
    `offset` int,
    `dst` tinyint(1) unsigned,
    `south` tinyint(1) unsigned default 0,
    `olson_name` varchar(32)
) DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%_timezones` (`offset`, `dst`, `olson_name`) VALUES
    -- source borrowed from the jstz project
    (-720, 0, 'Pacific/Majuro'),
    (-660, 0, 'Pacific/Pago_Pago'),
    (-600, 1, 'America/Adak'),
    (-600, 0, 'Pacific/Honolulu'),
    (-570, 0, 'Pacific/Marquesas'),
    (-540, 0, 'Pacific/Gambier'),
    (-540, 1, 'America/Anchorage'),
    (-480, 1, 'America/Los_Angeles'),
    (-480, 0, 'Pacific/Pitcairn'),
    (-420, 0, 'America/Phoenix'),
    (-420, 1, 'America/Denver'),
    (-360, 0, 'America/Guatemala'),
    (-360, 1, 'America/Chicago'),
    (-300, 0, 'America/Bogota'),
    (-300, 1, 'America/New_York'),
    (-270, 0, 'America/Caracas'),
    (-240, 1, 'America/Halifax'),
    (-240, 0, 'America/Santo_Domingo'),
    (-240, 1, 'America/Santiago'),
    (-210, 1, 'America/St_Johns'),
    (-180, 1, 'America/Godthab'),
    (-180, 0, 'America/Argentina/Buenos_Aires'),
    (-180, 1, 'America/Montevideo'),
    (-120, 0, 'America/Noronha'),
    (-120, 1, 'America/Noronha'),
    (-60,  1, 'Atlantic/Azores'),
    (-60,  0, 'Atlantic/Cape_Verde'),
    (0,    0, 'UTC'),
    (0,    1, 'Europe/London'),
    (60,   1, 'Europe/Berlin'),
    (60,   0, 'Africa/Lagos'),
    (120,  1, 'Asia/Beirut'),
    (120,  0, 'Africa/Johannesburg'),
    (180,  0, 'Asia/Baghdad'),
    (180,  1, 'Europe/Moscow'),
    (210,  1, 'Asia/Tehran'),
    (240,  0, 'Asia/Dubai'),
    (240,  1, 'Asia/Baku'),
    (270,  0, 'Asia/Kabul'),
    (300,  1, 'Asia/Yekaterinburg'),
    (300,  0, 'Asia/Karachi'),
    (330,  0, 'Asia/Kolkata'),
    (345,  0, 'Asia/Kathmandu'),
    (360,  0, 'Asia/Dhaka'),
    (360,  1, 'Asia/Omsk'),
    (390,  0, 'Asia/Rangoon'),
    (420,  1, 'Asia/Krasnoyarsk'),
    (420,  0, 'Asia/Jakarta'),
    (480,  0, 'Asia/Shanghai'),
    (480,  1, 'Asia/Irkutsk'),
    (525,  0, 'Australia/Eucla'),
    (525,  1, 'Australia/Eucla'),
    (540,  1, 'Asia/Yakutsk'),
    (540,  0, 'Asia/Tokyo'),
    (570,  0, 'Australia/Darwin'),
    (570,  1, 'Australia/Adelaide'),
    (600,  0, 'Australia/Brisbane'),
    (600,  1, 'Asia/Vladivostok'),
    (630,  1, 'Australia/Lord_Howe'),
    (660,  1, 'Asia/Kamchatka'),
    (660,  0, 'Pacific/Noumea'),
    (690,  0, 'Pacific/Norfolk'),
    (720,  1, 'Pacific/Auckland'),
    (720,  0, 'Pacific/Tarawa'),
    (765,  1, 'Pacific/Chatham'),
    (780,  0, 'Pacific/Tongatapu'),
    (780,  1, 'Pacific/Apia'),
    (840,  0, 'Pacific/Kiritimati');

-- XXX:
-- These zone have opposite DST interpretations and also have norther
-- hemisphere counterparts
INSERT INTO `%TABLE_PREFIX%_timezones` (`offset`, `dst`, `south`, `olson_name`) VALUES
    (-360, 1, 1, 'Pacific/Easter'),
    (60,   1, 1, 'Africa/Windhoek'),
    (600,  1, 1, 'Australia/Sydney');

UPDATE `%TABLE_PREFIX%staff` A1
    JOIN `%TABLE_PREFIX%timezone` A2 ON (A1.`timezone_id` = A2.`id`)
    JOIN `%TABLE_PREFIX%_timezones` A3 ON (A2.`offset` * 60 = A3.`offset`
        AND A1.`daylight_saving` = A3.`dst`
        AND A3.`south` = 0)
    SET A1.`timezone` = A3.`olson_name`;

UPDATE `%TABLE_PREFIX%user_account` A1
    JOIN `%TABLE_PREFIX%timezone` A2 ON (A1.`timezone_id` = A2.`id`)
    JOIN `%TABLE_PREFIX%_timezones` A3 ON (A2.`offset` * 60 = A3.`offset`
        AND A1.`dst` = A3.`dst`
        AND A3.`south` = 0)
    SET A1.`timezone` = A3.`olson_name`;

-- Update system default timezone
SET @default_timezone_id = (
    SELECT `value` FROM `%TABLE_PREFIX%config` A1
    WHERE A1.`key` = 'default_timezone_id'
      AND A1.`namespace` = 'core'
);
SET @enable_daylight_saving = (
    SELECT `value` FROM `%TABLE_PREFIX%config` A1
    WHERE A1.`key` = 'enable_daylight_saving'
      AND A1.`namespace` = 'core'
);

UPDATE `%TABLE_PREFIX%config` A1
    JOIN `%TABLE_PREFIX%timezone` A2 ON (@default_timezone_id = A2.`id`)
    JOIN `%TABLE_PREFIX%_timezones` A3 ON (A2.`offset` * 60 = A3.`offset`
        AND @enable_daylight_saving = A3.`dst`
        AND A3.`south` = 0)
    SET A1.`value` = A3.`olson_name`
    WHERE A1.`key` = 'default_timezone_id'
      AND A1.`namespace` = 'core';

UPDATE `%TABLE_PREFIX%config` A1
    SET A1.`key` = 'default_timezone'
    WHERE A1.`key` = 'default_timezone_id'
      AND A1.`namespace` = 'core';

DROP TABLE %TABLE_PREFIX%_timezones;

ALTER TABLE `%TABLE_PREFIX%ticket`
    ADD `est_duedate` datetime default NULL AFTER `duedate`,
    ADD `lastupdate` datetime default NULL AFTER `lastresponse`;

UPDATE `%TABLE_PREFIX%ticket` A1
    LEFT JOIN `%TABLE_PREFIX%sla` A2 ON (A1.sla_id = A2.id)
    SET A1.`est_duedate` =
        COALESCE(A1.`duedate`, A1.`created` + INTERVAL A2.`grace_period` HOUR),
      A1.`lastupdate` =
        CAST(GREATEST(IFNULL(A1.lastmessage, 0), IFNULL(A1.closed, 0), IFNULL(A1.reopened, 0), A1.created) as DATETIME);

CREATE TABLE `%TABLE_PREFIX%queue` (
  `id` int(11) unsigned not null auto_increment,
  `parent_id` int(11) unsigned not null default 0,
  `flags` int(11) unsigned not null default 0,
  `staff_id` int(11) unsigned not null default 0,
  `sort` int(11) unsigned not null default 0,
  `title` varchar(60),
  `config` text,
  `created` datetime not null,
  `updated` datetime not null,
  primary key (`id`)
) DEFAULT CHARSET=utf8;

-- Add flags field to form field
ALTER TABLE  `%TABLE_PREFIX%form_field`
    ADD  `flags` INT UNSIGNED NOT NULL DEFAULT  '1' AFTER  `form_id`;

-- Flag field stored in the system elsewhere as nonstorable locally.
UPDATE `%TABLE_PREFIX%form_field` A1 JOIN `%TABLE_PREFIX%form` A2 ON(A2.id=A1.form_id)
    SET A1.`flags` = 3
    WHERE A2.`type` = 'U' AND A1.`name` IN('name','email');

UPDATE `%TABLE_PREFIX%form_field` A1 JOIN `%TABLE_PREFIX%form` A2 ON(A2.id=A1.form_id)
    SET A1.`flags`=3
    WHERE A2.`type`='O' AND A1.`name` IN('name');

-- Thread entry field is stored externally
UPDATE `%TABLE_PREFIX%form_field` A1 JOIN `%TABLE_PREFIX%form` A2 ON(A2.id=A1.form_id)
    SET A1.`flags`=3
    WHERE A2.`type`='T' AND A1.`name` IN ('message');

-- Coalesce to zero here in case the config option has never been saved
set @client_edit = coalesce(
    (select value from `%TABLE_PREFIX%config` where `key` =
    'allow_client_updates'), 0);

-- Transfer previous visibility and requirement settings to new flag field
UPDATE `%TABLE_PREFIX%form_field` SET `flags` = `flags` |
     CASE WHEN `private` = 0 and @client_edit = 1 THEN CONV(3300, 16, 10)
          WHEN `private` = 0 and @client_edit = 0 THEN CONV(3100, 16, 10)
          WHEN `private` = 1 THEN CONV(3000, 16, 10)
          WHEN `private` = 2 and @client_edit = 1 THEN CONV(300, 16, 10)
          WHEN `private` = 2 and @client_edit = 0 THEN CONV(100, 16, 10) END
   | CASE WHEN `required` = 0 THEN 0
          WHEN `required` = 1 THEN CONV(4400, 16, 10)
          WHEN `required` = 2 THEN CONV(400, 16, 10)
          WHEN `required` = 3 THEN CONV(4000, 16, 10) END
   | IF(`edit_mask` & 1, CONV(20, 16, 10), 0)
   | IF(`edit_mask` & 2, CONV(40000, 16, 10), 0)
   | IF(`edit_mask` & 4, CONV(10000, 16, 10), 0)
   | IF(`edit_mask` & 8, CONV(20000, 16, 10), 0)
   | IF(`edit_mask` & 16, CONV(10, 16, 10), 0)
   | IF(`edit_mask` & 32, CONV(40, 16, 10), 0);

-- Detect inline images not recorded as inline
CREATE TABLE `%TABLE_PREFIX%_unknown_inlines` AS
  SELECT A2.`attach_id`
  FROM `%TABLE_PREFIX%file` A1
  JOIN `%TABLE_PREFIX%ticket_attachment` A2 ON (A1.id = A2.file_id)
  JOIN `%TABLE_PREFIX%ticket_thread` A3 ON (A3.ticket_id = A2.ticket_id)
  WHERE A1.`type` LIKE 'image/%' AND A2.inline = 0
    AND A3.body LIKE CONCAT('%"cid:', A1.key, '"%');

UPDATE `%TABLE_PREFIX%ticket_attachment` A1
  JOIN %TABLE_PREFIX%_unknown_inlines A2 ON (A1.attach_id = A2.attach_id)
  SET A1.inline = 1;

DROP TABLE `%TABLE_PREFIX%_unknown_inlines`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '1ee831c854fe9f35115a3e672916bb91'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
