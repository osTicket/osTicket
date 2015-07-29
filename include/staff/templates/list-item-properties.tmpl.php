<?php
    $properties_form = $item ? $item->getConfigurationForm($_POST ?: null)
        : $list->getConfigurationForm($_POST ?: null);
    $hasProperties = count($properties_form->getFields()) > 0;
?>
<h3 class="drag-handle"><?php echo $list->getName(); ?> &mdash; <?php
    echo $item ? $item->getValue() : __('Add New List Item'); ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>

<?php if ($hasProperties) { ?>
<ul class="tabs" id="item_tabs">
    <li class="active">
        <a href="#value"><i class="icon-reorder"></i>
        <?php echo __('Value'); ?></a>
    </li>
    <li><a href="#item-properties"><i class="icon-asterisk"></i>
        <?php echo __('Item Properties'); ?></a>
    </li>
</ul>
<?php } ?>

<form method="post" id="item_tabs_container" action="<?php echo $action; ?>">
    <?php
    echo csrf_token();
    $internal = $item ? $item->isInternal() : false;
?>

<div class="tab_content" id="value">
<?php
    $form = $item_form;
    include 'dynamic-form-simple.tmpl.php';
?>
</div>

<div class="tab_content hidden" id="item-properties">
<?php
    if ($hasProperties) {
        $form = $properties_form;
        include 'dynamic-form-simple.tmpl.php';
    }
?>
</div>

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

<script type="text/javascript">
   // Make translatable fields translatable
   $('input[data-translate-tag], textarea[data-translate-tag]').translatable();
</script>
