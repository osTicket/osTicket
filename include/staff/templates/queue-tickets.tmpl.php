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

// Make sure the cdata materialized view is available
TicketForm::ensureDynamicDataView();

// Identify columns of output
$columns = $queue->getColumns();

// Figure out REFRESH url — which might not be accurate after posting a
// response
list($path,) = explode('?', $_SERVER['REQUEST_URI'], 2);
$args = array();
parse_str($_SERVER['QUERY_STRING'], $args);

// Remove commands from query
unset($args['id']);
if ($args['a'] !== 'search') unset($args['a']);

$refresh_url = $path . '?' . http_build_query($args);

?>

<!-- SEARCH FORM START -->
<div id='basic_search'>
  <div class="pull-right" style="height:25px">
    <span class="valign-helper"></span>
    <?php
    require STAFFINC_DIR.'templates/queue-sort.tmpl.php';
    ?>
  </div>
    <form action="tickets.php" method="get" onsubmit="javascript:
  $.pjax({
    url:$(this).attr('action') + '?' + $(this).serialize(),
    container:'#pjax-container',
    timeout: 2000
  });
return false;">
    <input type="hidden" name="a" value="search">
    <input type="hidden" name="search-type" value=""/>
    <div class="attached input">
      <input type="text" class="basic-search" data-url="ajax.php/tickets/lookup" name="query"
        autofocus size="30" value="<?php echo Format::htmlchars($_REQUEST['query'], true); ?>"
        autocomplete="off" autocorrect="off" autocapitalize="off">
      <button type="submit" class="attached button"><i class="icon-search"></i>
      </button>
    </div>
    <a href="#" onclick="javascript:
        $.dialog('ajax.php/tickets/search', 201);"
        >[<?php echo __('advanced'); ?>]</a>
        <i class="help-tip icon-question-sign" href="#advanced"></i>
    </form>
</div>
<!-- SEARCH FORM END -->

<div class="clear"></div>
<div style="margin-bottom:20px; padding-top:5px;">
    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><a href="<?php echo $refresh_url; ?>"
                    title="<?php echo __('Refresh'); ?>"><i class="icon-refresh"></i> <?php echo
                    $queue->getName(); ?></a></h2>
            </div>
            <div class="pull-right flush-right">
            <?php
            // TODO: Respect queue root and corresponding actions
            if ($count) {
                Ticket::agentActions($thisstaff, array('status' => $status));
            }?>
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>

<form action="?" method="POST" name='tickets' id="tickets">
<?php csrf_token(); ?>
 <input type="hidden" name="a" value="mass_process" >
 <input type="hidden" name="do" id="action" value="" >

<table class="list" border="0" cellspacing="1" cellpadding="2" width="940">
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
  <tfoot>
    <tr>
      <td colspan="7">
        <?php if($total && $thisstaff->canManageTickets()){ ?>
        <?php echo __('Select');?>:&nbsp;
        <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
        <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
        <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
        <?php }else{
            echo '<i>';
            echo $ferror?Format::htmlchars($ferror):__('Query returned 0 results.');
            echo '</i>';
        } ?>
      </td>
    </tr>
  </tfoot>
</table>

<?php
    if ($count > 0) { //if we actually had any tickets returned.
?>  <div>
      <span class="faded pull-right"><?php echo $pageNav->showing(); ?></span>
<?php
        echo __('Page').':'.$pageNav->getPageLinks().'&nbsp;';
        echo sprintf('<a class="export-csv no-pjax" href="?%s">%s</a>',
                Http::build_query(array(
                        'a' => 'export', 'queue' => $_REQUEST['queue'],
                        'status' => $_REQUEST['status'])),
                __('Export'));
        ?>
        <i class="help-tip icon-question-sign" href="#export"></i>
    </div>
<?php
    } ?>

</form>
