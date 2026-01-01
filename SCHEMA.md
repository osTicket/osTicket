# osTicket Database Schema

This document provides a comprehensive reference for the osTicket database schema.

## Overview

- **Database Engine**: MySQL (primary supported database)
- **Default Charset**: UTF-8
- **Table Prefix**: Configurable via `%TABLE_PREFIX%` (default: `ost_`)
- **Total Tables**: 66
- **Schema Location**: `setup/inc/streams/core/install-mysql.sql`
- **Migrations**: `include/upgrader/streams/core/` (99 migration files)

## Database Variants

> **Note**: osTicket currently supports **MySQL only**. The schema uses MySQL-specific syntax including:
> - `AUTO_INCREMENT` for auto-incrementing columns
> - `ENGINE=MyISAM` / `ENGINE=InnoDB` specifications
> - MySQL-specific collations (`utf8_unicode_ci`, `ascii_general_ci`, `ascii_bin`)

### Engine Selection

| Engine | Tables | Use Case |
|--------|--------|----------|
| MyISAM | Most tables | Default, optimized for reads |
| InnoDB | `sequence`, `thread_referral`, `event` | Transaction support, row-level locking |

---

## Table Reference

### Configuration & System Tables

#### `config`
System configuration key-value store.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `namespace` | VARCHAR(64) | NOT NULL | Configuration namespace |
| `key` | VARCHAR(64) | NOT NULL | Configuration key |
| `value` | TEXT | NOT NULL | Configuration value |
| `updated` | TIMESTAMP | | Last update time |

**Indexes**: UNIQUE(`namespace`, `key`)

---

#### `sequence`
Auto-increment sequence management for ticket numbers.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `name` | VARCHAR(64) | | Sequence name |
| `flags` | INT(10) UNSIGNED | DEFAULT 0 | |
| `next` | BIGINT(20) UNSIGNED | NOT NULL, DEFAULT 1 | Next value |
| `increment` | INT(11) | DEFAULT 1 | Increment step |
| `padding` | CHAR(1) | DEFAULT '0' | Padding character |
| `updated` | DATETIME | | Last update |

**Engine**: InnoDB (required for row-level locking)

---

#### `syslog`
System logs and audit trails.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `log_id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `log_type` | ENUM('Debug','Warning','Error') | NOT NULL | Log level |
| `title` | VARCHAR(255) | NOT NULL | Log title |
| `log` | TEXT | NOT NULL | Log message |
| `logger` | VARCHAR(64) | NOT NULL | Logger identifier |
| `ip_address` | VARCHAR(64) | NOT NULL | Client IP |
| `created` | DATETIME | NOT NULL | Creation time |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `log_type`, `created`

---

#### `session`
User session management.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `session_id` | VARCHAR(255) | PK, ascii_general_ci | Session identifier |
| `session_data` | BLOB | | Serialized session data |
| `session_expire` | DATETIME | | Expiration time |
| `session_updated` | DATETIME | | Last activity |
| `user_id` | VARCHAR(16) | | Associated user/staff |
| `user_ip` | VARCHAR(64) | | Client IP |
| `user_agent` | VARCHAR(255) | utf8_unicode_ci | Browser user agent |

**Indexes**: `updated` (`session_updated`, `session_id`)

---

#### `event`
Event definitions and triggers.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(10) UNSIGNED | PK, AUTO_INCREMENT | |
| `name` | VARCHAR(60) | NOT NULL | Event name |
| `description` | VARCHAR(60) | | Event description |

**Engine**: InnoDB

---

### User & Account Tables

#### `user`
End user/client profiles.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(10) UNSIGNED | PK, AUTO_INCREMENT | |
| `org_id` | INT(11) UNSIGNED | DEFAULT 0 | Organization FK |
| `default_email_id` | INT(10) UNSIGNED | NOT NULL | Primary email FK |
| `status` | INT(11) UNSIGNED | DEFAULT 0 | Account status flags |
| `name` | VARCHAR(128) | NOT NULL | Display name |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `org_id`, `default_email_id`, `name`

---

#### `user_email`
User email addresses (supports multiple per user).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(10) UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | INT(10) UNSIGNED | NOT NULL | User FK |
| `flags` | INT(10) UNSIGNED | DEFAULT 0 | Email flags |
| `address` | VARCHAR(255) | NOT NULL | Email address |

**Indexes**: UNIQUE(`address`), `user_id`

---

#### `user_account`
User portal login credentials.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | INT(10) UNSIGNED | NOT NULL | User FK |
| `status` | INT(11) UNSIGNED | DEFAULT 0 | Account status |
| `timezone` | VARCHAR(64) | | User timezone |
| `lang` | VARCHAR(16) | | Preferred language |
| `username` | VARCHAR(64) | | Login username |
| `passwd` | VARCHAR(128) | ascii_bin | Password hash |
| `backend` | VARCHAR(32) | | Auth backend |
| `extra` | TEXT | | Additional data (JSON) |
| `registered` | DATETIME | | Registration date |

**Indexes**: UNIQUE(`username`), `user_id`

---

#### `user__cdata`
User custom data fields (dynamic form data).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `user_id` | INT(11) UNSIGNED | PK | User FK |
| *dynamic* | *varies* | | Custom fields from forms |

---

#### `organization`
Client organizations/companies.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `name` | VARCHAR(128) | NOT NULL | Organization name |
| `manager` | VARCHAR(16) | | Account manager reference |
| `status` | INT(11) UNSIGNED | DEFAULT 0 | Status flags |
| `domain` | VARCHAR(256) | | Email domain for auto-add |
| `extra` | TEXT | | Additional data (JSON) |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `name`

---

#### `organization__cdata`
Organization custom data fields.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `org_id` | INT(11) UNSIGNED | PK | Organization FK |
| *dynamic* | *varies* | | Custom fields from forms |

---

### Staff & Access Control Tables

#### `staff`
Help desk agents/support staff.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `staff_id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `dept_id` | INT(10) UNSIGNED | NOT NULL, DEFAULT 0 | Primary department FK |
| `role_id` | INT(10) UNSIGNED | NOT NULL, DEFAULT 0 | Primary role FK |
| `username` | VARCHAR(32) | NOT NULL | Login username |
| `firstname` | VARCHAR(32) | | First name |
| `lastname` | VARCHAR(32) | | Last name |
| `passwd` | VARCHAR(128) | | Password hash |
| `backend` | VARCHAR(32) | | Auth backend |
| `email` | VARCHAR(128) | | Staff email |
| `phone` | VARCHAR(24) | | Phone number |
| `phone_ext` | VARCHAR(6) | | Extension |
| `mobile` | VARCHAR(24) | | Mobile number |
| `signature` | TEXT | | Email signature |
| `lang` | VARCHAR(16) | | Preferred language |
| `timezone` | VARCHAR(64) | | Timezone |
| `locale` | VARCHAR(16) | | Locale setting |
| `notes` | TEXT | | Admin notes |
| `isactive` | TINYINT(1) | DEFAULT 1 | Active status |
| `isadmin` | TINYINT(1) | DEFAULT 0 | Admin flag |
| `isvisible` | TINYINT(1) | DEFAULT 1 | Visibility in lists |
| `onvacation` | TINYINT(1) | DEFAULT 0 | Vacation mode |
| `assigned_only` | TINYINT(1) | DEFAULT 0 | See assigned only |
| `show_assigned_tickets` | TINYINT(1) | DEFAULT 0 | UI preference |
| `change_passwd` | TINYINT(1) | DEFAULT 0 | Force password change |
| `max_page_size` | INT(11) UNSIGNED | DEFAULT 0 | Pagination preference |
| `auto_refresh_rate` | INT(10) UNSIGNED | DEFAULT 0 | Auto-refresh rate |
| `default_signature_type` | ENUM('none','mine','dept') | DEFAULT 'none' | Signature preference |
| `default_paper_size` | ENUM('Letter','Legal','Ledger','A4','A3') | DEFAULT 'Letter' | PDF paper size |
| `extra` | TEXT | | Additional data (JSON) |
| `permissions` | TEXT | | Permission overrides |
| `created` | DATETIME | NOT NULL | Creation date |
| `lastlogin` | DATETIME | | Last login time |
| `passwdreset` | DATETIME | | Password reset time |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: UNIQUE(`username`), `dept_id`, `isadmin`, `isactive`, `onvacation`

