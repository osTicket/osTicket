<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <tbody>
        <tr><th><em><b><?php echo __('New Ticket Alert'); ?></b>:
            <i class="help-tip icon-question-sign" href="#ticket_alert"></i>
            </em></th></tr>
        <tr>
            <td><em><b><?php echo __('Status'); ?>:</b></em> &nbsp;
                <input type="radio" name="ticket_alert_active"  value="1"
                <?php echo $config['ticket_alert_active']?'checked':''; ?>
                /> <?php echo __('Enable'); ?>
                <input type="radio" name="ticket_alert_active"  value="0"   <?php echo !$config['ticket_alert_active']?'checked':''; ?> />
                 <?php echo __('Disable'); ?>
                &nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['ticket_alert_active']; ?></font></em>
             </td>
        </tr>
        <tr>
            <td>
                <input type="checkbox" name="ticket_alert_admin" <?php echo $config['ticket_alert_admin']?'checked':''; ?>>
                <?php echo __('Admin Email'); ?> <em>(<?php echo $cfg->getAdminEmail(); ?>)</em>
            </td>
        </tr>
        <tr>
            <td>
                <input type="checkbox" name="ticket_alert_dept_manager" <?php echo $config['ticket_alert_dept_manager']?'checked':''; ?>>
                <?php echo __('Department Manager'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <input type="checkbox" name="ticket_alert_dept_members" <?php echo $config['ticket_alert_dept_members']?'checked':''; ?>>
                <?php echo __('Department Members'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <input type="checkbox" name="ticket_alert_acct_manager" <?php echo $config['ticket_alert_acct_manager']?'checked':''; ?>>
                <?php echo __('Organization Account Manager'); ?>
            </td>
        </tr>
        <tr><th><em><b><?php echo __('New Message Alert'); ?></b>:
            <i class="help-tip icon-question-sign" href="#message_alert"></i>
            </em></th></tr>
        <tr>
            <td><em><b><?php echo __('Status'); ?>:</b></em> &nbsp;
              <input type="radio" name="message_alert_active"  value="1"
              <?php echo $config['message_alert_active']?'checked':''; ?>
              /> <?php echo __('Enable'); ?>
              &nbsp;&nbsp;
              <input type="radio" name="message_alert_active"  value="0"   <?php echo !$config['message_alert_active']?'checked':''; ?> />
              <?php echo __('Disable'); ?>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="message_alert_laststaff" <?php echo $config['message_alert_laststaff']?'checked':''; ?>>
                <?php echo __('Last Respondent'); ?>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="message_alert_assigned" <?php
              echo $config['message_alert_assigned']?'checked':''; ?>>
              <?php echo __('Assigned Agent / Team'); ?>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="message_alert_dept_manager" <?php
              echo $config['message_alert_dept_manager']?'checked':''; ?>>
              <?php echo __('Department Manager'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <input type="checkbox" name="message_alert_acct_manager" <?php echo $config['message_alert_acct_manager']?'checked':''; ?>>
                <?php echo __('Organization Account Manager'); ?>
            </td>
        </tr>
        <tr><th><em><b><?php echo __('New Internal Activity Alert'); ?></b>:
            <i class="help-tip icon-question-sign" href="#internal_note_alert"></i>
            </em></th></tr>
        <tr>
            <td><em><b><?php echo __('Status'); ?>:</b></em> &nbsp;
              <input type="radio" name="note_alert_active"  value="1"   <?php echo $config['note_alert_active']?'checked':''; ?> />
                <?php echo __('Enable'); ?>
              &nbsp;&nbsp;
              <input type="radio" name="note_alert_active"  value="0"   <?php echo !$config['note_alert_active']?'checked':''; ?> />
                <?php echo __('Disable'); ?>
              &nbsp;&nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['note_alert_active']; ?></font>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="note_alert_laststaff" <?php echo
              $config['note_alert_laststaff']?'checked':''; ?>> <?php echo __('Last Respondent'); ?>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="note_alert_assigned" <?php echo $config['note_alert_assigned']?'checked':''; ?>>
                <?php echo __('Assigned Agent / Team'); ?>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="note_alert_dept_manager" <?php echo $config['note_alert_dept_manager']?'checked':''; ?>>
                <?php echo __('Department Manager'); ?>
            </td>
        </tr>
        <tr><th><em><b><?php echo __('Ticket Assignment Alert'); ?></b>:
            <i class="help-tip icon-question-sign" href="#assignment_alert"></i>
            </em></th></tr>
        <tr>
            <td><em><b><?php echo __('Status'); ?>: </b></em> &nbsp;
              <input name="assigned_alert_active" value="1" type="radio"
                <?php echo $config['assigned_alert_active']?'checked="checked"':''; ?>> <?php echo __('Enable'); ?>
              &nbsp;&nbsp;
              <input name="assigned_alert_active" value="0" type="radio"
                <?php echo !$config['assigned_alert_active']?'checked="checked"':''; ?>> <?php echo __('Disable'); ?>
               &nbsp;&nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['assigned_alert_active']; ?></font>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="assigned_alert_staff" <?php echo
              $config['assigned_alert_staff']?'checked':''; ?>> <?php echo __('Assigned Agent'); ?>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox"name="assigned_alert_team_lead" <?php
              echo $config['assigned_alert_team_lead']?'checked':''; ?>> <?php echo __('Team Lead'); ?>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox"name="assigned_alert_team_members" <?php echo $config['assigned_alert_team_members']?'checked':''; ?>>
                <?php echo __('Team Members'); ?>
            </td>
        </tr>
        <tr><th><em><b><?php echo __('Ticket Transfer Alert'); ?></b>:
            <i class="help-tip icon-question-sign" href="#transfer_alert"></i>
            </em></th></tr>
        <tr>
            <td><em><b><?php echo __('Status'); ?>:</b></em> &nbsp;
            <input type="radio" name="transfer_alert_active"  value="1"   <?php echo $config['transfer_alert_active']?'checked':''; ?> />
                <?php echo __('Enable'); ?>
            <input type="radio" name="transfer_alert_active"  value="0"   <?php echo !$config['transfer_alert_active']?'checked':''; ?> />
                <?php echo __('Disable'); ?>
              &nbsp;&nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['alert_alert_active']; ?></font>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="transfer_alert_assigned" <?php echo $config['transfer_alert_assigned']?'checked':''; ?>>
                <?php echo __('Assigned Agent / Team'); ?>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="transfer_alert_dept_manager" <?php echo $config['transfer_alert_dept_manager']?'checked':''; ?>>
                <?php echo __('Department Manager'); ?>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="transfer_alert_dept_members" <?php echo $config['transfer_alert_dept_members']?'checked':''; ?>>
                <?php echo __('Department Members'); ?>
            </td>
        </tr>
        <tr><th><em><b><?php echo __('Overdue Ticket Alert'); ?></b>:
            <i class="help-tip icon-question-sign" href="#overdue_alert"></i>
            </em></th></tr>
        <tr>
            <td><em><b><?php echo __('Status'); ?>:</b></em> &nbsp;
              <input type="radio" name="overdue_alert_active"  value="1"
                <?php echo $config['overdue_alert_active']?'checked':''; ?> /> <?php echo __('Enable'); ?>
              <input type="radio" name="overdue_alert_active"  value="0"
                <?php echo !$config['overdue_alert_active']?'checked':''; ?> /> <?php echo __('Disable'); ?>
              &nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['overdue_alert_active']; ?></font>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="overdue_alert_assigned" <?php
                echo $config['overdue_alert_assigned']?'checked':''; ?>> <?php echo __('Assigned Agent / Team'); ?>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="overdue_alert_dept_manager" <?php
                echo $config['overdue_alert_dept_manager']?'checked':''; ?>> <?php echo __('Department Manager'); ?>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="overdue_alert_dept_members" <?php
                echo $config['overdue_alert_dept_members']?'checked':''; ?>> <?php echo __('Department Members'); ?>
            </td>
        </tr>
        <tr><th>
            <em><b><?php echo __('System Alerts'); ?></b>: <i class="help-tip icon-question-sign" href="#system_alerts"></i></em></th></tr>
        <tr>
            <td>
              <input type="checkbox" name="send_sys_errors" checked="checked" disabled="disabled">
                <?php echo __('System Errors'); ?>
              <em><?php echo __('(enabled by default)'); ?></em>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="send_sql_errors" <?php echo $config['send_sql_errors']?'checked':''; ?>>
                <?php echo __('SQL errors'); ?>
            </td>
        </tr>
        <tr>
            <td>
              <input type="checkbox" name="send_login_errors" <?php echo $config['send_login_errors']?'checked':''; ?>>
                <?php echo __('Excessive failed login attempts'); ?>
            </td>
        </tr>
    </tbody>
</table>
