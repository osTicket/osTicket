<?php
/**
 * Calling conventions
 *
 * $name - condition field name, like 'thread__lastmessage'
 * $prop - CSS property name from QueueColumnConditionProperty::$properties
 * $v - value for the property
 */
?>
<div class="condition-property">
  <div class="pull-right">
    <a href="#" onclick="javascript:$(this).closest('.condition-property').remove()"
      ><i class="icon-trash"></i></a>
  </div>
  <div><?php echo mb_convert_case($prop, MB_CASE_TITLE); ?></div>
<?php
    $F = QueueColumnConditionProperty::getField($prop);
    $F->set('name', "prop-{$name}-{$prop}");
    $F->value = $v;
    $form = new SimpleForm(array($F), $_POST);
    echo $F->render();
    echo $form->getMedia();
?>
</div>
