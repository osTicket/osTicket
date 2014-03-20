<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');

?>
<h2>Access Control Settings</h2>
<form action="settings.php?t=access" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="access" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4>Configure Access to this Help Desk</h4>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th colspan="2">
                <em><b>Staff Authentication Settings</b></em>
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
        <tr><td>Bind Staff Session to IP:</td>
            <td>
              <input type="checkbox" name="staff_ip_binding" <?php echo $config['staff_ip_binding']?'checked="checked"':''; ?>>
              <em>(binds staff session to originating IP address upon login)</em>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><b>Client Authentication Settings</b></em>
            </th>
        </tr>
        <tr><td>Registration Required:</td>
            <td><input type="checkbox" name="clients_only" <?php
                if ($config['clients_only'])
                    echo 'checked="checked"'; ?>/>
                Require registration and login to create tickets
            </td>
        <tr><td>Registration Method:</td>
            <td><select name="client_registration">
<?php foreach (array(
    'public' => 'Public — Anyone can register',
    'auto' => 'Automatic — Create new accounts for all new tickets',
    'closed' => 'Private — Only staff can register clients',)
    as $key=>$val) { ?>
        <option value="<?php echo $key; ?>" <?php
        if ($config['client_registration'] == $key)
            echo 'selected="selected"'; ?>><?php echo $val;
        ?></option><?php
    } ?>
            </select></td>
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
</tbody>
</table>
<p style="text-align:center">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes">
</p>
</form>
