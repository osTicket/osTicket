<div style="overflow-y: auto; height:auto; max-height: 350px;">
<table class="table">
<?php
$hidden_cols = $queue->inheritColumns() || count($queue->columns) === 0;
if ($queue->parent) { ?>
  <tbody>
    <tr>
      <td colspan="3">
        <input type="checkbox" name="inherit-columns" <?php
          if ($hidden_cols) echo 'checked="checked"'; ?>
          onchange="javascript:$(this).closest('table').find('.if-not-inherited').toggle(!$(this).prop('checked'));" />
        <?php echo __('Inherit columns from the parent queue'); ?>
        <br /><br />
      </td>
    </tr>
  </tbody>
<?php } elseif ($queue instanceof SavedQueue) { ?>
  <tbody>
    <tr>
      <td colspan="3">
        <input type="checkbox" name="inherit-columns" <?php
          if ($queue->useStandardColumns()) echo 'checked="checked"';
          if ($queue instanceof SavedSearch && $queue->__new__) echo 'disabled="disabled"'; ?>
          onchange="javascript:$(this).closest('table').find('.if-not-inherited').toggle(!$(this).prop('checked'));
          $(this).closest('table').find('.standard-columns').toggle($(this).prop('checked'));" />
        <?php echo __('Use standard columns'); ?>
        <br /><br />
      </td>
    </tr>
  </tbody>
<?php }
$hidden_cols = $queue->inheritColumns() || ($queue->useStandardColumns() &&
        $queue->parent_id);
?>
  <tbody class="if-not-inherited <?php if ($hidden_cols) echo 'hidden'; ?>">
    <tr class="header">
      <td nowrap><small><b><?php echo __('Heading and Width'); ?></b></small></td>
      <td><small><b><?php echo __('Column Details'); ?></b></small></td>
      <td><small><b><?php echo __('Sortable'); ?></b></small></td>
    </tr>
  </tbody>
  <tbody class="sortable-rows if-not-inherited <?php if ($hidden_cols) echo 'hidden'; ?>">
    <tr id="column-template" class="hidden">
      <td nowrap>
        <i class="faded-more icon-sort"></i>
        <input type="hidden" data-name="column_id" />
        <input type="text" size="25" data-name="heading"
          data-translate-tag="" />
        <input type="text" size="5" data-name="width" />
      </td>
      <td>
<?php if (!$queue instanceof SavedSearch) { ?>
        <div>
        <a class="inline action-button"
            href="#" onclick="javascript:
            var colid = $(this).closest('tr').find('[data-name=column_id]').val();
            $.dialog('ajax.php/tickets/search/column/edit/' + colid, 201);
            return false;
            "><i class="icon-cog"></i> <?php echo __('Config'); ?></a>
        </div>
<?php }
      else { ?>
        <input readonly type="text" style="border:none;background:transparent" data-name="name" />
<?php } ?>
      </td>
      <td>
        <input type="checkbox" data-name="sortable"/>
        <a href="#" class="pull-right drop-column" title="<?php echo __('Delete');
          ?>"><i class="icon-trash"></i></a>
      </td>
    </tr>
  </tbody>
  <tbody class="if-not-inherited <?php if ($hidden_cols) echo 'hidden'; ?>">
    <tr class="header">
      <td colspan="3"></td>
    </tr>
    <tr>
      <td colspan="3" id="append-column">
        <i class="icon-plus-sign"></i>
        <select id="add-column" data-quick-add="queue-column">
          <option value="">— <?php echo __('Add a column'); ?> —</option>
<?php foreach (QueueColumn::objects() as $C) { ?>
          <option value="<?php echo $C->id; ?>"><?php echo
              Format::htmlchars($C->name); ?></option>
<?php } ?>
<?php if (!$queue instanceof SavedSearch) { ?>
          <option value="0" data-quick-add>&mdash; <?php echo __('Add New');?> &mdash;</option>
<?php } ?>
        </select>
        <button type="button" class="green button">
          <?php echo __('Add'); ?>
        </button>
      </td>
    </tr>
  </tbody>
  <tbody class="standard-columns <?php if (!$hidden_cols) echo 'hidden'; ?>">
    <?php
    foreach ($queue->getStandardColumns() as $c) { ?>
    <tr>
      <td nowrap><?php echo Format::htmlchars($c->heading); ?></td>
      <td nowrap><?php echo Format::htmlchars($c->name); ?></td>
      <td>&nbsp;</td>
    </tr>
    <?php
    } ?>
  </tbody>
</table>
</div>
<script>
+function() {
$('[name=inherit-columns]').on('click', function() {
    $('.standard-columns').toggle();
});
var Q = setInterval(function() {
  if ($('#append-column').length == 0)
    return;
  clearInterval(Q);

  var addColumn = function(colid, info) {

    if (!colid || $('tr#column-'+colid).length)
        return;

    var copy = $('#column-template').clone(),
        name_prefix = 'columns[' + colid + ']';
    info['column_id'] = colid;
    copy.find('input[data-name]').each(function() {
      var $this = $(this),
          name = $this.data('name');

      if (info[name] !== undefined) {
        if ($this.is(':checkbox'))
          $this.prop('checked', info[name]);
        else
          $this.val(info[name]);
      }
      $this.attr('name', name_prefix + '[' + name + ']');
    });
    copy.find('span').text(info['name']);
    copy.attr('id', 'column-'+colid).show().insertBefore($('#column-template'));
    copy.removeClass('hidden');
    if (info['trans'] !== undefined) {
      var input = copy.find('input[data-translate-tag]')
        .attr('data-translate-tag', info['trans']);
      if ($.fn.translatable)
        input.translatable();
      // Else it will be made translatable when the JS library is loaded
    }
    copy.find('a.drop-column').click(function() {
      $('<option>')
        .attr('value', copy.find('input[data-name=column_id]').val())
        .text(info.name)
        .insertBefore($('#add-column')
          .find('[data-quick-add]')
        );
      copy.fadeOut(function() { $(this).remove(); });
      return false;
    });
    var selected = $('#add-column').find('option[value=' + colid + ']');
    selected.remove();
  };

  $('#append-column').find('button').on('click', function() {
    var selected = $('#add-column').find(':selected'),
        id = parseInt(selected.val());
    if (!id)
        return;
    addColumn(id, {name: selected.text(), heading: selected.text(), width: 100, sortable: 1});
    return false;
  });
<?php foreach ($queue->getColumns(true) as $C) {
  echo sprintf('addColumn(%d, {name: %s, heading: %s, width: %d, trans: %s,
  sortable: %s});',
    $C->column_id, JsonDataEncoder::encode($C->name),
    JsonDataEncoder::encode($C->heading), $C->width,
    JsonDataEncoder::encode($C->getTranslateTag('heading')),
    $C->isSortable() ? 1 : 0);
} ?>
}, 25);
}();
</script>
