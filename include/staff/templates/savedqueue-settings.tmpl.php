<div style="overflow-y: auto; height:auto; max-height: 350px;">
 <div>
    <div class="faded"><strong><?php echo __('Name'); ?></strong></div>
    <div>
    <?php
    if ($queue->checkOwnership($thisstaff)) { ?>
    <input name="queue-name" type="text" size="40"
        value="<?php echo Format::htmlchars($queue->getName()); ?>"
        placeholder="<?php echo __('Search Title'); ?>">
    <?php
    } else {
        echo Format::htmlchars($queue->getName());
    } ?>
    </div>
    <div class="error" id="name-error"><?php echo
    Format::htmlchars($errors['queue-name']); ?></div>
 </div>
 <div>
    <div class="faded"><strong><?php echo __("Quick Filter"); ?></strong></div>
    <div>
        <select name="filter">
          <option value="" <?php if ($queue->filter == "")
              echo 'selected="selected"'; ?>>— <?php echo __('None'); ?> —</option>
          <?php
          if ($queue->parent) { ?>
          <option value="::" <?php if ($queue->filter == "::")
              echo 'selected="selected"'; ?>>— <?php echo __('Inherit from parent');
            if (($qf = $queue->parent->getQuickFilterField()))
                echo sprintf(' (%s)', $qf->getLabel()); ?> —</option>
<?php
          }
         foreach ($queue->getSupportedFilters() as $path => $f) {
            list($label, $field) = $f;
?>
          <option value="<?php echo $path; ?>"
            <?php if ($path == $queue->filter) echo 'selected="selected"'; ?>
            ><?php echo Format::htmlchars($label); ?></option>
<?php } ?>
         </select>
      </div>
        <div class="error"><?php
            echo Format::htmlchars($errors['filter']); ?></div>
 </div>
 <div>
    <div class="faded"><strong><?php echo __("Default Sorting"); ?></strong></div>
    <div>
        <select name="sort_id">
         <option value="" <?php if ($queue->sort_id == 0)
            echo 'selected="selected"'; ?>>— <?php echo __('System Default'); ?> —</option>
         <?php
         if ($queue->parent) { ?>
          <option value="::" <?php echo $queue->isDefaultSortInherited() ?
              'selected="selected"' : ''; ?>>— <?php echo __('Inherit from parent');
            if ($sort = $queue->parent->getDefaultSort())
                echo sprintf(' (%s)', Format::htmlchars($sort->getName())); ?> —</option>
        <?php
         }
        foreach ($queue->getSortOptions() as $sort) { ?>
          <option value="<?php echo $sort->id; ?>"
            <?php if ($sort->id == $queue->sort_id) echo 'selected="selected"'; ?>
            ><?php echo Format::htmlchars($sort->getName()); ?></option>
<?php } ?>
        </select>
      </div>
        <div class="error"><?php
            echo Format::htmlchars($errors['sort_id']); ?></div>
  </div>
</div>
