<?php
if (!defined('OSTCLIENTINC')) die('Access Denied');

$email = Format::input($_POST['luser'] ?: $_GET['e']);
$passwd = Format::input($_POST['lpasswd'] ?: $_GET['t']);

$content = Page::lookupByType('banner-client');

if ($content) {
    list($title, $body) = $ost->replaceTemplateVariables(
        array($content->getLocalName(), $content->getLocalBody())
    );
} else {
    $title = __('Sign In');
    $body = __('To better serve you, we encourage our clients to register for an account and verify the email address we have on record.');
}

?>
<h1 class="alg-text-h1 text-center"><?php echo Format::display($title); ?></h1>
<p class="mt-2 alg-text-h3 text-center"><?php echo Format::display($body); ?></p>
<div class="alg-container">
    <form action="login.php" method="post" class="alg-rounded-large bg-transparent shadow-none border-0" id="clientLogin">
        <?php csrf_token(); ?>
        <div class="row d-flex">
            <div class="col-md-6 alg-signin-left d-md-flex justify-content-center d-none alg-bg-background-90">
                <img src="../../images/3094352-removebg-preview 1.png" alt="" srcset="" class="signIn-image alg-img-obj-fit">
            </div>
            <div class="col-md-6 alg-signin-right alg-bg-background-100 d-flex flex-column justify-content-md-center p-md-3 p-4 p-md-5">
                <div class="">
                    <div class="form-group">
                        <strong><?php echo Format::htmlchars($errors['login']); ?></strong>
                        <div>
                            <label for="exampleInputPassword1" class="text-start alg-text-p">Email Address</label>
                            <input style="width: 97%;" id="username" placeholder="<?php echo __('Email or Username'); ?>" type="text" name="luser" size="30" value="<?php echo $email; ?>" class=" form-control ticket-input">
                        </div>





                        <div class="form-group mt-4">
                            <label for="exampleInputPassword1" class="text-start alg-text-p">Email Address</label>

                            <input id="passwd" style="width: 97%;" placeholder="<?php echo __('Password'); ?>" type="password" name="lpasswd" size="30" maxlength="128" value="<?php echo $passwd; ?>" class="form-control ticket-input"></td>
                        </div>
                        <p>
                            <input class=" ticket-input mt-4 alg-text-p alg-bg-secondary-50 py-2 text-white" style="width: 97%;background-color:var(--secondary-50)" type="submit" value="<?php echo __('Sign In'); ?>">
                            <?php if ($suggest_pwreset) { ?>
                                <a style="padding-top:4px;display:inline-block;" href="pwreset.php"><?php echo __('Forgot My Password'); ?></a>
                            <?php } ?>
                        </p>
                    </div>
                    <div class="text-center mt-4">
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
                                                                                                if (count($ext_bks)) echo '<hr style="width:70%"/>'; ?>
                            <div style="margin-bottom: 5px">
                                <?php echo __('Not yet registered?'); ?> <a href="account.php?do=create"><?php echo __('Create an account'); ?></a>
                            </div>
                        <?php } ?>
                        <div>
                            <b><?php echo __("I'm an agent"); ?></b> —
                            <a href="<?php echo ROOT_PATH; ?>scp/"><?php echo __('sign in here'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<br>
<p class="text-center mt-2">
    <?php
    if (
        $cfg->getClientRegistrationMode() != 'disabled'
        || !$cfg->isClientLoginRequired()
    ) {
        echo sprintf(
            __('If this is your first time contacting us or you\'ve lost the ticket number, please %s open a new ticket %s'),
            '<a href="open.php">',
            '</a>'
        );
    } ?>
</p>