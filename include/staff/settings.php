<?php include "./include/header.php" ?>
<h2>System Preferences and Settings  (v1.6 ST)</h2>

<form action="settings.php" method="post">
<br>
<a href="#" class="expand_all">Expand All</a> |
<a href="#" class="collapse_all">Collapse All</a>
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><a href="#"><span>&ndash;</span>&nbsp;General Settings</a></h4>
                <em>Offline mode will disable client interface and only allow super admins to login to Staff Control Panel</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="220" class="required">
                Helpdesk Status:
            </td>
            <td>
                <input type="radio" name="isonline" value="1" checked="checked"><strong>Online</strong> (Active)
                <input type="radio" name="isonline" value="0"><strong>Offline</strong> (Disabled)
                &nbsp;<span class="warn">&nbsp;</span>
            </td>
        </tr>
        <tr>
            <td width="220" class="required">
                Helpdesk URL:
            </td>
            <td>
                <input type="text" size="40" name="helpdesk_url" value="http://helpdesk.enhancesoft.com/">
                &nbsp;<span class="error">&nbsp;</span>
            </td>
        </tr>
        <tr>
            <td width="220">
                Helpdesk Name/Title:
            </td>
            <td>
                <input type="text" size="40" name="helpdesk_title" value="Enhancesoft :: Support Ticket System">
            </td>
        </tr>
        <tr>
            <td width="220" class="required">
                Default E-Mail Templates:
            </td>
            <td>
                <select name="default_template_id">
                    <option value="0">Select Default Template</option>
                    <option value="1">osTicket Default Template</option>
                    <option value="3" selected="selected">No Links</option>
                </select>
                &nbsp;<span class="error">&nbsp;</span>
            </td>
        </tr>
        <tr>
            <td width="220" class="required">
                Default Department:
            </td>
            <td>
                <select name="default_dept_id">
                    <option value="0">Select Default Dept</option>
                    <option value="1" selected="selected">Support Dept</option>
                    <option value="2">Billing Dept</option>
                    <option value="4">Test Dept</option>
                </select>
                &nbsp;<span class="error">&nbsp;</span>
            </td>
        </tr>
        <tr>
            <td width="220">
                Default Page Size:
            </td>
            <td>
                <select name="max_page_size">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                    <option value="25" selected="selected">25</option>
                    <option value="30">30</option>
                    <option value="35">35</option>
                    <option value="40">40</option>
                    <option value="45">45</option>
                    <option value="50">50</option>
                </select>
            </td>
        </tr>
        <tr>
            <td width="220">
                Default Log Level:
            </td>
            <td>
                <select name="log_level">
                    <option value="0">None (Disable Logger)</option>
                    <option value="3">DEBUG</option>
                    <option value="2" selected="selected">WARN</option>
                    <option value="1">ERROR</option>
                </select>
            </td>
        </tr>
        <tr>
            <td width="220">
                Purge Logs:
            </td>
            <td>
                <select name="log_graceperiod">
                    <option value="0" selected>Never Purge Logs</option>
                    <option value="1">After 1 Month</option>
                    <option value="2">After 2 Months</option>
                    <option value="3">After 3 Months</option>
                    <option value="4">After 4 Months</option>
                    <option value="5">After 5 Months</option>
                    <option value="6">After 6 Months</option>
                    <option value="7">After 7 Months</option>
                    <option value="8">After 8 Months</option>
                    <option value="9">After 9 Months</option>
                    <option value="10">After 10 Months</option>
                    <option value="11">After 11 Months</option>
                    <option value="12">After 12 Months</option>
                </select>
            </td>
        </tr>
        <tr>
            <td width="220">
                Excessive Staff Logins:
            </td>
            <td>
                <select name="staff_max_logins">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4" selected="selected">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                    <option value="9">9</option>
                    <option value="10">10</option>
                </select> failed login attempt(s) allowed before a
                <select name="staff_login_timeout">
                    <option value="1">1</option>
                    <option value="2" selected="selected">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                    <option value="9">9</option>
                    <option value="10">10</option>
                </select> minute lock-out is enforced.
            </td>
        </tr>
        <tr>
            <td width="220">
                Staff Session Timeout:
            </td>
            <td>
                <input type="text" name="staff_session_timeout" size="4" value="0">
                &nbsp;Maximum idle time in minutes before a staff member must log in again (enter 0 to disable).
            </td>
        </tr>
        <tr>
            <td width="220">
                Staff Session IP Binding:
            </td>
            <td>
                <input type="checkbox" name="staff_ip_binding" checked="checked" value="1">
                <em>(binds staff session to originating IP address upon login)</em>
            </td>
        </tr>
        <tr>
            <td width="220">
                Excessive Client Logins:
            </td>
            <td>
                <select name="client_max_logins">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4" selected="selected">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                    <option value="9">9</option>
                    <option value="10">10</option>
                </select> failed login attempt(s) allowed before a
                <select name="client_login_timeout">
                    <option value="1">1</option>
                    <option value="2" selected="selected">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                    <option value="9">9</option>
                    <option value="10">10</option>
                </select> minute lock-out is enforced.
            </td>
        </tr>
        <tr>
            <td width="220">
                Client Session Timeout:
            </td>
            <td>
                <input type="text" name="client_session_timeout" size="4" value="0">
                &nbsp;Maximum idle time in minutes before a client must log in again (enter 0 to disable).
            </td>
        </tr>
        <tr>
            <td width="220">
                Clickable URLs:
            </td>
            <td>
                <input type="checkbox" name="clickable_urls" checked="checked" value="1">
                <em>(converts URLs in messages to clickable links)</em>
            </td>
        </tr>
        <tr>
            <td width="220">
                Enable Auto-cron:
            </td>
            <td>
                <input type="checkbox" name="enable_auto_cron" value="1">
                <em>(executes cron jobs based on staff activity - not recommended)</em>
            </td>
        </tr>
    </tbody>
