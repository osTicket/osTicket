# osTicket AI Assistant Integration

## Overview

The AI Assistant is a powerful feature that helps support staff analyze tickets and receive AI-generated insights based on ticket thread history. This feature integrates GitHub Copilot API to provide context-aware responses to staff questions about tickets.

## Features

- **Staff-Only Access**: AI assistant is only visible and accessible to staff members
- **Context-Aware Responses**: AI analyzes full ticket thread history including all messages, authors, and timestamps
- **Rate Limiting**: Prevents abuse with configurable rate limits per staff member
- **Interaction Logging**: All AI interactions are logged for auditing and review
- **Secure Configuration**: API keys stored securely in the database
- **User-Friendly Interface**: Collapsible panel with example questions and easy-to-use controls
- **Copy & Clear Functions**: Easy response management

## Installation

### Step 1: Database Setup

Run the SQL migration to create the AI log table and default configuration:

```bash
mysql -u your_username -p your_database < setup/sql/ai-assistant-install.sql
```

**Important**: If your osTicket installation uses a different table prefix than `ost_`, edit the SQL file first and replace `ost_` with your prefix.

### Step 2: Verify File Installation

All necessary files should already be in place:

**Backend Files:**
- `/include/class.ai.assistant.php` - Main AI assistant class
- `/include/ajax.ai.php` - AJAX API endpoint handler
- `/include/staff/templates/ai-assistant.tmpl.php` - UI template

**Frontend Files:**
- `/js/ai-assistant.js` - JavaScript functionality
- `/css/ai-assistant.css` - Styling

**Configuration Files:**
- `/setup/sql/ai-assistant-install.sql` - Database installation
- `/setup/sql/ai-assistant-uninstall.sql` - Database uninstallation

### Step 3: Clear Cache

Clear osTicket cache to ensure new files are loaded:

```bash
rm -rf /path/to/osticket/data/cache/*
```

## Configuration

### 1. Get GitHub Copilot API Key

To use the AI Assistant, you need a GitHub Copilot API key:

