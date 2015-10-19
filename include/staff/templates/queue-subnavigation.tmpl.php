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
<li class="<?php if ($hasChildren)  echo 'subQ'; ?>">
  <?php
  if ($hasChildren) { ?>
    <i class="icon-caret-down"></i>
  <?php }
  if ($thisstaff->isAdmin()) { ?>
  <!-- Edit Queue -->
  <div class="editQ pull-right">
    <i class="icon-cog"></i>
    <div class="manageQ">
      <ul>
        <?php if ($hasChildren) { ?>
        <li class="positive">
          <a href="<?php echo $queue->getHref(); ?>">
            <i class="icon-fixed-width icon-plus-sign"></i><?php echo __('Add Queue'); ?></a>
        </li>
        <?php } ?>
        <li>
          <a href="<?php
    echo $queue->isPrivate()
        ? sprintf('#" data-dialog="ajax.php/tickets/search/%d', $queue->getId())
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
  <?php } ?>
  <!-- Display Latest Ticket count -->
  <span class="pull-right">(?)</span>
  <!-- End Edit Queue -->
  <a class="truncate <?php if ($selected) echo ' active'; ?>" href="<?php echo $queue->getHref();
    ?>"><?php echo $q->getName(); ?></a>
<?php if ($hasChildren) {
    echo '<ul>';
    foreach ($children as $q) {
        include __FILE__;
    }
    echo '</ul>';
} ?>
</li>
