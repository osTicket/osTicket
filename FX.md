# osTicket Business Logic & Operations

This document describes the business logic, operations, and invariants of the osTicket help desk system.

## Overview

osTicket is a PHP-based help desk ticketing system with the following core capabilities:
- Multi-channel ticket ingestion (Web, Email, Phone, API)
- Department and team-based routing
- SLA management with business hours support
- Role-based access control
- Dynamic forms and custom fields
- Knowledge base (FAQ)
- Email notifications and auto-responses

---

## Core Business Entities

### Ticket
The central entity representing a support request.

**Key Properties**:
- `number`: Human-readable ticket identifier (customizable format)
- `status`: Current state (open, closed, archived, deleted)
- `source`: Origin channel (Web, Email, Phone, API, Other)
- `isoverdue`: SLA violation flag
- `isanswered`: Response status flag

**Relationships**:
| Entity | Cardinality | Description |
|--------|-------------|-------------|
| User | N:1 | Ticket owner/requester |
| Staff | N:1 | Assigned agent (nullable) |
| Team | N:1 | Assigned team (nullable) |
| Department | N:1 | Owning department |
| Topic | N:1 | Help topic/category |
| Status | N:1 | Current status definition |
| Priority | N:1 | Priority level |
| SLA | N:1 | Service level agreement |
| Thread | 1:1 | Communication thread |
| Lock | 1:1 | Edit lock (when being edited) |

**Source File**: `include/class.ticket.php` (~4800 lines)

---

### User
End-user/client who creates or is associated with tickets.

**Key Properties**:
- `name`: Display name
- `status`: Account status flags
- `default_email_id`: Primary email address

**Relationships**:
| Entity | Cardinality | Description |
|--------|-------------|-------------|
| UserEmail | 1:N | Email addresses |
| UserAccount | 1:1 | Portal login (optional) |
| Organization | N:1 | Parent organization |
| Ticket | 1:N | Owned tickets |

**Source File**: `include/class.user.php`

---

### Staff
Internal support agent/administrator.

**Key Properties**:
- `username`: Login username
- `isactive`: Active status
- `isadmin`: Administrator flag
- `onvacation`: Vacation mode (excludes from assignment)
- `assigned_only`: View only assigned tickets

**Relationships**:
| Entity | Cardinality | Description |
|--------|-------------|-------------|
| Department | N:1 | Primary department |
| Role | N:1 | Primary role |
| StaffDeptAccess | 1:N | Extended department access |
| Team | N:M | Team memberships |

**Source File**: `include/class.staff.php`

---

### Organization
Company or group that users belong to.

**Key Properties**:
- `name`: Organization name
- `domain`: Email domain for auto-assignment
- `manager`: Account manager (Staff or Team)

**Features**:
- Shared ticket visibility for organization members
- Domain-based auto-association for new users
- Custom data via dynamic forms

**Source File**: `include/class.organization.php`

---

### Department
Organizational unit for ticket routing and management.

**Key Properties**:
- `name`: Department name
- `path`: Hierarchical path (supports nesting)
- `ispublic`: Visibility to end users

**Flags**:
| Flag | Description |
|------|-------------|
| `FLAG_ACTIVE` | Department is active |
| `FLAG_ARCHIVED` | Department is archived (hidden) |
| `FLAG_ASSIGN_MEMBERS_ONLY` | Only primary members can be assigned |
| `FLAG_ASSIGN_PRIMARY_ONLY` | Only primary member assignments |
| `FLAG_DISABLE_AUTO_CLAIM` | Prevent claim-on-reply |
| `FLAG_DISABLE_REOPEN_AUTO_ASSIGN` | Prevent auto-assign on reopen |

**Source File**: `include/class.dept.php`

---

### Team
Group of staff members for collective assignment.

**Key Properties**:
- `name`: Team name
- `lead_id`: Team lead staff member
- `flags`: Team configuration flags

**Relationships**:
| Entity | Cardinality | Description |
|--------|-------------|-------------|
| Staff | N:M | Team members |
| Staff (lead) | N:1 | Team lead |

**Source File**: `include/class.team.php`

