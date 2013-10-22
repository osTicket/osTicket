/**
 * @signature dad45ca24a3800102f8b92c7279347db
 * @version 1.8.0 - HTML ticket thread
 *
 * Migrate to a single attachment table to allow for inline image support
 * with an almost countless number of attachment tables to support what is
 * attached to what
 */

DROP TABLE IF EXISTS `%TABLE_PREFIX%attachment`;
CREATE TABLE `%TABLE_PREFIX%attachment` (
  `object_id` int(11) unsigned NOT NULL,
  `type` char(1) NOT NULL,
  `file_id` int(11) unsigned NOT NULL,
  `inline` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`object_id`,`file_id`,`type`)
) DEFAULT CHARSET=utf8;

-- Migrate canned attachments
INSERT INTO `%TABLE_PREFIX%attachment`
  (`object_id`, `type`, `file_id`, `inline`)
  SELECT `canned_id`, 'C', `file_id`, 0
  FROM `%TABLE_PREFIX%canned_attachment`;

DROP TABLE `%TABLE_PREFIX%canned_attachment`;

-- Migrate faq attachments
INSERT INTO `%TABLE_PREFIX%attachment`
  (`object_id`, `type`, `file_id`, `inline`)
  SELECT `faq_id`, 'F', `file_id`, 0
  FROM `%TABLE_PREFIX%faq_attachment`;

DROP TABLE `%TABLE_PREFIX%faq_attachment`;

DROP TABLE IF EXISTS `%TABLE_PREFIX%draft`;
CREATE TABLE `%TABLE_PREFIX%draft` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) unsigned NOT NULL,
  `namespace` varchar(32) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

-- Migrate email templates to HTML
UPDATE `%TABLE_PREFIX%email_template`
    SET `body` = REPLACE( REPLACE( REPLACE( REPLACE(
        `body`,
        '&', '&amp;'),
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>');

UPDATE `%TABLE_PREFIX%email_template`
    SET `body` = CONCAT('<div>', `body`, '</div>');

-- Migrate notes to HTML
UPDATE `%TABLE_PREFIX%api_key`
    SET `notes` = REPLACE( REPLACE( REPLACE( REPLACE(
        `notes`,
        '&', '&amp;'),
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>');
UPDATE `%TABLE_PREFIX%email`
    SET `notes` = REPLACE( REPLACE( REPLACE( REPLACE(
        `notes`,
        '&', '&amp;'),
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>');
UPDATE `%TABLE_PREFIX%email_template_group`
    SET `notes` = REPLACE( REPLACE( REPLACE( REPLACE(
        `notes`,
        '&', '&amp;'),
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>');
UPDATE `%TABLE_PREFIX%faq`
    SET `notes` = REPLACE( REPLACE( REPLACE( REPLACE(
        `notes`,
        '&', '&amp;'),
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>');
UPDATE `%TABLE_PREFIX%faq_category`
    SET `notes` = REPLACE( REPLACE( REPLACE( REPLACE(
        `notes`,
        '&', '&amp;'),
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>');
UPDATE `%TABLE_PREFIX%filter`
    SET `notes` = REPLACE( REPLACE( REPLACE( REPLACE(
        `notes`,
        '&', '&amp;'),
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>');
UPDATE `%TABLE_PREFIX%groups`
    SET `notes` = REPLACE( REPLACE( REPLACE( REPLACE(
        `notes`,
        '&', '&amp;'),
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>');
UPDATE `%TABLE_PREFIX%help_topic`
    SET `notes` = REPLACE( REPLACE( REPLACE( REPLACE(
        `notes`,
        '&', '&amp;'),
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>');
UPDATE `%TABLE_PREFIX%page`
    SET `notes` = REPLACE( REPLACE( REPLACE( REPLACE(
        `notes`,
        '&', '&amp;'),
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>');
UPDATE `%TABLE_PREFIX%sla`
    SET `notes` = REPLACE( REPLACE( REPLACE( REPLACE(
        `notes`,
        '&', '&amp;'),
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>');
UPDATE `%TABLE_PREFIX%staff`
    SET `notes` = REPLACE( REPLACE( REPLACE( REPLACE(
        `notes`,
        '&', '&amp;'),
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>');
UPDATE `%TABLE_PREFIX%team`
    SET `notes` = REPLACE( REPLACE( REPLACE( REPLACE(
        `notes`,
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>'),
        '&', '&amp;');

-- Migrate canned responses to HTML
UPDATE `%TABLE_PREFIX%canned_response`
    SET `notes` = REPLACE( REPLACE( REPLACE( REPLACE(
        `notes`,
        '&', '&amp;'),
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>'),
    `response` = REPLACE( REPLACE( REPLACE( REPLACE(
        `response`,
        '&', '&amp;'),
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>');

-- Migrate ticket-thread to HTML
-- XXX: Migrate & -> &amp; ? -- the problem is that there's a fix in 1.7.1
-- that properly encodes these characters, so encoding & would mean possible
-- double encoding.
UPDATE `%TABLE_PREFIX%ticket_thread`
    SET `body` = REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE(
        `body`,
        '\r', ''),
        '\n ', '\n'),
        '\n\n\n', '\n\n'),
        '\n\n\n', '\n\n'),
        '\n\n\n', '\n\n'),
        '<', '&lt;'),
        '>', '&gt;'),
        '\n', '<br/>');

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'dad45ca24a3800102f8b92c7279347db'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
