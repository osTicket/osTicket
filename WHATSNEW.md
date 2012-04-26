New stuff in 1.7-dpr3
======================


New stuff in 1.7-dpr2
======================

Features
--------
  * Autocomplete for ticket search box (emails and ticket numbers typeahead)
  * Redesigned staff login page
  * Warning when leaving unsaved changes in admin and staff settings pages
  * Auto change admin settings pages when selecting a new page from the
    drop-down list
  * Create a ticket in one click from the staff panel
  * Preview ticket from the search results
  * Export tickets to CSV file

Issues
------
  * (#1) Automatically cleanup orphaned attachments
  * (#2) Reject ticket creation when a matching email filter has
         'Reject email' set
  * (#3) Ticket search results are properly paginated
  * (#4) Make email filters editable
  * (#5) Add .htaccess for API URLs rewrites
  * (#6) Add utf-8 content type declaration for installer HTML output
  * (#8) Fix installer for PHP settings with 'register_globals' enabled

Outstanding
-----------
  * Implement the dashboard reports
  * Advanced search form for ticket searches
  * Multi-file upload for responses, notes, and new tickets
  * PDF export for ticket thread
  * Misc. improvements

New Features in 1.7
===================
Version 1.7 includes several new features

Email Filters
-------------
As an upgrade from email banning (which is still supported), email filters
allow for matching incoming email in the subject line and message body. For
matching emails, the administrator has the ability to automatically route
tickets:

  * To a specific department, staff member, and/or team
  * Automatically assign ticket priority and/or service-level-agreement
  * Disable ticket auto-responses

Canned Attachments
------------------
Attach files to your canned responses. These attachments are automatically
attached to the ticket thread along with the canned response. The
attachments are not duplicated in the database and therefore use virtually
no space.

Service Level Agreements
------------------------
Service level agreements allow for a configurable grace period based on the
department or help topic associated with the ticket. A default SLA is
provided, and a due date can be set to override the grace period of the SLA
for a ticket.

Client-side Knowledgebase
-------------------------
Manage a searchable help document portal for your users

API
---
Interface with osTicket via HTTP requests. Starting with version 1.7,
tickets are createable by submitting an HTTP POST request to either

    /api/tickets.xml
    /api/tickets.json
