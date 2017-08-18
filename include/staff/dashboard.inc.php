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
        <div class="card-box">
            <h4 class="text-dark  header-title m-t-0 m-b-10">Backlog</h4>
        
            <div class="widget-chart text-center">
                <div id="backlog" style="height: 275px;"></div>
        
            </div>
        </div>
    </div>
    
    <div class="col-lg-3">
        <div class="card-box">
            <h4 class="text-dark  header-title m-t-0 m-b-10">Tickets by Status</h4>
        
            <div class="widget-chart text-center">
                <div id="ticketsbystatus" style="height: 275px;"></div>
        
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card-box">
            <h4 class="text-dark  header-title m-t-0 m-b-10">My Tickets by Status</h4>
        
            <div class="widget-chart text-center">
                <div id="myticketsbystatus" style="height: 275px;"></div>
        
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card-box">
            <h4 class="text-dark  header-title m-t-0 m-b-10">Suggestions by Status</h4>
        
            <div class="widget-chart text-center">
                <div id="suggestionsbystatus" style="height: 275px;"></div>
        
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
        <div class="card-box">
            <h4 class="text-dark  header-title m-t-0 m-b-10">Tickets (Open|Closed|Backlog)</h4>
        
            <div class="widget-chart text-center">
                <div id="ticketsopenclosedbacklog" style="height: 275px;"></div>
        
            </div>
            <div class="p-b-50">
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-box">
            <h4 class="text-dark  header-title m-t-0 m-b-10">Top 10 Topics</h4>
        
            <div class="widget-chart text-center">
                <div id="toptentopic" style="height: 275px;"></div>
        
            </div>
            <div class="p-b-50">
            </div>
        </div>
    </div>
    
</div>
<script>
Morris.Bar({
  element: 'ticketsbystatus',
  data: [
    { y: 'Unassigned', a: <?php echo $UnassignedTickets; ?>},
    { y: 'Assigned', a: <?php echo $AssignedTickets; ?>},
    { y: 'Held', a: <?php echo $HeldTickets; ?>},
    { y: 'Agent Reply', a: <?php echo $ReplyTickets; ?>},
    { y: 'Submitter Reply', a: <?php echo $TheirReplyTickets; ?>},
    { y: 'Quote', a: <?php echo $AwaitingQuoteTickets; ?>},
    { y: 'Implementation', a: <?php echo $ImplementationTickets; ?>},
  ],
  xkey: 'y',
  ykeys: ['a'],
  labels: [''],
  hideHover: 'auto',
  barRatio: 0.4,
  resize: true,
  xLabelAngle: 45,
  barColors: function (row, series, type) {
    
       switch (row.x){
            case 0:
                return "#C0392B";
                break;
            case 1:
                return "#039cfd";
                break;
            case 2:
                return "#f1b53d";
                break;
            case 3:
                return "#ef5350";
                break;
            case 4:
                return "#52bb56";
                break;
            case 5:
               return "#9B59B6";
                break;
            case 6:
                return "#7266ba";
                break;
        }
    }
  }).on('click', function(i){
   
  switch (i) {
    case 0:
        window.location.href = "tickets.php?queue=3&p=1&l=&s=1";
        break;
    case 1:
        window.location.href = "tickets.php?queue=3&p=1&l=&s=11";
        break;
    case 2:
        window.location.href = "tickets.php?queue=3&p=1&l=&s=8";
        break;
    case 3:
        window.location.href = "tickets.php?queue=3&p=1&l=&s=7";
        break;
    case 4:
        window.location.href = "tickets.php?queue=3&p=1&l=&s=6";
        break;
    case 5:
        window.location.href = "tickets.php?queue=3&p=1&l=&s=10";
        break;
    case 6:
        window.location.href = "tickets.php?queue=3&p=1&l=&s=9";
        break;
  }    
});

Morris.Bar({
  element: 'myticketsbystatus',
  data: [
    { y: 'Assigned', a: <?php echo $MyAssignedTickets; ?>},
    { y: 'Held', a: <?php echo $MyHeldTickets; ?>},
    { y: 'Agent Reply', a: <?php echo $MyReplyTickets; ?>},
    { y: 'Submitter Reply', a: <?php echo $MyTheirReplyTickets; ?>},
    { y: 'Quote', a: <?php echo $MyAwaitingQuoteTickets; ?>},
    { y: 'Implementation', a: <?php echo $MyImplementationTickets; ?>},
  ],
  xkey: 'y',
  ykeys: ['a'],
  labels: [''],
  hideHover: 'auto',
   resize: true,
  barRatio: 0.4,
  xLabelAngle: 45,
  barColors: function (row, series, type) {
    
       switch (row.x){
            case 0:
                return "#039cfd";
                break;
            case 1:
                return "#f1b53d";
                break;
            case 2:
                return "#ef5350";
                break;
            case 3:
                return "#52bb56";
                break;
            case 4:
               return "#9B59B6";
                break;
            case 5:
                return "#7266ba";
                break;
        }
    }
  }).on('click', function(i){
   
  switch (i) {
    case 0:
        window.location.href = "tickets.php?queue=31&p=1&l=&s=1";
        break;
    case 1:
        window.location.href = "tickets.php?queue=31&p=1&l=&s=11";
        break;
    case 2:
        window.location.href = "tickets.php?queue=31&p=1&l=&s=8";
        break;
    case 3:
        window.location.href = "tickets.php?queue=31&p=1&l=&s=7";
        break;
    case 4:
        window.location.href = "tickets.php?queue=31&p=1&l=&s=6";
        break;
    case 5:
        window.location.href = "tickets.php?queue=31&p=1&l=&s=10";
        break;
    case 6:
        window.location.href = "tickets.php?queue=31&p=1&l=&s=9";
        break;
  }    
});

