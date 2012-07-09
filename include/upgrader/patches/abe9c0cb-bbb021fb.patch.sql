/**
 * Add an 'annulled' column to the %ticket_event table to assist in tracking
 * real statistics for reopened and closed tickets -- the events should not
 * count more than one time.
 *
 * @version 1.7-dpr3 ticket-event-annul
 */

ALTER TABLE `%TABLE_PREFIX%ticket_event`
    ADD `annulled` tinyint(1) NOT NULL DEFAULT '0' AFTER `staff`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='bbb021fbeb377ca66b6997b77e0167cc';
