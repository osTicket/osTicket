<?php
// Calling convention:
//
// $field - field for the condition (Ticket / Last Update)
// $condition - <QueueColumnCondition> instance for this condition
// $object_id - ID# of the object to which the condition belongs
// $id - temporary ID number for the condition
// $field_name - search path / name for the field
?>
<div class="condition">
  <input name="conditions[]" value="<?php echo $id; ?>" type="hidden" />
  <input name="condition_column[]" value="<?php echo $object_id; ?>"
    type="hidden" />
  <input name="condition_field[]" value="<?php echo $field_name; ?>" type="hidden" />
  <div class="pull-right">
    <a href="#" onclick="javascript: $(this).closest('.condition').remove();
      return false;
      "><i class="icon-trash"></i></a>
  </div>
  <div><strong><?php echo $label ?: $field->getLabel(); ?></div></strong>
  <div class="advanced-search">
<?php
$parts = CustomQueue::getSearchField(array($label, $field), $field_name);
// Drop the search checkbox field
unset($parts["{$field_name}+search"]);
list(, $crit_method, $crit_value) = $condition->getCriteria();
foreach ($parts as $name=>$F) {
    if (substr($name, -7) == '+method') {
        // XXX: Hack — drop visibility connection between the method drop-down
        //      and the enabled checkbox
        unset($F->ht['visibility']);
        // Set the select method, if any
        if ($crit_method)
            $F->value = $crit_method;
    }
    if ($crit_value && strpos($name, "+{$crit_method}") > 0) {
        $F->value = $crit_value;
    }
}
$form = new SimpleForm($parts, false, array('id' => $id));
foreach ($form->getFields() as $F) { ?>
    <fieldset id="field<?php echo $F->getWidget()->id;
        ?>" <?php
            $class = array();
            @list($name, $sub) = explode('+', $F->get('name'), 2);
            if (!$F->isVisible()) $class[] = "hidden";
            if ($sub === 'method')
                $class[] = "adv-search-method";
            elseif ($F->get('__searchval__'))
                $class[] = "adv-search-val";
            if ($class)
                echo 'class="'.implode(' ', $class).'"';
            ?>>
        <?php echo $F->render(); ?>
        <?php foreach ($F->errors() as $E) {
            ?><div class="error"><?php echo $E; ?></div><?php
        } ?>
    </fieldset>
<?php } ?>

    <div class="properties" style="margin-left: 25px; margin-top: 10px">
<?php
foreach ($condition->getProperties() as $prop=>$v) {
    include 'queue-column-condition-prop.tmpl.php';
} ?>
      <div style="margin-top: 10px">
        <i class="icon-plus-sign"></i>
        <select onchange="javascript:
        var $this = $(this),
            selected = $this.find(':selected'),
            container = $this.closest('div');
        $.ajax({
          url: 'ajax.php/queue/condition/addProperty',
          data: { prop: selected.val(), condition: <?php echo $id; ?> },
          dataType: 'html',
          success: function(html) {
            $(html).insertBefore(container);
            selected.prop('disabled', true);
          }
        });
        ">
          <option>— <?php echo __('Add a property'); ?> —</option>
<?php foreach (array_keys(QueueColumnConditionProperty::$properties) as $p) {
    echo sprintf('<option value="%s">%s</option>', $p, mb_convert_case($p, MB_CASE_TITLE));
} ?>
        </select>
      </div>
    </div>
  </div>
</div>
