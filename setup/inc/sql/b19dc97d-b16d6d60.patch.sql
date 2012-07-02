/**
 * Support canned response definition for email filters
 *
 * @version 1.7-rc1 canned-response-in-filter
 */

ALTER TABLE `%TABLE_PREFIX%email_filter`
    ADD `canned_response_id` int(11) unsigned NOT NULL default '0'
        AFTER `disable_autoresponder`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='71e05961fdb7a993a21704ae513512bc';
