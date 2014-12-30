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
    <p><?= __("We've just sent you an email to the address you entered. Please follow the link in the email to confirm your account and gain access to your tickets."); ?></p>
<?php endif;
