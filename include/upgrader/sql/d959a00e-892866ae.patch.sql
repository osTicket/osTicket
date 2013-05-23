/**
 * The database install script changed to support installation on cluster
 * servers. No significant changes need to be rolled for continuous updaters
 *
 * @version v1.7.1
 * @signature 892866ae9af89d40415b738bbde54a15
 */

ALTER TABLE `%TABLE_PREFIX%session`
   CHANGE `session_id` `session_id` VARCHAR(255) collate ascii_general_ci,
   CHANGE `session_data` `session_data` BLOB;

-- update schema signature
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='892866ae9af89d40415b738bbde54a15';
