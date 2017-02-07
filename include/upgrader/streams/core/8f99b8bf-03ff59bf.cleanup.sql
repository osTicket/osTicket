DELETE FROM `%TABLE_PREFIX%config`
    WHERE `namespace`='core' AND `key` = 'random_ticket_ids';

ALTER TABLE `%TABLE_PREFIX%ticket`
    DROP COLUMN `status`;

-- Regenerate the CDATA table with the new format for 1.9.4
DROP TABLE IF EXISTS `%TABLE_PREFIX%ticket__cdata`;

OPTIMIZE TABLE `%TABLE_PREFIX%ticket`;
