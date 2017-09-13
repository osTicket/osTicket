<script src="<?php echo ROOT_PATH; ?>scp/js/morris.min.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/raphael-min.js"></script>

<div class="subnav">

    <div class="float-left subnavtitle">
                          
    <?php echo __('IT Dashboard');?>                        
    
    </div>
    <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
   &nbsp;
      </div>   
   <div class="clearfix"></div> 
</div> 




<div class="row">
                            <div class="col-lg-6 col-xl-3">
                                <div class="widget-bg-color-icon card-box">
                                    <div class="bg-icon bg-icon-danger pull-left">
                                        <i class="mdi mdi-ticket-confirmation text-danger"></i>
                                    </div>
                                    <div class="text-right">
                                       <a href="tickets.php?queue=241&p=1"><h3 class="text-dark"><b class="counter"><?php echo $BacklogTotal;?></b></h3></a>
                                        <p class="text-muted mb-0">Backlog</p>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                            
                            <div class="col-lg-6 col-xl-3">
                                <div class="widget-bg-color-icon card-box">
                                    <div class="bg-icon bg-icon-primary pull-left">
                                        <i class="mdi mdi-ticket-account text-success"></i>
                                    </div>
                                    <div class="text-right">
                                        <a href="tickets.php?queue=3&p=1"><h3 class="text-dark"><b class="counter"><?php echo $OpenTickets;?></b></h3></a>
                                        <p class="text-muted mb-0">Open Tickets</p>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-xl-3">
                                <div class="widget-bg-color-icon card-box">
                                    <div class="bg-icon bg-icon-warning pull-left">
                                        <i class="mdi mdi-ticket text-success"></i>
                                    </div>
                                    <div class="text-right">
                                        <a href="tickets.php?queue=31&p=1"><h3 class="text-dark"><b class="counter"><?php echo $MyOpenTickets;?></b></h3></a>
                                        <p class="text-muted mb-0">My Open Tickets</p>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-xl-3">
                                <div class="widget-bg-color-icon card-box">
                                    <div class="bg-icon bg-icon-success pull-left">
                                        <i class="ti-light-bulb text-success"></i>
                                    </div>
                                    <div class="text-right">
                                        <a href="tickets.php?queue=17&p=1"><h3 class="text-dark"><b class="counter"><?php echo $SuggestionAssignedTicket + $SuggestionImplementationTicket + $SuggestionAwaitingQuoteTickets ?></b></h3></a>
                                        <p class="text-muted mb-0">Open Suggestions</p>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
</div>

<div class="row">
    <div class="col-lg-3">
        <div class="portlet"><!-- /primary heading -->
            <div class="portlet-heading">
                <h3 class="portlet-title text-dark">
                    Backlog
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion1" href="#portlet1"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet1" class="panel-collapse collapse show">
                <div class="portlet-body">
                    <div id="toptentopic">
                        <div id="backlog-chart-container" class="flot-chart" style="height: 260px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3">
        <div class="portlet"><!-- /primary heading -->
            <div class="portlet-heading">
                <h3 class="portlet-title text-dark">
                    TICKETS (By Status)
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion2" href="#portlet2"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet2" class="panel-collapse collapse show">
                <div class="portlet-body">
                    <div id="toptentopic">
                        <div id="ticketsbystatus-chart-container" class="flot-chart" style="height: 260px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="portlet"><!-- /primary heading -->
            <div class="portlet-heading">
                <h3 class="portlet-title text-dark">
                    MY TICKETS (By Status)
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion2" href="#portlet4"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet4" class="panel-collapse collapse show">
                <div class="portlet-body">
                    <div id="toptentopic">
                        <div id="myticketsbystatus-chart-container" class="flot-chart" style="height: 260px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="portlet"><!-- /primary heading -->
            <div class="portlet-heading">
                <h3 class="portlet-title text-dark">
                    SUGGESTIONS (By Status)
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion2" href="#portlet5"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet5" class="panel-collapse collapse show">
                <div class="portlet-body">
                    <div id="toptentopic">
                        <div id="suggestionssbystatus-chart-container" class="flot-chart" style="height: 260px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