---

#### `staff_dept_access`
Extended department access for staff.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `staff_id` | INT(10) UNSIGNED | PK | Staff FK |
| `dept_id` | INT(10) UNSIGNED | PK | Department FK |
| `role_id` | INT(10) UNSIGNED | NOT NULL | Role for this dept |
| `flags` | INT(10) UNSIGNED | DEFAULT 1 | Access flags |

**Indexes**: `dept_id`

---

#### `role`
Staff roles with permission sets.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `flags` | INT(10) UNSIGNED | DEFAULT 1 | Role flags |
| `name` | VARCHAR(64) | NOT NULL | Role name |
| `permissions` | TEXT | | Permission JSON |
| `notes` | TEXT | | Role description |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: UNIQUE(`name`)

---

#### `group`
Staff groups (legacy, maintained for compatibility).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(10) UNSIGNED | PK, AUTO_INCREMENT | |
| `role_id` | INT(10) UNSIGNED | DEFAULT 0 | Default role FK |
| `flags` | INT(10) UNSIGNED | DEFAULT 1 | Group flags |
| `name` | VARCHAR(120) | NOT NULL | Group name |
| `notes` | TEXT | | Group notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

---

### Department & Team Tables

#### `department`
Support departments with routing configuration.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `pid` | INT(11) UNSIGNED | DEFAULT 0 | Parent department FK |
| `tpl_id` | INT(10) UNSIGNED | DEFAULT 0 | Email template set FK |
| `sla_id` | INT(10) UNSIGNED | DEFAULT 0 | Default SLA FK |
| `schedule_id` | INT(10) UNSIGNED | DEFAULT 0 | Business hours FK |
| `email_id` | INT(10) UNSIGNED | DEFAULT 0 | Outgoing email FK |
| `autoresp_email_id` | INT(10) UNSIGNED | DEFAULT 0 | Auto-response email FK |
| `manager_id` | INT(10) UNSIGNED | DEFAULT 0 | Department manager FK |
| `flags` | INT(10) UNSIGNED | DEFAULT 0 | Department flags |
| `name` | VARCHAR(128) | NOT NULL | Department name |
| `signature` | TEXT | NOT NULL | Department signature |
| `ispublic` | TINYINT(1) UNSIGNED | DEFAULT 1 | Public visibility |
| `group_membership` | TINYINT(1) | DEFAULT 0 | Extended access mode |
| `ticket_auto_response` | TINYINT(1) | DEFAULT 1 | Auto-response enabled |
| `message_auto_response` | TINYINT(1) | DEFAULT 0 | Message auto-response |
| `path` | VARCHAR(128) | NOT NULL | Hierarchical path |
| `updated` | DATETIME | NOT NULL | Last update |
| `created` | DATETIME | NOT NULL | Creation date |

**Indexes**: UNIQUE(`name`, `pid`), `manager_id`, `autoresp_email_id`, `tpl_id`, `flags`

---

#### `team`
Support teams for group assignment.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `team_id` | INT(10) UNSIGNED | PK, AUTO_INCREMENT | |
| `lead_id` | INT(10) UNSIGNED | DEFAULT 0 | Team lead staff FK |
| `flags` | INT(10) UNSIGNED | DEFAULT 1 | Team flags |
| `name` | VARCHAR(125) | NOT NULL | Team name |
| `notes` | TEXT | | Team notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: UNIQUE(`name`), `lead_id`

---

#### `team_member`
Team membership junction table.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `team_id` | INT(10) UNSIGNED | PK | Team FK |
| `staff_id` | INT(10) UNSIGNED | PK | Staff FK |
| `flags` | INT(10) UNSIGNED | DEFAULT 0 | Membership flags |

**Indexes**: `staff_id`

---

### Ticket Tables