</table>

<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><a href="#"><span>&ndash;</span>&nbsp;Date and Time Settings</a></h4>
                <em>Please refer to <a href="http://php.net/date" target="_blank">PHP Manual</a> for supported parameters.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="220" class="required">
                Time Format:
            </td>
            <td>
                <input type="text" name="time_format" value="h:i A">
                &nbsp;<span class="error">&nbsp;</span>
                <em> 09:24 AM</em>
            </td>
        </tr>
        <tr>
            <td width="220" class="required">
                Date Format:
            </td>
            <td>
                <input type="text" name="date_format" value="m/d/Y">
                &nbsp;<span class="error">&nbsp;</span>
                <em>05/06/2011</em>
            </td>
        </tr>
        <tr>
            <td width="220" class="required">
                Date &amp; Time Format:
            </td>
            <td>
                <input type="text" name="datetime_format" value="m/d/Y g:i a">
                &nbsp;<span class="error">&nbsp;</span>
                <em>05/06/2011 9:24 am</em>
            </td>
        </tr>
        <tr>
            <td width="220" class="required">
                Day, Date &amp; Time Format:
            </td>
            <td>
                <input type="text" name="daydatetime_format" value="D, M j Y g:ia">
                &nbsp;<span class="error">*&nbsp;</span>
                <em>Fri, May 6 2011 9:24am</em>
            </td>
        </tr>
        <tr>
            <td width="220">
                Default Timezone:
            </td>
            <td>
                <select name="timezone_offset">
                    <option value="0">Server Time (GMT 0:00)</option>                        <option value="-12.0">GMT -12.0 (Eniwetok, Kwajalein)</option>
                    <option value="-11.0">GMT -11.0 (Midway Island, Samoa)</option>
                    <option value="-10.0">GMT -10.0 (Hawaii)</option>
                    <option value="-9.0">GMT -9.0 (Alaska)</option>
                    <option value="-8.0">GMT -8.0 (Pacific Time (US & Canada))</option>
                    <option value="-7.0">GMT -7.0 (Mountain Time (US & Canada))</option>
                    <option value="-6.0">GMT -6.0 (Central Time (US & Canada), Mexico City)</option>
                    <option value="-5.0" selected="selected">GMT -5.0 (Eastern Time (US & Canada), Bogota, Lima)</option>
                    <option value="-4.0">GMT -4.0 (Atlantic Time (Canada), Caracas, La Paz)</option>
                    <option value="-3.5">GMT -3.5 (Newfoundland)</option>
                    <option value="-3.0">GMT -3.0 (Brazil, Buenos Aires, Georgetown)</option>
                    <option value="-2.0">GMT -2.0 (Mid-Atlantic)</option>
                    <option value="-1.0">GMT -1.0 (Azores, Cape Verde Islands)</option>
                    <option value="0.0">GMT 0.0 (Western Europe Time, London, Lisbon, Casablanca)</option>
                    <option value="1.0">GMT 1.0 (Brussels, Copenhagen, Madrid, Paris)</option>
                    <option value="2.0">GMT 2.0 (Kaliningrad, South Africa)</option>
                    <option value="3.0">GMT 3.0 (Baghdad, Riyadh, Moscow, St. Petersburg)</option>
                    <option value="3.5">GMT 3.5 (Tehran)</option>
                    <option value="4.0">GMT 4.0 (Abu Dhabi, Muscat, Baku, Tbilisi)</option>
                    <option value="4.5">GMT 4.5 (Kabul)</option>
                    <option value="5.0">GMT 5.0 (Ekaterinburg, Islamabad, Karachi, Tashkent)</option>
                    <option value="5.5">GMT 5.5 (Bombay, Calcutta, Madras, New Delhi)</option>
                    <option value="6.0">GMT 6.0 (Almaty, Dhaka, Colombo)</option>
                    <option value="7.0">GMT 7.0 (Bangkok, Hanoi, Jakarta)</option>
                    <option value="8.0">GMT 8.0 (Beijing, Perth, Singapore, Hong Kong)</option>
                    <option value="9.0">GMT 9.0 (Tokyo, Seoul, Osaka, Sapporo, Yakutsk)</option>
                    <option value="9.5">GMT 9.5 (Adelaide, Darwin)</option>
                    <option value="10.0">GMT 10.0 (Eastern Australia, Guam, Vladivostok)</option>
                    <option value="11.0">GMT 11.0 (Magadan, Solomon Islands, New Caledonia)</option>
                    <option value="12.0">GMT 12.0 (Auckland, Wellington, Fiji, Kamchatka)</option>
                </select>
            </td>
        </tr>
        <tr>
            <td width="220">
                Daylight Savings
            </td>
            <td>
                <input type="checkbox" name="daylight_savings" value="1">
                <em>observe daylight savings time</em>
            </td>
        </tr>
    </tbody>
