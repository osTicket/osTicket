/**
 * @version v1.9.0
 * @signature 8f99b8bf9bee63c8e4dc274ffbdda383
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

ALTER TABLE `%TABLE_PREFIX%organization`
    CHANGE `staff_id` `manager` varchar(16) NOT NULL DEFAULT '',
    CHANGE `domain` `domain` varchar(256) NOT NULL DEFAULT '';

UPDATE `%TABLE_PREFIX%config`
    SET `value` = '8f99b8bf9bee63c8e4dc274ffbdda383'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
