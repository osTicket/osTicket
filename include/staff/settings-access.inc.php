<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');

?>
<h2><?php echo __('Access Control Settings'); ?></h2>
<form action="settings.php?t=access" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="access" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo __('Configure Access to this Help Desk'); ?></h4>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th colspan="2">
                <em><b><?php echo __('Agent Authentication Settings'); ?></b></em>
            </th>
        </tr>
        <tr><td><?php echo __('Password Expiration Policy'); ?>:</th>
            <td>
                <select name="passwd_reset_period">
                   <option value="0"> &mdash; <?php echo __('No expiration'); ?> &mdash;</option>
                  <?php
                    for ($i = 1; $i <= 12; $i++) {
                        echo sprintf('<option value="%d" %s>%s</option>',
                                $i,(($config['passwd_reset_period']==$i)?'selected="selected"':''),
                                sprintf(_N('Monthly', 'Every %d months', $i), $i));
                    }
                    ?>
                </select>
                <font class="error"><?php echo $errors['passwd_reset_period']; ?></font>
                <i class="help-tip icon-question-sign" href="#password_expiration_policy"></i>
            </td>
        </tr>
        <tr><td><?php echo __('Allow Password Resets'); ?>:</th>
            <td>
              <input type="checkbox" name="allow_pw_reset" <?php echo $config['allow_pw_reset']?'checked="checked"':''; ?>>
              &nbsp;<i class="help-tip icon-question-sign" href="#allow_password_resets"></i>
            </td>
        </tr>
        <tr><td><?php echo __('Reset Token Expiration'); ?>:</th>
            <td>
              <input type="text" name="pw_reset_window" size="6" value="<?php
                    echo $config['pw_reset_window']; ?>">
                    <em><?php echo __('minutes'); ?></em>
                    <i class="help-tip icon-question-sign" href="#reset_token_expiration"></i>
                &nbsp;<font class="error"><?php echo $errors['pw_reset_window']; ?></font>
            </td>
        </tr>
        <tr><td><?php echo __('Agent Excessive Logins'); ?>:</td>
            <td>
                <select name="staff_max_logins">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>', $i,(($config['staff_max_logins']==$i)?'selected="selected"':''), $i);
                    }
                    ?>
                </select> <?php echo __(
                'failed login attempt(s) allowed before a lock-out is enforced'); ?>
                <br/>
                <select name="staff_login_timeout">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>', $i,(($config['staff_login_timeout']==$i)?'selected="selected"':''), $i);
                    }
                    ?>
                </select> <?php echo __('minutes locked out'); ?>
            </td>
        </tr>
        <tr><td><?php echo __('Agent Session Timeout'); ?>:</td>
            <td>
              <input type="text" name="staff_session_timeout" size=6 value="<?php echo $config['staff_session_timeout']; ?>">
                <?php echo __('minutes'); ?> <em><?php echo __('(0 to disable)'); ?></em>. <i class="help-tip icon-question-sign" href="#staff_session_timeout"></i>
            </td>
        </tr>
        <tr><td><?php echo __('Bind Agent Session to IP'); ?>:</td>
            <td>
              <input type="checkbox" name="staff_ip_binding" <?php echo $config['staff_ip_binding']?'checked="checked"':''; ?>>
              <i class="help-tip icon-question-sign" href="#bind_staff_session_to_ip"></i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><b><?php echo __('End User Authentication Settings'); ?></b></em>
            </th>
        </tr>
        <tr><td><?php echo __('Registration Required'); ?>:</td>
            <td><input type="checkbox" name="clients_only" <?php
                if ($config['clients_only'])
                    echo 'checked="checked"'; ?>/> <?php echo __(
                    'Require registration and login to create tickets'); ?>
            <i class="help-tip icon-question-sign" href="#registration_method"></i>
            </td>
        <tr><td><?php echo __('Registration Method'); ?>:</td>
            <td><select name="client_registration">
<?php foreach (array(
    'disabled' => __('Disabled — All users are guests'),
    'public' => __('Public — Anyone can register'),
    'closed' => __('Private — Only agents can register users'),)
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
        <tr><td><?php echo __('User Excessive Logins'); ?>:</td>
            <td>
                <select name="client_max_logins">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>', $i,(($config['client_max_logins']==$i)?'selected="selected"':''), $i);
                    }

                    ?>
                </select> <?php echo __(
                'failed login attempt(s) allowed before a lock-out is enforced'); ?>
                <br/>
                <select name="client_login_timeout">
                  <?php
                    for ($i = 1; $i <= 10; $i++) {
                        echo sprintf('<option value="%d" %s>%d</option>', $i,(($config['client_login_timeout']==$i)?'selected="selected"':''), $i);
                    }
                    ?>
                </select> <?php echo __('minutes locked out'); ?>
            </td>
        </tr>
        <tr><td><?php echo __('User Session Timeout'); ?>:</td>
            <td>
              <input type="text" name="client_session_timeout" size=6 value="<?php echo $config['client_session_timeout']; ?>">
              <i class="help-tip icon-question-sign" href="#client_session_timeout"></i>
            </td>
        </tr>
        <tr><td><?php echo __('Client Quick Access'); ?>:</td>
            <td><input type="checkbox" name="client_verify_email" <?php
                if ($config['client_verify_email'])
                    echo 'checked="checked"'; ?>/> <?php echo __(
                'Require email verification on "Check Ticket Status" page'); ?>
            <i class="help-tip icon-question-sign" href="#client_verify_email"></i>
            </td>
        </tr>
    </tbody>
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo __('Authentication and Registration Templates'); ?></h4>
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
    <em>(<?php echo sprintf(__('Last Updated %s'), Format::db_datetime($upd));
        ?>)</em></span></td></tr><?php
}; ?>
        <tr>
            <th colspan="2">
                <em><b><?php echo __(
                'Authentication and Registration Templates'); ?></b></em>
            </th>
        </tr>
        <?php $manage_content(__('Agents'), 'pwreset-staff'); ?>
        <?php $manage_content(__('Clients'), 'pwreset-client'); ?>
        <?php $manage_content(__('Guest Ticket Access'), 'access-link'); ?>
        <tr>
            <th colspan="2">
                <em><b><?php echo __('Sign In Pages'); ?></b></em>
            </th>
        </tr>
        <?php $manage_content(__('Agent Login Banner'), 'banner-staff'); ?>
        <?php $manage_content(__('Client Sign-In Page'), 'banner-client'); ?>
        <tr>
            <th colspan="2">
                <em><b><?php echo __('User Account Registration'); ?></b></em>
            </th>
        </tr>
        <?php $manage_content(__('Please Confirm Email Address Page'), 'registration-confirm'); ?>
        <?php $manage_content(__('Confirmation Email'), 'registration-client'); ?>
        <?php $manage_content(__('Account Confirmed Page'), 'registration-thanks'); ?>
        <tr>
            <th colspan="2">
                <em><b><?php echo __('Agent Account Registration'); ?></b></em>
            </th>
        </tr>
        <?php $manage_content(__('Agent Welcome Email'), 'registration-staff'); ?>
</tbody>
</table>
<p style="text-align:center">
    <input class="button" type="submit" name="submit" value="<?php echo __('Save Changes'); ?>">
    <input class="button" type="reset" name="reset" value="<?php echo __('Reset Changes'); ?>">
</p>
</form>