Morris.Bar({
  element: 'backlog',
  data: [
    { y: 'CAN', a: <?php echo $BacklogTickets["CAN"]; ?>},
    { y: 'EXT', a: <?php echo $BacklogTickets["EXT"]; ?>},
    { y: 'IND', a: <?php echo $BacklogTickets["IND"]; ?>},
    { y: 'MEX', a: <?php echo $BacklogTickets["MEX"]; ?>},
    { y: 'NTC', a: <?php echo $BacklogTickets["NTC"]; ?>},
    { y: 'OH', a: <?php echo $BacklogTickets["OH"]; ?>},
    { y: 'SS', a: <?php echo $BacklogTickets["SS"]; ?>},
    { y: 'TNN1', a: <?php echo $BacklogTickets["TNN1"]; ?>},
    { y: 'TNN2', a: <?php echo $BacklogTickets["TNN2"]; ?>},
    { y: 'TNS', a: <?php echo $BacklogTickets["TNS"]; ?>},
  ],
  xkey: 'y',
  ykeys: ['a'],
  labels: [''],
  hideHover: 'auto',
  barRatio: 0.4,
   resize: true,
  xLabelAngle: 60,
  barColors: function (row, series, type) {
    
       switch (row.x){
            case 0:
                return "#f1b53d";
                break;
            case 1:
                return "#795548";
                break;
            case 2:
                return "#039cfd";
                break;
            case 3:
                return "#7266ba";
                break;
            case 4:
                return "#E67E22";
                break;
            case 5:
               return "#9B59B6";
                break;
            case 6:
                return "#546E7A";
                break;
            case 7:
                return "#1ABC9C";
                break;
            case 8:
                return "#C0392B";
                break;
            case 9:
                return "#27AE60";
                break;        
        }
    }
  }).on('click', function(i){
   
  switch (i) {
    case 0:
        window.location.href = "tickets.php?queue=3&p=1&l=2&s=";
        break;
    case 1:
        window.location.href = "tickets.php?queue=3&p=1&l=10&s=";
        break;
    case 2:
        window.location.href = "tickets.php?queue=3&p=1&l=8&s=";
        break;
    case 3:
        window.location.href = "tickets.php?queue=3&p=1&l=6&s=";
        break;
    case 4:
        window.location.href = "tickets.php?queue=3&p=1&l=5&s=";
        break;
    case 5:
        window.location.href = "tickets.php?queue=3&p=1&l=9&s=";
        break;
    case 6:
        window.location.href = "tickets.php?queue=3&p=1&l=11&s=";
        break;
    case 7:
        window.location.href = "tickets.php?queue=3&p=1&l=4&s=";
        break;
    case 8:
        window.location.href = "tickets.php?queue=3&p=1&l=3&s=";
        break;
    case 9:
        window.location.href = "tickets.php?queue=3&p=1&l=7&s=";
        break;        
  }    
});

Morris.Bar({
  element: 'suggestionsbystatus',
  data: [
    { y: 'Assigned', a: <?php echo $SuggestionAssignedTickets; ?>},
    { y: 'Quote', a: <?php echo $SuggestionImplementationTickets; ?>},
    { y: 'Implementation', a: <?php echo $SuggestionAwaitingQuoteTickets; ?>},
  ],
  xkey: 'y',
  ykeys: ['a'],
  labels: [''],
  hideHover: 'auto',
  barRatio: 0.4,
  resize: true,
  xLabelAngle: 45,
  barColors: function (row, series, type) {
    
       switch (row.x){
            case 0:
                return "#039cfd";
                break;
            case 1:
                return "#9B59B6";
                break;
            case 2:
                return "#7266ba";
            
        }
    }
  }).on('click', function(i){
   
  switch (i) {
    case 0:
        window.location.href = "tickets.php?queue=17&p=1&l=&s=11";
        break;
    case 1:
        window.location.href = "tickets.php?queue=17&p=1&l=&s=10";
        break;
    case 2:
        window.location.href = "tickets.php?queue=17&p=1&l=&s=9";
        break;
   
  }    
});

Morris.Area({
  element: 'ticketsopenclosedbacklog',
  data: [
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

        foreach ($results as $result) {
            echo "{ y: '".$result['WEEK']."', a: ".$result['BACKLOG'].", b: ".$result['OPEN'].", c:".$result['CLOSED']." },";
        }
        
    ?> 
        ],
  xkey: 'y',
  ykeys: ['c','a','b'],
  xLabels: 'week',
  xLabelAngle: 45,
  labels: ['Backlog','Opened', 'Closed' ],
  fillOpacity: 0.7,
  hideHover: 'auto',
  behaveLikeLine: true,
  resize: true,
  pointStrokeColors: ['black'],
  lineColors:['#d9221d','#e2c22a','#6c92ea']
});

Morris.Bar({
  element: 'toptentopic',
  data: [
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
                        WHEN 43 THEN 'Connectivity/Downtime/Vend as TOPICor' 
                        WHEN 41 THEN 'Connectivity/Maintenance'
                                                            
                        WHEN 44 THEN 'Connectivity/Maintenance/Internal' 
                        WHEN 45 THEN 'Connectivity/Maintenance/Vend as TOPICor' 
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
                ORDER BY COUNT DESC limit 10)a
                order by count";
        $tresults = db_query($sql1); 

        foreach ($tresults as $tresult) {
            echo "{ y: '".$tresult['TOPIC']."', a: ".$tresult['COUNT']."},";
        }
    ?> 
        ],
  xkey: 'y',
  ykeys: ['a'],
  xLabelAngle: 45,
  labels: [''],
  fillOpacity: 0.7,
  hideHover: 'auto',
  resize: true,
   
});
$('svg').height(700);
</script>



