<?php
/**
 * Calling conventions
 *
 * $column - <QueueColumn> instance for this column
 * $root - <Class> name of queue root ('Ticket')
 * $data_form - <QueueColDataConfigForm> instance, optional
 */
$colid = $column->getId();
$data_form = $data_form ?: $column->getDataConfigForm($_POST);
?>
<ul class="tabs">
  <li class="active"><a href="#data"><?php echo __('Data'); ?></a></li>
  <li><a href="#annotations"><?php echo __('Annotations'); ?></a></li>
  <li><a href="#conditions"><?php echo __('Conditions'); ?></a></li>
</ul>

<div class="tab_content" id="data">
<?php
  print $data_form->asTable();
?>
</div>

<div class="hidden tab_content" style="margin: 0 20px"
  data-col-id="<?php echo $colid; ?>"
  id="annotations" style="max-width: 400px">
  <div class="empty placeholder" style="margin-left: 20px">
    <em><?php echo __('No annotations for this field'); ?></em>
  </div>
  <div>
    <div class="annotation clear template hidden" style="padding:3px 0">
      <input data-field="input" data-name="annotations[]" value="" type="hidden" />
      <input data-field="column" data-name="deco_column[]" value="" type="hidden" />
      <i data-field="icon"></i>
      <div data-field="name" style="display: inline-block; width: 150px"></div>
      <select data-field="position" data-name="deco_pos[]">
<?php foreach (QueueColumnAnnotation::getPositions() as $key=>$desc) {
        echo sprintf('<option value="%s">%s</option>', $key, Format::htmlchars($desc));
} ?>
      </select>
      <div class="pull-right">
        <a href="#" data-field="delete" title="<?php echo __('Delete'); ?>"
            onclick="javascript:
            var tab = $(this).closest('.tab_content'),
                annotation = $(this).closest('.annotation'),
                klass = annotation.find('input[data-field=input]').val(),
                select = $('select.add-annotation', tab);
            select.find('option[value=' + klass + ']').prop('disabled', false);
            annotation.remove();
            if (tab.find('.annotation:not(.template)').length === 0)
                tab.find('.empty.placeholder').show()
            return false;"><i class="icon-trash"></i></a>
      </div>
    </div>

    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #bbb">
      <i class="icon-plus-sign"></i>
      <select class="add-annotation">
        <option>— <?php echo __("Add a annotation"); ?> —</option>
<?php
$annotations = array();
foreach (QueueColumnAnnotation::getAnnotations('Ticket') as $class)
    $annotations[$class] = $class::getDescription();
foreach (Internationalization::sortKeyedList($annotations) as $class=>$desc) {
        echo sprintf('<option data-icon="%s" value="%s">%s</option>',
          $class::$icon, $class, $desc);
      } ?>
      </select>
    </div>

    <script>
      $(function() {
        var addAnnotation = function(type, desc, icon, pos) {
          var template = $('.annotation.template', '#annotations'),
              clone = template.clone().removeClass('hidden')
                  .removeClass('template').insertBefore(template),
              input = clone.find('[data-field=input]'),
              colid = clone.closest('.tab_content').data('colId'),
              column = clone.find('[data-field=column]'),
              name = clone.find('[data-field=name]'),
              i = clone.find('[data-field=icon]'),
              position = clone.find('[data-field=position]');
          input.attr('name', input.data('name')).val(type);
          column.attr('name', column.data('name')).val(colid);
          i.addClass('icon-fixed-width icon-' + icon);
          name.text(desc);
          position.attr('name', position.data('name'));
          if (pos)
            position.val(pos);
          template.closest('.tab_content').find('.empty').hide();
        };
        $('select.add-annotation', '#annotations').change(function() {
          var selected = $(this).find(':selected');
          addAnnotation(selected.val(), selected.text(), selected.data('icon'));
          selected.prop('disabled', true);
        });
        $('#annotations').click('a[data-field=delete]',
        function() {
          var tab = $('#annotations');
          if ($('.annotation', tab).length === 0)
            tab.find('.empty').show();
        });
        <?php foreach ($column->getAnnotations() as $d) {
            echo sprintf('addAnnotation(%s, %s, %s, %s);',
                JsonDataEncoder::encode($d->getClassName()),
                JsonDataEncoder::encode($d::getDescription()),
                JsonDataEncoder::encode($d::getIcon()),
                JsonDataEncoder::encode($d->getPosition())
            );
        } ?>
      });
    </script>
  </div>
</div>

<div class="hidden tab_content" id="conditions" style="margin: 0 20px">
  <div style="margin-bottom: 15px"><?php echo __("Conditions are used to change the view of the data in a row based on some conditions of the data. For instance, a column might be shown bold if some condition is met.");
  ?></div>
  <div class="conditions">
<?php
if ($column->getConditions(false)) {
  $fields = CustomQueue::getSearchableFields($root);
  foreach ($column->getConditions() as $i=>$condition) {
     $id = QueueColumnCondition::getUid();
     list($label, $field) = $condition->getField();
     if (!$label || !$field)
        continue;
     $field_name = $condition->getFieldName();
     $object_id = $column->getId();
     include STAFFINC_DIR . 'templates/queue-column-condition.tmpl.php';
  }
} ?>
    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #bbb">
      <i class="icon-plus-sign"></i>
      <select class="add-condition">
        <option>— <?php echo __("Add a condition"); ?> —</option>
<?php
      foreach (CustomQueue::getSearchableFields('Ticket') as $path=>$f) {
          list($label) = $f;
          echo sprintf('<option value="%s">%s</option>', $path, Format::htmlchars($label));
      }
?>
      </select>
      <script>
      $(function() {
        var colid = <?php echo $colid ?: 0; ?>,
            nextid = <?php echo QueueColumnCondition::getUid(); ?>;
        $('#conditions select.add-condition').change(function() {
          var $this = $(this),
              container = $this.closest('div'),
              selected = $this.find(':selected');
          $.ajax({
            url: 'ajax.php/queue/condition/add',
            data: { field: selected.val(), object_id: colid, id: nextid },
            dataType: 'html',
            success: function(html) {
              $(html).insertBefore(container);
              nextid++;
            }
          });
        });
      });
      </script>
    </div>
  </div>
</div>
