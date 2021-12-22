<h3 class="drag-handle"><?php echo $title ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<div class="clear"></div>
<hr/>
<?php if (isset($errors['err'])) { ?>
    <div id="msg_error" class="error-banner"><?php echo Format::htmlchars($errors['err']); ?></div>
<?php } ?>
<form method="post" action="#<?php echo $path; ?>">
  <div class="quick-add">
    <?php echo $form->asTable(); ?>
  </div>
  <hr>
  <p class="full-width">
    <span class="buttons pull-left">
      <input type="reset" value="<?php echo __('Reset'); ?>" />
      <input type="button" name="cancel" class="close"
        value="<?php echo __('Cancel'); ?>" />
    </span>
    <span class="buttons pull-right">
      <input type="submit" value="<?php
        echo $verb ?: __('Create'); ?>" />
    </span>
  </p>
  <div class="clear"></div>
</form>
