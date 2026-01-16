# osTicket AI Assistant Integration
## Microsoft 365 Copilot with Azure AD Authentication

## Overview

The AI Assistant is a powerful feature that helps support staff analyze tickets and receive AI-generated insights based on ticket thread history. This feature integrates **Microsoft 365 Copilot API** with **Azure AD (Entra ID) OAuth authentication** to provide context-aware responses to staff questions about tickets.

## Features

- **Staff-Only Access**: AI assistant is only visible and accessible to staff members
- **Context-Aware Responses**: AI analyzes full ticket thread history including all messages, authors, and timestamps
- **Secure Azure AD OAuth**: Uses OAuth 2.0 client credentials flow for authentication
- **Automatic Token Management**: OAuth tokens are cached and automatically refreshed
- **Rate Limiting**: Prevents abuse with configurable rate limits per staff member
- **Interaction Logging**: All AI interactions are logged for auditing and review
- **Secure Configuration**: Credentials stored securely in the database
- **User-Friendly Interface**: Collapsible panel with example questions and easy-to-use controls
- **Copy & Clear Functions**: Easy response management

## Prerequisites

Before installation, you need:

1. **Microsoft 365 Copilot License** or Azure OpenAI Service
2. **Azure AD (Entra ID) Subscription** with admin access
3. **osTicket 1.17.x or 1.18.x** installed
4. **MySQL/MariaDB** database access
5. **PHP with curl extension** enabled
6. **HTTPS access** from server to Azure endpoints

## Installation

### Step 1: Azure AD App Registration

#### 1.1 Create App Registration