</div>
<div class="row">
    
    <div class="col-lg-6 col-xl-3">
        <div class="widget-bg-color-icon card-box">
            <div class="bg-icon bg-icon-primary pull-left">
                <i class="ti-info-alt text-success"></i>
            </div>
            <div class="text-right">
                <a href="tickets.php?queue=14&p=1"><h3 class="text-dark"><b class="counter"><?php echo $OpenIssuesTickets;?></b></h3></a>
                <p class="text-muted mb-0">Open Issues</p>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
     <div class="col-lg-6 col-xl-3">
        <div class="widget-bg-color-icon card-box">
            <div class="bg-icon bg-icon-danger pull-left">
                <i class="ti-info-alt text-danger"></i>
            </div>
            <div class="text-right">
                <a href="tickets.php?queue=33&p=1"><h3 class="text-dark"><b class="counter"><?php echo $MyOpenIssuesTickets;?></b></h3></a>
                <p class="text-muted mb-0">My Open Issues</p>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
    
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="portlet"><!-- /primary heading -->
            <div class="portlet-heading">
                <h3 class="portlet-title text-dark">
                    TICKETS (OPEN|CLOSED|BACKLOG)
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion1" href="#portlet6"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet6" class="panel-collapse collapse show">
                <div class="portlet-body">
                    <div id="combine-chart">
                        <div id="combine-chart-container" class="flot-chart" style="height: 320px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="portlet"><!-- /primary heading -->
            <div class="portlet-heading">
                <h3 class="portlet-title text-dark">
                    TICKETS (TOP 10)
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion1" href="#portlet7"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet7" class="panel-collapse collapse show">
                <div class="portlet-body">
                    <div id="toptentopic">
                        <div id="toptentopic-chart-container" class="flot-chart" style="height: 320px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                       
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.flot.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.flot.tooltip.js"></script> 
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.flot.time.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.flot.tooltip.min.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.flot.resize.min.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.flot.pie.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.flot.categories.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.flot.selection.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.flot.stack.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.flot.crosshair.js"></script>

<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.flot.tickrotor.js"></script>

<script>

$('svg').height(700);

