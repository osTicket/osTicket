<?php
//
// Calling conventions
// $q - <CustomQueue> object for this navigation entry
// $selected - <bool> true if this queue is currently active
// $child_selected - <bool> true if the selected queue is a descendent
global $cfg;
$childs = $children;
$this_queue = $q;
$selected = (!isset($_REQUEST['a'])  && $_REQUEST['queue'] == $this_queue->getId());
?>
<li class="top-queue item <?php if ($child_selected) echo 'child active';
    elseif ($selected) echo 'active'; ?>">
  <a href="<?php echo $this_queue->getHref(); ?>"
    class="Ticket"><i class="small icon-sort-down pull-right"></i><?php echo $this_queue->getName(); ?>
<?php if ($cfg->showTopLevelTicketCounts()) { ?>
    <span id="queue-count-bucket" class="hidden">
      (<span class="queue-count"
        data-queue-id="<?php echo $this_queue->id; ?>"><span class="faded-more"></span>
      </span>)
    </span>
<?php } ?>
  </a>
  <div class="customQ-dropdown">
    <ul class="scroll-height">
      <!-- Add top-level queue (with count) -->

      <?php
      if (!$children) { ?>
      <li class="top-level">
        <span class="pull-right newItemQ queue-count"
          data-queue-id="<?php echo $q->id; ?>"><span class="faded-more">-</span>
        </span>

        <a class="truncate <?php if ($selected) echo ' active'; ?>" href="<?php echo $q->getHref();
          ?>" title="<?php echo Format::htmlchars($q->getName()); ?>">
        <?php
          echo Format::htmlchars($q->getName()); ?>
        </a>
      </li>
      <?php
      } ?>
      <!-- Start Dropdown and child queues -->
      <?php foreach ($childs as $_) {
          list($q, $children) = $_;
          if (!$q->isPrivate())
              include 'queue-subnavigation.tmpl.php';
      }
      $first_child = true;
      foreach ($childs as $_) {
        list($q, $children) = $_;
        if (!$q->isPrivate())
            continue;
        if ($first_child) {
            $first_child = false;
            echo '<li class="personalQ"></li>';
        }
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
