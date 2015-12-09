<?php
// vim: expandtab sw=2 ts=2 sts=2:

if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$info = $qs = array();

if (!$queue) {
    $queue = CustomQueue::create(array(
        'flags' => CustomQueue::FLAG_QUEUE,
    ));
    $title=__('Add New Queue');
    $action='create';
    $submit_text=__('Create');
}
else {
    $title=__('Manage Custom Queue');
    $action='update';
    $submit_text=__('Save Changes');
    $info['id'] = $queue->getId();
    $qs += array('id' => $queue->getId());
}
?>

<form action="queues.php?<?php echo Http::build_query($qs); ?>" method="post" id="save" autocomplete="off">
  <?php csrf_token(); ?>
  <input type="hidden" name="do" value="<?php echo $action; ?>">
  <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
  <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
  <input type="hidden" name="root" value="<?php echo Format::htmlchars($_REQUEST['t']); ?>">

  <h2><a href="settings.php?t=tickets#queues"><?php echo __('Ticket Queues'); ?></a>
      <i class="icon-caret-right" style="color:rgba(0,0,0,.3);"></i> <?php echo $title; ?>
      <?php if (isset($queue->id)) { ?><small>
      — <?php echo $queue->getFullName(); ?></small>
      <?php } ?>
  </h2>

  <ul class="clean tabs">
    <li class="active"><a href="#criteria"><i class="icon-filter"></i>
      <?php echo __('Criteria'); ?></a></li>
    <li><a href="#columns"><i class="icon-columns"></i>
      <?php echo __('Columns'); ?></a></li>
    <li><a href="#sorting-tab"><i class="icon-sort-by-attributes"></i>
      <?php echo __('Sort'); ?></a></li>
    <li><a href="#preview-tab"><i class="icon-eye-open"></i>
      <?php echo __('Preview'); ?></a></li>
  </ul>

  <div class="tab_content" id="criteria">
    <table class="table">
      <td style="width:60%; vertical-align:top">
        <div><strong><?php echo __('Queue Name'); ?>:</strong></div>
        <input type="text" name="name" value="<?php
          echo Format::htmlchars($queue->getName()); ?>"
          style="width:100%" />

        <br/>
        <br/>
        <div><strong><?php echo __("Queue Search Criteria"); ?></strong></div>
        <label class="checkbox" style="line-height:1.3em">
          <input type="checkbox" class="checkbox" name="inherit" <?php
            if ($queue->inheritCriteria()) echo 'checked="checked"';
            ?>/>
          <?php echo __('Include parent search criteria');
          if ($queue->parent) { ?>
            <span id="parent_q_crit" class="faded">
            <i class="icon-caret-right"></i>
            <br/><?php
              echo $queue->parent->describeCriteria();
            ?></span>
<?php     } ?>
        </label>
        <hr/>
        <div class="error"><?php echo $errors['criteria']; ?></div>
        <div class="advanced-search">
<?php
            $form = $queue->getForm();
            $search = $queue;
            $matches = $queue->getSupportedMatches();
            include STAFFINC_DIR . 'templates/advanced-search-criteria.tmpl.php';
?>
        </div>
      </td>
      <td style="width:35%; padding-left:40px; vertical-align:top">
        <div><strong><?php echo __("Parent Queue"); ?>:</strong></div>
        <select name="parent_id" onchange="javascript:
        $('#parent_q_crit').toggle($(this).find(':selected').val()
          == <?php echo $queue->parent_id ?: 0; ?>);">
          <option value="0">— <?php echo __('Top-Level Queue'); ?> —</option>
<?php foreach (CustomQueue::queues() as $cq) {
        if ($cq->getId() == $queue->getId())
          continue;
?>
          <option value="<?php echo $cq->id; ?>"
            <?php if ($cq->getId() == $queue->parent_id) echo 'selected="selected"'; ?>
            ><?php echo $cq->getFullName(); ?></option>
<?php } ?>
        </select>

        <br/>
        <br/>
        <div><strong><?php echo __("Quick Filter"); ?></strong></div>
        <hr/>
        <select name="filter">
          <option value="" <?php if ($queue->filter == "")
              echo 'selected="selected"'; ?>>— <?php echo __('None'); ?> —</option>
          <option value="::" <?php if ($queue->filter == "::")
              echo 'selected="selected"'; ?>>— <?php echo __('Inherit from parent');
            if ($queue->parent 
                && ($qf = $queue->parent->getQuickFilterField()))
                echo sprintf(' (%s)', $qf->getLabel()); ?> —</option>