</table>
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><a href="#"><span>&ndash;</span>&nbsp;Ticket Options and Settings</a></h4>
                <em>If enabled ticket lock get auto-renewed on form activity.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="220">
                Ticket IDs:
            </td>
            <td>
                <input type="radio" name="random_ticket_ids" value="0"> Sequential
                <input type="radio" name="random_ticket_ids" value="1" checked="checked">Random  (recommended)
            </td>
        </tr>
        <tr>
            <td width="220" class="multi-line">
                Ticket Priority:
            </td>
            <td>
                <select name="default_priority_id">
                    <option value="1">Low</option>
                    <option value="2" selected="selected">Normal</option>
                    <option value="3">High</option>
                    <option value="4">Emergency</option>
                </select> &nbsp;Default Priority<br>
                <input type="checkbox" name="allow_priority_change" >
                Allow user to overwrite/set priority (new web tickets)<br>

                <input type="checkbox" name="use_email_priority"  >
                Use email priority when available (new emailed tickets)
            </td>
        </tr>
        <tr>
            <td width="220">
                Maximum <strong>Open</strong> Tickets:
            </td>
            <td>
                <input type="text" name="max_open_tickets" size="4" value="0">
                per email <em>(helps with spam and flood control - enter 0 for unlimited)</em>
            </td>
        </tr>
        <tr>
            <td width="220">
                Ticket Auto-lock Time:
            </td>
            <td>
                <input type="text" name="autolock_minutes" size="4" value="3">
                <em>(minutes to lock a ticket on activity - enter 0 to disable locking)</em>
            </td>
        </tr>
        <tr>
            <td width="220">
                Ticket Grace Period:
            </td>
            <td>
                <input type="text" name="overdue_grace_period" size=4 value="0">
                <em>(hours before ticket is marked overdue - enter 0 to disable aging)</em>
            </td>
        </tr>
        <tr>
            <td width="220">
                Reopened Tickets:
            </td>
            <td>
                <input type="checkbox" name="auto_assign_reopened_tickets" checked="checked">
                Auto-assign reopened tickets to last available respondent. <em>(3 months limit)</em>
            </td>
        </tr>
        <tr>
            <td width="220">
                Assigned Tickets:
            </td>
            <td>
                <input type="checkbox" name="show_assigned_tickets">
                Show assigned tickets on open queue.
            </td>
        </tr>
        <tr>
            <td width="220">
                Answered Tickets:
            </td>
            <td>
                <input type="checkbox" name="show_nswered_tickets">
                Show answered tickets on open queue.
            </td>
        </tr>
        <tr>
            <td width="220">
                Ticket Activity Log:
            </td>
            <td>
                <input type="checkbox" name="log_ticket_activity">
                Log ticket activity as an internal note.
            </td>
        </tr>
        <tr>
            <td width="220">
                Staff Identity Masking:
            </td>
            <td>
                <input type="checkbox" name="hide_staff_name">
                Hide staff's name on responses.
            </td>
        </tr>
        <tr>
            <td width="220">
                Human Verification:
            </td>
            <td>
                <input type="checkbox" name="enable_captcha">
                Enable CAPTCHA on new web tickets.
                <em>(requires GDLib)</em>
            </td>
        </tr>
    </tbody>
