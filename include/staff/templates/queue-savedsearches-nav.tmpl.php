<?php
//
// Calling conventions
// $searches = All visibile saved searches
// $child_selected - <bool> true if the selected queue is a descendent
// $adhoc - not FALSE if an adhoc advanced search exists

$searches = SavedQueue::getHierarchicalQueues($thisstaff);
if ($queue && !$queue->parent_id && $queue->staff_id)
    $child_selected = true;
?>
<li class="primary-only item <?php if ($child_selected) echo 'active'; ?>">
<?php
  $href = 'href="tickets.php?queue=adhoc"';
  if (!isset($_SESSION['advsearch']))
      $href = 'href="#" data-dialog="ajax.php/tickets/search"';
?>
  <a class="Ticket" <?php echo $href; ?>><i class="icon-sort-down pull-right"></i><?php echo __('Search');
  ?></a>
  <div class="customQ-dropdown">
    <ul class="scroll-height">
      <!-- Start Dropdown and child queues -->
      <?php foreach ($searches as $search) {
          list($q, $children) = $search;
          if ($q->checkAccess($thisstaff))
            include 'queue-subnavigation.tmpl.php';
      } ?>
     <?php
     if (isset($_SESSION['advsearch'])) { ?>
      <li>
        <h4><?php echo __('Recent Searches'); ?></h4>
        <?php
          foreach ($_SESSION['advsearch'] as $token=>$criteria) {
              $q = new SavedSearch(array('root' => 'T'));
              $q->id = 'adhoc,'.$token;
              $q->title = $q->describeCriteria($criteria);

              include 'queue-subnavigation.tmpl.php';
          } ?>
      </li>
     <?php
     } ?>
    </ul>
    <!-- Add Queue button sticky at the bottom -->

     <div class="add-queue">
      <a class="full-width" onclick="javascript:
        $.dialog('ajax.php/tickets/search', 201);">
        <span><i class="green icon-plus-sign"></i>
          <?php echo __('Add personal search'); ?></span>
      </a>
    </div>
  </div>
</li>
