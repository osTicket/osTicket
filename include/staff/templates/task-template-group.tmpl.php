<h3 class="drag-handle"><i class="icon-paste"></i> <?php echo __('Add Task Template Set'); ?></i></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>
<form method="post" action="<?php echo $info['action']; ?>">
    <?php echo $form->asTable(); ?>

    <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" name="cancel" class="<?php
                echo $user ? 'cancel' : 'close' ?>" value="<?php echo __('Cancel'); ?>">
        </span>
        <span class="buttons pull-right">
            <input type="submit" value="<?php echo __('Save Changes'); ?>">
        </span>
     </p>
</form>
