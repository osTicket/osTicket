<script type="text/javascript" src="js/raphael-min.js"></script>
<script type="text/javascript" src="js/g.raphael.js"></script>
<script type="text/javascript" src="js/g.line-min.js"></script>
<script type="text/javascript" src="js/g.dot-min.js"></script>
<script type="text/javascript" src="js/dashboard.inc.js"></script>

<!--<link rel="stylesheet" type="text/css" href="css/bootstrap.css"/>-->
<link rel="stylesheet" type="text/css" href="css/dashboard.css"/>

<div id="basic_search">
    <div style="min-height:25px;">
        <!--<p><?php //echo __('Select the starting time and period for the system activity graph');?></p>-->
        <form class="form-inline" id="timeframe-form">
            <label>
                <?php echo __( 'Report timeframe'); ?>:
                <input type="text" class="dp input-medium search-query" name="start" placeholder="<?php echo __('Last month');?>" />
            </label>
            <label>
                <?php echo __( 'period');?>:
                <select name="period">
                    <option value="now" selected="selected">
                        <?php echo __( 'Up to today');?>
                    </option>
                    <option value="+7 days">
                        <?php echo __( 'One Week');?>
                    </option>
                    <option value="+14 days">
                        <?php echo __( 'Two Weeks');?>
                    </option>
                    <option value="+1 month">
                        <?php echo __( 'One Month');?>
                    </option>
                    <option value="+3 months">
                        <?php echo __( 'One Quarter');?>
                    </option>
                </select>
            </label>
            <button class="green button action-button muted" type="submit">
                <?php echo __( 'Refresh');?>
            </button>
            <i class="help-tip icon-question-sign" href="#report_timeframe"></i>
        </form>
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
<ul id="tabular-navigation" class="tabs">
</ul>
<!--<div id="table-here"></div>-->

<?php
