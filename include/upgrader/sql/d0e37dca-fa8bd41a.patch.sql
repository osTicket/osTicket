/**
 * @version v1.7 RC3
 * @signature 58ef694d5ebf73cc291e07e597c6f85d
 *
 *  Upgrade from 1.6 RC3 + filters
 *  
 */

RENAME TABLE  `%TABLE_PREFIX%email_filter` TO  `%TABLE_PREFIX%filter`;

RENAME TABLE  `%TABLE_PREFIX%email_filter_rule` TO  `%TABLE_PREFIX%filter_rule`;

ALTER TABLE  `%TABLE_PREFIX%filter` 
    ADD  `target` ENUM(  'All',  'Web',  'Email',  'API' ) NOT NULL DEFAULT  'All' AFTER  `sla_id` ,
    ADD INDEX (  `target` );

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='58ef694d5ebf73cc291e07e597c6f85d';
