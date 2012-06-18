/**
 * Add column for default paper size when printing tickets from the system
 * 
 * @version 1.7-rc1 default-paper-size
 */

ALTER TABLE %TABLE_PREFIX%staff ADD
    `default_paper_size` ENUM( 'Letter', 'Legal', 'Ledger', 'A4', 'A3' ) NOT NULL DEFAULT 'Letter'
    AFTER `default_signature_type`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='aa4664afc3b43d4068eb2e82684fc28e';
