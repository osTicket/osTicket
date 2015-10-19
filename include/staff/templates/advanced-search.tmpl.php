<?php
$parent_id = $_REQUEST['parent_id'] ?: $search->parent_id;
if ($parent_id
    && (!($queue = CustomQueue::lookup($parent_id)))
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
          <?php foreach (CustomQueue::queues()
              ->filter(array('parent_id' => 0))
              as $q) { ?>
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
  <hr/>
  <div class="flex row">
    <div class="span12">
      <input type="hidden" name="a" value="search">
      <?php include STAFFINC_DIR . 'templates/advanced-search-criteria.tmpl.php'; ?>
    </div>
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
