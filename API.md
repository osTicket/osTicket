# osTicket API Documentation

## Authentication
All endpoints require an API key in the header:
```
X-API-Key: YOUR_API_KEY
```

---

## 1. List Tickets
**`GET /api/tickets.json`**

Retrieve a paginated list of tickets with optional filtering and sorting.

### Parameters
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number (starting from 1) |
| `per_page` | int | 100 | Items per page (max 200) |
| `order` | string | desc | Sort order: `asc` or `desc` |
| `status` | string | - | Filter by status: `open`, `closed`, status ID, or partial name |
| `state` | string | - | Alternative filter: `open` or `closed` |

### Examples
```bash
# Basic list (first 100 tickets, newest first)
GET /api/tickets.json

# Pagination
GET /api/tickets.json?page=2&per_page=50

# Filter open tickets, page 3, 25 per page
GET /api/tickets.json?status=open&page=3&per_page=25

# Filter by status name (partial match)
GET /api/tickets.json?status=Wacht

# Closed tickets, oldest first
GET /api/tickets.json?status=closed&order=asc
```

### Response Format
```json
{
  "tickets": [
    {
      "id": 18129,
      "number": "658972", 
      "subject": "FW: Herinnering: Inbreuk op copyright...",
      "created": "2025-08-28 15:46:04",
      "status": {
        "id": 1,
        "name": "Open",
        "state": "open",
        "mode": 1,
        "flags": 0,
        "sort": 1,
        "created": "2018-01-26 08:15:23",
        "updated": "0000-00-00 00:00:00",
        "properties": {
          "description": "Open tickets."
        }
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 100,
    "total": 1547,
    "total_pages": 16,
    "has_next": true,
    "has_previous": false,
    "showing_from": 1,
    "showing_to": 100
  }
}
```

---

## 2. Ticket Details
**`GET /api/tickets/{id}.json`**

Get complete ticket information including full thread conversation.

### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | int | Yes | Ticket database ID |

### Example
```bash
GET /api/tickets/18122.json
```

### Response Format
```json
{
  "id": 18122,
  "number": "395965",
  "subject": "Domein bredayetis.nl",
  "created": "2025-08-25 15:22:03",
  "status": {
    "id": 1,
    "name": "Open",
    "state": "open",
    "mode": 1,
    "flags": 0,
    "sort": 1,
    "created": "2018-01-26 08:15:23",
    "updated": "0000-00-00 00:00:00",
    "properties": {
      "description": "Open tickets."
    }
  },
  "department": {
    "id": 1,
    "name": "Support"
  },
  "user": {
    "id": 123,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "thread": [
    {
      "id": 67218,
      "type": "M",
      "type_name": "message",
      "display_type": "customer_message",
      "title": "",
      "body": "Clean text without HTML",
      "body_html": "<div>HTML version with formatting</div>",
      "created": "2025-08-25 15:22:03",
      "updated": null,
      "source": "email",
      "poster": "email@example.com",
      "is_internal": false,
      "is_system": false,
      "is_edited": false,
      "is_response": false,
      "is_message": true,
      "attachments": [
        {
          "id": 123,
          "name": "document.pdf",
          "size": 245760,
          "type": "application/pdf",
          "is_inline": false
        }
      ],
      "author": {
        "type": "user",
        "name": "John Doe"
      }
    }
  ]
}
```

### Thread Entry Types
- **`display_type`** values for frontend styling:
  - `system` - System messages (grey)
  - `internal_note` - Internal notes (white/grey) 
  - `staff_response` - Staff response to customer (orange)
  - `customer_message` - Customer message (blue)

---

## 3. Attachment URLs
**`GET /api/attachments/{id}/url.json`**

Generate secure, time-limited download URLs for attachments.

### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | int | Yes | Attachment ID (from ticket details) |

### Example
```bash
GET /api/attachments/123/url.json
```

### Response Format
```json
{
  "attachment_id": 123,
  "filename": "document.pdf",
  "size": 245760,
  "type": "application/pdf",
  "url": "https://service.d-media.nl/api/file.php?id=123&expires=1234567890&signature=abc123&disposition=inline",
  "expires": "2025-08-29 15:46:04",
  "expires_timestamp": 1234567890
}
```

**Note:** URLs are valid for 24 hours and require the generated signature for access.

---

## Error Responses

### 401 Unauthorized
```json
{
  "error": "API key not authorized"
}
```

### 404 Not Found
```json
{
  "error": "Ticket not found"
}
```

---

## Frontend Integration Tips

### Pagination Loop
```javascript
let currentPage = 1;
let hasMore = true;

while (hasMore) {
  const response = await fetch(`/api/tickets.json?page=${currentPage}&per_page=50`);
  const data = await response.json();
  
  // Process tickets
  data.tickets.forEach(ticket => {
    console.log(`Ticket #${ticket.number}: ${ticket.subject}`);
  });
  
  hasMore = data.pagination.has_next;
  currentPage++;
}
```

### Status Colors (from osTicket UI)
- **Open**: Green (`#28a745`)
- **Wacht op intern**: Orange (`#fd7e14`) 
- **Wacht op klant**: Orange (`#fd7e14`)
- **Closed**: Grey (`#6c757d`)

### Thread Message Colors
- **System**: Grey
- **Internal Note**: White/Grey background
- **Staff Response**: Orange
- **Customer Message**: Blue

---

## Rate Limiting
- No explicit rate limits currently implemented
- Recommended: max 100 requests per minute per API key
- Use pagination to avoid large data transfers

## Base URL
```
https://service.d-media.nl/api/
```