---

### Role
Permission set for staff access control.

**Key Properties**:
- `name`: Role name
- `permissions`: JSON-encoded permission set

**Source File**: `include/class.role.php`

---

### Thread
Communication container for ticket/task entries.

**Key Properties**:
- `object_id`: Parent object ID
- `object_type`: Parent type (T=Ticket, A=Task)
- `lastresponse`: Last staff response time
- `lastmessage`: Last user message time

**Relationships**:
| Entity | Cardinality | Description |
|--------|-------------|-------------|
| ThreadEntry | 1:N | Messages/responses/notes |
| ThreadEvent | 1:N | Activity audit log |
| ThreadCollaborator | 1:N | CC'd users |
| ThreadReferral | 1:N | Cross-references |

**Source File**: `include/class.thread.php` (~2500 lines)

---

### ThreadEntry
Individual message within a thread.

**Entry Types**:
| Type | Code | Description |
|------|------|-------------|
| Message | `M` | From user/client |
| Response | `R` | From staff |
| Note | `N` | Internal only |

**Source File**: `include/class.thread.php`

---

### Task
Internal work item, optionally linked to a ticket.

**Key Properties**:
- Similar structure to tickets
- Own threading system
- Can be standalone or attached to ticket

**Relationships**:
| Entity | Cardinality | Description |
|--------|-------------|-------------|
| Ticket | N:1 | Parent ticket (optional) |
| Staff | N:1 | Assigned agent |
| Team | N:1 | Assigned team |
| Department | N:1 | Owning department |
| Thread | 1:1 | Communication thread |

**Source File**: `include/class.task.php`

---

### SLA (Service Level Agreement)
Defines response time expectations.

**Key Properties**:
- `grace_period`: Hours until overdue (business or calendar)
- `flags`: SLA behavior flags

**Flags**:
| Flag | Value | Description |
|------|-------|-------------|
| `FLAG_ACTIVE` | 1 | SLA is active |
| `FLAG_ESCALATE` | 2 | Enable priority escalation |
| `FLAG_NOALERTS` | 4 | Suppress overdue alerts |
| `FLAG_TRANSIENT` | 8 | Temporary SLA |

**Source File**: `include/class.sla.php`

---

### HelpTopic
Ticket categorization and routing configuration.

**Key Properties**:
- `topic`: Topic name
- `topic_pid`: Parent topic (hierarchical)
- `ispublic`: Visibility to users

**Routing Configuration**:
- Default department
- Default priority
- Default SLA
- Default assignee (staff or team)
- Associated forms

**Source File**: `include/class.topic.php`

---

### Filter
Email/ticket routing rules.

**Key Properties**:
- `execorder`: Execution priority
- `match_all_rules`: Match mode (AND/OR)
- `stop_onmatch`: Stop processing on match
- `target`: Apply to (Any, Web, Email, API)

**Rule Operators**:
| Operator | Description |
|----------|-------------|
| `equal` | Exact match |
| `not_equal` | Not equal |
| `contains` | Contains substring |
| `dn_contain` | Does not contain |
| `starts` | Starts with |
| `ends` | Ends with |
| `match` | Regex match |
| `not_match` | Regex not match |

**Source File**: `include/class.filter.php`

---

## Core Operations

### Ticket Lifecycle

#### 1. Ticket Creation

**Method**: `Ticket::create($vars, &$errors, $origin, $autorespond, $alert)`

**Flow**:
```
1. Validate input data
   ├── Check required fields
   ├── Validate user email (not banned)
   └── Process help topic forms

2. Apply ticket filters (TicketFilter)
   ├── Match against filter rules
   ├── Execute filter actions
   └── Stop on match (if configured)

3. Create ticket record
   ├── Generate ticket number
   ├── Set department, status, priority
   ├── Calculate SLA due date
   └── Create thread

4. Create user if needed
   ├── Find by email or create
   ├── Associate with organization
   └── Set as ticket owner

5. Post initial message
   └── Create thread entry (type=M)

6. Send notifications
   ├── Auto-response to user (if enabled)
   └── Alert to staff (if enabled)

7. Log event
   └── Record creation in thread_event
```

