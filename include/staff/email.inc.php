<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
$info = $qs = array();
if($email && $_REQUEST['a']!='add'){
    $title=__('Update Email Address');
    $action='update';
    $submit_text=__('Save Changes');
    $info=$email->getInfo();
    $info['id']=$email->getId();
    if($info['mail_delete'])
        $info['postfetch']='delete';
    elseif($info['mail_archivefolder'])
        $info['postfetch']='archive';
    else
        $info['postfetch']=''; //nothing.
    if($info['userpass'])
        $passwdtxt=__('To change password enter new password above.');

    $qs += array('id' => $email->getId());
}else {
    $title=__('Add New Email Address');
    $action='create';
    $submit_text=__('Submit');
    $info['ispublic']=isset($info['ispublic'])?$info['ispublic']:1;
    $info['ticket_auto_response']=isset($info['ticket_auto_response'])?$info['ticket_auto_response']:1;
    $info['message_auto_response']=isset($info['message_auto_response'])?$info['message_auto_response']:1;
    if (!$info['mail_fetchfreq'])
        $info['mail_fetchfreq'] = 5;
    if (!$info['mail_fetchmax'])
        $info['mail_fetchmax'] = 10;
    if (!isset($info['smtp_auth']))
        $info['smtp_auth'] = 1;
    $qs += array('a' => $_REQUEST['a']);
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info, true);
?>
<h2><?php echo $title; ?>
    <?php if (isset($info['email'])) { ?><small>
    — <?php echo $info['email']; ?></small>
    <?php } ?>
