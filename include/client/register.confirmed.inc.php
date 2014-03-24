<?php if ($content) { ?>
<h1><?php echo Format::display($content->getName()); ?></h1>
<p><?php
echo Format::display($content->getBody()); ?>
</p>
<?php } else { ?>
<h1>Account Registration</h1>
<p>
<strong>Thanks for registering for an account.</strong>
</p>
<p>
You've confirmed your email address and successfully activated your account.
You may proceed to check on previously opened tickets or open a new ticket.
</p>
<p><em>Your friendly support center</em></p>
<?php } ?>
