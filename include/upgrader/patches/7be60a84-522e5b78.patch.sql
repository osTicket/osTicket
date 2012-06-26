/**
 * @version v1.7-DPR1 (P1)
 */ 
UPDATE `%TABLE_PREFIX%email_template`
    SET `ticket_overlimit_subj` = 'Open Tickets Limit Reached'
    WHERE `tpl_id` = 1 AND `cfg_id` = 1;

UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='522e5b783c2824c67222260ee22baa93';