</h2>
<form action="emails.php?<?php echo Http::build_query($qs); ?>" method="post" class="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Email Information and Settings');?></strong></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
                <?php echo __('Email Address');?>
            </td>
            <td>
                <input type="text" size="35" name="email" value="<?php echo $info['email']; ?>"
                    autofocus>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['email']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Email Name');?>
            </td>
            <td>
                <input type="text" size="35" name="name" value="<?php echo $info['name']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['name']; ?>&nbsp;</span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('New Ticket Settings'); ?></strong></em>
            </th>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Department');?>
            </td>
            <td>
        <span>
			<select name="dept_id">
			    <option value="0" selected="selected">&mdash; <?php
                echo __('System Default'); ?> &mdash;</option>
			    <?php
                if ($depts=Dept::getPublicDepartments()) {
                  if($info['dept_id'] && !array_key_exists($info['dept_id'], $depts))
                  {
                    $depts[$info['dept_id']] = $email->dept;
                    $warn = sprintf(__('%s selected must be active'), __('Department'));
                  }

                    foreach ($depts as $id => $name) {
				        $selected=($info['dept_id'] && $id==$info['dept_id'])?'selected="selected"':'';
				        echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$name);
				    }
			    }
			    ?>
			</select>
      <?php
      if($warn) { ?>
          &nbsp;<span class="error">*&nbsp;<?php echo $warn; ?></span>
      <?php } ?>
			<i class="help-tip icon-question-sign" href="#new_ticket_department"></i>
        </span>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Priority'); ?>
            </td>
            <td>
		<span>
			<select name="priority_id">
			    <option value="0" selected="selected">&mdash; <?php
                echo __('System Default'); ?> &mdash;</option>
			    <?php
			    $sql='SELECT priority_id, priority_desc FROM '.PRIORITY_TABLE.' pri ORDER by priority_urgency DESC';
			    if(($res=db_query($sql)) && db_num_rows($res)){
				while(list($id,$name)=db_fetch_row($res)){
				    $selected=($info['priority_id'] && $id==$info['priority_id'])?'selected="selected"':'';
				    echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$name);
				}
			    }
			    ?>
			</select>
			<i class="help-tip icon-question-sign" href="#new_ticket_priority"></i>
		</span>
		&nbsp;<span class="error"><?php echo $errors['priority_id']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Help Topic'); ?>
            </td>
            <td>
		<span>
			<select name="topic_id">
                <option value="0" selected="selected">&mdash; <?php echo __('System Default'); ?> &mdash;</option>
			    <?php
                    $warn = '';
                    $topics = Topic::getHelpTopics();
                    if($info['topic_id'] && !array_key_exists($info['topic_id'], $topics)) {
                      $topics[$info['topic_id']] = $email->topic;
                      $warn = sprintf(__('%s selected must be active'), __('Help Topic'));
                    }
                    foreach ($topics as $id=>$topic) { ?>
                        <option value="<?php echo $id; ?>"<?php echo ($info['topic_id']==$id)?'selected':''; ?>><?php echo $topic; ?></option>
                    <?php
                    } ?>
			</select>
      <?php
      if($warn) { ?>
          &nbsp;<span class="error">*&nbsp;<?php echo $warn; ?></span>
      <?php } ?>
			<i class="help-tip icon-question-sign" href="#new_ticket_help_topic"></i>
		</span>
                <span class="error">
			<?php echo $errors['topic_id']; ?>
		</span>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Auto-Response'); ?>
            </td>
            <td>
                <label><input type="checkbox" name="noautoresp" value="1" <?php echo $info['noautoresp']?'checked="checked"':''; ?> >
                <?php echo sprintf(__('<strong>Disable</strong> for %s'), __('this email')); ?>
                </label>
                <i class="help-tip icon-question-sign" href="#auto_response"></i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Email Login Information'); ?></strong>
                &nbsp;<i class="help-tip icon-question-sign" href="#login_information"></i></em>
            </th>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Username'); ?>
            </td>
            <td>
                <input type="text" size="35" name="userid" value="<?php echo $info['userid']; ?>"
                    autocomplete="off" autocorrect="off">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['userid']; ?>&nbsp;</span>
                <i class="help-tip icon-question-sign" href="#userid"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
               <?php echo __('Password'); ?>
            </td>
            <td>
                <input type="password" size="35" name="passwd" value="<?php echo $info['passwd']; ?>"
                    autocomplete="new-password">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd']; ?>&nbsp;</span>
                <br><em><?php echo $passwdtxt; ?></em>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Fetching Email via IMAP or POP'); ?></strong>
                &nbsp;<i class="help-tip icon-question-sign" href="#mail_account"></i>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['mail']; ?></font></em>
            </th>
        </tr>
        <tr>
            <td><?php echo __('Status'); ?></td>
            <td>
                <label><input type="radio" name="mail_active"  value="1"   <?php echo $info['mail_active']?'checked="checked"':''; ?> />&nbsp;<?php echo __('Enable'); ?></label>
                &nbsp;&nbsp;
                <label><input type="radio" name="mail_active"  value="0"   <?php echo !$info['mail_active']?'checked="checked"':''; ?> />&nbsp;<?php echo __('Disable'); ?></label>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['mail_active']; ?></font>
            </td>
        </tr>
        <tr><td><?php echo __('Hostname'); ?></td>
            <td>
		<span>
			<input type="text" name="mail_host" size=35 value="<?php echo $info['mail_host']; ?>">
			&nbsp;<font class="error">&nbsp;<?php echo $errors['mail_host']; ?></font>
			<i class="help-tip icon-question-sign" href="#host_and_port"></i>
		</span>
            </td>
        </tr>
        <tr><td><?php echo __('Mail Folder'); ?></td>
            <td>
                <span>
                        <input type="text" name="mail_folder" size=20 value="<?php echo $info['mail_folder']; ?>"
                            placeholder="INBOX">
                        &nbsp;<font class="error">&nbsp;<?php echo $errors['mail_folder']; ?></font>
                        <i class="help-tip icon-question-sign" href="#mail_folder"></i>
                </span>
            </td>
        </tr>
        <tr><td><?php echo __('Port Number'); ?></td>
            <td><input type="text" name="mail_port" size=6 value="<?php echo $info['mail_port']?$info['mail_port']:''; ?>">
		<span>
			&nbsp;<font class="error">&nbsp;<?php echo $errors['mail_port']; ?></font>
			<i class="help-tip icon-question-sign" href="#host_and_port"></i>
		</span>
            </td>
        </tr>
        <tr><td><?php echo __('Mail Box Protocol'); ?></td>
            <td>
		<span>
			<select name="mail_proto">
                <option value=''>&mdash; <?php echo __('Select protocol'); ?> &mdash;</option>
