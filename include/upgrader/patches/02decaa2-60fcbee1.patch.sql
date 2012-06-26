-- Update all temlates with the new wording.
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_overlimit_body` = '%name\r\n\r\nYou have reached the maximum number of open tickets allowed.\r\n\r\nTo be able to open another ticket, one of your pending tickets must be closed. To update or add comments to an open ticket simply login using the link below.\r\n\r\n%url/view.php?e=%email\r\n\r\nThank you.\r\n\r\nSupport Ticket System';

UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='60fcbee1da3180d1b690187aa5006c88';
