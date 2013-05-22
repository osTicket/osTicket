/**
 * The database install script changed to support installation on cluster
 * servers. No significant changes need to be rolled for continuous updaters
 *
 * @version v1.7.1
 * @signature 557cc9f9a663c56c259604ee1fe2e1fd
 */

-- update schema signature
UPDATE `%TABLE_PREFIX%config`
    SET `schema_signature`='557cc9f9a663c56c259604ee1fe2e1fd';
