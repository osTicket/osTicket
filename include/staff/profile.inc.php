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
 <h2>My Account Profile</h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4>Account Information</h4>
                <em>Contact information.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
                Username:
            </td>
            <td><b><?php echo $staff->getUserName(); ?></b>&nbsp;<i class="help-tip icon-question-sign" href="#username"></i></td>
        </tr>

        <tr>
            <td width="180" class="required">
                First Name:
            </td>
            <td>
                <input type="text" size="34" name="firstname" value="<?php echo $info['firstname']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['firstname']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Last Name:
            </td>
            <td>
                <input type="text" size="34" name="lastname" value="<?php echo $info['lastname']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['lastname']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Email Address:
            </td>
            <td>
                <input type="text" size="34" name="email" value="<?php echo $info['email']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['email']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180">
                Phone Number:
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
                Mobile Number:
            </td>
            <td>
                <input type="text" size="22" name="mobile" value="<?php echo $info['mobile']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['mobile']; ?></span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Preferences</strong>: Profile preferences and settings.</em>
            </th>
        </tr>
        <tr>
            <td width="180" class="required">
                Time Zone:
            </td>
            <td>
                <select name="timezone_id" id="timezone_id">
                    <option value="0">&mdash; Select Time Zone &mdash;</option>
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
                Preferred Language:
            </td>
            <td>
        <?php
        $langs = Internationalization::availableLanguages(); ?>
                <select name="lang">
                    <option value="">&mdash; Use Browser Preference &mdash;</option>
<?php foreach($langs as $l) {
    $selected = ($info['lang'] == $l['code']) ? 'selected="selected"' : ''; ?>
                    <option value="<?php echo $l['code']; ?>" <?php echo $selected;
                        ?>><?php echo $l['desc']; ?></option>
<?php } ?>
                </select>
                <span class="error">&nbsp;<?php echo $errors['lang']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180">
               Daylight Saving:
            </td>
            <td>
                <input type="checkbox" name="daylight_saving" value="1" <?php echo $info['daylight_saving']?'checked="checked"':''; ?>>
                Observe daylight saving
                <em>(Current Time: <strong><?php echo Format::date($cfg->getDateTimeFormat(),Misc::gmtime(),$info['tz_offset'],$info['daylight_saving']); ?></strong>)</em>
            </td>
        </tr>
        <tr>
            <td width="180">Maximum Page size:</td>
            <td>
                <select name="max_page_size">
                    <option value="0">&mdash; system default &mdash;</option>
                    <?php
                    $pagelimit=$info['max_page_size']?$info['max_page_size']:$cfg->getPageSize();
                    for ($i = 5; $i <= 50; $i += 5) {
                        $sel=($pagelimit==$i)?'selected="selected"':'';
                         echo sprintf('<option value="%d" %s>show %s records</option>',$i,$sel,$i);
                    } ?>
                </select> per page.
            </td>
        </tr>
        <tr>
            <td width="180">Auto Refresh Rate:</td>
            <td>
                <select name="auto_refresh_rate">
                  <option value="0">&mdash; disable &mdash;</option>
                  <?php
                  $y=1;
                   for($i=1; $i <=30; $i+=$y) {
                     $sel=($info['auto_refresh_rate']==$i)?'selected="selected"':'';
                     echo sprintf('<option value="%d" %s>Every %s %s</option>',$i,$sel,$i,($i>1?'mins':'min'));
                     if($i>9)
                        $y=2;
                   } ?>
                </select>
                <em>(Tickets page refresh rate in minutes.)</em>
            </td>
        </tr>
        <tr>
            <td width="180">Default Signature:</td>
            <td>
                <select name="default_signature_type">
                  <option value="none" selected="selected">&mdash; None &mdash;</option>
                  <?php
                  $options=array('mine'=>'My Signature','dept'=>'Dept. Signature (if set)');
                  foreach($options as $k=>$v) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $k,($info['default_signature_type']==$k)?'selected="selected"':'',$v);
                  }
                  ?>
                </select>
                <em>(You can change selection on ticket page)</em>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['default_signature_type']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180">Default Paper Size:</td>
            <td>
                <select name="default_paper_size">
                  <option value="none" selected="selected">&mdash; None &mdash;</option>
                  <?php
                  $options=array('Letter', 'Legal', 'A4', 'A3');
                  foreach($options as $v) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $v,($info['default_paper_size']==$v)?'selected="selected"':'',$v);
                  }
                  ?>
                </select>
                <em>Paper size used when printing tickets to PDF</em>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['default_paper_size']; ?></span>
            </td>
        </tr>
        <tr>
            <td>Show Assigned Tickets:</td>
            <td>
                <input type="checkbox" name="show_assigned_tickets" <?php echo $info['show_assigned_tickets']?'checked="checked"':''; ?>>
                <em>Show assigned tickets on open queue.&nbsp;<i class="help-tip icon-question-sign" href="#show_assigned_tickets"></i></em>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Password</strong>: To reset your password, provide your current password and a new password below.&nbsp;<span class="error">&nbsp;<?php echo $errors['passwd']; ?></span></em>
            </th>
        </tr>
        <?php if (!isset($_SESSION['_staff']['reset-token'])) { ?>
        <tr>
            <td width="180">
                Current Password:
            </td>
            <td>
                <input type="password" size="18" name="cpasswd" value="<?php echo $info['cpasswd']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['cpasswd']; ?></span>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <td width="180">
                New Password:
            </td>
            <td>
                <input type="password" size="18" name="passwd1" value="<?php echo $info['passwd1']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd1']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180">
                Confirm New Password:
            </td>
            <td>
                <input type="password" size="18" name="passwd2" value="<?php echo $info['passwd2']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Signature</strong>: Optional signature used on outgoing emails.
                &nbsp;<span class="error">&nbsp;<?php echo $errors['signature']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#signature"></i></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea class="richtext no-bar" name="signature" cols="21"
                    rows="5" style="width: 60%;"><?php echo $info['signature']; ?></textarea>
                <br><em>Signature is made available as a choice, on ticket reply.</em>
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:200px;">
    <input type="submit" name="submit" value="Save Changes">
    <input type="reset"  name="reset"  value="Reset Changes">
    <input type="button" name="cancel" value="Cancel Changes" onclick='window.location.href="index.php"'>
</p>
</form>
