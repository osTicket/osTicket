<?php
if(!defined('OSTSTAFFINC') || !$staff || !$thisstaff) die('Access Denied');
?>

<form action="profile.php" method="post" class="save" autocomplete="off">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="update">
 <input type="hidden" name="id" value="<?php echo $staff->getId(); ?>">
<h2><?php echo __('My Account Profile');?></h2>
  <ul class="clean tabs">
    <li class="active"><a href="#account"><i class="icon-user"></i> <?php echo __('Account'); ?></a></li>
    <li><a href="#preferences"><?php echo __('Preferences'); ?></a></li>
    <li><a href="#signature"><?php echo __('Signature'); ?></a></li>
  </ul>

  <div class="tab_content" id="account">
    <table class="table two-column" width="940" border="0" cellspacing="0" cellpadding="2">
      <tbody>
        <tr><td colspan="2"><div>
        <div class="avatar pull-left" style="margin: 10px 15px; width: 100px; height: 100px;">
<?php       $avatar = $staff->getAvatar();
            echo $avatar;
if ($avatar->isChangeable()) { ?>
          <div style="text-align: center">
            <a class="button no-pjax"
                href="#ajax.php/staff/<?php echo $staff->getId(); ?>/avatar/change"
                onclick="javascript:
    event.preventDefault();
    var $a = $(this),
        form = $a.closest('form');
    $.ajax({
      url: $a.attr('href').substr(1),
      dataType: 'json',
      success: function(json) {
        if (!json || !json.code)
          return;
        var code = form.find('[name=avatar_code]');
        if (!code.length)
          code = form.append($('<input>').attr({type: 'hidden', name: 'avatar_code'}));
        code.val(json.code).trigger('change');
        $a.closest('.avatar').find('img').replaceWith($(json.img));
      }
    });
    return false;"><i class="icon-retweet"></i></a>
          </div>
<?php
} ?>
        </div>
        <table class="table two-column" border="0" cellspacing="2" cellpadding="2" style="width:760px">
        <tr>
          <td class="required"><?php echo __('Name'); ?>:</td>
          <td>
            <input type="text" size="20" maxlength="64" style="width: 145px" name="firstname"
              autofocus value="<?php echo Format::htmlchars($staff->firstname); ?>"
              placeholder="<?php echo __("First Name"); ?>" />
            <input type="text" size="20" maxlength="64" style="width: 145px" name="lastname"
              value="<?php echo Format::htmlchars($staff->lastname); ?>"
              placeholder="<?php echo __("Last Name"); ?>" />
            <div class="error"><?php echo $errors['firstname']; ?></div>
            <div class="error"><?php echo $errors['lastname']; ?></div>
          </td>
        </tr>
        <tr>
          <td class="required"><?php echo __('Email Address'); ?>:</td>
          <td>
            <input type="email" size="40" maxlength="64" style="width: 300px" name="email"
              value="<?php echo Format::htmlchars($staff->email); ?>"
              placeholder="<?php echo __('e.g. me@mycompany.com'); ?>" />
            <div class="error"><?php echo $errors['email']; ?></div>
          </td>
        </tr>
        <tr>
          <td><?php echo __('Phone Number');?>:</td>
          <td>
            <input type="tel" size="18" name="phone" class="auto phone"
              value="<?php echo Format::htmlchars($staff->phone); ?>" />
            <?php echo __('Ext');?>
            <input type="text" size="5" name="phone_ext"
              value="<?php echo Format::htmlchars($staff->phone_ext); ?>">
            <div class="error"><?php echo $errors['phone']; ?></div>
            <div class="error"><?php echo $errors['phone_ext']; ?></div>
          </td>
        </tr>
        <tr>
          <td><?php echo __('Mobile Number');?>:</td>
          <td>
            <input type="tel" size="18" name="mobile" class="auto phone"
              value="<?php echo Format::htmlchars($staff->mobile); ?>" />
            <div class="error"><?php echo $errors['mobile']; ?></div>
          </td>
        </tr>
        </table></div></td></tr>
      </tbody>
      <!-- ================================================ -->
      <tbody>
        <tr class="header">
          <th colspan="2">
            <?php echo __('Authentication'); ?>
          </th>
        </tr>
        <?php if ($bk = $staff->getAuthBackend()) { ?>
        <tr>
          <td><?php echo __("Backend"); ?></td>
          <td><?php echo $bk->getName(); ?></td>
        </tr>
        <?php } ?>
        <tr>
          <td class="required"><?php echo __('Username'); ?>:
            <span class="error">*</span></td>
          <td>
            <input type="text" size="40" style="width:300px"
              class="staff-username typeahead"
              name="username" disabled value="<?php echo Format::htmlchars($staff->username); ?>" />
