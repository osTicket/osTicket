<?php
//
// Calling conventions
// $q - <CustomQueue> object for this navigation entry
// $selected - <bool> true if this queue is currently active
// $child_selected - <bool> true if the selected queue is a descendent
$queue = $q;
$selected = $_REQUEST['queue'] == $queue->getId();
?>
<li class="item <?php if ($child_selected) echo 'child active';
    elseif ($selected) echo 'active'; ?>">
  <a href="<?php echo $queue->getHref(); ?>"><i class="icon-sort-down pull-right"></i><?php echo $queue->getName(); ?></a>
  <div class="customQ-dropdown">
    <ul class="scroll-height">
      <!-- Start Dropdown and child queues -->
      <?php foreach ($queue->getPublicChildren() as $q) {
          include 'queue-subnavigation.tmpl.php';
      } ?>
      <!-- Dropdown Titles -->
      <li>
        <h4><?php echo __('Personal Queues'); ?></h4>
      </li>
      <?php foreach ($queue->getMyChildren() as $q) {
        include 'queue-subnavigation.tmpl.php';
      } ?>
    </ul>
    <!-- Add Queue button sticky at the bottom -->
    <div class="add-queue">
      <a class="flush-right full-width" onclick="javascript:
        $.dialog('ajax.php/tickets/search', 201);">
        <div class="add pull-right"><i class="green icon-plus-sign"></i></div>
          <span><?php echo __('Add personal queue'); ?></span>
      </a>
    </div>
  </div>
</li>
