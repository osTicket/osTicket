-- ==========================================
-- AI Assistant Installation SQL
-- Microsoft 365 Copilot with Azure AD Authentication
-- ==========================================
--
-- This script creates configuration for AI Assistant
-- that uses Microsoft 365 Copilot API with Azure AD OAuth
--
-- NO DATABASE TABLES CREATED - Configuration only
--
-- Usage:
-- mysql -u username -p database_name < ai-assistant-install.sql
--
-- ==========================================

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

-- Rate Limiting (session-based, no database)
('ai.assistant', 'rate_limit', '10', NOW()),   -- Requests per hour per staff member

-- OAuth Token Cache (managed automatically)
('ai.assistant', 'token_cache', '', NOW()),    -- Cached OAuth access token
('ai.assistant', 'token_expires', '0', NOW()); -- Token expiration timestamp

-- ==========================================
-- Installation Complete
--
-- NOTE: This installation does NOT create any database tables.
--       - Rate limiting uses PHP sessions (not database)
--       - No interaction logging to database
--       - All data is session-based and configuration-based
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
