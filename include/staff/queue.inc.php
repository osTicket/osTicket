<?php
// vim: expandtab sw=2 ts=2 sts=2:

if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$info = $qs = array();
$parent = null;
if (!$queue) {
    $queue = CustomQueue::create(array(
        'flags' => CustomQueue::FLAG_QUEUE,
        'parent_id' => 0,
    ));
}
if ($queue->__new__) {
    $title=__('Add New Queue');
    $action='create';
    $submit_text=__('Create');
}
else {
    $parent = $queue->parent;
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
    <li><a href="#conditions-tab"><i class="icon-exclamation-sign"></i>
      <?php echo __('Conditions'); ?></a></li>
    <li><a href="#export-columns"><i class="icon-download"></i>
      <?php echo __('Export'); ?></a></li>
    <li><a href="#preview-tab"><i class="icon-eye-open"></i>
      <?php echo __('Preview'); ?></a></li>
  </ul>

  <div class="tab_content" id="criteria">
    <table class="table">
      <td style="width:60%; vertical-align:top">
        <div><strong><?php echo __('Queue Name'); ?>:</strong></div>
        <input type="text" name="queue-name" value="<?php
          echo Format::htmlchars($queue->getName()); ?>"
          style="width:100%" />
        <br/>
        <div class="error"><?php echo $errors['queue-name']; ?></div>
        <br/>
        <div>
          <div><strong><?php echo __("Parent Queue"); ?>:</strong></div>
          <select name="parent_id" id="parent-id">
            <option value="0">— <?php echo __('Top-Level Queue'); ?> —</option>
  <?php foreach (CustomQueue::queues() as $cq) {
          // Queue cannot be a descendent of itself
          if ($cq->id == $queue->id)
              continue;
          if (strpos($cq->path, "/{$queue->id}/") !== false)
              continue;
  ?>
            <option value="<?php echo $cq->id; ?>"
              <?php if ($cq->getId() == $queue->parent_id) echo 'selected="selected"'; ?>
              ><?php echo $cq->getFullName(); ?></option>
  <?php } ?>
          </select>
          <span class="error"><?php echo Format::htmlchars($errors['parent_id']); ?></span>
        </div>
        <div class="faded <?php echo $parent ? ' ': 'hidden'; ?>"
            id="inherited-parent" style="margin-top: 1em;">
          <div><strong><i class="icon-caret-down"></i>&nbsp; <?php echo  __('Inherited Criteria'); ?></strong></div>
          <div id="parent-criteria">
            <?php echo $parent ? nl2br(Format::htmlchars($parent->describeCriteria())) : ''; ?>
          </div>
        </div>
        <hr/>
        <div><strong><?php echo __("Queue Search Criteria"); ?></strong></div>
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
<?php foreach ($queue->getSupportedFilters() as $path => $f) {
        list($label, $field) = $f;
?>
          <option value="<?php echo $path; ?>"
            <?php if ($path == $queue->filter) echo 'selected="selected"'; ?>
            ><?php echo Format::htmlchars($label); ?></option>
<?php } ?>
        </select>
        <div class="error"><?php
            echo Format::htmlchars($errors['filter']); ?></div>
        <br/>

        <div><strong><?php echo __("Default Sorting"); ?></strong></div>
        <hr/>
        <select name="sort_id">
          <option value="" <?php if ($queue->filter == "")
              echo 'selected="selected"'; ?>>— <?php echo __('None'); ?> —</option>
          <option value="::" <?php if ($queue->isDefaultSortInherited())
              echo 'selected="selected"'; ?>>— <?php echo __('Inherit from parent');
            if ($queue->parent
                && ($sort = $queue->parent->getDefaultSort()))
                echo sprintf(' (%s)', $sort->getName()); ?> —</option>
<?php foreach ($queue->getSortOptions() as $sort) { ?>
          <option value="<?php echo $sort->id; ?>"
            <?php if ($sort->id == $queue->sort_id) echo 'selected="selected"'; ?>
            ><?php echo Format::htmlchars($sort->getName()); ?></option>
<?php } ?>
        </select>
        <div class="error"><?php
            echo Format::htmlchars($errors['sort_id']); ?></div>
      </td>
    </table>
  </div>

  <div class="hidden tab_content" id="columns">

    <div>
      <h3 class="title"><?php echo __("Manage columns in this queue"); ?>
        <div class="sub-title"><?php echo __(
            'Add, and remove the fields in this list using the options below. Drag columns to reorder them.');
            ?></div>
      </h3>
    </div>
    <?php include STAFFINC_DIR . "templates/queue-columns.tmpl.php"; ?>
  </div>

  <div class="hidden tab_content" id="export-columns">
    <div>
      <h3 class="title"><?php echo __("Manage Export fields this queue"); ?>
        <div class="sub-title"><?php echo __(
            'Add, and remove the fields in this list using the options below. Drag fields to reorder them.');
            ?></div>
      </h3>
    </div>
    <?php include STAFFINC_DIR . "templates/queue-fields.tmpl.php"; ?>
  </div>
  <div class="hidden tab_content" id="sorting-tab">
    <h3 class="title"><?php echo __("Manage Queue Sorting"); ?>
      <div class="sub-title"><?php echo __(
        "Select the sorting options available in the sorting drop-down when viewing the queue. New items can be added via the drop-down below.");
      ?></div>
    </h3>
    <table class="queue-sort table">
<?php
if ($queue->parent) { ?>
          <tbody>
            <tr>
              <td colspan="3">
                <input type="checkbox" name="inherit-sorting" <?php
                  if ($queue->inheritSorting()) echo 'checked="checked"'; ?>
                  onchange="javascript:$(this).closest('table').find('.if-not-inherited').toggle(!$(this).prop('checked'));" />
                <?php echo __('Inherit sorting from the parent queue'); ?>
                <br /><br />
              </td>
            </tr>
          </tbody>
<?php } ?>
          <tbody class="if-not-inherited <?php if ($queue->inheritSorting()) echo 'hidden'; ?>">
            <tr class="header">
              <td nowrap><small><b><?php echo __('Name'); ?></b></small></td>
              <td><small><b><?php echo __('Details'); ?></b></small></td>
              <td/>
            </tr>
          </tbody>
          <tbody class="sortable-rows if-not-inherited <?php
            if ($queue->inheritSorting()) echo 'hidden'; ?>">
            <tr id="sort-template" class="hidden">
              <td nowrap>
                <i class="faded-more icon-sort"></i>
                <input type="hidden" data-name="sorts[]" />
                <span data-name="name"></span>
              </td>
              <td>
                <div>
                <a class="inline action-button"
                    href="#" onclick="javascript:
                    var colid = $(this).closest('tr').find('[data-name^=sorts]').val();
                    $.dialog('ajax.php/tickets/search/sort/edit/' + colid, 201);
                    return false;
                    "><i class="icon-cog"></i> <?php echo __('Config'); ?></a>
                </div>
              </td>
              <td>
                <div class="pull-right">
                  <small class="hidden faded"><?php echo __('Default'); ?></small>
                  <a href="#" class="drop-sort" title="<?php echo __('Delete');
                    ?>"><i class="icon-trash"></i></a>
                </div>
              </td>
            <tr>
          </tbody>
            <tbody class="if-not-inherited <?php
            if ($queue->inheritSorting()) echo 'hidden'; ?>">
              <tr class="header">
                  <td colspan="3"></td>
              </tr>
              <tr>
                  <td colspan="3" id="append-sort">
                      <i class="icon-plus-sign"></i>
                      <select id="add-sort" data-quick-add="queue-sort">
                          <option value="">— <?php
                            echo __('Add Sort Criteria'); ?> —</option>
<?php foreach (QueueSort::forQueue($queue) as $QS) { ?>
                          <option value="<?php echo $QS->id; ?>"><?php
                            echo Format::htmlchars($QS->getName()); ?></option>
<?php } ?>
                          <option value="0" data-quick-add>&mdash; <?php
                            echo __('Add New Sort Criteria');?> &mdash;</option>
                      </select>
                      <button type="button" class="green button"><?php
                        echo __('Add'); ?></button>
                  </td>
              </tr>
            </tbody>
<script>
+function() {
var Q = setInterval(function() {
  if ($('#append-sort').length == 0)
    return;
  clearInterval(Q);

  var addSortOption = function(sortid, info) {
    if (!sortid) return;
    var copy = $('#sort-template').clone();
    info['sorts[]'] = sortid;
    copy.find('input[data-name]').each(function() {
      var $this = $(this),
          name = $this.data('name');
      if (info[name] !== undefined) {
        $this.val(info[name]);
      }
      $this.attr('name', name);
    });
    copy.find('span').text(info['name']);
    copy.attr('id', '').show().insertBefore($('#sort-template'));
    copy.removeClass('hidden');
    var a = copy.find('a.drop-sort').click(function() {
      $('<option>')
        .attr('value', copy.find('input[data-name^=sorts]').val())
        .text(info.name)
        .insertBefore($('#add-sort')
          .find('[data-quick-add]')
        );
      copy.fadeOut(function() { $(this).remove(); });
      return false;
    });
    if (info.default) {
      a.parent().find('small').show();
      a.remove();
    }
    var selected = $('#add-sort').find('option[value=' + sortid + ']');
    selected.remove();
  };

  $('#append-sort').find('button').on('click', function() {
    var selected = $('#add-sort').find(':selected'),
        id = parseInt(selected.val());
    if (!id)
        return;
    addSortOption(id, {name: selected.text()});
    return false;
  });
<?php foreach ($queue->getSortOptions() as $C) {
  echo sprintf('addSortOption(%d, {name: %s, default: %d});',
    $C->sort_id, JsonDataEncoder::encode($C->getName()),
    $queue->getDefaultSortId() == $C->sort_id
  );
} ?>
}, 25);
$('select#parent-id').change(function() {
    var form = $(this).closest('form');
    var qid = parseInt($(this).val(), 10) || 0;

    if (qid > 0) {
        $.ajax({
            type: "GET",
            url: 'ajax.php/queue/'+qid,
            dataType: 'json',
            success: function(queue) {
                $('#parent-name', form).html(queue.name);
                $('#parent-criteria', form).html(queue.criteria);
                $('#inherited-parent', form).fadeIn();
                }
            })
            .done(function() { })
            .fail(function() { });
    } else {
        $('#inherited-parent', form).fadeOut();
    }
});
}();
</script>
        </table>
    </div>

    <div class="hidden tab_content" id="preview-tab">
    <div id="preview">
    </div>

    <script>
      $(function() {
        $('#preview-tab').on('afterShow', function() {
          $.ajax({
            url: 'ajax.php/queue<?php
                if (isset($queue->id)) echo "/{$queue->id}"; ?>/preview',
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

  <div class="hidden tab_content" id="conditions-tab">
    <div style="margin-bottom: 15px"><?php echo __("Conditions are used to change the view of the data in a row based on some conditions of the data. For instance, a column might be shown bold if some condition is met.");
      ?> <?php echo __("These conditions apply to an entire row in the queue.");
    ?></div>
    <div class="conditions">
<?php
if ($queue->getConditions()) {
  $fields = CustomQueue::getSearchableFields($queue->getRoot());
  foreach ($queue->getConditions() as $i=>$condition) {
     $id = QueueColumnCondition::getUid();
     list($label, $field) = $condition->getField();
     if (!$field || !$label)
        continue;
     $field_name = $condition->getFieldName();
     $object_id = $queue->id;
     include STAFFINC_DIR . 'templates/queue-column-condition.tmpl.php';
  }
} ?>

    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #bbb">
      <i class="icon-plus-sign"></i>
      <select class="add-condition">
        <option value="0">— <?php echo __("Add a condition"); ?> —</option>
<?php
      foreach (CustomQueue::getSearchableFields('Ticket') as $path=>$f) {
          list($label) = $f;
          echo sprintf('<option value="%s">%s</option>', $path, Format::htmlchars($label));
      }
?>
      </select>
      <script>
      $(function() {
        var queueid = <?php echo $queue->id ?: 0; ?>,
            nextid = <?php echo 1000 + QueueColumnCondition::getUid(); ?>;
        $('#conditions-tab select.add-condition').change(function() {
          var $this = $(this),
              container = $this.closest('div'),
              selected = $this.find(':selected');
          if (selected.val() <= 0)
            return;
          $.ajax({
            url: 'ajax.php/queue/condition/add',
            data: { field: selected.val(), object_id: queueid, id: nextid },
            dataType: 'html',
            success: function(html) {
              $(html).insertBefore(container);
              $this.find('[value=0]').select();
              nextid++;
            }
          });
        });
      });
      </script>
    </div>
  </div>

  </div>

  <p style="text-align:center;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick="window.history.go(-1);">
  </p>

</form>
