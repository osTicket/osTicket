
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
