<?php
/**
 * Calling conventions
 *
 * $column - <QueueColumn> instance for this column
 */
$colid = $column->getId();
?>
<h3 class="drag-handle"><?php echo __('Manage Queue Column'); ?> &mdash;
    <?php echo $column->get('name') ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>

<form method="post" action="#tickets/search/column/edit/<?php
    echo $colid; ?>">

<?php
include 'queue-column.tmpl.php';
?>

<hr>
<p class="full-width">
    <span class="buttons pull-left">
        <input type="reset" value="<?php echo __('Reset'); ?>" class="btn btn-sm btn-warning">
        <input type="button" value="<?php echo __('Cancel'); ?>" class="close btn-danger">
    </span>
    <span class="buttons pull-right">
        <input type="submit" value="<?php echo __('Save'); ?>" class="btn btn-sm btn-primary">
    </span>
 </p>

 </form>