! function($) {
	"use strict";

	var FlotChart = function() {
		this.$body = $("body")
		this.$realData = []
	};

    
    //creates Combine Chart
	FlotChart.prototype.createCombineGraph = function(selector, ticks, labels, datas) {

		var data = [{
			label : labels[0],
			data : datas[0],
			lines : {
				show : true,
				fill : true
			},
			points : {
				show : true
			}
		}, {
			label : labels[1],
			data : datas[1],
			lines : {
				show : true
			},
			points : {
				show : true
			}
		}, {
			label : labels[2],
			data : datas[2],
			bars : {
				show : true,
                align: "center"   
			}
		}];
		var options = {
			series : {
				shadowSize : 0
			},
			grid : {
				hoverable : true,
				clickable : true,
				tickColor : "#f9f9f9",
				borderWidth : 1,
				borderColor : "#eeeeee"
			},
			colors : ['#d9221d', '#e2c22a', '#6c92ea'],
			tooltip: {
                 show: true,
                 cssClass: "flot",
                 content: "%x | %s | %y",
                
                
              },
			legend : {
				position : "ne",
				margin : [0, -24],
				noColumns : 0,
				labelBoxBorderColor : null,
				labelFormatter : function(label, series) {
					// just add some space to labes
					return '' + label + '&nbsp;&nbsp;';
				},
				width : 30,
				height : 2
			},
			yaxis : {
				tickColor : '#f5f5f5',
				font : {
					color : '#bdbdbd'
				}
			},
			xaxis : {
				ticks: ticks,
				tickColor : '#f5f5f5',
				font : {
                    color : '#868e96',
                    				},
                
                rotateTicks: 135
			}
		};

		$.plot($(selector), data, options);
	},

	//initializing various charts and components
	FlotChart.prototype.init = function() {
		
         <?php
        $sql="select CALENDARWEEK as WEEK, 
                max(case when Status = 'OPEN' then VALUE else 0 end)as OPEN, 
                max(case when Status = 'CLOSED' then VALUE else 0 end) as CLOSED,
                max(case when Status = 'BACKLOG' then VALUE else 0 end) as BACKLOG
                from ( 

                Select * from(                        
                                SELECT   COUNT(created) AS VALUE, 'OPEN' AS Status, FROM_DAYS(TO_DAYS(created) - MOD(TO_DAYS(created) 
                                                         - 2, 7)) AS CALENDARWEEK
                                FROM         ost_ticket
                                WHERE     FROM_DAYS(TO_DAYS(created) - MOD(TO_DAYS(created) - 2, 7)) BETWEEN DATE_SUB(CURRENT_DATE (), 
                                                         INTERVAL 12 WEEK) AND CURRENT_DATE () -1
                                AND ost_ticket.topic_id <> 12 and topic_id <> 14
                                GROUP BY FROM_DAYS(TO_DAYS(created) - MOD(TO_DAYS(created) - 2, 7)) 
                                
                                Union all
                                
                                SELECT   COUNT(closed) AS VALUE, 'CLOSED' AS Status, FROM_DAYS(TO_DAYS(closed) - MOD(TO_DAYS(closed) 
                                                         - 2, 7)) AS CALENDARWEEK
                                FROM         ost_ticket
                                WHERE     FROM_DAYS(TO_DAYS(closed) - MOD(TO_DAYS(closed) - 2, 7)) BETWEEN DATE_SUB(CURRENT_DATE (), 
                                                         INTERVAL 12 WEEK) AND CURRENT_DATE () -1
                                AND ost_ticket.topic_id <> 12 and topic_id <> 14
                                GROUP BY FROM_DAYS(TO_DAYS(closed) - MOD(TO_DAYS(closed) - 2, 7))) data
                                
                                UNION all 
                                select sum(CAN)+sum(EXT)+sum(IND)+sum(MEX)+sum(NTC)+sum(OH)+sum(TNN1)+sum(SS)+sum(TNN2)+sum(TNS) as VALUE, 'BACKLOG' AS Status,  
                STR_TO_DATE(CONCAT(YEAR,WEEK,' Monday'), '%X%V %W') as CALENDARWEEK from osticket_sup.ost_backlog 

                where STR_TO_DATE(CONCAT(YEAR,WEEK,' Monday'), '%X%V %W')

                BETWEEN DATE_SUB(CURRENT_DATE (), INTERVAL 12 WEEK) AND CURRENT_DATE () -1
                group by STR_TO_DATE(CONCAT(YEAR,WEEK,' Monday'), '%X%V %W')
                                
                Order by CALENDARWEEK, STATUS)dt

                group by CALENDARWEEK;";
        $results = db_query($sql); 
                
        
    ?> 
        
		//Combine graph data
		var Backlog = [
        <?php
        $r=0;
        foreach ($results as $result) {
            echo "[".$r.",\"".$result['BACKLOG']."\"],";
             $r++;
        }
        
        ?>
        ];
		var Open = [
        <?php
        $r=0;
        foreach ($results as $result) {
            echo "[".$r.",\"".$result['OPEN']."\"],";
             $r++;
        }
        
        ?>
        ];
		var Closed = [
        <?php
        $r=0;
        foreach ($results as $result) {
            echo "[".$r.",\"".$result['CLOSED']."\"],";
             $r++;
        }
        
        ?>
        ];
		var Weeks = [
        
        <?php
        $r=0;
        foreach ($results as $result) {
            echo "[".$r.",\"".$result['WEEK']."\"],";
             $r++;
        }
        
        ?>
        
        ];
		var combinelabels = ["Backlog", "Open", "Closed"];
		var combinedatas = [Backlog, Open, Closed];

		this.createCombineGraph("#combine-chart #combine-chart-container", Weeks, combinelabels, combinedatas);
	},

	//init flotchart
	$.FlotChart = new FlotChart, $.FlotChart.Constructor =
	FlotChart

}(window.jQuery),

//initializing flotchart
function($) {
	"use strict";
	$.FlotChart.init()
}(window.jQuery);