<?php if (!$bk || $bk->supportsPasswordChange()) { ?>
            <button type="button" id="change-pw-button" class="action-button" onclick="javascript:
            $.dialog('ajax.php/staff/'+<?php echo $staff->getId(); ?>+'/change-password', 201);">
              <i class="icon-refresh"></i> <?php echo __('Change Password'); ?>
            </button>
<?php } ?>
            <i class="offset help-tip icon-question-sign" href="#username"></i>
            <div class="error"><?php echo $errors['username']; ?></div>
          </td>
        </tr>

<?php
if (($bks=Staff2FABackend::allRegistered())) {
    $current = $staff->get2FABackendId();
    $required2fa = $cfg->require2FAForAgents();
    $_config = $staff->getConfig();
?>
        <tr>
          <td <?php if ($required2fa) echo 'class="required"'; ?>><?php echo __('Default 2FA'); ?>:</td>
          <td>
            <select name="default_2fa" id="default2fa-selection"
              style="width:300px">
              <?php
              if (!$required2fa) { ?>
              <option value="">&mdash; <?php echo __('Disable'); ?> &mdash;</option>
              <?php
              }
             foreach ($bks as $bk) {
                 $configuration = $staff->get2FAConfig($bk->getId());
                 $configured = $configuration['verified'];
                 ?>
              <option id="<?php echo $bk->getId(); ?>"
                      value="<?php echo $bk->getId(); ?>" <?php
                if ($current == $bk->getId() && $configured)
                  echo ' selected="selected" '; ?>
                <?php
                if (!$configured)
                   echo ' disabled="disabled" '; ?>
                 ><?php
                echo $bk->getName(); ?></option>
             <?php } ?>
            </select>
            &nbsp;
            <button type="button" id="config2fa-button" class="action-button" onclick="javascript:
            $.dialog('ajax.php/staff/'+<?php echo $staff->getId();
                    ?>+'/2fa/configure', 201);">
              <i class="icon-gear"></i> <?php echo __('Configure Options'); ?>
            </button>
            <i class="offset help-tip icon-question-sign" href="#config2fa"></i>
            <div class="error"><?php echo $errors['default_2fa']; ?></div>
          </td>
        </tr>
<?php
} ?>
      </tbody>
      <!-- ================================================ -->
      <tbody>
        <tr class="header">
          <th colspan="2">
            <?php echo __('Status and Settings'); ?>
          </th>
        </tr>
        <tr>
          <td colspan="2">
            <label class="checkbox">
            <input type="checkbox" name="onvacation"
              <?php echo ($staff->onvacation) ? 'checked="checked"' : ''; ?> />
              <?php echo __('Vacation Mode'); ?>
            </label>
            <br/>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- =================== PREFERENCES ======================== -->

  <div class="hidden tab_content" id="preferences">
    <table class="table two-column" width="100%">
      <tbody>
        <tr class="header">
          <th colspan="2">
            <?php echo __('Preferences'); ?>
            <div><small><?php echo __(
            "Profile preferences and settings"
          ); ?>
            </small></div>
          </th>
        </tr>
        <tr>
            <td width="180"><?php echo __('Maximum Page size');?>:</td>
            <td>
                <select name="max_page_size">
                    <option value="0">&mdash; <?php echo __('System Default');?> &mdash;</option>
                    <?php
                    $pagelimit = $staff->max_page_size ?: $cfg->getPageSize();
                    for ($i = 5; $i <= 50; $i += 5) {
                        $sel=($pagelimit==$i)?'selected="selected"':'';
                         echo sprintf('<option value="%d" %s>'.__('show %s records').'</option>',$i,$sel,$i);
                    } ?>
                </select> <?php echo __('per page.');?>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Auto Refresh Rate');?>:
              <div class="faded"><?php echo __('Tickets page refresh rate in minutes.'); ?></div>
            </td>
            <td>
                <select name="auto_refresh_rate">
                  <option value="0">&mdash; <?php echo __('Disabled');?> &mdash;</option>
                  <?php
                  $y=1;
                   for($i=1; $i <=30; $i+=$y) {
                     $sel=($staff->auto_refresh_rate==$i)?'selected="selected"':'';
                     echo sprintf('<option value="%d" %s>%s</option>', $i, $sel,
                        @sprintf(_N('Every minute', 'Every %d minutes', $i), $i));
                     if($i>9)
                        $y=2;
                   } ?>
                </select>
            </td>
        </tr>

        <tr>
            <td><?php echo __('Default From Name');?>:
              <div class="faded"><?php echo __('From name to use when replying to a thread');?></div>
            </td>
            <td>
                <select name="default_from_name">
                  <?php
                   $options=array(
                           'email' => __("Email Address Name"),
                           'dept' => sprintf(__("Department Name (%s)"),
                               __('if public' /* This is used in 'Department's Name (>if public<)' */)),
                           'mine' => __('My Name'),
                           '' => '— '.__('System Default').' —',
                           );
                  if ($cfg->hideStaffName())
                    unset($options['mine']);

                  foreach($options as $k=>$v) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $k,($staff->default_from_name && $staff->default_from_name==$k)?'selected="selected"':'',$v);
                  }
                  ?>
                </select>
                <div class="error"><?php echo $errors['default_from_name']; ?></div>
            </td>
        </tr>
        <tr>
            <td>
                <?php echo __('Default Ticket Queue'); ?>:
            </td>
            <td>
                <select name="default_ticket_queue_id">
                 <option value="0">&mdash; <?php echo __('system default');?> &mdash;</option>
                 <?php
                 $queues = CustomQueue::queues()
                    ->filter(Q::any(array(
                        'flags__hasbit' => CustomQueue::FLAG_PUBLIC,
                        'staff_id' => $thisstaff->getId(),
                    )))
                    ->all();
                 foreach ($queues as $q) { ?>
                  <option value="<?php echo $q->id; ?>" <?php
                    if ($q->getId() == $staff->default_ticket_queue_id) echo 'selected="selected"'; ?> >
                   <?php echo $q->getFullName(); ?></option>
                 <?php
                 } ?>
                </select>
            </td>
        </tr>

        <tr>
            <td><?php echo __('Thread View Order');?>:
              <div class="faded"><?php echo __('The order of thread entries');?></div>
            </td>
            <td>
                <select name="thread_view_order">
                  <?php
                   $options=array(
                           'desc' => __('Descending'),
                           'asc' => __('Ascending'),
                           '' => '— '.__('System Default').' —',
                           );
                  foreach($options as $k=>$v) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $k
                                ,($staff->thread_view_order == $k) ? 'selected="selected"' : ''
                                ,$v);
                  }
                  ?>
                </select>
                <div class="error"><?php echo $errors['thread_view_order']; ?></div>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Default Signature');?>:
              <div class="faded"><?php echo __('This can be selected when replying to a thread');?></div>
            </td>
            <td>
                <select name="default_signature_type">
                  <option value="none" selected="selected">&mdash; <?php echo __('None');?> &mdash;</option>
                  <?php
                   $options=array('mine'=>__('My Signature'),'dept'=>sprintf(__('Department Signature (%s)'),
                       __('if set' /* This is used in 'Department Signature (>if set<)' */)));
                  foreach($options as $k=>$v) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $k,($staff->default_signature_type==$k)?'selected="selected"':'',$v);
                  }
                  ?>
                </select>
                <div class="error"><?php echo $errors['default_signature_type']; ?></div>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Default Paper Size');?>:
              <div class="faded"><?php echo __('Paper size used when printing tickets to PDF');?></div>
            </td>
            <td>
                <select name="default_paper_size">
                  <option value="none" selected="selected">&mdash; <?php echo __('None');?> &mdash;</option>
                  <?php

                  foreach(Export::$paper_sizes as $v) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $v,($staff->default_paper_size==$v)?'selected="selected"':'',__($v));
                  }
                  ?>
                </select>
                <div class="error"><?php echo $errors['default_paper_size']; ?></div>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Reply Redirect'); ?>:
                <div class="faded"><?php echo __('Redirect URL used after replying to a ticket.');?></div>
            </td>
            <td>
                <select name="reply_redirect">
                  <?php
                  $options=array('Queue'=>__('Queue'),'Ticket'=>__('Ticket'));
                  foreach($options as $key=>$opt) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $key,($staff->reply_redirect==$key)?'selected="selected"':'',$opt);
                  }
                  ?>
                </select>
                <div class="error"><?php echo $errors['reply_redirect']; ?></div>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Image Attachment View'); ?>:
                <div class="faded"><?php echo __('Open image attachments in new tab or directly download. (CTRL + Right Click)');?></div>
            </td>
            <td>
                <select name="img_att_view">
                  <?php
                  $options=array('download'=>__('Download'),'inline'=>__('Inline'));
                  foreach($options as $key=>$opt) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $key,($staff->img_att_view==$key)?'selected="selected"':'',$opt);
                  }
                  ?>
                </select>
                <div class="error"><?php echo $errors['img_att_view']; ?></div>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Editor Spacing'); ?>:
                <div class="faded"><?php echo __('Set the editor spacing to Single or Double when pressing Enter.');?></div>
            </td>
            <td>
                <select name="editor_spacing">
                  <?php
                  $options=array('double'=>__('Double'),'single'=>__('Single'));
                  $spacing = $staff->editor_spacing;
                  foreach($options as $key=>$opt) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $key,($spacing==$key)?'selected="selected"':'',$opt);
                  }
                  ?>
                </select>
                <div class="error"><?php echo $errors['editor_spacing']; ?></div>
            </td>
        </tr>
      </tbody>
      <tbody>
        <tr class="header">
          <th colspan="2">
            <?php echo __('Localization'); ?>
          </th>
        </tr>
        <tr>
            <td><?php echo __('Time Zone');?>:</td>
            <td>
                <?php
                $TZ_NAME = 'timezone';
                $TZ_TIMEZONE = $staff->timezone;
                include STAFFINC_DIR.'templates/timezone.tmpl.php'; ?>
                <div class="error"><?php echo $errors['timezone']; ?></div>
            </td>
        </tr>
        <tr><td><?php echo __('Time Format');?>:</td>
            <td>
                <select name="datetime_format">
