<?php
//
// Calling conventions
// $q - <CustomQueue> object for this navigation entry
// $selected - <bool> true if this queue is currently active
// $child_selected - <bool> true if the selected queue is a descendent
$this_queue = $q;
$selected = $_REQUEST['queue'] == $this_queue->getId();
?>
<li class="item <?php if ($child_selected) echo 'child active';
    elseif ($selected) echo 'active'; ?>">
  <a href="<?php echo $this_queue->getHref(); ?>"><i class="icon-sort-down pull-right"></i><?php echo $this_queue->getName(); ?></a>
  <div class="customQ-dropdown">
    <ul class="scroll-height">
      <!-- Start Dropdown and child queues -->
      <?php foreach ($this_queue->getPublicChildren() as $q) {
          include 'queue-subnavigation.tmpl.php';
      } ?>
      <!-- Dropdown Titles -->
        <li class="personalQ">
        </li>      

      <?php foreach ($this_queue->getMyChildren() as $q) {
        include 'queue-subnavigation.tmpl.php';
      } ?>
    </ul>
    <!-- Add Queue button sticky at the bottom -->
    <div class="add-queue">
      <a class="full-width" onclick="javascript:
        var pid = <?php echo $this_queue->getId() ?: 0; ?>;
        $.dialog('ajax.php/tickets/search?parent_id='+pid, 201);">
        <span><i class="green icon-plus-sign"></i> 
          <?php echo __('Add personal queue'); ?></span>
      </a>
    </div>
  </div>
</li>
