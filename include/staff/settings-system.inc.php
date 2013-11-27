<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');

$gmtime = Misc::gmtime();
?>
<h2>System Settings and Preferences - <span>osTicket (<?php echo $cfg->getVersion(); ?>)</span></h2>
<form action="settings.php?t=system" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="system" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4>System Settings &amp; Preferences</h4>
                <em><b>General Settings</b>: Offline mode will disable client interface and only allow admins to login to Staff Control Panel</em>
            </th>
        </tr>
    </thead>
    <tbody>

        <tr>
            <td width="220" class="required">Helpdesk Status:</td>
            <td>
                <input type="radio" name="isonline"  value="1"   <?php echo $config['isonline']?'checked="checked"':''; ?> /><b>Online</b> (Active)
                <input type="radio" name="isonline"  value="0"   <?php echo !$config['isonline']?'checked="checked"':''; ?> /><b>Offline</b> (Disabled)
                &nbsp;<font class="error">&nbsp;<?php echo $config['isoffline']?'osTicket offline':''; ?></font>
                <i class="help-tip icon-question-sign" href="#helpdesk_status"></i>
            </td>
        </tr>
        <tr>
            <td width="220" class="required">Helpdesk URL:</td>
            <td>
                <input type="text" size="40" name="helpdesk_url" value="<?php echo $config['helpdesk_url']; ?>">
                &nbsp;<font class="error">*&nbsp;<?php echo $errors['helpdesk_url']; ?></font>
                <i class="help-tip icon-question-sign" href="#helpdesk_url"></i>
        </td>
        </tr>
        <tr>
            <td width="220" class="required">Helpdesk Name/Title:</td>
            <td><input type="text" size="40" name="helpdesk_title" value="<?php echo $config['helpdesk_title']; ?>">
                &nbsp;<font class="error">*&nbsp;<?php echo $errors['helpdesk_title']; ?></font>
                <i class="help-tip icon-question-sign" href="#helpdesk_name"></i>
            </td>
        </tr>
        <tr>
            <td width="220" class="required">Default Department:</td>
            <td>
                <select name="default_dept_id">
                    <option value="">&mdash; Select Default Department &mdash;</option>
                    <?php
                    $sql='SELECT dept_id,dept_name FROM '.DEPT_TABLE.' WHERE ispublic=1';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while (list($id, $name) = db_fetch_row($res)){
                            $selected = ($config['default_dept_id']==$id)?'selected="selected"':''; ?>
                            <option value="<?php echo $id; ?>"<?php echo $selected; ?>><?php echo $name; ?> Dept</option>
                        <?php
                        }
                    } ?>
                </select>&nbsp;<font class="error">*&nbsp;<?php echo $errors['default_dept_id']; ?></font>
                <i class="help-tip icon-question-sign" href="#default_dept"></i>
            </td>
        </tr>

        <tr><td>Default Page Size:</td>
            <td>
                <select name="max_page_size">
                    <?php
                     $pagelimit=$config['max_page_size'];
                    for ($i = 5; $i <= 50; $i += 5) {
                        ?>
                        <option <?php echo $config['max_page_size']==$i?'selected="selected"':''; ?> value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php
                    } ?>
                </select>
                <i class="help-tip icon-question-sign" href="#page_size"></i>
            </td>
        </tr>
        <tr>
            <td>Default Log Level:</td>
            <td>
                <select name="log_level">
                    <option value=0 <?php echo $config['log_level'] == 0 ? 'selected="selected"':''; ?>>None (Disable Logger)</option>
                    <option value=3 <?php echo $config['log_level'] == 3 ? 'selected="selected"':''; ?>> DEBUG</option>
                    <option value=2 <?php echo $config['log_level'] == 2 ? 'selected="selected"':''; ?>> WARN</option>
                    <option value=1 <?php echo $config['log_level'] == 1 ? 'selected="selected"':''; ?>> ERROR</option>
                </select>
                <font class="error">&nbsp;<?php echo $errors['log_level']; ?></font>
                <i class="help-tip icon-question-sign" href="#log_level"></i>
            </td>
        </tr>
        <tr>
            <td>Purge Logs:</td>
            <td>
                <select name="log_graceperiod">
                    <option value=0 selected>Never Purge Logs</option>
                    <?php
                    for ($i = 1; $i <=12; $i++) {
                        ?>
                        <option <?php echo $config['log_graceperiod']==$i?'selected="selected"':''; ?> value="<?php echo $i; ?>">
                            After&nbsp;<?php echo $i; ?>&nbsp;<?php echo ($i>1)?'Months':'Month'; ?></option>
                        <?php
                    } ?>
                </select>
                <i class="help-tip icon-question-sign" href="#purge_logs"></i>
            </td>
        </tr>
        <tr>
            <td width="180">Default Name Formatting:</td>
            <td>
                <select name="name_format">
