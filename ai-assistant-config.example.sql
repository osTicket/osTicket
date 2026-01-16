-- ==========================================
-- AI Assistant Configuration Example
-- Microsoft 365 Copilot with Azure AD OAuth
-- ==========================================
--
-- This file contains example SQL commands to configure
-- the AI Assistant feature with Microsoft 365 Copilot API
-- and Azure AD (Entra ID) authentication.
--
-- INSTRUCTIONS:
-- 1. Copy this file: cp ai-assistant-config.example.sql ai-assistant-config.sql
-- 2. Edit ai-assistant-config.sql with your actual Azure AD values
-- 3. Run: mysql -u username -p database_name < ai-assistant-config.sql
-- 4. DO NOT commit ai-assistant-config.sql to version control (contains secrets)
--
-- ==========================================

-- ==========================================
-- STEP 1: AZURE AD APP REGISTRATION
-- ==========================================
--
-- Before configuring, you must:
-- 1. Go to Azure Portal: https://portal.azure.com
-- 2. Navigate to "Azure Active Directory" (Microsoft Entra ID)
-- 3. Go to "App registrations" → "New registration"
-- 4. Register your application:
--    - Name: "osTicket AI Assistant"
--    - Supported account types: "Single tenant"
--    - Redirect URI: (leave empty for service-to-service)
-- 5. Note down the following from the Overview page:
--    - Application (client) ID
--    - Directory (tenant) ID
-- 6. Go to "Certificates & secrets" → "Client secrets" → "New client secret"
-- 7. Create a secret and note down the VALUE (not the ID)
-- 8. Go to "API permissions" → "Add a permission"
-- 9. Add Microsoft 365 Copilot API permissions (if available)
--    Or use Microsoft Graph API permissions for chat
--
-- ==========================================

-- ==========================================
-- STEP 2: REQUIRED AZURE AD CREDENTIALS
-- ==========================================

-- Enable the AI Assistant (set to 1 to enable, 0 to disable)
UPDATE `ost_config`
SET `value` = '1',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'enabled';

-- Set your Azure AD Tenant ID (Directory ID)
-- Example: '12345678-1234-1234-1234-123456789012'
-- IMPORTANT: Replace 'YOUR_TENANT_ID_HERE' with your actual Tenant ID
UPDATE `ost_config`
SET `value` = 'YOUR_TENANT_ID_HERE',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'tenant_id';

-- Set your Application (Client) ID from Azure AD app registration
-- Example: '87654321-4321-4321-4321-210987654321'
-- IMPORTANT: Replace 'YOUR_CLIENT_ID_HERE' with your actual Client ID
UPDATE `ost_config`
SET `value` = 'YOUR_CLIENT_ID_HERE',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'client_id';

-- Set your Client Secret VALUE (not the Secret ID)
-- This is the secret you created in "Certificates & secrets"
-- Example: 'AbC123~XyZ789.QwE456-RtY012'
-- IMPORTANT: Replace 'YOUR_CLIENT_SECRET_HERE' with your actual Client Secret
-- WARNING: This is sensitive! Keep it secure and never commit to version control
UPDATE `ost_config`
SET `value` = 'YOUR_CLIENT_SECRET_HERE',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'client_secret';

-- ==========================================
-- STEP 3: OPTIONAL AI MODEL SETTINGS
-- ==========================================

-- AI Model Selection
-- Options: 'gpt-4', 'gpt-3.5-turbo', 'gpt-4-turbo'
-- gpt-4: Most accurate, higher cost, slower
-- gpt-4-turbo: Faster gpt-4, good balance
-- gpt-3.5-turbo: Fastest, lower cost, less accurate
UPDATE `ost_config`
SET `value` = 'gpt-4',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'model';

-- Temperature (Controls response creativity/randomness)
-- Range: 0.0 to 1.0
-- 0.0: Very focused and deterministic (best for factual Q&A)
-- 0.3: Slightly creative (recommended for support)
-- 0.7: Balanced creativity
-- 1.0: Maximum creativity and variation
UPDATE `ost_config`
SET `value` = '0.7',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'temperature';

