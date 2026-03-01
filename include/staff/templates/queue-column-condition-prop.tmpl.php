<?php
/**
 * Calling conventions
 *
 * $id - temporary condition ID number for the property
 * $prop - CSS property name from QueueColumnConditionProperty::$properties
 * $v - value for the property
 */
?>
<div class="condition-property">
  <input type="hidden" name="properties[]" value="<?php echo $id; ?>" />
  <input type="hidden" name="property_name[]" value="<?php echo $prop; ?>" />
  <div class="pull-right">
    <a href="#" onclick="javascript:$(this).closest('.condition-property').remove()"
      ><i class="icon-trash"></i></a>
  </div>
  <div><?php echo mb_convert_case($prop, MB_CASE_TITLE); ?></div>
<?php
    $F = QueueColumnConditionProperty::getField($prop);
    $F->value = $v;
    $form = new SimpleForm(array($F), false, array('id' => $id));
    echo $F->render();
    echo $form->getMedia();
?>
</div>
