<h1><?php echo __('Manage Your Profile Information'); ?></h1>
<p><?php echo __(
'Use the forms below to update the information we have on file for your account'
); ?>
</p>
<form action="profile.php" method="post">
  <?php csrf_token(); ?>
<table width="800" class="padded">
<?php
foreach ($user->getForms() as $f) {
    $f->render(['staff' => false]);
}
if ($acct = $thisclient->getAccount()) {
    $info=$acct->getInfo();
    $info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<tr>
    <td colspan="2">
        <div><hr><h3><?php echo __('Preferences'); ?></h3>
        </div>
    </td>
</tr>
    <tr>
        <td width="180">
            <?php echo __('Time Zone');?>:
        </td>
        <td>
            <?php
            $TZ_NAME = 'timezone';
            $TZ_TIMEZONE = $info['timezone'];
            include INCLUDE_DIR.'staff/templates/timezone.tmpl.php'; ?>
            <div class="error"><?php echo $errors['timezone']; ?></div>
        </td>
    </tr>
<?php if ($cfg->getSecondaryLanguages()) { ?>
    <tr>
        <td width="180">
            <?php echo __('Preferred Language'); ?>:
        </td>
        <td>
    <?php
    $langs = Internationalization::getConfiguredSystemLanguages(); ?>
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
<?php }
      if ($acct->isPasswdResetEnabled()) { ?>
<tr>
    <td colspan="2">
        <div><hr><h3><?php echo __('Access Credentials'); ?></h3></div>
    </td>
</tr>
<?php if (!isset($_SESSION['_client']['reset-token'])) { ?>
<tr>
    <td width="180">
        <?php echo __('Current Password'); ?>:
    </td>
    <td>
        <input type="password" size="18" name="cpasswd" maxlength="128" value="<?php echo $info['cpasswd']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['cpasswd']; ?></span>
    </td>
</tr>
<?php } ?>
<tr>
    <td width="180">
        <?php echo __('New Password'); ?>:
    </td>
    <td>
        <input type="password" size="18" name="passwd1" maxlength="128" value="<?php echo $info['passwd1']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd1']; ?></span>
    </td>
</tr>
<tr>
    <td width="180">
        <?php echo __('Confirm New Password'); ?>:
    </td>
    <td>
        <input type="password" size="18" name="passwd2" maxlength="128" value="<?php echo $info['passwd2']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span>
    </td>
</tr>
<?php } ?>
<?php } ?>
<?php 
    $hasAuth2FAPlugin = false;
    if (class_exists('Auth2FAPlugin')) {
        foreach (PluginManager::allActive() as $plugin) {
            if ($plugin instanceof Auth2FAPlugin) {
                $hasAuth2FAPlugin = true;
                break;
            }
        }
    }
?>
<?php if($hasAuth2FAPlugin && $bks=User2FABackend::allRegistered()) {
    $account = $user->getAccount();
    $current = $account->get2FABackendId();
    $required2fa = $cfg->require2FAForUsers();
    $_config = $account->getConfig();
?>
<tr>
    <td colspan="2">
        <div><hr><h3 <?php if ($required2fa) echo 'class="required"'; ?>><?php echo __('Default 2FA'); ?></h3></div>
    </td>
</tr>
<tr>
    <td width="180">
        <select name="default_2fa" id="default2fa-selection"
            style="width:300px">
            <?php
            if (!$required2fa) :?>
                <option value="">&mdash; <?php echo __('Disable'); ?> &mdash;</option>
            <?php endif; ?>
            <?php foreach ($bks as $bk): ?>
                <?php
                $configuration = $account->get2FAConfig($bk->getId());
                $configured = $configuration['verified'];
                ?>
            <option id="<?php echo $bk->getId(); ?>" value="<?php echo $bk->getId(); ?>" 
            <?php
            if ($current == $bk->getId() && $configured)
                echo ' selected="selected" '; ?>
            <?php
            if (!$configured)
                echo ' disabled="disabled" '; ?>
                ><?php
            echo $bk->getName(); ?></option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <button type="button" id="config2fa-button" class="action-button" onclick="javascript:
        $.dialog('ajax.php/users/'+<?php echo $account->getId();
                ?>+'/2fa/configure', 201);">
            <i class="icon-gear"></i> <?php echo __('Configure Options'); ?>
        </button>
        <i class="offset help-tip icon-question-sign" href="#config2fa"></i>
        <div class="error"><?php echo $errors['default_2fa']; ?></div>

    </td>
</tr>
<?php }?>
</table>
<hr>
<p style="text-align: center;">
    <input type="submit" value="<?php echo __('Update'); ?>"/>
    <input type="reset" value="<?php echo __('Reset'); ?>"/>
    <input type="button" value="<?php echo __('Cancel'); ?>" onclick="javascript:
        window.location.href='index.php';"/>
</p>
</form>
