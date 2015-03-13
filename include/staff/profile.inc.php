<?php
if(!defined('OSTSTAFFINC') || !$staff || !$thisstaff) die('Access Denied');

$info=$staff->getInfo();
$info['signature'] = Format::viewableImages($info['signature']);
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
$info['id']=$staff->getId();
?>
<form action="profile.php" method="post" id="save" autocomplete="off">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="update">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2><?php echo __('My Account Profile');?></h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo __('Account Information');?></h4>
                <em><?php echo __('Contact information');?></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
                <?php echo __('Username');?>:
            </td>
            <td><b><?php echo $staff->getUserName(); ?></b>&nbsp;<i class="help-tip icon-question-sign" href="#username"></i></td>
        </tr>

        <tr>
            <td width="180" class="required">
                <?php echo __('First Name');?>:
            </td>
            <td>
                <input type="text" size="34" name="firstname" value="<?php echo $info['firstname']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['firstname']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Last Name');?>:
            </td>
            <td>
                <input type="text" size="34" name="lastname" value="<?php echo $info['lastname']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['lastname']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Email Address');?>:
            </td>
            <td>
                <input type="text" size="34" name="email" value="<?php echo $info['email']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['email']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Phone Number');?>:
            </td>
            <td>
                <input type="text" size="22" name="phone" value="<?php echo $info['phone']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['phone']; ?></span>
                Ext <input type="text" size="5" name="phone_ext" value="<?php echo $info['phone_ext']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['phone_ext']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Mobile Number');?>:
            </td>
            <td>
                <input type="text" size="22" name="mobile" value="<?php echo $info['mobile']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['mobile']; ?></span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Preferences');?></strong>: <?php echo __('Profile preferences and settings.');?></em>
            </th>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Time Zone');?>:
            </td>
            <td>
                <select name="timezone_id" id="timezone_id">
                    <option value="0">&mdash; <?php echo __('Select Time Zone');?> &mdash;</option>
                    <?php
                    $sql='SELECT id, offset,timezone FROM '.TIMEZONE_TABLE.' ORDER BY id';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$offset, $tz)=db_fetch_row($res)){
                            $sel=($info['timezone_id']==$id)?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>GMT %s - %s</option>',$id,$sel,$offset,$tz);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['timezone_id']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Preferred Language'); ?>:
            </td>
            <td>
        <?php
        $langs = Internationalization::availableLanguages(); ?>
                <select name="lang">
                    <option value="">&mdash; <?php echo __('Use Browser Preference'); ?> &mdash;</option>
