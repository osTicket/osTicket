<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$info=array();
$qstr='';
if($staff && $_REQUEST['a']!='add'){
    //Editing Department.
    $title=__('Update Agent');
    $action='update';
    $submit_text=__('Save Changes');
    $passwd_text=__('To reset the password enter a new one below');
    $info=$staff->getInfo();
    $info['id']=$staff->getId();
    $info['teams'] = $staff->getTeams();
    $info['signature'] = Format::viewableImages($info['signature']);
    $qstr.='&id='.$staff->getId();
}else {
    $title=__('Add New Agent');
    $action='create';
    $submit_text=__('Add Agent');
    $passwd_text=__('Temporary password required only for "Local" authenication');
    //Some defaults for new staff.
    $info['change_passwd']=1;
    $info['welcome_email']=1;
    $info['isactive']=1;
    $info['isvisible']=1;
    $info['isadmin']=0;
    $info['timezone_id'] = $cfg->getDefaultTimezoneId();
    $info['daylight_saving'] = $cfg->observeDaylightSaving();
    $qstr.='&a=add';
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="staff.php?<?php echo $qstr; ?>" method="post" id="save" autocomplete="off">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2><?php echo __('Agent Account');?></h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em><strong><?php echo __('User Information');?></strong></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
                <?php echo __('Username');?>:
            </td>
            <td>
                <input type="text" size="30" class="staff-username typeahead"
                     name="username" value="<?php echo $info['username']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['username']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#username"></i>
            </td>
        </tr>

        <tr>
            <td width="180" class="required">
                <?php echo __('First Name');?>:
            </td>
            <td>
                <input type="text" size="30" name="firstname" class="auto first"
                     value="<?php echo $info['firstname']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['firstname']; ?></span>&nbsp;
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Last Name');?>:
            </td>
            <td>
                <input type="text" size="30" name="lastname" class="auto last"
                    value="<?php echo $info['lastname']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['lastname']; ?></span>&nbsp;
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Email Address');?>:
            </td>
            <td>
                <input type="text" size="30" name="email" class="auto email"
                    value="<?php echo $info['email']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['email']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#email_address"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Phone Number');?>:
            </td>
            <td>
                <input type="text" size="18" name="phone" class="auto phone"
                    value="<?php echo $info['phone']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['phone']; ?></span>
                <?php echo __('Ext');?> <input type="text" size="5" name="phone_ext" value="<?php echo $info['phone_ext']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['phone_ext']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Mobile Number');?>:
            </td>
            <td>
                <input type="text" size="18" name="mobile" class="auto mobile"
                    value="<?php echo $info['mobile']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['mobile']; ?></span>
            </td>
        </tr>
<?php if (!$staff) { ?>
        <tr>
            <td width="180"><?php echo __('Welcome Email'); ?></td>
            <td><input type="checkbox" name="welcome_email" id="welcome-email" <?php
                if ($info['welcome_email']) echo 'checked="checked"';
                ?> onchange="javascript:
                var sbk = $('#backend-selection');
                if ($(this).is(':checked'))
                    $('#password-fields').hide();
                else if (sbk.val() == '' || sbk.val() == 'local')
                    $('#password-fields').show();
                " />
                <?php echo __('Send sign in information'); ?>
                &nbsp;<i class="help-tip icon-question-sign" href="#welcome_email"></i>
            </td>
        </tr>
<?php } ?>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Authentication'); ?></strong>: <?php echo $passwd_text; ?> &nbsp;<span class="error">&nbsp;<?php echo $errors['temppasswd']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#account_password"></i></em>
            </th>
        </tr>
        <tr>
            <td><?php echo __('Authentication Backend'); ?></td>
            <td>
            <select name="backend" id="backend-selection" onchange="javascript:
                if (this.value != '' && this.value != 'local')
                    $('#password-fields').hide();
                else if (!$('#welcome-email').is(':checked'))
                    $('#password-fields').show();
                ">
                <option value="">&mdash; <?php echo __('Use any available backend'); ?> &mdash;</option>
            <?php foreach (StaffAuthenticationBackend::allRegistered() as $ab) {
                if (!$ab->supportsInteractiveAuthentication()) continue; ?>
                <option value="<?php echo $ab::$id; ?>" <?php
                    if ($info['backend'] == $ab::$id)
                        echo 'selected="selected"'; ?>><?php
                    echo $ab->getName(); ?></option>
            <?php } ?>
            </select>
            </td>
        </tr>
    </tbody>
    <tbody id="password-fields" style="<?php
        if ($info['welcome_email'] || ($info['backend'] && $info['backend'] != 'local'))
            echo 'display:none;'; ?>">
        <tr>
            <td width="180">
                <?php echo __('Password');?>:
            </td>
            <td>
                <input type="password" size="18" name="passwd1" value="<?php echo $info['passwd1']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd1']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Confirm Password');?>:
            </td>
            <td>
                <input type="password" size="18" name="passwd2" value="<?php echo $info['passwd2']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span>
            </td>
        </tr>

        <tr>
            <td width="180">
                <?php echo __('Forced Password Change');?>:
            </td>
            <td>
                <input type="checkbox" name="change_passwd" value="0" <?php echo $info['change_passwd']?'checked="checked"':''; ?>>
                <?php echo __('<strong>Force</strong> password change on next login.');?>
                &nbsp;<i class="help-tip icon-question-sign" href="#forced_password_change"></i>
            </td>
        </tr>
    </tbody>
    <tbody>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __("Agent's Signature");?></strong>:
                <?php echo __('Optional signature used on outgoing emails.');?>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['signature']; ?></span></em>
                &nbsp;<i class="help-tip icon-question-sign" href="#agents_signature"></i></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea class="richtext no-bar" name="signature" cols="21"
                    rows="5" style="width: 60%;"><?php echo $info['signature']; ?></textarea>
                <br><em><?php echo __('Signature is made available as a choice, on ticket reply.');?></em>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Account Status & Settings');?></strong>: <?php echo __('Department and group assigned control access permissions.');?></em>
            </th>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Account Type');?>:
            </td>
            <td>
                <input type="radio" name="isadmin" value="1" <?php echo $info['isadmin']?'checked="checked"':''; ?>>
                    <font color="red"><strong><?php echo __('Admin');?></strong></font>
                <input type="radio" name="isadmin" value="0" <?php echo !$info['isadmin']?'checked="checked"':''; ?>><strong><?php echo __('Agent');?></strong>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['isadmin']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Account Status');?>:
            </td>
            <td>
                <input type="radio" name="isactive" value="1" <?php echo $info['isactive']?'checked="checked"':''; ?>><strong><?php echo __('Active');?></strong>
                <input type="radio" name="isactive" value="0" <?php echo !$info['isactive']?'checked="checked"':''; ?>><strong><?php echo __('Locked');?></strong>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['isactive']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#account_status"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Assigned Group');?>:
            </td>
            <td>
                <select name="group_id" id="group_id">
                    <option value="0">&mdash; <?php echo __('Select Group');?> &mdash;</option>
                    <?php
                    $sql='SELECT group_id, group_name, group_enabled as isactive FROM '.GROUP_TABLE.' ORDER BY group_name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$name,$isactive)=db_fetch_row($res)){
                            $sel=($info['group_id']==$id)?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s %s</option>',$id,$sel,$name,($isactive?'':__('(disabled)')));
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['group_id']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#assigned_group"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Primary Department');?>:
            </td>
            <td>
                <select name="dept_id" id="dept_id">
                    <option value="0">&mdash; <?php echo __('Select Department');?> &mdash;</option>
                    <?php
                    $sql='SELECT dept_id, dept_name FROM '.DEPT_TABLE.' ORDER BY dept_name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$name)=db_fetch_row($res)){
                            $sel=($info['dept_id']==$id)?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$sel,$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['dept_id']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#primary_department"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __("Agent's Time Zone");?>:
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
               <?php echo __('Daylight Saving');?>:
            </td>
            <td>
                <input type="checkbox" name="daylight_saving" value="1" <?php echo $info['daylight_saving']?'checked="checked"':''; ?>>
                <?php echo __('Observe daylight saving');?>
                <em>(<?php echo __('Current Time');?>: <strong><?php
                    echo Format::date($cfg->getDateTimeFormat(),Misc::gmtime(),$info['tz_offset'],$info['daylight_saving']);
                ?></strong>)
                &nbsp;<i class="help-tip icon-question-sign" href="#daylight_saving"></i>
                </em>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Limited Access');?>:
            </td>
            <td>
                <input type="checkbox" name="assigned_only" value="1" <?php echo $info['assigned_only']?'checked="checked"':''; ?>><?php echo __('Limit ticket access to ONLY assigned tickets.');?>
                &nbsp;<i class="help-tip icon-question-sign" href="#limited_access"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Directory Listing');?>:
            </td>
            <td>
                <input type="checkbox" name="isvisible" value="1" <?php echo $info['isvisible']?'checked="checked"':''; ?>>&nbsp;<?php
                echo __('Make visible in the Agent Directory'); ?>
                &nbsp;<i class="help-tip icon-question-sign" href="#directory_listing"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Vacation Mode');?>:
            </td>
            <td>
                <input type="checkbox" name="onvacation" value="1" <?php echo $info['onvacation']?'checked="checked"':''; ?>>
                    <?php echo __('Change Status to Vacation Mode'); ?>
                    &nbsp;<i class="help-tip icon-question-sign" href="#vacation_mode"></i>
            </td>
        </tr>
        <?php
         //List team assignments.
         $sql='SELECT team.team_id, team.name, isenabled FROM '.TEAM_TABLE.' team  ORDER BY team.name';
         if(($res=db_query($sql)) && db_num_rows($res)){ ?>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Assigned Teams');?></strong>: <?php echo __("Agent will have access to tickets assigned to a team they belong to regardless of the ticket's department.");?> </em>
            </th>
        </tr>
        <?php
         while(list($id,$name,$isactive)=db_fetch_row($res)){
             $checked=($info['teams'] && in_array($id,$info['teams']))?'checked="checked"':'';
             echo sprintf('<tr><td colspan=2><input type="checkbox" name="teams[]" value="%d" %s>%s %s</td></tr>',
                     $id,$checked,$name,($isactive?'':__('(disabled)')));
         }
        } ?>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Internal Notes'); ?></strong></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea class="richtext no-bar" name="notes" cols="28"
                    rows="7" style="width: 80%;"><?php echo $info['notes']; ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:250px;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick='window.location.href="staff.php"'>
</p>
</form>