**Origins**:
| Origin | Description | Auto-response | Alert |
|--------|-------------|---------------|-------|
| `Web` | Client portal | Yes | Yes |
| `Email` | Email fetcher | Configurable | Yes |
| `Phone` | Staff-entered | No | Configurable |
| `API` | API request | Configurable | Configurable |
| `Staff` | Staff on behalf | No | Configurable |

**Invariants**:
- Email address must not be banned
- Department must be active
- User must have valid email
- Filter rejection halts creation

---

#### 2. Ticket Assignment

**Methods**:
- `Ticket::assignToStaff($staff, $note, $alert)`
- `Ticket::assignToTeam($team, $note, $alert)`
- `Ticket::assign($form)` - Form-based assignment

**Flow**:
```
1. Validate assignment
   ├── Staff must be active
   ├── Staff must have department access
   └── Team must be active

2. Clear existing assignment
   ├── Clear referrals (if assigned)
   └── Update staff_id/team_id

3. Log assignment
   └── Create thread event

4. Notify (if alert=true)
   └── Send assignment notification
```

**Department Assignment Rules**:
| Flag | Behavior |
|------|----------|
| `FLAG_ASSIGN_MEMBERS_ONLY` | Only primary department members |
| `FLAG_ASSIGN_PRIMARY_ONLY` | Excludes extended access members |

**Invariants**:
- Staff must have access to ticket's department
- Staff on vacation cannot be assigned (unless forced)
- Unassignment clears both staff and team

---

#### 3. Ticket Transfer

**Method**: `Ticket::transfer($dept_id, $comments, $alert)`

**Flow**:
```
1. Validate transfer
   ├── Target department exists
   └── Target department is active

2. Update department
   └── Set new dept_id

3. Re-evaluate SLA
   ├── Use department default SLA
   └── Recalculate due date

4. Handle staff assignment
   ├── Clear if staff not in new dept
   └── Keep if staff has access

5. Reopen if closed
   └── Set status to open

6. Post internal note
   └── Document transfer reason

7. Clear referrals
   └── Remove department referrals

8. Log event
   └── Record transfer
```

**Invariants**:
- Closed tickets are reopened on transfer
- Staff assignment cleared if no access to new department
- SLA recalculated based on new department

---

#### 4. Ticket Status Changes

**Method**: `Ticket::setStatus($status, $comments, &$errors, $set_closing_agent)`

**States**:
| State | Description | Transitions |
|-------|-------------|-------------|
| `open` | Active ticket | → closed |
| `closed` | Resolved | → open (reopen) |
| `archived` | Archived | Read-only |
| `deleted` | Marked for deletion | Hard delete |

**Close Flow**:
```
1. Check closeable
   └── isCloseable() validation

2. Set status
   └── Update status_id

3. Record closing agent
   └── staff_id = closing staff

4. Clear flags
   ├── Clear overdue flag
   └── Clear due dates

5. Set closed timestamp
   └── closed = NOW()

6. Log event
   └── Record closure
```

**Reopen Flow**:
```
1. Check reopenable
   └── isReopenable() validation

2. Set status to open
   └── Update status_id

3. Re-assign (if enabled)
   └── Assign to closing agent

4. Set reopen timestamp
   └── reopened = NOW()

5. Log event
   └── Record reopen
```

**Invariants**:
- Only staff with PERM_CLOSE can close tickets
- Deleted status triggers permanent deletion
- Reopened tickets may auto-assign to closing agent

---

#### 5. Ticket Merging

**Method**: `Ticket::merge($tickets, $form)`

**Flow**:
```
1. Validate merge
   ├── Target tickets exist
   └── Staff has permission

2. For each source ticket:
   ├── Set parent (ticket_pid)
   ├── Migrate collaborators
   ├── Migrate tasks (optional)
   ├── Preserve thread entries
   └── Update status (per config)

3. Update parent ticket
   ├── Consolidate thread data
   └── Update timestamps

4. Log events
   └── Record merge on all tickets
```

**Options**:
- Merge tasks to parent
- Set child ticket status
- Preserve original data in extra field

