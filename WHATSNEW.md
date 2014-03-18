osTicket v1.8.1.2
=================
* All fixes and enhancements from v1.8.0.4, plus *

### Enhancements
  * Better detection of email loops (#584, #684)

### Bugs
  * Fix selection of the auto-response email for a department (#666)
  * Don't require current password when resetting (#671)
  * Fix incorrect matchup of collaborators to users (#676)

osTicket v1.8.1.1
=================
* All fixes and enahncements from v1.8.0.4, plus *

### Enhancements
  * Add signature to activity notice for staff replies (#605)
  * Show company name in the copyright footer (#586)
  * Signature is displayed below the staff response box (#609)

### Bugs
  * Fix footnotes generated in html2text for same link text but different URLs (5e2f58d)
  * Fix processing of emails for existing users (#588)
  * Avoid adding an aliased system email address as a collaborator (#604, #627)
  * Show current staff / user names where possible (#608)
  * Fix display of _forgot my password_ link (#611)
  * Export the value of custom fields (not the ID number) (#610)
  * Fix saving the backend with file metadata (#595)
  * Use the database as a failsafe attachment backend (#594)
  * Avoid a crash when sending some mails (#589)
  * Fix migrating attachments when upgrading from osTicket 1.6 (#614)
  * Email templates ship with the ticket number in the subject line (#644)
  * If inline images are stripped from the email, they are not considered attachments (#649, bcbebd0, 35a23be)
  * Fix incorrect Content-Id headers generated for inline images (23ce0a0, e37ec74)
  * New installs have the `Staff` group enabled (c7130c5)
  * Always show the ticket thread when following an email link (17725ca)

### Security and Performance
  * Staff can only see closed tickets if they have access via group or primary department (#623, #655)
  * Fix incorrect honoring of ban list and over limit settings (#660)
  * Keep existing session after login (c4bfb69)
  * Fix password reset system (dfaca0d, #664)

osTicket v1.8.0.4
=================
### Enhancements
  * Departments can have a department if there are no members (#618)
  * Any valid email address can be used throughout the system (#673, e3adfaa)

### Bugs
  * Preserve inline image floating in ticket thread (#612)
  * Recover from crash when creating new user accounts (#617)
  * Fix SQL error for some variable names on custom fields (#620)
  * Fix canned append in non-HTML mode (#621)
  * Fix stripping of leading zeros from phone numbers (#622)
  * Strip `<?xml ... >` processing instructions from html email (#628)
  * Disable error_reporting for releases (#630)
  * Fix crash during PDF generation for some PHP installations (#631)
  * Clicking away from popup tips closes them (#645)
  * FAQ last-modified time can be something other than midnight (#647)
  * Fix creeping widget sizes in create-user dialog (#648)
  * Usernames can have Unicode characters (#650)
  * If Auto-claim tickets is disabled, reopen tickets unassigned (#651)
  * New SLA's default to have alerts enabled (#654)
  * Fix creating ticket by staff without required contact-information fields (#656)
  * Fix crash viewing ticket in the client portal if no departments are public (#658)
  * Fix crash for some custom field configurations (#659)
  * Allow manual update of SLA to a transient SLA (#663, e37ec74)
  * Fix upgrade crash from some osTicket 1.6 installations (#687)
  * Fix over-stripping of some HTML element sections (#686)
  * Better detection of email loops (#584, #684)
  * Fix attachments from new ticket by staff to be associated with the response (#688)

### Geeky Stuff
  * Persistent database connections are supported (#693)
  * Regression testing now tests for JS syntax errors (#669)
  * Add git version to the deployment script (#630)

### Performance and Security
  * Staff can only see closed tickets if they have access via group or primary department (#623, #655)

osTicket v1.8.1
===============
*All fixes and enhancements from v1.8.0.3, plus*

### Enhancements
  * Ticket filters support matching on email To and Cc fields (#529)
  * Popup summary and collaborator list on ticket queue page (#521)

### Bugs
  * New ticket by staff adds `recipient` and `staff` context to email templates (#527)
  * Forbid password reset for non-local users (#570)
  * Allow an administrator to lift the force password change flag (#570)
  * Locks are released on logout (#522)
  * Text email footnotes are written as [title][#] (7ccbf0c)
  * Fix several issues with display and download of attachments (#530)
  * Fix sending a reply email if requested not to (#531)
  * Only consider collaborators if the receiving system email is identified (#537)
  * Do not consider `delivered-to` addresses as collaborators (#544)
  * Assume `iso-8859-1` MIME body encoding if not specified (#551)
  * Add new features to the storage API to implement Amazon S3 (#515)

### Performance and Security
  * Support auditing login attempts (#559)
  * Avoid auth strikeouts when not attempting a login (#559, #523)

osTicket v1.8.0.3
=================
### Enhancements
  * Much better email bounce detection (#461, #474)
  * Microsoft® TNEF emails are supported (#555, 0890481, #567)
  * Handle messages forwarded as `message/rfc822` content type (#482)
  * [Esc] key cancels popup dialogs (#465)
  * New ticket by staff workflow is simplified (#543)
  * Support regex matches in ticket filter (584465c)

### Bugs
  * 'Priority' column is included in the ticket queue export (#451)
  * Retry queries on MySQL error 1213 (#493)
  * Client login email is not case-sensitive (398cbc7)
  * Drop silly border on text emails if HTML ticket thread is disabled (439a32a)
  * Fix ticket submission error if client is already logged in (#469)
  * Fix fetching from more than 10 mail accounts (#468)
  * Fix clickable links ending with punctuation (#497)
  * Fix whitespace mangling of Unicode text with non-breaking-spaces (#502)
  * Fix image size set to zero when images are added to drafts (#504)
  * Correctly detect php-dom extension (#503)
  * Fix delivery issue of emails delivered to group mailboxes (#510)
  * Fix E_STRICT annoyance from class.config.php (#518)
  * Fix dashboard report timeframe for non-US date formats (#520)
  * Fix dashboard report ending "period" (#520)
  * Fixup Message-Id and Delivered-To for encapsulated messages (#528)
  * Much better compatibility implementation of the `mbstring` module (#517)
  * Consider the `delivered-to` header in finding the system email (#535)
  * Ticket variables are available in templates regardless of case (#545)
  * Allow advanced search on any priority *regression* (#547)
  * Fix email address list parsing on bad MIME headers (#560)
  * Automatically detect file MIME type if not specified (ac42e62)
  * Fix login issue when upgrading from osTicket 1.6 (#571)
  * Fix attachment corruption on some documents like PDFs (#576)

### Performance and Security
  * Reuse SMTP connections where possible (#462)
  * Enforce max file size for attachments sent via API (#568)

osTicket v1.8.1-rc1
===================
### Enhancements
  * Much better email bounce detection (#461, #474)
  * Handle messages forwarded as `message/rfc822` content type (#482)
  * [Esc] key cancels popup dialogs (#465)
  * Support regex matches in ticket filter (584465c)

### Bugs
  * 'Priority' column is included in the ticket queue export (#451)
  * Retry queries on MySQL error 1213 (#493)
  * Client login email is not case-sensitive (398cbc7)
  * Drop silly border on text emails if HTML ticket thread is disabled (439a32a)
  * Fix ticket submission error if client is already logged in (#469)
  * Fix fetching from more than 10 mail accounts (#468)
  * Fix `deploy` command-line application (#450)
  * Fix error email on upgrade (#452)
  * Ship with a `plugins/` folder (90b0a65)
  * Fix file key not replaced in thread body correctly for de-duplicated files (#492)
  * Better handling of text and html thread posts (#508)
  * Fix clickable links ending with punctuation (#497)
  * Fix whitespace mangling of Unicode text with non-breaking-spaces (#502)
  * Fix image size set to zero when images are added to drafts (#504)
  * Correctly detect php-dom extension (#503)
  * Fix delivery issue of emails delivered to group mailboxes (#510)

### Merged from v1.8.0.2
  * Log entry for password reset attempts (#435)

osTicket v1.8.1 (Preview)
=========================
### Collaborator Support (CC)
In addition to the ticket owner, other end users can be collaborators on a
ticket. Responses received from them are integrated automatically into the
ticket thread, and emails are sent to all collaborators when new messages and
responses arrive into the system. All collaborators have access to the ticket
via the client portal and are able to log new messages.

### Plugin management system
osTicket supports plugins via a (currently undocumented) simple plugin API and
interface. Plugins can be written and distributed as files or unpacked via ZIP
archives, or distributed via PHP PHAR files. The plugin system is developed in
hopes of adding extensibility to osTicket without significant overhead.
Initially, two "classes" of plugins are supported: authentication, and file
storage.

### Pluggable authentication
Staff members can now be authenticated against a backend other than the
osTicket internal database. Available immediately is integration with LDAP
(RFC-2307) and Microsoft® Active Directory. The initial authentication system
also support user lookups, so when browsing for new users when creating
tickets, your directory server will be queried for users and email addresses.

### Pluggable attachment storage
Attachments can live outside the database again. You can now write or install a
plugin to store your attachments somewhere other than in your database, and
osTicket will use the backend to store and retrieve (or redirect to) your
attachments. We've initially made a plugin available to store attachments on
the filesystem and plan on adding an Amazon S3 plugin very soon.

### Internationalization, Phase 1
Select your default data on installation, and select the language preference,
as a staff member, for the help tips. You can also now select the language of
the email templates when creating a new template. The templates for that
language will be used instead of the English ones where translated versions are
available.

### Minor Enhancements
  * Clients can update their profile information on the web portal
  * Clients can update ticket details (if enabled)
  * Custom ticket-details fields are included in ticket queue exports

osTicket v1.8.0.2
=================
### Enhancements
  * HTML editor has an underline button (#377)
  * New ticket form pre-selects default priority (#400)
  * Help topics do not require an associated priority (#397)
  * Extra fields associated with help topics are shown above the ticket-details form (#398)
  * Auto-complete is supported on email address fields (#401)
  * Choice fields allow specification of a prompt and default value (#427)
  * Email template page makes templates easier to manage (#417)
  * New ticket user-lookup popup supports cancel (#434)

### Bugs
  * Ticket locks are correctly released (#335)
  * Pages show inline images correctly (8a1f4e6)
  * Internet Explorer compatibility view is disabled for the scp (#368)
  * *regression* Staff no longer receive attachments on alerts (#379)
  * Emails correctly differentiate HTML and text versions (#212, #384)
  * Ticket queue counts are correct for limited users (#298, #389)
  * Phone number field might be a text box (#390)
  * Fix incorrect ticket rejection for new ticket by staff (#425)
  * Fix crash of cron executions on some platforms (#421)
  * `realpath` may fail on some Windows® platforms (#424, cff8db8)
  * Fix incorrect handling of typeahead list fields with leading numeric chars (#422)

### Performance and Security
  * Ticket queue has significantly better performance (#357, #388, #413, a03dec5, 31bb4ac, e9a3b98)
  * Remove several unnecessary queries (#415)
  * Password reset attempts are logged (#435)
  * Handle garbage username input for password reset (344c95f)

### Upstream 1.7 Commits
  * Database hostname supports local socket specified as localhost:/path/to/socket (osTicket/osTicket-1.7#864)
  * Upgrader correct upgrades the ban list from 1.6. Migrator corrects incorrect upgrade (osTicket/osTicket-1.7#869)
  * Ticket number is detected in subject line without brackets (osTicket/osTicket-1.7#873, 358cdeb)
  * Fixup redirect headers for modern IIS servers (osTicket/osTicket-1.7#874)
  * Correctly support PHP 5.5 (5e8e233)
  * Fix missing parameter to TicketLock::lookup (osTicket/osTicket-1.7#878)

osTicket v1.8.0.1
=================
### Enhancements
  * Allow edit of user on the ticket open page (#291)
  * Display complete contact information to lookup dialog (07ec37d)
  * Clarify `mysqli` extension requirement on install and upgrade pages (#309,
    334461e)
  * Add option to display unprocessed name (original) (#323)

### Bugfixes
  * Fix parser error for PHP < 5.3 on upgrade and install (1ff1540)
  * Remove dependency on mbstring (for real this time) (50d3d70)
  * Fix incorrect advanced search hits on some custom fields (#290)
  * Custom forms require a title (otherwise you cannot click on them to edit)
    (#293)
  * Update client phone number on ticket view page after update (#292)
  * Fix regression where validation errors were not shown on new ticket form
    (#303)
  * Fix bug where client name and email were not filterable for web submissions
    (#319)
  * Fix various autocorrect annoyances (#321)

### Performance and Security
  * Improve performance of ticket filtering on some configurations (#301)
  * Fix possible cross site scripting (XSS) vulnerability on display of contact
    information values (#297)

osTicket v1.8.0
=============
### Enhancements
  * Rich text ticket thread (#5)
  * Custom forms and fields (#2)
  * Translatable initial data *almost*

### Rich Text Ticket Thread
As an option, enabled by default, osTicket can now process HTML email and
allows for rich text markup in most long-answer boxes across the system,
including staff replies and internal notes posted to the ticket thread. To keep
the feature consistent throughout, canned responses and email templates also
sport an HTML theme now.

### Custom Forms and Fields
The data collected from your users when they fill out the ticket form is now
customizable. You can now ask any information relevant to your business
practice, and can customize the type of input show from the user. Currently,
short and long answer fields, drop-down lists, checkboxes, date and time, and
phone number fields are available. Each field is configurable and can be setup
according to your liking. Fields can also be marked as required, whose input is
required to submit the ticket, and internal, whose input is not visible to the
end user.

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
