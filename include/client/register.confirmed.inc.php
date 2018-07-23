<div class="row">
    <div class="col-md-4">
        <div class="panel panel-default">
            <?php if ($content) {
                list($title, $body) = $ost->replaceTemplateVariables(array($content->getName(), $content->getBody())); ?>
                <div class="panel-heading"><?php echo Format::display($title); ?></div>
                <div class="panel-body">
                    <p><?php echo Format::display($body); ?></p>
                </div>
            <?php } else { ?>
                <div class="panel-heading"><?php echo __('Account Registration'); ?></div>
                <div class="panel-body">
                    <p><strong><?php echo __('Thanks for registering for an account.'); ?></strong></p>
                    <p>
                        <?php echo __(
                        "You've confirmed your email address and successfully activated your account.  You may proceed to check on previously opened tickets or open a new ticket."
                        ); ?>
                    </p>
                    <p><em><?php echo __('Your friendly support center'); ?></em></p>
                </div>
            <?php } ?>
        </div>
    </div>
</div>