/**
 * @version v1.7 RC3
 * @signature fa8bd41a6fbee9f2bd97c59f4d0778ba
 *
 *  Upgrade from 1.6 RC3 + filters
 *  
 */

RENAME TABLE  `%TABLE_PREFIX%email_filter` TO  `%TABLE_PREFIX%filter`;

RENAME TABLE  `%TABLE_PREFIX%email_filter_rule` TO  `%TABLE_PREFIX%filter_rule`;

ALTER TABLE  `%TABLE_PREFIX%filter` CHANGE  `reject_email`  `reject_ticket` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0';

ALTER TABLE  `%TABLE_PREFIX%filter` 
    ADD  `target` ENUM(  'All',  'Web',  'Email',  'API' ) NOT NULL DEFAULT  'All' AFTER  `sla_id` ,
    ADD INDEX (  `target` );

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='fa8bd41a6fbee9f2bd97c59f4d0778ba';
