osTicket v1.16.3
================
### Improvements
* installer: Help Topic Disabled Fields (81e99fe4)
* Do not autocomplete new access fields of the (another) user (02633694)
* issue: mPDF Table Print (38c0979e)
* Make string localizable (4cc509b1, 612183ce)

### Security
* mpdf: Unregister PHAR (57721def)
* issue: Form Elements & Attribute (45b6cf2e)
* Security: Session Fixation (85a76f40)
* security: Unvalidated Password Change (01a378f6)
* xss: System Logs (334934ec)
* xss: Agent Directory (a5c4d931)

osTicket v1.16.2
================
### Improvements
* Issue: Topic->getHelpTopics() don't return localized names when $allData = true (a078a0f)
* class.email: allow empty smtp_passwd when existing (0d0d8a1)
* Fixes permission issue when registration mode ist disabled (dee6a13)
* email: use correct e-mail formatting (7692637)
* Fix HTML syntax in thread view (84913f5)
* Fix slugify regex (f72691c)
* issue: preg_match Pass By Reference (148a2e7)
* issue: php_analyze each() (f627a5e)
* issue: Deprecated Required Parameter (0d0ab46)
* tests: UninitializedVars (60f6ad7)
* tests: Validation Checks (91e7d30)
* issue: QueueSort (92e820c)
* issue: Task Export (baa46d3)
* issue: PEAR Mail parseAddressList() (7130056)
* issue: Filter Events (8c9b392)
* issue: Last Message Data Source (d185e50)
* issue: Undefined Constant GLOB_BRACE (0499b97)
* Update edit.inc.php (8b5ea64)
* Update ticket-preview.tmpl.php (e6f437c)
* Update ticket-view.inc.php (84c4eb8)
* issue: Nullable date() Timestamp (a77158b)

osTicket v1.16.1
================
### Improvements
* issue: Remaining Deprecated each() (69db3a7)
* issue: User sendUnlockEmail (13652db)
* issue: Auth_SASL Non-Static (4378f77)
* issue: Banlist Non-Static (96995e1)
* issue: PHP Version Requirements 1.16.x (fdc0445)
* issue: Email Templates Static (fb0b075)
* issue: Status List getSortModes() (6d4650d)

osTicket v1.16
==============
### Enhancements
* PHP8: Static Method Lint Test (958a748)
* Update MPDF (59dc587)

### Improvements
* prereqs: Update README.md (2767ad4)
* upgrader: v1.16 Release (57347ab)
* release: Prep Release (0905e7b)
* php8: Temporarily Suppress Warnings (0afe2b2)
* issue: PEAR each() (1eafd98)
* issue: Further Fixes (03d203b)
* issue: EmailDataParser (c4fc76c)
* issue: Even More Fixes (998cd7d)
* issue: Misc. Fixes (ec9c09d)
* issue: Static to Non-Static (3bb36ff)
* issue: create_function() (7668b71)
* issue: Unparenthesized (64076e2)
* issue: Undefined Key, Var, Prop (6974734)
* issue: Undefined Function each() (e261a5f)
* issue: Calling Non-Static Statically (a4ab19d)
* Fix first problems with PHP 8 (ab77c0a, 29dcbd9, 6b3c7f9, 4d972b5, e63998e)

osTicket v1.15.8
================
### Enhancements
* issue: Check User Status (3a2f072d)
* jquery: Update jQueryUI 1.13.1 (ed958d98)

### Improvements
* issue: Redactor Freezing (42525aca)

### Security
* mpdf: Unregister PHAR (57721def)
* issue: Form Elements & Attribute (45b6cf2e)
* Security: Session Fixation (85a76f40)
* security: Unvalidated Password Change (01a378f6)
* xss: System Logs (334934ec)
* xss: Agent Directory (a5c4d931)

osTicket v1.15.7
================
### Enhancements
* i18n: Bosnian Flag CSS (94f5e95)
* i18n: Translatable Tooltip (e2be9e7)
* i18n: No Tasks String (01016db)

### Improvements
* Use HTTPS version of Crowdin project (d52cc40, da1309a)
* fix staff login redirect loop when system is offline (38b8f53)
* oops: Banlist Created (f0ae76f)
* issue: MaxLength Attribute (225eb7a)
* Update ticket-tasks.inc.php (b063504)
* Issue: Disabled/Archived Primary Dept (0b7c9c2)
* issue: Sweden Capitalization (3cb05ad)
* typo: Emtpy To Empty (9c858b8)
* issue: Fixes Test Errors (34cf2f2)

osTicket v1.15.6
================
### Enhancements
* i18n: Missing translations for the action buttons (823b5fb)

### Improvements
* issue: Required Custom Field User Registration (f4e693e)

osTicket v1.15.5
================
### Enhancements
* redactor: Upgrade to version 3.5.2 (4e4f82e)
* auth: Allow AuthBackends to auto-register Users (6836b17)

### Improvements
* issue: Khmer Language Flag (f878f14)
* issue: Assignee Item Property (3cde88a)
* issue: CDATA Rebuild Issue (9da3b5e)
* issue: Plus Symbol (b0f143e)
* issue: Plain-Text Canned Responses (19c03ca)
* issue: Improved Validation for Variable Names (35c8ca8)
* Revert "issue: List Item Properties On Mouseover" (5c91f64)

osTicket v1.15.4
================
### Enhancements
* Feature: Log Event For Filters (7d48735, 1fd2b6b, 99ec09a)

### Improvements
* Issue: Delete Referrals (790c0e6)
* Show "-Empty-" value for empty due dates in ticket view (64712eb)
* Issue: Audit Closed Ticket Events (311a600)

### Security
* security: PwReset Username and Username Discoverability (e282910, 86165c2)
* security: SSRF External Images (1c6f98e)
* xss: Stored XSS/Domain Whitelist Bypass (4b4da5b)
* security: Recipient Injection via User's Name (7c5c584)
* xss: Advanced Search (4a8d3c8)
* xss: Tasks (b01c6a2)

osTicket v1.15.3.1
==================
### Improvements
* Auth: Service Name (da05573)

osTicket v1.15.3
================
### Enhancements
* Enhancement: S3 Plugin Folder Capability (ae4ed63)

