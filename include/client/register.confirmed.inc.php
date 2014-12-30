<?php
if ($content) :
    list($title, $body) = $ost->replaceTemplateVariables(
            array($content->getName(), $content->getBody()));
    ?>
    <h1><?= Format::display($title); ?></h1>
    <p><?= Format::display($body); ?></p>
<?php else : ?>
    <h1><?= __('Account Registration'); ?></h1>
    <p><strong><?= __('Thanks for registering for an account.'); ?></strong></p>
    <p><?= __("You've confirmed your email address and successfully activated your account.  You may proceed to check on previously opened tickets or open a new ticket."); ?></p>
    <p><em><?= __('Your friendly support center'); ?></em></p>
<?php endif;
