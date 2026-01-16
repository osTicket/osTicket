-- ==========================================
-- AI Assistant Uninstallation SQL
-- Removes AI Assistant configuration
-- ==========================================
--
-- This removes all AI Assistant configuration settings
--
-- NO DATABASE TABLES TO DROP - Configuration only
--
-- Usage:
-- mysql -u username -p database_name < ai-assistant-uninstall.sql
--
-- ==========================================

-- Remove configuration settings
DELETE FROM `ost_config` WHERE `namespace` = 'ai.assistant';

-- ==========================================
-- Uninstallation Complete
--
-- NOTE: This only removes configuration settings.
--       No database tables were created, so none to drop.
--       Session-based rate limiting data clears automatically.
--
-- To completely remove the AI Assistant feature:
-- 1. Run this SQL script (done)
-- 2. Remove code files (see AI-ASSISTANT-README.md)
-- 3. Clear osTicket cache
-- ==========================================
