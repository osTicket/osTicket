/**
 * @signature c37e165651dc289240fee7d244990ac1
 * @version v1.16
 * @title PHP 8 Support
 *
 * This patch updates the `ost_help_topic`.`topic` length from 32 to 128.
 */

-- Update topic length
ALTER TABLE `%TABLE_PREFIX%help_topic`
    CHANGE `topic` `topic` VARCHAR(128) NOT NULL default '';

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'c37e165651dc289240fee7d244990ac1'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
