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
We've just sent you an email to the address you entered. Please follow the
link in the email to confirm your account and gain access to your tickets.
</p>
<?php } ?>
