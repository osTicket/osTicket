<?php
// Calling conventions
// $q - <CustomQueue> object for this navigation entry
$queue = $q;
$children = $queue instanceof CustomQueue ? $queue->getPublicChildren() : array();
$subq_searches = $queue instanceof CustomQueue ? $queue->getMyChildren() : array();
$hasChildren = count($children) + count($subq_searches) > 0;
$selected = $_REQUEST['queue'] == $q->getId();
global $thisstaff;
?>
<!-- SubQ class: only if top level Q has subQ -->
<li <?php if ($hasChildren)  echo 'class="subQ"'; ?>>

  <span class="<?php if ($thisstaff->isAdmin() || $q->isPrivate())  echo 'personalQmenu'; ?>
    pull-right newItemQ queue-count"
    data-queue-id="<?php echo $q->id; ?>"><span class="faded-more">-</span>
  </span>

  <a class="truncate <?php if ($selected) echo ' active'; ?>" href="<?php echo $queue->getHref();
    ?>" title="<?php echo Format::htmlchars($q->getName()); ?>">
      <?php
        echo Format::htmlchars($q->getName()); ?>
      <?php
        if ($hasChildren) { ?>
            <i class="icon-caret-down"></i>
      <?php } ?>
    </a>

    <?php
    $closure_include = function($q) use ($thisstaff, $ost, $cfg) {
        global $thisstaff, $ost, $cfg;
        include __FILE__;
    };
    if ($hasChildren) { ?>
    <ul class="subMenuQ">
    <?php
    foreach ($children as $q)
        $closure_include($q);

    // Include personal sub-queues
    $first_child = true;
    foreach ($subq_searches as $q) {
      if ($first_child) {
          $first_child = false;
          echo '<li class="personalQ"></li>';
      }
      $closure_include($q);
    } ?>
    </ul>
<?php
} ?>
</li>
