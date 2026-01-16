<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');

?>
<h2><?php echo __('Users Settings'); ?></h2>
<form action="settings.php?t=users" method="post" class="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="users" >
<ul class="tabs" id="users-tabs">
    <li class="active"><a href="#settings">
        <i class="icon-asterisk"></i> <?php echo __('Settings'); ?></a></li>
    <li><a href="#templates">
        <i class="icon-file-text"></i> <?php echo __('Templates'); ?></a></li>
</ul>
<div id="users-tabs_container">
   <div id="settings" class="tab_content">
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <tbody>

        <tr>
            <th colspan="2">
                <em><b><?php echo __('General Settings'); ?></b></em>
            </th>
        </tr>
        <tr>
            <td width="180"><?php echo __('Name Formatting'); ?>:</td>
            <td>
                <select name="client_name_format">
                <?php foreach (PersonsName::allFormats() as $n=>$f) {
                    list($desc, $func) = $f;
                    $selected = ($config['client_name_format'] == $n) ? 'selected="selected"' : ''; ?>
                                    <option value="<?php echo $n; ?>" <?php echo $selected;
                                        ?>><?php echo __($desc); ?></option>
                <?php } ?>
                </select>
                <i class="help-tip icon-question-sign" href="#client_name_format"></i>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Avatar Source'); ?>:</td>
            <td>
                <select name="client_avatar">
<?php           require_once INCLUDE_DIR . 'class.avatar.php';
                foreach (AvatarSource::allSources() as $id=>$class) {
                    $modes = $class::getModes();
                    if ($modes) {
                        echo "<optgroup label=\"{$class::getName()}\">";
                        foreach ($modes as $mid=>$mname) {
                            $oid = "$id.$mid";
                            $selected = ($config['client_avatar'] == $oid) ? 'selected="selected"' : '';
                            echo "<option {$selected} value=\"{$oid}\">{$class::getName()} / {$mname}</option>";
                        }
                        echo "</optgroup>";
                    }
                    else {
                        $selected = ($config['client_avatar'] == $id) ? 'selected="selected"' : '';
                        echo "<option {$selected} value=\"{$id}\">{$class::getName()}</option>";
                    }
                } ?>
                </select>
                <div class="error"><?php echo Format::htmlchars($errors['client_avatar']); ?></div>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><b><?php echo __('Authentication Settings'); ?></b></em>
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
		<tr>
			<td><?php echo __('Password Policy'); ?>:</td>
			<td>
				<select name="client_passwd_policy">
				<option value=" "> &mdash; <?php echo __('All Active Policies'); ?> &mdash;</option>
				<?php
                foreach (PasswordPolicy::allActivePolicies() as $P) {
                    $id = $P->getBkId();
                    echo sprintf('<option value="%s" %s>%s</option>',
                            $id,
                            (($config['client_passwd_policy'] == $id) ? 'selected="selected"' : ''),
                            $P->getName());
                }
				?>
				</select>
				<font class="error"><?php echo
				$errors['client_passwd_policy']; ?></font>
				<i class="help-tip icon-question-sign" href="#client_password_policy"></i>
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
        <tr><td><?php echo __('Authentication Token'); ?>:</td>
            <td><input type="checkbox" name="allow_auth_tokens" <?php
                if ($config['allow_auth_tokens'])
                    echo 'checked="checked"'; ?>/> <?php
                    echo __('Enable use of authentication tokens to auto-login users'); ?>
            <i class="help-tip icon-question-sign" href="#allow_auth_tokens"></i>
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
    </table>
   </div>
   <div id="templates" class="tab_content hidden">
    <table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <tbody>
<?php
$res = db_query('select distinct(`type`), id, notes, name, updated from '
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
    <div style="padding:2px 5px">
    <a href="#ajax.php/content/<?php echo $id; ?>/manage"
    onclick="javascript:
        $.dialog($(this).attr('href').substr(1), 201);
    return false;" class="pull-left"><i class="icon-file-text icon-2x"
        style="color:#bbb;"></i> </a>
    <span style="display:inline-block;width:90%;width:calc(100% - 32px);padding-left:10px;line-height:1.2em">
    <a href="#ajax.php/content/<?php echo $id; ?>/manage"
    onclick="javascript:
        $.dialog($(this).attr('href').substr(1), 201, null, {size:'large'});
    return false;"><?php
    echo Format::htmlchars($title); ?></a><br/>
        <span class="faded"><?php
        echo Format::display($notes); ?>
        <br><em><?php echo sprintf(__('Last Updated %s'), Format::datetime($upd));
        ?></em></span>
    </div></td></tr><?php
}; ?>
        <tr>
            <th colspan="2">
                <em><b><?php echo __(
                'Authentication and Registration Templates &amp; Pages'); ?></b></em>
            </th>
        </tr>
        <?php $manage_content(__('Guest Ticket Access'), 'access-link'); ?>
        <?php $manage_content(__('Sign-In Page'), 'banner-client'); ?>
        <?php $manage_content(__('Password Reset Email'), 'pwreset-client'); ?>
        <?php $manage_content(__('Please Confirm Email Address Page'), 'registration-confirm'); ?>
        <?php $manage_content(__('Account Confirmation Email'), 'registration-client'); ?>
        <?php $manage_content(__('Account Confirmed Page'), 'registration-thanks'); ?>
</tbody>
</table>
</div>
<p style="text-align:center">
    <input class="button" type="submit" name="submit" value="<?php echo __('Save Changes'); ?>">
    <input class="button" type="reset" name="reset" value="<?php echo __('Reset Changes'); ?>">
</p>
</div>
</form>
