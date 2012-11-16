/**
 * @version v1.7 RC4
 * @signature c2a64ea46d1fb749f5d908820bb813a0
 *
 * Supports starts- and ends-with in email filter rules
 *  
 */

ALTER TABLE  `%TABLE_PREFIX%filter_rule` CHANGE  `how`  `how` ENUM(  'equal',
    'not_equal',  'contains',  'dn_contain',  'starts',  'ends' )

UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='c2a64ea46d1fb749f5d908820bb813a0';
