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

?>
<table class="list queue" border="0" cellspacing="1" cellpadding="2" width="940">
  <thead>
    <tr>
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
    foreach ($columns as $C) {
        echo "<td>";
        echo $C->render($T);
        echo "</td>";
    }
    echo '</tr>';
}
?>
  </tbody>
</table>