### Improvements
* Auth: Service Name on Client Portal (b755b99)
* CDATA: Rebuild Cdata tables post Install (4409dbd)
* Issue: Deleted Forms on Help Topics (4494bf4)
* Issue: Saving Priority Field (56c50b8)
* CDATA: Dynamic Forms Views (4ab3602)
* CDATA: Check Cdata Tables on Cron (b758c14, 3397987, aba5970)
* CDATA: DynamicFormField Update (678decc, 9f01ee1)
* Issue: Filter Email Variables (3a36727)
* status: Localize Status Names (6173a73)
* Issue: Queue Columns Custom Fields (bd4cfb4)
* Issue: Custom Dept/Custom Assignee Exports (8140d4b)
* Issue: 'Open' heading don't get translated (6065715)
* Make Max-Age consistent across backends (292df94)
* Zip Export: Include Custom File Upload Files (1328bd7)
* Issue: Spaces in List Items (ef6c949)
* Issue: Class PageNate had problems with total value as string (573ab33)
* Tweak PersonsName->getShort() and getShortFormal() (fa64ad4, ad91e66, 8671a68)

### Security
* xss: SVG Image (68dcaa2)
* security: open.php Refresh (b8603c7)
* xss: Client-Side i18n (fd560df)
* session: Verify UserAgent String (f71c954)

osTicket v1.15.2
================
### Enhancements
* Issue: Visibility Permissions (8da9da3)
* Depts Visibility (fe37ae2)
* Issue: Task Inline Transfer (e43d6bf)

### Improvements
* Make word count error match the actual limit (3e177bb)
* Mute warning when Every Minute is selected. (2a56da7)
* Remove extra selection - Choice Field handles prompt. (b564ce3)
* Fail gracefully when supported matches return null (4550b65)
* Create variable before passing it by reference (858649c)
* FAQ: Check for lookup failures (edd1feb)
* 2fa: use isset to check checkbox (7f68060)
* Session: Regenerate session id before closing it. (14e9fb2)
* Issue: Unlinking Tickets (98efec5)
* Issue: Agents/Depts in Queue Cols (d8f6ef6)
* Issue: Revise getDepartments (71f4c0c)
* Issue: Visibility Permissions (3ee5941)
* issue: getDefaultDeptId() On Null (38a09e6)
* issue: Dashboard Export Date Range (db79149)
* Issue: Echoing Default Dept Status (fe31575)
* issue: Export Memory Limit (3a5e5c9)
* Use PageNate->showing() for Users and Orgs in SCP (63f0ae8)
* Don't capitalize Queue menu items (2348850)
* Issue: Disabled Dept on Email (b1397a3)
* issue: Assign To Sort Alphabetically (77c7a12)
* Update class.plugin.php (4a3451f)

### Security
* xss: FormAction Attribute (8d956e0)
* xss: onerror Property (25e6d12)

osTicket v1.15.1
================
### Improvements
* readme: Update PHP Version (a4c85d7)
* placeholder: Quote and encode html chars (0056d14)

osTicket v1.14.8
================
### Improvements
* issue: Auto-Assign Comments Var (c3171c3)
* issue: List Item Properties On Mouseover (a6a7192)
* issue: def_assn_role (6ad568f)
* search: Child Thread Relation (08785f9)

### Security
* security: PwReset Username and Username Discoverability (e282910, 86165c2)
* security: SSRF External Images (1c6f98e)
* xss: Stored XSS/Domain Whitelist Bypass (4b4da5b)
* security: Recipient Injection via User's Name (7c5c584)
* xss: Advanced Search (4a8d3c8)
* xss: Tasks (b01c6a2)

osTicket v1.14.7
================
### Enhancements
* redactor: Upgrade to version 3.5.1 (2617f53)

### Improvements
* issue: Setup Admin Password Heltip Verbiage (7866a72)
* issue: getDBVersion() SQL Errors (43210e3)
* issue: Missing Thread On Referral Check (1359d91)
* readme: Update PHP Version (a1cf24f)
* typo: Default Sorting (89c322b)
* issue: Choices Field Sanitization (07526af)
* issue: "New Task Alert" email template typo (8178b4d)
* issue: Edit Entry Dropped Attachments (a9a64ed)
* issue: EmailTest Draft (27259e1)
* issue: Release Notes Links (e9a2155)
* Role: Handle null perms (4211952)
* Issue: Missing null check in Staff->updatePerms() (f9626f8)
* Banlist sorting by Updated (15ccc71)
* issue: ticket.dept.sla Variable sla_id (bf15d6f)

### Security
* xss: SVG Image (68dcaa2)
* security: open.php Refresh (b8603c7)
* xss: Client-Side i18n (fd560df)
* session: Verify UserAgent String (f71c954)

osTicket v1.14.6
================
### Enhancements
* redactor: Upgrade to version 3.4.9 (ab40f97)

### Improvements
* issue: markAs Popup Manager (No Access) (8d1d623)
* issue: Task last_update Var (08cd762)
* issue: SCP Login Redirect (9b12a54)
* issue: Client-Side Reply Draft Saving (996cd9e)

### Security
* xss: FormAction Attribute (8d956e0)
* xss: onerror Property (25e6d12)

osTicket v1.14.5
================
### Enhancements
* typo: Change User Confirmation Popup (79e6513)
* redactor: Upgrade to version 3.4.6 (5c77b0d)

### Improvements
* issue: MailFetch Inline Disposition (fbf0c7d)
* issue: Editor Spacing (a6cbc5c)
* Issue: Collaborator Adding New Collabs (a4ab6b6)
* mail: Reply to Ticket Owner Only (a4bb20a)
* dept: Dept Deletion Bug (7cba73d)
* issue: Task From Ticket (4b48456)

### Security
* security: Parent Ticket Access (Client) (5972fe8)

osTicket v1.15
==============
### Enhancements
* Change dept_id and priority_id fron tinyint to int (e54f6f3)
* csrf: Add ability to rotate token (36e614c)
* Feature: Agent/Department Visibility (5fbd762, e4346d2, 4ad7e95, 49b2f1b, 46033d1, 3a8ea4b, 6eae7e6, f306ce8, 6fdc111, 4489b2f, 7f0602a, 484023d, 3722fc5, 6425146, 9902ac2, 07b2373, ca81176, 4e86313)
* db: Latest Indexes (da2fd37, 2731074, c359d12, ea09373, 4c9968b)
* SLA Plan Search Field (0fd63b4)
* 2FA Backends (5dd0a34, 4ef752c, cff12f7, ea86103, 4b6bc73, a1b7826, 3f08e62, 9d46c84, 8f4fe18)
* Password Policy Revisited (e1aba7c, 744676b)

### Improvements
* Issue: Missing Events (38232f2)
* Issue: 2FA Upgrade (0065c3b)
* Ticket From Email (f02edd9)
* Issue: User Custom Dept Field (52825f0)
* Staff: Password Change (7527ea7)
* oops: Indexes Patch Schema (709b55f)
* CsvImporter: Skip Byte Order Mark (BOM) if present (bfd5da8)
* Oops: Method Inheritance Compatibility (cb13b82)
* issue: Form Instructions Translation (4f7d23c)

