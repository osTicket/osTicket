/**
 * Support canned response definition for email filters
 *
 * @version 1.7-rc1 canned-response-in-filter
 */

ALTER TABLE `%TABLE_PREFIX%email_filter`
    ADD `canned_response_id` int(11) unsigned NOT NULL default '0'
        AFTER `disable_autoresponder`;

-- Add index for linking responses to messages quickly
ALTER TABLE `%TABLE_PREFIX%ticket_thread` ADD KEY `pid` (`pid`);

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='435c62c3b23795529bcfae7e7371d82e';
