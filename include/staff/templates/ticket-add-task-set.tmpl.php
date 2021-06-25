<h3 class="drag-handle"><i class="icon-paste"></i> <?php
   echo sprintf(__('Ticket #%s'), $ticket->getNumber()).' - '.__('Attach Tasks'); ?></i></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>
<form method="post" action="<?php echo $action; ?>">
<?php echo $form->asTable(); ?>
  <hr/>
  <p class="full-width">
    <span class="buttons pull-left">
      <input type="reset" value="<?php echo __('Reset'); ?>">
      <input type="button" name="cancel" class="<?php
        echo $user ? 'cancel' : 'close' ?>" value="<?php echo __('Cancel'); ?>">
    </span>
    <span class="buttons pull-right">
      <button class="button" type="submit">
        <i class="icon-pushpin"></i>
        <?php echo __('Attach'); ?>
      </button>
    </span>
 </p>
</form>
