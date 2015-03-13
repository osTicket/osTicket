<?php
global $cfg;

if (!$info['title'])
    $info['title'] = sprintf(__('Register: %s'), Format::htmlchars($user->getName()));

if (!$_POST) {

    $info['sendemail'] = true; // send email confirmation.

    if (!isset($info['timezone_id']))
        $info['timezone_id'] = $cfg->getDefaultTimezoneId();

    if (!isset($info['dst']))
        $info['dst'] = $cfg->observeDaylightSaving();
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
<div><p id="msg_info"><i class="icon-info-sign"></i>&nbsp;<?php
echo sprintf(__(
'Complete the form below to create a user account for <b>%s</b>.'
), Format::htmlchars($user->getName()->getOriginal())
); ?>
</p></div>
<div id="user-registration" style="display:block; margin:5px;">
    <form method="post" class="user"
        action="#users/<?php echo $user->getId(); ?>/register">
        <input type="hidden" name="id" value="<?php echo $user->getId(); ?>" />
        <table width="100%">
        <tbody>
            <tr>
                <th colspan="2">
                    <em><strong><?php echo __('User Account Login'); ?></strong></em>
                </th>
            </tr>
            <tr>
                <td><?php echo __('Authentication Sources'); ?>:</td>
                <td>
            <select name="backend" id="backend-selection" onchange="javascript:
                if (this.value != '' && this.value != 'client') {
                    $('#activation').hide();
                    $('#password').hide();
                }
                else {
                    $('#activation').show();
                    if ($('#sendemail').is(':checked'))
                        $('#password').hide();
                    else
                        $('#password').show();
                }
                ">
                <option value="">&mdash; <?php echo __('Use any available backend'); ?> &mdash;</option>
            <?php foreach (UserAuthenticationBackend::allRegistered() as $ab) {
                if (!$ab->supportsInteractiveAuthentication()) continue; ?>
                <option value="<?php echo $ab::$id; ?>" <?php
                    if ($info['backend'] == $ab::$id)
                        echo 'selected="selected"'; ?>><?php
                    echo $ab->getName(); ?></option>
            <?php } ?>
            </select>
                </td>
            </tr>
            <tr>
                <td width="180">
                    <?php echo __('Username'); ?>:
                </td>
                <td>
                    <input type="text" size="35" name="username" value="<?php echo $info['username'] ?: $user->getEmail(); ?>">
                    &nbsp;<span class="error">&nbsp;<?php echo $errors['username']; ?></span>
                </td>
            </tr>
        </tbody>
        <tbody id="activation">
            <tr>
                <td width="180">
                    <?php echo __('Status'); ?>:
                </td>
                <td>
                  <input type="checkbox" id="sendemail" name="sendemail" value="1"
                    <?php echo $info['sendemail'] ? 'checked="checked"' :
                    ''; ?> ><?php echo sprintf(__(
                    'Send account activation email to %s.'), $user->getEmail()); ?>
                </td>
            </tr>
        </tbody>
        <tbody id="password"
            style="<?php echo $info['sendemail'] ? 'display:none;' : ''; ?>"
            >
            <tr>
                <td width="180">
                    <?php echo __('Temporary Password'); ?>:
                </td>
                <td>
                    <input type="password" size="35" name="passwd1" value="<?php echo $info['passwd1']; ?>">
                    &nbsp;<span class="error">&nbsp;<?php echo
                    $errors['passwd1']; ?></span>
                </td>
            </tr>
            <tr>
                <td width="180">
                   <?php echo __('Confirm Password'); ?>:
                </td>
                <td>
                    <input type="password" size="35" name="passwd2" value="<?php echo $info['passwd2']; ?>">
                    &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo __('Password Change'); ?>:
                </td>
                <td colspan=2>
                    <input type="checkbox" name="pwreset-flag" value="1" <?php
                        echo $info['pwreset-flag'] ?  'checked="checked"' : ''; ?>>
                        <?php echo __('Require password change on login'); ?>
                    <br/>
                    <input type="checkbox" name="forbid-pwreset-flag" value="1" <?php
                        echo $info['forbid-pwreset-flag'] ?  'checked="checked"' : ''; ?>>
                        <?php echo __('User cannot change password'); ?>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <th colspan="2"><em><strong><?php echo
                    __('User Preferences'); ?></strong></em></th>
            </tr>
                <td><?php echo __('Time Zone'); ?>:</td>
                <td>
                    <select name="timezone_id" id="timezone_id">
                        <?php
                        $sql='SELECT id, offset, timezone FROM '.TIMEZONE_TABLE.' ORDER BY id';
                        if(($res=db_query($sql)) && db_num_rows($res)){
                            while(list($id, $offset, $tz) = db_fetch_row($res)) {
                                $sel=($info['timezone_id']==$id) ? 'selected="selected"' : '';
                                echo sprintf('<option value="%d" %s>GMT %s - %s</option>',
                                        $id, $sel, $offset, $tz);
                            }
                        }
                        ?>
                    </select>
                    &nbsp;<span class="error"><?php echo $errors['timezone_id']; ?></span>
                </td>
            </tr>
            <tr>
                <td width="180">
                   <?php echo __('Daylight Saving'); ?>:
                </td>
                <td>
                    <input type="checkbox" name="dst" value="1" <?php echo $info['dst'] ? 'checked="checked"' : ''; ?>>
                    <?php echo __('Observe daylight saving'); ?>
                </td>
            </tr>
        </tbody>
        </table>
        <hr>
        <p class="full-width">
            <span class="buttons pull-left">
                <input type="reset" value="<?php echo __('Reset'); ?>">
                <input type="button" name="cancel" class="close" value="<?php echo __('Cancel'); ?>">
            </span>
            <span class="buttons pull-right">
                <input type="submit" value="<?php echo __('Create Account'); ?>">
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