1. Sign up for [GitHub Copilot Business](https://github.com/features/copilot)
2. Generate an API key from your GitHub account settings
3. Keep the API key secure - you'll need it for configuration

### 2. Configure AI Assistant

The AI Assistant can be configured directly in the database using the `ost_config` table:

```sql
-- Enable AI Assistant
UPDATE ost_config SET value = '1' WHERE namespace = 'ai.assistant' AND key = 'enabled';

-- Set API Key (replace YOUR_API_KEY with your actual key)
UPDATE ost_config SET value = 'YOUR_API_KEY' WHERE namespace = 'ai.assistant' AND key = 'api_key';

-- Optional: Adjust model (default: gpt-4)
UPDATE ost_config SET value = 'gpt-4' WHERE namespace = 'ai.assistant' AND key = 'model';

-- Optional: Adjust temperature (0.0-1.0, default: 0.7)
UPDATE ost_config SET value = '0.7' WHERE namespace = 'ai.assistant' AND key = 'temperature';

-- Optional: Adjust max tokens (default: 1000)
UPDATE ost_config SET value = '1000' WHERE namespace = 'ai.assistant' AND key = 'max_tokens';

-- Optional: Adjust rate limit (requests per hour, default: 10)
UPDATE ost_config SET value = '10' WHERE namespace = 'ai.assistant' AND key = 'rate_limit';
```

### Configuration Options Explained

| Setting | Description | Default | Valid Values |
|---------|-------------|---------|--------------|
| `enabled` | Enable/disable AI Assistant | `0` (disabled) | `0` or `1` |
| `api_key` | GitHub Copilot API key | Empty | Your API key string |
| `model` | AI model to use | `gpt-4` | `gpt-4`, `gpt-3.5-turbo` |
| `temperature` | Response creativity (higher = more creative) | `0.7` | `0.0` to `1.0` |
| `max_tokens` | Maximum response length | `1000` | `1` to `4000` |
| `rate_limit` | Max requests per staff member per hour | `10` | Any positive integer |

### 3. Environment Variables (Optional)

For enhanced security, you can store the API key in environment variables instead:

1. Edit your `.env` file or web server configuration
2. Add: `GITHUB_COPILOT_API_KEY=your_api_key_here`
3. Modify `/include/class.ai.assistant.php` to read from environment variable if preferred

## Usage

### For Staff Members

Once configured, staff members will see the AI Assistant panel in the ticket view:

1. **Open any ticket** - The AI Assistant panel appears below the ticket thread
2. **Click the panel header** to expand/collapse it
3. **Type your question** or click an example question button
4. **Click "Ask AI Assistant"** to submit
5. **View the response** - AI analyzes the ticket and provides insights
6. **Copy response** - Use the Copy button to copy the response to clipboard
7. **Clear response** - Use the Clear button to remove the response

### Example Questions

- "What is the main issue in this ticket?"
- "What solutions have been attempted?"
- "What are the next recommended steps?"
- "Summarize this ticket conversation."
- "What is the customer's sentiment?"
- "Are there any recurring patterns in this thread?"

### Keyboard Shortcuts

- **Ctrl+Enter** or **Cmd+Enter** - Submit question (when focused on question input)

### Conversation History

Click "Conversation History" to view past AI interactions for this ticket. This helps maintain context and avoid asking duplicate questions.

## Security Features

### Access Control

- **Staff-Only**: AI Assistant is not visible to clients/end-users
- **Permission Checking**: Staff must have permission to view the ticket
- **Session Validation**: All requests validate active staff session

### Rate Limiting

- Configurable requests per hour per staff member
- Prevents abuse and controls API costs
- Returns clear error message when limit is reached

### Input Sanitization

- All user inputs are sanitized and validated
- HTML tags stripped from questions
- SQL injection prevention
- XSS protection

### API Security

- API keys stored in database (not in code)
- HTTPS required for API communication
- Request timeout to prevent hanging connections
- Error messages don't expose sensitive information

## Logging & Auditing

All AI interactions are logged in the `ost_ai_log` table:

```sql
-- View recent AI interactions
SELECT
    al.*,
    t.number as ticket_number,
    s.firstname,
    s.lastname
FROM ost_ai_log al
JOIN ost_ticket t ON t.ticket_id = al.ticket_id
JOIN ost_staff s ON s.staff_id = al.staff_id
ORDER BY al.created DESC
LIMIT 50;

-- View AI usage by staff member
SELECT
    s.firstname,
    s.lastname,
    COUNT(*) as total_requests,
    MAX(al.created) as last_request
FROM ost_ai_log al
JOIN ost_staff s ON s.staff_id = al.staff_id
GROUP BY al.staff_id
ORDER BY total_requests DESC;

-- View AI usage by date
SELECT
    DATE(created) as date,
    COUNT(*) as requests
FROM ost_ai_log
GROUP BY DATE(created)
ORDER BY date DESC;
```

## Troubleshooting

### AI Assistant Panel Not Showing

1. Verify database migration ran successfully
2. Check that files are in correct locations
3. Clear browser cache and osTicket cache
4. Verify staff has permission to view tickets
5. Check browser console for JavaScript errors

### "AI Assistant is not enabled" Error

1. Verify `enabled` is set to `1` in configuration
2. Verify API key is set in configuration
3. Check database connection
4. Review PHP error logs

### "Rate Limit Reached" Error

1. Wait for the rate limit window to reset (1 hour)
2. Increase rate limit in configuration if needed
3. Check `ost_ai_log` table for request timestamps

### API Connection Errors

1. Verify API key is correct
2. Check internet connectivity from server
3. Verify firewall allows outbound HTTPS to api.githubcopilot.com
4. Check SSL certificate validity
5. Review PHP curl settings

### Empty or Invalid Responses

1. Check API credit/quota
2. Verify model name is correct
3. Try reducing max_tokens if responses are cut off
4. Check ticket has sufficient thread content
5. Review API error messages in logs

## API Cost Management

### Estimating Costs

GitHub Copilot API charges based on:
- Number of requests
- Tokens used (input + output)

Average ticket analysis:
- Input: ~500-1500 tokens (ticket thread)
- Output: ~200-800 tokens (response)

### Cost Control Strategies

1. **Adjust Rate Limits**: Lower rate limits reduce usage
2. **Optimize max_tokens**: Set appropriate response length
3. **Use Smaller Model**: Consider gpt-3.5-turbo for lower costs
4. **Monitor Usage**: Review logs regularly
5. **Staff Training**: Educate staff on effective question patterns

## Privacy & Compliance

### Data Handling

- Ticket thread content is sent to GitHub Copilot API
- AI responses are stored in your database
- No data is shared outside GitHub Copilot API and your server

### Compliance Considerations

- **GDPR**: Consider data processing agreements with GitHub
- **HIPAA**: Consult with compliance team before using with healthcare data
- **PCI-DSS**: Do not use with tickets containing payment card data
- **Internal Policies**: Review with security/compliance teams

### Disclaimers

Add appropriate disclaimers to your staff training:
- AI responses are suggestions, not definitive answers
- Staff should verify AI insights before taking action
- Sensitive data in tickets will be sent to external API
- AI may occasionally produce incorrect information

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

# 3. Remove AJAX route registration
# Edit scp/ajax.php and remove AI assistant routes (lines added during installation)

# 4. Remove includes from header
# Edit include/staff/header.inc.php and remove CSS/JS includes

# 5. Remove integration from ticket view
# Edit include/staff/ticket-view.inc.php and remove AI assistant template include

# 6. Clear cache
rm -rf /path/to/osticket/data/cache/*
```

## Support & Contributions

### Getting Help

- Check this README for common issues
- Review PHP error logs
- Check JavaScript console for errors
- Test API key with curl commands

### Contributing

Suggestions for improvements:
- Additional AI models support
- Admin panel for configuration
- Enhanced analytics dashboard
- Multi-language support
- Custom prompt templates

## License

This AI Assistant integration follows the osTicket license:
Released under the GNU General Public License WITHOUT ANY WARRANTY.

## Credits

Developed as an enhancement to osTicket for improved staff productivity and customer support quality.

---

**Version**: 1.0.0
**Last Updated**: 2024-01-16
**Compatible with**: osTicket 1.17.x and 1.18.x
