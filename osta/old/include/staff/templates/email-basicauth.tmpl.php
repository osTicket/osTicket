<h3><?php echo __('Basic Authentication'); ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>
<?php
$action = sprintf('#email/%d/auth/config/%s/%s',
        $email->getId(), $type, $auth);

if (isset($errors['err'])) {
    echo sprintf('<p id="msg_error">%s</p>', $errors['err']);
} elseif (isset($info['warning'])) {
    echo sprintf('<p id="msg_warning">%s</p>', $info['warning']);
} elseif (isset($info['msg'])) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} ?>
<form method="post" action="<?php echo $action; ?>">
  <div class="quick-add">
    <?php echo $form->asTable(); ?>
  </div>
<hr/>
<p class="full-width">
    <span class="buttons" style="float:left">
        <input type="button" name="cancel" class="close" value="<?php echo
        __('Cancel'); ?>">
    </span>
    <span class="buttons" style="float:right">
        <input type="submit" value="<?php echo __('Save'); ?>">
    </span>
</p>
</form>
