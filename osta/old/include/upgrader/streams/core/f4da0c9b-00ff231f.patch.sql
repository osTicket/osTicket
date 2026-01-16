/*
 * @version=1.7RC2+
 * 
 * Add auto-reply template.
 */

ALTER TABLE  `%TABLE_PREFIX%email_template` 
    ADD  `ticket_autoreply_subj` VARCHAR( 255 ) NOT NULL AFTER  `ticket_autoresp_body` ,
    ADD  `ticket_autoreply_body` TEXT NOT NULL AFTER  `ticket_autoreply_subj`;

UPDATE `%TABLE_PREFIX%email_template` 
    SET `ticket_autoreply_subj`='Support Ticket Opened [#%{ticket.number}]',
        `ticket_autoreply_body`='%{ticket.name},\r\n\r\nA request for support has been created and assigned ticket #%{ticket.number} with the following auto-reply:\r\n\r\n%{response}\r\n\r\n\r\nWe hope this response has sufficiently answered your questions. If not, please do not open another ticket. If need be, representative will follow-up with you as soon as possible.\r\n\r\nYou can view this ticket''s progress online here: %{ticket.client_link}.';

 -- update schema signature
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='00ff231f2ade8797a0e7f2a7fccd52f4';
