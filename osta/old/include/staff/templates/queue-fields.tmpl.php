<div style="overflow-y: auto; height:auto; max-height: 350px;">
<table class="table">
<?php
if ($queue->parent) { ?>
  <tbody>
    <tr>
      <td colspan="3">
        <input type="checkbox" name="inherit-exports" <?php
          if ($queue->inheritExport()) echo 'checked="checked"'; ?>
          onchange="javascript:$(this).closest('table').find('.if-not-inherited').toggle(!$(this).prop('checked'));" />
        <?php echo __('Inherit export fields from the parent queue'); ?>
        <br /><br />
      </td>
    </tr>
  </tbody>
<?php }
      // Adhoc Advanced search does not have customizable export, but saved
      // ones do
      elseif ($queue->__new__) { ?>
  <tbody>
    <tr>
      <td colspan="3">
        <input type="checkbox" name="inherit-exports" <?php
          if (count($queue->exports) == 0) echo 'checked="checked"';
          if ($queue instanceof SavedSearch) echo 'disabled="disabled"'; ?>
          onchange="javascript:$(this).closest('table').find('.if-not-inherited').toggle(!$(this).prop('checked'));" />
        <?php echo __('Use standard export fields'); ?>
        <br /><br />
      </td>
    </tr>
  </tbody>
<?php }
$hidden_cols = $queue->inheritExport();
?>
  <tbody class="if-not-inherited <?php if ($hidden_cols) echo 'hidden'; ?>">
    <tr class="header">
      <td nowrap><small><b><?php echo __('Heading'); ?></b></small></td>
      <td><small><b><?php echo __('Field'); ?></b></small></td>
      <td><small>&nbsp;</small></td>
    </tr>
  </tbody>
  <tbody class="sortable-rows if-not-inherited <?php if ($hidden_cols) echo
  'hidden'; ?>" style="overflow-y: auto;">
    <tr id="field-template" class="hidden field-entry">
      <td nowrap>
        <i class="faded-more icon-sort"></i>
        <input type="hidden" data-name="name" />
        <input type="text" size="25" data-name="heading"
          data-translate-tag="" />
      </td>
      <td><span>Field</span></td>
      <td>
        <a href="#" class="pull-right drop-field" title="<?php echo __('Delete');
          ?>"><i class="icon-trash"></i></a>
      </td>
    </tr>
  </tbody>
  <tbody id="fields" class="if-not-inherited  <?php if ($hidden_cols) echo 'hidden'; ?>">
    <tr class="header">
        <td colspan="3"></td>
    </tr>
    <tr>
    <td colspan="3" id="append-field">
    <i class="icon-plus-sign"></i>
    <select id="add-field" name="new-field" style="max-width: 300px;">
        <option value="">— <?php echo __('Add Other Field'); ?> —</option>
    <?php
    $fields = CustomQueue::getExportableFields();
    if (is_array($fields)) {
    foreach ($fields as $path => $label) { ?>
        <option value="<?php echo $path; ?>" <?php
            if (isset($state[$path])) echo 'disabled="disabled"';
            ?>><?php echo $label; ?></option>
    <?php }
    } ?>
    </select>
    <button type="button" class="green button">
          <?php echo __('Add'); ?>
    </button>
    </td></tr>
  </tbody>
</table>
</div>
<script>
+function() {
var Q = setInterval(function() {
  if ($('#append-field').length == 0)
    return;
  clearInterval(Q);

  var addField = function(field, info) {
    if (!field) return;

    var i  = $('#fields tr.field-entry').length;
    var copy = $('#field-template').clone(),
        name_prefix = 'exports['+ field +']';

    copy.find('input[data-name]').each(function() {
      var $this = $(this),
          name = $this.data('name');

      if (info[name] !== undefined) {
        $this.val(info[name]);
      }
      $this.attr('name', name_prefix + '[' + name + ']');
    });
    copy.find('span').text(info['name']);
    copy.attr('id', '').show().insertBefore($('#field-template'));
    copy.removeClass('hidden');
    if (info['trans'] !== undefined) {
      var input = copy.find('input[data-translate-tag]')
        .attr('data-translate-tag', info['trans']);
      if ($.fn.translatable)
        input.translatable();
      // Else it will be made translatable when the JS library is loaded
    }
    copy.find('a.drop-field').click(function() {
      $('<option>')
        .attr('value', copy.find('input[data-name=field]').val())
        .text(info.name)
        .insertBefore($('#add-field')
          .find('[data-quick-add]')
        );
      copy.fadeOut(function() { $(this).remove(); });
      return false;
    });

    var selected = $('#add-field').find("option[value='" + escape(field) +
            "']");
    selected.remove();
  };

  $('#append-field').find('button').on('click', function() {
    var selected = $('#add-field').find(':selected'),
        field = selected.val();
    if (!field)
        return;
    addField(field, {name: selected.text(), heading: selected.text()});
    return false;
  });
<?php
    foreach ($queue->getExportFields(false) as $k => $v) {
    echo sprintf('addField(%s, {name: %s, heading: %s});',
    JsonDataEncoder::encode($k),
    JsonDataEncoder::encode($v),
    JsonDataEncoder::encode($v));
  }
?>
}, 25);
}();
</script>