osTicket v1.14.4
================
### Enhancements
* forms: Pseudo-random name for Dynamicforms on POST (077d26f)
* Authcode: Ticket Access Link (043c3fe)
* redactor: Upgrade to version 3.4.5 (e593c5c, 9102240, e471132)
* Auth: Client Create Request (c3c01d3, 43e07c2)

### Improvements
* Issue: Event YAML (52c7211)
* issue: Missing Description On New Task (949acc6)
* issue: Draft Save (f2c5c5a)
* mpdf: Logo Overlap (5012ccc)
* Issue: Viewing Email Templates (817cdee)
* Issue: Topics on Install (bfaad5b)
* Partially revert commit 077d26f6d0bb15 (d554c2b)
* Issue: Prevent Deleting All Topics (8d2b8c6)
* Issue: Sub Queues (8e3a6c6)
* authtoken: Add ticket link when recipient is ticket owner (2be608c)
* redactor: Improper Formatting When Double Spacing (fe26123)
* forms: Add SECRET_SALT to field name hash (4eeb4b5, 133362d)
* issue: Better URL Parsing For External Inline Images (50eed90)
* Issue: Default Delimiter (f302503)
* issue: Update Autocomplete (d3245b1)
* Fix incorrrect compare locked staff at Ticket Preview (e8f0c58)
* Don't use a default comment on ticket assignment (433e62d)
* Issue: Ticket Export Delimiters (38dbe73)
* CsvImporter: Skip Byte Order Mark (BOM) if present (9e1dfef)
* Drop nested table from open new ticket (fb0164b)
* issue: New Message Alert Recipients (ea9cd56)
* issue: Custom REGEX Failure (4850b2a)
* Issue: Ticket From Thread Attachments (1de3f6a)
* cli: Manage.php Errors (239b9ba)
* issue: Assignee Field (3c89117)
* issue: Agent Password Reset With No Existing Password (101ebea)
* More modern, cleaner DocBlock (84195ec)
* issue: Ticket Merge Select2 (bffac98)

osTicket v1.14.3
================
### Enhancements
* select2: Update To 4.0.13 (b67c75b)
* jquery: Update To 3.5.1 (121ab41)
* redactor: Upgrade to version 3.4.2 (384fe27)
* Issue: Template Variables in Ticket Filter (8ef505d, 8a82d1e)
* issue: Get Team Members For Alerts (d88e384)
* Issue: Topic Help Tip (66fc808)
* Create SECURITY.md (165cf18, 0ecfceb)
* redactor: Upgrade to version 3.4.1 (8f08a09)
* inline: RichText Fields View First (d8ff946, a97ddba)
* print: Update Icons and Add Titles (be18e46)
* issue: Update Print Options Icons/Text/Title (b4cd46a)
* refactor: Help Topic Status Refresh (2dee16b)
* Adding translation to the dashboard plot labels. (ebfd68b)
* Issue: Language Verification (a1e9342)

### Improvements
* oops: Local Inline Images (f6cd8c4)
* Issue: Ticket Edit Save (3281e74)
* Revert Topic Saving Fixes (0ff87f3)
* issue: NOTLS For IMAP/POP Without SSL (7506937)
* Update dynamic-field-config.tmpl.php (e847ddb)
* Ticket Merge Translation Improvement (ba389a6)
* oops: Task Missing Parentheses (b7684ad)
* Issue: Create Task File Upload (87f5006)
* issue: Delete Users With Tickets (9d2e1da)
* DynamicField Update (c21452b)
* issue: Form Field Help Text Not Null (e295c52)
* export: Duplicate Results (b415baf)
* issue: Email Template Internal Notes (8d6b9aa)
* oops: Change lastupdate To updated For Tasks (03bedc5)
* i18n: Redactor Files Not Included (f91308a)
* Issue: Ticket Task Print (7b6ba94)
* Issue: Topic Fields on Ticket Edit (f79a28a)
* issue: Activity Notice getLastRespondent() (07024fc)
* Issue: Create Team With Members (6f50e91)
* i18n: Don't Store Files Under Branch Name (31dfc6e)
* template: Add Ticket ID To Var Scope (351f8ec)
* Issue: Topic and Department Columns (36778cc)
* sla: Force Intval For Scientific Floats (9ea2e4d)
* oops: JS Method Typo (58e559d)
* issue: Signature Box No Longer Expands (5d68847)
* install: Add Mark As Answered To All Access (0765571)
* print: Client Print Not Respecting Identity Masking (5db5a72)
* templates: %{ticket.thread.complete} Not Respecting Identity Masking (faec1a7)
* issue: Filter Action Add Button (adc46ae)
* install: Embedded Domain Whitelist (e0b5d81)
* install: Schedule, SLA, and Help Tip Updates (88dd0aa, e589c1b, 1860db4)
* Issue: Ticket Number Search (61443ef)
* issue: PHP 7.4 Warnings (1aafa42, d93379e, 90f5985)
* issue: Flush Model Cache (db5eb07)
* Issue: PHP Warning (4997780)
* issue: MySQL 8.0 {min,max} Value Error (bb54dea)
* issue: Mass Delete Help Topics Warning (52fd884)
* issue: Org Added Collabs (0ee25b8)
* issue: Attachment Upload Configuration (2540350)

### Performance and Security
* security: Reported Vulns July-August 2020 (fb57082, d2491c1, d98c2d0, 518de22)
* xss: FAQ Category On Errors (292e7dd)

osTicket v1.14.2
================
### Enhancements
* Task Inline Edit (ad04c05, 027c8d2, 7209b03, 2b8a6dc, 79b69aa, 49aba87, 1179d60, cc8d64e)
* feature: Configurable Agents As Collaborators (5f5403d, bdcaeea, 9426e67, 4ed30c5)
* Ticket Merge Modal Improvements (d31a0c7)
* redactor: Double Spacing Optional (fa418e6)
* inline: Set Help Topic Refresh Statuses (be4e01e)
* lint: updateEstDueDate (a8fe0bd)
* Ticket Merge Parent Status (bebc724)
* Schedule Entry - getOccurrences algo. (0184473)
* Queue Query Optimization (8c07c17)