<?php foreach (SavedSearch::getSearchableFields('Ticket') as $path=>$f) {
        list($label, $field) = $f;
        if (!$field->supportsQuickFilter())
          continue;
?>
          <option value="<?php echo $path; ?>"
            <?php if ($path == $queue->filter) echo 'selected="selected"'; ?>
            ><?php echo $label; ?></option>
<?php } ?>
        </select>
        <br/>
        <br/>
        <div><strong><?php echo __("Sort Options"); ?></strong></div>
        <hr/>
      </td>
    </table>
  </div>

  <div class="hidden tab_content" id="columns">

    <div class="tab-desc">
        <p><b><?php echo __("Manage columns in this queue"); ?></b>
        <br><?php echo __(
        "Add, remove, and customize the content of the columns in this queue using the options below. Click a column header to manage or resize it"); ?></p>
    </div>
    <table class="table two-column">
      <tbody>
        <tr class="header">
          <td style="width:36%"><small><b><?php echo __('Heading and Width'); ?></b></small></td>
          <td><small><b><?php echo __('Column Details'); ?></b></small></td>
          <td><small><b><?php echo __('Sortable'); ?></b></small></td>
        </tr>
      </tbody>
      <tbody class="sortable-rows">
        <tr id="column-template" class="hidden">
          <td>
            <i class="faded-more icon-sort"></i>
            <input type="text" size="25" data-name="heading"
              data-translate-tag="" />
            <input type="text" size="5" data-name="width" />
          </td>
          <td>
            <input type="hidden" data-name="queue_id"
              value="<?php echo $queue->getId(); ?>"/>
            <input type="hidden" data-name="column_id" />
            <div>
            <a class="inline action-button"
                href="#" onclick="javascript:
                var colid = $(this).closest('tr').find('[data-name=column_id]').val();
                $.dialog('ajax.php/tickets/search/column/edit/' + colid, 201);
                return false;
                "><i class="icon-cog"></i> <?php echo __('Config'); ?></a>
            </div>
          </td>
          <td>
            <input type="checkbox" data-name="sortable">
            <a href="#" class="pull-right drop-column" title="<?php echo __('Delete');
              ?>"><i class="icon-trash"></i></a>
          </td>
        </tr>
      </tbody>
      <tbody>
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
              <option value="0" data-quick-add>&mdash; <?php echo __('Add New');?> &mdash;</option>
            </select>
            <button type="button" class="green button">
              <?php echo __('Add'); ?>
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
    
    
    <div class="hidden tab_content" id="sorting-tab">
        <div class="tab-desc">
            <p><b><?php echo __("Manage Queue Sorting"); ?></b>
            <br><?php echo __("Add, edit or remove the sorting criteria for this custom queue using the options below. Sorting is priortized in ascending order."); ?></p>
        </div>
        <table class="queue-sort table">
            <tbody class="sortable-rows ui-sortable">
                <tr style="display: table-row;">
                    <td>
                        <i class="faded-more icon-sort"></i>
                         <a class="inline"
                href="#" onclick="javascript:
                var colid = $(this).closest('tr').find('[data-name=sorting_id]').val();
                $.dialog('ajax.php/tickets/search/sorting/edit/' + colid, 201);
                return false;"><?php echo __('This is sort criteria title 1'); ?></a>
                    </td>
                    <td>
                        <a href="#" class="pull-right drop-column" title="Delete"><i class="icon-trash"></i></a>
                    </td>
                </tr>
                <tr style="display: table-row;">
                    <td>
                        <i class="faded-more icon-sort"></i>
                        <a class="inline"
                href="#" onclick="javascript:
                var colid = $(this).closest('tr').find('[data-name=sorting_id]').val();
                $.dialog('ajax.php/tickets/search/sorting/edit/' + colid, 201);
                return false;
                "><?php echo __('This is sort criteria title 2'); ?></a>
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
                    <td colspan="3" id="append-sort">
                        <i class="icon-plus-sign"></i>
                        <select id="add-sort" data-quick-add="queue-column">
                            <option value="">— Add Sort Criteria —</option>
                            <option value="">Sort Option 1</option>
                            <option value="">Sort Option 2</option>
                            <option value="0" data-quick-add>&mdash; <?php echo __('Add New Sort Criteria');?> &mdash;</option>
                        </select>
                        <button type="button" class="green button">Add</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>    
    
    <div class="hidden tab_content" id="preview-tab">
    <div id="preview">
    </div>

    <script>
      $(function() {
        $('#preview-tab').on('afterShow', function() {
          $.ajax({
            url: 'ajax.php/queue/preview',
            type: 'POST',
            data: $('#save').serializeArray(),
            success: function(html) {
              $('#preview').html(html);
            }
          });
        });
      });
    </script>

  </div>

  <p style="text-align:center;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick="window.history.go(-1);">
  </p>

</form>

<script>
var addColumn = function(colid, info) {
  if (!colid) return;
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
  copy.attr('id', '').show().insertBefore($('#column-template'));
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

<?php foreach ($queue->columns as $C) {
  echo sprintf('addColumn(%d, {name: %s, heading: %s, width: %d, trans: %s,
  sortable: %s});',
    $C->column_id, JsonDataEncoder::encode($C->name),
    JsonDataEncoder::encode($C->heading), $C->width,
    JsonDataEncoder::encode($C->getTranslateTag('heading')),
    $C->isSortable() ? 1 : 0);
} ?>
</script>
