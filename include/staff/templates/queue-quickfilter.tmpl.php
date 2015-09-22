<?php
# Calling conventions
#
# $queue - <CustomQueue> queue with quick filter options
# $quick_filter - <string> selected quick filter choice
# $param - <string> URL param to use when selecting an item from the list

if (!$queue || !$queue->filter)
    return;

$param = $param ?: 'filter';
$quick_filter = $quick_filter ?: $_REQUEST[$param];

if (!($qf_field = $queue->getQuickFilterField($quick_filter)))
    return;

$choices = $qf_field->getQuickFilterChoices();
?>
<span class="action-button muted" data-dropdown="#quickfilter-dropdown">
  <i class="icon-caret-down pull-right"></i>
  <span><i class="icon-filter"></i> <?php
    echo $qf_field->get('label');
    if (isset($quick_filter) && isset($choices[$quick_filter]))
      echo sprintf(': %s', Format::htmlchars($choices[$quick_filter])); ?></span>
</span>


<div id="quickfilter-dropdown" class="action-dropdown anchor-right"
onclick="javascript:
var query = addSearchParam({'<?php echo $param; ?>': $(event.target).data('value')});
$.pjax({
    url: '?' + query,
    timeout: 2000,
    container: '#pjax-container'});">
  <ul>
  <?php foreach ($choices as $k=>$desc) {
    $selected = isset($quick_filter) && $quick_filter == $k;
  ?>
    <li <?php
    if ($selected) echo 'class="active"';
    ?>>
      <a href="#" data-value="<?php echo Format::htmlchars($k); ?>">
        <?php echo Format::htmlchars($desc); ?></a>
    </li>
  <?php } ?>
  </ul>
</div>
