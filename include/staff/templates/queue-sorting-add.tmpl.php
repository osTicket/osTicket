<?php
/**
 * Calling conventions
 *
 * $column - <QueueColumn> instance for this column
 */
$colid = 0;
?>
<h3 class="drag-handle"><?php echo __('Add Sort Option'); ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>

<form method="post" action="#admin/quick-add/queue-sort">

<?php
include 'queue-sorting.tmpl.php';
?>

<hr>
<p class="full-width">
    <span class="buttons pull-left">
        <input type="reset" value="<?php echo __('Reset'); ?>">
        <input type="button" value="<?php echo __('Cancel'); ?>" class="close">
    </span>
    <span class="buttons pull-right">
        <input type="submit" value="<?php echo __('Save'); ?>">
    </span>
 </p>

 </form>
