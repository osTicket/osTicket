/**
 * @signature 959aca6ed189cd918d227a3ea8a135a3
 * @version v1.9.6
 * @title Retire `private`, `required`, and `edit_mask` for fields
 *
 */

ALTER TABLE `%TABLE_PREFIX%form_field`
    DROP `private`,
    DROP `required`,
    DROP `edit_mask`;

ALTER TABLE `%TABLE_PREFIX%content`
    DROP `lang`;

-- DROP IF EXISTS `%content.content_id`
SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = '%TABLE_PREFIX%content'
        AND table_schema = DATABASE()
        AND column_name = 'content_id'
    ) > 0,
    "ALTER TABLE `%TABLE_PREFIX%content` DROP `content_id`",
    "SELECT 1"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

-- DROP IF EXISTS `%task.sla_id`
SET @s = (SELECT IF(
    (SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE table_name = '%TABLE_PREFIX%task'
        AND table_schema = DATABASE()
        AND column_name = 'sla_id'
    ) > 0,
    "ALTER TABLE `%TABLE_PREFIX%task` DROP `sla_id`",
    "SELECT 1"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

-- Retire %team.[flag fields]
ALTER TABLE `%TABLE_PREFIX%team`
    DROP `isenabled`,
    DROP `noalerts`;

-- Retire %dept.[flag fields]
DELETE FROM `%TABLE_PREFIX%config`
WHERE `key`='assign_members_only' AND `namespace` LIKE 'dept.%';

-- Retire %sla.[flag fields]
ALTER TABLE `%TABLE_PREFIX%sla`
  DROP `isactive`,
  DROP `enable_priority_escalation`,
  DROP `disable_overdue_alerts`;

DELETE FROM `%TABLE_PREFIX%config`
WHERE `key`='transient' AND `namespace` LIKE 'sla.%';

DELETE FROM `%TABLE_PREFIX%config`
WHERE `key`='configuration' AND `namespace` LIKE 'list.%';

DELETE FROM `%TABLE_PREFIX%config`
WHERE `key`='name_format' AND `namespace` = 'core';

-- Orphan users who don't know they're orphans
UPDATE `%TABLE_PREFIX%user` A1
  LEFT JOIN `%TABLE_PREFIX%organization` A2 ON (A1.`org_id` = A2.`id`)
  SET A1.`org_id` = 0
  WHERE A2.`id` IS NULL;
