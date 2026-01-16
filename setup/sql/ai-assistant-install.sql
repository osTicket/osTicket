-- ==========================================
-- AI Assistant Installation SQL
-- Microsoft 365 Copilot with Azure AD Authentication
-- ==========================================
--
-- This script creates tables and configuration for AI Assistant
-- that uses Microsoft 365 Copilot API with Azure AD OAuth
--
-- Usage:
-- mysql -u username -p database_name < ai-assistant-install.sql
--
-- IMPORTANT: Replace 'ost_' with your table prefix if different
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

-- Insert default configuration settings for Microsoft 365 Copilot
INSERT IGNORE INTO `ost_config` (`namespace`, `key`, `value`, `updated`) VALUES
-- Feature toggle
('ai.assistant', 'enabled', '0', NOW()),

-- Azure AD (Entra ID) Credentials
-- These must be obtained from Azure Portal
('ai.assistant', 'tenant_id', '', NOW()),      -- Your Azure AD Tenant ID
('ai.assistant', 'client_id', '', NOW()),      -- Application (client) ID from app registration
('ai.assistant', 'client_secret', '', NOW()),  -- Client secret value from app registration

-- AI Model Settings
('ai.assistant', 'model', 'gpt-4', NOW()),
('ai.assistant', 'temperature', '0.7', NOW()),
('ai.assistant', 'max_tokens', '1000', NOW()),

-- Rate Limiting
('ai.assistant', 'rate_limit', '10', NOW()),   -- Requests per hour per staff member

-- OAuth Token Cache (managed automatically)
('ai.assistant', 'token_cache', '', NOW()),    -- Cached OAuth access token
('ai.assistant', 'token_expires', '0', NOW()); -- Token expiration timestamp

-- ==========================================
-- Installation Complete
--
-- NEXT STEPS:
-- 1. Register an app in Azure Portal (portal.azure.com)
-- 2. Configure API permissions for Microsoft 365 Copilot
-- 3. Generate a client secret
-- 4. Update configuration with your credentials:
--    - tenant_id
--    - client_id
--    - client_secret
-- 5. Enable the feature (set 'enabled' to '1')
--
-- See AI-ASSISTANT-README.md for detailed instructions
-- ==========================================
