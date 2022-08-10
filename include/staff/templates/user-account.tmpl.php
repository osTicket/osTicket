<?php
$account = $user->getAccount();
$access = (isset($info['_target']) && $info['_target'] == 'access');
$org = $user->getOrganization();

if (!$info['title'])
    $info['title'] = Format::htmlchars($user->getName());
?>
<h3 class="drag-handle"><?php echo $info['title']; ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<div class="clear"></div>
<hr/>
<?php
if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['msg']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} ?>
<form method="post" class="user" action="#users/<?php echo $user->getId(); ?>/manage" >
<ul class="tabs" id="user-account-tabs">
    <li <?php echo !$access? 'class="active"' : ''; ?>><a href="#user-account"
        ><i class="icon-user"></i>&nbsp;<?php echo __('User Information'); ?></a></li>
    <li <?php echo $access? 'class="active"' : ''; ?>><a href="#user-access"
        ><i class="icon-fixed-width icon-lock faded"></i>&nbsp;<?php echo __('Manage Access'); ?></a></li>
</ul>


 <input type="hidden" name="id" value="<?php echo $user->getId(); ?>" />
<div id="user-account-tabs_container">
 <div class="tab_content"  id="user-account" style="display:<?php echo $access? 'none' : 'block'; ?>; margin:5px;">
    <form method="post" class="user" action="#users/<?php echo $user->getId(); ?>/manage" >
        <input type="hidden" name="id" value="<?php echo $user->getId(); ?>" />
        <table width="100%">
        <tbody>
            <tr>
                <th colspan="2">
                    <em><strong><?php echo __('User Information'); ?></strong></em>
                </th>
            </tr>
            <tr>
                <td width="180">
                    <?php echo __('Name'); ?>:
                </td>
                <td> <?php echo Format::htmlchars($user->getName()); ?> </td>
            </tr>
            <tr>
                <td width="180">
                    <?php echo __('Email'); ?>:
                </td>
                <td> <?php echo $user->getEmail(); ?> </td>
            </tr>
            <tr>
                <td width="180">
                    <?php echo __('Organization'); ?>:
                </td>
                <td><?php echo $org ? Format::htmlchars($org->getName()) : ''; ?></td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <th colspan="2"><em><strong><?php echo __('User Preferences'); ?></strong></em></th>
            </tr>
            <tr>
                <td width="180">
                    <?php echo __('Time Zone');?>:
                </td>
                <td>
                    <?php
                    $TZ_NAME = 'timezone';
                    $TZ_TIMEZONE = $info['timezone'];
                    include STAFFINC_DIR.'templates/timezone.tmpl.php'; ?>
                    <div class="error"><?php echo $errors['timezone']; ?></div>
                </td>
            </tr>
        </tbody>
        </table>
 </div>
 <div class="tab_content"  id="user-access" style="display:<?php echo $access? 'block' : 'none'; ?>; margin:5px;">
        <table width="100%">
        <tbody>
            <tr>
                <th colspan="2"><em><strong><?php echo __('Account Access'); ?></strong></em></th>
            </tr>
            <tr>
                <td width="180"><?php echo __('Status'); ?>:</td>
                <td> <?php echo $user->getAccountStatus(); ?> </td>
            </tr>
            <tr>
                <td width="180">
                    <?php echo __('Username'); ?>:
                </td>
                <td>
                    <input type="text" size="35" name="username" value="<?php echo $info['username']; ?>" autocomplete="new-password">
                    <i class="help-tip icon-question-sign" data-title="<?php
                        echo __("Login via email"); ?>"
                    data-content="<?php echo sprintf('%s: %s',
                        __('Users can always sign in with their email address'),
                        $user->getEmail()); ?>"></i>
                    <div class="error"><?php echo $errors['username']; ?></div>
                </td>
            </tr>
            <tr>
                <td width="180">
                    <?php echo __('New Password'); ?>:
                </td>
                <td>
                    <input type="password" size="35" name="passwd1" value="<?php echo $info['passwd1']; ?>" autocomplete="new-password">
                    &nbsp;<span class="error">&nbsp;<?php echo
                    $errors['passwd1']; ?></span>
                </td>
            </tr>
            <tr>
                <td width="180">
                   <?php echo __('Confirm Password'); ?>:
                </td>
                <td>
                    <input type="password" size="35" name="passwd2" value="<?php echo $info['passwd2']; ?>" autocomplete="new-password">
                    &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <th colspan="2"><em><strong><?php echo __('Account Flags'); ?></strong></em></th>
            </tr>
            <tr>
                <td colspan="2">
                <?php
                  echo sprintf('<div><input type="checkbox" name="locked-flag" %s
                       value="1"> %s</div>',
                       $account->isLocked() ?  'checked="checked"' : '',
                       __('Administratively Locked')
                       );
                  ?>
                   <div><input type="checkbox" name="pwreset-flag" value="1" <?php
                    echo $account->isPasswdResetForced() ?
                    'checked="checked"' : ''; ?>> <?php echo __('Password Reset Required'); ?></div>
                   <div><input type="checkbox" name="forbid-pwchange-flag" value="1" <?php
                    echo !$account->isPasswdResetEnabled() ?
                    'checked="checked"' : ''; ?>> <?php echo __('User cannot change password'); ?></div>
                </td>
            </tr>
        </tbody>
        </table>
   </div>
   </div>
   <hr>
   <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" name="cancel" class="close" value="<?php echo __('Cancel'); ?>">
        </span>
        <span class="buttons pull-right">
            <input type="submit"
                value="<?php echo __('Save Changes'); ?>">
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