$(function() {

		var data = [
        
           <?php
        $sql1="select * from (SELECT COUNT(TOPIC) AS COUNT, TOPIC
                FROM (SELECT ost_ticket.number AS Ticket, 
                    CASE ost_help_topic.topic_id 
                        WHEN 35 THEN 'Associates' 
                        WHEN 29 THEN 'Associates/Add' 
                        WHEN 36 THEN 'Associates/Change' 
                        WHEN 31 THEN 'Associates/Termination' 
                        WHEN 27 THEN 'Connectivity' 
                        WHEN 37 THEN 'Connectivity/Add'
                                                            
                        WHEN 39 THEN 'Connectivity/Change' 
                        WHEN 40 THEN 'Connectivity/Downtime' 
                        WHEN 42 THEN 'Connectivity/Downtime/Internal' 
                        WHEN 43 THEN 'Connectivity/Downtime/Vendor' 
                        WHEN 41 THEN 'Connectivity/Maintenance'
                                                            
                        WHEN 44 THEN 'Connectivity/Maintenance/Internal' 
                        WHEN 45 THEN 'Connectivity/Maintenance/Vendor' 
                        WHEN 85 THEN 'Connectivity/VPN' 
                        WHEN 81 THEN 'Connectivity/WSA' 
                        WHEN 21 THEN 'Email' 
                        WHEN 46 THEN 'Email/Add'
                                                            
                        WHEN 47 THEN 'Email/Change' 
                        WHEN 48 THEN 'Email/Downtime' 
                        WHEN 83 THEN 'Email/Outlook' 
                        WHEN 84 THEN 'Email/OWA' 
                        
                        WHEN 30 THEN 'Facility' 
                        WHEN 50 THEN 'Facility/Downtime' 
                        WHEN 86 THEN 'Facility/Door System' 
                        WHEN 51 THEN 'Facility/Downtime/Power Outtage' 
                        WHEN 49 THEN 'Facility/Organization (5S)' 
                        
                        WHEN 22 THEN 'File and Print' 
                        WHEN 52 THEN 'File and Print/Add' 
                        WHEN 57 THEN 'File and Print/Change' 
                        WHEN 58 THEN 'File and Print/Configuration'
                                                            
                        WHEN 53 THEN 'File and Print/Permissions' 
                        WHEN 54 THEN 'File and Print/Permissions/Add' 
                        WHEN 55 THEN 'File and Print/Permissions/Change' 
                        WHEN 56 THEN 'ile and Print/Permissions/Remove' 
                        WHEN 32 THEN 'Hardware' 
                        WHEN 59
                                                            THEN 'Hardware/Add' 
                        WHEN 60 THEN 'Hardware/Change' 
                        WHEN 61 THEN 'Hardware/Configuration' 
                        WHEN 62 THEN 'Hardware/Downtime' 
                        WHEN 63 THEN 'Hardware/Maintenance' 
                        WHEN 26 THEN 'Skype | Phones' 
                        WHEN 64 THEN 'Skype | Phones/Add'
                                                            
                        WHEN 65 THEN 'Skype | Phones/Change' 
                        WHEN 66 THEN 'Skype | Phones/Configuration' 
                        WHEN 68 THEN 'Skype | Phones/Downtime' 
                        WHEN 67 THEN 'Skype | Phones/Remove' 
                        WHEN 28 THEN 'Software' 
                        WHEN 34 THEN 'Software/Engineering | Design'
                                                            
                        WHEN 72 THEN 'Software/Engineering | Design/Install' 
                        WHEN 74 THEN 'Software/Engineering | Design/Remove' 
                        WHEN 73 THEN 'Software/Engineering | Design/Update' 
                        WHEN 69 THEN 'Software/Install' 
                        WHEN 33 THEN 'Software/Office'
                                                            
                        WHEN 75 THEN 'Software/Office/Install' 
                        WHEN 77 THEN 'Software/Office/Remove' 
                        WHEN 76 THEN 'Software/Office/Update' 
                        WHEN 71 THEN 'Software/Remove' 
                        WHEN 82 THEN 'Software/QuoteLog' 
                        WHEN 13 THEN 'Software/ShopEdge'
                                                            
                        WHEN 78 THEN 'Software/ShopEdge/Downtime' 
                        WHEN 15 THEN 'Software/ShopEdge/EDI' 
                        WHEN 17 THEN 'Software/ShopEdge/Performance' 
                        WHEN 18 THEN 'Software/ShopEdge/Printing' 
                        WHEN 19 THEN 'Software/ShopEdge/Reports'
                                                            
                        WHEN 16 THEN 'Software/ShopEdge/Security' 
                        WHEN 14 THEN 'Software/ShopEdge/Suggestion' 
                        WHEN 80 THEN 'Software/Suggestions System' 
                        WHEN 12 THEN 'Open Issue' 
                    END AS TOPIC
                    FROM      ost_ticket LEFT JOIN
                                      ost_help_topic ON ost_help_topic.topic_id = ost_ticket.topic_id
                    WHERE   ost_ticket.status_id <> 3 AND ost_ticket.status_id <> 2 AND ost_ticket.status_id <> 12 AND ost_ticket.topic_id <> 14 AND ost_ticket.topic_id <> 12) AS a

                WHERE  TOPIC IS NOT NULL
                GROUP BY TOPIC
                limit 10)a
                order by count desc";
        $tresults = db_query($sql1); 

        foreach ($tresults as $tresult) {
            echo "[\"".$tresult['TOPIC']."\", ".$tresult['COUNT']."],";
        }
    ?> 
                
        ];

		$.plot("#toptentopic-chart-container", [ data ], {
			series: {
				bars: {
					show: true,
					barWidth: 0.6,
					align: "center"
				}
			},
            grid : {
				hoverable : true,
				clickable : true,
				tickColor : "#f9f9f9",
				borderWidth : 1,
				borderColor : "#eeeeee",
                labelMargin: 18
			},
            colors : ['#6c92ea'],
			 tooltip: {
                 show: true,
                 cssClass: "flot",
                 content: "%x: %y",
                
                
              },
            yaxis : {
				tickColor : '#f5f5f5',
				font : {
					color : '#bdbdbd'
				}
			},
			xaxis: {
				mode: "categories",
				tickLength: 0,
                tickColor : '#f5f5f5',
				font : {
                    color : '#868e96',
                    				},
                
                rotateTicks: 135
			}
		});
	
	});

