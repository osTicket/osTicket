<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$email=Format::input($_POST['luser']?:$_GET['e']);
$passwd=Format::input($_POST['lpasswd']?:$_GET['t']);

$content = Page::lookupByType('banner-client');

if ($content) {
    list($title, $body) = $ost->replaceTemplateVariables(
        array($content->getName(), $content->getBody()));
} else {
    $title = __('Sign In');
    $body = __('To better serve you, we encourage our clients to register for an account and verify the email address we have on record.');
}

?>
<h1><?php echo Format::display($title); ?></h1>
<p><?php echo Format::display($body); ?></p>

<div class="row">
    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-body">
                <form action="login.php" method="post" id="clientLogin">
                    <?php csrf_token(); ?>
                    <div class="login-box">
                        <strong><?php echo Format::htmlchars($errors['login']); ?></strong>
                        <div class="form-group">
                            <label for=""><?php echo __('Email or Username'); ?></label>
                            <input id="username" placeholder="<?php echo __('Email or Username'); ?>" type="text" name="luser" size="30" value="<?php echo $email; ?>" class="nowarn">
                        </div>

                        <div class="form-group">
                            <label for=""><?php echo __('Password'); ?></label>
                            <input id="passwd" placeholder="<?php echo __('Password'); ?>" type="password" name="lpasswd" size="30" value="<?php echo $passwd; ?>" class="nowarn"></td>
                        </div>

                        <input class="btn btn-primary btn-block" type="submit" value="<?php echo __('Sign In'); ?>">
                        <?php if ($suggest_pwreset) { ?>
                            <a style="padding-top:4px;display:inline-block;" href="pwreset.php"><?php echo __('Forgot My Password'); ?></a>
                        <?php } ?>
                    </div>

                    <div class="form-group">
                        <?php
                        $ext_bks = array();
                        foreach (UserAuthenticationBackend::allRegistered() as $bk)
                            if ($bk instanceof ExternalAuthentication)
                                $ext_bks[] = $bk;

                        if (count($ext_bks)) {
                            foreach ($ext_bks as $bk) { ?>
                                <div class="external-auth"><?php $bk->renderExternalLink(); ?></div><?php
                            }
                        }
                        if ($cfg && $cfg->isClientRegistrationEnabled()) {
                            if (count($ext_bks)) echo '<hr/>'; ?>
                            <p style="margin-bottom: 5px">
                                <?php echo __('Not yet registered?'); ?> <a href="account.php?do=create"><?php echo __('Create an account'); ?></a>
                            </p>
                        <?php                   
                        } ?>
                    </div>
                </form>
            </div>
            <div class="panel-footer">
                <p>
                    <?php
                    if ($cfg->getClientRegistrationMode() != 'disabled'
                        || !$cfg->isClientLoginRequired()) {
                        echo sprintf(__('If this is your first time contacting us or you\'ve lost the ticket number, please %s open a new ticket %s'),
                            '<a href="open.php">', '</a>');
                    } ?>
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <?php echo __("I'm an agent"); ?>
            </div>
            <div class="panel-body">
                <div class="text-center">
                    <a class="btn btn-warning btn-block" href="<?php echo ROOT_PATH; ?>scp/">
                        <i class="fa fa-sign-in"></i>
                        <?php echo __('sign in here'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>