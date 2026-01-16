<?php 
if(!defined('OSTCLIENTINC')) die('Access Denied');

$email=Format::input($_POST['lemail']?$_POST['lemail']:$_GET['e']);
$ticketid=Format::input($_POST['lticket']?$_POST['lticket']:$_GET['t']);

if ($cfg->isClientEmailVerificationRequired())
    $button = __("Email Access Link");
else
    $button = __("View Ticket");
?>
<h1><?php echo __('Check Ticket Status'); ?></h1>
<!-- osta --><div class="subtitle"><?php
echo __('Please provide your email address and a ticket number.');
if ($cfg->isClientEmailVerificationRequired())
    echo ' '.__('An access link will be emailed to you.');
else
    echo ' '.__('This will sign you in to view your ticket.');
?></div><!-- osta -->

<div class="clear"></div>

<form action="login.php" method="post" id="clientLogin">
    <?php csrf_token(); ?><?php echo Format::htmlchars($errors['login']); ?></strong>
<div id="check-ticket">
	<div id="access-link" class="client-choice">
		<div id="open-title">
			<?php echo __('Email Access Link'); ?>
		</div>
		<div id="open-text">
        <label for="email"><?php echo __('Email Address'); ?>
        <input id="email" placeholder="<?php echo __('e.g. john.doe@osticket.com'); ?>" type="text"
            name="lemail" size="30" value="<?php echo $email; ?>" class="nowarn"></label>
        <label for="ticketno"><?php echo __('Ticket Number'); ?>
        <input id="ticketno" type="text" name="lticket" placeholder="<?php echo __('e.g. 051243'); ?>"
            size="30" value="<?php echo $ticketid; ?>" class="nowarn"></label>
		</div>


		<input id="" class="client-choice-icon" type="submit" value="<?php echo $button; ?>">

	</div>
	</form>
	<?php if ($cfg && $cfg->getClientRegistrationMode() !== 'disabled') { ?>
	<div id="sign-in" class="client-choice">
		<div id="open-title"><?php echo __('Have an account with us?'); ?></div>
		<div id="open-text">
        	<div id="options">
				
						<strong></strong><br />
						<a href="login.php"><?php echo __('Sign In'); ?></a> <?php
					if ($cfg->isClientRegistrationEnabled()) { ?>
				<?php echo sprintf(__('or %s register for an account %s to access all your tickets.'),
					'<a href="account.php?do=create">','</a>');
					}
				}?>
				<br /><br /><!-- osta -->
				<?php
				if ($cfg->getClientRegistrationMode() != 'disabled'
					|| !$cfg->isClientLoginRequired()) {
					echo sprintf(
					__("If this is your first time contacting us or you've lost the ticket number, please %s open a new ticket %s"),
						'<a href="open.php">','</a>');
				} ?>
            </div>
		</div>
	</div>
</div>
<div class="clear"></div>

