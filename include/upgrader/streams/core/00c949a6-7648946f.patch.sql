/**
 * @signature 7648946f2bbccb665c4c3050a6a4e5e8
 * @version v1.10.2
 * @title SLA Active Hours
 * @author Tyler Robinson
 */
ALTER TABLE `%TABLE_PREFIX%sla`
  ADD `sun_mode` int(10) unsigned NOT NULL DEFAULT 1,
  ADD `sun_start_time` varchar(5) NOT NULL DEFAULT 0,
  ADD `sun_end_time` varchar(5) NOT NULL DEFAULT 0,
  ADD `mon_mode` int(10) unsigned NOT NULL DEFAULT 1,
  ADD `mon_start_time` varchar(5) NOT NULL DEFAULT 0,
  ADD `mon_end_time` varchar(5) NOT NULL DEFAULT 0,
  ADD `tue_mode` int(10) unsigned NOT NULL DEFAULT 1,
  ADD `tue_start_time` varchar(5) NOT NULL DEFAULT 0,
  ADD `tue_end_time` varchar(5) NOT NULL DEFAULT 0,
  ADD `wed_mode` int(10) unsigned NOT NULL DEFAULT 1,
  ADD `wed_start_time` varchar(5) NOT NULL DEFAULT 0,
  ADD `wed_end_time` varchar(5) NOT NULL DEFAULT 0,
  ADD `thu_mode` int(10) unsigned NOT NULL DEFAULT 1,
  ADD `thu_start_time` varchar(5) NOT NULL DEFAULT 0,
  ADD `thu_end_time` varchar(5) NOT NULL DEFAULT 0,
  ADD `fri_mode` int(10) unsigned NOT NULL DEFAULT 1,
  ADD `fri_start_time` varchar(5) NOT NULL DEFAULT 0,
  ADD `fri_end_time` varchar(5) NOT NULL DEFAULT 0,
  ADD `sat_mode` int(10) unsigned NOT NULL DEFAULT 1,
  ADD `sat_start_time` varchar(5) NOT NULL DEFAULT 0,
  ADD `sat_end_time` varchar(5) NOT NULL DEFAULT 0;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '7648946f2bbccb665c4c3050a6a4e5e8'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';