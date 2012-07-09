/**
 * @version v1.7-DPR2-P2 
 */
UPDATE `%TABLE_PREFIX%sla`
    SET `created` = NOW(),
        `updated` = NOW()
    WHERE `created` IS NULL;

UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='02decaa20c10c9615558762018e25507';
