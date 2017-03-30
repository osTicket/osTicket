osTicket v1.10
==============
### Enhancements
 * Support Passive Email Threading (#3276)
 * Account for agents name format setting when sorting agents (#3274, 5c548c7)
 * Ticket Filters: Support Lookup By Name (#3274, ef9b743)
 * Enable preloaded canned responses by default (#3274, 7267531)

### Improvements
 * Task: Missing Description on create (#3274, 865db9)
 * Save task due date on create (#3438)
 * Show overlay on forms submit (#3426, #3391)
 * upgrader: Fix crash on SequenceLoader (#3421)
 * upgrader: Fix undefined js function when upgrading due to stale JS file (#3424)
 * Use help topic as the subject line when issue summary is disabled (#3274, 74bdc02)
 * PEAR: Turn off peer name verification by default (SMTP) (#3274, 4f68aeb)
 * Cast orm objects to string when doing db_real_escape (#3274, e63ba58)
 * Save department on __create (#3274, c664c93)
 * Limit records to be indexed per cron run to 500 (#3274, 9174bab)

### Performance and Security
 * Fix memory leak when applying 'Use Reply-To Email' ticket filter action (#3437, 84f085d)
 * XSS: Sanitize and validate HTTP_X_FORWARDED_FOR header (#3439, b794c599)
 * XSS: Encode html chars on help desk title/name (#3439, a57de770)

osTicket v1.10-rc.3
===================
### Enhancements
  * Compatibility with PHP7 (#2828)
  * Share tickets among organization members (#2405)
  * Add lock semantics compatible with v1.9 (lock on view) (f826189)
  * Staff login backdrop is customizable (#2468)
  * Add advanced search for closed date, thread last message, thread last
    response (#2444)
  * Disable auto-claim by department (#2591)
  * Properly flag SYSTEM thread postings (#2702)
  * Add option to use dept/agent name on replies (#2700)
  * Add a preference option to set the sort order of the thread entries in DESC
    or ASC order (#2700)
  * Thread dates can be shown as relative or absolute timestamps (#2700)
  * Make Avatars optional on thread view (#2701)
  * Make Authentication Tokens Optional (auto-login links in emails) (#2714)
  * Use icons for ticket and task actions (#2760)
  * role: Add option to use primary role on assignment (#2832)

### Improvements
  * All improvements cited in v1.9.12 and v1.9.13
  * Fix deleting of custom logos (#2433)
  * Fix assignment setting on new tasks (#2452)
  * Fix subject display of non-short-answer fields on ticket view and ticket
    queue (#2463)
  * Fix advanced search of ticket source (#2479)
  * Forbid adding deleted forms via "Manage Forms" (#2483)
  * Use horizontal tabs for translatable article content rather than the left
    tabs in a table (#2484)
  * Fix lock expiration time if PHP and database have different time zones
    (#2533)
  * Fix user class and ID matching from email headers (#2549)
  * Fix emission of `Content-Language` header in client portal for multiple
    system languages, thanks @t-oster (#2555)
  * Fix deployment of fresh git repo or download on PHP 5.6 (#2571)
  * Fix handling of abbreviated database timezones like `CDT` (#2570)
  * Fix incorrect height display of avatars (#2580, #2609)
  * Sort help topic names case insensitively, thanks @jdelhome3578 (#2530)
  * Fix detection of looped emails (f2cac64)
  * Fix crash in ticket preview (popout) if ticket has no thread (bd9e9c5)
  * Fix javascript crash adding new ticket filter (d2af0eb)
  * Fix crash if the `name` field of a user is a drop-down (ec0b2c5)
  * Fix incorrect SQL query removing departments (cf6cd81)
  * Properly fallback to database file storage if system is misconfigured (1580136)
  * Fix crash handling fields with `__` in the name in the VisibilityConstraint
    class (b3d09b6)
  * Remove staff-dept records when removing an agent (ecf6931)
  * Avoid crashing processing ORM records with NULL select_related models (#2589)
  * Fix several full-text search related issues (#2588, #2603)
  * Fix crash sending registration link for a guest user (#2552)
  * Avoid showing lock icon for expired locks on ticket listing (#2617)
  * Fix incorrect redirect from SSO authentication, thanks @kevinoconnor7
    (#2641)
  * Fix vertical overflow of uploaded image preview (#2616)
  * Fix unnecessary dropping of CDATA table on MySQL 5.6 (#2638)
  * Fix several issues on user directory ticket listing (#2626)
  * Fix encoding of attachment filenames in emails (#2586)
  * Fix warning rendering advanced search dialog, thanks @t-oster (#2594)
  * Fix bounce message loop for message alert to a bad agent email address
    (#2639)
  * Make fulltext search optional on user lookup (#2657)
  * Add the [claim] feature again (#2681)
  * Fix agent's Signature & Timezone dropped on update (#2720)
  * Fix crash in user CSV import (#2708)
  * Fix crash in user ajax lookup (#2600)
  * Send Reference and In-Reply-To headers only for thread items pertinent to
    the receiving user (#2723)
  * Properly clean HTML custom fields (#2736)
  * Fix changing/saving properties on internal ticket statuses, with the
    exception of the state (#2767)
  * Fix CSV list import (#2738)
  * Fix late redirect header for single ticket typeahead result (#2830)
  * Add sortable column headers in the ticket and task queues (#2761)
  * Fix several issues with the file CLI app (#2808)
  * Fix config crash on install (#2827, #2844)
  * Set due date based on user's timezone (#2812, #2981)
  * Fix crash rendering some email addresses to string (#2844)
  * Fix crash rendering thread with invalid timestamps (#2844)
  * Log assignment note (comments), if any, when staff created ticket is
    assigned (#2944)
  * Change transient SLA, on transfer,  if target department has a valid SLA
    (#2944)
  * Fix typo on task transfer modal dialog (#2944)
  * Fix ticket source on ticket edit (#2944)
  * Convert user time to database time when querying stats (#2944)
  * Fix date picker clearing input on invalid date format (#2944)
  * Show topic-specific thank-you page (#2915)
  * Department manager can be excluded from the new ticket alert (#2974)
  * Do not scrub iframe `@src` attribute (#2940)

### Performance and Security
  * Use full-text search for quick-search typeahead boxes (#2479)
  * Speed up a few slow and noisy queries (5c68eb3, 340fee7, 208fcc3)
  * Lower memory requirements processing attachments (#2491, #2492)
  * Ensure agent still has access when reopening a ticket (#2768)
  * Always perform validation server-side for ajax uploads (#2844)
  * Protect access to files shown in the FileUpload field (#2618)
  * Decode entities prior to HTML scrubbing (#2940)

### Known Issues
  * Uploading multiple files simultaneous (via drag and drop) will cause some
    files to be dropped

osTicket v1.10-rc.2
===================
### Enhancements
  * Lazy locking system for ticket locking (#2325, #2351, 37cdf25, de92ec5,
    37a0676)
  * Add settings for avatars and local "Oscar's A-Team" avatars (#2334)
  * Several UI tweaks (7436195, #2426)
  * Add transfer and assign mass actions to tickets (#2375)
  * Import agents from the command line (#2323)
  * User select dialog can be opened after closing in new ticket by staff
    (605c313)
  * Deadband new message alert and autoresponse to once per five minutes per
    user per thread (598dedc)
  * [Add Rule] button to add many new rules at one to a ticket filter (c03279d)

### Improvements
  * Fix several install and upgrade-related issues (fc10dcb, e1ca975, b709139,
    abc8619, #2411, 832ea94, abb9a08, e3bb6c2, 8e373d4)
  * Fix database timezone detection on Windows (#2297)
  * Fix several tasks related issues (#2311, #2344, #2376, #2400, #2421, c3d48a9)
  * Fix hiding of department-specific canned responses (#2315)
  * Fix add and edit of ticket status list items (#2314)
  * Fix incorrect definition of some ORM tables (#2324, 69839af)
  * Fix crash rendering a closed ticket (#2328)
  * Fix case-insensitive sorting of help topics (#2357)
  * Fix several advanced search related issues (#2317, 3d4313f, ce3ceae,
    b5e6d4e, 5a935ca)
  * Fix incorrect SQL deleting a department (#2359)
  * Fix incorrect array usage of department members for alerts (#2356)
  * Add missing perm for view all agents' stats (#2358)
  * Fix missing thread inline images from redactor image manager (be77da4)
  * Fix updating configuration for file upload fields (2f4f9c1)
  * Fix crash creating tickets with canned attachments (a156bba)
  * Fix missing inline images in mailouts (84c9b54)
  * Prefer submitted text over last-saved draft (46ab79b)
  * Fix incorrect FAQ link in front-page sidebar (ea9dd5f)
  * Fix missing assignee selection on new ticket by staff (7865eee)
  * Fix issue details showing up on ticket edit (a183a98, 7fbd0f6)
  * Fix inability to change SLA on some tickets (#2392)
  * Fix auto-claim on new ticket by staff if a filter added a canned reply (c2ce2e9)
  * Fix Dept::getMembersForAlerts() missing primary members (abc93efd)
  * Fix inability to create tickets if missing the ASSIGN permission on all
    depts (0c49e62)
  * Fix inability as staff to reset a user's password (0006dd8)
  * Render fields marked !visible and !editable, but required on the client
    portal (7f55a0b)
  * Fix sorting of help topics (a7cc49f, 08a32a4)
  * Fix new message alert to a random staff member (d3685a9)
  * Fix saving abbreviations on new list items (538087b)
  * Fix parsing of some multi-part MIME messages (c57c22a)
  * Fix numerous crashes

### Performance and Security
  * Improve performance loading the ticket view (6bba226, 4b12d54)
  * Improve performance loading queue statistics (0a89510, 6b76402)
  * Dramatically improve full-text search performance (167287d)

osTicket 1.10
==================
## Major New Features

### Internationalization, Phase III
![screen shot 2014-10-18 at 11 40 38 pm](https://cloud.githubusercontent.com/assets/672074/4692086/b16b1474-574a-11e4-89e7-b871ff591802.png)

Phase III of the internationalization project is the next major advancement of
osTicket language support. The greatest improvement is that all
administratively customizable content. While this is a great last mile for many
multilingual support teams, we've also revisited the client interface main
pages as well as the knowledge base on both the client and staff panels.

  * Elect primary and secondary languages
    * Any language can be the primary, any number of languages can be secondary
    * English-US can be disabled
    * Order of secondary languages is sortable and controls flag order
  * All content is translatable to secondary languages
      * Help Topics
        * Alphabetic sorting happens after translation
      * SLA Plans
      * Departments
      * Custom Forms (and all configurations such as placeholders)
      * Custom Lists
        * Items
        * Properties and configurations
      * Site Pages
      * FAQ Categories
      * FAQ Articles
        * Common attachments (available for all translations)
        * Per-language attachments
      * Content such as welcome emails and password-reset emails
  * Olson timezones are used instead of GMT offset
    * Auto-detect support for agent and client timezone
  * Time and date formats can be automatic by locale preference now.
    * Locale preference is the default
    * Locale preference with forced 24-hour time is also an option
    * Advanced format is also possible using the intl library and `sprintf` as a backup
    * Formats including the day of the week are localized
    * Chinese and Arabic formats using alternate day, month, and year digits and separators are now automatic.
  * Client portal has HTML headers indicate search engine links to pages of other
    languages, as well as the Content-Header HTTP header to indicate the intended
    audience
  * Spell check in text boxes, textareas and rich text editors should respect the
    language of the content being edited

### Tasks
![screen shot 2015-05-06 at 12 36 14 pm](https://cloud.githubusercontent.com/assets/672074/7616658/c5147c68-f96b-11e4-85b7-e74a3482bb4f.png)

Tasks are sub-tickets which can be created and attached to tickets as well as
created separately. Tasks have their own assignees, department routing and
visibility, due date, and custom data. Tasks have their own threads and can
have a list of collaborators. All in all, tasks may very well be the greatest
advancement of osTicket since the advent of the ticket itself.

### New Advanced Search
![screen shot 2015-05-13 at 12 35 15 pm](https://cloud.githubusercontent.com/assets/672074/7616759/94616a1c-f96c-11e4-8c19-ae1ca26a85c0.png)

The advanced search feature is rewritten to address several  shortcomings of
the original feature as well as a host of new features including
  * Search by any field, built-in or custom
  * Save your searches
  * Advanced search is shown as a new queue
  * Current advanced search criteria is maintained between searches
  * Sorting options are relevant to queue and preference remains after navigation between queues

## Minor New Features

### Thread editing
![screen shot 2015-03-20 at 6 56 10 pm](https://cloud.githubusercontent.com/assets/672074/6762680/ce4e78a0-cf32-11e4-9316-c0a969e9c70a.png)

Thread items can now be edited. The original entries are preserved and are
accessible via a thread item's "History". Items can be resent with or without
editing them, and a signature selection is available when resending.

### Roles, and custom extended access
![screen shot 2015-05-03 at 9 05 12 pm](https://cloud.githubusercontent.com/assets/672074/7448163/257ce586-f1d8-11e4-8ed8-a11324d13027.png)

The group permissions component has been offloaded to a new component, named
"Roles". Roles allow for naming a set of permissions. Agents now have a
"Primary Role" which defines their access to global things like the user
directory and their access for their primary department. Each department
granted via "Groups" is allows to be linked to a distinct "Role". This allows
granting Read-Only access to some departments, for instance.

### Improved knowledge base interface
![screen shot 2014-10-18 at 11 55 58 pm](https://cloud.githubusercontent.com/assets/672074/4692123/5ec01038-574c-11e4-80a7-7e8a8efe3963.png)
  * "Featured" articles show on the front page
  * Knowledge base search on front page
  * Translatable content
  * Locale-specific attachments

### Multiple forms and disable individual fields for Help Topics
Help Topic configuration has a new super feature. Multiple forms can now be
associated with each help topic, and the order the forms should appear for new
tickets and editing tickets is configurable. Previously, the custom forms were
always rendered above the "Ticket Details" form; but now it's completely
customizable. What's more is that individual fields **including the issue
details** can be disabled for any help topic.

### Department hierarchy
Departments are now nestable. All departments can have a parent department, and
the hierarchy is arbitrarily nestable. Access is cascaded so that access to a
parent department automatically extends access to all descendent departments.

### Image annotation
![screen shot 2015-05-04 at 9 07 38 pm](https://cloud.githubusercontent.com/assets/672074/7466027/ac34575c-f2a1-11e4-9335-417960f89334.png)

Images can be annotated to add simple shapes like ovals, boxes, arrows and
text. Annotates can be committed, and a new image is created from the
annotations; however, annotations can still be edited before the thread post is
submitted. Annotations are supported for both clients and agents, and the
images can be selected from the ticket thread, so images already posted can be
easily marked up.

### Variable context type-ahead
![screen shot 2015-04-20 at 4 32 58 pm](https://cloud.githubusercontent.com/assets/672074/7240963/ee930d8c-e77a-11e4-8928-26240274db13.png)

When editing content which uses variables, such as a thank-you page or an email
template, variable placeholders now use a type-ahead feature. This new pop out
significantly improves the connection between which variables are available in
which templates. It also allows for adding significantly to the variable
library without relying on exhaustive documentation to convey this information.
Some new variables include
  * User lists, such as department members, team members, and collaborator lists
  * Lists can be rendered as names, emails, or both
  * Dates are format-able to time, short, full, and long
  * Dates can be humanized to something like *in about an hour*
  * Dates can be auto localized and formatted to the recipients locale and time
    zone selection
  * Attachments to thread items and custom fields can be attached via variable
    (e.g. `%{message.files}`)

### Redesigned list management
![Simplified, tabular, paginated view of list items, with mass actions](https://cloud.githubusercontent.com/assets/672074/5881786/3040d162-a309-11e4-9529-8ae51d358f81.png)

The list management feature has a significant overhaul to accommodate larger
lists. It also provides a heads display of list item properties as well as AJAX
updates. CSV import and pagination have also been added as well as mass enable,
disable, and delete.

### Pluggable filter actions
![screen shot 2015-05-04 at 8 59 32 pm](https://cloud.githubusercontent.com/assets/672074/7465977/801b4cbc-f2a0-11e4-9598-95dd52e79e82.png)

Filter actions are now far more flexible allowing for more elaborate and
creative filter actions to be created. A new filter action has been added as an
example of future possibilities: send an email. The new feature allows for
ticket filter actions to be defined without modification to internal table
structures, and even allows actions to be created via plugins!

Actions are also sortable and performed in the order specified, which allows
doing something like sending an email before rejecting the ticket.

### Other Improvements
#### Custom Data
* Fields have more granular access configuration. View, edit, and requirement
  can be enabled individually for both agents and end users
* Fields can be marked for required for closed. Therefore they can inhibit
  closure of a ticket without a valid value.

#### Export
The agent's locale is considered when exporting CSV and semicolon separators
are used where necessary

#### User Interface
The subject line and many other text fields around the system are truncated by
the browser, which fixes early truncation for some language with long Unicode
byte stream, such as Chinese.

#### Improved lock system
The ticket lock system uses a code now which is rotated when updates to tickets
are submitted. This helps prevent unwanted extra posts to tickets. A new
annoying popup is displayed when viewing the ticket and the lock is about to
expire.

#### Draft system
The draft system has been rewritten to reduce the number of requests to the
backend and to reduce the dreaded "Unable to save draft" popup

#### ORM
The database query system is being redesigned to use an object relational
mapper (ORM) instead of SQL queries. This will eventually lead to fewer
database queries to use the system, cleaner code, and will allow the use of
database engines other than MySQL. The ORM was originally introduced in
osTicket v1.8.0, but has seen the greatest boost in capability in this release.
About 47% of the SQL queries are removed between v1.9.7 and v1.10

osTicket v1.9.12
================
### Improvements
  * Fix missing search box adding user to organization (#2431)
  * Fix incorrect update time on FAQ view in staff portal (194f890)
  * Fix incorrect parsing of some multi-part MIME messages (fe62226)
  * Fix auto-claim for new ticket by staff if a filter added a canned response
    (eca531f)
  * Fix malformed results on remote user search when adding users (#2335)
  * Fix search by ticket number on client portal (#2294)
  * Fix association of user email without a domain to an organization without
    an email domain setting (#2293)

### Performance and Security
  * Revert poor performing ticket stats query (#2318)

osTicket v1.9.11
================
*We skipped v1.9.10 to avoid confusion with v1.10 (the major release coming out at the same time)*

### Enhancements
  * Log to syslog on php mail() error (#2128)
  * Full path of help topics shown in filter management (3d98dff)
  * Auto rebuild the search index if %_search table is dropped (#2250)
  * New version available message in system information (0cca608)

### Improvements
  * Fix appearance of ` <div>` in user names (*regression in v1.9.9*) (be2f138)
  * Out-of-office notification does not clear closing agent (#2181)
  * Fix check for departments limiting assignees to members only (#2143)
  * Fix signal data pass by reference (#2195)
  * Fix template variables not rendering in href attributes (#2223)
  * Fix missing custom data for new users (#2203)
  * Fix incorrect cli option expansion (#2199)
  * Properly encode `To` header for php mail() sends (857dd22)
  * Fix incorrect message body when fetching TNEF emails (0ec7cf6)
  * Fix layout of some tables in PDF export (cef3dd3)

### Performance and Security
  * Fix XSS issue on choices field type (#2271)

osTicket v1.9.9
===============
### Enhancements
  * Properly balance stripped and invalid HTML (#2145)
  * Add MANIFEST file to deployment process and retire duplicate code for packaging (#2052)

### Improvements
  * Fix inability to configure LDAP and S3 plugins (*regression*) (59337b3)
  * Fix incorrect whitespace in search indexed HTML content (#2111)
  * Add support for invalid `multipart/relative` content type (aaf1b74)
  * Force line breaks for very long HTML lines (56cc709)

### Performance and Security
  * Fix slow query for ticket counts for large datasets (c4ace2d)
  * Fix slow thread load query (thanks @torohill) (7b7e855)

osTicket v1.9.8.1
=================
### Enhancements
  * Add option to disable email address verification

### Improvements
  * Fix crash upgrading from osTicket v1.6

osTicket v1.9.8
===============
### Enhancements
  * Update user information for existing users when importing CSV (#1993)
  * Agent names are consistently formatted and sorted throughout the system (#1972)
  * Memcache session backend support. (See `include/ost-sampleconfig.php`) (#2031)
  * Email domain validation includes DNS record verification (#2042)
  * Make ticket queue selection sticky (aa2dc85)

### Improvements
  * Fix incorrect mapping of ISO charsets to ISO-8859-1, thanks @nerull7
  * Fix unnecessary drop of ticket CDATA table because of update to deleted
    field (#1932)
  * Fix inability to create or update organization custom data (#1942)
  * Fix inability to update some fields of user custom data (#1942)
  * Fix filtering user custom data for email tickets (#1943)
  * Fix missing email headers resulting in incorrectly threaded emails when
    delivered (#1947)
  * Cleanup file data when removing custom file uploads (#1942)
  * Fix crash when exporting PDF and PHAR extension is not enabled
  * Fix crash processing some TNEF documents (89f3ed7, #1956)
  * Fix handling of GBK charset when gb2312 is advertised (#2000)
  * Fix link to client ticket listing when logged in, thanks @neewy (#1952)
  * Disambiguate staff and collaborators when processing a some emails (#1983)
  * Fix several i18n phrase and layout issues (#1958, #1962, #2039)
  * Improve detection of some bounce notices with alternative content (#1994)
  * Fix image URL rewrite when pasting existing images, from a KB article for
    instance (#1960)
  * Preserve internal note formatting on new ticket by staff if HTML is
    disabled (#2001)
  * Touch organization `updated` timestamp on custom data update (#2007)
  * Fix deployment on Windows® platforms, thanks @yadimon (#2033)
  * Fix upgrade crash if retrying an old, failed upgrade from v1.6 (#1995)
  * Fix corruption of some html content (9ae01bf)

osTicket v1.9.7
===============
### Enhancements
  * Remote IP is logged for staff replies (#1846)
  * Add option to require client login to view knowledge base (#1851)
  * Internal activity alert, replacing the internal note alert, includes alerts
    of responses made by other agents (#1865)
  * Email system now uses LF instead of CRLF as the default (#1909)
  * Mass actions for user directory (#1924)
  * Unassign tickets on transfer if current assignee is not a member of the new
    department and the department has "Restrict assignment to members" enabled
    (#1923)

### Improvements
  * Clear overdue flag when a ticket is closed, thanks @A-Lawrence (#1739)
  * Clear attached file listing on client post (regression) (#1845)
  * Delete ticket custom data on delete (#1840)
  * Trim whitespace from filter match data on update (#1844)
  * Fix dropping of custom data on API post (#1839)
  * Fix advanced search on create date (#1848)
  * Fix initial load and pagination of dashboard page (#1856)
  * Fix incorrect internal/public category setting in drop down for new FAQ
    (#1867)
  * Add UTF-8 BOM to CSV export for correct Unicode detection (#1869)
  * Fix not considering the setting for alert assigned on new message (#1850)
  * Skip new activity notice if collaborator(s) included in email To or Cc
    header (#1871)
  * Fix inability to uncheck a custom data checkbox (#1866)
  * Fix advanced search for unassigned tickets (#1857)
  * Fix navigation warning if not using the lock feature (#1898)
  * Fix detection of message of some bounce notices (#1914)
  * Fix SQL alert with multiple Message-ID headers (#1920)
  * Add a warning if attempting to configure archiving for POP accounts (#1921)
  * Fix missing UTF-8 output encoding header for staff control panel (#1918)
  * Fix z-index issue between popup previews and modal dialogs (#1919)
  * Record imported file backend when importing files (f1e31ba)

### Performance and Security
  * Fix XSS vulnerability in sequence management (88bedbd)
  * Defer loading of thread email header information when loading ticket thread
    (#1900)

osTicket v1.9.6
===============
### Enhancements
  * New Message-Id system allowing for better threading in mail clients (#1549,
    #1730)
  * Fix forced session expiration after 24 hours (#1677)
  * Staff panel logo is customizable (#1718)
  * Priority fields have a selectable default (instead of system default) (#1732)
  * Import/Export support for file contents via cli (#1661)

### Improvements
  * Fix broken links in documentation, thanks @Chefkeks (#1675)
  * Fix handling of some Redmond-specific character set encoding names (#1698)
  * Include the users name in the "To" field of outbound email (#1549)
  * Delete collaborators when deleting tickets (#1709)
  * Fix regression preventing auto-responses for staff new tickets (#1712)
  * Fix empty export if ticket details form has multiple priority fields (#1732)
  * Fix filtering by list item properties in ticket filters (#1741)
  * Fix missing icon for "add new filter", thanks @Chefkeks (#1735)
  * Support Firefox v6 - v12 on the file drop widget (#1776)
  * Show update errors on access templates (#1778)
  * Allow empty staff login banner on update (#1778)
  * Fix corruption of text thread bodies for third-party collaborator email
    posts (#1794)
  * Add some hidden template variables to pop out content (#1781)
  * Fix missing validation for user name and email address (#1816, eb8858e)
  * Turn off search indexing when complete, disable incorrectly implemented
    work breaking, squelch error 1062 email from search backend (afa9692)
  * Fix possible out of memory crash in custom forms (#1707, 0440111)

### Performance and Security
  * Fix generation of random data on Windows® platforms (#1672)
  * Fix possible DoS and brute force on login pages (#1727)
  * Fix possible redirect away from HTTPS on client login page, thanks @ldrumm
    (#1782)

osTicket v1.9.5.1
=================
### Improvements
  * Fix file.php to serve files added to system before osTicket v1.9.1
  * Fix file.php to serve files if client panel or system is offline
  * Fix popover download of inline images
  * Avoid de-duplicating zero-length files
  * Send new message alert to team members if not assigned to an agent
  * Fix import of users to organization not setting the organization
  * Fix redactor toolbar showing over the date picker (#1450, thanks @Chefkeks)

### Performance and Security
  * Fix XSS vulnerability in client language selection

osTicket v1.9.5
===============
### Enhancements
  * Add support for organization vars in templates
    (`%{ticket.user.organization...}`) (#1561)
  * Canned responses feature can now be disabled (#1562)
  * Drop link redirection through l.php (#1640)
  * Use unified file download script (#1641). Links can now be shared with
    external users and accessed without authenticating.
  * Ticket filters support matching and banning based on the Reply-To user
    information (#1645)

### Improvements
  * Remove custom data when users are deleted (#1492)
  * Fix matching of ticket number in subject (regression in v1.9.4) (#1486)
  * Several minor translatable strings (#1441, #1489, #1560), thanks @Chefkeks
  * Fix invalid UTF-8 chars PDF error for empty thread title (regression in
    v1.9.4) (#1512)
  * Consider auto response checkbox and department setting for new ticket by
    staff (#1509)
  * Fix PHP crash if `finfo` extension is missing (#1437)
  * Fix export of choice field items (#1436)
  * Properly handle alert and auto response flags from API (#1435), thanks
    @stevepacker
  * Fix current value of choice fields if set to boolean false (#1466)
  * Do not reopen tickets for automated responses (#1529)
  * Properly handle uppercase file extensions in file field configuration
    (#1549)
  * Fix release of ticket lock when navigating away from ticket view (#1552)
  * Display FAQ article consistently on client portal (#1553)
  * Avoid wrapping password reset URLs on text emails (#1558)
  * Fix field requirement for clients when only required for agents (#1559)
  * Fix language selection for new email template group (#1563)
  * Fix incorrect status of new ticket if opened as `closed` and assigning to
    an agent (#1565)
  * Forbid disabling the only active administrator (#1569)
  * Searching for tickets searches to midnight of the end date (#1572), thanks
    @grintor
  * Fix rejection of tickets by filter, even if a previous matching filter
    would stop on match (#1644)
  * Fix matching of `User / Email Address` in ticket filters (#1644)
  * Properly HTML escape thread bodies when quoting (#1637)
  * Use department email for agent alerts (#1555)
  * Skip team assignment alert on new ticket if assigned to an agent (fddb3c7)
  * Use custom form name as the page title when editing (#1646)

### Performance and Security
  * Fix possible XSS vulnerability in sortable table view pages (#1639)

osTicket v1.9.4
===============
### Major New Features
  * New ticket states (resolved, archived, and deleted) (#1094, #1159)
  * Custom ticket statuses (#1159)
  * Custom ticket number formats (#1128)
  * Full text search capabilities (*beta*)
  * Multiselect for choice fields and custom list selections
  * Phase II Multi-Lingual Support (User Interface) (see
    http://i18n.osticket.com and http://jipt.i18n.osticket.com) (#1096)
    * Active interface translations of 46 languages currently
    * Popup help tip documentation in all languages
    * Flags displayed on client portal for manual switch of UI language by
      EndUsers
    * Automatic detection of enduser and agent language preference as
      advertised by the browser
    * Improved PDF ticket printing support, including greater support for
      eastern characters such as Thai, Korean, Chinese, and Japanese
    * Proper support for searching, including breaking words for languages
      which do not use word breaks, such as Japanese
    * Proper user interface layout for right-to-left languages such as Hebrew,
      Arabic, and Farsi
    * Right-to-Left support for the HTML text editor, regardless of the viewing
      user’s current language setting
    * Proper handling of bidirectional text in PDF output and in the ticket
      view

### Enhancements
  * Plugins can have custom configurations (#1156)
  * Upgrade to mPDF to v5.7.3 (#1356)
  * Add support for PDF fonts in language packs (#1356)
  * Advanced search improved to support multiple selections, custom status and flags

### Improvements
  * Fix display of text thread entries with HTML characters (`<`) (#1360)
  * Fix crash creating new ticket if organization custom data has a selection field (#1361)
  * Fix footer disappearance on PJAX navigation (#1366)
  * Fix User Directory not sortable by user status (#1375)
  * Fix loss of enduser or agent priority selection on new ticket (#1365)
  * Add validation error if setting EndUser username to an email address (#1368)
  * Fix skipped validation of some fields (#1369) (*regression from rc4*)
  * Fix detection of inline attachments from rich text inputs (#1357)
  * Fix dropping attachments when updating canned responses (#1357)
  * Fix PJAX navigation crash in some browsers (#1378)
  * Fix searching for tickets in the client portal (#1379) (*regression from rc4*)
  * Fix crash submitting new ticket as agent with validation errors (#1380)
  * Fix display of unanswered tickets in open queue (#1384)
  * Fix incorrect statistics on dashboard page (#1345)
  * Fix sorting by ticket number if using sequential numbers
  * Fix threading if HTML is enabled and QR is disabled (#1197)
  * Export ticket `created` date (#1201)
  * Fix duplicate email where a collaborator would receive a confirmation
    for his own message (#1235)
  * Fix multi-line display of checkbox descriptions (#1160)
  * Fix API validation failure for custom list selections (#1238)
  * Fix crash adding a new user with a selection field custom data
  * Fix failed user identification from email headers if `References` header
    is sorted differently be mail client (#1263)
  * Fix deletion of inline images on pages if draft was not saved (#1288)
  * Fix corruption of custom date time fields on client portal if using non
    US date format (#1320)
  * Fix corruption of email mailbox if improperly encoded as ISO-8859-1
    without RFC 2047 charset hint (#1332)
  * Fix occasional MySQL Commands OOS error from ORM (#1334)

### Performance and Security
  * Fix possible XSS vulnerability in email template management (#1163)

osTicket v1.9.3
===============
### Enhancements
  * Redactor link dialog has a few common links selectable (#1135)

### Improvements
  * Fix missing `%{recipient}` variable used in canned reply (filters) (#1047)
  * Fix `%{ticket.close_date}` variable in email message templates (#1090)
  * Fix timezone offset used in time drop down (#1103)
  * Fix premature session expiration (#1111)
  * Correctly tag emails with source `email` (#1104)
  * Correctly handle custom data for help topics (#1105)
  * Fix validation and display issues for email mailboxes with system default priority and department (#1114)
  * Fix crash when rendering custom list drop-downs with retired list items (#1113)
  * Avoid system alert notices bouncing and creating tickets (#1115)
  * Redactor no longer shortens URLs (#1135)

### Performance and Security
  * Fix XSS vulnerability in user name (#1108, #1131)

osTicket v1.9.2
===============
### Enhancements
  * Help topics have super powers (#974)
    * They can be arbitrarily nested
    * They can be manually sorted
    * Admins can select a system default help topic
    * They can inherit the form from a parent
  * Form data entered to custom forms is preserved when switching help topics
  * Update to Redactor 9.2.4 (http://imperavi.com/redactor/log/)
  * Using canned responses no longer requires [Append] click (#973)
  * Guests can sign out (#1000)
  * Filter by custom list item properties (#1024)
  * Time selection is based on admin configured time format (#1036)
  * (Optionally) clients can access tickets without clicking email link (#999)
  * Introduction of signals for mail filter plugins (#952)

### Improvements
  * Fix a few glitches on site page management (#986)
  * Fix saving department alert recipients (#985)
  * Fix assignment to account manager regardless of setting (#1013)
  * Fix dialog boxes on some PJAX navigations (#1034)
  * Help topics are properly sorted in FAQ management (#1035)
  * Fix MySQL commands out-of-sync triggered by the ORM (#1012)
  * Clients can follow email links from multiple tickets (#1001)
  * Workaround for PHP variable corruption issue (#917, #969)
  * All other improvements cited in v1.8.3

### Performance and Security
  * Fix XSS vulnerability in phone number widget (#1025)
  * Fix several XSS vulnerabilities in client and staff interfaces (#1024, #1025)

osTicket v1.8.4
===============
### Improvements
  * Fix misleading and incorrect custom form management pages (#919)
  * Fix linked external image tag corruption (#936)
  * Fix multiple [Show Images] button for external images in client interface (e4b721c)
  * Properly handle email address personal names with commas (#940)
  * Organizations can define a website now (13312dd)
  * Correctly handle email headers with leading tabs (RFC 2047) (#895, #953)
  * Implement `%{ticket.user.x}` for email templates and canned responses (#966)
  * Handle shameful `X-AMAZON-MAIL-RELAY-TYPE` invented by Amazon
  * Issue summary field type must have associated data (#987)
  * Fix `%{recipient.ticket_link}` for new message auto response (#989)
  * Fix corruption of `%{company.name}` on new ticket notice (#1002)
  * Fix signal data byref (#1037)
  * Correctly handle email priority headers (#491)
  * Fix mail header newline corruption with the Suhosin extension (#442)

### Performance and Security
  * Fix XSS vulnerability in the phone number widget (#1025)
  * Fix several XSS vulnerabilities (#1025)

osTicket v1.9.1
===============
### Enhancements
  * [Draft Saved] box does not show if nothing entered yet (be38e8b)
  * `Your Information` is now translatable (b189b86)
  * Canceling new ticket also deletes drafts (2695dce)
  * A users organization can be updated (#955)
  * Users can be removed from an organization (#957)

### Bugs
  * Fix confusing form view after adding a new form (#923)
  * Fix whitespace munging in emails if HTML ticket thread is disabled (#924)
  * Fix [loading] popup on form save (#925)
  * Fix URLs in emails linking through l.php (#929)
  * Fix crash on custom list view if there no properties defined (#935)
  * Fix handling of encoded email mailboxes with commas (#940)
  * Fix display of link, external images in the ticket thread (99e719d)
  * Fix crash submitting a new ticket with organization collaborators (7335525)
  * Fix handling of custom date and time fields (#944)
  * Fix PJAX detection of new deployment (a18bf0c)
  * Fix continual release of ticket locks after navigation (30a3d2)
  * Fix logout if following link from client email (bda2e42)
  * Fix un-editable organization website (13312dd)
  * Fix incorrect constant usage in User object (#958)

osTicket v1.9
=============
### Client Login and Registration
Setup flexible user registration policy for your help desk to match your
needs. Users can register for accounts via the client portal and can now
login with a username and password instead of email and ticket number. We
also have a forgot-my-password link and several other new minor adjustments
to the user profile.

### External Authentication Support
Use third-party SSO to authenticate your users and staff. Initial support
include OAuth2 and LDAP (v0.5 of the LDAP plugin is required)

### User Directory
Search, view, and manage, even delete! contact information from the users
from whom you receive tickets. Staff can also manually register users and
even set an initial password. Users can also be imported and exported via
CSV data.

### Organizations
Organize your users together into organizations. Organizations can have
internal owners ("Account Manager") and external owners ("Primary Contact").
The Account Manager can receive new ticket and new message alerts.
Organization Primary Contacts and members can be automatically added to
tickets as collaborators.

### User and Organization Notes
Quickly view, edit, add and remove pertinent notes on your users and
organizations

### Form Management
Staff members can now add, delete, and sort forms attached to tickets, users
and organizations as well as remove stale data where fields have been
retired from active forms.

### Custom Properties for Lists
Add properties to your list items and use it in your email templates and
pages. For example create an address property to a list of locations. List
items can also be disabled now, which causes them to be hidden from
selection.

### PJAX page loading
For browsers supporting PJAX, navigating around the system will see a
performance improvement as javascript and css files are not re-parsed for
each page load.

### Redactor 9.2
Several new features including a floating editor bar as well better support
for non-US keyboards

### Minor Enhancements
  * Agent selection for assignment can be limited to the current department
  * Complete help tip documentation for the Admin panel
  * Email addresses can have an associated Help Topic
  * Alerts and Notices can be disabled per Department
  * Agent portal can have a login banner
  * Inline images are not displayed with the attachments in the ticket view
  * Original thread content format is saved (html or text)
  * Alerts and Notices support quoted response removal

osTicket v1.8.3
=================
### Enhancements
  * Support filtering based on help topic (#728)
  * Embedded images ([rfc2397](http://www.ietf.org/rfc/rfc2397.txt)) are correctly supported (#740)
  * Allow regular staff members to show assigned open tickets on open queue (#751)
  * Support [rfc1892](http://www.ietf.org/rfc/rfc1892.txt) style bounce notices (#871)
  * Disable autocomplete on CAPTCHA fields (#821)
  * Show `closed` date on the closed ticket queue (#865)
  * Departments support assignment to members only (#862)
  * Department email selection is optional (#862)

### Bugs
  * Fix error output on some systems if the `i18n` folder is not readable (#714)
  * Fix possible crash if an email has no body (#707)
  * Fix errors in download hash generation (#743)
  * Support two-character file extensions (#719)
  * Fix inline images with an invalid content id (#708)
  * Remove confusing false-positive banner and admin email for client login (#763, #765)
  * Fix detection of inline images without a `cid:` URL scheme (#779)
  * Fix crash sending fatal alert email (bdfb2f1)
  * Fix partial corruption of HTML @style attributes (#784)
  * Fix several CSS styles for the staff interface (#785)
  * Properly clear department selection from other settings on deletion (#792)
  * Users with ticket-edit rights can see the "Change Owner" option in the more drop-down (#799)
  * Links to new osTicket site are now correct (#808)
  * Fix incorrect ticket count on simple ticket search (#809)
  * Fix attachment size detection on systems with `mbstring.func_overload` set (#811)
  * Fix horribly incorrect TNEF stream parsing (#825)
  * Fix incorrect SQL query searching staff directory (91d65d9)
  * Properly trim user input for ban list entries (#837)
  * Ticket assignment alert can be disabled (#839)
  * Preserve newlines in long answer form fields (with HTML disabled) (a04c5e7)
  * Fix javascript error on form submission with empty date picker field (0013b40)
  * Fix images in message portion of the new ticket notice to end user (#842)
  * Send new internal note alert to assigned team members (#858)
  * Properly strip leading and trailing whitespace from text/plain emails (fa7a025)
  * Fix incorrect default template for ticket auto responses (97d6e25)
  * Canned responses can be disabled (120d90b)
  * Don't corrupt filters with selected, disabled teams, SLAs (120d90b)
  * Fix crash sending some alert emails (efa7311)
  * Fix HTML scrubbing with some content-ids (efa7311, eb5861f)
  * Squelch E_WARN from ContentAjaxAPI::getSignature (ed33d06)
  * `@localhost` is *not* a valid email address (f40c018)
  * Fix `web.config` for newly patched IIS setups (78a47c2)
  * Honor disabled assignment alerts for teams (#894)
  * Send out internal note alerts to assigned staff (and Team) if Agent closes the ticket with the note (#903)

### Performance and Security
  * Fix cross site scripting (XSS) vulnerability in thread entry widget (9916214)
  * Mail parsing uses significantly less memory (#861)


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
