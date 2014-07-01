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
                <i class="help-tip icon-question-sign" href="#password_expiration_policy"></i>
            </td>
        </tr>
        <tr><td>Allow Password Resets:</th>
            <td>
              <input type="checkbox" name="allow_pw_reset" <?php echo $config['allow_pw_reset']?'checked="checked"':''; ?>>
              &nbsp;<i class="help-tip icon-question-sign" href="#allow_password_resets"></i>
            </td>
        </tr>
        <tr><td>Reset Token Expiration:</th>
            <td>
              <input type="text" name="pw_reset_window" size="6" value="<?php
                    echo $config['pw_reset_window']; ?>">
                <em>mins</em>&nbsp;<i class="help-tip icon-question-sign" href="#reset_token_expiration"></i>
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
                mins <em>( 0 to disable)</em>. <i class="help-tip icon-question-sign" href="#staff_session_timeout"></i>
            </td>
        </tr>
        <tr><td>Bind Staff Session to IP:</td>
            <td>
              <input type="checkbox" name="staff_ip_binding" <?php echo $config['staff_ip_binding']?'checked="checked"':''; ?>>
              <i class="help-tip icon-question-sign" href="#bind_staff_session_to_ip"></i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><b>End User Authentication Settings</b></em>
            </th>
        </tr>
        <tr><td>Registration Required:</td>
            <td><input type="checkbox" name="clients_only" <?php
                if ($config['clients_only'])
                    echo 'checked="checked"'; ?>/>
                Require registration and login to create tickets
            <i class="help-tip icon-question-sign" href="#registration_method"></i>
            </td>
        <tr><td>Registration Method:</td>
            <td><select name="client_registration">
<?php foreach (array(
    'disabled' => 'Disabled — All users are guests',
    'public' => 'Public — Anyone can register',
    'closed' => 'Private — Only staff can register users',)
    as $key=>$val) { ?>
        <option value="<?php echo $key; ?>" <?php
        if ($config['client_registration'] == $key)
            echo 'selected="selected"'; ?>><?php echo $val;
        ?></option><?php
    } ?>
            </select>
            <i class="help-tip icon-question-sign" href="#registration_method"></i>
            </td>
        </tr>
        <tr><td>User Excessive Logins:</td>
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
        <tr><td>User Session Timeout:</td>
            <td>
              <input type="text" name="client_session_timeout" size=6 value="<?php echo $config['client_session_timeout']; ?>">
              <i class="help-tip icon-question-sign" href="#client_session_timeout"></i>
            </td>
        </tr>
        <tr><td>Client Quick Access:</td>
            <td><input type="checkbox" name="client_verify_email" <?php
                if ($config['client_verify_email'])
                    echo 'checked="checked"'; ?>/>
                Require email verification on "Check Ticket Status" page
            <i class="help-tip icon-question-sign" href="#client_verify_email"></i>
            </td>
        </tr>
    </tbody>
    <thead>
        <tr>
            <th colspan="2">
                <h4>Authentication and Registration Templates</h4>
            </th>
        </tr>
    </thead>
    <tbody>
<?php
$res = db_query('select distinct(`type`), content_id, notes, name, updated from '
    .PAGE_TABLE
    .' where isactive=1 group by `type`');
$contents = array();
while (list($type, $id, $notes, $name, $u) = db_fetch_row($res))
    $contents[$type] = array($id, $name, $notes, $u);

$manage_content = function($title, $content) use ($contents) {
    list($id, $name, $notes, $upd) = $contents[$content];
    $notes = explode('. ', $notes);
    $notes = $notes[0];
    ?><tr><td colspan="2">
    <a href="#ajax.php/content/<?php echo $id; ?>/manage"
    onclick="javascript:
        $.dialog($(this).attr('href').substr(1), 200);
    return false;"><i class="icon-file-text pull-left icon-2x"
        style="color:#bbb;"></i> <?php
    echo Format::htmlchars($title); ?></a><br/>
        <span class="faded" style="display:inline-block;width:90%"><?php
        echo Format::display($notes); ?>
    <em>(Last Updated <?php echo Format::db_datetime($upd); ?>)</em></span></td></tr><?php
}; ?>
        <tr>
            <th colspan="2">
                <em><b>Authentication and Registration Templates</b></em>
            </th>
        </tr>
        <?php $manage_content('Staff Members', 'pwreset-staff'); ?>
        <?php $manage_content('Clients', 'pwreset-client'); ?>
        <?php $manage_content('Guess Ticket Access', 'access-link'); ?>
        <tr>
            <th colspan="2">
                <em><b>Sign-In Pages</b></em>
            </th>
        </tr>
        <?php $manage_content('Staff Login Banner', 'banner-staff'); ?>
        <?php $manage_content('Client Sign-In Page', 'banner-client'); ?>
        <tr>
            <th colspan="2">
                <em><b>User Account Registration</b></em>
            </th>
        </tr>
        <?php $manage_content('Please Confirm Email Address Page', 'registration-confirm'); ?>
        <?php $manage_content('Confirmation Email', 'registration-client'); ?>
        <?php $manage_content('Account Confirmed Page', 'registration-thanks'); ?>
        <tr>
            <th colspan="2">
                <em><b>Staff Account Registration</b></em>
            </th>
        </tr>
        <?php $manage_content('Staff Welcome Email', 'registration-staff'); ?>
</tbody>
</table>
<p style="text-align:center">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes">
</p>
</form>