#### `ticket`
Core support tickets table.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `ticket_id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `ticket_pid` | INT(11) UNSIGNED | DEFAULT 0 | Parent ticket (for merges) |
| `number` | VARCHAR(20) | NOT NULL | Ticket number |
| `user_id` | INT(11) UNSIGNED | NOT NULL, DEFAULT 0 | Ticket owner FK |
| `user_email_id` | INT(11) UNSIGNED | DEFAULT NULL | Owner email FK |
| `status_id` | INT(10) UNSIGNED | NOT NULL, DEFAULT 0 | Current status FK |
| `dept_id` | INT(10) UNSIGNED | NOT NULL, DEFAULT 0 | Department FK |
| `sla_id` | INT(10) UNSIGNED | DEFAULT 0 | SLA FK |
| `topic_id` | INT(10) UNSIGNED | DEFAULT 0 | Help topic FK |
| `staff_id` | INT(10) UNSIGNED | DEFAULT 0 | Assigned staff FK |
| `team_id` | INT(10) UNSIGNED | DEFAULT 0 | Assigned team FK |
| `email_id` | INT(11) UNSIGNED | DEFAULT 0 | Receiving email FK |
| `lock_id` | INT(11) UNSIGNED | DEFAULT 0 | Edit lock FK |
| `flags` | INT(10) UNSIGNED | DEFAULT 0 | Ticket flags |
| `sort` | INT(11) UNSIGNED | DEFAULT 0 | Sort order |
| `ip_address` | VARCHAR(64) | NOT NULL, DEFAULT '' | Creator IP |
| `source` | ENUM('Web','Email','Phone','API','Other') | DEFAULT 'Other' | Ticket source |
| `source_extra` | VARCHAR(40) | | Additional source info |
| `isoverdue` | TINYINT(1) UNSIGNED | DEFAULT 0 | Overdue flag |
| `isanswered` | TINYINT(1) UNSIGNED | DEFAULT 0 | Answered flag |
| `duedate` | DATETIME | | User-set due date |
| `est_duedate` | DATETIME | | SLA-calculated due date |
| `reopened` | DATETIME | | Last reopen time |
| `closed` | DATETIME | | Closure time |
| `lastupdate` | DATETIME | | Last activity |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `user_id`, `dept_id`, `staff_id`, `team_id`, `status_id`, `created`, `closed`, `duedate`, `topic_id`, `sla_id`, `ticket_pid`

---

#### `ticket__cdata`
Ticket custom data fields.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `ticket_id` | INT(11) UNSIGNED | PK | Ticket FK |
| *dynamic* | *varies* | | Custom fields from forms |

---

#### `ticket_status`
Ticket status definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `name` | VARCHAR(60) | NOT NULL | Status name |
| `state` | VARCHAR(16) | NOT NULL | State: open, closed, archived, deleted |
| `mode` | INT(11) UNSIGNED | NOT NULL, DEFAULT 0 | Status mode |
| `flags` | INT(11) UNSIGNED | NOT NULL, DEFAULT 0 | Status flags |
| `sort` | INT(11) UNSIGNED | NOT NULL, DEFAULT 0 | Sort order |
| `properties` | TEXT | NOT NULL | JSON properties |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: UNIQUE(`name`), `state`

---

#### `ticket_priority`
Priority level definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `priority_id` | TINYINT(4) UNSIGNED | PK, AUTO_INCREMENT | |
| `priority` | VARCHAR(60) | NOT NULL | Priority name |
| `priority_desc` | VARCHAR(30) | NOT NULL | Short description |
| `priority_color` | VARCHAR(7) | NOT NULL | Hex color code |
| `priority_urgency` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 0 | Urgency level (0-4) |
| `ispublic` | TINYINT(1) | DEFAULT 1 | Public visibility |

**Indexes**: UNIQUE(`priority`), `priority_urgency`, `ispublic`

---

#### `lock`
Pessimistic edit locks for tickets.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `lock_id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `staff_id` | INT(10) UNSIGNED | NOT NULL, DEFAULT 0 | Lock holder FK |
| `expire` | DATETIME | | Lock expiration |
| `code` | VARCHAR(20) | | Lock code |
| `created` | DATETIME | NOT NULL | Creation time |

---

### Thread & Communication Tables

#### `thread`
Ticket/task communication threads.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `object_id` | INT(11) UNSIGNED | NOT NULL | Parent object ID |
| `object_type` | CHAR(1) | NOT NULL | Object type code |
| `extra` | TEXT | | Additional data (JSON) |
| `lastresponse` | DATETIME | | Last response time |
| `lastmessage` | DATETIME | | Last message time |
| `created` | DATETIME | NOT NULL | Creation date |

**Indexes**: `object_id`, `object_type`

**Object Type Codes**:
- `T` = Ticket
- `A` = Task
- `C` = Child Ticket (merged)

---

#### `thread_entry`
Individual thread messages/responses.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `pid` | INT(11) UNSIGNED | DEFAULT 0 | Parent entry (for threading) |
| `thread_id` | INT(11) UNSIGNED | NOT NULL | Thread FK |
| `staff_id` | INT(11) UNSIGNED | DEFAULT 0 | Staff author FK |
| `user_id` | INT(11) UNSIGNED | DEFAULT 0 | User author FK |
| `type` | CHAR(1) | NOT NULL, DEFAULT '' | Entry type |
| `flags` | INT(11) UNSIGNED | DEFAULT 0 | Entry flags |
| `poster` | VARCHAR(128) | NOT NULL, DEFAULT '' | Poster name |
| `editor` | INT(10) UNSIGNED | | Editor ID |
| `editor_type` | CHAR(1) | | Editor type (S=Staff, U=User) |
| `source` | VARCHAR(32) | NOT NULL, DEFAULT '' | Entry source |
| `title` | VARCHAR(255) | | Entry title/subject |
| `body` | TEXT | NOT NULL | Message body |
| `format` | VARCHAR(16) | NOT NULL, DEFAULT 'html' | Body format |
| `ip_address` | VARCHAR(64) | NOT NULL, DEFAULT '' | Author IP |
| `extra` | TEXT | | Additional data (JSON) |
| `recipients` | TEXT | | Recipient list |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL, DEFAULT '0000-00-00 00:00:00' | Last update |

**Indexes**: `pid`, `thread_id`, `staff_id`, `type`

**Entry Type Codes**:
- `M` = Message (from user)
- `R` = Response (from staff)
- `N` = Note (internal)

---

#### `thread_entry_email`
Email metadata for thread entries.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `thread_entry_id` | INT(11) UNSIGNED | NOT NULL | Thread entry FK |
| `email_id` | INT(11) UNSIGNED | DEFAULT 0 | Receiving email FK |
| `mid` | VARCHAR(255) | | Message-ID header |
| `headers` | TEXT | | Raw email headers |

