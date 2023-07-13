/**
 * @version v1.17
 * @signature 83a22ba22b1a6a624fcb1da03882ac1b
 * @title Move Plugin Config store back to central config
 *
 */

UPDATE `%TABLE_PREFIX%config`
    SET `value` = '83a22ba22b1a6a624fcb1da03882ac1b', updated = NOW()
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
