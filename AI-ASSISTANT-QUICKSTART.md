# AI Assistant Quick Start Guide

## 5-Minute Setup

### Prerequisites
- osTicket 1.17.x or 1.18.x installed
- MySQL/MariaDB database access
- GitHub Copilot API key ([Get one here](https://github.com/features/copilot))
- Command line access to server

### Installation Steps

#### 1. Install Database Tables (2 minutes)

```bash
cd /path/to/osticket
mysql -u your_username -p your_database < setup/sql/ai-assistant-install.sql
```

Enter your MySQL password when prompted.

#### 2. Configure API Key (1 minute)

```bash
# Create your config file
cp ai-assistant-config.example.sql ai-assistant-config.sql

# Edit the file
nano ai-assistant-config.sql
```

Replace `YOUR_GITHUB_COPILOT_API_KEY_HERE` with your actual API key, then save and exit.

```bash
# Apply configuration
mysql -u your_username -p your_database < ai-assistant-config.sql
```

#### 3. Clear Cache (30 seconds)

```bash
rm -rf /path/to/osticket/data/cache/*
```

#### 4. Test the Feature (1 minute)

1. Log in to osTicket staff panel
2. Open any ticket with thread history
3. Scroll down to see the **AI Assistant** panel (purple header with lightbulb icon)
4. Click to expand the panel
5. Type a question or click an example question
6. Click "Ask AI Assistant"
7. View the AI-generated response!

### Quick Configuration Reference

| What | Where | Default |
|------|-------|---------|
| Enable/Disable | ost_config table, key 'enabled' | Disabled (0) |
| API Key | ost_config table, key 'api_key' | Empty |
| Rate Limit | ost_config table, key 'rate_limit' | 10 per hour |
| Model | ost_config table, key 'model' | gpt-4 |

### Troubleshooting Common Issues

**Panel not showing?**
- Clear browser cache (Ctrl+Shift+R)
- Verify database migration ran successfully
- Check browser console for errors

**"AI Assistant is not enabled" message?**
- Run: `SELECT * FROM ost_config WHERE namespace='ai.assistant'`
- Verify 'enabled' = '1' and 'api_key' is set

**Rate limit error?**
- Wait 1 hour OR
- Increase rate limit in database

### Test API Connection

```bash
# Test your API key with curl
curl -X POST https://api.githubcopilot.com/chat/completions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "messages": [{"role": "user", "content": "Say hello"}],
    "model": "gpt-4",
    "max_tokens": 50
  }'
```

If you get a response, your API key works!

### Next Steps

- Read the full [AI-ASSISTANT-README.md](AI-ASSISTANT-README.md) for advanced configuration
- Train your staff on effective question patterns
- Monitor usage via `ost_ai_log` table
- Adjust rate limits based on your needs

## Quick Commands Cheat Sheet

```bash
# Enable AI Assistant
mysql -u user -p db -e "UPDATE ost_config SET value='1' WHERE namespace='ai.assistant' AND key='enabled'"

# Disable AI Assistant
mysql -u user -p db -e "UPDATE ost_config SET value='0' WHERE namespace='ai.assistant' AND key='enabled'"

# Change rate limit to 20/hour
mysql -u user -p db -e "UPDATE ost_config SET value='20' WHERE namespace='ai.assistant' AND key='rate_limit'"

# View recent AI interactions
mysql -u user -p db -e "SELECT * FROM ost_ai_log ORDER BY created DESC LIMIT 10"

# Count AI usage by staff
mysql -u user -p db -e "SELECT staff_id, COUNT(*) as requests FROM ost_ai_log GROUP BY staff_id"
```

## Support

For detailed documentation, see [AI-ASSISTANT-README.md](AI-ASSISTANT-README.md)

---

**Happy AI-assisted ticketing!** 🎉
