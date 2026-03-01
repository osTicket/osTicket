<h3 class="drag-handle"><?php echo __('Add New Schedule'); ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>
<form method="post" action="<?php echo $action; ?>">
<?php echo csrf_token(); ?>
<div>
<?php
    $form =$form ?: Schedule::basicForm($_POST);
    include 'dynamic-form-simple.tmpl.php';
?>
</div>
<?php
if ($schedule) { ?>
<hr>
<div>
<input type="checkbox" checked="checked"
    name="sid"
    value="<?php echo $schedule->getId(); ?>">
&nbsp;<?php echo sprintf('Clone %s Entries',sprintf('<u>%s</u>',
            $schedule->getName())); ?>
    <div class="error"><?php echo $errors['sid']; ?> </div>
</div>
<?php
} ?>
<hr>
<p class="full-width">
    <span class="buttons pull-left">
        <input type="reset" value="<?php echo __('Reset'); ?>">
        <input type="button" value="<?php echo __('Cancel'); ?>" class="close">
    </span>
    <span class="buttons pull-right">
        <input type="submit" value="<?php echo __('Create'); ?>">
    </span>
 </p>
</form>
