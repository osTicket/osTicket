<form action="settings.php?t=alerts" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="alerts" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4>Alerts and Notices Setting</h4>
                <em>Alerts sent to staff on ticket "events". Staff assignment takes precedence over team assignment.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="160">New Ticket Alert:</td>
            <td>
                <input type="radio" name="ticket_alert_active"  value="1"   <?php echo $config['ticket_alert_active']?'checked':''; ?> />Enable
                <input type="radio" name="ticket_alert_active"  value="0"   <?php echo !$config['ticket_alert_active']?'checked':''; ?> />Disable
                &nbsp;&nbsp;<em>Alert sent out on new tickets <font class="error">&nbsp;<?php echo $errors['ticket_alert_active']; ?></font></em><br>
                <strong>Recipients</strong>:&nbsp;
                <input type="checkbox" name="ticket_alert_admin" <?php echo $config['ticket_alert_admin']?'checked':''; ?>> Admin Email
                <input type="checkbox" name="ticket_alert_dept_manager" <?php echo $config['ticket_alert_dept_manager']?'checked':''; ?>> Department Manager
                <input type="checkbox" name="ticket_alert_dept_members" <?php echo $config['ticket_alert_dept_members']?'checked':''; ?>> Department Members (spammy)
            </td>
        </tr>
        <tr>
            <td width="160">New Message Alert:</td>
            <td>
              <input type="radio" name="message_alert_active"  value="1"   <?php echo $config['message_alert_active']?'checked':''; ?> />Enable
              <input type="radio" name="message_alert_active"  value="0"   <?php echo !$config['message_alert_active']?'checked':''; ?> />Disable
              &nbsp;&nbsp;<em>Alert sent out when a new message is appended to an existing ticket <font class="error">&nbsp;<?php echo $errors['message_alert_active']; ?></font></em><br>
              <strong>Recipients</strong>:&nbsp;
              <input type="checkbox" name="message_alert_laststaff" <?php echo $config['message_alert_laststaff']?'checked':''; ?>> Last Respondent
              <input type="checkbox" name="message_alert_assigned" <?php echo $config['message_alert_assigned']?'checked':''; ?>> Assigned Staff
              <input type="checkbox" name="message_alert_dept_manager" <?php echo $config['message_alert_dept_manager']?'checked':''; ?>> Department Manager (spammy)
            </td>
        </tr>
        <tr>
            <td width="160">New Internal Note Alert:</td>
            <td>
              <input type="radio" name="note_alert_active"  value="1"   <?php echo $config['note_alert_active']?'checked':''; ?> />Enable
              <input type="radio" name="note_alert_active"  value="0"   <?php echo !$config['note_alert_active']?'checked':''; ?> />Disable
               &nbsp;&nbsp;<em>Alert sent out when a new internal note is posted &nbsp;<font class="error">&nbsp;<?php echo $errors['note_alert_active']; ?></font></em><br>
              <strong>Recipients</strong>:&nbsp;
              <input type="checkbox" name="note_alert_laststaff" <?php echo $config['note_alert_laststaff']?'checked':''; ?>> Last Respondent
              <input type="checkbox" name="note_alert_assigned" <?php echo $config['note_alert_assigned']?'checked':''; ?>> Assigned Staff
              <input type="checkbox" name="note_alert_dept_manager" <?php echo $config['note_alert_dept_manager']?'checked':''; ?>> Department Manager (spammy)
            </td>
        </tr>
        <tr>
            <td width="160">Ticket Assignment Alert:</td>
            <td>
              <input name="assigned_alert_active" value="1" checked="checked" type="radio">Enable
              <input name="assigned_alert_active" value="0" type="radio">Disable
               &nbsp;&nbsp;<em>Alert sent out to staff on ticket assignment &nbsp;<font class="error">&nbsp;<?php echo $errors['assigned_alert_active']; ?></font></em><br>
              <strong>Recipients</strong>:&nbsp;
              <input type="checkbox" name="assigned_alert_staff" <?php echo $config['assigned_alert_staff']?'checked':''; ?>> Assigned Staff
              <input type="checkbox"name="assigned_alert_team_lead" <?php echo $config['assigned_alert_team_lead']?'checked':''; ?>>Team Lead (Team assignment)
              <input type="checkbox"name="assigned_alert_team_members" <?php echo $config['assigned_alert_team_members']?'checked':''; ?>>
                Team Members (spammy)
            </td>
        </tr>
        <tr>
            <td width="160">Ticket Transfer Alert:</td>
            <td>
              <input type="radio" name="transfer_alert_active"  value="1"   <?php echo $config['transfer_alert_active']?'checked':''; ?> />Enable
              <input type="radio" name="transfer_alert_active"  value="0"   <?php echo !$config['transfer_alert_active']?'checked':''; ?> />Disable
              &nbsp;&nbsp;<em>Alert sent out to staff on ticket transfer&nbsp;<font class="error">&nbsp;
<?php echo $errors['alert_alert_active']; ?></font></em><br>
              <strong>Recipients</strong>:&nbsp;
              <input type="checkbox" name="transfer_alert_assigned" <?php echo $config['transfer_alert_assigned']?'checked':''; ?>> Assigned Staff/Team
              <input type="checkbox" name="transfer_alert_dept_manager" <?php echo $config['transfer_alert_dept_manager']?'checked':''; ?>> Department Manager
              <input type="checkbox" name="transfer_alert_dept_members" <?php echo $config['transfer_alert_dept_members']?'checked':''; ?>> Department Members
 (spammy)
            </td>
        </tr>
        <tr>
            <td width="160">Overdue Ticket Alert:</td>
            <td>
              <input type="radio" name="overdue_alert_active"  value="1"   <?php echo $config['overdue_alert_active']?'checked':''; ?> />Enable
              <input type="radio" name="overdue_alert_active"  value="0"   <?php echo !$config['overdue_alert_active']?'checked':''; ?> />Disable
              &nbsp;&nbsp;<em>Alert sent out when a ticket becomes overdue - admin email gets an alert by default. &nbsp;<font class="error">&nbsp;<?php echo $errors['overdue_alert_active']; ?></font></em><br>
              <strong>Recipients</strong>:&nbsp;
              <input type="checkbox" name="overdue_alert_assigned" <?php echo $config['overdue_alert_assigned']?'checked':''; ?>> Assigned Staff/Team
              <input type="checkbox" name="overdue_alert_dept_manager" <?php echo $config['overdue_alert_dept_manager']?'checked':''; ?>> Department Manager
              <input type="checkbox" name="overdue_alert_dept_members" <?php echo $config['overdue_alert_dept_members']?'checked':''; ?>> Department Members (spammy)
            </td>
        </tr>
        <tr>
            <td width="160">System Alerts:</td>
            <td><em><b>Enabled</b>: Errors are sent to system admin email (<?php echo $cfg->getAdminEmail(); ?>)</em><br>
              <input type="checkbox" name="send_sys_errors" checked="checked" disabled="disabled">System Errors
              <input type="checkbox" name="send_sql_errors" <?php echo $config['send_sql_errors']?'checked':''; ?>>SQL errors
              <input type="checkbox" name="send_login_errors" <?php echo $config['send_login_errors']?'checked':''; ?>>Excessive Login attempts
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:200px;">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes">
</p>
</form>
