/**
 * @version v1.7 RC4
 * @signature c2a64ea46d1fb749f5d908820bb813a0
 *
 *  - Supports starts- and ends-with in ticket filter rules
 *  - Fix assigned template variable
 */

ALTER TABLE  `%TABLE_PREFIX%filter_rule` CHANGE  `how`  `how` ENUM(  'equal',
    'not_equal',  'contains',  'dn_contain',  'starts',  'ends' );

-- %message
UPDATE `%TABLE_PREFIX%email_template`
    SET `assigned_alert_body` = REPLACE(`assigned_alert_body`, '%message', '%{comments}');

UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='c2a64ea46d1fb749f5d908820bb813a0';
