<?php
if (!$info['title'])
    $info['title'] = 'Register: '.Format::htmlchars($user->getName());

// TODO: Set defaults
if (!$_POST) {
    $info['sendemail'] = true; // send email confirmation.
}

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
<div><p id="msg_info"><i class="icon-info-sign"></i>&nbsp;Complete the form
below to create a user account for <b><?php echo
$user->getName()->getOriginal(); ?></b>.</p></div>
<div id="user-registration" style="display:block; margin:5px;">
    <form method="post" class="user"
        action="#users/<?php echo $user->getId(); ?>/register">
        <input type="hidden" name="id" value="<?php echo $user->getId(); ?>" />
        <table width="100%">
        <tbody>
            <tr>
                <th colspan="2">
                    <em><strong>User Account Login</strong></em>
                </th>
            </tr>
            <tr>
                <td width="180">
                    Status:
                </td>
                <td>
                  <input type="checkbox" id="sendemail" name="sendemail" value="1"
                    <?php echo $info['sendemail'] ? 'checked="checked"' : ''; ?> >
                    Send account confirmation email to user.
                </td>
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
        </tbody>
        <tbody id="password"
            style="<?php echo $info['sendemail'] ? 'display:none;' : ''; ?>"
            >
            <tr>
                <td width="180">
                    Temp. Password:
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
                    <input type="checkbox" name="dst" value="1" <?php echo $info['dst'] ? 'checked="checked"' : ''; ?>>
                    Observe daylight saving
                </td>
            </tr>
        </tbody>
        </table>
        <hr>
        <p class="full-width">
            <span class="buttons" style="float:left">
                <input type="reset" value="Reset">
                <input type="button" name="cancel" class="close" value="Cancel">
            </span>
            <span class="buttons" style="float:right">
                <input type="submit" value="Create Account">
            </span>
         </p>
    </form>
</div>
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