1. Go to [Azure Portal](https://portal.azure.com)
2. Navigate to **"Azure Active Directory"** (or "Microsoft Entra ID")
3. Click **"App registrations"** → **"New registration"**
4. Configure your app:
   - **Name**: `osTicket AI Assistant`
   - **Supported account types**: `Accounts in this organizational directory only (Single tenant)`
   - **Redirect URI**: Leave empty (this is a service-to-service app)
5. Click **"Register"**

#### 1.2 Note Your Credentials

From the app **Overview** page, copy:
- **Application (client) ID** - You'll need this
- **Directory (tenant) ID** - You'll need this

#### 1.3 Create Client Secret

1. In your app, go to **"Certificates & secrets"**
2. Click **"Client secrets"** → **"New client secret"**
3. Add a description (e.g., "osTicket AI Assistant Secret")
4. Set expiration (recommendation: 24 months)
5. Click **"Add"**
6. **IMPORTANT**: Copy the secret **VALUE** immediately (you can't see it again!)

#### 1.4 Configure API Permissions

1. In your app, go to **"API permissions"**
2. Click **"Add a permission"**
3. Select **"Microsoft Graph"** or **"Microsoft 365 Copilot"** (if available)
4. Choose **"Application permissions"** (not Delegated)
5. Add these permissions:
   - `Chat.ReadWrite.All` or similar for AI services
   - Or specific Microsoft 365 Copilot permissions
6. Click **"Grant admin consent"** for your organization

### Step 2: Database Setup

Run the SQL migration to create the AI log table and configuration:

```bash
cd /path/to/osticket
mysql -u your_username -p your_database < setup/sql/ai-assistant-install.sql
```

**Important**: If your osTicket uses a different table prefix than `ost_`, edit the SQL file first and replace `ost_` with your prefix.

### Step 3: Configure Credentials

#### Option A: Using Configuration SQL File (Recommended)

```bash
# Copy the example configuration
cp ai-assistant-config.example.sql ai-assistant-config.sql

# Edit with your values
nano ai-assistant-config.sql
```

Replace these placeholders:
- `YOUR_TENANT_ID_HERE` → Your Directory (tenant) ID
- `YOUR_CLIENT_ID_HERE` → Your Application (client) ID
- `YOUR_CLIENT_SECRET_HERE` → Your Client Secret VALUE

Then apply:

```bash
mysql -u your_username -p your_database < ai-assistant-config.sql
```

#### Option B: Direct SQL Commands

```sql
-- Enable AI Assistant
UPDATE ost_config SET value = '1'
WHERE namespace = 'ai.assistant' AND key = 'enabled';

-- Set Tenant ID
UPDATE ost_config SET value = 'your-tenant-id-here'
WHERE namespace = 'ai.assistant' AND key = 'tenant_id';

-- Set Client ID
UPDATE ost_config SET value = 'your-client-id-here'
WHERE namespace = 'ai.assistant' AND key = 'client_id';

-- Set Client Secret
UPDATE ost_config SET value = 'your-client-secret-here'
WHERE namespace = 'ai.assistant' AND key = 'client_secret';
```

### Step 4: Clear Cache

```bash
rm -rf /path/to/osticket/data/cache/*
```

### Step 5: Test the Installation

1. Log in to osTicket staff panel
2. Open any ticket with thread history
3. Look for the **AI Assistant** panel (purple header with lightbulb icon)
4. Click to expand the panel
5. Ask a test question (e.g., "Summarize this ticket")
6. Verify you receive an AI-generated response

## Configuration Options

All configuration is stored in the `ost_config` table with namespace `ai.assistant`:

| Setting | Description | Default | Required |
|---------|-------------|---------|----------|
| `enabled` | Enable/disable AI Assistant | `0` | Yes |
| `tenant_id` | Azure AD Tenant (Directory) ID | Empty | Yes |
| `client_id` | Azure AD Application (Client) ID | Empty | Yes |
| `client_secret` | Azure AD Client Secret Value | Empty | Yes |
| `model` | AI model to use | `gpt-4` | No |
| `temperature` | Response creativity (0.0-1.0) | `0.7` | No |
| `max_tokens` | Maximum response length | `1000` | No |
| `rate_limit` | Requests per staff per hour | `10` | No |
| `token_cache` | Cached OAuth token (auto-managed) | Empty | No |
| `token_expires` | Token expiration time (auto-managed) | `0` | No |

### Adjusting Settings

```sql
-- Change AI model
UPDATE ost_config SET value = 'gpt-4-turbo'
WHERE namespace = 'ai.assistant' AND key = 'model';

-- Adjust temperature (0.0 = focused, 1.0 = creative)
UPDATE ost_config SET value = '0.3'
WHERE namespace = 'ai.assistant' AND key = 'temperature';

-- Set response length
UPDATE ost_config SET value = '2000'
WHERE namespace = 'ai.assistant' AND key = 'max_tokens';

-- Increase rate limit
UPDATE ost_config SET value = '20'
WHERE namespace = 'ai.assistant' AND key = 'rate_limit';
```

## Usage Guide

### For Staff Members

1. **Open any ticket** - The AI Assistant panel appears below the ticket thread
2. **Expand the panel** - Click the purple header
3. **Ask your question**:
   - Type in the text box, or
   - Click an example question button
4. **Submit** - Click "Ask AI Assistant" or press Ctrl+Enter
5. **Review response** - AI analyzes ticket and provides insights
6. **Copy response** - Use Copy button to copy to clipboard
7. **View history** - Click "Conversation History" to see past interactions

### Example Questions

- "What is the main issue in this ticket?"
- "What solutions have been attempted?"
- "What are the next recommended steps?"
- "Summarize this ticket conversation."
- "What is the customer's sentiment?"
- "Has this issue been resolved?"
- "What information is still needed?"

### Keyboard Shortcuts

- **Ctrl+Enter** or **Cmd+Enter** - Submit question (when focused in text field)

## Security & Authentication

### OAuth 2.0 Flow

The AI Assistant uses **OAuth 2.0 Client Credentials** flow:

1. When a staff member asks a question, the system checks for a cached token
2. If token is missing or expired, requests new token from Azure AD
3. Token is cached securely in database with expiration time
4. Token is automatically refreshed when needed (5-minute buffer before expiration)
5. All API requests use Bearer token authentication

### Security Features

- **Azure AD Authentication**: Enterprise-grade OAuth 2.0
- **Token Caching**: Reduces authentication overhead
- **Automatic Token Refresh**: Seamless token management
- **Staff-Only Access**: Not visible to clients/end-users
- **Permission Checking**: Staff must have ticket access
- **Rate Limiting**: Prevents abuse and controls costs
- **Input Sanitization**: All inputs are sanitized and validated
- **HTTPS Required**: All communication encrypted
- **Audit Logging**: All interactions logged with timestamps

### Access Control

- Staff must be authenticated
- Staff must have permission to view the specific ticket
- Rate limits apply per staff member
- All requests validate active session

## Monitoring & Auditing

### View AI Usage

```sql
-- Recent interactions
SELECT
    al.*,
    t.number as ticket_number,
    CONCAT(s.firstname, ' ', s.lastname) as staff_name
FROM ost_ai_log al
JOIN ost_ticket t ON t.ticket_id = al.ticket_id
JOIN ost_staff s ON s.staff_id = al.staff_id
ORDER BY al.created DESC
LIMIT 50;

-- Usage by staff member
SELECT
    CONCAT(s.firstname, ' ', s.lastname) as staff_name,
    COUNT(*) as total_requests,
    MAX(al.created) as last_request
FROM ost_ai_log al
JOIN ost_staff s ON s.staff_id = al.staff_id
GROUP BY al.staff_id
ORDER BY total_requests DESC;

-- Daily usage statistics
SELECT
    DATE(created) as date,
    COUNT(*) as requests,
    COUNT(DISTINCT staff_id) as unique_staff,
    COUNT(DISTINCT ticket_id) as unique_tickets
FROM ost_ai_log
WHERE created >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created)
ORDER BY date DESC;

-- Rate limit check (current hour)
SELECT
    CONCAT(s.firstname, ' ', s.lastname) as staff_name,
    COUNT(*) as requests_this_hour,
    (SELECT value FROM ost_config WHERE namespace='ai.assistant' AND key='rate_limit') as rate_limit
FROM ost_ai_log al
JOIN ost_staff s ON s.staff_id = al.staff_id
WHERE al.created >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY al.staff_id
ORDER BY requests_this_hour DESC;
```

## Troubleshooting

### AI Assistant Panel Not Showing

**Symptoms**: Panel doesn't appear in ticket view

**Solutions**:
1. Clear browser cache (Ctrl+Shift+R)
2. Verify database migration ran successfully:
   ```sql
   SHOW TABLES LIKE '%ai_log';
   SELECT * FROM ost_config WHERE namespace='ai.assistant';
   ```
3. Check PHP error logs for errors
4. Verify staff has permission to view tickets
5. Clear osTicket cache: `rm -rf data/cache/*`

### "AI Assistant is not enabled" Error

**Symptoms**: Error message when opening panel

**Solutions**:
1. Verify `enabled` is set to `1`:
   ```sql
   SELECT * FROM ost_config WHERE namespace='ai.assistant' AND key='enabled';
   ```
2. Verify all Azure AD credentials are configured:
   ```sql
   SELECT key, IF(value='', 'EMPTY', 'SET') as status
   FROM ost_config
   WHERE namespace='ai.assistant' AND key IN ('tenant_id', 'client_id', 'client_secret');
   ```
3. Check that values don't have extra spaces or quotes

### OAuth Authentication Errors

**Symptoms**: "Failed to authenticate" or "OAuth token request failed"

**Solutions**:
1. **Verify Azure AD credentials are correct**:
   - Check tenant_id matches Directory ID in Azure Portal
   - Check client_id matches Application ID in Azure Portal
   - Verify client_secret is the VALUE, not the Secret ID
   - Ensure client secret hasn't expired

2. **Check API permissions**:
   - Go to Azure Portal → Your app → API permissions
   - Verify permissions are granted
   - Click "Grant admin consent" if not done

3. **Test OAuth manually** with curl:
   ```bash
   curl -X POST \
     https://login.microsoftonline.com/YOUR_TENANT_ID/oauth2/v2.0/token \
     -d "client_id=YOUR_CLIENT_ID" \
     -d "client_secret=YOUR_CLIENT_SECRET" \
     -d "scope=https://api.business.microsoft.com/.default" \
     -d "grant_type=client_credentials"
   ```

4. **Check firewall**:
   - Ensure server can reach `login.microsoftonline.com`
   - Verify outbound HTTPS (port 443) is allowed

5. **Clear token cache**:
   ```sql
   UPDATE ost_config SET value='' WHERE namespace='ai.assistant' AND key='token_cache';
   UPDATE ost_config SET value='0' WHERE namespace='ai.assistant' AND key='token_expires';
   ```

### "Rate Limit Reached" Error

**Symptoms**: Staff member sees rate limit message

**Solutions**:
1. Wait for rate limit window to reset (1 hour from first request)
2. Check current usage:
   ```sql
   SELECT staff_id, COUNT(*) as requests
   FROM ost_ai_log
   WHERE created >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
   GROUP BY staff_id;
   ```
3. Increase rate limit if needed:
   ```sql
   UPDATE ost_config SET value='20'
   WHERE namespace='ai.assistant' AND key='rate_limit';
   ```

### API Connection Errors

**Symptoms**: "API Connection Error" or timeout

**Solutions**:
1. Verify server has internet connectivity
2. Test API endpoint accessibility:
   ```bash
   curl -I https://api.business.microsoft.com
   ```
3. Check PHP curl is enabled:
   ```bash
   php -m | grep curl
   ```
4. Verify SSL certificates are up to date
5. Check PHP timeout settings in php.ini
6. Review web server error logs

### Empty or Invalid Responses

**Symptoms**: Response is empty or doesn't make sense

**Solutions**:
1. Verify ticket has sufficient thread content
2. Check token hasn't expired (auto-refreshes, but verify):
   ```sql
   SELECT key, value FROM ost_config
   WHERE namespace='ai.assistant' AND key IN ('token_cache', 'token_expires');
   ```
3. Try reducing `max_tokens` if responses are cut off
4. Adjust `temperature` for more focused responses (lower = more focused)
5. Check API quota/billing in Azure Portal

## Cost Management

### Understanding Costs

Microsoft 365 Copilot API charges based on:
- **Token usage**: Input tokens (ticket context) + output tokens (response)
- **Model selected**: GPT-4 is more expensive than GPT-3.5-turbo
- **Request volume**: More requests = higher costs

**Average ticket analysis**:
- Input: ~500-1500 tokens (depends on thread length)
- Output: ~200-800 tokens (depends on max_tokens setting)
- Total per request: ~700-2300 tokens

### Cost Control Strategies

1. **Adjust Rate Limits**: Lower limits reduce usage
   ```sql
   UPDATE ost_config SET value='5'
   WHERE namespace='ai.assistant' AND key='rate_limit';
   ```

2. **Optimize max_tokens**: Set appropriate response length
   ```sql
   UPDATE ost_config SET value='500'
   WHERE namespace='ai.assistant' AND key='max_tokens';
   ```

3. **Use Smaller Model**: GPT-3.5-turbo is faster and cheaper
   ```sql
   UPDATE ost_config SET value='gpt-3.5-turbo'
   WHERE namespace='ai.assistant' AND key='model';
   ```

4. **Monitor Usage**: Review logs regularly
   ```sql
   -- Monthly token estimate
   SELECT
       MONTH(created) as month,
       COUNT(*) * 1500 as estimated_tokens_used
   FROM ost_ai_log
   GROUP BY MONTH(created);
   ```

5. **Staff Training**: Educate staff on effective question patterns to reduce unnecessary requests

### Setting Azure Spending Limits

1. Go to Azure Portal → Cost Management
2. Set up budgets and alerts
3. Configure spending caps if available
4. Monitor daily costs

## Privacy & Compliance

### Data Handling

- Ticket thread content is sent to Microsoft 365 Copilot API
- AI responses are stored in your osTicket database
- OAuth tokens are cached temporarily in database
- No data is shared outside Microsoft's infrastructure and your server

### Compliance Considerations

#### GDPR (EU)
- Ticket data may contain personal information
- Review Microsoft's Data Processing Agreement
- Ensure you have legal basis for processing
- Update privacy policy to mention AI usage

#### HIPAA (Healthcare)
- **DO NOT** use with tickets containing Protected Health Information (PHI)
- Microsoft 365 Copilot may not be HIPAA-compliant for all features
- Consult with compliance team before deployment

#### PCI-DSS (Payment Cards)
- **DO NOT** use with tickets containing payment card data
- Ensure PCI data is not included in ticket threads
- Filter sensitive data before enabling AI Assistant

#### SOC 2 / ISO 27001
- Review Microsoft's security certifications
- Ensure your usage aligns with your security policies
- Implement appropriate access controls

### Best Practices

1. **Staff Training**: Train staff on data sensitivity
2. **Privacy Policy**: Update to mention AI assistance
3. **Data Minimization**: Only use on necessary tickets
4. **Access Logs**: Regularly review AI usage logs
5. **Disclaimers**: Add disclaimers about AI usage

## Uninstallation

To completely remove the AI Assistant:

```bash
# 1. Run uninstall SQL
mysql -u your_username -p your_database < setup/sql/ai-assistant-uninstall.sql

# 2. Remove files
rm /path/to/osticket/include/class.ai.assistant.php
rm /path/to/osticket/include/ajax.ai.php
rm /path/to/osticket/include/staff/templates/ai-assistant.tmpl.php
rm /path/to/osticket/js/ai-assistant.js
rm /path/to/osticket/css/ai-assistant.css
rm /path/to/osticket/setup/sql/ai-assistant-*.sql
rm /path/to/osticket/ai-assistant-config.example.sql
rm /path/to/osticket/AI-ASSISTANT-*.md

# 3. Revert code changes
# Edit scp/ajax.php and remove AI assistant routes (search for "ajax.ai.php")
# Edit include/staff/header.inc.php and remove CSS/JS includes (search for "ai-assistant")
# Edit include/staff/ticket-view.inc.php and remove template include (search for "ai-assistant")
# Edit bootstrap.php and remove AI_LOG_TABLE constant

# 4. Clear cache
rm -rf /path/to/osticket/data/cache/*
```

### Delete Azure AD App Registration

1. Go to Azure Portal → Azure Active Directory
2. Navigate to App registrations
3. Find "osTicket AI Assistant"
4. Click Delete
5. Confirm deletion

## Advanced Topics

### Custom Prompts

To customize the system prompt sent to AI, edit `/include/class.ai.assistant.php`:

```php
// Around line 270
$messages = array(
    array(
        'role' => 'system',
        'content' => 'Your custom system prompt here...'
    ),
    // ...
);
```

### Integration with Other Services

The AI Assistant can be extended to integrate with:
- Knowledge bases (fetch relevant articles)
- CRM systems (include customer history)
- Sentiment analysis tools
- Translation services

## Support & Contributing

### Getting Help

1. Check this README for common issues
2. Review PHP error logs: `/var/log/php/error.log`
3. Check browser console: F12 → Console tab
4. Test OAuth with curl commands above
5. Verify Azure AD app configuration

### Reporting Issues

When reporting issues, include:
- osTicket version
- PHP version
- MySQL version
- Browser and version
- Error messages from logs
- Steps to reproduce

### Contributing

Suggestions for improvements:
- Admin panel for configuration (GUI instead of SQL)
- Support for other AI providers (Azure OpenAI, OpenAI direct)
- Enhanced analytics dashboard
- Multi-language support
- Custom prompt templates
- Ticket categorization suggestions
- Auto-response drafting

## Reference Links

- [Microsoft 365 Copilot API Documentation](https://learn.microsoft.com/en-us/microsoft-365-copilot/extensibility/api/ai-services/chat/overview)
- [Azure AD App Registration Guide](https://learn.microsoft.com/en-us/entra/identity-platform/quickstart-register-app)
- [OAuth 2.0 Client Credentials Flow](https://learn.microsoft.com/en-us/entra/identity-platform/v2-oauth2-client-creds-grant-flow)
- [osTicket Documentation](https://docs.osticket.com/)

## License

This AI Assistant integration follows the osTicket license:
Released under the GNU General Public License WITHOUT ANY WARRANTY.
See LICENSE.TXT for details.

## Credits

Developed as an enhancement to osTicket for improved staff productivity and customer support quality using Microsoft 365 Copilot AI services.

---

**Version**: 2.0.0 (Microsoft 365 Copilot)
**Last Updated**: 2024-01-16
**Compatible with**: osTicket 1.17.x and 1.18.x
**API Provider**: Microsoft 365 Copilot / Azure OpenAI
**Authentication**: Azure AD (Entra ID) OAuth 2.0
