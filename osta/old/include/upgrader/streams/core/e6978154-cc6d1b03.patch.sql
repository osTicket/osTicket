/**
* @signature cc6d1b03792f91fc3b6bc1d85bdc9813
* @version v1.14.0
* @title Staff/User Email Length
*
* This patch increases the length of the Staff/User email address columns.
* In addition it updates the configuration for Contact Form > Email Address
* field to allow 255 characters.
*
*/

-- Increase Staff Email Address length
ALTER TABLE `%TABLE_PREFIX%staff` MODIFY COLUMN `email` VARCHAR(255);

-- Increase User Email Address length
ALTER TABLE `%TABLE_PREFIX%user_email` MODIFY COLUMN `address` VARCHAR(255);

-- Update Contact Form > Email Address field length
UPDATE `%TABLE_PREFIX%form_field` SET `configuration` = REPLACE(`configuration`,'"length":64','"length":255') WHERE `name` = 'email';

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
  SET `value` = 'cc6d1b03792f91fc3b6bc1d85bdc9813', `updated` = NOW()
  WHERE `key` = 'schema_signature' AND `namespace` = 'core';
