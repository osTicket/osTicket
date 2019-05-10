<?php echo $data_form->asTable(); ?>

<table class="table">
    <tbody class="sortable-rows">
      <tr id="sort-column-template" class="hidden">
        <td nowrap>
          <i class="faded-more icon-sort"></i>
          <span data-name="label"></span>
        </td>
        <td>
          <select data-name="descending">
            <option value="0">
              <?php echo __('Ascending');?>
            </option>
            <option value="1">
              <?php echo __('Descending');?>
            </option>
          </select>
        </td>
        <td>
          <a href="#" class="pull-right drop-column" title="Delete"><i class="icon-trash"></i></a>
        </td>
      </tr>
    </tbody>
    <tbody>
        <tr class="header">
            <td colspan="3"></td>
        </tr>
        <tr>
            <td colspan="3" id="append-sort-column">
                <i class="icon-plus-sign"></i>
                <select id="add-sort-column">
                    <option value="">— <?php echo __("Add Field"); ?> —</option>
<?php foreach (CustomQueue::getSearchableFields($sort->getRoot()) as $path=>$F) {
    list($label,) = $F;
?>
                    <option value="<?php echo Format::htmlchars($path); ?>"><?php
                        echo Format::htmlchars($label);
                    ?></option>
<?php } ?>
                </select>
                <button type="button" class="green button"><?php
                  echo __('Add'); ?></button>
            </td>
        </tr>
    </tbody>
<script>
+function() {
var Q = setInterval(function() {
  if ($('#append-sort-column').length == 0)
    return;
  clearInterval(Q);

  var addSortColumn = function(info) {
    if (!info.path) return;
    var copy = $('#sort-column-template').clone(),
        name_prefix = 'columns[' + info.path + ']';
    copy.find(':input[data-name]').each(function() {
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
    copy.attr('id', '').show().insertBefore($('#sort-column-template'));
    copy.removeClass('hidden');
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
    var selected = $('#add-sort-column').find('option[value=' + info.path + ']');
    selected.remove();
  };

  $('#append-sort-column').find('button').on('click', function() {
    var selected = $('#add-sort-column').find(':selected'),
        path = selected.val();
    if (!path)
        return;
    addSortColumn({path: path, name: selected.text(), descending: 0});
    return false;
  });
<?php foreach ($sort->getColumns() as $path=>$C) {
  list(list($label,), $descending) = $C;
  echo sprintf('addSortColumn({path: %s, name: %s, descending: %d});',
    JsonDataEncoder::encode($path),
    JsonDataEncoder::encode($label),
    $descending ? 1 : 0
  );
} ?>
}, 25);
}();
</script>
</table>
