-- ==========================================
-- AI Assistant Uninstallation SQL
-- Removes AI Assistant tables and settings
-- ==========================================
--
-- WARNING: This will delete all AI interaction logs
-- Make a backup before running this script
--
-- Usage:
-- mysql -u username -p database_name < ai-assistant-uninstall.sql
--
-- ==========================================

-- Remove foreign key constraints first
ALTER TABLE `ost_ai_log` DROP FOREIGN KEY IF EXISTS `ai_log_ibfk_1`;
ALTER TABLE `ost_ai_log` DROP FOREIGN KEY IF EXISTS `ai_log_ibfk_2`;

-- Drop AI Log Table
DROP TABLE IF EXISTS `ost_ai_log`;

-- Remove configuration settings
DELETE FROM `ost_config` WHERE `namespace` = 'ai.assistant';

-- ==========================================
-- Uninstallation Complete
-- ==========================================
