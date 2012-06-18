ALTER TABLE  `%TABLE_PREFIX%config` CHANGE  `show_answered_tickets`  `show_answered_tickets` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0';
ALTER TABLE  `%TABLE_PREFIX%config` ADD  `show_notes_inline` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '1' AFTER  `show_answered_tickets`;
-- update patch signature
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='c2d2fabfdf15e1632f00850ffb361558';