**Invariants**:
- Merged tickets maintain parent reference
- Access to parent grants access to children
- Thread entries preserved with merge metadata

---

#### 6. Reply Posting

**Method**: `Ticket::postReply($vars, &$errors, $alert, $claim)`

**Flow**:
```
1. Validate reply
   ├── Body content required
   └── Staff has permission

2. Create thread entry
   ├── Type = 'R' (response)
   ├── Set staff author
   └── Store recipients

3. Process recipients
   ├── To: User email
   ├── CC: Collaborators
   └── BCC: Additional

4. Auto-claim (if enabled)
   └── Assign to replying staff

5. Update ticket
   ├── Set answered flag
   ├── Update lastupdate
   └── Update status (optional)

6. Inject signature
   └── Department or staff signature

7. Send notification
   └── Email to recipients

8. Log event
   └── Record response
```

**Invariants**:
- Reply requires PERM_REPLY permission
- Auto-claim only if department allows
- Signature determined by staff preference

---

### User Operations

#### User Creation

**Methods**:
- `UserModel::create($vars)` - Direct creation
- `User::fromVars($vars)` - From form data
- `User::fromEmail($email)` - From email lookup

**Flow**:
```
1. Validate input
   ├── Email required
   ├── Email not banned
   └── Email unique

2. Create user record
   ├── Set name
   └── Set status

3. Create user email
   ├── Create UserEmail record
   └── Set as default

4. Organization association
   ├── Match by domain (if configured)
   └── Or explicit assignment

5. Process custom data
   └── Save form entries
```

**Invariants**:
- Email addresses must be unique
- Banned emails prevent creation
- Organization auto-association by domain

---

#### User Account Management

**Features**:
- Portal login credentials
- Password reset flow
- Backend authentication (LDAP, OAuth)
- Two-factor authentication

**Account States**:
| Status | Description |
|--------|-------------|
| Active | Can login |
| Locked | Temporarily blocked |
| Disabled | Permanently disabled |

---

### Staff Operations

#### Staff Authentication

**Flow**:
```
1. Lookup by username/email
2. Validate backend
   ├── Local password check
   ├── LDAP authentication
   └── OAuth/SSO
3. Check 2FA (if enabled)
4. Validate account status
   ├── Must be active
   └── Check password expiry
5. Create session
6. Log login event
```

**Invariants**:
- Staff must be active
- Force password change if flagged
- Session bound to IP (if configured)

---

#### Department Access

**Access Types**:
| Type | Description |
|------|-------------|
| Primary | Default department assignment |
| Extended | Additional department access via StaffDeptAccess |

**Permission Resolution**:
```
1. Check primary department
   └── Use primary role permissions

2. Check extended access
   └── Use role from StaffDeptAccess

3. Check assignment
   └── Assigned tickets visible

4. Check referrals
   └── Referred tickets visible
```

---

### Email Operations

#### Inbound Email Processing

**Components**:
- `MailFetcher`: Polls configured mailboxes
- `MailParser`: Parses MIME messages
- `TicketFilter`: Routes messages

**Flow**:
```
1. Connect to mailbox
   ├── IMAP/POP3 protocol
   └── OAuth2 or password auth

2. Fetch messages
   ├── Limit by fetchmax setting
   └── Check folder configuration

3. Parse message
   ├── Extract headers
   ├── Parse MIME body
   ├── Extract attachments
   └── Detect charset

4. Identify thread
   ├── Match by Message-ID
   ├── Match by References header
   └── Match by ticket number in subject

5. Process message
   ├── New ticket: Create ticket
   └── Reply: Add to thread

6. Post-fetch action
   ├── Delete from server
   ├── Archive to folder
   └── Leave on server
```

**Bounce Detection**:
- Auto-reply headers detected
- Bounce patterns matched
- System email flagging

**Invariants**:
- Banned emails rejected
- System emails tracked to prevent loops
- Thread matching by multiple criteria

---

#### Outbound Email

**Methods**:
- `Mailer::send()` - Standard email
- `Ticket::sendAutoReply()` - Acknowledgment
- `Ticket::sendAlert()` - Staff notification

