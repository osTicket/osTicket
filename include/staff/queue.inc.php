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
      // <?php echo $title; ?>
      <?php if (isset($queue->id)) { ?><small>
      — <?php echo $queue->getName(); ?></small>
      <?php } ?>
  </h2>


  <ul class="clean tabs">
    <li class="active"><a href="#criteria"><i class="icon-filter"></i>
      <?php echo __('Criteria'); ?></a></li>
    <li><a href="#columns"><i class="icon-columns"></i>
      <?php echo __('Columns'); ?></a></li>
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
        <div><input type="checkbox" class="checkbox" name="inherit" <?php
            if ($queue->inheritCriteria()) echo 'checked="checked"';
            ?>/> <?php echo __('Include parent search criteria'); ?></div>
        <hr/>
        <div class="error"><?php echo $errors['criteria']; ?></div>
        <div class="advanced-search">
<?php
            $form = $queue->getSearchForm();
            $search = $queue;
            $matches = SavedSearch::getSupportedTicketMatches();
            include STAFFINC_DIR . 'templates/advanced-search-criteria.tmpl.php';
?>
        </div>
      </td>
      <td style="width:35%; padding-left:40px; vertical-align:top">
        <div><strong><?php echo __("Parent Queue"); ?>:</strong></div>
        <select name="parent_id">
          <option value="0">— <?php echo __('Top-Level Queue'); ?> —</option>
<?php foreach (CustomQueue::objects() as $cq) {
        if ($cq->getId() == $queue->getId())
          continue;
?>
          <option value="<?php echo $cq->id; ?>"
            <?php if ($cq->getId() == $queue->parent_id) echo 'selected="selected"'; ?>
            ><?php echo $cq->getName(); ?></option>
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
    <h2><?php echo __("Manage columns in this queue"); ?></h2>
    <p><?php echo __("Add, remove, and customize the content of the columns in this queue using the options below. Click a column header to manage or resize it"); ?></p>

    <div>
      <i class="icon-plus-sign"></i>
      <select id="add-column" data-next-id="0" onchange="javascript:
        var $this = $(this),
            selected = $this.find(':selected'),
            nextId = $this.data('nextId'),
            columns = $('#resizable-columns');
        $.ajax({
          url: 'ajax.php/queue/addColumn',
          data: { field: selected.val(), id: nextId },
          dataType: 'json',
          success: function(json) {
            var div = $('<div></div>')
                .addClass('column-header ui-resizable')
                .text(json.heading)
                .data({id: nextId, colId: 'colconfig-'+nextId, width: json.width})
                .append($('<i>')
                  .addClass('icon-ellipsis-vertical ui-resizable-handle ui-resizable-handle-e')
                )
                .append($('<input />')
                  .attr({type:'hidden', name:'columns[]'})
                  .val(nextId)
                );
              config = $('<div></div>')
                .addClass('hidden column-configuration')
                .attr('id', 'colconfig-' + nextId);
            config.append($(json.config)).insertAfter(columns.append(div));
            $this.data('nextId', nextId+1);
          }
        });
      ">
        <option value="">— <?php echo __('Add a column'); ?> —</option>
<?php foreach (SavedSearch::getSearchableFields('Ticket') as $path=>$f) {
        list($label,) = $f; ?>
        <option value="<?php echo $path; ?>"><?php echo $label; ?></option>
<?php } ?>
      </select>

      <div id="resizable-columns">
<?php foreach ($queue->getColumns() as $column) {
        $colid = $column->getId();
        $maxcolid = max(@$maxcolid ?: 0, $colid);
        echo sprintf('<div data-id="%1$s" data-col-id="colconfig-%1$s" class="column-header" '
          .'data-width="%2$s">%3$s'
          .'<i class="icon-ellipsis-vertical ui-resizable-handle ui-resizable-handle-e"></i>'
          .'<input type="hidden" name="columns[]" value="%1$s"/>'
          .'</div>',
          $colid, $column->getWidth(), $column->getHeading(),
          $column->sort ?: 1);
} ?>
      </div>
      <script>
        $(function() {
          $('#add-column').data('nextId', <?php echo $maxcolid+1; ?>);
          var qq = setInterval(function() {
            var total = 0,
                container = $('#resizable-columns'),
                width = container.width(),
                w2px = 1.25,
                columns = $('.column-header', container);
            // Await computation of the <div>'s width
            if (width)
              clearInterval(qq);
            columns.each(function() {
              total += $(this).data('width') || 100;
            });
            container.data('w2px', w2px);
            columns.each(function() {
              // FIXME: jQuery will compensate for padding (40px)
              $(this).width(w2px * ($(this).data('width') || 100) - 42);
            });
          }, 20);
        });
      </script>

<?php foreach ($queue->getColumns() as $column) {
        $colid = $column->getId();
        echo sprintf('<div class="hidden column-configuration" id="colconfig-%s">',
            $colid);
        include STAFFINC_DIR . 'templates/queue-column.tmpl.php';
        echo '</div>';
} ?>
    </div>

    <script>
      var aa = setInterval(function() {
        var cols = $('#resizable-columns');
        if (cols.length && cols.sortable)
          clearInterval(aa);
        cols.sortable({
          containment: 'parent'
        });
        $('.column-header', cols).resizable({
          handles: {'e' : '.ui-resizable-handle'},
          grid: [ 20, 0 ],
          maxHeight: 16,
          minHeight: 16,
          stop: function(event, ui) {
            var w2px = ui.element.parent().data('w2px'),
                width = ui.element.width() - 42;
            ui.element.data('width', width / w2px);
            // TODO: Update WIDTH text box in the data form
          }
        });
        cols.click('.column-header', function(e) {
          var $this = $(event.target);
          $this.parent().children().removeClass('active');
          $this.addClass('active');
          $('.column-configuration', $this.closest('.tab_content')).hide();
          $('#'+$this.data('colId')).fadeIn('fast');
        });
      }, 20);
    </script>
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