### Improvements
* Issue: Merge Child Status (34af390)
* 1.14 Misc. Fixes (869d117)
* Issue: Filter Actions for Deleted Objects (b918e2d)
* ui: improve action buttons (b25c66b, a11f882)
* Misc. Fixes (e8acf81, f5d1664, aed5d6f, ac5f93e, 77577df, e397f03,
* bfa7b57, 3c0dd3d, 5955c3a, c2b99ab, c9cc1e2, 51b5839)
* Issue: Class AuditEntry Not Found (8023ba9)
* oopsie: Revert errant delete by commit 027c8d29 (f16cd79)
* lint: oopsie on undefined variable (4f19924)
* issue: Move Owner Check to Ticket Collabs (7d44262)
* oops: Update Schedule No Description (774d5f7)
* revert: Agent Added Agent Collabs (cc7deda)
* issue: AuditEntry Not Found (9e3ca8c)
* issue: Collab Pass By Reference (a3a1d45)
* Add AnnotatedField interface to  TicketTasksCountField (fbc1b11)
* Audit Plugin Modal Fix (a883f90)
* Issue: Duplicate Thread Entry Merge Records (ef6b7da)
* export: User/Org Tickets (0cd4168)
* i18n: Help Text Translate Button (cc2d0ab)
* install: Task Title/Description Field Flags (6727ebb)
* issue: Trailing Whitespaces Number Lookups (d8cb1e8)
* issue: Duplicate Tickets in Lookup (95f9b83)
* issue: BooleanField Inline Edit Value (23a463c)
* issue: BooleanField Cannot Be Unchecked (1556242)
* Issue: Managing Child Ticket Threads (7f85946)
* Oops: Set Child Ticket Status (c216a72)
* feature: Force HTTPS (1de9b4c)
* Issue: Close Child Ticket Without Help Topic (2fb81aa)
* Issue: Inline Edit Long Answer (608044d)
* Issue: Ticket Link/Merge (bb0f9bd)
* dept: Disable Auto Claim (3684879)
* redactor: Cancel Button (3bab103)
* issue: Redactor z-index (19cc9a0)
* Issue: Overwriting thisstaff on Assign (77065f5)
* Schedule Entry - Initial Occurrence Scope (3a41b09, 3da5bd7)
* issue: mPDF Print Tables (2bd464f)
* Clear Overdue Flag 4realz (057c817)
* Ticket Merge/Link Defaults (3f93cd9)
* User Audit Issue (97a55a4, 613b4ab)

### Performance and Security
* upgrade: Redactor 3.3.5 (4634a86)

osTicket v1.12.6
================
### Enhancements
* issue: Edit User Popup Perm (c73877d)
* format: Strip PUA (Plane 16) (caeda93)
* issue: New Task Alert (5dd123e, 3283050)
* lint: Minified JS Warnings (0443715)
* support: IE Discontinued (699728f)
* issue: Shared Mailbox Auth (7bb9fd8)
* plugins: Add Version Column (f86c93e, 5b0f1ce)
* Issue: User Imports Headers (787417f)
* Support message/rfc822 as attachments (af1c4a6)
* tooltip: Email Username (edd1fc3)
* Issue: Ticket Search Typeahead (9b9a56f)
* Issue: Ticket Open URL (f2e2403)
* Inline Edit Fields With Data Integrity (6015d04)

