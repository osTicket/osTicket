/**
 *  Add help topic nesting support.
 *
 * @version 1.7-rc2 - nested help topics.
 */

-- Add help topic parent id.
ALTER TABLE  `%TABLE_PREFIX%help_topic` 
    ADD  `topic_pid` INT(10) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `topic_id` ,
    ADD INDEX (  `topic_pid` );

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='d0e37dca324648f1ce2d10528a6026d4';
