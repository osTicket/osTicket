DELETE FROM  `%TABLE_PREFIX%config`
    WHERE  `key` = 'properties' AND  `namespace` LIKE  'TS.%';