### Improvements
* issue: Show Custom Validation Message (ca6ad5c)
* SLA Grace Period (b373b8f)
* issue: DynamicForm i18n Instructions Decode (56d3d67)
* issue: Information Field Help Text Decode (abb9799)
* i18n: Help Text Formatting (6b8cc9b)
* issue: ThreadEntryField Help Text (6370484)
* issue: Department Parent (3f29845)
* Add support for sub-query based constraints. (c069def, 6579cf4, f61748c, 0eeec7e)
* Update class.filter.php (#5320) (f42f2baf)
* Allow external UserAuthenticationBackend … (ffb179f)
* Issue: New Agent Extended Access and Teams (d4b8b3a)
* issue: Mass Process Add Users To Organization (6cc7c69)
* i18n: TextareaField Placeholder (fa9df2b)
* issue: Confirm Popup Promise (b1f881b)
* Remove unnecessary PHP Notice in ORM (Fix #5432) (5dac549, 03e25a8)
* session: Destroy Warning PHP 7.3 (e6f0483)
* oops: is_numeric Soft Fail (7c9ed61)
* Oops: User Import Fix (40b40f8)
* validate: Validation Error Messages From Source (9e21dfd)
* issue: PasswordField Validation (9cc5cb6)
* issue: Multiple Choice Export (3005d42)
* lint: Uninitialized Matches (7873c5b)
* issue: PDF Global $ost (07878f5)
* Spelling correction function name (44cbc30)
* issue: Quotes In User Name (ea6fc44)
* issue: Add Remote Collaborator (c60e2f3)
* emoji: Strip From Subject (e3547ea, e24c78c)
* Issue: DB Error #1054 (18c9311)
* install: Forum and Docs Links (dddfede)
* queue: Inherit Columns Option (2e146ad)
* Issue: Help Topic Number Format (52c9c59)
* issue: User Manage Org Name (7a6b85c)
* issue: Require Client Login (5136198)
* Fix confusing sentence (4e7d12c)
* issue: Update Staff checkPassword() (0659338)
* validate: Number Field Edit Zero (e6e4e90)
* issue: New Agent Welcome Email (1949d4f)
* queue: Inherit Columns Option (38df2c8, 1a32e2a, 4434a93)
* Issue: Remove Referrals (2acf9aa)
* mail: Mail_Parse::getAttachments () (d310740)
* Issue: Blank Date Time (60ccbb7)
* Issue: Thread Events for File Field Changes (4d43adf)
* Issue: New Custom Fields (9591411)
* Oops: Variable Overwrite (c048768)
* session: Destroy Warning (8c69891)
* Issue: DB Error #1064 Queue Counts (f26ce60)
* notes: Confirm Deletion (0d86e7f)

### Performance and Security
* xss: All Reported Vulns (f705001, de41aeb, fc4c860, d54cca0, 6c724ea, 601fdcd)

osTicket v1.14.1
================
### Improvements
* Revert commit cedd6121 (7dd7bfa)
* Clear Overdue On Reopen (6dc0b74)
* Allow Repeatable Once Entries (cb9bb2f)

osTicket v1.12.5
================
### Performance and Security
* Hotfix: File data callback (d3e643d)

osTicket v1.14
==============
### Enhancements
* php: 5.6 Support (5e7497d, 5f3a8f4)
* Oops: Lint Fixes (c5b15d6)
* Update index.php (e7779e2)
* Overdue oopsie (de7271d)
* Ticket Task count (b4fca25)
* Visibility: Move getJsComparator to Widget class (d3f46bf)
* i18n spelling  oopsies (36b44f7)

### Improvements
* Require Between Date Entries (1aaee58)
* Add Ticket Reference to Tasks (af97900)
* Disabled SLA (2fe5370)
* Queues:  Agents with no team assignment (a00cee9)
* Ticket Merge Upgrade Patch (38fada0)
* ticket-view: Add ZIP export option (5e5b6b8)
* issue: Check $cfg iFrame (a6b8200)
* feature: Separate SMTP Credentials (edb8ac6, d2cb614, 9d4bcb5, 093984b,
* b6d13a2)
* feature: Fetch From Mail Folder (d70dfc1, 901d30a)
* feature: Configurable iFrame Whitelist (44200e5, 2330f47)
* Ticket Merge: Close Children (6fef208)
* Audit Log Plugin (9b80889, 8e3fd4d, bb3d092, 46a764f, 72974c8, 27cfd65,
* 69da645, 349c982, 7cfc062, a4bd53b, e0ca7e2, d942b16, 2aacbd1, ff90638,
* 40771ea, 30eeaf9, 2c350f3, f261283, 205b3ae, 5c91dc1, ac1e99c, 59e5d71,
* 203c716, 54a175a, 5ba9e89, 419a478)
* Issue: Organization Update (7b6bd90)
* issue: Redactor Reset Buttons (8078d4d)

osTicket v1.12.4
================
### Enhancements
* issue: Spaces In Username (7c8f557)
* i18n: KnowledgeBase JS (bce8296)
* i18n: KnowledgeBase Category (5646e7c)
* i18n: OpenSSL Error (af6f0e9)
* Message Variable - %{message} (315c4e7)
* Datatimepicker: Time format (f0fccbc)

### Improvements
* Highlight tab with error(s) (b81b703)
* format: Clickable URLs (4f7569d)
* Queue Pages Default (dff8bc1, 5105250)
* Add Time boundaries to Between date range (cbc89b3, 31c97cf)
* Clear Overdue Flag on Due Date Change (8c76d70)
* db: System Time Zone (76087fc, d8adf85)
* Modify Reopen Assignment (d50ebbb)
* issue: Format File Name (bd427cd)
* issue: DB Error #1062 (27c925c)
* Issue: Edit Task Fields (05cbb75)
* issue: In-Reply-To Header (8849c19)
* orm: Refetch Failure (eb4bda8)
* issue: Delete Org Session Failure (bbd0c25)
* Feature: Mark as Answered permission option (2fcc664, 52aaa0b)
* issue: Umlauts In Subject (cccdb15)
* issue: Umlauts In Sender's Name (e3f42c3)
* Fix use of possibly uninitialised $_SERVER['HTTPS'] (8e9b150)
* issue: ACL Oopsie (4d774bc)
* issue: Revert  fefed14 (c9be2e0)

### Performance and Security
* Arbitrary Method Invocation (4dfb77c)
* Auth: Authentication Token Bypass (a9834d8)
* mPDF: Remote Code Execution Vulnerability (6e039ab)
* issue: Attachment Filter (9f4fbc2)

osTicket v1.14-rc2
==================
### Enhancements
* Oops: Lint Fixes (e76c64e)
* Draft Saving in New Redactor (644da1b)
* Cache Children Tickets (a0a58e4)
* Make getChildrenTickets Static (42339c2)
* Lint Fixes (98f4b37, 3eabaa1)

### Improvements
* Issue: Task Drafts (3e8bce4)
* Lint Fixes (3eabaa1)
* Issue: Task Collaborator Display (d1790f1)
* Fix Link Sort (3a41a8a)
* Issue: Unlinking From Child Ticket (c76cb21)
* issue: Redactor QuickNotes (7251bcf)
* Delete Thread Merge Conflict (d4b6ab6)
* Custom Queue default sort selection (bbd2e80)
* issue: Staff/User Email Length (b969407)
* Queue Sort Options (d2611b5)
* Default for Choice Fields (d85ede8)
* Show list of nested help topics on edit (423c915)
* Upgrader Issue: Ticket Flags (e0298f2)
* issue: Ticket Filter Assignment Event (606993e)
* Don't Delete Child Threads (24b220d)

osTicket v1.12.3
================
### Enhancements
* Datetime Formats (4709824)
* issue: Revert 453e815 (ddde34b)
* Revert "issue: Advanced Search Default Sorting" (d4befcd)
* feature: Expanded Print View (b2bd45f)
* i18n: Register Include (9b18dd6)
* Lint Fix (68f11e1)
* Update osTicket Requirements (a6a18ee)
* Update osTicket Requirements (27f1578)
* issue: Update Installer PHP Requirements (15d678b)
* issue: Update Outdated Links (25bf88f)

### Improvements
* issue: Mbstring Extension Requirement (5a96884)
* Instantiate  StaffDeptAccess (390ec3e)
* issue: Complete Thread Var Padding (Outlook) (d96285f)
* Issue: Empty Due Date (30f3b55)
* issue: PHP 7.3 New Agent Set Password (1bcd0e2)
* European Date Format Issue (df7306f)
* issue: CSV Patch Adv. Search Error (6ea7526)
* Issue: Annul Closed Events (8029b1b)
* issue: Department Referral Email (26d2990)
* Date Range Period Timezone (0f06f85)
* Issue: Undefined Constant Warning (c2ca730)
* issue: Support Exchange Shared Mailbox Auth (ac9ea5b)
* Issue: Inline Ticket Assignment (b757ec4)
* issue: Dashboard No Help Error (ab0cdc6)
* Organization Update (1588344)
* issue: Advanced Search Default Sorting (dda483e)
* issue: Image Attachment View (eb1a4ea)
* issue: Reset Role Permissions (0c2cecb)
* issue: Error On QueueSort Config (1b1e742)
* Required Short Answer Field = '0' (c58916b)
* Fix Admin Alert (5f6bd42)
* issue: Set Staff Password On Creation (d9108b1)
* issue: THIS_VERSION Utilize MAJOR_VERSION (5b4c512)
* Issue: Reopen Assignment (e73e881)

osTicket v1.14-rc1
==================
### Major New Features
* Feature: Merge/Link Tickets (a8a4dec…c870df0)
* Introducing Schedules / Business Hours for SLAs (54e06e9…39771f8)
* Export Revisited (19ac222…045f6a6)

### Enhancements
* Ticket Merge Code Fixes (06faacb)
* Custom Priority Field Blank (f7ea1f6)
* Field Length Truncate (4d6de40)
* Formatting cleanup (d0de290)
* issue: Fix Patch Issue (8a8167e)
* Code Cleanup (6ff4491)
* Add thread_type Patch (9f9292f)
* Modify Draft Saving (e06fb46)
* Don't require refresh for inline edit (1071d10)
* ORM Parentheses Patch (fff2e29)
* Delete Threads/Entries of Deleted Tickets (a39c115)
* issue: Form Field Flags (94e770a)
* issue: User/Org Ticket Export (25153ed)
* Add thread_type Patch (15ed4b1)
* queue: Add Filtering To Queues (cedd612)
* issue: Default Queue Sorting (6db9507)
* Fix Saving For Fields: (c3eaec1)
* Issue: Queue with Teams in Criteria (d8b61e8)

osTicket v1.12.2
================
### Enhancements
* issue: v1.12 Git MAJOR_VERSION (3f80266)

### Improvements
* issue: README.md osTicket Logo (7121043)
* issue: README.md Image Size (8b90010)
* issue: DatetimeField Remove Unused Vars / Use parseDateTime() (d9aa91b)

osTicket v1.12.1
================
### Enhancements
* issue: Queue Sort Title No Validation Error (029b0f2)
* Issue: Tickets Visibility (60aa7b8)
* task: Implement edit of task thread (394ddee)
* Reformat Incorrect Reply-To Headers (e9dda94)
* DatetimeField: Add jquery-ui-timepicker-addon (dbff3b2)
* Add/Remove Collaborators Without Refresh (5a5044a)

### Improvements
* issue: API Unexpected Data Warnings (4f68eb9)
* Double semicolon removed (bacd836)
* Empty extra in list_items (1309a6c)
* Issue: Ticket Alerts vs Dept Recipients (581f1f9)
* issue: iFrame Single Quotes (4b59b4f)
* issue: PDF Squares Instead Of Text (69c5095)
* issue: Class Format Disposition Misspelling (1d3f1a3)

### Performance and Security
* Remove File Type Override (539d343)
* Validate integrity of uploads (eba6fb9)
* issue: Rogue Closing div Breaks HTML Thread Tree (3bb4c0a)
* xss: Install Form (c3ba5b7)
* security: CSV Formula Injection (9981848)
* security: HTML File Browser Execution (Windows: Firefox/IE) (33ed106)

osTicket v1.10.7
================
### Enhancements
* Lint Fixes (8c878db)
* cli: Package Better Wording (bf20bdd)

### Improvements
* queues: Fix compatibility issues with newer jQuery (c54372f)
* FAQ Issues (ce3d69a)
* cli: Package No File Permissions (25e6c6e)
* oops: .eml/.msg Missing Not Operator (ce8aadf)
* issue: Retained Deleted ListItem Errors (a3297a2)
* issue: Account Registration Throws Errors (a720507)
* issue: ISO-8859-8-i Charset Issues (4da0324)
* issue: Search Reindexing Thread Entries (bbf1010)
* issue: is_formula Dotall Mode (992e904)

### Performance and Security
* Remove File Type Override (539d343)
* Validate integrity of uploads (eba6fb9)
* issue: Rogue Closing div Breaks HTML Thread Tree (3bb4c0a)
* xss: Install Form (c3ba5b7)
* security: CSV Formula Injection (9981848)
* security: HTML File Browser Execution (Windows: Firefox/IE) (33ed106)

osTicket v1.12
==============
### Enhancements
* issue: Upgrader Wrong Guide Link (#4739)
* iframe: Allow Multiple iFrame Domains (#4781)
* variable: Complete Thread ASC or DESC (#4737)
* issue: Strip Emoticons (#4523)
* feature: ACL (Access Control List) (#4841)

### Improvements
* issue: Maxfilesize Comma Crash (#4340)
* issue: System Ban List (#4706)
* queues: Fix compatibility issues with newer jQuery (#4698)
* filedrop: Fix file drag and drop (#4719)
* issue: PHP 7.2 Plugin Delete (#4722)
* issue: Local Avatar Annotation (#4721)
* Selected Navigation Item (#4724)
* Issue: Attachments on Information Fields (#4730)
* issue: No Save Button On Quicknotes (#4706)
* Issue: Duplicate Tickets in Closed Queue (#4736)
* issue: APC CLI (#4731)
* users: Fix seaching of users (#4741)
* issue: Custom Column Org Link (#4755)
* issue: Internal Note Ignored (#4745)
* issue: PHP 7.2 Ticket Status (#4758)
* issue: Canned Response Variables (#4759)
* issue: FAQ Search Results (#4771)
* issue: FAQ Return Errors (#4772)
* Queue Columns (#4785)
* issue: Duplicate Form Titles (#4788)
* Issue: Exporting Tickets (#4790)
* issue: Organizations Users Sort (#4806)
* issue: Multilingual FAQ Category w/ Parent (#4812)
* issue: Task Print PDF (#4814)
* Issue: MPDF Export PHP < 7.0 (#4815)
* Quick Filter Fixes: (#4728)
* Assignment Restriction Issue (#4744)
* Issue: Saving Checkbox Values (#4798)
* Issue: Choosing Fields to Export (#4797)
* oops: Thread Variable Fatal Error (#4820)
* oops: Emojis Strip Korean (#4823)
* issue: iFrame On Install (#4824)
* Issue: Ticket Export Headers (#4796)
* issue: Organization Ticket Export No Filename (#4825)
* MPDF Issues (#4827)
* issue: sendAccessLink On NULL (#4828)
* issue: sendAccessLink On NULL v1.11 (#4829)
* Update README.md (eccc57a, e5f4180)
* issue: iFrame Single Quotes (#4844)
* issue: Choice Validation Accept Punctuation (#4847)
* issue: ACL Move To Inc Files (#4848)
* Issues since v1.11 release (#4850)
* PJAX: Increase default timeout (#4855)
* Mime Decode - Encoded char (#4851)
* MPDF Tasks (#4856)
* issue: .eml/.msg Attachments (#4857)
* issue: Task EstDueDate (#4862)
* Bug fixes and enhancements for v1.11 (#4863)
* Mailer: Allow for ability to pass -f option as from_address (#4864)
* Ticket Link: Always return a link (#4865)
* Minor Fixes (e628373)

### Performance and Security
* xss: XSS To LFI Vulnerability (#4869)
* jquery: Update Again (#4858)

osTicket v1.10.6
================
### Enhancements
* issue: Upgrader Wrong Guide Link (#4739)
* iframe: Allow Multiple iFrame Domains (#4781)
* issue: Strip Emoticons (#4523)

### Improvements
* issue: Maxfilesize Comma Crash (#4340)
* issue: No Save Button On Quicknotes (#4706)
* issue: PHP 7.2 Ticket Status (#4758)
* issue: Canned Response Variables (#4759)
* issue: FAQ Search Results (#4771)
* issue: FAQ Return Errors (#4772)
* issue: Duplicate Form Titles (#4788)
* issue: Organizations Users Sort (#4806)
* oops: Emojis Strip Korean (#4823)
* issue: iFrame On Install (#4824)
* issue: sendAccessLink On NULL (#4828)
* Update README.md (eccc57a, e5f4180)
* issue: iFrame Single Quotes (#4844)
* issue: .eml/.msg Attachments (#4857)

### Performance and Security
* xss: XSS To LFI Vulnerability (#4869)
* jquery: Update Again (#4858)

osTicket 1.11
=============
## Major New Features
* Release Ticket Assignment (d354e095)
* Require Help Topic To Close Ticket (#4400)
* Disable Collaborators On Reply (#4420)
* Complete Thread Variable (#4613)
* Public Mark As Answered/Unanswered (#4612)

###Enhancements
* Canned Response Select2 (#4311)
* filters: Move to the ORM (3c1bc3d9)
* oops: New sessions require non-null data (0d58a28a)
* issue: IE White Screen Of Death (#4346)
* Recipients Icon  View Email Recipients for Users (8c707b5d)
* Collaborator Fixes: (a4de3514)
* Collaborator Thread Event for Web Portal: (0b34753c)
* Collaborator Tickets Visibility Fix: (98dc5d9b)
* thread: getId On Non-Object (e3b333ed)
* Email Recipients Revisited Corrections (731c9fe9)
* Ticket-View Collaborator Collapsible Fix: (7be98ee7)
* mailer: EmailAddress Object as Array (#4368)
* collab: @localhost Mailer Error (#4380)
* SavedSearch Fixup (eadccc2a)
* Help Topic Inline Save Fix: (00a3be21)
* Email Name Format: (#4396, #4500)
* issue: Saved Searches Flags (#4395)
* queue: Improve queries necessary for rendering (#4342)
* status: Allow Reopen (#4411)
* Agent Default Queue: (#4412)
* Referral Assignment Issue: (#4414)
* Team Referral Check (#4415)
* Implement 'Select Active Collaborators': (#4420)
* Upgrader: Old search criteria (#4421)
* Advanced Search: TicketStatus / Status Name (#4423)
* Search All Tickets Setting (#4424)
* queues: Column Conditions Overwrite (#4445)
* queues: Row Conditions (#4444)
* issue: Multiple File Display (#4427)
* Staff Profile Updates: (#4462)
* Charset: Add generic transcode php_user_filter (#4469)
* issue: Image CID Attributes (#4477)
* Filter Action Saving Fix: (#4475)
* issue: Session form-data Files (#4482)
* issue: Default Help Topic Issue Summary (#4484)
* Implement Referral Internal Notes (#4486)
* issue: Existing User Registration (#4488)
* Adjust Filter Saving (49edbb3e)
* oops: Selection Search Bug (#4495)
* Filter Action Send an Email Issue (#4502)
* Optimize Upgrade: Remove ThreadEvent 'state' Enum (ebca2f9a)
* Creating Tickets with Attachments (47920c49)
* issue: Duplicate Personal Queues (#4503)
* issue: Newly Added Queues (#4504)
* Help Topic: Ignore invalid help topics (d93bb51b)
* footer: htmlchars company name (f6687f0f)
* Thread Entry: Chucked body (aff9bcb6)
* Filters: Cleanup filter actions on delete (6372b9c)
* orm: Add route to merge InstrumentedList (4a793a9)
* Search: Add duedate to base fields (b8bdd27)
* DateTimeFilter: Support empty value (95856cd)
* Export: Make Export Fast Again (#4479)
* DatetimeField: Format (8623ed60)
* Oops: Creating Tickets with Attachments (#4508)
* Column Annotation (2f7e3a01)
* Add NumericField (af829e82)
* Add Queue Columns Annotation as Fields. (aa0924a8)
* Retain Help Topics for Emails (#4512)
* export: Field Display (8adbd37d)
* Filter Action Validation Fixes (#4513)
* Advanced Search Column Conditions (#4514)
* Update Thread Events in Batches (bea99ae3)
* Update Fresh Install Process (fce25fbc)
* View All Tickets for User (#4528)
* Ticket Sources (#4534)
* Add period to DateTimeField (#4535)
* Form Attachment Issues (#4539)
* Attachment Names Issue (#4540)
* File Disposition (0c6e9acc)
* Primary Queues Buckets (#4538)
* Saving Changes to Filters (5f5951d2)
* Event Migration Optimization (#4561)
* Add new API headers to whitelist (#4563)
* upgrade: Actually re-fetch the config from database (#4564)
* issue: Export Event State Error (#4569)
* issue: Delete User Error (#4570)
* Dashboard Statistics Issue (#4574, #4585)
* issue: ticket_link Fatal Error (#4575)
* Queue Counts (#4572)
* issue: Queue sort_id (#4577)
* FileUploadField Validation (#4581)
* Status Column: Fix display and sorting (#4582)
* queue: Top-Level Ticket Counts (#4580)
* Deleting the Default Queue (#4576)
* issue: Email Default Dept (#4588)
* issue: create_date Variable (#4589)
* issue: FAQ/Page Attachments (#4595)
* Forms: Field Permissions (#4593)
* Field: Help Topic Forms (#4601)
* Task Collaborators (#4640)
* Issue: Edit Export Column Heading (#4649)
* Clarify User Import Instructions (#4651)
* Issue: Duplicate Search Results (#4630)
* Issue: DynamicFormEntry render (26ebcae0)
* Issue: Filters (#4655)
* issue: Task Response With Collaborators (#4661)
* issue: Scrollable Quickfilters (#4663)
* profile: Reply Redirect (#4656)
* Oops: Modify 0 in Short Answer Field Fix (#4670)
* Issue: Mass Assign (#4671)
* issue: SubQueues Hide PersonalQueues (#4682)
* issue: New Ticket Field Permissions (#4683)
* issue: Remove Referral Borked (#4684)
* Fix crashes compiling language packs on PHP 7 (#4688)

###Performance and Security
* PHP v5.6-v7.2 Support (#4680)
* Latest jQuery Upgrade (#4672)
* Update To Latest mPDF (460b445)

osTicket 1.11.0-rc.1
====================
## Major New Features
* Create Ticket or Task from Thread Entry
* Custom Columns/Custom Queues
* Inline Edit
* Ticket Referral
* CC/BCC
* Export Agent CSV
* Department Access CSV
* Archive Help Topics/Departments
* Nested Knowledgebase Categories

### Enhancements
* Fix Custom Department Field (#3976)
* Remove Future Search/Filter Criteria if Invalid
* Dashboard Statistics
* Fix Vimeo iFrames
* Fix randNumber()
* Section Break Hint
* List & Choice Searching (#3703, #3493, #2625)
* Adds osTicket Favicons (#4112)
* Fix Most Redactor Issues (#3849)
* Send Login Errors Still Sends (#4073)
* Private FAQs In Sidebar Search
* User Password Reset (#4030)
* Disabled & Private Help Topic (#3538)
* Helpdesk Status Help Tip
* Local Names In Validation Errors
* User Registration Form (#4043)
* Organization User List Pages Link (#4116)
* Ticket Edit Internal Note (#4028)
* Disable Canned Responses On New Ticket (#3971)
* Canned Response Margin
* Ticket Preview Custom Fields
* Help Topic SLA (#3979)
* Fix Agent Identity Masking (#2955, #3524)
* Force Keys For Choice Field Options (#4071)
* Check Missing Required Fields
* Task Action Button Styling
* Add Fullscreen To Embedded Videos
* Fix Serbian Flag Icon (#3952)
* Optimize Lock Table
* Fix Outdated Alerts Link (#3935)
* Fix Default Dept. Private Error (#3934)
* Mailto TLD Length (#4063)
* Remove Primary Contacts (#3903)
* Fix Reset Button(s) (#3670)
* Newsletter Link
* Offline Page Images (#3869)
* User Login Page Translation (#3860)
* Translate Special Characters (#3842)
* Custom Form Deletion (#3542, #4059)
* Client Side Long FAQ Title (#3380)
* Client FAQ Last Updated Time (#3475)
* Email Banlist Sorting (#3452)
* Fix New Ticket Cancel Button (#2624, #2881)
* SQL Error Unknown column 'relevance' (#2655)
* Fixes issue with last_update ticket variable
* Ticket Notice Alert
* Fix CSRF fail + shake effect (#3928, #3546)
* Issue/ticket preview collabs
* Allowing translation of copyrights in footers
* User/Organization are not translated (#3650)
* Fix DatePicker on client side (#3625, #3817, #3804, 0fbc09a)
* Add Custom Forms to Ticket Filter Data
* Fix for LDAP/AD auth plugin (#4198, #3460, #3544, #3549)

osTicket v1.10.5
================
### Enhancements
* issue: Translation Flags Not Clickable (#4687)
* issue: Hide Task Loading Overlay (#4660)
* Issue: Tasks Within Tickets (#4653)
* issue: Dashboard Export Period (#4650)
* Improve the Staff login (#4629)
* oops: Remove DST From User Update (#4599)
* issue: Dupe Page Requests Fix (#4568)
* change old wiki urls to new doc urls (#4517)
* oops: Class GenericAttachment Not Found (#4481)
* issue: Duplicate Page Requests (#4472)
* forms: Render Instructions (#4494)
* accessibility: Screen Readable Actions (#4490)
* forms: Disabled By Help Topic Users (#4476)
* issue: CDATA Phone Contains (#4471)
* Tasks: Task visibility (#4467)
* issue: mPDF SetAutoFont RTL (#4466)
* issue: SelectionField nset (#4465)
* cron: Clean Expired Passwd Resets (#4451)
* sessions: Clear On Password Set/Reset (#4450)
* Make getFiles() return files (#4410)
* issue: mPDF Arabic Fonts (#4455)
* issue: Task Term Search (#4453)
* Relative Time Fixes: (#4452)
* perms: Alphabetize Role/Staff Permissions (#4439)
* issue: Ticket Filter Does Not Match Regex (#4443)
* Task Assigned Team Issue: (#4437)
* issue: FAQ & Canned Attachments Dropping (#4428)
* issue: Wrong Attachment Names (#4426, #4425)
* issue: Featured FAQs On Disable (#4416)
* issue: Deleted Field Thread Events (#4394)
* issue: Custom File Upload Dropping (#4406)
* issue: Priority Field Template Variable (#4390)
* issue: Client Side Thread Entries (#4383)
* Web Portal Fixes: (#4369)
* issue: jQuery Sortable Redactor (#4381)
* issue: CLI Deploy Missing Bootstrap Fix (#4363)
* issue: Client Side Column Sorting (#4362)
* issue: ChoiceField Template Variable (#4359)
* issue: TextThreadEntryBody Sanitize (#4355)
* issue: Installer Footer Copyright (#4351)

### Performance and Security
* Latest jQuery Upgrade (#4672)

osTicket v1.10.4
================
### Enhancements
* issue: Auto-Assignment Log (#4316)
* issue: Language Pack Locale Mismatch (#4326)
* issue: CLI Deploy Missing Bootstrap (#4332)
* issue: User Import No Email (#4330)
* issue: Ticket Lock On Disable (#4335)

### Performance and Security
* security: Fix Multiple XSS Vulnerabilities (#4331)
* department: Error Feedback (#4331)

osTicket v1.10.3
================
### Enhancements
* issue: Org. User Account Status (#4219)
* upgrader: Flush Cache On Upgrade (#4227)
* issue: Outlook _MailEndCompose (#4206)
* issue: Files - deleteOrphans() (#4253)
* issue: Fix imap_open Disable Authenticator (#4195)
* Check permissions before displaying Close Task (#4177)

### Performance and Security
* issue: Information Page Performance (#4275)
* issue: Prevent Click Jacking (#4266)
* orm: queryset: Fix circular reference error (#4247)


osTicket v1.10.2
================
### Performance and Security
* Prevent Account Takeover (be0133b)
* Prevent Agent Directory XSS (36651b9)
* Httponly Cookies (5b2dfce)
* File Upload Bypass (3eb1614)
* Only allow image attachments to be opened in the browser window (4c79ff8)
* Fix randNumber() (5b8b95a)
* CSRF in users.inc.php URL (285a292)
* AJAX Reflected XSS (e919d8a)

osTicket v1.10.1
================
### Enhancements
* Users: Support search by phone number
* i18n: Fix getPrimaryLanguage() on non-object (#3799)
* Add TimezoneField (#3786)
* Chunk long text body (#3757, 7b68c994)
* Spyc: convert hex strings to INTs under PHP 7 (#3621)
* forms: Proper Field Deletion
* Move orphaned tasks on department deletion to the default department (42e2c55a)
* List: Save List Item Abbreviation (8513f137)

### Performance and Security
* XSS: Encode html entities of advanced search title (#3919)
* XSS: Encode html entities of cached form data (#3960, bcd58e8)
* ORM: Addresses an SQL injection vulnerability in ORM lookup function (#3959, 1eaa6910)


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