-- Maximum Tokens (Controls response length)
-- Range: 1 to 4000 (depends on model)
-- 500: Short, concise responses
-- 1000: Medium length (recommended)
-- 2000: Detailed, comprehensive responses
-- 4000: Very detailed (higher cost)
UPDATE `ost_config`
SET `value` = '1000',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'max_tokens';

-- Rate Limit (Requests per staff member per hour)
-- Range: 1 to 1000 (recommended: 5-20)
-- 5: Very restrictive, best for cost control
-- 10: Moderate, good for most use cases
-- 20: Generous, for active support teams
-- 50+: Liberal, monitor costs carefully
UPDATE `ost_config`
SET `value` = '10',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'rate_limit';

-- ==========================================
-- STEP 4: VERIFY CONFIGURATION
-- ==========================================

-- Run this query to see all AI Assistant settings
SELECT
    `key`,
    CASE
        WHEN `key` IN ('client_secret', 'token_cache') THEN '***REDACTED***'
        ELSE `value`
    END as `value`,
    `updated`
FROM `ost_config`
WHERE `namespace` = 'ai.assistant'
ORDER BY `key`;

-- Expected output (secrets will be redacted):
-- +---------------+----------------------------------+---------------------+
-- | key           | value                            | updated             |
-- +---------------+----------------------------------+---------------------+
-- | client_id     | 87654321-4321-...                | 2024-01-16 12:00:00 |
-- | client_secret | ***REDACTED***                   | 2024-01-16 12:00:00 |
-- | enabled       | 1                                | 2024-01-16 12:00:00 |
-- | max_tokens    | 1000                             | 2024-01-16 12:00:00 |
-- | model         | gpt-4                            | 2024-01-16 12:00:00 |
-- | rate_limit    | 10                               | 2024-01-16 12:00:00 |
-- | temperature   | 0.7                              | 2024-01-16 12:00:00 |
-- | tenant_id     | 12345678-1234-...                | 2024-01-16 12:00:00 |
-- | token_cache   | ***REDACTED***                   | 2024-01-16 12:00:00 |
-- | token_expires | 1705416000                       | 2024-01-16 12:00:00 |
-- +---------------+----------------------------------+---------------------+

-- ==========================================
-- STEP 5: TEST CONNECTION (OPTIONAL)
-- ==========================================

-- After configuration, test the connection:
-- 1. Log in to osTicket staff panel
-- 2. Open any ticket
-- 3. The AI Assistant panel should appear
-- 4. Try asking a question to verify OAuth works
--
-- If you encounter errors:
-- - Check Azure AD app permissions
-- - Verify tenant_id, client_id, and client_secret are correct
-- - Ensure client secret hasn't expired
-- - Check firewall allows connections to login.microsoftonline.com
--
-- ==========================================

-- ==========================================
-- TROUBLESHOOTING COMMANDS
-- ==========================================

-- Clear cached OAuth token (useful if token issues occur)
UPDATE `ost_config`
SET `value` = '',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'token_cache';

UPDATE `ost_config`
SET `value` = '0',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'token_expires';

-- Disable AI Assistant temporarily
UPDATE `ost_config`
SET `value` = '0',
    `updated` = NOW()
WHERE `namespace` = 'ai.assistant'
  AND `key` = 'enabled';

-- View recent AI usage
SELECT
    DATE(created) as date,
    COUNT(*) as requests,
    COUNT(DISTINCT staff_id) as unique_staff
FROM `ost_ai_log`
WHERE created >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created)
ORDER BY date DESC;

-- ==========================================
-- CONFIGURATION COMPLETE
--
-- For detailed documentation, see:
-- - AI-ASSISTANT-README.md (comprehensive guide)
-- - AI-ASSISTANT-QUICKSTART.md (quick start)
--
-- For Azure AD help, see:
-- - https://learn.microsoft.com/en-us/entra/identity-platform/quickstart-register-app
-- - https://learn.microsoft.com/en-us/microsoft-365-copilot/extensibility/api/ai-services/chat/overview
-- ==========================================