<?php
    foreach (MailFetcher::getSupportedProtos() as $proto=>$desc) { ?>
                <option value="<?php echo $proto; ?>" <?php
                    if ($info['mail_proto'] == $proto) echo 'selected="selected"';
                    ?>><?php echo $desc; ?></option>
<?php } ?>
			</select>
			<font class="error">&nbsp;<?php echo $errors['mail_protocol']; ?></font>
			<i class="help-tip icon-question-sign" href="#protocol"></i>
		</span>
            </td>
        </tr>

        <tr><td><?php echo __('Fetch Frequency'); ?></td>
            <td>
		<span>
            <input type="text" name="mail_fetchfreq" size=4 value="<?php echo $info['mail_fetchfreq']?$info['mail_fetchfreq']:''; ?>"> <?php echo __('minutes'); ?>
			<i class="help-tip icon-question-sign" href="#fetch_frequency"></i>
			&nbsp;<font class="error">&nbsp;<?php echo $errors['mail_fetchfreq']; ?></font>
		</span>
            </td>
        </tr>
        <tr><td><?php echo __('Emails Per Fetch'); ?></td>
            <td>
		<span>
			<input type="text" name="mail_fetchmax" size=4 value="<?php echo
            $info['mail_fetchmax']?$info['mail_fetchmax']:''; ?>">
			<i class="help-tip icon-question-sign" href="#emails_per_fetch"></i>
			&nbsp;<font class="error">&nbsp;<?php echo $errors['mail_fetchmax']; ?></font>
		</span>
            </td>
        </tr>
        <tr><td valign="top"><?php echo __('Fetched Emails');?></td>
             <td>
                <label><input type="radio" name="postfetch" value="archive" <?php echo ($info['postfetch']=='archive')? 'checked="checked"': ''; ?> >
                <?php echo __('Move to folder'); ?>:
                <input type="text" name="mail_archivefolder" size="20" value="<?php echo $info['mail_archivefolder']; ?>"/></label>
                    &nbsp;<font class="error"><?php echo $errors['mail_archivefolder']; ?></font>
                    <i class="help-tip icon-question-sign" href="#fetched_emails"></i>
                <br/>
                <label><input type="radio" name="postfetch" value="delete" <?php echo ($info['postfetch']=='delete')? 'checked="checked"': ''; ?> >
                <?php echo __('Delete emails'); ?></label>
                <br/>
                <label><input type="radio" name="postfetch" value="" <?php echo (isset($info['postfetch']) && !$info['postfetch'])? 'checked="checked"': ''; ?> >
                <?php echo __('Do nothing <em>(not recommended)</em>'); ?></label>
              <br /><font class="error"><?php echo $errors['postfetch']; ?></font>
            </td>
        </tr>

        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Sending Email via SMTP'); ?></strong>
                &nbsp;<i class="help-tip icon-question-sign" href="#smtp_settings"></i>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['smtp']; ?></font></em>
            </th>
        </tr>
        <tr><td><?php echo __('Status');?></td>
            <td>
                <label><input type="radio" name="smtp_active" value="1" <?php echo $info['smtp_active']?'checked':''; ?> />&nbsp;<?php echo __('Enable');?></label>
                &nbsp;
                <label><input type="radio" name="smtp_active" value="0" <?php echo !$info['smtp_active']?'checked':''; ?> />&nbsp;<?php echo __('Disable');?></label>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['smtp_active']; ?></font>
            </td>
        </tr>
        <tr><td><?php echo __('Hostname'); ?></td>
            <td><input type="text" name="smtp_host" size=35 value="<?php echo $info['smtp_host']; ?>">
                &nbsp;<font class="error"><?php echo $errors['smtp_host']; ?></font>
			<i class="help-tip icon-question-sign" href="#host_and_port"></i>
            </td>
        </tr>
        <tr><td><?php echo __('Port Number'); ?></td>
            <td><input type="text" name="smtp_port" size=6 value="<?php echo $info['smtp_port']?$info['smtp_port']:''; ?>">
                &nbsp;<font class="error"><?php echo $errors['smtp_port']; ?></font>
			<i class="help-tip icon-question-sign" href="#host_and_port"></i>
            </td>
        </tr>
        <tr><td><?php echo __('Authentication Required'); ?></td>
            <td>

                 <label><input type="radio" name="smtp_auth"  value="1"
                     <?php echo $info['smtp_auth']?'checked':''; ?> /> <?php echo __('Yes'); ?></label>
                 &nbsp;
                 <label><input type="radio" name="smtp_auth"  value="0"
                     <?php echo !$info['smtp_auth']?'checked':''; ?> /> <?php echo __('No'); ?></label>
                 &nbsp;
                 <label><input type="checkbox" name="smtp_auth_creds" value="1"
                     <?php echo $info['smtp_auth_creds']?'checked':''; ?> /> <?php echo __('Use Separate Authentication'); ?></label>
                       <i class="help-tip icon-question-sign" href="#smtp_auth_creds"></i>
                <font class="error">&nbsp;<?php echo $errors['smtp_auth']; ?></font>
            </td>
        </tr>
        <tr style="display:none;" class="smtp"><td><?php echo __('Username'); ?></td>
            <td>
                <input type="text" size="35" name="smtp_userid" value="<?php echo $info['smtp_userid']; ?>"
                    autocomplete="off" autocorrect="off">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['smtp_userid']; ?>&nbsp;</span>
            </td>
        </tr>
        <tr style="display:none;" class="smtp"><td><?php echo __('Password'); ?></td>
            <td>
                <input type="password" size="35" name="smtp_passwd" value="<?php echo $info['smtp_passwd']; ?>"
                    autocomplete="new-password">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['smtp_passwd']; ?>&nbsp;</span>
                <br><em><?php if ($info['smtp_userpass']) echo $passwdtxt; ?></em>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Header Spoofing'); ?></td>
            <td>
                <label><input type="checkbox" name="smtp_spoofing" value="1" <?php echo $info['smtp_spoofing'] ?'checked="checked"':''; ?>>
                <?php echo sprintf(__('Allow for %s'), __('this email')); ?></label>
                <i class="help-tip icon-question-sign" href="#header_spoofing"></i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Internal Notes');?></strong>: <?php
                echo __("Be liberal, they're internal");?> &nbsp;<span class="error">&nbsp;<?php echo $errors['notes']; ?></span></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea class="richtext no-bar" name="notes" cols="21"
                    rows="5" style="width: 60%;"><?php echo $info['notes']; ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
<p style="text-align:center;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick='window.location.href="emails.php"'>
</p>
</form>
<script type="text/javascript">
// SMTP Authentication Credentials
$(document).ready(function(){
    $('input[name=smtp_auth], input[name=smtp_auth_creds]').bind('change', function(){
        // Toggle Auth Checkbox
        if ($('input[name=smtp_auth]:checked').val() == 1) {
            $('input[name=smtp_auth_creds]').removeAttr('disabled');
        } else {
            $('input[name=smtp_auth_creds]').attr('disabled', true);
        }
        // Toggle Auth Input Visibility
        if ($('input[name=smtp_auth_creds]:checked').val() == 1
              && $('input[name=smtp_auth]:checked').val() == 1)
            $('.smtp').show();
        else
            $('.smtp').hide();
    });
    $('input[name=smtp_auth], input[name=smtp_auth_creds]').trigger('change');
});
</script>