**Indexes**: `thread_entry_id`, `mid`, `email_id`

---

#### `thread_entry_merge`
Merged thread entry data preservation.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `thread_entry_id` | INT(11) UNSIGNED | NOT NULL | Thread entry FK |
| `data` | TEXT | | Preserved merge data (JSON) |

---

#### `thread_event`
Thread activity log/audit trail.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `thread_id` | INT(11) UNSIGNED | NOT NULL | Thread FK |
| `thread_type` | CHAR(1) | DEFAULT '' | Thread object type |
| `event_id` | INT(11) UNSIGNED | DEFAULT NULL | Event definition FK |
| `staff_id` | INT(11) UNSIGNED | DEFAULT NULL | Acting staff FK |
| `team_id` | INT(11) UNSIGNED | DEFAULT NULL | Acting team FK |
| `dept_id` | INT(11) UNSIGNED | DEFAULT NULL | Related department FK |
| `topic_id` | INT(11) UNSIGNED | DEFAULT NULL | Related topic FK |
| `data` | TEXT | | Event data (JSON) |
| `username` | VARCHAR(128) | NOT NULL, DEFAULT 'SYSTEM' | Actor username |
| `uid` | INT(11) UNSIGNED | | Actor user ID |
| `uid_type` | CHAR(1) | NOT NULL, DEFAULT 'S' | Actor type |
| `annulled` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 0 | Event annulled |
| `timestamp` | DATETIME | NOT NULL | Event time |

**Indexes**:
- `ticket_state` (`thread_id`, `event_id`, `timestamp`)
- `ticket_stats` (`timestamp`, `event_id`)

---

#### `thread_referral`
Inter-ticket cross-references/referrals.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `thread_id` | INT(11) UNSIGNED | NOT NULL | Source thread FK |
| `object_id` | INT(11) UNSIGNED | NOT NULL | Target object ID |
| `object_type` | CHAR(1) | NOT NULL | Target object type |
| `created` | DATETIME | NOT NULL | Creation date |

**Engine**: InnoDB
**Indexes**: UNIQUE(`object_id`, `object_type`, `thread_id`), `thread_id`

---

#### `thread_collaborator`
External collaborators on ticket threads.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `flags` | INT(10) UNSIGNED | DEFAULT 1 | Collaborator flags |
| `thread_id` | INT(11) UNSIGNED | NOT NULL | Thread FK |
| `user_id` | INT(11) UNSIGNED | NOT NULL | Collaborator user FK |
| `role` | CHAR(1) | NOT NULL, DEFAULT 'M' | Role: M=Participant, C=CC |
| `created` | DATETIME | NOT NULL | Addition date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: UNIQUE(`thread_id`, `user_id`), `user_id`

---

### Task Tables

#### `task`
Internal work items/sub-tickets.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `object_id` | INT(11) UNSIGNED | DEFAULT 0 | Parent object ID |
| `object_type` | CHAR(1) | DEFAULT '' | Parent object type |
| `number` | VARCHAR(20) | | Task number |
| `dept_id` | INT(10) UNSIGNED | NOT NULL, DEFAULT 0 | Department FK |
| `staff_id` | INT(10) UNSIGNED | DEFAULT 0 | Assigned staff FK |
| `team_id` | INT(10) UNSIGNED | DEFAULT 0 | Assigned team FK |
| `lock_id` | INT(11) UNSIGNED | DEFAULT 0 | Edit lock FK |
| `flags` | INT(10) UNSIGNED | DEFAULT 0 | Task flags |
| `duedate` | DATETIME | | Due date |
| `closed` | DATETIME | | Closure date |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `dept_id`, `staff_id`, `team_id`, `created`, `object` (`object_id`, `object_type`), `flags`

---

#### `task__cdata`
Task custom data fields.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `task_id` | INT(11) UNSIGNED | PK | Task FK |
| *dynamic* | *varies* | | Custom fields from forms |

---

### Note & Draft Tables

#### `note`
Internal notes (non-ticket).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `pid` | INT(11) UNSIGNED | DEFAULT 0 | Parent note (for threading) |
| `staff_id` | INT(11) UNSIGNED | NOT NULL, DEFAULT 0 | Author staff FK |
| `ext_id` | VARCHAR(10) | | External reference ID |
| `body` | TEXT | | Note content |
| `status` | INT(11) UNSIGNED | DEFAULT 0 | Note status |
| `sort` | INT(11) UNSIGNED | DEFAULT 0 | Sort order |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `ext_id`

---

#### `draft`
Unsaved message drafts.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `staff_id` | INT(11) UNSIGNED | NOT NULL | Author staff FK |
| `namespace` | VARCHAR(32) | NOT NULL | Draft context namespace |
| `body` | TEXT | NOT NULL | Draft content |
| `extra` | TEXT | | Additional data (JSON) |
| `created` | TIMESTAMP | | Creation time |
| `updated` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last update |

**Indexes**: `staff_id`, `namespace`

---

### Email & Communication Tables

#### `email`
Email addresses and department associations.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `email_id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `noautoresp` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 0 | Disable auto-response |
| `priority_id` | TINYINT(4) UNSIGNED | DEFAULT 2 | Default priority FK |
| `dept_id` | INT(10) UNSIGNED | DEFAULT 0 | Default department FK |
| `topic_id` | INT(11) UNSIGNED | DEFAULT 0 | Default topic FK |
| `email` | VARCHAR(255) | NOT NULL | Email address |
| `name` | VARCHAR(255) | NOT NULL | Display name |
| `notes` | TEXT | | Admin notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: UNIQUE(`email`), `priority_id`, `dept_id`

---

#### `email_account`
Email account configurations (IMAP/SMTP).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `email_id` | INT(11) UNSIGNED | NOT NULL | Email FK |
| `type` | ENUM('mailbox','smtp') | NOT NULL | Account type |
| `auth_bk` | VARCHAR(64) | | Auth backend |
| `auth_id` | TEXT | | Auth credentials |
| `active` | TINYINT(1) | DEFAULT 1 | Active status |
| `host` | VARCHAR(255) | | Server hostname |
| `port` | INT(11) | | Server port |
| `folder` | VARCHAR(255) | | IMAP folder |
| `protocol` | ENUM('IMAP','POP','SMTP','OTHER') | | Protocol type |
| `encryption` | ENUM('NONE','AUTO','SSL') | | Encryption type |
| `fetchfreq` | TINYINT(3) | DEFAULT 5 | Fetch frequency (minutes) |
| `fetchmax` | TINYINT(4) UNSIGNED | DEFAULT 30 | Max messages per fetch |
| `postfetch` | ENUM('delete','nothing','archive') | DEFAULT 'nothing' | Post-fetch action |
| `archivefolder` | VARCHAR(255) | | Archive folder path |
| `allow_spoofing` | TINYINT(1) UNSIGNED | DEFAULT 0 | Allow from spoofing |
| `num_errors` | TINYINT(3) UNSIGNED | DEFAULT 0 | Error count |
| `last_error_msg` | VARCHAR(255) | | Last error message |
| `last_error` | DATETIME | | Last error time |
| `last_activity` | DATETIME | | Last activity time |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `email_id`, `type`

---

#### `email_template_group`
Email template sets.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `tpl_id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `isactive` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 0 | Active status |
| `name` | VARCHAR(32) | NOT NULL | Template set name |
| `lang` | VARCHAR(16) | DEFAULT 'en_US' | Language code |
| `notes` | TEXT | | Admin notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

