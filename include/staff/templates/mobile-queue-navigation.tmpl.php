<?php
//
// Calling conventions
// $q - <CustomQueue> object for this navigation entry
// $selected - <bool> true if this queue is currently active
// $child_selected - <bool> true if the selected queue is a descendent
global $cfg;
if ( is_array( $queues) ) 
foreach ($queues as $_) {
    list($q, $children) = $_;
    if ($q->isPrivate())
        continue;

    $this_queue = $q;
    $childs = $children;
    $selected = (!isset($_REQUEST['a'])  && $_REQUEST['queue'] == $this_queue->getId());

?>
<li class="top-queue item <?php if ($child_selected) echo 'child active';
    elseif ($selected) echo 'active'; elseif (!$selected) echo 'inactive' ?>">
  <a href="#<?php echo ""; /*$this_queue->getHref(); */?>"
    class="Ticket submenu-button"><i class="small icon-sort-down pull-right"></i><?php echo $this_queue->getName(); ?>
<?php if ($cfg->showTopLevelTicketCounts()) { ?>
    <span id="queue-count-bucket" class="hidden">
      (<span class="queue-count"
        data-queue-id="<?php echo $this_queue->id; ?>"><span class="faded-more"></span>
      </span>)
    </span>
<?php } ?>
  </a>
  
    <ul class="scroll-height submenu">
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
             include 'mobile-queue-subnavigation.tmpl.php';
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
        include 'mobile-queue-subnavigation.tmpl.php';
      } ?>
    </ul>

</li>
<?php
}

?>
