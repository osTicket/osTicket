<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$userid=Format::input($_POST['userid']);
?>
<div class="row">
    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading"><?php echo __('Forgot My Password'); ?></div>
            <div class="panel-body">
                <p>
                    <?php echo __(
                    'Enter your username or email address again in the form below and press the <strong>Login</strong> to access your account and reset your password.');
                    ?>
                </p>
                <form action="pwreset.php" method="post" id="clientLogin">
                    <?php csrf_token(); ?>
                    <input type="hidden" name="do" value="reset"/>
                    <input type="hidden" name="token" value="<?php echo Format::htmlchars($_REQUEST['token']); ?>"/>
                    <strong><?php echo Format::htmlchars($banner); ?></strong>
                    <div class="form-group">
                        <label for="username"><?php echo __('Username'); ?>:</label>
                        <input id="username" type="text" name="userid" size="30" value="<?php echo $userid; ?>">
                    </div>
                    <input class="btn btn-primary btn-block" type="submit" value="Login">
                </form>
            </div>
        </div>
    </div>
</div>
