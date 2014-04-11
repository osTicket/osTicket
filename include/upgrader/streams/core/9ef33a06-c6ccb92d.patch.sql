/**
 * @version v1.8.2
 * @signature 87a26394310ce54b8edf57a970c488e6
 * @title Move organization support from UserAccount to User model.
 *
 */

ALTER TABLE `%TABLE_PREFIX%user`
    ADD `org_id` int(11) unsigned NOT NULL AFTER  `id`,
    ADD `status` int(11) unsigned NOT NULL DEFAULT 0 AFTER `default_email_id`,
    ADD INDEX (`org_id`);

ALTER TABLE `%TABLE_PREFIX%user_account`
    DROP `org_id`,
    ADD INDEX (`user_id`);

ALTER TABLE `%TABLE_PREFIX%ticket`
    ADD INDEX (`user_id`);

ALTER TABLE `%TABLE_PREFIX%draft`
    ADD `extra` text AFTER `body`;

UPDATE `%TABLE_PREFIX%config`
    SET `value` = '87a26394310ce54b8edf57a970c488e6'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