---

#### `email_template`
Individual email templates.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `tpl_id` | INT(11) UNSIGNED | NOT NULL, DEFAULT 0 | Template set FK |
| `code_name` | VARCHAR(32) | NOT NULL | Template identifier |
| `subject` | VARCHAR(255) | NOT NULL | Email subject template |
| `body` | TEXT | NOT NULL | Email body template |
| `notes` | TEXT | | Admin notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: UNIQUE(`tpl_id`, `code_name`)

---

#### `canned_response`
Pre-written response templates.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `canned_id` | INT(10) UNSIGNED | PK, AUTO_INCREMENT | |
| `dept_id` | INT(10) UNSIGNED | DEFAULT 0 | Department FK (0=global) |
| `isenabled` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 1 | Enabled status |
| `title` | VARCHAR(255) | NOT NULL | Response title |
| `response` | TEXT | NOT NULL | Response body |
| `lang` | VARCHAR(16) | DEFAULT 'en_US' | Language code |
| `notes` | TEXT | | Admin notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: UNIQUE(`title`), `dept_id`, `isenabled` (as 'active')

---

### Filter & Routing Tables

#### `filter`
Email/ticket routing rules.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `execorder` | INT(10) UNSIGNED | NOT NULL, DEFAULT 99 | Execution order |
| `isactive` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 1 | Active status |
| `flags` | INT(10) UNSIGNED | DEFAULT 0 | Filter flags |
| `status` | INT(11) UNSIGNED | DEFAULT 0 | Filter status |
| `match_all_rules` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 0 | Match mode (all/any) |
| `stop_onmatch` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 0 | Stop on match |
| `target` | ENUM('Any','Web','Email','API') | DEFAULT 'Any' | Filter target |
| `email_id` | INT(10) UNSIGNED | DEFAULT 0 | Specific email FK |
| `name` | VARCHAR(32) | NOT NULL | Filter name |
| `notes` | TEXT | | Admin notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `target`, `email_id`

---

#### `filter_rule`
Individual filter conditions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `filter_id` | INT(11) UNSIGNED | NOT NULL | Filter FK |
| `what` | VARCHAR(32) | NOT NULL | Field to match |
| `how` | ENUM('equal','not_equal','contains','dn_contain','starts','ends','match','not_match') | NOT NULL | Match operator |
| `val` | VARCHAR(255) | NOT NULL | Match value |
| `isactive` | TINYINT(1) UNSIGNED | DEFAULT 1 | Rule active |
| `notes` | TEXT | | Admin notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: UNIQUE(`filter_id`, `what`, `how`, `val`), `filter_id`

---

#### `filter_action`
Filter triggered actions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `filter_id` | INT(11) UNSIGNED | NOT NULL | Filter FK |
| `sort` | INT(10) UNSIGNED | NOT NULL, DEFAULT 0 | Execution order |
| `type` | VARCHAR(24) | NOT NULL | Action type |
| `configuration` | TEXT | | Action config (JSON) |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `filter_id`

---

### Knowledge Base Tables

#### `faq`
FAQ articles.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `faq_id` | INT(10) UNSIGNED | PK, AUTO_INCREMENT | |
| `category_id` | INT(10) UNSIGNED | NOT NULL, DEFAULT 0 | Category FK |
| `ispublished` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 0 | Published status |
| `question` | VARCHAR(255) | NOT NULL | FAQ question |
| `answer` | TEXT | NOT NULL | FAQ answer |
| `keywords` | TINYTEXT | | Search keywords |
| `notes` | TEXT | | Admin notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: UNIQUE(`question`), `category_id`, `ispublished`

---

#### `faq_category`
Hierarchical FAQ categories.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `category_id` | INT(10) UNSIGNED | PK, AUTO_INCREMENT | |
| `category_pid` | INT(10) UNSIGNED | DEFAULT 0 | Parent category FK |
| `ispublic` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 0 | Public visibility |
| `name` | VARCHAR(125) | | Category name |
| `description` | TEXT | NOT NULL | Category description |
| `notes` | TEXT | | Admin notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `ispublic`

---

#### `faq_topic`
FAQ to help topic associations.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `faq_id` | INT(10) UNSIGNED | PK | FAQ FK |
| `topic_id` | INT(10) UNSIGNED | PK | Help topic FK |

---

