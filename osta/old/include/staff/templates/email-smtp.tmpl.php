<?php
if (!$email) return;

if (isset($errors['smtp_auth'])) {
    echo sprintf('<p id="msg_error">%s</p>', $errors['smtp_auth']);
}
?>
<div>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <tbody>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('SMTP Settings'); ?></strong>
                &nbsp;<i class="help-tip icon-question-sign" href="#smtp_settings"></i>
                &nbsp;<font class="error"><?php echo $errors['smtp_err']; ?></font></em>
            </th>
        </tr>
        <tr><td width="180"><?php echo __('Status');?></td>
            <td>
                <label><input type="radio" name="smtp_active" value="1"
                <?php echo $info['smtp_active'] ? 'checked' : ''; ?>/>&nbsp;<?php echo __('Enable');?></label>
                &nbsp;
                <label><input type="radio" name="smtp_active" value="0"
                <?php echo !$info['smtp_active'] ? 'checked' : ''; ?>/>&nbsp;<?php echo __('Disable');?></label>
                &nbsp;<font class="error"><?php echo $errors['smtp_active']; ?></font>
            </td>
        </tr>
    </tbody>
    <tbody>
        <tr><td><?php echo __('Hostname'); ?></td>
            <td><input type="text" name="smtp_host" size=35 value="<?php echo $info['smtp_host']; ?>">
                &nbsp;<font class="error"><?php echo $errors['smtp_host']; ?></font>
			<i class="help-tip icon-question-sign" href="#host_and_port"></i>
            </td>
        </tr>
        <tr><td><?php echo __('Port Number'); ?></td>
            <td><input type="text" name="smtp_port" size=6 value="<?php echo
            $info['smtp_port'] ?: ''; ?>">
                &nbsp;<font class="error"><?php echo $errors['smtp_port']; ?></font>
			<i class="help-tip icon-question-sign" href="#host_and_port"></i>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo __('Authentication'); ?>
            </td>
            <td>
            <select class="emailauth" name="smtp_auth_bk">
<?php
    $info['smtp_auth_bk'] = $info['smtp_auth_bk'] ?: 'mailbox';
    foreach (Email::getSupportedSMTPAuthTypes() as $auth => $desc) { ?>
                <option value="<?php echo $auth; ?>" <?php
                    if ($info['smtp_auth_bk'] == $auth) echo 'selected="selected"';
                    ?>><?php echo $desc; ?></option>
<?php } ?>
            </select>
                <i class="help-tip icon-question-sign" href="#authentication"></i>
                <a class="action-button auth_config" id="smtp_auth_bk_config"
                data-type="smtp"
                data-orig="<?php echo $info['smtp_auth_bk'];?>"
                style="overflow:inherit; display:<?php echo
                !in_array($info['smtp_auth_bk'], ['none','mailbox']) ?
                'block-inline' : 'none'; ?>;" href="#<?php echo
                $info['smtp_auth_bk'];?>">
                    <i class="icon-edit"></i> <?php echo __('Config'); ?>
                </a>
                <br><font class="error"><?php echo
                $errors['smtp_auth_bk']; ?></font>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Header Spoofing'); ?></td>
            <td>
                <label><input type="checkbox" name="smtp_allow_spoofing"
                value="1" <?php echo $info['smtp_allow_spoofing'] ? 'checked="checked"' : ''; ?>>
                <?php echo sprintf(__('Allow for %s'), __('this email')); ?></label>
                <i class="help-tip icon-question-sign" href="#header_spoofing"></i>
            </td>
        </tr>
    </tbody>
</table>
</div>