**Template Variables**:
| Variable | Description |
|----------|-------------|
| `%ticket` | Ticket object |
| `%user` | User object |
| `%staff` | Staff object |
| `%message` | Thread entry |
| `%signature` | Signature text |

**Invariants**:
- From address per department
- Auto-response respects noautoresp flag
- System emails tracked for loop prevention

---

### SLA Processing

#### Due Date Calculation

**Method**: `SLA::addGracePeriod($datetime, $format)`

**Flow**:
```
1. Get grace period hours
2. Check for schedule
   ├── If schedule: Use business hours
   └── If no schedule: Calendar hours
3. Add hours to start time
4. Return calculated due date
```

**Business Hours Calculation**:
```
For each hour to add:
  1. Check if current time in schedule
  2. If in schedule: Add hour
  3. If not: Skip to next scheduled period
  4. Handle holidays and exceptions
```

**Invariants**:
- Grace period always in hours
- Business hours exclude non-working periods
- Timezone from schedule or system default

---

#### Overdue Processing

**Method**: `Ticket::checkOverdue()` (static, via cron)

**Flow**:
```
1. Query open tickets where:
   ├── isoverdue = 0
   └── duedate <= NOW() OR est_duedate <= NOW()

2. For each ticket:
   ├── Set isoverdue = 1
   └── Update timestamp

3. Send alerts (if enabled)
   └── Per SLA FLAG_NOALERTS setting
```

**Invariants**:
- Only open tickets checked
- Either duedate or est_duedate triggers overdue
- SLA can suppress alerts

---

### Filter System

#### Filter Execution

**Method**: `TicketFilter::apply($ticket)`

**Flow**:
```
1. Load active filters (ordered by execorder)

2. For each filter:
   ├── Check target (Web/Email/API/Any)
   └── Evaluate rules

3. Rule evaluation:
   ├── If match_all_rules: AND logic
   └── If !match_all_rules: OR logic

4. On match:
   ├── Execute filter actions
   └── If stop_onmatch: Break

5. Return matched filter or null
```

**Filter Actions**:
| Action | Description |
|--------|-------------|
| `assign` | Assign to staff/team |
| `dept` | Route to department |
| `priority` | Set priority |
| `status` | Set status |
| `sla` | Set SLA |
| `topic` | Set help topic |
| `reject` | Reject ticket |
| `email` | Send email |

**Invariants**:
- Filters execute in order
- Stop on match halts processing
- Reject action prevents ticket creation

---

### Dynamic Forms

#### Form Processing

**Flow**:
```
1. Load form definition
   └── DynamicForm::lookup($id)

2. Render form fields
   ├── Text, textarea, select
   ├── Date, datetime
   ├── Checkbox, radio
   └── Custom types

3. Validate submission
   ├── Required field check
   ├── Type validation
   └── Custom validators

4. Save form entry
   ├── Create FormEntry
   └── Create FormEntryValues
```

**Form Types**:
| Type | Code | Usage |
|------|------|-------|
| Ticket | `T` | Ticket custom fields |
| User | `U` | User profile fields |
| Organization | `O` | Organization fields |
| Task | `A` | Task custom fields |
| Generic | `G` | Reusable forms |

**Custom Data Storage**:
- Form entries linked by object_type + object_id
- Values stored in form_entry_values
- Aggregated data in *__cdata tables

---

## Access Control

### Permission Model

#### Ticket Permissions

| Permission | Constant | Description |
|------------|----------|-------------|
| Create | `PERM_CREATE` | Create tickets on behalf of users |
| Edit | `PERM_EDIT` | Edit ticket information |
| Assign | `PERM_ASSIGN` | Assign to agents/teams |
| Release | `PERM_RELEASE` | Release assignment |
| Transfer | `PERM_TRANSFER` | Transfer between departments |
| Refer | `PERM_REFER` | Manage referrals |
| Merge | `PERM_MERGE` | Merge tickets |
| Link | `PERM_LINK` | Link related tickets |
| Reply | `PERM_REPLY` | Post replies |
| Mark Answered | `PERM_MARKANSWERED` | Toggle answered status |
| Close | `PERM_CLOSE` | Close/resolve tickets |
| Delete | `PERM_DELETE` | Delete tickets |