#### `help_topic`
Help topics for ticket categorization.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `topic_id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `topic_pid` | INT(10) UNSIGNED | DEFAULT 0 | Parent topic FK |
| `ispublic` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 1 | Public visibility |
| `noautoresp` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 0 | Disable auto-response |
| `flags` | INT(10) UNSIGNED | DEFAULT 0 | Topic flags |
| `status_id` | INT(10) UNSIGNED | DEFAULT 0 | Default status FK |
| `priority_id` | TINYINT(4) UNSIGNED | DEFAULT 0 | Default priority FK |
| `dept_id` | INT(10) UNSIGNED | DEFAULT 0 | Default department FK |
| `staff_id` | INT(10) UNSIGNED | DEFAULT 0 | Default assignee FK |
| `team_id` | INT(10) UNSIGNED | DEFAULT 0 | Default team FK |
| `sla_id` | INT(10) UNSIGNED | DEFAULT 0 | Default SLA FK |
| `page_id` | INT(10) UNSIGNED | DEFAULT 0 | Associated page FK |
| `sequence_id` | INT(10) UNSIGNED | DEFAULT 0 | Number sequence FK |
| `sort` | INT(10) UNSIGNED | DEFAULT 0 | Sort order |
| `topic` | VARCHAR(128) | NOT NULL | Topic name |
| `number_format` | VARCHAR(32) | | Ticket number format |
| `notes` | TEXT | | Admin notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: UNIQUE(`topic`, `topic_pid`), `topic_pid`, `priority_id`, `dept_id`, (`staff_id`, `team_id`), `sla_id`, `page_id`

---

#### `help_topic_form`
Topic-to-form assignments.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `topic_id` | INT(11) UNSIGNED | NOT NULL | Topic FK |
| `form_id` | INT(10) UNSIGNED | NOT NULL | Form FK |
| `sort` | INT(10) UNSIGNED | NOT NULL, DEFAULT 1 | Display order |
| `extra` | TEXT | | Additional config (JSON) |

**Indexes**: `topic-form` (`topic_id`, `form_id`)

---

### Dynamic Forms Tables

#### `form`
Dynamic form definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `pid` | INT(11) UNSIGNED | DEFAULT 0 | Parent form FK |
| `type` | VARCHAR(8) | NOT NULL, DEFAULT 'G' | Form type code |
| `flags` | INT(10) UNSIGNED | DEFAULT 1 | Form flags |
| `title` | VARCHAR(255) | NOT NULL | Form title |
| `instructions` | VARCHAR(512) | | Form instructions |
| `name` | VARCHAR(64) | NOT NULL, DEFAULT '' | Internal name |
| `notes` | TEXT | | Admin notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `type`

**Form Type Codes**:
- `G` = Generic
- `U` = User
- `T` = Ticket
- `O` = Organization
- `A` = Task
- `C` = Company

---

#### `form_field`
Form field definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `form_id` | INT(11) UNSIGNED | NOT NULL | Form FK |
| `flags` | INT(10) UNSIGNED | DEFAULT 1 | Field flags |
| `type` | VARCHAR(255) | NOT NULL, DEFAULT 'text' | Field type |
| `label` | VARCHAR(255) | NOT NULL | Display label |
| `name` | VARCHAR(64) | NOT NULL | Field name |
| `configuration` | TEXT | | Field config (JSON) |
| `sort` | INT(10) UNSIGNED | NOT NULL | Sort order |
| `hint` | VARCHAR(512) | | Help text |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `form_id`, `sort`

---

#### `form_entry`
Form submission instances.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `form_id` | INT(11) UNSIGNED | NOT NULL | Form FK |
| `object_id` | INT(11) UNSIGNED | | Parent object ID |
| `object_type` | CHAR(1) | NOT NULL, DEFAULT 'T' | Object type code |
| `sort` | INT(11) UNSIGNED | NOT NULL, DEFAULT 1 | Sort order |
| `extra` | TEXT | | Additional data (JSON) |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `entry_lookup` (`object_type`, `object_id`)

---

#### `form_entry_values`
Form field values.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `entry_id` | INT(11) UNSIGNED | PK | Form entry FK |
| `field_id` | INT(11) UNSIGNED | PK | Form field FK |
| `value` | TEXT | | Field value |
| `value_id` | INT(11) | | Reference value ID |

---

#### `list`
Dropdown/select list definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `name` | VARCHAR(255) | NOT NULL | List name |
| `name_plural` | VARCHAR(255) | | Plural form |
| `sort_mode` | ENUM('Alpha','-Alpha','SortCol') | DEFAULT 'Alpha' | Sort mode |
| `masks` | INT(11) UNSIGNED | NOT NULL, DEFAULT 0 | Property masks |
| `type` | VARCHAR(16) | | List type |
| `configuration` | TEXT | | List config (JSON) |
| `notes` | TEXT | | Admin notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `type`

---

#### `list_items`
List item values.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `list_id` | INT(11) UNSIGNED | NOT NULL | List FK |
| `status` | INT(11) UNSIGNED | NOT NULL, DEFAULT 1 | Item status |
| `value` | VARCHAR(255) | NOT NULL | Display value |
| `extra` | TEXT | | Additional data (JSON) |
| `sort` | INT(11) UNSIGNED | NOT NULL, DEFAULT 1 | Sort order |
| `properties` | TEXT | | Item properties (JSON) |

**Indexes**: `list_item_lookup` (`list_id`)

---

### File & Attachment Tables

#### `file`
File storage registry.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `ft` | CHAR(1) | NOT NULL, DEFAULT 'T' | File type code |
| `bk` | CHAR(1) | NOT NULL, DEFAULT 'D' | Storage backend |
| `type` | VARCHAR(255) | NOT NULL, DEFAULT '', ascii_general_ci | MIME type |
| `size` | BIGINT(20) UNSIGNED | NOT NULL, DEFAULT 0 | File size (bytes) |
| `key` | VARCHAR(86) | ascii_general_ci | File key/hash |
| `signature` | VARCHAR(86) | ascii_bin | Content signature |
| `name` | VARCHAR(255) | NOT NULL, DEFAULT '' | Original filename |
| `attrs` | TEXT | | File attributes (JSON) |
| `created` | DATETIME | NOT NULL | Creation date |

**Indexes**: `ft`, `key`, `signature`, `type`, `created`, `size`

**Storage Backends** (`bk`):
- `D` = Database (file_chunk table)
- `F` = Filesystem
- `S` = S3/Object Storage

---

#### `file_chunk`
File content chunks (database storage).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `file_id` | INT(11) UNSIGNED | PK | File FK |
| `chunk_id` | INT(11) UNSIGNED | PK | Chunk sequence |
| `filedata` | LONGBLOB | NOT NULL | Binary data |

---

#### `attachment`
File-to-object associations.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(10) UNSIGNED | PK, AUTO_INCREMENT | |
| `object_id` | INT(11) UNSIGNED | NOT NULL | Parent object ID |
| `type` | CHAR(1) | NOT NULL | Object type code |
| `file_id` | INT(11) UNSIGNED | NOT NULL | File FK |
| `name` | VARCHAR(255) | | Display name override |
| `inline` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 0 | Inline display |
| `lang` | VARCHAR(16) | | Language code |

**Indexes**: UNIQUE `file-type` (`object_id`, `file_id`, `type`), UNIQUE `file_object` (`file_id`, `object_id`)

---

### SLA Table

#### `sla`
Service Level Agreement definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `flags` | INT(10) UNSIGNED | DEFAULT 3 | SLA flags |
| `grace_period` | INT(10) UNSIGNED | NOT NULL, DEFAULT 0 | Grace period (hours) |
| `name` | VARCHAR(64) | NOT NULL, DEFAULT '' | SLA name |
| `notes` | TEXT | | Admin notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: UNIQUE(`name`)

**SLA Flags**:
- `FLAG_ACTIVE` (1): SLA is active
- `FLAG_ESCALATE` (2): Enable escalation
- `FLAG_NOALERTS` (4): Suppress overdue alerts
- `FLAG_TRANSIENT` (8): Temporary SLA

---

### Schedule Tables

#### `schedule`
Business hours/schedule definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(10) UNSIGNED | PK, AUTO_INCREMENT | |
| `flags` | INT(10) UNSIGNED | DEFAULT 0 | Schedule flags |
| `name` | VARCHAR(255) | NOT NULL | Schedule name |
| `timezone` | VARCHAR(64) | | Schedule timezone |
| `description` | TEXT | | Description |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

---

#### `schedule_entry`
Schedule time blocks.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `schedule_id` | INT(10) UNSIGNED | NOT NULL | Schedule FK |
| `flags` | INT(10) UNSIGNED | DEFAULT 0 | Entry flags |
| `sort` | INT(10) UNSIGNED | DEFAULT 0 | Sort order |
| `name` | VARCHAR(255) | | Entry name |
| `repeats` | VARCHAR(16) | NOT NULL, DEFAULT 'never' | Repeat pattern |
| `starts_on` | DATE | | Start date |
| `starts_at` | TIME | | Start time |
| `ends_on` | DATE | | End date |
| `ends_at` | TIME | | End time |
| `stops_on` | DATE | | Repeat stop date |
| `day` | INT(10) UNSIGNED | DEFAULT 0 | Day of week/month |
| `week` | INT(10) UNSIGNED | DEFAULT 0 | Week number |
| `month` | INT(10) UNSIGNED | DEFAULT 0 | Month |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `schedule_id`, `repeats`

---

### Queue/View Tables

#### `queue`
Custom ticket queues/saved searches.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `parent_id` | INT(11) UNSIGNED | DEFAULT 0 | Parent queue FK |
| `columns_id` | INT(11) UNSIGNED | DEFAULT 0 | Column set FK |
| `sort_id` | INT(11) UNSIGNED | DEFAULT 0 | Default sort FK |
| `flags` | INT(11) UNSIGNED | DEFAULT 0 | Queue flags |
| `staff_id` | INT(11) UNSIGNED | DEFAULT 0 | Owner staff FK (0=public) |
| `sort` | INT(11) UNSIGNED | DEFAULT 0 | Sort order |
| `title` | VARCHAR(60) | | Queue title |
| `config` | TEXT | | Queue config (JSON) |
| `filter` | VARCHAR(64) | | Quick filter |
| `root` | VARCHAR(32) | | Root object type |
| `path` | VARCHAR(80) | NOT NULL, DEFAULT '/' | Hierarchical path |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `staff_id`, `parent_id`

---

#### `queue_column`
Available queue column definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `flags` | INT(11) UNSIGNED | DEFAULT 0 | Column flags |
| `name` | VARCHAR(64) | NOT NULL | Column name |
| `primary` | VARCHAR(255) | NOT NULL, DEFAULT '' | Primary field path |
| `secondary` | VARCHAR(255) | | Secondary field path |
| `filter` | VARCHAR(32) | | Quick filter type |
| `truncate` | VARCHAR(16) | | Truncation mode |
| `annotations` | TEXT | | Column annotations (JSON) |
| `conditions` | TEXT | | Display conditions (JSON) |
| `extra` | TEXT | | Additional config (JSON) |

---

#### `queue_columns`
Staff queue column selections.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `queue_id` | INT(11) UNSIGNED | PK | Queue FK |
| `column_id` | INT(11) UNSIGNED | PK | Column FK |
| `staff_id` | INT(11) UNSIGNED | PK | Staff FK (0=default) |
| `bits` | INT(11) UNSIGNED | DEFAULT 0 | Column bits |
| `sort` | INT(11) UNSIGNED | DEFAULT 0 | Display order |
| `heading` | VARCHAR(64) | | Custom heading |
| `width` | INT(11) UNSIGNED | DEFAULT 100 | Column width (px) |

---

#### `queue_sort`
Sort column definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `root` | VARCHAR(32) | DEFAULT '' | Root object type |
| `name` | VARCHAR(64) | NOT NULL, DEFAULT '' | Sort name |
| `columns` | TEXT | | Sort columns (JSON) |
| `updated` | DATETIME | NOT NULL | Last update |

---

#### `queue_sorts`
Sort assignments to queues.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `queue_id` | INT(11) UNSIGNED | PK | Queue FK |
| `sort_id` | INT(11) UNSIGNED | PK | Sort definition FK |
| `bits` | INT(11) UNSIGNED | DEFAULT 0 | Sort bits |
| `sort` | INT(11) UNSIGNED | DEFAULT 0 | Order |

---

#### `queue_export`
Queue export column definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `queue_id` | INT(11) UNSIGNED | NOT NULL | Queue FK |
| `path` | VARCHAR(255) | NOT NULL | Export field path |
| `heading` | VARCHAR(64) | | Column heading |
| `sort` | INT(11) UNSIGNED | DEFAULT 0 | Export order |

**Indexes**: `queue_id`

---

#### `queue_config`
Staff queue configurations.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `queue_id` | INT(11) UNSIGNED | PK | Queue FK |
| `staff_id` | INT(11) UNSIGNED | PK | Staff FK |
| `setting` | TEXT | | Settings (JSON) |
| `updated` | DATETIME | NOT NULL | Last update |

---

### API Table

#### `api_key`
API authentication keys.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(10) UNSIGNED | PK, AUTO_INCREMENT | |
| `isactive` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 1 | Active status |
| `ipaddr` | VARCHAR(64) | NOT NULL | Allowed IP address |
| `apikey` | VARCHAR(255) | NOT NULL | API key |
| `can_create_tickets` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 1 | Create tickets permission |
| `can_exec_cron` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 1 | Execute cron permission |
| `notes` | TEXT | | Admin notes |
| `updated` | DATETIME | NOT NULL | Last update |
| `created` | DATETIME | NOT NULL | Creation date |

**Indexes**: `ipaddr`, UNIQUE(`apikey`)

---

### Plugin Tables

#### `plugin`
Installed plugins registry.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `name` | VARCHAR(255) | NOT NULL | Plugin name |
| `install_path` | VARCHAR(60) | NOT NULL | Installation path |
| `isphar` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 0 | PHAR package |
| `isactive` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 0 | Active status |
| `version` | VARCHAR(64) | | Plugin version |
| `notes` | TEXT | | Admin notes |
| `installed` | DATETIME | NOT NULL | Installation date |

**Indexes**: UNIQUE(`install_path`)

---

#### `plugin_instance`
Plugin instances/configurations.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `plugin_id` | INT(11) UNSIGNED | NOT NULL | Plugin FK |
| `flags` | INT(11) UNSIGNED | NOT NULL, DEFAULT 0 | Instance flags |
| `name` | VARCHAR(128) | NOT NULL, DEFAULT '' | Instance name |
| `notes` | TEXT | | Admin notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: `plugin_id`

---

### Internationalization Table

#### `translation`
i18n translation strings.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(11) UNSIGNED | PK, AUTO_INCREMENT | |
| `object_hash` | VARCHAR(32) | | Source object hash |
| `type` | ENUM('phrase','article','override') | | Translation type |
| `flags` | INT(11) UNSIGNED | NOT NULL, DEFAULT 0 | Translation flags |
| `revision` | INT(11) UNSIGNED | | Revision number |
| `agent_id` | INT(11) UNSIGNED | NOT NULL, DEFAULT 0 | Translator ID |
| `lang` | VARCHAR(16) | NOT NULL, DEFAULT 'en_US' | Target language |
| `text` | MEDIUMTEXT | NOT NULL | Translated text |
| `source_text` | MEDIUMTEXT | | Original text |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: (`type`, `lang`), `object_hash`

---

### Content Table

#### `content`
Static pages/information content.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id` | INT(10) UNSIGNED | PK, AUTO_INCREMENT | |
| `isactive` | TINYINT(1) UNSIGNED | NOT NULL, DEFAULT 0 | Active status |
| `type` | VARCHAR(32) | NOT NULL, DEFAULT 'other' | Content type |
| `name` | VARCHAR(255) | NOT NULL | Content name/identifier |
| `body` | TEXT | NOT NULL | Content body |
| `notes` | TEXT | | Admin notes |
| `created` | DATETIME | NOT NULL | Creation date |
| `updated` | DATETIME | NOT NULL | Last update |

