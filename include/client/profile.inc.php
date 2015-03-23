<h1><?php echo __('Manage Your Profile Information'); ?></h1>
<p><?php echo __(
'Use the forms below to update the information we have on file for your account'
); ?>
</p>
<form action="profile.php" method="post">
  <?php csrf_token(); ?>
<table class="padded">
<?php
foreach ($user->getForms() as $f) {
    $f->render(false);
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
        <td class="text-nowrap">
            <?php echo __('Time Zone');?>:
        </td>
        <td>
            <select name="timezone" class="chosen-select" id="timezone-dropdown">
                <option value=""><?php echo __('System Default'); ?></option>
<?php foreach (DateTimeZone::listIdentifiers() as $zone) { ?>
                <option value="<?php echo $zone; ?>" <?php
                if ($info['timezone'] == $zone)
                    echo 'selected="selected"';
                ?>><?php echo str_replace('/', ' / ', $zone); ?></option>
<?php } ?>
            </select>
            <div class="error"><?php echo $errors['timezone']; ?></div>
        </td>
    </tr>
    <tr>
        <td class="text-nowrap">
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
<?php if ($acct->isPasswdResetEnabled()) { ?>
<tr>
    <td colspan=2">
        <div><hr><h3><?php echo __('Access Credentials'); ?></h3></div>
    </td>
</tr>
<?php if (!isset($_SESSION['_client']['reset-token'])) { ?>
<tr>
    <td class="text-nowrap">
        <?php echo __('Current Password'); ?>:
    </td>
    <td>
        <input type="password" size="18" name="cpasswd" value="<?php echo $info['cpasswd']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['cpasswd']; ?></span>
    </td>
</tr>
<?php } ?>
<tr>
    <td class="text-nowrap">
        <?php echo __('New Password'); ?>:
    </td>
    <td>
        <input type="password" size="18" name="passwd1" value="<?php echo $info['passwd1']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd1']; ?></span>
    </td>
</tr>
<tr>
    <td class="text-nowrap">
        <?php echo __('Confirm New Password'); ?>:
    </td>
    <td>
        <input type="password" size="18" name="passwd2" value="<?php echo $info['passwd2']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span>
    </td>
</tr>
<?php } ?>
<?php } ?>
</table>
<hr>
<p style="text-align: center;">
    <input type="submit" class="btn btn-success" value="Update"/>
    <input type="reset" class="btn btn-warning" value="Reset"/>
    <input type="button" class="btn btn-default" value="Cancel" onclick="javascript:
        window.location.href='index.php';"/>
</p>
</form>
<script type="text/javascript">
$('#timezone-dropdown').chosen({
    header: <?php echo JsonDataEncoder::encode(__('Time Zones')); ?>,
    allow_single_deselect: true
});
</script>
