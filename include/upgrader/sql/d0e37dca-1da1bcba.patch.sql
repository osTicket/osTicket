/**
 * @version v1.7 RC3
 * @signature 1da1bcbafcedc65efef58f142a48ac91
 *
 *  Upgrade from 1.6 RC3 + filters
 *  
 */

RENAME TABLE  `%TABLE_PREFIX%email_filter` TO  `%TABLE_PREFIX%filter`;

RENAME TABLE  `%TABLE_PREFIX%email_filter_rule` TO  `%TABLE_PREFIX%filter_rule`;

ALTER TABLE  `%TABLE_PREFIX%filter` CHANGE  `reject_email`  `reject_ticket` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0';

ALTER TABLE  `%TABLE_PREFIX%filter` 
    ADD  `target` ENUM(  'Any',  'Web',  'Email',  'API' ) NOT NULL DEFAULT  'Any' AFTER  `sla_id` ,
    ADD INDEX (  `target` );

UPDATE `%TABLE_PREFIX%filter` SET `target` = 'Email' WHERE `email_id` != 0;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='1da1bcbafcedc65efef58f142a48ac91';
