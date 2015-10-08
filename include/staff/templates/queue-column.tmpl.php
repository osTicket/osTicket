<?php
/**
 * Calling conventions
 *
 * $column - <QueueColumn> instance for this column
 */
$colid = $column->getId();
$data_form = $column->getDataConfigForm($_POST);
?>
<ul class="alt tabs">
  <li class="active"><a href="#<?php echo $colid; ?>-data"><?php echo __('Data'); ?></a></li>
  <li><a href="#<?php echo $colid; ?>-annotations"><?php echo __('Annotations'); ?></a></li>
  <li><a href="#<?php echo $colid; ?>-conditions"><?php echo __('Conditions'); ?></a></li>
  <a onclick="javascript:
  $(this).closest('.column-configuration').hide();
  $('#resizable-columns').find('div[data-id=<?php echo $colid; ?>]').hide()
    .find('input[name^=columns]').remove();
  " class="button red pull-right"><?php echo __("Delete Column"); ?></a>
</ul>

<div class="tab_content" id="<?php echo $colid; ?>-data">
<?php
  print $data_form->asTable();
?>
</div>

<div class="hidden tab_content" data-col-id="<?php echo $colid; ?>"
  id="<?php echo $colid; ?>-annotations" style="max-width: 400px">
  <div class="empty placeholder" style="margin-left: 20px">
    <em><?php echo __('No annotations for this field'); ?></em>
  </div>
  <div style="margin: 0 20px;">
    <div class="annotation clear template hidden">
      <input data-field="input" data-name="annotations[]" value="" type="hidden" />
      <input data-field="column" data-name="deco_column[]" value="" type="hidden" />
      <i data-field="icon"></i>
      <span data-field="name"></span>
      <div class="pull-right">
        <select data-field="position" data-name="deco_pos[]">
<?php foreach (QueueColumnAnnotation::getPositions() as $key=>$desc) {
          echo sprintf('<option value="%s">%s</option>', $key, Format::htmlchars($desc));
} ?>
        </select>
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

    <div style="margin-top: 20px">
      <i class="icon-plus-sign"></i>
      <select class="add-annotation">
        <option>— <?php echo __("Add a annotation"); ?> —</option>
<?php foreach (CustomQueue::getAnnotations('Ticket') as $class) {
        echo sprintf('<option data-icon="%s" value="%s">%s</option>',
          $class::$icon, $class, $class::getDescription());
      } ?>
      </select>
    </div>

    <script>
      $(function() {
        var addAnnotation = function(type, desc, icon, pos) {
          var template = $('.annotation.template', '#<?php echo $colid; ?>-annotations'),
              clone = template.clone().show().removeClass('template').insertBefore(template),
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
        $('select.add-annotation', '#<?php echo $colid; ?>-annotations').change(function() {
          var selected = $(this).find(':selected');
          addAnnotation(selected.val(), selected.text(), selected.data('icon'));
          selected.prop('disabled', true);
        });
        $('#<?php echo $colid; ?>-annotations').click('a[data-field=delete]',
        function() {
          var tab = $('#<?php echo $colid; ?>-annotations');
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

<div class="hidden tab_content" id="<?php echo $colid; ?>-conditions">
  <div style="margin: 0 20px"><?php echo __("Conditions are used to change the view of the data in a row based on some conditions of the data. For instance, a column might be shown bold if some condition is met.");
  ?></div>
  <div class="conditions" style="margin: 20px; max-width: 400px">
<?php
if ($column->getConditions()) {
  $fields = SavedSearch::getSearchableFields($column->getQueue()->getRoot());
  foreach ($column->getConditions() as $i=>$condition) {
     $id = $column->getId() * 40 + $i;
     $field = $condition->getField();
     $field_name = $condition->getFieldName();
     include STAFFINC_DIR . 'templates/queue-column-condition.tmpl.php';
  }
} ?>
    <div style="margin-top: 20px">
      <i class="icon-plus-sign"></i>
      <select class="add-condition">
        <option>— <?php echo __("Add a condition"); ?> —</option>
<?php
      foreach (SavedSearch::getSearchableFields('Ticket') as $path=>$f) {
          list($label) = $f;
          echo sprintf('<option value="%s">%s</option>', $path, Format::htmlchars($label));
      }
?>
      </select>
      <script>
      $(function() {
        var colid = <?php echo $colid ?: 0 ?>,
            nextid = colid * 40;
        $('#' + colid + '-conditions select.add-condition').change(function() {
          var $this = $(this),
              container = $this.closest('div'),
              selected = $this.find(':selected');
          $.ajax({
            url: 'ajax.php/queue/condition/add',
            data: { field: selected.val(), colid: colid, id: nextid },
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
