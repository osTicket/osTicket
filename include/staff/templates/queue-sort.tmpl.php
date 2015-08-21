
<span class="action-button muted" data-dropdown="#sort-dropdown" data-toggle="tooltip" title="<?php echo $sort_options[$sort_cols]; ?>">
  <i class="icon-caret-down pull-right"></i>
  <span><i class="icon-sort-by-attributes-alt <?php if ($sort_dir) echo 'icon-flip-vertical'; ?>"></i> <?php echo __('Sort');?></span>
</span>
<div id="sort-dropdown" class="action-dropdown anchor-right"
onclick="javascript:
var query = addSearchParam({'sort': $(event.target).data('mode'), 'dir': $(event.target).data('dir')});
$.pjax({
    url: '?' + query,
    timeout: 2000,
    container: '#pjax-container'});">
  <ul class="bleed-left">
    <?php foreach ($queue_sort_options as $mode) {
    $desc = $sort_options[$mode];
    $icon = '';
    $dir = '0';
    $selected = $sort_cols == $mode; ?>
    <li <?php
    if ($selected) {
    echo 'class="active"';
    $dir = ($sort_dir == '1') ? '0' : '1'; // Flip the direction
    $icon = ($sort_dir == '1') ? 'icon-hand-up' : 'icon-hand-down';
    }
    ?>>
        <a href="#" data-mode="<?php echo $mode; ?>" data-dir="<?php echo $dir; ?>">
          <i class="icon-fixed-width <?php echo $icon; ?>"
          ></i> <?php echo Format::htmlchars($desc); ?></a>
      </li>
    <?php } ?>
 </ul>
</div>

