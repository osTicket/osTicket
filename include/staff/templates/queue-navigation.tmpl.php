<?php
//
// Calling conventions
// $q - <CustomQueue> object for this navigation entry
// $selected - <bool> true if this queue is currently active
// $child_selected - <bool> true if the selected queue is a descendent
$this_queue = $q;
$selected = (!isset($_REQUEST['a'])  && $_REQUEST['queue'] == $this_queue->getId());
?>
<li class="top-queue item <?php if ($child_selected) echo 'child active';
    elseif ($selected) echo 'active'; ?>">
  <a href="<?php echo $this_queue->getHref(); ?>"
    class="Ticket"><i class="small icon-sort-down pull-right"></i><?php echo $this_queue->getName(); ?></a>
  <div class="customQ-dropdown">
    <ul class="scroll-height">
      <!-- Add top-level queue (with count) -->
      <li class="top-level">
        <span class="pull-right newItemQ queue-count"
          data-queue-id="<?php echo $q->id; ?>"><span class="faded-more">-</span>
        </span>

        <a class="truncate <?php if ($selected) echo ' active'; ?>" href="<?php echo $q->getHref();
          ?>" title="<?php echo Format::htmlchars($q->getName()); ?>">
        <?php
          echo Format::htmlchars($q->getName()); ?>
        </a>
        </h4>
      </li>

      <!-- Start Dropdown and child queues -->
      <?php foreach ($this_queue->getPublicChildren() as $q) {
          include 'queue-subnavigation.tmpl.php';
      } ?>
      <!-- Personal Queues -->
      <?php
      $queues = $this_queue->getMyChildren();
      if (count($queues)) { ?>
      <li class="personalQ"></li>
      <?php foreach ($queues as $q) {
        include 'queue-subnavigation.tmpl.php';
       }
      }?>
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
