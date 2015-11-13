<?php
//
// Calling conventions
// $searches = All visibile saved searches
// $child_selected - <bool> true if the selected queue is a descendent
// $adhoc - not FALSE if an adhoc advanced search exists
?>
<li class="item <?php if ($child_selected) echo 'child active'; ?>">
<?php
  $href = 'href="tickets.php?queue=adhoc"';
  if (!isset($_SESSION['advsearch']))
      $href = 'href="#" data-dialog="ajax.php/tickets/search"';
?>
  <a <?php echo $href; ?>><i class="icon-sort-down pull-right"></i><?php echo __('Search');
  ?></a>
  <div class="customQ-dropdown">
    <ul class="scroll-height">
      <!-- Start Dropdown and child queues -->
      <?php foreach ($searches->findAll(array(
            'staff_id' => $thisstaff->getId(),
            'parent_id' => 0,
            Q::not(array(
                'flags__hasbit' => SavedSearch::FLAG_PUBLIC
            ))
      )) as $q) {
        include 'queue-subnavigation.tmpl.php';
      } ?>
      <li>
        <h4><?php echo __('Recent Searches'); ?></h4>
<?php if (isset($_SESSION['advsearch'])) {
          foreach ($_SESSION['advsearch'] as $token=>$criteria) {
              $q = new SavedSearch(array('root' => 'T'));
              $q->id = 'adhoc,'.$token;
              $q->title = $q->describeCriteria($criteria);

              include 'queue-subnavigation.tmpl.php';
          }
      } ?>
      <!-- Dropdown Titles -->
      </li>
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
