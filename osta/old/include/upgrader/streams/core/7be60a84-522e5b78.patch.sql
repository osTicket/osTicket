/**
 * No longer necessary -- don't clobber email templates for previous
 * osTicket administrators
 *
 * @version v1.7-DPR1 (P1)
 */

UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='522e5b783c2824c67222260ee22baa93';