</table>
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><a href="#"><span>&ndash;</span>&nbsp;E-mail Settings</a></h4>
                <em>Note that global settings can be disabled at dept/e-mail level.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="220" class="required multi-line">
                Incoming Email:
                <br><em>For mail fetcher (POP/IMAP) to work you must set a cron job or enable auto-cron</em>
            </td>
            <td>
                <input type="checkbox" name="enable_mail_fetch" value="1" checked="checked"> Enable POP/IMAP email fetch
                &nbsp;<em>(Global setting which can be disabled at email level)</em><br>

                <input type="checkbox" name="enable_email_piping" value="1" checked="checked"> Enable email piping
                &nbsp;<em>(You pipe we accept policy)</em><br>

                <input type="checkbox" name="strip_quoted_reply" checked="checked">
                Strip quoted reply <em>(depends on the tag below)</em><br><br>

                Reply Separator Tag:
                <input type="text" name="reply_separator" value="-- do not edit --">
                &nbsp;<span class="error">&nbsp;</span>
            </td>
        </tr>
        <tr>
            <td width="220" class="required multi-line">
                Outgoing Email:
                <br><em><strong>Default Email:</strong> Only applies to outgoing emails with no SMTP settings.</em><br/>

            </td>
            <td>
                <select name="default_smtp_id" onChange="document.getElementById('overwrite').style.display=(this.options[this.selectedIndex].value>0)?'block':'none';">
                    <option value="0">Select One</option>
                    <option value="0">None: Use PHP mail function</option>
                    <option value="1" selected="selected">osTicket Support &lt;support@osticket.com&gt; (smtp.gmail.com)</option>
                </select>

                <span id="overwrite" style="display:display">
                <br><input type="checkbox" name="spoof_default_smtp" >
                    Allow spoofing (No Overwrite).
                </span>
            </td>
        </tr>
        <tr>
            <td width="220" class="required">
                Default System E-Mail:
            </td>
            <td>
                <select name="default_email_id">
                    <option value="0">Select One</option>
                    <option value="1" selected="selected">osTicket Support &lt;support@osticket.com&gt;</option>
                    <option value="2">osTicket Alerts &lt;alerts@osticket.com&gt;</option>
                    <option value="3">noreply@osticket.com</option>
                    <option value="5">lvcta.com (Test) &lt;support@lvcta.com&gt;</option>
                </select>
            </td>
        </tr>
        <tr>
            <td width="220" class="required">
                Default Alert E-Mail:
            </td>
            <td>
                <select name="alert_email_id">
                    <option value="0">Select One</option>
                    <option value="1">osTicket Support &lt;support@osticket.com&gt;</option>
                    <option value="2" selected="selected">osTicket Alerts &lt;alerts@osticket.com&gt;</option>
                    <option value="3">noreply@osticket.com</option>
                    <option value="5">lvcta.com (Test) &lt;support@lvcta.com&gt;</option>
                </select>
            </td>
        </tr>
        <tr>
            <td width="220" class="required">
                System Admin E-mail Address:
            </td>
            <td>
                <input type="text" size="25" name="admin_email" value="peter@osticket.com">
                &nbsp;<span class="error">&nbsp;</span>
            </td>
        </tr>
    </tbody>
