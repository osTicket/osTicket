/**
 * @version v1.7.1
 * @signature 8aeda901a16e08c3229f1ac6da568e02
 *
 *  - Transitional patch to fix DB ENGINE
 *
 *
 *
 */

UPDATE `%TABLE_PREFIX%config`
    SET `value` = '8aeda901a16e08c3229f1ac6da568e02'
	WHERE `key` = 'schema_signature' AND `namespace` = 'core';
