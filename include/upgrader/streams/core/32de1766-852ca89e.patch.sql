/**
 * @version v1.7.1
 * @signature 852ca89e1440e736d763b3b87f039bd7
 *
 *  - Changes config table to be key/value based and allows for
 *    configuration key clobbering by defining a namespace for the keys. The
 *    current configuration settings are stored in the 'core' namespace
 */

DROP TABLE IF EXISTS `%TABLE_PREFIX%_config`;
CREATE TABLE `%TABLE_PREFIX%_config` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `namespace` varchar(64) NOT NULL,
  `key` varchar(64) NOT NULL,
  `value` text NOT NULL,
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`namespace`, `key`)
) DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%_config` (`key`, `value`, `namespace`) VALUES
  ('isonline', (SELECT `isonline` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('enable_daylight_saving', (SELECT `enable_daylight_saving` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('staff_ip_binding', (SELECT `staff_ip_binding` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('staff_max_logins', (SELECT `staff_max_logins` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('staff_login_timeout', (SELECT `staff_login_timeout` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('staff_session_timeout', (SELECT `staff_session_timeout` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('passwd_reset_period', (SELECT `passwd_reset_period` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('client_max_logins', (SELECT `client_max_logins` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('client_login_timeout', (SELECT `client_login_timeout` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('client_session_timeout', (SELECT `client_session_timeout` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('max_page_size', (SELECT `max_page_size` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('max_open_tickets', (SELECT `max_open_tickets` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('max_file_size', (SELECT `max_file_size` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('max_user_file_uploads', (SELECT `max_user_file_uploads` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('max_staff_file_uploads', (SELECT `max_staff_file_uploads` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('autolock_minutes', (SELECT `autolock_minutes` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('overdue_grace_period', (SELECT `overdue_grace_period` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('alert_email_id', (SELECT `alert_email_id` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('default_email_id', (SELECT `default_email_id` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('default_dept_id', (SELECT `default_dept_id` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('default_sla_id', (SELECT `default_sla_id` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('default_priority_id', (SELECT `default_priority_id` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('default_template_id', (SELECT `default_template_id` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('default_timezone_id', (SELECT `default_timezone_id` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('default_smtp_id', (SELECT `default_smtp_id` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('allow_email_spoofing', (SELECT `allow_email_spoofing` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('clickable_urls', (SELECT `clickable_urls` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('allow_priority_change', (SELECT `allow_priority_change` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('use_email_priority', (SELECT `use_email_priority` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('enable_kb', (SELECT `enable_kb` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('enable_premade', (SELECT `enable_premade` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('enable_captcha', (SELECT `enable_captcha` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('enable_auto_cron', (SELECT `enable_auto_cron` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('enable_mail_polling', (SELECT `enable_mail_polling` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('send_sys_errors', (SELECT `send_sys_errors` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('send_sql_errors', (SELECT `send_sql_errors` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('send_mailparse_errors', (SELECT `send_mailparse_errors` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('send_login_errors', (SELECT `send_login_errors` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('save_email_headers', (SELECT `save_email_headers` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('strip_quoted_reply', (SELECT `strip_quoted_reply` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('log_ticket_activity', (SELECT `log_ticket_activity` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('ticket_autoresponder', (SELECT `ticket_autoresponder` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('message_autoresponder', (SELECT `message_autoresponder` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('ticket_notice_active', (SELECT `ticket_notice_active` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('ticket_alert_active', (SELECT `ticket_alert_active` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('ticket_alert_admin', (SELECT `ticket_alert_admin` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('ticket_alert_dept_manager', (SELECT `ticket_alert_dept_manager` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('ticket_alert_dept_members', (SELECT `ticket_alert_dept_members` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('message_alert_active', (SELECT `message_alert_active` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('message_alert_laststaff', (SELECT `message_alert_laststaff` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('message_alert_assigned', (SELECT `message_alert_assigned` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('message_alert_dept_manager', (SELECT `message_alert_dept_manager` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('note_alert_active', (SELECT `note_alert_active` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('note_alert_laststaff', (SELECT `note_alert_laststaff` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('note_alert_assigned', (SELECT `note_alert_assigned` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('note_alert_dept_manager', (SELECT `note_alert_dept_manager` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('transfer_alert_active', (SELECT `transfer_alert_active` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('transfer_alert_assigned', (SELECT `transfer_alert_assigned` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('transfer_alert_dept_manager', (SELECT `transfer_alert_dept_manager` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('transfer_alert_dept_members', (SELECT `transfer_alert_dept_members` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('overdue_alert_active', (SELECT `overdue_alert_active` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('overdue_alert_assigned', (SELECT `overdue_alert_assigned` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('overdue_alert_dept_manager', (SELECT `overdue_alert_dept_manager` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('overdue_alert_dept_members', (SELECT `overdue_alert_dept_members` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('assigned_alert_active', (SELECT `assigned_alert_active` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('assigned_alert_staff', (SELECT `assigned_alert_staff` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('assigned_alert_team_lead', (SELECT `assigned_alert_team_lead` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('assigned_alert_team_members', (SELECT `assigned_alert_team_members` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('auto_assign_reopened_tickets', (SELECT `auto_assign_reopened_tickets` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('show_related_tickets', (SELECT `show_related_tickets` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('show_assigned_tickets', (SELECT `show_assigned_tickets` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('show_answered_tickets', (SELECT `show_answered_tickets` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('show_notes_inline', (SELECT `show_notes_inline` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('hide_staff_name', (SELECT `hide_staff_name` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('overlimit_notice_active', (SELECT `overlimit_notice_active` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('email_attachments', (SELECT `email_attachments` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('allow_attachments', (SELECT `allow_attachments` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('allow_email_attachments', (SELECT `allow_email_attachments` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('allow_online_attachments', (SELECT `allow_online_attachments` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('allow_online_attachments_onlogin', (SELECT `allow_online_attachments_onlogin` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('random_ticket_ids', (SELECT `random_ticket_ids` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('log_level', (SELECT `log_level` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('log_graceperiod', (SELECT `log_graceperiod` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('upload_dir', (SELECT `upload_dir` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('allowed_filetypes', (SELECT `allowed_filetypes` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('time_format', (SELECT `time_format` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('date_format', (SELECT `date_format` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('datetime_format', (SELECT `datetime_format` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('daydatetime_format', (SELECT `daydatetime_format` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('reply_separator', (SELECT `reply_separator` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('admin_email', (SELECT `admin_email` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('helpdesk_title', (SELECT `helpdesk_title` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('helpdesk_url', (SELECT `helpdesk_url` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core')
, ('schema_signature', (SELECT `schema_signature` FROM `%TABLE_PREFIX%config` WHERE `id` = 1), 'core');

DROP TABLE `%TABLE_PREFIX%config`;
ALTER TABLE `%TABLE_PREFIX%_config` RENAME TO `%TABLE_PREFIX%config`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '852ca89e1440e736d763b3b87f039bd7'
	WHERE `key` = 'schema_signature' AND `namespace` = 'core';
