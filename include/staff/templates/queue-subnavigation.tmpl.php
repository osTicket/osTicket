<?php
// Calling conventions
// $q - <CustomQueue> object for this navigation entry
$queue = $q;
$children = $queue instanceof CustomQueue ? $queue->getPublicChildren() : array();
$hasChildren = count($children) > 0;
$selected = $_REQUEST['queue'] == $q->getId();
global $thisstaff;
?>
<!-- SubQ class: only if top level Q has subQ -->
<li <?php if ($hasChildren)  echo 'class="subQ"'; ?>>

<?php      
    if ($q->isPrivate()) { ?>
  <!-- Edit Queue -->
  <div class="controlQ">
  <div class="editQ pull-right">
    <i class="icon-cog"></i>
    <div class="manageQ">
      <ul>
        <li>
          <a href="<?php
    echo $queue->isPrivate()
        ? sprintf('#" data-dialog="ajax.php/tickets/search/%s',
            urlencode($queue->getId()))
        : sprintf('queues.php?id=%d', $queue->getId()); ?>">
            <i class="icon-fixed-width icon-pencil"></i>
            <?php echo __('Edit'); ?></a>
        </li>
        <li class="danger">
          <a href="#"><i class="icon-fixed-width icon-trash"></i><?php echo __('Delete'); ?></a>
        </li>
      </ul>
    </div>
  </div>
    </div>
  <?php } ?>
  <!-- Display Latest Ticket count -->      
      <span class="<?php if ($q->isPrivate())  echo 'personalQmenu'; ?> pull-right newItemQ">(90)</span>

  <!-- End Edit Queue -->
  <a class="truncate <?php if ($selected) echo ' active'; ?>" href="<?php echo $queue->getHref();
    ?>" title="<?php echo Format::htmlchars($q->getName()); ?>">
      <?php
        echo Format::htmlchars($q->getName()); ?>
      <?php
        if ($hasChildren) { ?>
            <i class="icon-caret-down"></i>
      <?php } ?>
    </a>

    <?php if ($hasChildren) {
    echo '<ul class="subMenuQ">';
    foreach ($children as $q) {
        include __FILE__;
    }
    echo '</ul>';
} ?>
</li>
