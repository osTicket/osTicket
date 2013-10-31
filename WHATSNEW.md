osTicket v1.7.3
===============
### Enhancements
  * Ticket thread items are now available for email templates (#790)
  * Support MySQL servers on a non-standard port number, which is also not set
    in the `php.ini` file (#775)

### Bugfixes
  * Fix email handling where the character set advertised is `us-ascii` but
    `iso-8859-1` was intended (#770)
  * Ticket source is now editable (#772, #777)
  * Email parsing would crash if `Reply-To` header was not found (#780)
  * CSRF token creation would fail on some Windows installations (#771, #776)
  * Tickets without an SLA set would never go overdue (#757, #767)
  * FAQ search now hits category names (#781)
  * FAQ search hits are sorted by article title now (#786)
  * Email replies with nothing before the quoted response marker should remain
    as is (#787)
  * CAPTCHA responses are now considered case-insensitive (#823)
  * `References` email header how includes the parent email `Message-Id` (#825)
  * Email attachment parsing would crash if the `Content-Disposition` header
    had no parameters (#828)
  * Date format on the jQuery-UI datepicker is admin configurable now (#829)

### Performance
  * Scanning deleting orphaned files is much faster (#773, #778)

osTicket v1.7.2
===============
### Enhancements
  * The ticket number is no longer required in the subject line and staff can
    reply to emails and create an internal note (*released in v1.7.1.2*)
  * Show customized site logo on PDF output (#763)
  * Support deployment for initial install with cli deploy script (#750)
  * Require complete regression test pass before packaging new release (#751)
  * Die with HTTP/500 for misconfiguration or database connect failure (#762)

### Bug fixes
  * Detect and import inline attachments without a Content-Disposition header
    (#737)
  * Show correct template description *again* (#742, #743)
  * Import attachments from emails continuing a ticket thread (*regression
    introduced in v1.7.1.2*) (#745)
  * Support UTF-8 encoded filenames for fetched emails (#738)
  * Disable Kerberos and NTLM authentication in mail fetching (#739)
  * Forbid empty reply-separator setting (#752)
  * Only email administrators for log messages that would be written to the
    database (#754)
  * Emails fetched and rejected by a ticket filter that are not deleted or
    moved to a folder will not be re-fetched and re-rejected (#755)
  * Workaround for some mail clients' inability to properly decode
    quoted-printable encoded emails (#760)
  * Inline text bodies are incorrectly detected as attachments without a
    filename (#761)
  * Properly decode and display some international chars in PDF printing (#765)
  * Do not double encode XML entities in ticket thread titles (#718, #567)
  * Display correct template description on edit (#724, #727)
  * Fix download of attachments with commas (',') in the filename (#702)
  * Fix incorrect content-type header for CAPTCHA (#699)

### Security
  * Require email address match if ticket number is matched in subject line and
    neither references or in-reply-to headers match an existing ticket thread
    item (*regression introduced in v1.7.1.2*) (#748)

### Performance
  * Address database performance issue scanning for orphaned file_chunk records
    (#764)

osTicket v1.7.1
===============
### Bugfixes
  * Properly reject attachments submitted via the API (#668)
  * Correctly support the "Use Reply-To" in ticket filters (#669)
  * Don't log users out after changing username or email address (#684)
  * Don't leak private FAQ article titles (#683)

osTicket v1.7.1-rc1
===================
### Enhancements
  * Custom logos and site pages (#604, #632, #616)
  * Password reset link (#638)
  * Export and import feature. Useful for migrations and backups. (#626)
  * Use your email address as your username for logins (#631)
  * SLA's can be marked *transient*. Tickets with a transient SLA will
    change to the SLA of the new department or help-topic when transferred
    or edited.
  * Support installation on MySQL and MariaDB clusters. Use default storage
    engine and don't assume predictable auto-increment values (#568, #621)

### Geeky Stuff
  * mysqli support for PHP5+
  * SSL support for database connections
  * Namespaced configuration. This greatly simplifies the process of adding
  * new configurable item (#564)
  * Add signals API. A simple event hooking mechanism to allow for
  * extensibility (#577)
  * Add deployment command-line script (#586)
  * Allow XHTML editing in the nicEditor (#615)
  * Allow parallel database migration streams (#563) -- paves the way for
    *extensions*
  * Use row-based email templates (#604) -- simplifies the process of adding
    new email message templates (think *extensions*)
  * Support fetching from email boxes with aliased email addresses (#663)
  * Introduce new crypto library that provides failsafe encryption for email
    passwords (#651)

### Bugfixes
  * Several typos in code and messages (#617, #618, #644, #660)
  * Fix several upgrader bugs (#548, #619)
  * Fix install fail on some Windows platforms (#570)
  * Fix several issues in the command-line management (#580)
  * Make room for command-line installation of osTicket (#581)
  * *regression* Fix corrupted attachment downloads (#579, #583)
  * Fix truncated attachment downloads when `zlib.output_compression` is
    enabled (#596)
  * Disable cron activities when upgrade is pending (#594)
  * Provide failsafe encoding for improperly-formatted emails (#601)
  * Fix corrupted email attachments processed via `pipe.php` (#607)
  * Fix discarding of poorly encoded base64 emails (#624)
  * Support MariaDB 10.0+ (#630)
  * Properly trim ticket email and name fields (#600)
  * Fix truncated text from text/plain emails and web interface posts (#652)
  * Add **Assigned To** and other fields to ticket view export (#646)
  * *regression* Fix attachment migration (#648)
  * Display correct staff notes (#588)
  * Display correct auto-response email for departments (#575)
  * Fix login form ("Authentication Required") loop (#653)
  * Ensure email message-id is fetched correctly (#664)
  * Ensure X-Forwarded-For header does not have leading or trailing
    whitespace (#665)

### Performance
  * Only fetch configuration for multifile upload if necessary (#637)
  * Don't use sessions on the API (#623)
  * *regression* Avoid an extra query per request to fetch schema signature
    (#658)

New stuff in 1.7.0
====================
   * Bug fixes from rc6

New stuff in 1.7-rc6
====================
  * Bug fixes and enhancements from rc5

New stuff in 1.7-rc5
====================
  * Bug fixes from rc4

New stuff in 1.7-rc4
====================
  * Bug fixes from rc3

New stuff in 1.7-rc3
====================
  * Bug fixes from rc2
  * Canned auto-reply template
  * Modal dialogs
  * PEAR packages upgrade
  * Email encoding

New stuff in 1.7-rc2
====================
  * Bug fixes from rc1
  * Nested help topics support

New stuff in 1.7-rc1
====================
  * Upgrade support for osTicket 1.6-rc1 and later
  * Multi-file upload support -- more than one file (configurable) can be
    uploaded with new messages, replies, and internal notes via the web
    interface
  * Department/Group access feature allowing members of a group access to a
    department. Staff members are members of a (primary) group, and that
    group can be granted access to one or more departments, granting the
    associated staff access to departments other than their primary
    department.
  * Email filters can specify a canned auto-response
  * Support inline attachments for fetched email

New stuff in 1.7-dpr4
======================
  * Dashboard reports for ticket system activity and statistics
  * PDF print / export for tickets (staff pages only)

New stuff in 1.7-dpr3
======================
  * Advanced search on tickets page
  * Ticket thread -- revised ticket message storage model for greater
    flexability
  * New database upgrade system allowing for continuous updates to the
    database model. This will greatly simplify the process of making
    modifications to the osTicket database.

New stuff in 1.7-dpr2
======================
  * Autocomplete for ticket search box (emails and ticket numbers typeahead)
  * Redesigned staff login page
  * Warning when leaving unsaved changes in admin and staff settings pages
  * Auto change admin settings pages when selecting a new page from the
    drop-down list
  * Create a ticket in one click from the staff panel
  * Preview ticket from the search results
  * Export tickets to CSV file

New Features in 1.7
===================
Version 1.7 includes several new features

Ticket Filters
-------------
As an upgrade from email banning (which is still supported), ticket filters
allow for matching incoming email in the subject line and message body. For
matching emails, the administrator has the ability to automatically route
tickets:

  * To a specific department, staff member, and/or team
  * Automatically assign ticket priority and/or service-level-agreement
  * Disable ticket auto-responses
  * Send automatic canned responses

Tickets filters are also applied to tickets submitted via all ticket
interfaces, including the API, email, staff and client web interfaces. And,
as a bonus, the filters can be configured to target only a single interface.
So an administrator could, for instance, target tickets received via email
from a particular domain.

Canned Attachments
------------------
Attach files to your canned responses. These attachments are automatically
attached to the ticket thread along with the canned response. The
attachments are not duplicated in the database and therefore use virtually
no space.

Database-backed Attachments
---------------------------
No more crazy security-related configuration to your host server in order to
support attachments. Attachments are now quietly stored in the database. The
upgrade migration will automatically port attachments from the previous
locations into the database.

Service Level Agreements
------------------------
Service level agreements allow for a configurable grace period based on the
department or help topic associated with the ticket. A default SLA is
provided, and a due date can be set to override the grace period of the SLA
for a ticket.

Client-side Knowledgebase
-------------------------
Manage a searchable help document portal for your users

Dashboard Reports
-----------------
Flashy reports of ticket system activity as well as exportable ticket system
statistics, allowing for easy report generation from office spreadsheet
applications.

Ticket Export
-------------
Convert the ticket thread to a printed format for long term storage. The
ticket view page now supports a print feature, which will render the ticket
as a PDF document.

API
---
Interface with osTicket via HTTP requests. Starting with version 1.7,
tickets are createable by submitting an HTTP POST request to either

    /api/tickets.xml
    /api/tickets.json

The API can also be used to pipe emails into the osTicket system. Use the
included `automail.php` or `automail.pl` script to pipe emails to the
system, or post raw email messages directly to

    /api/tickets.email

Use of the API requires an API key, which can be created and configured in
the admin panel of the support system.

For technical details, please refer to [API Docs] (setup/doc/api.md).

Geeky New Features
==================

Unicode
-------
Better and more consistent international text handling

Flexible Template Variables
---------------------------
Template variables have been redesigned to be more flexible. They have been
integrated into the respective object classes so that an object as well as
its properties can be represented in template variables. For instance
%{ticket.staff.name}
