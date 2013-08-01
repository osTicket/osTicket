/**
 * @version v1.8.0
 * @signature 43fbf5b9254787c93d049be0b607cf2d
 *
 * Make default data translatable. This patch also adds columns to database
 * tables to introduce the concept of a language.
 *
 */

ALTER TABLE `%TABLE_PREFIX%email_template_group`
    ADD `lang` varchar(16) NOT NULL default 'en_US' AFTER `name`;

ALTER TABLE `%TABLE_PREFIX%email_template`
    ADD `notes` text AFTER `body`;

ALTER TABLE `%TABLE_PREFIX%canned_response`
    ADD `lang` varchar(16) NOT NULL default 'en_US' AFTER `response`;

ALTER TABLE `%TABLE_PREFIX%page`
    ADD `lang` varchar(16) NOT NULL default 'en_US' AFTER `body`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '43fbf5b9254787c93d049be0b607cf2d'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
