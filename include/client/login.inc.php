<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$email=Format::input($_POST['luser']?:$_GET['e']);
$passwd=Format::input($_POST['lpasswd']?:$_GET['t']);

$content = Page::lookupByType('banner-client');

if ($content) {
    list($title, $body) = $ost->replaceTemplateVariables(
        array($content->getLocalName(), $content->getLocalBody()));
} else {
    $title = __('Sign In');
    $body = __('To better serve you, we encourage our clients to register for an account and verify the email address we have on record.');
}

?>
<h1><?php echo Format::display($title); ?></h1>
<div class="subtitle"><?php echo Format::display($body); ?></div>


<form action="login.php" method="post" id="clientLogin">
    <?php csrf_token(); ?>
<div id="check-ticket">
	<div id="login-sign-in" class="client-choice">
		<div id="open-title">
			<?php echo __('Sign In'); ?>
		</div>
		<div id="open-text">
		
            	<strong><?php echo Format::htmlchars($errors['login']); ?></strong>
				<label for="username"><?php echo __('Email or Username'); ?>
                <input id="username" placeholder="<?php echo __('Email or Username'); ?>" type="text" name="luser" size="30" value="<?php echo $email; ?>" class="nowarn"></label>
				<label for="passwd"><?php echo __('Password'); ?>
                <input id="passwd" placeholder="<?php echo __('Password'); ?>" type="password" name="lpasswd" size="30" value="<?php echo $passwd; ?>" class="nowarn"></label>
			
		</div>


				<input id="sign-in-button" class="client-choice-icon" type="submit" value="<?php echo __('Sign In'); ?>">

                <?php if ($suggest_pwreset) { ?>
                <a style="padding-top:4px;display:inline-block;" href="pwreset.php"><?php echo __('Forgot My Password'); ?></a>
                <?php } ?>
				
				
				
	</div>
	</form>
	<?php if ($cfg && $cfg->getClientRegistrationMode() !== 'disabled') { ?>
	<div id="login-options" class="client-choice">
		<div id="open-text">
        	<div id="options"><br />
			
			
                <b><?php echo __("I'm an agent"); ?></b> —
                <a href="<?php echo ROOT_PATH; ?>scp/"><?php echo __('sign in here'); ?></a>
                <br /><br /> 
				
				<?php
					$ext_bks = array();
					foreach (UserAuthenticationBackend::allRegistered() as $bk)
						if ($bk instanceof ExternalAuthentication)
							$ext_bks[] = $bk;

					if (count($ext_bks)) {
						foreach ($ext_bks as $bk) { ?>
				<div class="external-auth"><?php $bk->renderExternalLink(); ?></div>
				<?php
					}
					}
					if ($cfg && $cfg->isClientRegistrationEnabled()) {
					if (count($ext_bks)) echo '<hr style="width:70%"/>'; ?>

					<?php echo __('Not yet registered?'); ?> <a href="account.php?do=create"><?php echo __('Create an account'); ?></a>
					<br /><br />
                                <!--osta-->			
				<?php
				if ($cfg->getClientRegistrationMode() != 'disabled'
					|| !$cfg->isClientLoginRequired()) {
					echo sprintf(
					__("If this is your first time contacting us or you've lost the ticket number, please %s open a new ticket %s"),
						'<a href="open.php">','</a>');
				} ?>
				
				
				<div id="login-hide">
				<?php
					if ($cfg->isClientRegistrationEnabled()) { ?>
				<?php echo sprintf(__('or %s register for an account %s to access all your tickets.'),
					'<a href="account.php?do=create">','</a>');
					}
				}?>
				</div>		
				
				
				<?php } ?>
				
            </div>
		</div>
	</div>
</div>
<!--osta-->
<div class="clear"></div>