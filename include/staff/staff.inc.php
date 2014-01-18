<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$info=array();
$qstr='';
if($staff && $_REQUEST['a']!='add'){
    //Editing Department.
    $title='Update Staff';
    $action='update';
    $submit_text='Save Changes';
    $passwd_text='To reset the password enter a new one below';
    $info=$staff->getInfo();
    $info['id']=$staff->getId();
    $info['teams'] = $staff->getTeams();
    $qstr.='&id='.$staff->getId();
}else {
    $title='Add New Staff';
    $action='create';
    $submit_text='Add Staff';
    $passwd_text='Temporary password required only for "Local" authenication';
    //Some defaults for new staff.
    $info['change_passwd']=1;
    $info['isactive']=1;
    $info['isvisible']=1;
    $info['isadmin']=0;
    $qstr.='&a=add';
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="staff.php?<?php echo $qstr; ?>" method="post" id="save" autocomplete="off">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2>Staff Account</h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em><strong>User Information</strong></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
                Username:
            </td>
            <td>
                <input type="text" size="30" class="staff-username typeahead"
                     name="username" value="<?php echo $info['username']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['username']; ?></span>
            </td>
        </tr>

        <tr>
            <td width="180" class="required">
                First Name:
            </td>
            <td>
                <input type="text" size="30" name="firstname" class="auto first"
                     value="<?php echo $info['firstname']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['firstname']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Last Name:
            </td>
            <td>
                <input type="text" size="30" name="lastname" class="auto last"
                    value="<?php echo $info['lastname']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['lastname']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Email Address:
            </td>
            <td>
                <input type="text" size="30" name="email" class="auto email"
                    value="<?php echo $info['email']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['email']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180">
                Phone Number:
            </td>
            <td>
                <input type="text" size="18" name="phone" class="auto phone"
                    value="<?php echo $info['phone']; ?>">
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
                <input type="text" size="18" name="mobile" class="auto mobile"
                    value="<?php echo $info['mobile']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['mobile']; ?></span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Authentication</strong>: <?php echo $passwd_text; ?> &nbsp;<span class="error">&nbsp;<?php echo $errors['temppasswd']; ?></span></em>
            </th>
        </tr>
        <tr>
            <td>Authentication Backend</td>
            <td>
            <select name="backend" onchange="javascript:
                if (this.value != '' && this.value != 'local')
                    $('#password-fields').hide();
                else
                    $('#password-fields').show();
                ">
                <option value="">&mdash; Use any available backend &mdash;</option>
            <?php foreach (StaffAuthenticationBackend::allRegistered() as $ab) {
                if (!$ab->supportsAuthentication()) continue; ?>
                <option value="<?php echo $ab::$id; ?>" <?php
                    if ($info['backend'] == $ab::$id)
                        echo 'selected="selected"'; ?>><?php
                    echo $ab::$name; ?></option>
            <?php } ?>
            </select>
            </td>
        </tr>
        </tbody>
        <tbody id="password-fields" style="<?php if ($info['backend'] && $info['backend'] != 'local')
            echo 'display:none;'; ?>">
        <tr>
            <td width="180">
                Password:
            </td>
            <td>
                <input type="password" size="18" name="passwd1" value="<?php echo $info['passwd1']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd1']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180">
                Confirm Password:
            </td>
            <td>
                <input type="password" size="18" name="passwd2" value="<?php echo $info['passwd2']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span>
            </td>
        </tr>

        <tr>
            <td width="180">
                Forced Password Change:
            </td>
            <td>
                <input type="checkbox" name="change_passwd" value="0" <?php echo $info['change_passwd']?'checked="checked"':''; ?>>
                <strong>Force</strong> password change on next login.
            </td>
        </tr>
    </tbody>
    <tbody>
        <tr>
            <th colspan="2">
                <em><strong>Staff's Signature</strong>: Optional signature used on outgoing emails. &nbsp;<span class="error">&nbsp;<?php echo $errors['signature']; ?></span></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea class="richtext no-bar" name="signature" cols="21"
                    rows="5" style="width: 60%;"><?php echo $info['signature']; ?></textarea>
                <br><em>Signature is made available as a choice, on ticket reply.</em>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Account Status & Settings</strong>: Dept. and assigned group controls access permissions.</em>
            </th>
        </tr>
        <tr>
            <td width="180" class="required">
                Account Type:
            </td>
            <td>
                <input type="radio" name="isadmin" value="1" <?php echo $info['isadmin']?'checked="checked"':''; ?>>
                    <font color="red"><strong>Admin</strong></font>
                <input type="radio" name="isadmin" value="0" <?php echo !$info['isadmin']?'checked="checked"':''; ?>><strong>Staff</strong>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['isadmin']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Account Status:
            </td>
            <td>
                <input type="radio" name="isactive" value="1" <?php echo $info['isactive']?'checked="checked"':''; ?>><strong>Active</strong>
                <input type="radio" name="isactive" value="0" <?php echo !$info['isactive']?'checked="checked"':''; ?>><strong>Locked</strong>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['isactive']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Assigned Group:
            </td>
            <td>
                <select name="group_id" id="group_id">
                    <option value="0">&mdash; Select Group &mdash;</option>
                    <?php
                    $sql='SELECT group_id, group_name, group_enabled as isactive FROM '.GROUP_TABLE.' ORDER BY group_name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$name,$isactive)=db_fetch_row($res)){
                            $sel=($info['group_id']==$id)?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s %s</option>',$id,$sel,$name,($isactive?'':' (Disabled)'));
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['group_id']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Primary Department:
            </td>
            <td>
                <select name="dept_id" id="dept_id">
                    <option value="0">&mdash; Select Department &mdash;</option>
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
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['dept_id']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Staff's Time Zone:
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
               Daylight Saving:
            </td>
            <td>
                <input type="checkbox" name="daylight_saving" value="1" <?php echo $info['daylight_saving']?'checked="checked"':''; ?>>
                Observe daylight saving
                <em>(Current Time: <strong><?php echo Format::date($cfg->getDateTimeFormat(),Misc::gmtime(),$info['tz_offset'],$info['daylight_saving']); ?></strong>)</em>
            </td>
        </tr>
        <tr>
            <td width="180">
                Limited Access:
            </td>
            <td>
                <input type="checkbox" name="assigned_only" value="1" <?php echo $info['assigned_only']?'checked="checked"':''; ?>>Limit ticket access to ONLY assigned tickets.
            </td>
        </tr>
        <tr>
            <td width="180">
                Directory Listing:
            </td>
            <td>
                <input type="checkbox" name="isvisible" value="1" <?php echo $info['isvisible']?'checked="checked"':''; ?>>Show the user on staff's directory
            </td>
        </tr>
        <tr>
            <td width="180">
                Vacation Mode:
            </td>
            <td>
                <input type="checkbox" name="onvacation" value="1" <?php echo $info['onvacation']?'checked="checked"':''; ?>>
                    Staff on vacation mode. (<i>No ticket assignment or alerts</i>)
            </td>
        </tr>
        <?php
         //List team assignments.
         $sql='SELECT team.team_id, team.name, isenabled FROM '.TEAM_TABLE.' team  ORDER BY team.name';
         if(($res=db_query($sql)) && db_num_rows($res)){ ?>
        <tr>
            <th colspan="2">
                <em><strong>Assigned Teams</strong>: Staff will have access to tickets assigned to a team they belong to regardless of the ticket's department. </em>
            </th>
        </tr>
        <?php
         while(list($id,$name,$isactive)=db_fetch_row($res)){
             $checked=($info['teams'] && in_array($id,$info['teams']))?'checked="checked"':'';
             echo sprintf('<tr><td colspan=2><input type="checkbox" name="teams[]" value="%d" %s>%s %s</td></tr>',
                     $id,$checked,$name,($isactive?'':' (Disabled)'));
         }
        } ?>
        <tr>
            <th colspan="2">
                <em><strong>Admin Notes</strong>: Internal notes viewable by all admins.&nbsp;</em>
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
    <input type="reset"  name="reset"  value="Reset">
    <input type="button" name="cancel" value="Cancel" onclick='window.location.href="staff.php"'>
</p>
</form>