</table>
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><a href="#"><span>&ndash;</span>&nbsp;Autoresponders (Global Setting)</a></h4>
                <em>This is global setting which can be disabled at department level.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="220" class="multi-line">
                New Ticket:
            </td>
            <td>
                <em>Autoresponse includes the ticket ID required to check status of the ticket</em><br>
                <input type="radio" name="ticket_autoresponder"  value="1">Enable
                <input type="radio" name="ticket_autoresponder"  value="0" checked="checked">Disable
                <br><br>
            </td>
        </tr>
        <tr>
            <td width="220" class="multi-line">
                New Ticket by Staff:
            </td>
            <td>
                <em>Notice sent when staff creates a ticket on behalf of the user (Staff can disable)</em><br>
                <input type="radio" name="ticket_notice_active" value="1" checked="checked">Enable
                <input type="radio" name="ticket_notice_active" value="0">Disable
                <br><br>
            </td>
        </tr>
        <tr>
            <td width="220" class="multi-line">
                New Message:
            </td>
            <td>
                <em>Message appended to an existing ticket confirmation</em><br>
                <input type="radio" name="message_autoresponder" value="1">Enable
                <input type="radio" name="message_autoresponder" value="0" checked="checked">Disable
                <br><br>
            </td>
        </tr>
        <tr>
            <td width="220" class="multi-line">
                Ticket Denied:
            </td>
            <td>
                <em>Ticket denied notice sent <strong>only once</strong> on limit violation to the user.</em><br>
                <input type="radio" name="overlimit_notice_active"  value="1">Enable
                <input type="radio" name="overlimit_notice_active"  value="0" checked="checked">Disable
                <em><strong>Note:</strong> Admin gets alerts on ALL denials by default.</em>
                <br><br>
            </td>
        </tr>
    </tbody>
