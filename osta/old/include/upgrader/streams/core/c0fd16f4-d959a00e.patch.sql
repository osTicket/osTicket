/**
 * @version v1.7
 *
 * @schema d959a00e55c75e0c903b9e37324fd25d
 */

-- Add cron exec service
ALTER TABLE  `%TABLE_PREFIX%api_key`
    ADD  `can_exec_cron` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '1' AFTER  `can_create_tickets`;

-- Drop email piping settings from config table.
ALTER TABLE  `%TABLE_PREFIX%config` 
    DROP  `enable_email_piping`;

-- update schema signature
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='d959a00e55c75e0c903b9e37324fd25d';
