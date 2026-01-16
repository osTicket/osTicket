-- ==========================================
-- AI Assistant Installation SQL
-- Creates tables for AI interaction logging
-- ==========================================
--
-- Run this SQL to install AI Assistant tables
-- Replace 'ost_' with your table prefix if different
--
-- Usage:
-- mysql -u username -p database_name < ai-assistant-install.sql
--
-- ==========================================

-- Create AI Log Table
CREATE TABLE IF NOT EXISTS `ost_ai_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) unsigned NOT NULL,
  `staff_id` int(11) unsigned NOT NULL,
  `question` text NOT NULL,
  `response` text NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `staff_id` (`staff_id`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraints (optional, comment out if you don't want constraints)
ALTER TABLE `ost_ai_log`
  ADD CONSTRAINT `ai_log_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `ost_ticket` (`ticket_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ai_log_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `ost_staff` (`staff_id`) ON DELETE CASCADE;

-- Insert default configuration settings
INSERT IGNORE INTO `ost_config` (`namespace`, `key`, `value`, `updated`) VALUES
('ai.assistant', 'enabled', '0', NOW()),
('ai.assistant', 'api_key', '', NOW()),
('ai.assistant', 'model', 'gpt-4', NOW()),
('ai.assistant', 'temperature', '0.7', NOW()),
('ai.assistant', 'max_tokens', '1000', NOW()),
('ai.assistant', 'rate_limit', '10', NOW());

-- ==========================================
-- Installation Complete
-- ==========================================
