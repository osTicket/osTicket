<?php
$parent_id = $_REQUEST['parent_id'] ?: $search->parent_id;
if ($parent_id
    && (!($parent = CustomQueue::lookup($parent_id)))
) {
    $parent_id = null;
}
?>
<div id="advanced-search" class="advanced-search">
<h3 class="drag-handle"><?php echo __('Advanced Ticket Search');?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>

<form action="#tickets/search" method="post" name="search">

  <div class="flex row">
    <div class="span6">
      <select name="parent_id">
          <option value="0" <?php
              if (!$parent_id) echo 'selected="selected"';
              ?>><?php echo '—'.__("My Searches").'—'; ?></option>
          <?php
foreach (CustomQueue::queues()->order_by('sort', 'title') as $q) { ?>
          <option value="<?php echo $q->id; ?>"
              <?php if ($parent_id == $q->id) echo 'selected="selected"'; ?>
              ><?php echo $q->getFullName(); ?></option>
<?php       } ?>
      </select>
    </div><div class="span6">
      <input name="name" type="text" size="30" 
        value="<?php echo Format::htmlchars($search->getName()); ?>"
        placeholder="<?php
        echo __('Enter a title for the search queue'); ?>"/>
      <div class="error"><?php echo Format::htmlchars($errors['name']); ?></div>
    </div>
  </div>

<ul class="tabs">
    <li class="active"><a href="#criteria"><?php echo __('Criteria'); ?></a></li>
    <li><a href="#columns"><?php echo __('Columns'); ?></a></li>
</ul>

<div class="tab_content" id="criteria">
  <div class="flex row">
    <div class="span12">
<?php if ($parent) { ?>
      <div class="faded" style="margin-bottom: 1em">
      <div>
        <strong><?php echo __('Inherited Criteria'); ?></strong>
      </div>
      <div>
        <?php echo nl2br(Format::htmlchars($parent->describeCriteria())); ?>
      </div>
      </div>
<?php } ?>
      <input type="hidden" name="a" value="search">
      <?php include STAFFINC_DIR . 'templates/advanced-search-criteria.tmpl.php'; ?>
    </div>
  </div>

</div>

<div class="tab_content hidden" id="columns">
    <?php 
    $queue = $search;
    include STAFFINC_DIR . "templates/queue-columns.tmpl.php"; ?>
</div>

  <hr/>
  <div>
    <div class="buttons pull-right">
      <button class="button" type="submit" name="submit" value="search"
        id="do_search"><i class="icon-search"></i>
        <?php echo __('Search'); ?></button>
      <button class="green button" type="submit" name="submit" value="save"
        onclick="javascript:
          var form = $(this).closest('form');
          form.attr('action', form.attr('action') + '/' + <?php echo
            $search->id ?: "'create'"; ?>);"
        ><i class="icon-save"></i>
        <?php echo __('Save'); ?>
      </button>
    </div>
  </div>

</form>

<script>
+function() {
   // Return a helper with preserved width of cells
   var fixHelper = function(e, ui) {
      ui.children().each(function() {
          $(this).width($(this).width());
      });
      return ui;
   };
   // Sortable tables for dynamic forms objects
   $('.sortable-rows').sortable({
       'helper': fixHelper,
       'cursor': 'move',
       'stop': function(e, ui) {
           var attr = ui.item.parent('tbody').data('sort'),
               offset = parseInt($('#sort-offset').val(), 10) || 0;
           warnOnLeave(ui.item);
           $('input[name^='+attr+']', ui.item.parent('tbody')).each(function(i, el) {
               $(el).val(i + 1 + offset);
           });
       }
   });
}();
</script>