<?php foreach (PersonsName::allFormats() as $n=>$f) {
    list($desc, $func) = $f;
    $selected = ($config['name_format'] == $n) ? 'selected="selected"' : ''; ?>
                    <option value="<?php echo $n; ?>" <?php echo $selected;
                        ?>><?php echo $desc; ?></option>
<?php } ?>
                </select>
                <i class="help-tip icon-question-sign" href="#name_format"></i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><b>Authentication Settings</b></em>
            </th>
        </tr>
        <tr><td>Password Expiration Policy:</th>
            <td>
                <select name="passwd_reset_period">
                   <option value="0"> &mdash; No expiration &mdash;</option>
                  <?php
                    for ($i = 1; $i <= 12; $i++) {
                        echo sprintf('<option value="%d" %s>%s%s</option>',
                                $i,(($config['passwd_reset_period']==$i)?'selected="selected"':''), $i>1?"Every $i ":'', $i>1?' Months':'Monthly');
                    }
                    ?>
                </select>
                <font class="error"><?php echo $errors['passwd_reset_period']; ?></font>
                <i class="help-tip icon-question-sign" href="#password_reset"></i>
            </td>
        </tr>
        <tr><td>Allow Password Resets:</th>
            <td>
              <input type="checkbox" name="allow_pw_reset" <?php echo $config['allow_pw_reset']?'checked="checked"':''; ?>>
              <em>Enables the <u>Forgot my password</u> link on the staff
              control panel</em>
            </td>
        </tr>
        <tr><td>Password Reset Window:</th>
            <td>
              <input type="text" name="pw_reset_window" size="6" value="<?php
                    echo $config['pw_reset_window']; ?>">
                Maximum time <em>in minutes</em> a password reset token can
                be valid.
                &nbsp;<font class="error">&nbsp;<?php echo $errors['pw_reset_window']; ?></font>
            </td>
        </tr>
        <tr><td>Staff Excessive Logins:</td>
            <td>
                <select name="staff_max_logins">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>', $i,(($config['staff_max_logins']==$i)?'selected="selected"':''), $i);
                    }
                    ?>
                </select> failed login attempt(s) allowed before a
                <select name="staff_login_timeout">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>', $i,(($config['staff_login_timeout']==$i)?'selected="selected"':''), $i);
                    }
                    ?>
                </select> minute lock-out is enforced.
            </td>
        </tr>
        <tr><td>Staff Session Timeout:</td>
            <td>
              <input type="text" name="staff_session_timeout" size=6 value="<?php echo $config['staff_session_timeout']; ?>">
                Maximum idle time in minutes before a staff member must log in again (enter 0 to disable).
            </td>
        </tr>
        <tr><td>Client Excessive Logins:</td>
            <td>
                <select name="client_max_logins">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>', $i,(($config['client_max_logins']==$i)?'selected="selected"':''), $i);
                    }

                    ?>
                </select> failed login attempt(s) allowed before a
                <select name="client_login_timeout">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>', $i,(($config['client_login_timeout']==$i)?'selected="selected"':''), $i);
                    }
                    ?>
                </select> minute lock-out is enforced.
            </td>
        </tr>

        <tr><td>Client Session Timeout:</td>
            <td>
              <input type="text" name="client_session_timeout" size=6 value="<?php echo $config['client_session_timeout']; ?>">
                &nbsp;Maximum idle time in minutes before a client must log in again (enter 0 to disable).
            </td>
        </tr>
        <tr><td>Bind Staff Session to IP:</td>
            <td>
              <input type="checkbox" name="staff_ip_binding" <?php echo $config['staff_ip_binding']?'checked="checked"':''; ?>>
              <em>(binds staff session to originating IP address upon login)</em>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><b>Date and Time Options</b>&nbsp;
                <i class="help-tip icon-question-sign" href="#date_and_time"></i>
                </em>
            </th>
        </tr>
        <tr><td width="220" class="required">Time Format:</td>
            <td>
                <input type="text" name="time_format" value="<?php echo $config['time_format']; ?>">
                    &nbsp;<font class="error">*&nbsp;<?php echo $errors['time_format']; ?></font>
                    <em><?php echo Format::date($config['time_format'], $gmtime, $config['tz_offset'], $config['enable_daylight_saving']); ?></em></td>
        </tr>
        <tr><td width="220" class="required">Date Format:</td>
            <td><input type="text" name="date_format" value="<?php echo $config['date_format']; ?>">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['date_format']; ?></font>
                        <em><?php echo Format::date($config['date_format'], $gmtime, $config['tz_offset'], $config['enable_daylight_saving']); ?></em>
            </td>
        </tr>
        <tr><td width="220" class="required">Date &amp; Time Format:</td>
            <td><input type="text" name="datetime_format" value="<?php echo $config['datetime_format']; ?>">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['datetime_format']; ?></font>
                        <em><?php echo Format::date($config['datetime_format'], $gmtime, $config['tz_offset'], $config['enable_daylight_saving']); ?></em>
            </td>
        </tr>
        <tr><td width="220" class="required">Day, Date &amp; Time Format:</td>
            <td><input type="text" name="daydatetime_format" value="<?php echo $config['daydatetime_format']; ?>">
                        &nbsp;<font class="error">*&nbsp;<?php echo $errors['daydatetime_format']; ?></font>
                        <em><?php echo Format::date($config['daydatetime_format'], $gmtime, $config['tz_offset'], $config['enable_daylight_saving']); ?></em>
            </td>
        </tr>
        <tr><td width="220" class="required">Default Time Zone:</td>
            <td>
                <select name="default_timezone_id">
                    <option value="">&mdash; Select Default Time Zone &mdash;</option>
                    <?php
                    $sql='SELECT id, offset,timezone FROM '.TIMEZONE_TABLE.' ORDER BY id';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id, $offset, $tz)=db_fetch_row($res)){
                            $sel=($config['default_timezone_id']==$id)?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>GMT %s - %s</option>', $id, $sel, $offset, $tz);
                        }
                    }
                    ?>
                </select>
                &nbsp;<font class="error">*&nbsp;<?php echo $errors['default_timezone_id']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="220">Daylight Saving:</td>
            <td>
                <input type="checkbox" name="enable_daylight_saving" <?php echo $config['enable_daylight_saving'] ? 'checked="checked"': ''; ?>>Observe daylight savings
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:250px;">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes">
</p>
</form>