//Backlog
$(function() {

		var data = [
        ["CAN", <?php echo $BacklogTickets["CAN"]; ?>], 
        ["EXT", <?php echo $BacklogTickets["EXT"]; ?>], 
        ["IND", <?php echo $BacklogTickets["IND"]; ?>], 
        ["MEX", <?php echo $BacklogTickets["MEX"]; ?>], 
        ["NTC", <?php echo $BacklogTickets["NTC"]; ?>], 
        ["OH", <?php echo $BacklogTickets["OH"]; ?>], 
        ["SS", <?php echo $BacklogTickets["SS"]; ?>], 
        ["TNN1", <?php echo $BacklogTickets["TNN1"]; ?>], 
        ["TNN2", <?php echo $BacklogTickets["TNN2"]; ?>], 
        ["TNS", <?php echo $BacklogTickets["TNS"]; ?>]
       
        ];

		$.plot("#backlog-chart-container", [ data ], {
			series: {
				bars: {
					show: true,
					barWidth: 0.6,
					align: "center"
				}
			},
            grid : {
				hoverable : true,
				clickable : true,
				tickColor : "#f9f9f9",
				borderWidth : 1,
				borderColor : "#eeeeee",
                labelMargin: 10
			},
            colors : ['#d9221d'],
			 tooltip: {
                 show: true,
                 cssClass: "flot",
                 content: "%x: %y",
                
                
              },
            yaxis : {
				tickColor : '#f5f5f5',
				font : {
					color : '#bdbdbd'
				}
			},
			xaxis: {
				mode: "categories",
				tickLength: 0,
                tickColor : '#f5f5f5',
				font : {
                    color : '#868e96',
                    				},
                
                rotateTicks: 135
			}
		});
	
	});    

