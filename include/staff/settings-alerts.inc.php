<h2>Alerts and Notices
    <i class="help-tip icon-question-sign" href="#page_title"></i></h2>
<form action="settings.php?t=alerts" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="alerts" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th>
                <h4>Alerts and Notices sent to staff on ticket "events"</h4>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr><th><em><b>New Ticket Alert</b>:
            <i class="help-tip icon-question-sign" href="#ticket_alert"></i>
            </em></th></tr>
        <tr>
            <td><em><b>Status:</b></em> &nbsp;
                <input type="radio" name="ticket_alert_active"  value="1"
                <?php echo $config['ticket_alert_active']?'checked':''; ?>
                /> Enable
                <input type="radio" name="ticket_alert_active"  value="0"   <?php echo !$config['ticket_alert_active']?'checked':''; ?> /> Disable
                &nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['ticket_alert_active']; ?></font></em>
             </td>
        </tr>
        <tr>
            <td>
                <input type="checkbox" name="ticket_alert_admin" <?php echo $config['ticket_alert_admin']?'checked':''; ?>> Admin Email <em>(<?php echo $cfg->getAdminEmail(); ?>)</em>
            </td>
        </tr>
        <tr>
            <td>
                <input type="checkbox" name="ticket_alert_dept_manager" <?php echo $config['ticket_alert_dept_manager']?'checked':''; ?>> Department Manager
            </td>
        </tr>
        <tr>
            <td>
                <input type="checkbox" name="ticket_alert_dept_members" <?php echo $config['ticket_alert_dept_members']?'checked':''; ?>> Department Members
            </td>
        </tr>
        <tr>
            <td>
                <input type="checkbox" name="ticket_alert_acct_manager" <?php echo $config['ticket_alert_acct_manager']?'checked':''; ?>> Organization Account Manager
            </td>
        </tr>
        <tr><th><em><b>New Message Alert</b>:
            <i class="help-tip icon-question-sign" href="#message_alert"></i>
            </em></th></tr>
        <tr>
            <td><em><b>Status:</b></em> &nbsp;
              <input type="radio" name="message_alert_active"  value="1"
              <?php echo $config['message_alert_active']?'checked':''; ?>
              /> Enable
              &nbsp;&nbsp;
              <input type="radio" name="message_alert_active"  value="0"   <?php echo !$config['message_alert_active']?'checked':''; ?> /> Disable
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="message_alert_laststaff" <?php echo $config['message_alert_laststaff']?'checked':''; ?>> Last Respondent
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="message_alert_assigned" <?php
              echo $config['message_alert_assigned']?'checked':''; ?>>
              Assigned Staff
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="message_alert_dept_manager" <?php
              echo $config['message_alert_dept_manager']?'checked':''; ?>>
              Department Manager
            </td>
        </tr>
        <tr>
            <td>
                <input type="checkbox" name="message_alert_acct_manager" <?php echo $config['message_alert_acct_manager']?'checked':''; ?>> Organization Account Manager
            </td>
        </tr>
        <tr><th><em><b>New Internal Note Alert</b>:
            <i class="help-tip icon-question-sign" href="#internal_note_alert"></i>
            </em></th></tr>
        <tr>
            <td><em><b>Status:</b></em> &nbsp;
              <input type="radio" name="note_alert_active"  value="1"   <?php echo $config['note_alert_active']?'checked':''; ?> /> Enable
              &nbsp;&nbsp;
              <input type="radio" name="note_alert_active"  value="0"   <?php echo !$config['note_alert_active']?'checked':''; ?> /> Disable
              &nbsp;&nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['note_alert_active']; ?></font>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="note_alert_laststaff" <?php echo
              $config['note_alert_laststaff']?'checked':''; ?>> Last Respondent
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="note_alert_assigned" <?php echo $config['note_alert_assigned']?'checked':''; ?>> Assigned Staff / Team
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="note_alert_dept_manager" <?php echo $config['note_alert_dept_manager']?'checked':''; ?>> Department Manager
            </td>
        </tr>
        <tr><th><em><b>Ticket Assignment Alert</b>:
            <i class="help-tip icon-question-sign" href="#assignment_alert"></i>
            </em></th></tr>
        <tr>
            <td><em><b>Status: </b></em> &nbsp;
              <input name="assigned_alert_active" value="1" type="radio"
                <?php echo $config['assigned_alert_active']?'checked="checked"':''; ?>> Enable
              &nbsp;&nbsp;
              <input name="assigned_alert_active" value="0" type="radio"
                <?php echo !$config['assigned_alert_active']?'checked="checked"':''; ?>> Disable
               &nbsp;&nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['assigned_alert_active']; ?></font>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="assigned_alert_staff" <?php echo
              $config['assigned_alert_staff']?'checked':''; ?>> Assigned Staff
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox"name="assigned_alert_team_lead" <?php
              echo $config['assigned_alert_team_lead']?'checked':''; ?>> Team Lead
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox"name="assigned_alert_team_members" <?php echo $config['assigned_alert_team_members']?'checked':''; ?>>
                Team Members
            </td>
        </tr>
        <tr><th><em><b>Ticket Transfer Alert</b>:
            <i class="help-tip icon-question-sign" href="#transfer_alert"></i>
            </em></th></tr>
        <tr>
            <td><em><b>Status:</b></em> &nbsp;
              <input type="radio" name="transfer_alert_active"  value="1"   <?php echo $config['transfer_alert_active']?'checked':''; ?> /> Enable
              <input type="radio" name="transfer_alert_active"  value="0"   <?php echo !$config['transfer_alert_active']?'checked':''; ?> /> Disable
              &nbsp;&nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['alert_alert_active']; ?></font>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="transfer_alert_assigned" <?php echo $config['transfer_alert_assigned']?'checked':''; ?>> Assigned Staff / Team
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="transfer_alert_dept_manager" <?php echo $config['transfer_alert_dept_manager']?'checked':''; ?>> Department Manager
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="transfer_alert_dept_members" <?php echo $config['transfer_alert_dept_members']?'checked':''; ?>>
                Department Members
            </td>
        </tr>
        <tr><th><em><b>Overdue Ticket Alert</b>:
            <i class="help-tip icon-question-sign" href="#overdue_alert"></i>
            </em></th></tr>
        <tr>
            <td><em><b>Status:</b></em> &nbsp;
              <input type="radio" name="overdue_alert_active"  value="1"
                <?php echo $config['overdue_alert_active']?'checked':''; ?> /> Enable
              <input type="radio" name="overdue_alert_active"  value="0"
                <?php echo !$config['overdue_alert_active']?'checked':''; ?> /> Disable
              &nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['overdue_alert_active']; ?></font>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="overdue_alert_assigned" <?php
                echo $config['overdue_alert_assigned']?'checked':''; ?>> Assigned Staff / Team
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="overdue_alert_dept_manager" <?php
                echo $config['overdue_alert_dept_manager']?'checked':''; ?>> Department Manager
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="overdue_alert_dept_members" <?php
                echo $config['overdue_alert_dept_members']?'checked':''; ?>> Department Members
            </td>
        </tr>
        <tr><th>
            <em><b>System Alerts</b>: <i class="help-tip icon-question-sign" href="#system_alerts"></i></em></th></tr>
        <tr>
            <td>
              <input type="checkbox" name="send_sys_errors" checked="checked" disabled="disabled"> System Errors
              <em>(enabled by default)</em>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="send_sql_errors" <?php echo $config['send_sql_errors']?'checked':''; ?>> SQL errors
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="send_login_errors" <?php echo $config['send_login_errors']?'checked':''; ?>> Excessive failed login attempts
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:350px;">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes">
</p>
</form>
