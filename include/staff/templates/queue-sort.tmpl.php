<?php
if (count($queue->getSortOptions()) === 0)
    return;

if (isset($sort) && isset($sort['queuesort'])) {
    $queuesort = $sort['queuesort'];
    $sort_id = $queuesort->id;
    $sort_dir = $sort['dir'];
}
elseif (strpos($_GET['sort'], 'qs-') === 0) {
    $sort_id = substr($_GET['sort'], 3);
    $queuesort = QueueSort::lookup($sort_id);
    $sort_dir = $_GET['dir'];
} elseif ($queuesort = $queue->getDefaultSort()) {
    $sort_id = $queuesort->id;
}

?>

<span class="action-button muted" data-dropdown="#sort-dropdown"
  data-toggle="tooltip" title="<?php
    if (is_object($queuesort)) echo Format::htmlchars($queuesort->getName()); ?>">
  <i class="icon-caret-down pull-right"></i>
  <span><i class="icon-sort-by-attributes-alt <?php if ($sort_dir) echo 'icon-flip-vertical'; ?>"></i> <?php echo __('Sort');?></span>
</span>
<div id="sort-dropdown" class="action-dropdown anchor-right"
onclick="javascript:
var $et = $(event.target),
    query = addSearchParam({'sort': $et.data('mode'), 'dir': $et.data('dir')});
$.pjax({
    url: '?' + query,
    timeout: 2000,
    container: '#pjax-container'});
return false;">
  <ul class="bleed-left">
    <?php foreach ($queue->getSortOptions() as $qs) {
    $desc = $qs->getName();
    $icon = '';
    $dir = '0';
    $selected = isset($queuesort) && $queuesort->id == $qs->id; ?>
    <li <?php
    if ($selected) {
      echo 'class="active"';
      $dir = ($sort_dir == '1') ? '0' : '1'; // Flip the direction
      $icon = ($sort_dir == '1') ? 'icon-hand-up' : 'icon-hand-down';
    }
    ?>>
        <a href="#" data-mode="qs-<?php echo $qs->id; ?>" data-dir="<?php echo $dir; ?>">
          <i class="icon-fixed-width <?php echo $icon; ?>"
          ></i> <?php echo Format::htmlchars($desc); ?></a>
      </li>
    <?php } ?>
 </ul>
</div>

