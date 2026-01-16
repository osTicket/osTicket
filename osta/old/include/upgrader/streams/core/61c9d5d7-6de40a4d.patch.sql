/**
 * @version 1.8-stable
 * @signature 6de40a4d5bad7a2923e769a4db1ff3b9
 *
 * Cleanup old and no longer used config settings
 */

DELETE FROM `%TABLE_PREFIX%config` WHERE `namespace`='core' and `key` IN (
    'upload_dir',
    'clickable_urls',
    'allow_priority_change',
    'log_ticket_activity',
    'overdue_grace_period',
    'allow_email_spoofing',
    'show_notes_inline'
);

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '6de40a4d5bad7a2923e769a4db1ff3b9'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