**Indexes**: UNIQUE(`name`)

---

## Object Type Codes (Polymorphism)

The database uses single-character codes for polymorphic relationships:

| Code | Object Type |
|------|-------------|
| `T` | Ticket |
| `A` | Task |
| `C` | Child Ticket (merged) |
| `U` | User |
| `O` | Organization |
| `S` | Staff |
| `D` | Department |
| `E` | Team |
| `K` | FAQ |
| `F` | AttachmentFile |
| `H` | ThreadEntry (generic) |

---

## Entity Relationship Summary

### Primary Relationships

```
User ─────────────────┬── UserEmail (1:N)
  │                   └── UserAccount (1:1)
  └── Organization (N:1)

Ticket ───────────────┬── User (N:1)
  │                   ├── Staff (N:1, assigned)
  │                   ├── Team (N:1)
  │                   ├── Department (N:1)
  │                   ├── Topic (N:1)
  │                   ├── Status (N:1)
  │                   ├── Priority (N:1)
  │                   ├── SLA (N:1)
  │                   ├── Thread (1:1)
  │                   └── Lock (1:1)

Thread ───────────────┬── ThreadEntry (1:N)
  │                   ├── ThreadEvent (1:N)
  │                   ├── ThreadCollaborator (1:N)
  │                   └── ThreadReferral (1:N)

Staff ────────────────┬── Department (N:1, primary)
  │                   ├── Role (N:1, primary)
  │                   ├── StaffDeptAccess (1:N, extended)
  │                   └── TeamMember (1:N)

Department ───────────┬── Department (N:1, parent)
  │                   ├── Email (N:1)
  │                   ├── SLA (N:1)
  │                   ├── Schedule (N:1)
  │                   └── Staff (N:1, manager)

Form ─────────────────┬── FormField (1:N)
  └── FormEntry (1:N) ─── FormEntryValues (1:N)
```