//Tickets By Status
$(function() {

		var data = [
        ["Unassigned", <?php echo $UnassignedTickets; ?>], 
        ["Assigned", <?php echo $AssignedTickets; ?>], 
        ["Held", <?php echo $HeldTickets; ?>], 
        ["Agent Reply", <?php echo $ReplyTickets; ?>], 
        ["Submitter Reply", <?php echo $TheirReplyTickets; ?>], 
        ["Quote", <?php echo $AwaitingQuoteTickets; ?>], 
        ["Implementation", <?php echo $ImplementationTickets; ?>]
               
        ];

		$.plot("#ticketsbystatus-chart-container", [ data ], {
			series: {
				bars: {
					show: true,
					barWidth: 0.6,
					align: "center"
				}
			},
            grid : {
				hoverable : true,
				clickable : true,
				tickColor : "#f9f9f9",
				borderWidth : 1,
				borderColor : "#eeeeee",
                labelMargin: 20
			},
            colors : ['#6c92ea'],
			 tooltip: {
                 show: true,
                 cssClass: "flot",
                 content: "%x: %y",
                
                
              },
            yaxis : {
				tickColor : '#f5f5f5',
				font : {
					color : '#bdbdbd'
				}
			},
			xaxis: {
				mode: "categories",
				tickLength: 0,
                tickColor : '#f5f5f5',
				font : {
                    color : '#868e96',
                    				},
                
                rotateTicks: 135
			}
		});
	
	});   

//My Tickets By Status
$(function() {

		var data = [
        ["Assigned", <?php echo $MyAssignedTickets; ?>], 
        ["Held", <?php echo $MyHeldTickets; ?>], 
        ["Agent Reply", <?php echo $MyReplyTickets; ?>], 
        ["Submitter Reply", <?php echo $MyTheirReplyTickets; ?>], 
        ["Quote", <?php echo $MyAwaitingQuoteTickets; ?>], 
        ["Implementation", <?php echo $MyImplementationTickets; ?>]
               
        ];

		$.plot("#myticketsbystatus-chart-container", [ data ], {
			series: {
				bars: {
					show: true,
					barWidth: 0.6,
					align: "center"
				}
			},
            grid : {
				hoverable : true,
				clickable : true,
				tickColor : "#f9f9f9",
				borderWidth : 1,
				borderColor : "#eeeeee",
                labelMargin: 20
			},
            colors : ['#e2c22a'],
			 tooltip: {
                 show: true,
                 cssClass: "flot",
                 content: "%x: %y",
                
                
              },
            yaxis : {
				tickColor : '#f5f5f5',
				font : {
					color : '#bdbdbd'
				}
			},
			xaxis: {
				mode: "categories",
				tickLength: 0,
                tickColor : '#f5f5f5',
				font : {
                    color : '#868e96',
                    				},
                
                rotateTicks: 135
			}
		});
	
	});   
//Suggestions By Status
$(function() {

		var data = [
        ["Assigned", <?php echo $SuggestionAssignedTickets; ?>], 
        ["Quote", <?php echo $SuggestionImplementationTickets; ?>], 
        ["Implementation", <?php echo $SuggestionAwaitingQuoteTickets; ?>]
               
        ];

		$.plot("#suggestionssbystatus-chart-container", [ data ], {
			series: {
				bars: {
					show: true,
					barWidth: 0.6,
					align: "center"
				}
			},
            grid : {
				hoverable : true,
				clickable : true,
				tickColor : "#f9f9f9",
				borderWidth : 1,
				borderColor : "#eeeeee",
                labelMargin: 20
			},
            colors : ['#7DCC80'],
			 tooltip: {
                 show: true,
                 cssClass: "flot",
                 content: "%x: %y",
                
                
              },
            yaxis : {
				tickColor : '#f5f5f5',
				font : {
					color : '#bdbdbd'
				}
			},
			xaxis: {
				mode: "categories",
				tickLength: 0,
                tickColor : '#f5f5f5',
				font : {
                    color : '#868e96',
                    				},
                
                rotateTicks: 135
			}
		});
	
	});            
</script>



