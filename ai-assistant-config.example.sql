-- ==========================================
-- AI Assistant Configuration Example
-- ==========================================
--
-- This file contains example SQL commands to configure
-- the AI Assistant feature in osTicket.
--
-- INSTRUCTIONS:
-- 1. Copy this file: cp ai-assistant-config.example.sql ai-assistant-config.sql
-- 2. Edit ai-assistant-config.sql with your actual values
-- 3. Run: mysql -u username -p database_name < ai-assistant-config.sql
-- 4. DO NOT commit ai-assistant-config.sql to version control (contains API key)
--
-- ==========================================

-- ==========================================
-- REQUIRED SETTINGS
-- ==========================================

-- Enable the AI Assistant (set to 1 to enable, 0 to disable)
UPDATE `ost_config`
SET `value` = '1',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'enabled';

-- Set your GitHub Copilot API Key
-- IMPORTANT: Replace 'YOUR_GITHUB_COPILOT_API_KEY_HERE' with your actual API key
UPDATE `ost_config`
SET `value` = 'YOUR_GITHUB_COPILOT_API_KEY_HERE',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'api_key';

-- ==========================================
-- OPTIONAL SETTINGS
-- ==========================================

-- AI Model Selection
-- Options: 'gpt-4', 'gpt-3.5-turbo'
-- gpt-4: More accurate, higher cost
-- gpt-3.5-turbo: Faster, lower cost
UPDATE `ost_config`
SET `value` = 'gpt-4',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'model';

-- Temperature (Controls response creativity)
-- Range: 0.0 to 1.0
-- 0.0: More focused and deterministic
-- 0.7: Balanced (recommended)
-- 1.0: More creative and varied
UPDATE `ost_config`
SET `value` = '0.7',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'temperature';

-- Maximum Tokens (Controls response length)
-- Range: 1 to 4000
-- 500: Short, concise responses
-- 1000: Medium length (recommended)
-- 2000: Detailed, comprehensive responses
UPDATE `ost_config`
SET `value` = '1000',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'max_tokens';

-- Rate Limit (Requests per staff member per hour)
-- Range: 1 to 1000 (recommended: 5-20)
-- 5: Very restrictive, for cost control
-- 10: Moderate (recommended for most)
-- 20: Generous, for heavy users
-- 50+: Liberal, watch costs carefully
UPDATE `ost_config`
SET `value` = '10',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'rate_limit';

-- ==========================================
-- VERIFY CONFIGURATION
-- ==========================================

-- Run this query to see all AI Assistant settings
SELECT
    `key`,
    `value`,
    `updated`
FROM `ost_config`
WHERE `namespace` = 'ai.assistant'
ORDER BY `key`;

-- Expected output:
-- +-------------+----------------------------------+---------------------+
-- | key         | value                            | updated             |
-- +-------------+----------------------------------+---------------------+
-- | api_key     | ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx | 2024-01-16 12:00:00 |
-- | enabled     | 1                                | 2024-01-16 12:00:00 |
-- | max_tokens  | 1000                             | 2024-01-16 12:00:00 |
-- | model       | gpt-4                            | 2024-01-16 12:00:00 |
-- | rate_limit  | 10                               | 2024-01-16 12:00:00 |
-- | temperature | 0.7                              | 2024-01-16 12:00:00 |
-- +-------------+----------------------------------+---------------------+

-- ==========================================
-- CONFIGURATION COMPLETE
-- ==========================================