---

## Migration System

### Overview
- **Location**: `include/upgrader/streams/core/`
- **File Types**: `.patch.sql` (additions), `.cleanup.sql` (cleanup)
- **Tracking**: `config` table with `schema_signature` key

### Migration File Format
```sql
/**
 * @signature <sha1_hash>
 * @version v1.x.x
 * @title Migration description
 */

-- Schema changes here
ALTER TABLE ...

-- Update signature
UPDATE %TABLE_PREFIX%config
SET value = '<new_signature>'
WHERE key = 'schema_signature' AND namespace = 'core';
```

---

## Index Strategy

### Performance Indexes
- All foreign keys have corresponding indexes
- Composite indexes on frequently joined columns
- Covering indexes for common query patterns

### Unique Constraints
- Natural keys (email addresses, usernames, names)
- Composite uniqueness (topic+parent, filter rules)

---

## Notes

### Character Sets
- Default: `utf8`
- Binary fields: `ascii_bin` (passwords, signatures)
- Case-insensitive: `ascii_general_ci` (file types, session IDs)
- Unicode collation: `utf8_unicode_ci` (user agents)

### Timestamps
- All major tables have `created` and `updated` columns
- Some tables use `TIMESTAMP` with `ON UPDATE CURRENT_TIMESTAMP`

### Soft Deletes
- Uses `flags` field for deletion markers
- Hard deletes available for certain statuses (deleted state)
