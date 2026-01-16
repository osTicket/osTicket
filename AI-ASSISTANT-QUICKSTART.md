# AI Assistant Quick Start Guide
## Microsoft 365 Copilot with Azure AD OAuth

## 15-Minute Setup

### Prerequisites
- osTicket 1.17.x or 1.18.x installed
- MySQL/MariaDB database access
- Azure AD admin access
- Command line access to server

### Installation Steps

#### 1. Azure AD App Registration (5 minutes)

**Create the App:**

1. Go to [Azure Portal](https://portal.azure.com)
2. Navigate to **Azure Active Directory** → **App registrations**
3. Click **"New registration"**
4. Name it: **"osTicket AI Assistant"**
5. Account type: **"Single tenant"**
6. Click **"Register"**

**Copy these values from Overview page:**
```
Application (client) ID: ________________________________________
Directory (tenant) ID:   ________________________________________
```

**Create Client Secret:**

1. Go to **"Certificates & secrets"** → **"Client secrets"**
2. Click **"New client secret"**
3. Description: "osTicket Secret"
4. Expiration: 24 months
5. Click **"Add"**
6. **⚠️ IMPORTANT: Copy the VALUE immediately!**
```
Client Secret VALUE: ____________________________________________
```

**Grant Permissions:**

1. Go to **"API permissions"** → **"Add a permission"**
2. Select **"Microsoft Graph"** (or Microsoft 365 Copilot if available)
3. Choose **"Application permissions"**
4. Add: `Chat.ReadWrite.All` or similar
5. Click **"Grant admin consent for [Your Org]"**

#### 2. Install Database Tables (2 minutes)

```bash
cd /path/to/osticket
mysql -u your_username -p your_database < setup/sql/ai-assistant-install.sql
```

Enter your MySQL password when prompted.

#### 3. Configure Azure AD Credentials (3 minutes)

**Option A: Using SQL File (Recommended)**

```bash
# Copy example config
cp ai-assistant-config.example.sql ai-assistant-config.sql

# Edit file
nano ai-assistant-config.sql
```

Replace these three placeholders with values from Step 1:
- `YOUR_TENANT_ID_HERE` → Your Directory (tenant) ID
- `YOUR_CLIENT_ID_HERE` → Your Application (client) ID
- `YOUR_CLIENT_SECRET_HERE` → Your Client Secret VALUE

Save and apply:

```bash
mysql -u your_username -p your_database < ai-assistant-config.sql
```

**Option B: Direct SQL**

```sql
-- Enable AI Assistant
UPDATE ost_config SET value = '1'
WHERE namespace = 'ai.assistant' AND key = 'enabled';

-- Set your Tenant ID (Directory ID from Azure)
UPDATE ost_config SET value = 'paste-tenant-id-here'
WHERE namespace = 'ai.assistant' AND key = 'tenant_id';

-- Set your Client ID (Application ID from Azure)
UPDATE ost_config SET value = 'paste-client-id-here'
WHERE namespace = 'ai.assistant' AND key = 'client_id';

-- Set your Client Secret (Secret VALUE from Azure)
UPDATE ost_config SET value = 'paste-client-secret-here'
WHERE namespace = 'ai.assistant' AND key = 'client_secret';
```

#### 4. Clear Cache (30 seconds)

```bash
rm -rf /path/to/osticket/data/cache/*
```

#### 5. Test the Feature (1 minute)

1. Log in to osTicket staff panel
2. Open any ticket with thread history
3. Scroll down - you should see the **AI Assistant** panel (purple header with lightbulb icon)
4. Click the header to expand
5. Type a question or click an example question
6. Click **"Ask AI Assistant"**
7. Wait 5-10 seconds for AI response
8. ✅ Success! You should see an AI-generated response

### Verification Checklist

Use this to verify your setup:

```sql
-- Check all settings are configured
SELECT
    key,
    CASE
        WHEN key IN ('client_secret', 'token_cache') THEN '***REDACTED***'
        WHEN value = '' THEN '⚠️ EMPTY'
        ELSE '✓ SET'
    END as status
FROM ost_config
WHERE namespace='ai.assistant'
ORDER BY key;
```

Expected output:
```
+--------------+-------------+
| key          | status      |
+--------------+-------------+
| client_id    | ✓ SET       |
| client_secret| ***REDACTED***|
| enabled      | ✓ SET       |
| max_tokens   | ✓ SET       |
| model        | ✓ SET       |
| rate_limit   | ✓ SET       |
| temperature  | ✓ SET       |
| tenant_id    | ✓ SET       |
| token_cache  | ***REDACTED***|
| token_expires| ✓ SET       |
+--------------+-------------+
```

### Quick Configuration Reference

| Setting | Default | Purpose |
|---------|---------|---------|
| enabled | 0 (disabled) | Turn feature on/off |
| tenant_id | empty | Azure AD Directory ID |
| client_id | empty | Azure AD Application ID |
| client_secret | empty | Azure AD Secret |
| model | gpt-4 | AI model to use |
| temperature | 0.7 | Response creativity |
| max_tokens | 1000 | Response length limit |
| rate_limit | 10 | Requests per staff per hour |

### Troubleshooting Common Issues

#### Panel not showing?
```bash
# Clear both browser and server cache
Ctrl+Shift+R  # Browser
rm -rf /path/to/osticket/data/cache/*  # Server

# Verify table exists
mysql -u user -p db -e "SHOW TABLES LIKE '%ai_log%'"
```

#### "AI Assistant is not enabled" error?
```sql
-- Check configuration
SELECT key, IF(value='', 'EMPTY', 'SET') as status
FROM ost_config
WHERE namespace='ai.assistant'
AND key IN ('enabled', 'tenant_id', 'client_id', 'client_secret');
```

All four should show 'SET'. If any show 'EMPTY', go back to Step 3.

#### OAuth authentication failed?
```bash
# Test OAuth manually
curl -X POST \
  https://login.microsoftonline.com/YOUR_TENANT_ID/oauth2/v2.0/token \
  -d "client_id=YOUR_CLIENT_ID" \
  -d "client_secret=YOUR_CLIENT_SECRET" \
  -d "scope=https://api.business.microsoft.com/.default" \
  -d "grant_type=client_credentials"
```

Should return JSON with `access_token`. If error:
- Verify tenant_id, client_id, client_secret are correct
- Check client secret hasn't expired in Azure Portal
- Ensure API permissions are granted in Azure Portal
- Verify firewall allows connection to login.microsoftonline.com

#### Rate limit error?
```sql
-- Check current usage
SELECT staff_id, COUNT(*) as requests
FROM ost_ai_log
WHERE created >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY staff_id;

-- Increase limit if needed
UPDATE ost_config SET value='20'
WHERE namespace='ai.assistant' AND key='rate_limit';
```

### Test OAuth Connection

Use this curl command to verify your Azure AD credentials work:

```bash
# Replace with your actual values:
TENANT_ID="your-tenant-id-here"
CLIENT_ID="your-client-id-here"
CLIENT_SECRET="your-client-secret-here"

curl -X POST \
  "https://login.microsoftonline.com/$TENANT_ID/oauth2/v2.0/token" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "client_id=$CLIENT_ID&client_secret=$CLIENT_SECRET&scope=https://api.business.microsoft.com/.default&grant_type=client_credentials"
```

**Success response:**
```json
{
  "token_type": "Bearer",
  "expires_in": 3599,
  "access_token": "eyJ0eXAi..."
}
```

**Failure response examples:**

```json
// Invalid tenant ID
{"error": "invalid_tenant", "error_description": "AADSTS90002: Tenant not found..."}

// Invalid client ID
{"error": "invalid_client", "error_description": "AADSTS700016: Application not found..."}

// Invalid client secret
{"error": "invalid_client", "error_description": "AADSTS7000215: Invalid client secret..."}

// Expired client secret
{"error": "invalid_client", "error_description": "AADSTS7000222: The provided client secret expired..."}
```

### Quick Commands Cheat Sheet

```bash
# Enable AI Assistant
mysql -u user -p db -e "UPDATE ost_config SET value='1' WHERE namespace='ai.assistant' AND key='enabled'"

# Disable AI Assistant
mysql -u user -p db -e "UPDATE ost_config SET value='0' WHERE namespace='ai.assistant' AND key='enabled'"

# Change rate limit to 20/hour
mysql -u user -p db -e "UPDATE ost_config SET value='20' WHERE namespace='ai.assistant' AND key='rate_limit'"

# Clear OAuth token cache (force re-authentication)
mysql -u user -p db -e "UPDATE ost_config SET value='' WHERE namespace='ai.assistant' AND key IN ('token_cache', 'token_expires')"

# View recent AI interactions
mysql -u user -p db -e "SELECT * FROM ost_ai_log ORDER BY created DESC LIMIT 10"

# Count AI usage by staff
mysql -u user -p db -e "SELECT staff_id, COUNT(*) as requests FROM ost_ai_log GROUP BY staff_id"

# View daily usage last 7 days
mysql -u user -p db -e "SELECT DATE(created) as date, COUNT(*) as requests FROM ost_ai_log WHERE created >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created)"
```

### Next Steps

✅ **You're all set!** The AI Assistant is now active.

**Recommended:**
1. Train your staff on effective question patterns
2. Monitor usage via `ost_ai_log` table
3. Adjust rate limits based on your team's needs
4. Review Microsoft Azure costs regularly
5. Read [AI-ASSISTANT-README.md](AI-ASSISTANT-README.md) for advanced configuration

**Adjust Settings:**

```sql
-- Use GPT-4 Turbo (faster, cheaper than GPT-4)
UPDATE ost_config SET value='gpt-4-turbo'
WHERE namespace='ai.assistant' AND key='model';

-- More focused responses (less creative)
UPDATE ost_config SET value='0.3'
WHERE namespace='ai.assistant' AND key='temperature';

-- Longer responses
UPDATE ost_config SET value='2000'
WHERE namespace='ai.assistant' AND key='max_tokens';
```

### Getting Help

**For setup issues:**
1. Verify Azure AD app is configured correctly
2. Check PHP error logs: `tail -f /var/log/php/error.log`
3. Check browser console: F12 → Console tab
4. Test OAuth with curl command above

**For detailed documentation:**
- Read [AI-ASSISTANT-README.md](AI-ASSISTANT-README.md)
- Visit [Microsoft 365 Copilot API Docs](https://learn.microsoft.com/en-us/microsoft-365-copilot/extensibility/api/ai-services/chat/overview)
- Check [Azure AD Setup Guide](https://learn.microsoft.com/en-us/entra/identity-platform/quickstart-register-app)

---

**Happy AI-assisted ticketing!** 🎉

**Quick Links:**
- [Azure Portal](https://portal.azure.com) - Manage your app registration
- [Azure Cost Management](https://portal.azure.com/#view/Microsoft_Azure_CostManagement/Menu/~/costanalysis) - Monitor API costs
- [Microsoft 365 Admin](https://admin.microsoft.com/) - Manage licenses

**Security Reminder:**
- Never commit `ai-assistant-config.sql` to version control
- Client secrets expire - set calendar reminder to renew
- Review AI interaction logs regularly for compliance
- Monitor Azure costs to avoid surprises

---

**Version**: 2.0.0 (Microsoft 365 Copilot + Azure AD OAuth)
**Last Updated**: 2024-01-16