---

#### User Permissions

| Permission | Constant | Description |
|------------|----------|-------------|
| Create | `PERM_CREATE` | Create users |
| Edit | `PERM_EDIT` | Manage user info |
| Delete | `PERM_DELETE` | Delete users |
| Manage | `PERM_MANAGE` | Manage accounts |
| Directory | `PERM_DIRECTORY` | Access user directory |

---

#### Organization Permissions

| Permission | Constant | Description |
|------------|----------|-------------|
| Create | `PERM_CREATE` | Create organizations |
| Edit | `PERM_EDIT` | Manage organizations |
| Delete | `PERM_DELETE` | Delete organizations |

---

#### Task Permissions

| Permission | Constant | Description |
|------------|----------|-------------|
| Create | `PERM_CREATE` | Create tasks |
| Edit | `PERM_EDIT` | Edit tasks |
| Assign | `PERM_ASSIGN` | Assign tasks |
| Transfer | `PERM_TRANSFER` | Transfer tasks |
| Reply | `PERM_REPLY` | Post to task thread |
| Close | `PERM_CLOSE` | Close tasks |
| Delete | `PERM_DELETE` | Delete tasks |

---

### Permission Resolution

**Method**: `Ticket::checkStaffPerm($staff, $perm)`

**Resolution Order**:
```
1. Check department access
   └── Staff has primary or extended access?

2. Check assignment
   └── Staff assigned to ticket?

3. Check referral
   └── Ticket referred to staff/dept?

4. Check role permission
   └── Role contains permission?

5. Return result
```

---

### User Access Control

**Method**: `Ticket::checkUserAccess($user)`

**Access Levels**:
```
1. Ticket owner
   └── user_id matches

2. Organization member
   ├── Same organization
   └── Org ticket visibility enabled

3. Active collaborator
   └── In thread_collaborator (active)

4. CC collaborator
   └── In thread_collaborator (CC flag)

5. Merged ticket access
   └── Access to parent/child grants access
```

---

## Scheduled Tasks (Cron)

### Task Registry

| Task | Frequency | Description |
|------|-----------|-------------|
| MailFetcher | 5 min | Fetch and process incoming emails |
| TicketMonitor | 5 min | Check overdue tickets, cleanup locks |
| PurgeLogs | Daily (probabilistic) | Clean old system logs |
| PurgeDrafts | Each run | Remove stale drafts |
| CleanOrphanedFiles | 1/10 runs | Delete unlinked attachments |
| CleanExpiredSessions | Each run | Remove expired sessions |
| CleanPwResets | Each run | Remove expired password tokens |
| MaybeOptimizeTables | Weekly (probabilistic) | Database table optimization |

### Cron Execution

**Methods**:
- API endpoint with authentication (`api/cron.php`)
- CLI execution (`LocalCronApiController::call()`)

**Requirements**:
- Valid API key with `can_exec_cron` permission
- Or command-line access

---

## API

### Ticket API

**Endpoint**: `api/http.php/tickets.json`

**Operations**:
| Method | Path | Description |
|--------|------|-------------|
| POST | /tickets.json | Create ticket |
| POST | /tickets.xml | Create ticket (XML) |
| POST | /tickets.email | Create from email |

**Request Format** (JSON):
```json
{
  "email": "user@example.com",
  "name": "User Name",
  "subject": "Ticket Subject",
  "message": "Ticket body content",
  "topicId": 1,
  "attachments": [
    {
      "name": "file.pdf",
      "type": "application/pdf",
      "data": "base64encoded..."
    }
  ]
}
```

**Authentication**:
- API key in `X-API-Key` header
- IP whitelist validation

**Invariants**:
- API key must be active
- IP must match configured address
- `can_create_tickets` permission required

---

## Concurrency Control

### Ticket Locking

**Mechanism**: Pessimistic locking via `lock` table

