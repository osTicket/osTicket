/**
 * @signature ec19794f1fc8d6a54ac217d6e8006a85
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

-- Migrate email templates to HTML
UPDATE `%TABLE_PREFIX%email_template`
    SET `body` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;',
                REPLACE('&', '&amp;', `body`))));

-- Migrate notes to HTML
UPDATE `%TABLE_PREFIX%api_key`
    SET `notes` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;',
                REPLACE('&', '&amp;', `notes`))));
UPDATE `%TABLE_PREFIX%email`
    SET `notes` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;',
                REPLACE('&', '&amp;', `notes`))));
UPDATE `%TABLE_PREFIX%email_template_group`
    SET `notes` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;',
                REPLACE('&', '&amp;', `notes`))));
UPDATE `%TABLE_PREFIX%faq`
    SET `notes` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;',
                REPLACE('&', '&amp;', `notes`))));
UPDATE `%TABLE_PREFIX%faq_category`
    SET `notes` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;',
                REPLACE('&', '&amp;', `notes`))));
UPDATE `%TABLE_PREFIX%filter`
    SET `notes` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;',
                REPLACE('&', '&amp;', `notes`))));
UPDATE `%TABLE_PREFIX%groups`
    SET `notes` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;',
                REPLACE('&', '&amp;', `notes`))));
UPDATE `%TABLE_PREFIX%help_topic`
    SET `notes` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;',
                REPLACE('&', '&amp;', `notes`))));
UPDATE `%TABLE_PREFIX%page`
    SET `notes` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;',
                REPLACE('&', '&amp;', `notes`))));
UPDATE `%TABLE_PREFIX%sla`
    SET `notes` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;',
                REPLACE('&', '&amp;', `notes`))));
UPDATE `%TABLE_PREFIX%staff`
    SET `notes` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;',
                REPLACE('&', '&amp;', `notes`))));
UPDATE `%TABLE_PREFIX%team`
    SET `notes` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;',
                REPLACE('&', '&amp;', `notes`))));

-- Migrate canned responses to HTML
UPDATE `%TABLE_PREFIX%canned_response`
    SET `notes` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;',
                REPLACE('&', '&amp;', `notes`)))),
    `response` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;',
                REPLACE('&', '&amp;', `response`))));

-- Migrate ticket-thread to HTML
-- XXX: Migrate & -> &amp; ? -- the problem is that there's a fix in 1.7.1
-- that properly encodes these characters, so encoding & would mean possible
-- double encoding.
UPDATE `%TABLE_PREFIX%ticket_thread`
    SET `body` = REPLACE('\n', '<br/>',
        REPLACE('<', '&lt;',
            REPLACE('>', '&gt;', `body`)));

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = 'ec19794f1fc8d6a54ac217d6e8006a85'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
