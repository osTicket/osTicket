<?php
if (!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
$info = $qs = array();
if ($email && $_REQUEST['a'] != 'add'){
    $title = __('Update Email Address');
    $action = 'update';
    $submit_text = __('Save Changes');
    $info  = $email->getInfo();
    $info['id'] = $email->getId();
    $qs += array('id' => $email->getId());
}else {
    $title = __('Add New Email Address');
    $action = 'create';
    $submit_text = __('Submit');
    $info['id'] =  0;
    $info['ticket_auto_response'] = isset($info['ticket_auto_response']) ? $info['ticket_auto_response'] : 1;
    $info['message_auto_response'] = isset($info['message_auto_response']) ? $info['message_auto_response'] : 1;
    $qs += array('a' => $_REQUEST['a']);
}
$info = Format::htmlchars(($errors && $_POST) ? $_POST : $info, true);
?>
<h2  style="margin:0 0 10px 2px;"><?php
    echo sprintf('<a href="emails.php">%s</a>',   __('Emails'));
    if ($email) {
        echo sprintf('<small> &mdash; <a href="emails.php?id=%d">%s</a></small>',
        $email->getId(),
        Format::htmlchars($email->getAddress()));
    } else
        echo "<small> &mdash; $title </small>";
?>
</h2>

<form action="emails.php?<?php echo Http::build_query($qs); ?>" method="post" class="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <ul class="tabs">
    <li class="active"><a id="account_tab" href="#account"
        ><i class="icon-envelope"></i>&nbsp <?php echo __('Account'); ?></a></li>
   <?php
   if ($email) { ?>
    <li><a id="mailbox_tab" href="#mailbox"
        ><i class="icon-inbox"></i>&nbsp;<?php echo __('Remote Mailbox');
        ?></a></li>
    <li><a id="smtp_tab" href="#smtp"
        ><i class="icon-reply-all"></i>&nbsp;<?php echo sprintf('%s (%s)',
                __('Outgoing'), __('SMTP'));
        ?></a></li>
    <?php
   } ?>
</ul>
<div class="tab_content" id="account">
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Email Information and Settings');?></strong>:
                <?php  echo __('Changing Email Address will invalidates set Credentials'); ?>
                </em>
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
                <span class="error"> <?php echo $errors['topic_id']; ?> </span>
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
                <em><strong><?php echo __('Internal Notes');?></strong>: <?php
                echo __("Be liberal, they're internal");?> &nbsp;<span class="error">&nbsp;<?php echo $errors['notes']; ?></span></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea class="richtext no-bar" name="notes" cols="21"
                    rows="5" style="width: 60%;"><?php echo Format::viewableImages($info['notes']); ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
</div>
<?php
if ($email) { ?>
<div class="tab_content" id="mailbox" style="display:none;">
   <?php
    $pjax_container = '#holidays';
    include STAFFINC_DIR . 'templates/email-mailbox.tmpl.php';
   ?>
</div>
<div class="tab_content" id="smtp" style="display:none;">
   <?php
    $pjax_container = '#smtp';
    include STAFFINC_DIR . 'templates/email-smtp.tmpl.php';
   ?>
</div>
<?php
} ?>
<p style="text-align:center;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick='window.location.href="emails.php"'>
</p>
</form>
<script type="text/javascript">
$(function() {
    $('a.auth_config').on('click', function(e) {
        var target = $(this).attr('href').substr(1, $(this).attr('href').length);
        var type = $(this).data('type');
        var form = $(this).closest('form');
        if (target !== ''  && type) {
            // Stash form before launching config dialog.
            var action = 'ajax.php/email/<?php echo $info['id']; ?>/stash';
            $.ajax({
                url: action,
                method: 'POST',
                data: $.objectifyForm(form.serializeArray()),
                cache: false,
                success: function(json) {
                    // Launch the auth config dialog
                    $.dialog('ajax.php/email/<?php echo $info['id'];
                            ?>/auth/config/'+type+'/'+target, 201, function (xhr) {
                        $(this).removeClass('save pending').addClass('save success');
                        if (xhr.responseJSON && xhr.responseJSON.redirect) {
                            $(window).unbind('beforeunload');
                            window.location.href = xhr.responseJSON.redirect;
                        }
                    },
                    {size:(target == 'basic') ? 'normal' : 'xl'}
                    );
                }
            });
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
    });
    $('select.emailauth').on('change', function() {
        var selected = $(this).find('option:selected').val();
        var $target = $('a#'+this.name+'_config');
        $target.attr('href', '#'+selected).removeClass('save pending');
        if (selected == '' || $.inArray(selected, ['none', 'mailbox']) != -1) {
            $target.hide();
        } else {
            $target.show();
            if ($target.data('orig') !== selected)
                $target.addClass('save pending');
        }
    });
    $('select#postfetch').on('change', function() {
        var selected = $(this).find('option:selected').val();
        var $target = $('span#archive_folder');
        if (selected == 'archive') {
           $target.show();
        } else {
           $target.hide();
        }
    });
});
</script>
