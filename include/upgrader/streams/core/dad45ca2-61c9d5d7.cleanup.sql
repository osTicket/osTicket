-- Port data from the ticket table
-- 1. Create form entries for each ticket
INSERT INTO `%TABLE_PREFIX%form_entry` (
    `form_id`, `object_id`, `object_type`, `sort`, `created`, `updated`)
    SELECT (SELECT id FROM %TABLE_PREFIX%form WHERE `type`='T'),
        `ticket_id`, 'T', 10, `created`, `updated`
    FROM `%TABLE_PREFIX%ticket`;

-- 2. Copy subject lines from the ticket table into form entry
INSERT INTO `%TABLE_PREFIX%form_entry_values` (
    `field_id`, `entry_id`, `value`)
    SELECT A3.`id`, A2.`id`, A1.`subject`
    FROM `%TABLE_PREFIX%ticket` A1
        INNER JOIN `%TABLE_PREFIX%form_entry` A2 ON (A2.`object_id`
                = A1.`ticket_id` AND A2.`object_type` = 'T')
        INNER JOIN `%TABLE_PREFIX%form` A4 ON (A4.`id` = A2.`form_id`)
        INNER JOIN `%TABLE_PREFIX%form_field` A3 ON (A3.`form_id`
                = A4.`id`)
    WHERE A3.`name` = 'subject';

-- 2b. Copy priority from ticket to custom form entry
INSERT INTO `%TABLE_PREFIX%form_entry_values` (
    `field_id`, `entry_id`, `value`, `value_id`)
    SELECT A3.`id`, A2.`id`, A5.`priority_desc`, A1.`priority_id`
    FROM `%TABLE_PREFIX%ticket` A1
        INNER JOIN `%TABLE_PREFIX%form_entry` A2 ON (A2.`object_id`
                = A1.`ticket_id` AND A2.`object_type` = 'T')
        INNER JOIN `%TABLE_PREFIX%form` A4 ON (A4.`id` = A2.`form_id`)
        INNER JOIN `%TABLE_PREFIX%form_field` A3 ON (A3.`form_id`
                = A4.`id`)
        INNER JOIN `%TABLE_PREFIX%ticket_priority` A5 ON (A5.`priority_id`
                = A1.`priority_id`)
    WHERE A3.`name` = 'priority';

-- 3. Create <user> accounts for everybody
--      - Start with creating email addresses for the accounts
INSERT INTO `%TABLE_PREFIX%user_email` (`address`)
    SELECT DISTINCT `email` FROM `%TABLE_PREFIX%ticket`;

--      - Then create the accounts and link the `default_email`s
INSERT INTO `%TABLE_PREFIX%user` (`name`, `default_email_id`, `created`, `updated`)
    SELECT MAX(`name`), A2.`id`, A1.`created`, A1.`updated`
    FROM `%TABLE_PREFIX%ticket` A1
        INNER JOIN `%TABLE_PREFIX%user_email` A2 ON (A1.`email` = A2.`address`)
    GROUP BY A2.`id`;

--      - Now link the user and user_email tables
ALTER TABLE `%TABLE_PREFIX%user` ADD KEY `def_eml_id` (`default_email_id`, `id`);
UPDATE `%TABLE_PREFIX%user_email` A1
    SET user_id = (
        SELECT A2.`id` FROM `%TABLE_PREFIX%user` A2
        WHERE `default_email_id` = A1.`id`);
ALTER TABLE `%TABLE_PREFIX%user` DROP INDEX `def_eml_id`;

--      - Update the ticket table
UPDATE `%TABLE_PREFIX%ticket` A1
    JOIN `%TABLE_PREFIX%user_email` A2 ON A2.`address` = A1.`email`
    SET A1.`user_id` = A2.`user_id`,
        A1.`user_email_id` = A2.`id`;

-- TODO: Move this to a client info dynamic entry
-- 4. Create form entries for each ticket
INSERT INTO `%TABLE_PREFIX%form_entry` (
    `form_id`, `object_id`, `object_type`, `sort`, `created`, `updated`)
    SELECT DISTINCT A2.`id`, `user_id`, 'U', 10, MIN(A1.`created`),
        MAX(A1.`updated`)
    FROM `%TABLE_PREFIX%ticket` A1
    JOIN `%TABLE_PREFIX%form` A2 ON (A2.`type` = 'U')
    GROUP BY `user_id`, A2.`id`;

-- 5. Copy Phone from the ticket table to section #1
INSERT INTO `%TABLE_PREFIX%form_entry_values` (
    `field_id`, `entry_id`, `value`)
    SELECT A3.`id`, A2.`id`, MAX(CONCAT(
        REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE(
            A1.`phone`,
            ' ', ''),
            ')', ''),
            '(', ''),
            '+', ''),
            '-', ''),
            '.', ''), 'X', A1.`phone_ext`))
    FROM `%TABLE_PREFIX%ticket` A1
        INNER JOIN `%TABLE_PREFIX%user` A5 ON (A5.`id` = A1.`user_id`)
        INNER JOIN `%TABLE_PREFIX%form_entry` A2 ON (A2.`object_id`
                = A5.`id` AND A2.`object_type` = 'U')
        INNER JOIN `%TABLE_PREFIX%form` A4 ON (A4.`id` = A2.`form_id`)
        INNER JOIN `%TABLE_PREFIX%form_field` A3 ON (A3.`form_id`
                = A4.`id`)
    WHERE A3.`name` = 'phone' AND LENGTH(A1.`phone`)
    GROUP BY A3.`id`, A2.`id`;

-- 6. Remove columns from ticket table
ALTER TABLE `%TABLE_PREFIX%ticket`
    DROP COLUMN `name`,
    DROP COLUMN `email`,
    DROP COLUMN `phone`,
    DROP COLUMN `phone_ext`,
    DROP COLUMN `subject`,
    DROP COLUMN `priority_id`;

-- 5. Cleanup ticket table with dropped varchar columns
OPTIMIZE TABLE `%TABLE_PREFIX%ticket`;