**Flow**:
```
1. Staff views ticket
   └── Create lock record

2. Lock contains:
   ├── staff_id
   ├── expire time
   └── lock code

3. Other staff sees lock warning
   └── Can break lock if expired

4. Lock cleanup via cron
   └── Remove expired locks
```

**Invariants**:
- Only one active lock per ticket
- Lock expires after configured period
- Breaking lock requires permission

---

## Audit Trail

### Thread Events

All significant actions are logged to `thread_event`:

| Event | Description |
|-------|-------------|
| created | Ticket/task created |
| closed | Ticket closed |
| reopened | Ticket reopened |
| assigned | Assignment changed |
| transferred | Department changed |
| overdue | Marked overdue |
| edited | Ticket edited |
| merged | Tickets merged |
| linked | Tickets linked |
| referred | Ticket referred |
| claimed | Ticket claimed |
| released | Assignment released |

**Event Data**:
- Actor (staff/user/system)
- Timestamp
- Event-specific data (JSON)
- Annulment flag (for corrections)

---

## Invariants Summary

### Ticket Invariants

1. **Creation**
   - Email must not be banned
   - Department must be active
   - Topic must be active (if public)
   - Required form fields must be filled

2. **Assignment**
   - Staff must be active and not on vacation
   - Staff must have department access
   - Team must be active

3. **Status**
   - Only open → closed allowed (unless reopen)
   - Deleted state triggers permanent deletion
   - Closing agent recorded for reopen

4. **SLA**
   - Due date calculated from creation time
   - Business hours from schedule
   - Overdue flag set via cron

5. **Access**
   - Department access required for staff
   - Organization visibility optional for users
   - Collaborators have limited access

### User Invariants

1. Email address must be unique
2. Primary email required
3. Banned emails prevent all operations

### Staff Invariants

1. Username must be unique
2. Primary department required
3. Primary role required
4. Vacation mode excludes from assignment

### Organization Invariants

1. Name must be unique (if enforced)
2. Domain used for auto-association
3. Account manager can be Staff or Team

---

## Extension Points

### Signals (Events)

osTicket uses a signal system for extensibility:

| Signal | Description |
|--------|-------------|
| `cron` | Cron job execution |
| `ticket.created` | Ticket created |
| `ticket.closed` | Ticket closed |
| `ticket.assigned` | Ticket assigned |
| `mail.received` | Email received |
| `model.created` | Any model created |
| `model.updated` | Any model updated |
| `model.deleted` | Any model deleted |

**Usage**:
```php
Signal::connect('ticket.created', function($ticket) {
    // Custom logic
});
```

### Plugins

Plugins can extend functionality via:
- Custom filter actions
- Custom form field types
- Custom authentication backends
- Custom storage backends
- Signal handlers

**Source**: `include/class.plugin.php`

---

## Configuration

### System Configuration

Key configuration items in `config` table:

| Key | Namespace | Description |
|-----|-----------|-------------|
| `helpdesk_url` | core | Base URL |
| `helpdesk_title` | core | Site title |
| `default_dept_id` | core | Default department |
| `default_sla_id` | core | Default SLA |
| `default_priority_id` | core | Default priority |
| `default_template_id` | core | Default email template |
| `enable_kb` | core | Knowledge base enabled |
| `enable_captcha` | core | CAPTCHA enabled |
| `auto_claim_tickets` | core | Auto-claim on reply |
| `collaborator_ticket_visibility` | core | Collaborator access |
| `require_topic_to_close` | core | Require topic for closure |

### Department Configuration

Each department has:
- Email templates
- Default SLA
- Business hours schedule
- Auto-response settings
- Assignment policies

### Email Configuration

Each email account has:
- IMAP/POP3 settings
- SMTP settings
- Fetch frequency
- Post-fetch actions
- Authentication credentials

---

## Error Handling

### Validation Errors

Form and operation errors returned via `$errors` array:
- Field-specific errors by field name
- General errors in `err` key

### System Errors

Logged to `syslog` table:
- Debug, Warning, Error levels
- Logger identification
- IP address tracking

### Email Errors

Email account errors tracked:
- Error count
- Last error message
- Last error timestamp
- Auto-disable after threshold
