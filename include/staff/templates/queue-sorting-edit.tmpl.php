<?php
/**
 * Calling conventions
 *
 * $column - <QueueColumn> instance for this column
 */
$sortid = $sort->getId();
$advanced = in_array('extra', $sort::getMeta()->getFieldNames());
?>
<h3 class="drag-handle"><?php echo __('Manage Sort Options'); ?> &mdash;
    <?php echo $sort->get('name') ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>

<form method="post" action="#tickets/search/sort/edit/<?php
    echo $sortid; ?>">

<?php if ($advanced) { ?>
  <ul class="clean tabs">
    <li class="active"><a href="#fields"><i class="icon-columns"></i>
      <?php echo __('Fields'); ?></a></li>
    <li><a href="#advanced"><i class="icon-cog"></i>
      <?php echo __('Advanced'); ?></a></li>
  </ul>

  <div class="tab_content" id="fields">
<?php } ?>

<?php
include 'queue-sorting.tmpl.php';
?>

<?php if ($advanced) { ?>
  </div>

  <div class="hidden tab_content" id="advanced">
    <?php echo $sort->getAdvancedConfigForm()->asTable(); ?>
  </div>

<?php } ?>

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
