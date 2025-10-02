<?php
$report = new OverviewReport($_POST['start'], $_POST['period']);
$plots = $report->getPlotData();

?>
<script type="text/javascript" src="js/raphael-min.js?0375576"></script>
<script type="text/javascript" src="js/g.raphael.js?0375576"></script>
<script type="text/javascript" src="js/g.line-min.js?0375576"></script>
<script type="text/javascript" src="js/g.dot-min.js?0375576"></script>
<script type="text/javascript" src="js/dashboard.inc.js?0375576"></script>

<link rel="stylesheet" type="text/css" href="css/dashboard.css?0375576"/>

<form method="post" action="dashboard.php">
<div id="basic_search">
    <div style="min-height:25px;">
        <!--<p><?php //echo __('Select the starting time and period for the system activity graph');?></p>-->
            <?php echo csrf_token(); ?>
            <label>
                <?php echo __( 'Report timeframe'); ?>:
                <input type="text" class="dp input-medium search-query"
                    name="start" placeholder="<?php echo __('Last month');?>"
                    value="<?php
                        echo Format::htmlchars($report->getStartDate());
                    ?>" />
            </label>
            <label>
                <?php echo __('period');?>:
                <select name="period">
                    <?php foreach ($report::$end_choices as $val=>$desc)
                            echo "<option value='$val'>" . __($desc) . "</option>"; ?>
                </select>
            </label>
            <button class="green button action-button muted" type="submit">
                <?php echo __( 'Refresh');?>
            </button>
            <i class="help-tip icon-question-sign" href="#report_timeframe"></i>
    </div>
</div>
<div class="clear"></div>
<div style="margin-bottom:20px; padding-top:5px;">
    <div class="pull-left flush-left">
        <h2><?php echo __('Ticket Activity');
            ?>&nbsp;<i class="help-tip icon-question-sign" href="#ticket_activity"></i></h2>
    </div>
</div>
<div class="clear"></div>
<!-- Create a graph and fetch some data to create pretty dashboard -->
<div style="position:relative">
    <div id="line-chart-here" style="height:300px"></div>
    <div style="position:absolute;right:0;top:0" id="line-chart-legend"></div>
</div>

<hr/>
<h2><?php echo __('Statistics'); ?>&nbsp;<i class="help-tip icon-question-sign" href="#statistics"></i></h2>
<p><?php echo __('Statistics of tickets organized by department, help topic, and agent.');?></p>
<p><b><?php echo __('Range: '); ?></b>
  <?php
  $range = array();
  foreach ($report->getDateRange() as $date)
  {
    $date = str_ireplace('FROM_UNIXTIME(', '',$date);
    $date = str_ireplace(')', '',$date);
    $date = new DateTime('@'.$date);
    $date->setTimeZone(new DateTimeZone($cfg->getTimezone()));
    $timezone = $date->format('e');
    $range[] = $date->format('F j, Y');
  }
  echo __($range[0] . ' - ' . $range[1] .  ' (' . Format::timezone($timezone) . ')');
?>

<ul class="clean tabs">
<?php
$first = true;
$groups = $report->enumTabularGroups();
foreach ($groups as $g=>$desc) { ?>
    <li class="<?php echo $first ? 'active' : ''; ?>"><a href="#<?php echo Format::slugify($g); ?>"
        ><?php echo Format::htmlchars($desc); ?></a></li>
<?php
    $first = false;
} ?>
</ul>

<?php
$first = true;
foreach ($groups as $g=>$desc) {
    $data = $report->getTabularData($g); ?>
    <div class="tab_content <?php echo (!$first) ? 'hidden' : ''; ?>" id="<?php echo Format::slugify($g); ?>">
    <table class="dashboard-stats table"><tbody><tr>
<?php
    foreach ($data['columns'] as $j=>$c) {
      ?>
        <th <?php if ($j === 0) echo 'width="30%" class="flush-left"'; ?>><?php echo Format::htmlchars($c);
        switch ($c) {
          case 'Opened':
            ?>
              <i class="help-tip icon-question-sign" href="#opened"></i>
            <?php
            break;
          case 'Assigned':
            ?>
              <i class="help-tip icon-question-sign" href="#assigned"></i>
            <?php
            break;
            case 'Overdue':
              ?>
                <i class="help-tip icon-question-sign" href="#overdue"></i>
              <?php
              break;
            case 'Closed':
              ?>
                <i class="help-tip icon-question-sign" href="#closed"></i>
              <?php
              break;
            case 'Reopened':
              ?>
                <i class="help-tip icon-question-sign" href="#reopened"></i>
              <?php
              break;
            case 'Deleted':
              ?>
                <i class="help-tip icon-question-sign" href="#deleted"></i>
              <?php
              break;
            case 'Service Time':
              ?>
                <i class="help-tip icon-question-sign" href="#service_time"></i>
              <?php
              break;
            case 'Response Time':
              ?>
                <i class="help-tip icon-question-sign" href="#response_time"></i>
              <?php
              break;
        }
        ?></th>
<?php
    } ?>
    </tr></tbody>
    <tbody>
<?php
    foreach ($data['data'] as $i=>$row) {
        echo '<tr>';
        foreach ($row as $j=>$td) {
            if ($j === 0) { ?>
                <th class="flush-left"><?php echo Format::htmlchars($td); ?></th>
<?php       }
            else { ?>
                <td><?php echo Format::htmlchars($td);
                if ($td) { // TODO Add head map
                }
                echo '</td>';
            }
        }
        echo '</tr>';
    }
    $first = false; ?>
    </tbody></table>
    <div style="margin-top: 5px"><button type="submit" class="link button" name="export"
        value="<?php echo Format::htmlchars($g); ?>">
        <i class="icon-download"></i>
        <?php echo __('Export'); ?></a></div>
    </div>
<?php
}
?>
</form>
<script>
    $.drawPlots(<?php echo JsonDataEncoder::encode($report->getPlotData()); ?>);
    // Set Selected Period For Dashboard Stats and Export
    <?php if ($report && $report->end) { ?>
        $("div#basic_search select option").each(function(){
            // Remove default selection
            if ($(this)[0].selected)
                $(this).removeAttr('selected');
            // Set the selected period by the option's value (periods equal
            // option's values)
            if ($(this).val() == "<?php echo $report->end; ?>")
                $(this).attr("selected","selected");
        });
    <?php } ?>
</script>
