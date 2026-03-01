<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');
?>
<h2><?php echo __('Email Settings and Options');?></h2>
<form action="emailsettings.php" method="post" class="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="emails" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><?php echo __('Note that some of the global settings can be overridden at department/email level.');?></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required"><?php echo __('Default Template Set'); ?>:</td>
            <td>
                <select name="default_template_id">
                    <option value="">&mdash; <?php echo __('Select Default Email Template Set'); ?> &mdash;</option>
                    <?php
                    $sql='SELECT tpl_id, name FROM '.EMAIL_TEMPLATE_GRP_TABLE
                        .' WHERE isactive =1 ORDER BY name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while (list($id, $name) = db_fetch_row($res)){
                            $selected = ($config['default_template_id']==$id)?'selected="selected"':''; ?>
                            <option value="<?php echo $id; ?>"<?php echo $selected; ?>><?php echo $name; ?></option>
                        <?php
                        }
                    } ?>
                </select>&nbsp;<font class="error">*&nbsp;<?php echo $errors['default_template_id']; ?></font>
                <i class="help-tip icon-question-sign" href="#default_email_templates"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required"><?php echo __('Default System Email');?>:</td>
            <td>
                <select name="default_email_id">
                    <option value=0 disabled><?php echo __('Select One');?></option>
                    <?php
                    $sql='SELECT email_id,email,name FROM '.EMAIL_TABLE;
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while (list($id,$email,$name) = db_fetch_row($res)){
                            $email=$name?"$name &lt;$email&gt;":$email;
                            ?>
                            <option value="<?php echo $id; ?>"<?php echo ($config['default_email_id']==$id)?'selected="selected"':''; ?>><?php echo $email; ?></option>
                        <?php
                        }
                    } ?>
                 </select>
                 &nbsp;<font class="error">*&nbsp;<?php echo $errors['default_email_id']; ?></font>
                <i class="help-tip icon-question-sign" href="#default_system_email"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required"><?php echo __('Default Alert Email');?>:</td>
            <td>
                <select name="alert_email_id">
                    <option value="0" selected="selected"><?php echo __('Use Default System Email (above)');?></option>
                    <?php
                    $sql='SELECT email_id,email,name FROM '.EMAIL_TABLE.' WHERE email_id != '.db_input($config['default_email_id']);
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while (list($id,$email,$name) = db_fetch_row($res)){
                            $email=$name?"$name &lt;$email&gt;":$email;
                            ?>
                            <option value="<?php echo $id; ?>"<?php echo ($config['alert_email_id']==$id)?'selected="selected"':''; ?>><?php echo $email; ?></option>
                        <?php
                        }
                    } ?>
                 </select>
                 &nbsp;<font class="error">*&nbsp;<?php echo $errors['alert_email_id']; ?></font>
                <i class="help-tip icon-question-sign" href="#default_alert_email"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required"><?php echo __("Admin's Email Address");?>:</td>
            <td>
                <input type="text" size=40 name="admin_email" value="<?php echo $config['admin_email']; ?>">
                    &nbsp;<font class="error">*&nbsp;<?php echo $errors['admin_email']; ?></font>
                <i class="help-tip icon-question-sign" href="#admins_email_address"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required"><?php echo __("Verify Email Addresses");?>:</td>
            <td>
                <input type="checkbox" name="verify_email_addrs" <?php
                    if ($config['verify_email_addrs']) echo 'checked="checked"'; ?>>
                <?php echo __('Verify email address domain'); ?>
                <i class="help-tip icon-question-sign" href="#verify_email_addrs"></i>
            </td>
        </tr>
        <tr><th colspan=2><em><strong><?php echo __('Incoming Emails'); ?>:</strong>&nbsp;
            </em></th>
        <tr>
            <td width="180"><?php echo __('Email Fetching'); ?>:</td>
            <td><input type="checkbox" name="enable_mail_polling" value=1 <?php echo $config['enable_mail_polling']? 'checked="checked"': ''; ?>>
                <?php echo __('Enable'); ?>
                <i class="help-tip icon-question-sign" href="#email_fetching"></i>
                &nbsp;
                 <input type="checkbox" name="enable_auto_cron" <?php echo $config['enable_auto_cron']?'checked="checked"':''; ?>>
                <?php echo __('Fetch on auto-cron'); ?>&nbsp;
                <i class="help-tip icon-question-sign" href="#enable_autocron_fetch"></i>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Strip Quoted Reply');?>:</td>
            <td>
                <input type="checkbox" name="strip_quoted_reply" <?php echo $config['strip_quoted_reply'] ? 'checked="checked"':''; ?>>
                <?php echo __('Enable'); ?>
                <i class="help-tip icon-question-sign" href="#strip_quoted_reply"></i>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['strip_quoted_reply']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Reply Separator Tag');?>:</td>
            <td><input type="text" name="reply_separator" value="<?php echo $config['reply_separator']; ?>">
                &nbsp;<font class="error">&nbsp;<?php echo $errors['reply_separator']; ?></font>&nbsp;<i class="help-tip icon-question-sign" href="#reply_separator_tag"></i>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Emailed Tickets Priority'); ?>:</td>
            <td>
                <input type="checkbox" name="use_email_priority" value="1" <?php echo $config['use_email_priority'] ?'checked="checked"':''; ?>>
                &nbsp;<?php echo __('Enable'); ?>&nbsp;
                <i class="help-tip icon-question-sign" href="#emailed_tickets_priority"></i>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Accept All Emails'); ?>:</td>
            <td><input type="checkbox" name="accept_unregistered_email" <?php
                echo $config['accept_unregistered_email'] ? 'checked="checked"' : ''; ?>/>
                <?php echo __('Accept email from unknown Users'); ?>
                <i class="help-tip icon-question-sign" href="#accept_all_emails"></i>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Accept Email Collaborators'); ?>:</td>
            <td><input type="checkbox" name="add_email_collabs" <?php
            echo $config['add_email_collabs'] ? 'checked="checked"' : ''; ?>/>
            <?php echo __('Automatically add collaborators from email fields'); ?>&nbsp;
            <i class="help-tip icon-question-sign" href="#accept_email_collaborators"></i>
        </tr>
        <tr><th colspan=2><em><strong><?php echo __('Outgoing Email');?></strong>: <?php echo __('Default email only applies to outgoing emails without SMTP setting.');?></em></th></tr>
        <tr><td width="180"><?php echo __('Default MTA'); ?>:</td>
            <td>
                <select name="default_smtp_id">
                    <option value=0 selected="selected"><?php echo __('None: Use PHP mail function');?></option>
                    <?php
                    $sql=' SELECT email_id, email, name, smtp_host '
                        .' FROM '.EMAIL_TABLE.' WHERE smtp_active = 1';
                    if(($res=db_query($sql)) && db_num_rows($res)) {
                        while (list($id, $email, $name, $host) = db_fetch_row($res)){
                            $email=$name?"$name &lt;$email&gt;":$email;
                            ?>
                            <option value="<?php echo $id; ?>"<?php echo ($config['default_smtp_id']==$id)?'selected="selected"':''; ?>><?php echo $email; ?></option>
                        <?php
                        }
                    } ?>
                 </select>&nbsp;<font class="error">&nbsp;<?php echo $errors['default_smtp_id']; ?></font>
                 <i class="help-tip icon-question-sign" href="#default_mta"></i>
           </td>
       </tr>
        <tr>
            <td width="180"><?php echo __('Attachments');?>:</td>
            <td>
                <input type="checkbox" name="email_attachments" <?php echo $config['email_attachments']?'checked="checked"':''; ?>>
                <?php echo __('Email attachments to the user'); ?>
                <i class="help-tip icon-question-sign" href="#ticket_response_files"></i>
            </td>
        </tr>
    </tbody>
</table>
<p style="text-align:center;">
    <input class="button" type="submit" name="submit" value="<?php echo __('Save Changes');?>">
    <input class="button" type="reset" name="reset" value="<?php echo __('Reset Changes');?>">
</p>
</form>