<?php
    $datetime_format = $staff->datetime_format;
    foreach (array(
    'relative' => __('Relative Time'),
    '' => '— '.__('System Default').' —',
) as $v=>$name) { ?>
                    <option value="<?php echo $v; ?>" <?php
                    if ($v == $datetime_format)
                        echo 'selected="selected"';
                    ?>><?php echo $name; ?></option>
<?php } ?>
                </select>
            </td>
        </tr>
<?php if ($cfg->getSecondaryLanguages()) { ?>
        <tr>
            <td><?php echo __('Preferred Language'); ?>:</td>
            <td>
        <?php
        $langs = Internationalization::getConfiguredSystemLanguages(); ?>
                <select name="lang">
                    <option value="">&mdash; <?php echo __('Use Browser Preference'); ?> &mdash;</option>
<?php foreach($langs as $l) {
    $selected = ($staff->lang == $l['code']) ? 'selected="selected"' : ''; ?>
                    <option value="<?php echo $l['code']; ?>" <?php echo $selected;
                        ?>><?php echo Internationalization::getLanguageDescription($l['code']); ?></option>
<?php } ?>
                </select>
                <span class="error">&nbsp;<?php echo $errors['lang']; ?></span>
            </td>
        </tr>
<?php } ?>
<?php if (extension_loaded('intl')) { ?>
        <tr>
            <td><?php echo __('Preferred Locale');?>:</td>
            <td>
                <select name="locale">
                    <option value=""><?php echo __('Use Language Preference'); ?></option>
<?php foreach (Internationalization::allLocales() as $code=>$name) { ?>
                    <option value="<?php echo $code; ?>" <?php
                        if ($code == $staff->locale)
                            echo 'selected="selected"';
                    ?>><?php echo $name; ?></option>
<?php } ?>
                </select>
            </td>
        </tr>
<?php } ?>
    </table>
  </div>

  <!-- ==================== SIGNATURES ======================== -->

  <div id="signature" class="hidden">
    <table class="table two-column" width="100%">
      <tbody>
        <tr class="header">
          <th colspan="2">
            <?php echo __('Signature'); ?>
            <div><small><?php echo __(
            "Optional signature used on outgoing emails.")
            .' '.
            __('Signature is made available as a choice, on ticket reply.'); ?>
            </small></div>
          </th>
        </tr>
        <tr>
            <td colspan="2">
                <textarea class="richtext no-bar" name="signature" cols="21"
                    rows="5" style="width: 60%;"><?php echo Format::viewableImages(Format::htmlchars($staff->signature, true)); ?></textarea>
            </td>
        </tr>
      </tbody>
    </table>
  </div>

  <p style="text-align:center;">
    <button class="button action-button" type="submit" name="submit" ><i class="icon-save"></i> <?php echo __('Save Changes'); ?></button>
    <button class="button action-button" type="reset"  name="reset"><i class="icon-undo"></i>
        <?php echo __('Reset');?></button>
    <button class="red button action-button" type="button" name="cancel" onclick="window.history.go(-1);"><i class="icon-remove-circle"></i> <?php echo __('Cancel');?></button>
  </p>
    <div class="clear"></div>
</form>
<?php
if ($staff->change_passwd) { ?>
<script type="text/javascript">
    $(function() { $('#change-pw-button').trigger('click'); });
</script>
<?php
}
