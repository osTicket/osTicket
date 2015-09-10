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
  <li><a href="#<?php echo $colid; ?>-decorations"><?php echo __('Decorations'); ?></a></li>
  <li><a href="#<?php echo $colid; ?>-conditions"><?php echo __('Conditions'); ?></a></li>
</ul>

<div class="tab_content" id="<?php echo $colid; ?>-data">
<?php
  print $data_form->asTable();
?>
</div>

<div class="hidden tab_content" data-col-id="<?php echo $colid; ?>"
  id="<?php echo $colid; ?>-decorations" style="max-width: 400px">
  <div class="empty placeholder" style="margin-left: 20px">
    <em><?php echo __('No decorations for this field'); ?></em>
  </div>
  <div style="margin: 0 20px;">
    <div class="decoration clear template hidden">
      <input data-field="input" data-name="decorations[]" value="" type="hidden" />
      <input data-field="column" data-name="deco_column[]" value="" type="hidden" />
      <i data-field="icon"></i>
      <span data-field="name"></span>
      <div class="pull-right">
        <select data-field="position" data-name="deco_pos[]">
<?php foreach (QueueDecoration::getPositions() as $key=>$desc) {
          echo sprintf('<option value="%s">%s</option>', $key, Format::htmlchars($desc));
} ?>
        </select>
        <a href="#" data-field="delete" title="<?php echo __('Delete'); ?>"
            onclick="javascript: 
            var tab = $(this).closest('.tab_content'),
                decoration = $(this).closest('.decoration'),
                klass = decoration.find('input[data-field=input]').val(),
                select = $('select.add-decoration', tab);
            select.find('option[value=' + klass + ']').prop('disabled', false);
            decoration.remove();
            if (tab.find('.decoration:not(.template)').length === 0)
                tab.find('.empty.placeholder').show()
            return false;"><i class="icon-trash"></i></a>
      </div>
    </div>

    <div style="margin-top: 20px">
      <i class="icon-plus-sign"></i>
      <select class="add-decoration">
        <option>— <?php echo __("Add a decoration"); ?> —</option>
<?php foreach (CustomQueue::getDecorations('Ticket') as $class) {
        echo sprintf('<option data-icon="%s" value="%s">%s</option>',
          $class::$icon, $class, $class::getDescription());
      } ?>
      </select>
    </div>

    <script>
      $(function() {
        var addDecoration = function(type, desc, icon, pos) {
          var template = $('.decoration.template', '#<?php echo $colid; ?>-decorations'),
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
        $('select.add-decoration', '#<?php echo $colid; ?>-decorations').change(function() {
          var selected = $(this).find(':selected');
          addDecoration(selected.val(), selected.text(), selected.data('icon'));
          selected.prop('disabled', true);
        });
        $('#<?php echo $colid; ?>-decorations').click('a[data-field=delete]',
        function() {
          var tab = $('#<?php echo $colid; ?>-decorations');
          if ($('.decoration', tab).length === 0)
            tab.find('.empty').show();
        });
        <?php foreach ($column->getDecorations() as $d) {
            echo sprintf('addDecoration(%s, %s, %s);',
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
<?php foreach ($column->getConditions() as $condition) {
     include STAFFINC_DIR . 'templates/queue-column-condition.tmpl.php';
} ?>
    <div style="margin-top: 20px">
      <i class="icon-plus-sign"></i>
      <select class="add-condition">
        <option>— <?php echo __("Add a condition"); ?> —</option>
<?php
      foreach (SavedSearch::getSearchableFields('Ticket') as $path=>$f) {
          echo sprintf('<option value="%s">%s</option>', $path, Format::htmlchars($f->get('label')));
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
