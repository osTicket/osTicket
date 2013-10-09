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