</table>
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><a href="#"><span>&ndash;</span>&nbsp;Alerts and Notices</a></h4>
                <em>Notices sent to user use 'No Reply Email' whereas alerts to staff use 'Alert Email' set above as FROM address respectively.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="220" class="multi-line">
                New Ticket Alert:
            </td>
            <td>
                <input type="radio" name="ticket_alert_active" value="1" checked="checked">Enable
                <input type="radio" name="ticket_alert_active" value="0">Disable
                <br>
                <strong>Select recipients:</strong>&nbsp;
                <input type="checkbox" name="ticket_alert_admin" checked="checked"> Admin Email
                <input type="checkbox" name="ticket_alert_dept_manager"> Department Manager
                <input type="checkbox" name="ticket_alert_dept_members"> Department Members (spammy)
            </td>
        </tr>
        <tr>
            <td width="220" class="multi-line">
                New Message Alert:
            </td>
            <td>
                <input type="radio" name="message_alert_active" value="1" checked="checked">Enable
                <input type="radio" name="message_alert_active" value="0">Disable
                <br>
                <strong>Select recipients:</strong>&nbsp;
                <input type="checkbox" name="message_alert_laststaff" checked="checked"> Last Respondent
                <input type="checkbox" name="message_alert_assigned" checked="checked"> Assigned Staff
                <input type="checkbox" name="message_alert_dept_manager"> Department Manager (spammy)
            </td>
        </tr>
        <tr>
            <td width="220">
                New Internal Note Alert:
            </td>
            <td>
                <input type="radio" name="note_alert_active" value="1" checked="checked">Enable
                <input type="radio" name="note_alert_active" value="0">Disable
                <br>
                <strong>Select recipients:</strong>&nbsp;
                <input type="checkbox" name="note_alert_laststaff" checked="checked"> Last Respondent
                <input type="checkbox" name="note_alert_assigned" checked="checked"> Assigned Staff
                <input type="checkbox" name="note_alert_dept_manager"> Department Manager (spammy)
            </td>
        </tr>
        <tr>
            <td width="220" class="multi-line">
                Overdue Ticket Alert:
            </td>
            <td>
                <input type="radio" name="overdue_alert_active" value="1" checked="checked">Enable
                <input type="radio" name="overdue_alert_active"  value="0">Disable
                <br>
                <strong>Select recipients:</strong>
                <input type="checkbox" name="overdue_alert_assigned" checked="checked"> Assigned Staff
                <input type="checkbox" name="overdue_alert_dept_manager" checked="checked"> Department Manager
                <input type="checkbox" name="overdue_alert_dept_members"> Department Members (spammy)
                <br><em><strong>Note:</strong> Admin gets all overdue alerts by default.</em>
            </td>
        </tr>
        <tr>
            <td width="220" class="multi-line">
                System Errors:
            </td>
            <td>
                <input type="checkbox" name="send_sys_errors" checked="checked" disabled="disabled">System Errors
                <input type="checkbox" name="send_sql_errors" checked="checked">SQL errors
                <input type="checkbox" name="send_login_errors" checked="checked">Excessive Login attempts
                <br><em>Enabled errors are sent to admin email set above</em>
            </td>
        </tr>
    </tbody>
</table>
<p class="centered">
    <input class="btn_sm" type="submit" name="submit" value="Save Changes">
    <input class="btn_sm" type="reset" name="reset" value="Reset Changes">
</p>
</form>

<script type="text/javascript">
    jQuery(function($) {
        $('.expand_all').click(function(e) {
            e.preventDefault();
            $('.settings_table tbody').each(function() {
                $(this).slideDown();
            })
            $('.settings_table h4 span').each(function() {
                $(this).html('&ndash;');
            })
        })
        $('.collapse_all').click(function(e) {
            e.preventDefault();
            $('.settings_table tbody').each(function() {
                $(this).slideUp();
            })
            $('.settings_table h4 span').each(function() {
                $(this).text('+');
            })
        })
        $('.settings_table h4 a').click(function(e) {
            e.preventDefault();
            var parent_elem = $(this).parent().parent().parent().parent().parent();
            $('tbody', parent_elem).slideToggle();
            if($('th span', parent_elem).text() == '+') {
                $('th span', parent_elem).html('&ndash;')
            } else {
                $('th span', parent_elem).text('+')
            }
        })
    });
</script>

<?php include "./include/footer.php" ?>
