<?php
if (!defined('OSTCLIENTINC')) {
    die('Access Denied');
}

$userid = Format::input($_POST['userid']);
?>
<h1><?= __('Forgot My Password'); ?></h1>
<p><?= __('Enter your username or email address again in the form below and press the <strong>Login</strong> to access your account and reset your password.'); ?>
<form action="pwreset.php" method="post" id="clientLogin">
    <div style="width:50%;display:inline-block">
        <?php csrf_token(); ?>
        <input type="hidden" name="do" value="reset"/>
        <input type="hidden" name="token" value="<?= Format::htmlchars($_REQUEST['token']); ?>"/>
        <strong><?= Format::htmlchars($banner); ?></strong>
        <br>
        <div>
            <label for="username"><?= __('Username'); ?>:</label>
            <input id="username" type="text" name="userid" size="30" value="<?= $userid; ?>">
        </div>
        <p><input class="btn" type="submit" value="Login"></p>
    </div>
</form>
