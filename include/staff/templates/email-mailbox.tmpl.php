<?php
if (!$email) return;

if (isset($errors['mailbox_auth'])) {
    echo sprintf('<p id="msg_error">%s</p>', $errors['mailbox_auth']);
}
?>
<div>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <tbody>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Mailbox Setting'); ?></strong>
                &nbsp;<i class="help-tip icon-question-sign" href="#mail_account"></i>
                &nbsp;<font class="error"><?php echo $errors['mailbox_err']; ?></font></em>
            </th>
        </tr>
    </tbody>
    <tbody>
        <tr><td><?php echo __('Hostname'); ?></td>
            <td>
		<span>
			<input type="text" name="mailbox_host" size=35 value="<?php echo $info['mailbox_host']; ?>">
			<i class="help-tip icon-question-sign" href="#host_and_port"></i>
			&nbsp;<font class="error"><?php echo $errors['mailbox_host']; ?></font>
		</span>
            </td>
        </tr>
        <tr><td><?php echo __('Port Number'); ?></td>
            <td><input type="text" name="mailbox_port" size=6 value="<?php
            echo $info['mailbox_port'] ?: ''; ?>">
		<span>
            <i class="help-tip icon-question-sign" href="#host_and_port"></i>
			&nbsp;<font class="error"><?php echo $errors['mailbox_port']; ?></font>
		</span>
            </td>
        </tr>
        <tr><td><?php echo __('Mail Folder'); ?></td>
            <td>
                <span>
                        <input type="text" name="mailbox_folder" size=20 value="<?php echo $info['mailbox_folder']; ?>"
                            placeholder="INBOX">
                        <i class="help-tip icon-question-sign" href="#mailbox_folder"></i>
                        &nbsp;<font class="error"><?php echo $errors['mailbox_folder']; ?></font>
                </span>
            </td>
        </tr>
        <tr><td><?php echo __('Protocol'); ?></td>
            <td>
		<span>
			<select name="mailbox_protocol">
                <option value=''>&mdash; <?php echo __('Select protocol'); ?> &mdash;</option>
<?php
    foreach (Email::mailboxProtocols() as $proto => $desc) {
?>
                <option value="<?php echo $proto; ?>" <?php
                    if ($info['mailbox_protocol'] == $proto) echo 'selected="selected"';
                    ?>><?php echo $desc; ?></option>
<?php } ?>
			</select>
			<i class="help-tip icon-question-sign" href="#protocol"></i>
			<font class="error"><?php echo $errors['mailbox_protocol']; ?></font>
		</span>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo __('Authentication'); ?>
            </td>
            <td>
			<select class="emailauth" name="mailbox_auth_bk">
                <option value=''>&mdash; <?php echo __('Select Type'); ?> &mdash;</option>
<?php
    foreach (Email::getSupportedAuthTypes() as $auth => $desc) { ?>
                <option value="<?php echo $auth; ?>" <?php
                    if ($info['mailbox_auth_bk'] == $auth) echo 'selected="selected"';
                    ?>><?php echo $desc; ?></option>
<?php } ?>
			</select>
                <i class="help-tip icon-question-sign" href="#authentication"></i>
                <a class="action-button auth_config" id="mailbox_auth_bk_config"
                data-type="mailbox"
                data-orig="<?php echo $info['mailbox_auth_bk'];?>"
                style="overflow:inherit; display:<?php echo
                $info['mailbox_auth_bk'] ? 'block-inline': 'none' ?>;"
                href="#<?php echo $info['mailbox_auth_bk']; ?>">
                    <i class="icon-edit"></i> <?php echo __('Config'); ?>
                </a>
                &nbsp;<font class="error"><?php echo
                $errors['mailbox_auth_bk']; ?></font>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Email Fetching');
                ?></strong></em>
            </th>
        </tr>
        <tr>
            <td width="180"><?php echo __('Status'); ?></td>
            <td>
                <label><input type="radio" name="mailbox_active"  value="1" <?php
                    echo $info['mailbox_active'] ? 'checked="checked"' : '';
                    ?> />&nbsp;<?php
                    echo __('Enable'); ?></label>
                &nbsp;&nbsp;
                <label><input type="radio" name="mailbox_active"  value="0" <?php
                    echo !$info['mailbox_active'] ? 'checked="checked"' : '';
                    ?>/>&nbsp;<?php
                    echo __('Disable'); ?></label>
                &nbsp;<font class="error"><?php echo $errors['mailbox_active']; ?></font>
            </td>
        </tr>
        <tr><td><?php echo __('Fetch Frequency'); ?></td>
            <td>
		<span>
            <input type="text" name="mailbox_fetchfreq" size=4 value="<?php
                echo $info['mailbox_fetchfreq'] ?: ''; ?>"> <?php echo __('minutes'); ?>
			<i class="help-tip icon-question-sign" href="#fetch_frequency"></i>
			&nbsp;<font class="error"><?php echo $errors['mailbox_fetchfreq']; ?></font>
		</span>
            </td>
        </tr>
        <tr><td><?php echo __('Emails Per Fetch'); ?></td>
            <td>
		<span>
			<input type="text" name="mailbox_fetchmax" size=4 value="<?php echo
            $info['mailbox_fetchmax']?$info['mailbox_fetchmax']:''; ?>">
			<i class="help-tip icon-question-sign" href="#emails_per_fetch"></i>
			&nbsp;<font class="error"><?php echo $errors['mailbox_fetchmax']; ?></font>
		</span>
            </td>
        </tr>
        <tr><td valign="top"><?php echo __('Fetched Emails');?></td>
             <td>
                <select id="postfetch" name="mailbox_postfetch">
                    <option value=''>&mdash; <?php echo __('Select Action'); ?> &mdash;</option>
                    <?php
                    $actions = [
                        'archive' => sprintf('%s - %s',
                                __('Archive'), __('Move to folder')),
                        'delete' => __('Delete emails'),
                        'nothing' => sprintf('%s (%s)',
                                 __('Do Nothing'), __('not recommended'))];
                    foreach ($actions as $action => $desc) {?>
                    <option value="<?php echo $action;?>" <?php echo
                        ($info['mailbox_postfetch'] == $action) ?  'selected="selected"': ''; ?>>
                        <?php echo $desc;?></option>
                    <?php
                    } ?>
                </select>
                <i class="help-tip icon-question-sign" href="#fetched_emails"></i>
                <span id="archive_folder" style="margin-left:5px; overflow:inherit; display:<?php
                echo $info['mailbox_postfetch'] == 'archive' ? 'block-inline': 'none'; ?>;">
                   <input type="text" name="mailbox_archivefolder" size="20" value="<?php echo $info['mailbox_archivefolder']; ?>"/></label>
                    &nbsp;<font class="error"><?php echo $errors['mailbox_archivefolder']; ?></font>
                </span><br>
                <font class="error"><?php echo $errors['mailbox_postfetch']; ?></font>
            </td>
        </tr>
    </tbody>
 </table>
</div>
