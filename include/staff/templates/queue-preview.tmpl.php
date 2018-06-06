<?php
// Calling convention (assumed global scope):
// $tickets - <QuerySet> with all columns and annotations necessary to
//      render the full page
// $count - <int> number of records matching the search / filter part of the
//      query

$page = ($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('tickets.php', $args);
$tickets = $pageNav->paginate($tickets);

// Identify columns of output
$columns = $queue->getColumns();

// Apply default sort option
if ($queue_sort = $queue->getDefaultSort()) {
    $tickets = $queue_sort->applySort($tickets);
}

?>
<table class="list queue" border="0" cellspacing="1" cellpadding="2" width="940">
  <thead>
    <tr>
      <th width="12px"></th>
<?php
foreach ($columns as $C) {
    echo sprintf('<th width="%s">%s</th>', $C->getWidth(),
        Format::htmlchars($C->getHeading()));
} ?>
    </tr>
  </thead>
  <tbody>
<?php
foreach ($tickets as $T) {
    echo '<tr>';
    echo '<td><input type="checkbox" disabled="disabled" /></td>';
    foreach ($columns as $C) {
        list($content, $styles) = $C->render($T);
        $style = $styles ? 'style="'.$styles.'"' : '';
        echo "<td $style>";
        echo "<div $style>$content</div>";
        echo "</td>";
    }
    echo '</tr>';
}
?>
  </tbody>
</table>
