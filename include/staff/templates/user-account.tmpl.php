<?php
$account = $user->getAccount();
$access = (isset($info['_target']) && $info['_target'] == 'access');

if (!$info['title'])
    $info['title'] = Format::htmlchars($user->getName());
?>
<h3><?php echo $info['title']; ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<div class="clear"></div>
<hr/>
<?php
if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['msg']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} ?>
<ul class="tabs">
    <li><a href="#user-account" <?php echo !$access? 'class="active"' : ''; ?>
        ><i class="icon-user"></i>&nbsp;User Information</a></li>
    <li><a href="#user-access" <?php echo $access? 'class="active"' : ''; ?>
        ><i class="icon-fixed-width icon-lock faded"></i>&nbsp;Manage Access</a></li>
</ul>


<form method="post" class="user" action="#users/<?php echo $user->getId(); ?>/manage" >
 <input type="hidden" name="id" value="<?php echo $user->getId(); ?>" />
 <div class="tab_content"  id="user-account" style="display:<?php echo $access? 'none' : 'block'; ?>; margin:5px;">
    <form method="post" class="user" action="#users/<?php echo $user->getId(); ?>/manage" >
        <input type="hidden" name="id" value="<?php echo $user->getId(); ?>" />
        <table width="100%">
        <tbody>
            <tr>
                <th colspan="2">
                    <em><strong>User Information</strong></em>
                </th>
            </tr>
            <tr>
                <td width="180">
                    Name:
                </td>
                <td> <?php echo Format::htmlchars($user->getName()); ?> </td>
            </tr>
            <tr>
                <td width="180">
                    Email:
                </td>
                <td> <?php echo $user->getEmail(); ?> </td>
            </tr>
            <tr>
                <td width="180">
                    Organization:
                </td>
                <td>
                    <input type="text" size="35" name="org" value="<?php echo $info['org']; ?>">
                    &nbsp;<span class="error">&nbsp;<?php echo $errors['org']; ?></span>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <th colspan="2"><em><strong>User Preferences</strong></em></th>
            </tr>
                <td>Time Zone:</td>
                <td>
                    <select name="timezone_id" id="timezone_id">
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
                    &nbsp;<span class="error"><?php echo $errors['timezone_id']; ?></span>
                </td>
            </tr>
            <tr>
                <td width="180">
                   Daylight Saving:
                </td>
                <td>
                    <input type="checkbox" name="dst" value="1" <?php echo $info['dst']?'checked="checked"':''; ?>>
                    Observe daylight saving
                </td>
            </tr>
        </tbody>
        </table>
 </div>
 <div class="tab_content"  id="user-access" style="display:<?php echo $access? 'block' : 'none'; ?>; margin:5px;">
        <table width="100%">
        <tbody>
            <tr>
                <th colspan="2"><em><strong>Account Access</strong></em></th>
            </tr>
            <tr>
                <td width="180"> Status: </td>
                <td> <?php echo $user->getAccountStatus(); ?> </td>
            </tr>
            <tr>
                <td width="180">
                    Username:
                </td>
                <td>
                    <input type="text" size="35" name="username" value="<?php echo $info['username'] ?: $user->getEmail(); ?>">
                    &nbsp;<span class="error">&nbsp;<?php echo $errors['username']; ?></span>
                </td>
            </tr>
            <tr>
                <td width="180">
                    New Password:
                </td>
                <td>
                    <input type="password" size="35" name="passwd1" value="<?php echo $info['passwd1']; ?>">
                    &nbsp;<span class="error">&nbsp;<?php echo
                    $errors['passwd1']; ?></span>
                </td>
            </tr>
            <tr>
                <td width="180">
                   Confirm Password:
                </td>
                <td>
                    <input type="password" size="35" name="passwd2" value="<?php echo $info['passwd2']; ?>">
                    &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <th colspan="2"><em><strong>Account Flags</strong></em></th>
            </tr>
            <tr>
                <td colspan="2">
                <?php
                  echo sprintf('<div><input type="checkbox" name="locked-flag" %s
                       value="1"> Administratively Locked</div>',
                       $account->isLocked() ?  'checked="checked"' : ''
                       );
                  ?>
                   <div><input type="checkbox" name="pwreset-flag" value="1" <?php
                    echo $account->isPasswdResetForced() ?
                    'checked="checked"' : ''; ?>> Password Reset Required</div>
                   <div><input type="checkbox" name="forbid-pwchange-flag" value="1" <?php
                    echo !$account->isPasswdResetEnabled() ?
                    'checked="checked"' : ''; ?>> User Cannot Change Password</div>
                </td>
            </tr>
        </tbody>
        </table>
   </div>
   <hr>
   <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="reset" value="Reset">
            <input type="button" name="cancel" class="close" value="Cancel">
        </span>
        <span class="buttons" style="float:right">
            <input type="submit"
                value="Save Changes">
        </span>
    </p>
</form>
<div class="clear"></div>
<script type="text/javascript">
$(function() {
    $(document).on('click', 'input#sendemail', function(e) {
        if ($(this).prop('checked'))
            $('tbody#password').hide();
        else
            $('tbody#password').show();
    });
});
</script>
