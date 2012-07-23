<form action="settings.php?t=emails" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="emails" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4>Email Settings</h4>
                <em>Note that some of the global settings can be overwritten at department/email level.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">Default System Email:</td>
            <td>
                <select name="default_email_id">
                    <option value=0 disabled>Select One</option>
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
            </td>
        </tr>
        <tr>
            <td width="180" class="required">Default Alert Email:</td>
            <td>
                <select name="alert_email_id">
                    <option value="0" selected="selected">Use Default System Email (above)</option>
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
            </td>
        </tr>
        <tr>
            <td width="180" class="required">Admin Email Address:</td>
            <td>
                <input type="text" size=40 name="admin_email" value="<?php echo $config['admin_email']; ?>">
                    &nbsp;<font class="error">*&nbsp;<?php echo $errors['admin_email']; ?></font></td>
        </tr>
        <tr><th colspan=2><em><strong>Incoming Emails</strong>: For mail fetcher (POP/IMAP) to work you must set a cron job or enable auto-cron</em></th>
        <tr>
            <td width="180">Email Polling:</td>
            <td><input type="checkbox" name="enable_mail_polling" value=1 <?php echo $config['enable_mail_polling']? 'checked="checked"': ''; ?>  > Enable POP/IMAP
                 &nbsp;&nbsp;<em>(Global setting which can be disabled at email level)</em>
            </td>
        </tr>
        <tr>
            <td width="180">Email Piping:</td>
            <td><input type="checkbox" name="enable_email_piping" value=1 <?php echo $config['enable_email_piping']? 'checked="checked"': ''; ?>> Enable email piping
                 &nbsp;&nbsp;<em>(You pipe we accept policy)</em>
            </td>
        </tr>
        <tr>
            <td width="180">Strip Quoted Reply:</td>
            <td>
                <input type="checkbox" name="strip_quoted_reply" <?php echo $config['strip_quoted_reply'] ? 'checked="checked"':''; ?>>
                <em>(depends on the reply separator tag set below)</em>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['strip_quoted_reply']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="180">Reply Separator Tag:</td>
            <td><input type="text" name="reply_separator" value="<?php echo $config['reply_separator']; ?>">
                &nbsp;<font class="error">&nbsp;<?php echo $errors['reply_separator']; ?></font>
            </td>
        </tr>
        <tr><th colspan=2><em><strong>Outgoing Emails</strong>: Default email only applies to outgoing emails without SMTP setting.</em></th></tr>
        <tr><td width="180">Default Outgoing Email:</td>
            <td>
                <select name="default_smtp_id">
                    <option value=0 selected="selected">None: Use PHP mail function</option>
                    <?php
                    $sql='SELECT email_id,email,name,smtp_host FROM '.EMAIL_TABLE.' WHERE smtp_active=1';

                    if(($res=db_query($sql)) && db_num_rows($res)) {
                        while (list($id,$email,$name,$host) = db_fetch_row($res)){
                            $email=$name?"$name &lt;$email&gt;":$email;
                            ?>
                            <option value="<?php echo $id; ?>"<?php echo ($config['default_smtp_id']==$id)?'selected="selected"':''; ?>><?php echo $email; ?></option>
                        <?php
                        }
                    } ?>
                 </select>&nbsp;&nbsp;<font class="error">&nbsp;<?php echo $errors['default_smtp_id']; ?></font>
           </td>
       </tr>
    </tbody>
</table>
<p style="padding-left:250px;">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes">
</p>
</form>