<?php foreach($langs as $l) {
    $selected = ($info['lang'] == $l['code']) ? 'selected="selected"' : ''; ?>
                    <option value="<?php echo $l['code']; ?>" <?php echo $selected;
                        ?>><?php echo Internationalization::getLanguageDescription($l['code']); ?></option>
<?php } ?>
                </select>
                <span class="error">&nbsp;<?php echo $errors['lang']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180">
               <?php echo __('Daylight Saving');?>:
            </td>
            <td>
                <input type="checkbox" name="daylight_saving" value="1" <?php echo $info['daylight_saving']?'checked="checked"':''; ?>>
                <?php echo __('Observe daylight saving');?>
                <em>(<?php echo __('Current Time');?>: <strong><?php echo Format::date($cfg->getDateTimeFormat(),Misc::gmtime(),$info['tz_offset'],$info['daylight_saving']); ?></strong>)</em>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Maximum Page size');?>:</td>
            <td>
                <select name="max_page_size">
                    <option value="0">&mdash; <?php echo __('system default');?> &mdash;</option>
                    <?php
                    $pagelimit=$info['max_page_size']?$info['max_page_size']:$cfg->getPageSize();
                    for ($i = 5; $i <= 50; $i += 5) {
                        $sel=($pagelimit==$i)?'selected="selected"':'';
                         echo sprintf('<option value="%d" %s>'.__('show %s records').'</option>',$i,$sel,$i);
                    } ?>
                </select> <?php echo __('per page.');?>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Auto Refresh Rate');?>:</td>
            <td>
                <select name="auto_refresh_rate">
                  <option value="0">&mdash; <?php echo __('disable');?> &mdash;</option>
                  <?php
                  $y=1;
                   for($i=1; $i <=30; $i+=$y) {
                     $sel=($info['auto_refresh_rate']==$i)?'selected="selected"':'';
                     echo sprintf('<option value="%1$d" %2$s>'
                        .sprintf(
                            _N('Every minute', 'Every %d minutes', $i), $i)
                         .'</option>',$i,$sel);
                     if($i>9)
                        $y=2;
                   } ?>
                </select>
                <em><?php echo __('(Tickets page refresh rate in minutes.)');?></em>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Default Signature');?>:</td>
            <td>
                <select name="default_signature_type">
                  <option value="none" selected="selected">&mdash; <?php echo __('None');?> &mdash;</option>
                  <?php
                   $options=array('mine'=>__('My Signature'),'dept'=>sprintf(__('Department Signature (%s)'),
                       __('if set' /* This is used in 'Department Signature (>if set<)' */)));
                  foreach($options as $k=>$v) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $k,($info['default_signature_type']==$k)?'selected="selected"':'',$v);
                  }
                  ?>
                </select>
                <em><?php echo __('(This can be selectected when replying to a ticket)');?></em>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['default_signature_type']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Default Paper Size');?>:</td>
            <td>
                <select name="default_paper_size">
                  <option value="none" selected="selected">&mdash; <?php echo __('None');?> &mdash;</option>
                  <?php

                  foreach(Export::$paper_sizes as $v) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $v,($info['default_paper_size']==$v)?'selected="selected"':'',__($v));
                  }
                  ?>
                </select>
                <em><?php echo __('Paper size used when printing tickets to PDF');?></em>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['default_paper_size']; ?></span>
            </td>
        </tr>
        <tr>
            <td><?php echo __('Show Assigned Tickets');?>:</td>
            <td>
                <input type="checkbox" name="show_assigned_tickets" <?php echo $info['show_assigned_tickets']?'checked="checked"':''; ?>>
                <em><?php echo __('Show assigned tickets on open queue.');?></em>
                &nbsp;<i class="help-tip icon-question-sign" href="#show_assigned_tickets"></i></em>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Password');?></strong>: <?php echo __('To reset your password, provide your current password and a new password below.');?>&nbsp;<span class="error">&nbsp;<?php echo $errors['passwd']; ?></span></em>
            </th>
        </tr>
        <?php if (!isset($_SESSION['_staff']['reset-token'])) { ?>
        <tr>
            <td width="180">
                <?php echo __('Current Password');?>:
            </td>
            <td>
                <input type="password" size="18" name="cpasswd" value="<?php echo $info['cpasswd']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['cpasswd']; ?></span>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <td width="180">
                <?php echo __('New Password');?>:
            </td>
            <td>
                <input type="password" size="18" name="passwd1" value="<?php echo $info['passwd1']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd1']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Confirm New Password');?>:
            </td>
            <td>
                <input type="password" size="18" name="passwd2" value="<?php echo $info['passwd2']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Signature');?></strong>: <?php echo __('Optional signature used on outgoing emails.');?>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['signature']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#signature"></i></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea class="richtext no-bar" name="signature" cols="21"
                    rows="5" style="width: 60%;"><?php echo $info['signature']; ?></textarea>
                <br><em><?php __('Signature is made available as a choice, on ticket reply.');?></em>
            </td>
        </tr>
    </tbody>
</table>
<p style="text-align:center;">
    <input type="submit" name="submit" value="<?php echo __('Save Changes');?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset Changes');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel Changes');?>" onclick='window.location.href="index.php"'>
</p>
</form>
