
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
	<div class="row align-items-center">
		<div class="col-md align-self-center">
		<h1><?php echo Format::display($title); ?></h1>
		</div>
	</div>
	<div class="row align-items-center">
		<div class="col-md align-self-center">	
			<p><?php echo Format::display($body); ?></p>
		</div>	
	</div>
	<form action="login.php" method="post" id="clientLogin" class="form-inline">
   	<?php csrf_token(); ?>
	<div style="display:contents">
    <div class="login-box">

    	<strong><?php echo Format::htmlchars($errors['login']); ?></strong>
    	<div class="row"> 
    		<div class="col-md">
    		 		
					<div class="row">
    					<div class="col-sm">
    					 	<div class="form-group">					   					 		
    					 		<label for="username" style="width: 100%">
    					 			<?php echo __('Email or Username'); ?>
    					 			</label>		 		
      	  					<input id="username" placeholder="<?php echo __('Email or Username'); ?>" type="text" name="luser" size="30" value="<?php echo $email; ?>" 
      	  								class="nowarn form-control">  	  						
							</div>
    					</div>
    				</div>
    
    				<div class="row"> 
    					<div class="col-sm">
 	   					<div class="form-group">
    					 		<label for="passwd" style="width: 100%">
    					 			<?php echo __('Password'); ?>
    					 		</label>
   	   	  				<input id="passwd" placeholder="<?php echo __('Password'); ?>" type="password" name="lpasswd" size="30" value="<?php echo $passwd; ?>" class="nowarn"></td>
    						</div>
    					</div>
    				</div>
    				<div class="row">
    					<div class="col">
      	  					<input class="btn btn-md btn-primary" type="submit" value="<?php echo __('Sign In'); ?>" />
						</div>
					</div>
					<div class="row">
						<div class="col">
							<?php if ($suggest_pwreset) { ?>
       							 <a style="width:50%;padding-top:4px;display:inline-block;" href="pwreset.php"><?php echo __('Forgot My Password'); ?></a>
								<?php } ?>
   					</div>
    				</div>
    			
    		</div>
			<div class="col-md">    
				<div class="row" style="padding-top: 1rem; padding-left: 2rem;"> 
     				<div class="col-md">
						<?php
							$ext_bks = array();
							foreach (UserAuthenticationBackend::allRegistered() as $bk)
    							if ($bk instanceof ExternalAuthentication)
       						 $ext_bks[] = $bk;

								if (count($ext_bks)) 
									{
    									foreach ($ext_bks as $bk) 
    									{     				?>
											<div class="external-auth"><?php $bk->renderExternalLink(); ?></div><?php
    									}
									}
									?>
    				</div>
    			</div>									
									<?php
								if ($cfg && $cfg->isClientRegistrationEnabled()) {
    								if (count($ext_bks)) echo '<hr style="width:70%"/>'; ?>

    			<div class="row" style="padding-left: 2rem;">
    				<div class="col-md">
			    		<?php echo __('Not yet registered?'); ?> <a href="account.php?do=create"><?php echo __('Create an account'); ?></a>
    				</div>
    			</div>
					<?php } ?>
    			<div class="row" style="padding-top: 2rem; padding-left: 2rem;">
    				<div class="col-md">
		    			<b><?php echo __("I'm an agent"); ?></b> —
    					<a href="<?php echo ROOT_PATH; ?>scp/" class="btn btn-md btn-outline-primary"><?php echo __('sign in here'); ?></a>
    				</div>
    			</div>   	
    		</div>
    	</div>  
    </div>
	</div>
</form>
<br>
<div class="row" style="padding-left: 3rem;">
<?php
if ($cfg->getClientRegistrationMode() != 'disabled'
    || !$cfg->isClientLoginRequired()) {
    echo sprintf(__('If this is your first time contacting us or you\'ve lost the ticket number, please %s open a new ticket %s'),
        '<a href="open.php">', '</a>');
} ?>
</div>





