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
                            <div class="col-lg-1 col-xl-3">
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
                            
                            <div class="col-lg-1 col-xl-3">
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
                            <div class="col-lg-1 col-xl-3">
                                <div class="widget-bg-color-icon card-box">
                                    <div class="bg-icon bg-icon-purple pull-left">
                                        <i class="mdi mdi-ticket-account text-success"></i>
                                    </div>
                                    <div class="text-right">
                                        <h3 class="text-dark"><b class="counter"><?php echo $averagedaysopen;?></b></h3>
                                        <p class="text-muted mb-0">Average Days Open</p>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                            <div class="col-lg-1 col-xl-3">
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
                            <div class="col-lg-1 col-xl-3">
                                <div class="widget-bg-color-icon card-box">
                                    <div class="bg-icon bg-icon-success pull-left">
                                        <i class="ti-light-bulb text-success"></i>
                                    </div>
                                    <div class="text-right">
                                        <a href="tickets.php?queue=17&p=1"><h3 class="text-dark"><b class="counter"><?php echo $SuggestionAssignedTicket + $SuggestionThridPartyTicketsTicket + $SuggestionAwaitingQuoteTickets ?></b></h3></a>
                                        <p class="text-muted mb-0">Open Suggestions</p>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                            <div class="col-lg-1 col-xl-3">
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
                         <div class="col-lg-1 col-xl-3">
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
                    <a data-toggle="collapse" data-parent="#accordion2" href="#portlet3"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet3" class="panel-collapse collapse show">
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
                    <a data-toggle="collapse" data-parent="#accordion2" href="#portlet4"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet4" class="panel-collapse collapse show">
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
    <div class="col-lg-12">
        <div class="portlet"><!-- /primary heading -->
            <div class="portlet-heading">
                <h3 class="portlet-title text-dark">
                    TICKETS (YEAR TO DATE)
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion1" href="#portlet5"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet5" class="panel-collapse collapse show">
                <div class="portlet-body">
                
                    <div class="table-responsive">
                        
                            <?php
                            $sql = "select distinct STATUS from
                                    (
                                    SELECT t.ticket_id, t.updated, o.name as LOCATION, s.name as STATUS FROM ost_ticket t 
                                    left join ost_user u on u.id = t.user_id 
                                    left join ost_organization o on o.id = u.org_id
                                    left join ost_ticket_status s on s.id = t.status_id
                                    where year(t.updated) = year(now()) and s.state = 'open' AND t.topic_id <> 14 AND t.topic_id <> 12
                                    ) a
                                    where LOCATION is not null order by STATUS";
                                    
                            $statuses = db_query($sql);   

                            $sql = "select distinct LOCATION, STATUS from
                                    (
                                    select STATUS, LOCATION, sum(COUNT) as COUNT from
                                    (
                                    select STATUS, LOCATION, count(ticket_id) as COUNT from
                                        (
                                        SELECT t.ticket_id, t.updated, o.name as LOCATION, s.name as STATUS FROM ost_ticket t 
                                        left join ost_user u on u.id = t.user_id 
                                        left join ost_organization o on o.id = u.org_id
                                        left join ost_ticket_status s on s.id = t.status_id
                                        where year(t.updated) = year(now()) and s.state = 'open' AND t.topic_id <> 14 AND t.topic_id <> 12
                                        ) a
                                        where LOCATION is not null
                                        group by STATUS, LOCATION 
                                        union all 
                                        SELECT s.name as STATUS, o.name as LOCATION , 0 as COUNT FROM ost_organization o
                                    join
                                    ost_ticket_status s on 1=1 order by STATUS, LOCATION
                                    )d
                                    group by STATUS, LOCATION ) a
                                    where LOCATION is not null order by LOCATION";
                                    
                            $locs = db_query($sql); 
                            
                            $sql = "SELECT distinct name as LOCATION FROM ost_organization order by LOCATION";
                                    
                            $rawlocs = db_query($sql);
                            
                            $sql = "select STATUS, LOCATION, sum(COUNT) as COUNT from
                                    (
                                    select STATUS, LOCATION, count(ticket_id) as COUNT from
                                        (
                                        SELECT t.ticket_id, t.updated, o.name as LOCATION, s.name as STATUS FROM ost_ticket t 
                                        left join ost_user u on u.id = t.user_id 
                                        left join ost_organization o on o.id = u.org_id
                                        left join ost_ticket_status s on s.id = t.status_id
                                        where year(t.updated) = year(now())and s.state = 'open' AND t.topic_id <> 14 AND t.topic_id <> 12
                                        ) a
                                        where LOCATION is not null
                                        group by STATUS, LOCATION 
                                        union all 
                                        SELECT s.name as STATUS, o.name as LOCATION , 0 as COUNT FROM ost_organization o
                                    join
                                    ost_ticket_status s on 1=1 order by STATUS, LOCATION
                                    )d
                                    group by STATUS, LOCATION ";
                            
                            $tbldatas = db_query($sql);
                            
                            $sql="select LOCATION, sum(COUNT) as COUNT from
                                    (
                                    select STATUS, LOCATION, count(ticket_id) as COUNT from
                                        (
                                        SELECT t.ticket_id, t.updated, o.name as LOCATION, s.name as STATUS FROM ost_ticket t 
                                        left join ost_user u on u.id = t.user_id 
                                        left join ost_organization o on o.id = u.org_id
                                        left join ost_ticket_status s on s.id = t.status_id
                                        where year(t.updated) = year(now()) and s.state = 'open' AND t.topic_id <> 14 AND t.topic_id <> 12
                                        ) a
                                        where LOCATION is not null
                                        group by STATUS, LOCATION 
                                        union all 
                                        SELECT s.name as STATUS, o.name as LOCATION , 0 as COUNT FROM ost_organization o
                                    join
                                    ost_ticket_status s on 1=1 order by STATUS, LOCATION
                                    )d
                                    group by LOCATION ";
                                    
                            $tbltotals = db_query($sql);

                            ?>
                            <table class="table table-hover table-condensed table-sm m-b-0"><thead>
                            <tr class="bg-graphred"><th>OPEN</th>
                            <?php
                            
                            foreach ($rawlocs as $loc) {

                                echo '<th>'.$loc["LOCATION"].'</th>'; 
                            }
                            ?>
                            <th>TOTAL</th></tr></thead>
                            <?php
                            
                            foreach ($statuses as $status) {
                                
                                $class = null;
                                switch ($status["STATUS"]){
                                    case 'Hold':
                                    $class = 'class="text-warning"';
                                    break;
                                    
                                }
                                
                                echo '<tr '.$class.'><td>'.$status["STATUS"].'</td>'; 
                                    foreach ($locs as $loc) {
                                     
                                       if ($status["STATUS"] == $loc["STATUS"]) {
                                           
                                            foreach ($tbldatas as $tbldata) {
                                                
                                                if ($status["STATUS"] == $tbldata["STATUS"] &&  $loc["LOCATION"] == $tbldata["LOCATION"]) {
                                                    
                                                if ($tbldata["COUNT"] != 0) $count = number_format($tbldata["COUNT"]);
                                                    echo '<td>'.$count.'</td>';
                                                    $total = $total + $count;
                                                    $count = null;
                                                }
                                            }
                                            
                                    }
                                
                                } echo '<td><strong><span class="text-danger">'.number_format($total).'</span></strong></td></tr>'; 
                                $total= null;
                            }   
                             ?>
                             <tr class="text-danger"><th>TOTAL</th>
                             <?php
                             $total = null;
                             foreach ($tbltotals as $tbltotal){
                                 $count = $tbltotal["COUNT"];
                                 echo '<td><strong><span class="text-danger">'.number_format($count).'</span></strong></td>';
                                 $total = $total + $count;
                                 $count = null;
                             }   
                             
                             echo '<td><strong><span class="text-danger">'.number_format($total).'</strong></span></td></tr>';
                            ?>
                            
                            <?php
                            $sql = "select distinct STATUS from
                                    (
                                    SELECT t.ticket_id, t.updated, o.name as LOCATION, s.name as STATUS FROM ost_ticket t 
                                    left join ost_user u on u.id = t.user_id 
                                    left join ost_organization o on o.id = u.org_id
                                    left join ost_ticket_status s on s.id = t.status_id
                                    where year(t.updated) = year(now()) and s.state = 'closed' AND t.topic_id <> 14 AND t.topic_id <> 12
                                    ) a
                                    where LOCATION is not null and status = 'Closed' or status = 'Auto-Closed'order by STATUS";
                                    
                            $statuses = db_query($sql);   

                            $sql = "select distinct LOCATION, STATUS from
                                    (
                                    select STATUS, LOCATION, sum(COUNT) as COUNT from
                                    (
                                    select STATUS, LOCATION, count(ticket_id) as COUNT from
                                        (
                                        SELECT t.ticket_id, t.updated, o.name as LOCATION, s.name as STATUS FROM ost_ticket t 
                                        left join ost_user u on u.id = t.user_id 
                                        left join ost_organization o on o.id = u.org_id
                                        left join ost_ticket_status s on s.id = t.status_id
                                        where year(t.updated) = year(now()) and s.state = 'closed' AND t.topic_id <> 14 AND t.topic_id <> 12
                                        ) a
                                        where LOCATION is not null
                                        group by STATUS, LOCATION 
                                        union all 
                                        SELECT s.name as STATUS, o.name as LOCATION , 0 as COUNT FROM ost_organization o
                                    join
                                    ost_ticket_status s on 1=1 order by STATUS, LOCATION
                                    )d
                                    group by STATUS, LOCATION ) a
                                    where LOCATION is not null order by STATUS, LOCATION";
                                    
                            $locs = db_query($sql); 
                            
                            $sql = "SELECT distinct name as LOCATION FROM ost_organization order by LOCATION";
                                    
                            $rawlocs = db_query($sql);
                            
                            $sql = "select STATUS, LOCATION, sum(COUNT) as COUNT from
                                    (
                                    select STATUS, LOCATION, count(ticket_id) as COUNT from
                                        (
                                        SELECT t.ticket_id, t.updated, o.name as LOCATION, s.name as STATUS FROM ost_ticket t 
                                        left join ost_user u on u.id = t.user_id 
                                        left join ost_organization o on o.id = u.org_id
                                        left join ost_ticket_status s on s.id = t.status_id
                                        where year(t.updated) = year(now())and s.state = 'closed' AND t.topic_id <> 14 AND t.topic_id <> 12
                                        ) a
                                        where LOCATION is not null
                                        group by STATUS, LOCATION 
                                        union all 
                                        SELECT s.name as STATUS, o.name as LOCATION , 0 as COUNT FROM ost_organization o
                                    join
                                    ost_ticket_status s on 1=1 order by STATUS, LOCATION
                                    )d
                                    where status = 'Closed' or status = 'Auto-Closed' group by STATUS, LOCATION  order by STATUS, LOCATION";
                            
                            $tbldatas = db_query($sql);
                            
                            $sql = "select STATUS,sum(COUNT) as Count from (
                                select STATUS, LOCATION, sum(COUNT) as COUNT from
                                    (
                                    select STATUS, LOCATION, count(ticket_id) as COUNT from
                                        (
                                        SELECT t.ticket_id, t.updated, o.name as LOCATION, s.name as STATUS FROM ost_ticket t 
                                        left join ost_user u on u.id = t.user_id 
                                        left join ost_organization o on o.id = u.org_id
                                        left join ost_ticket_status s on s.id = t.status_id
                                        where year(t.updated) = year(now())and s.state = 'closed' AND t.topic_id <> 14 AND t.topic_id <> 12
                                        ) a
                                        where LOCATION is not null
                                        group by STATUS, LOCATION 
                                        union all 
                                        SELECT s.name as STATUS, o.name as LOCATION , 0 as COUNT FROM ost_organization o
                                    join
                                    ost_ticket_status s on 1=1 order by STATUS, LOCATION
                                    )d
                                    where status = 'Closed' or status = 'Auto-Closed' group by STATUS, LOCATION  order by STATUS, LOCATION
                                    )t
                                    group by status";
                                    
                            $tbldatasts = db_query($sql);
                            
                            foreach ($tbldatasts as $tbldatast) {
                                if ($tbldatast["STATUS"] == "Auto-Closed") { 
                                    $totac = $tbldatast["Count"];
                                }
                                if ($tbldatast["STATUS"] == "Closed") {
                                    $totc = $tbldatast["Count"];
                                }
                            }
                            
                            $sql="select LOCATION, sum(COUNT) as COUNT from
                                    (
                                    select STATUS, LOCATION, count(ticket_id) as COUNT from
                                        (
                                        SELECT t.ticket_id, t.updated, o.name as LOCATION, s.name as STATUS FROM ost_ticket t 
                                        left join ost_user u on u.id = t.user_id 
                                        left join ost_organization o on o.id = u.org_id
                                        left join ost_ticket_status s on s.id = t.status_id
                                        where year(t.updated) = year(now()) and s.state = 'closed' AND t.topic_id <> 14 AND t.topic_id <> 12
                                        ) a
                                        where LOCATION is not null
                                        group by STATUS, LOCATION 
                                        union all 
                                        SELECT s.name as STATUS, o.name as LOCATION , 0 as COUNT FROM ost_organization o
                                    join
                                    ost_ticket_status s on 1=1 order by STATUS, LOCATION
                                    )d
                                    group by LOCATION order by STATUS, LOCATION";
                                    
                            $tbltotals = db_query($sql);
                            

                            ?>
                            <tr><td>&nbsp;</td></tr>
                            <tr class="bg-graphgreen"><th>CLOSED</th>
                            <?php
                             
                            foreach ($rawlocs as $loc) {

                                echo '<th></th>'; 
                            }
                            ?>
                            <th></th></tr></thead>
                            <?php
                            
                            foreach ($statuses as $status) {
                                
                                $class = null;
                                switch ($status["STATUS"]){
                                    case 'Auto-Closed':
                                    $class = 'class="text-warning"';
                                    break;
                                }
                               
                                
                                echo '<tr '.$class.'><td>'.$status["STATUS"].'</td>'; 
                                    foreach ($locs as $loc) {
                                     
                                       if ($status["STATUS"] == $loc["STATUS"]) {
                                           
                                            foreach ($tbldatas as $tbldata) {
                                                
                                                if ($status["STATUS"] == $tbldata["STATUS"] &&  $loc["LOCATION"] == $tbldata["LOCATION"]) {
                                                  
                                                        
                                                $count = number_format($tbldata["COUNT"]);
                                                ?>
                                                    <td> <?php if ($count !=0)echo $count;?> </td>
                                                                                                                                                               
                                               <?php }
                                            }
                                    }
                                
                                } 
                                if ($status["STATUS"] == "Auto-Closed") {
                                    $ctotal = $totac;
                                } else {
                                    $ctotal = $totc;                                   
                                }
                                                                
                                echo '<td><strong><span class="text-success">'.number_format($ctotal).'</strong></span></td></tr>'; 
                                
                            }   
                             ?>
                             <tr class="text-success"><th>TOTAL</th>
                             <?php
                             
                             foreach ($tbltotals as $tbltotal){
                                 $count = $tbltotal["COUNT"];
                                 echo '<td><strong><span class="text-success">'.number_format($count).'</strong></span></td>';
                                 $btotal = $btotal + $count;
                                 $count = null;
                             }   
                             
                             echo '<td><strong><span class="text-success">'.number_format($btotal).'</strong></span></td></tr>';
                            ?>
                            
                            
                            <?php
                            $sql = "select distinct STATUS from
                                    (
                                    SELECT t.ticket_id, t.updated, o.name as LOCATION, s.name as STATUS FROM ost_ticket t 
                                    left join ost_user u on u.id = t.user_id 
                                    left join ost_organization o on o.id = u.org_id
                                    left join ost_ticket_status s on s.id = t.status_id
                                    where year(t.updated) = year(now()) AND t.topic_id <> 14 AND t.topic_id <> 12
                                    ) a
                                    where LOCATION is not null order by STATUS";
                                    
                            $statuses = db_query($sql);   

                            $sql = "select distinct LOCATION, STATUS from
                                    (
                                    select STATUS, LOCATION, sum(COUNT) as COUNT from
                                    (
                                    select STATUS, LOCATION, count(ticket_id) as COUNT from
                                        (
                                        SELECT t.ticket_id, t.updated, o.name as LOCATION, s.name as STATUS FROM ost_ticket t 
                                        left join ost_user u on u.id = t.user_id 
                                        left join ost_organization o on o.id = u.org_id
                                        left join ost_ticket_status s on s.id = t.status_id
                                        where year(t.updated) = year(now()) AND t.topic_id <> 14 AND t.topic_id <> 12
                                        ) a
                                        where LOCATION is not null
                                        group by STATUS, LOCATION 
                                        union all 
                                        SELECT s.name as STATUS, o.name as LOCATION , 0 as COUNT FROM ost_organization o
                                    join
                                    ost_ticket_status s on 1=1 order by STATUS, LOCATION
                                    )d
                                    group by STATUS, LOCATION ) a
                                    where LOCATION is not null order by LOCATION";
                                    
                            $locs = db_query($sql); 
                            
                            $sql = "SELECT distinct name as LOCATION FROM ost_organization order by LOCATION";
                                    
                            $rawlocs = db_query($sql);
                            
                            $sql = "select STATUS, LOCATION, sum(COUNT) as COUNT from
                                    (
                                    select STATUS, LOCATION, count(ticket_id) as COUNT from
                                        (
                                        SELECT t.ticket_id, t.updated, o.name as LOCATION, s.name as STATUS FROM ost_ticket t 
                                        left join ost_user u on u.id = t.user_id 
                                        left join ost_organization o on o.id = u.org_id
                                        left join ost_ticket_status s on s.id = t.status_id
                                        where year(t.updated) = year(now()) AND t.topic_id <> 14 AND t.topic_id <> 12
                                        ) a
                                        where LOCATION is not null
                                        group by STATUS, LOCATION 
                                        union all 
                                        SELECT s.name as STATUS, o.name as LOCATION , 0 as COUNT FROM ost_organization o
                                    join
                                    ost_ticket_status s on 1=1 order by STATUS, LOCATION
                                    )d
                                    group by STATUS, LOCATION ";
                            
                            $tbldatas = db_query($sql);
                            
                            $sql="select LOCATION, sum(COUNT) as COUNT from
                                    (
                                    select STATUS, LOCATION, count(ticket_id) as COUNT from
                                        (
                                        SELECT t.ticket_id, t.updated, o.name as LOCATION, s.name as STATUS FROM ost_ticket t 
                                        left join ost_user u on u.id = t.user_id 
                                        left join ost_organization o on o.id = u.org_id
                                        left join ost_ticket_status s on s.id = t.status_id
                                        where year(t.updated) = year(now())AND t.topic_id <> 14 AND t.topic_id <> 12
                                        ) a
                                        where LOCATION is not null
                                        group by STATUS, LOCATION 
                                        union all 
                                        SELECT s.name as STATUS, o.name as LOCATION , 0 as COUNT FROM ost_organization o
                                    join
                                    ost_ticket_status s on 1=1 order by STATUS, LOCATION
                                    )d
                                    group by LOCATION ";
                                    
                            $tbltotals = db_query($sql);
                            
                            $sql="select sum(COUNT) as COUNT, LOCATION from (
                                    Select count(user_id) as COUNT, LOCATION from
                                         (
                                         SELECT distinct t.user_id, o.name as LOCATION FROM ost_ticket t 
                                         left join ost_user u on t.user_id = u.id 
                                         left join ost_organization o on u.org_id = o.id 
                                          
                                         where year(t.updated) = year(now())AND t.topic_id <> 14 AND t.topic_id <> 12 
                                         
                                         )a
                                         where LOCATION is not null
                                         group by LOCATION 
                                         
                                         union all 
                                                 SELECT 0 as COUNT, o.name as LOCATION  FROM ost_organization o
                                              order by LOCATION)b
                                group by LOCATION";
                             
                            $usertotals = db_query($sql);

                            ?>
                            <tr><td>&nbsp;</td></tr>
                            <tr class="bg-graphgreen"><th>ALL TICKETS</th>
                            <?php
                             
                            foreach ($rawlocs as $loc) {

                                echo '<th></th>'; 
                            }
                            ?>
                            <th></th></tr></thead>
                          
                             <tr class="text-success"><th>TOTAL</th>
                             <?php
                             $total = null;
                             foreach ($tbltotals as $tbltotal){
                                 $count = $tbltotal["COUNT"];
                                 echo '<td><strong><span class="text-success">'.number_format($count).'</strong></span></td>';
                                 $ttotal = $ttotal + $count;
                                 $count = null;
                             }   
                             echo '<td><strong><span class="text-primary">'.number_format($ttotal).'</strong></span></td></tr>';
                            ?>
                            <tr class="text-warning"><th>TOTAL USERS</th>
                             <?php
                             $total = null;
                             foreach ($usertotals as $tbltotal){
                                 $count = $tbltotal["COUNT"];
                                 echo '<td><strong><span class="text-warning">'.number_format($count).'</strong></span></td>';
                                 $total = $total + $count;
                                 $count = null;
                             }   
                             $utotal = $total;
                             echo '<td><strong><span class="text-primary">'.number_format($utotal).'</strong></span></td></tr>';
                            ?>
                            <tr class="text-secondary"><th>TICKETS PER USER</th>
                             <?php
                             $total = null;
                             foreach ($tbltotals as $tbltotal){
                                 
                                 foreach ($usertotals as $usertotal){
                                     
                                     if ($usertotal["LOCATION"] == $tbltotal["LOCATION"]){
                                     if ($usertotal["COUNT"] !=0){
                                         $tcount = $tbltotal["COUNT"] / $usertotal["COUNT"];
                                     } else {
                                         $tcount =0;
                                     }
                                     echo '<td><strong><span class="text-secondary">'.number_format($tcount).'</strong></span></td>';
                                 $total = $total + $usertotal["COUNT"];
                                 $tcount = null;
                                     }
                                 }
                                 
                                 
                             }   
                             $ttotal = $ttotal / $total;
                             echo '<td><strong><span class="text-primary">'.number_format($ttotal).'</strong></span></td></tr>';
                            ?>
                        </table>
                    </div>
                </div>
            </div>
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
                    TOP 10 OPEN TOPICS
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

<div class="row">
    <div class="col-lg-6">
        <div class="portlet"><!-- /primary heading -->
            <div class="portlet-heading">
                <h3 class="portlet-title text-dark">
                    TICKETS (STATUS BY TECH)
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion1" href="#portlet8"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet8" class="panel-collapse collapse show">
                <div class="portlet-body">
                    <div id="statusbyagent-chart">
                        <div class="row">
                            <div id="statusbyagent-chart-container" class="col-sm-8" style="height: 320px;">
                            </div>
                            <div id="statusbyagent-chart-legend" class="col-sm-4">
                            </div>
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
                    TOP 10 CLOSED TOPICS (CURRENT YEAR)
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion1" href="#portlet9"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet9" class="panel-collapse collapse show">
                <div class="portlet-body">
                    <div id="toptenclosedtopic-chart">
                        
                            <div id="toptenclosedtopic-chart-container"  style="height: 320px;">
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
                    TOP 10 CLOSED TOPICS (PRIOR YEAR)
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion1" href="#portlet11"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet11" class="panel-collapse collapse show">
                <div class="portlet-body">
                    <div id="toptenclosedpytopic-chart">
                        
                            <div id="toptenclosedpytopic-chart-container"  style="height: 320px;">
                            </div>
                           
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    
</div>
       <div class="row">
    <div class="col-lg-6">
        <div class="portlet"><!-- /primary heading -->
            <div class="portlet-heading">
                <h3 class="portlet-title text-dark">
                    TICKETS (STATUS BY LOCATION)
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion1" href="#portlet10"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet10" class="panel-collapse collapse show">
                <div class="portlet-body">
                
                    <div id="statusbylocation-chart">
                        <div class="row">
                            <div id="statusbylocation-chart-container" class="col-sm-8" style="height: 320px;">
                            </div>
                            <div id="statusbylocation-chart-legend" class="col-sm-4">
                            </div>
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
                    TOP 10 TICKET (OPEN BY ASSOCIATE)
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion1" href="#portlet15"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet15" class="panel-collapse collapse show">
                <div class="portlet-body">
                    <div id="toptenopenbyassociate-chart">
                        
                            <div id="toptenopenbyassociate-chart-container"  style="height: 320px;">
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
                    TOP 10 TICKET (Closed BY ASSOCIATE)
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion1" href="#portlet14"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet14" class="panel-collapse collapse show">
                <div class="portlet-body">
                    <div id="toptenclosebyassociate-chart">
                        
                            <div id="toptenclosebyassociate-chart-container"  style="height: 320px;">
                            </div>
                           
                        </div>
                    </div>
                </div>
            </div>
        </div> 

 </div>
 <div class="row">
    <div class="col-lg-12">
        <div class="portlet"><!-- /primary heading -->
            <div class="portlet-heading">
                <h3 class="portlet-title text-dark">
                    TICKETS CLOSED (TECH 1 YEARS)
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion1" href="#portlet12"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet12" class="panel-collapse collapse show">
                <div class="portlet-body">
                
                    <div id="closedbytech-chart">
                        <div class="row">
                            <div id="closedbytech-chart-container" class="col-sm-10" style="height: 320px;">
                            </div>
                            <div id="closedbytech-chart-legend" class="col-sm-2">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
 </div>
 <div class="row">
    <div class="col-lg-12">
        <div class="portlet"><!-- /primary heading -->
            <div class="portlet-heading">
                <h3 class="portlet-title text-dark">
                    TICKETS OPENED (LOCATION 1 YEARS) 
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion1" href="#portlet16"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet16" class="panel-collapse collapse show">
                <div class="portlet-body">
                
                    <div id="openedbylocation-chart">
                        <div class="row">
                            <div id="openedbylocation-chart-container" class="col-sm-10" style="height: 320px;">
                            </div>
                            <div id="openedbylocation-chart-legend" class="col-sm-2">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
 </div>
 <div class="row">
    <div class="col-lg-12">
        <div class="portlet"><!-- /primary heading -->
            <div class="portlet-heading">
                <h3 class="portlet-title text-dark">
                    TICKETS CLOSED (LOCATION 1 YEARS)
                </h3>
                <div class="portlet-widgets">
                    
                    <span class="divider"></span>
                    <a data-toggle="collapse" data-parent="#accordion1" href="#portlet13"><i class="ion-minus-round"></i></a>
                    <span class="divider"></span>
                    <a href="#" data-toggle="remove"><i class="ion-close-round"></i></a>
                </div>
                <div class="clearfix"></div>
            </div>
            <div id="portlet13" class="panel-collapse collapse show">
                <div class="portlet-body">
                
                    <div id="ticketsclosedbylocation-chart">
                        <div class="row">
                            <div id="ticketsclosedbylocation-chart-container" class="col-sm-10" style="height: 320px;">
                            </div>
                            <div id="ticketsclosedbylocation-chart-legend" class="col-sm-2">
                            </div>
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
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.flot.orderBars.js"></script>
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
                align: "center",
                barWidth: 0.8,                
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
				borderColor : "#eeeeee",
                margin: 10
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
                                                         INTERVAL 12 WEEK) AND CURRENT_DATE ()
                                AND ost_ticket.topic_id <> 12 and topic_id <> 14 AND topic_id <> 94
                                GROUP BY FROM_DAYS(TO_DAYS(created) - MOD(TO_DAYS(created) - 2, 7)) 
                                
                                Union all
                                
                                SELECT   COUNT(closed) AS VALUE, 'CLOSED' AS Status, FROM_DAYS(TO_DAYS(closed) - MOD(TO_DAYS(closed) 
                                                         - 2, 7)) AS CALENDARWEEK
                                FROM         ost_ticket
                                WHERE     FROM_DAYS(TO_DAYS(closed) - MOD(TO_DAYS(closed) - 2, 7)) BETWEEN DATE_SUB(CURRENT_DATE (), 
                                                         INTERVAL 12 WEEK) AND CURRENT_DATE ()
                                AND ost_ticket.topic_id <> 12 and topic_id <> 14 AND topic_id <> 94
                                GROUP BY FROM_DAYS(TO_DAYS(closed) - MOD(TO_DAYS(closed) - 2, 7))) data
                                
                                UNION all 
                                select sum(CAN)+sum(EXT)+sum(IND)+sum(MEX)+sum(NTC)+sum(OH)+sum(TNN1)+sum(SS)+sum(TNN2)+sum(TNS) as VALUE, 'BACKLOG' AS Status,  
                STR_TO_DATE(CONCAT(YEARWEEK,' Monday'), '%x%v %W') as CALENDARWEEK from ost_backlog 

                where STR_TO_DATE(CONCAT(YEARWEEK,' Monday'), '%x%v %W')

                BETWEEN DATE_SUB(CURRENT_DATE (), INTERVAL 12 WEEK) AND CURRENT_DATE ()
                group by STR_TO_DATE(CONCAT(YEARWEEK,' Monday'), '%x%v %W')
                                
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
        $sql1="SELECT COUNT(TOPIC) AS COUNT, TOPIC
FROM     (SELECT ost_ticket.number AS Ticket, 
                                    CASE ost_help_topic.topic_id WHEN 35 THEN 'Associates' WHEN 29 THEN 'Associates/Add' WHEN 36 THEN 'Associates/Change' WHEN 31 THEN 'Associates/Termination' WHEN 27 THEN 'Connectivity' WHEN 37 THEN 'Connectivity/Add'
                                     WHEN 39 THEN 'Connectivity/Change' WHEN 40 THEN 'Connectivity/Downtime' WHEN 42 THEN 'Connectivity/Downtime/Internal' WHEN 43 THEN 'Connectivity/Downtime/Vend as TOPICor' WHEN 41 THEN 'Connectivity/Maintenance'
                                     WHEN 44 THEN 'Connectivity/Maintenance/Internal' WHEN 45 THEN 'Connectivity/Maintenance/Vend as TOPICor' WHEN 85 THEN 'Connectivity/VPN' WHEN 81 THEN 'Connectivity/WSA' WHEN 21 THEN 'Email' WHEN 46 THEN 'Email/Add'
                                     WHEN 47 THEN 'Email/Change' WHEN 48 THEN 'Email/Downtime' WHEN 83 THEN 'Email/Outlook' WHEN 84 THEN 'Email/OWA' WHEN 30 THEN 'Facility' WHEN 50 THEN 'Facility/Downtime' WHEN 86 THEN 'Facility/Door System' WHEN
                                     51 THEN 'Facility/Downtime/Power Outtage' WHEN 49 THEN 'Facility/Organization (5S)' WHEN 22 THEN 'File and Print' WHEN 52 THEN 'File and Print/Add' WHEN 57 THEN 'File and Print/Change' WHEN 58 THEN 'File and Print/Configuration'
                                     WHEN 53 THEN 'File and Print/Permissions' WHEN 54 THEN 'File and Print/Permissions/Add' WHEN 55 THEN 'File and Print/Permissions/Change' WHEN 56 THEN 'ile and Print/Permissions/Remove' WHEN 32 THEN 'Hardware' WHEN 59
                                     THEN 'Hardware/Add' WHEN 60 THEN 'Hardware/Change' WHEN 61 THEN 'Hardware/Configuration' WHEN 62 THEN 'Hardware/Downtime' WHEN 63 THEN 'Hardware/Maintenance' WHEN 26 THEN 'Skype | Phones' WHEN 64 THEN 'Skype | Phones/Add'
                                     WHEN 65 THEN 'Skype | Phones/Change' WHEN 66 THEN 'Skype | Phones/Configuration' WHEN 68 THEN 'Skype | Phones/Downtime' WHEN 67 THEN 'Skype | Phones/Remove' WHEN 28 THEN 'Software' WHEN 34 THEN 'Software/Engineering | Design'
                                     WHEN 72 THEN 'Software/Engineering | Design/Install' WHEN 74 THEN 'Software/Engineering | Design/Remove' WHEN 73 THEN 'Software/Engineering | Design/Update' WHEN 69 THEN 'Software/Install' WHEN 33 THEN 'Software/Office'
                                     WHEN 75 THEN 'Software/Office/Install' WHEN 77 THEN 'Software/Office/Remove' WHEN 76 THEN 'Software/Office/Update' WHEN 71 THEN 'Software/Remove' WHEN 82 THEN 'Software/QuoteLog' WHEN 13 THEN 'Software/ShopEdge'
                                     WHEN 78 THEN 'Software/ShopEdge/Downtime' WHEN 15 THEN 'Software/ShopEdge/EDI' WHEN 17 THEN 'Software/ShopEdge/Performance' WHEN 18 THEN 'Software/ShopEdge/Printing' WHEN 19 THEN 'Software/ShopEdge/Reports'
                                     WHEN 16 THEN 'Software/ShopEdge/Security' WHEN 14 THEN 'Software/ShopEdge/Suggestion' WHEN 80 THEN 'Software/Suggestions System' WHEN 12 THEN 'Open Issue' END AS TOPIC
                  FROM      ost_ticket LEFT JOIN
                                    ost_help_topic ON ost_help_topic.topic_id = ost_ticket.topic_id
                  WHERE   ost_ticket.status_id <> 3 AND ost_ticket.status_id <> 2 AND ost_ticket.status_id <> 12 AND ost_ticket.topic_id <> 14 AND ost_ticket.topic_id <> 12) AS a
WHERE  TOPIC IS NOT NULL
GROUP BY TOPIC
ORDER BY COUNT DESC limit 10";
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
                labelMargin: 18,
                margin: 10
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
//Top 10 Closed
    $(function() {

		var data = [
        
           <?php
        $sql1="SELECT COUNT(TOPIC) AS COUNT, TOPIC
FROM     (SELECT ost_ticket.number AS Ticket, 
                                    CASE ost_help_topic.topic_id WHEN 35 THEN 'Associates' WHEN 29 THEN 'Associates/Add' WHEN 36 THEN 'Associates/Change' WHEN 31 THEN 'Associates/Termination' WHEN 27 THEN 'Connectivity' WHEN 37 THEN 'Connectivity/Add'
                                     WHEN 39 THEN 'Connectivity/Change' WHEN 40 THEN 'Connectivity/Downtime' WHEN 42 THEN 'Connectivity/Downtime/Internal' WHEN 43 THEN 'Connectivity/Downtime/Vend as TOPICor' WHEN 41 THEN 'Connectivity/Maintenance'
                                     WHEN 44 THEN 'Connectivity/Maintenance/Internal' WHEN 45 THEN 'Connectivity/Maintenance/Vend as TOPICor' WHEN 85 THEN 'Connectivity/VPN' WHEN 81 THEN 'Connectivity/WSA' WHEN 21 THEN 'Email' WHEN 46 THEN 'Email/Add'
                                     WHEN 47 THEN 'Email/Change' WHEN 48 THEN 'Email/Downtime' WHEN 83 THEN 'Email/Outlook' WHEN 84 THEN 'Email/OWA' WHEN 30 THEN 'Facility' WHEN 50 THEN 'Facility/Downtime' WHEN 86 THEN 'Facility/Door System' WHEN
                                     51 THEN 'Facility/Downtime/Power Outtage' WHEN 49 THEN 'Facility/Organization (5S)' WHEN 22 THEN 'File and Print' WHEN 52 THEN 'File and Print/Add' WHEN 57 THEN 'File and Print/Change' WHEN 58 THEN 'File and Print/Configuration'
                                     WHEN 53 THEN 'File and Print/Permissions' WHEN 54 THEN 'File and Print/Permissions/Add' WHEN 55 THEN 'File and Print/Permissions/Change' WHEN 56 THEN 'ile and Print/Permissions/Remove' WHEN 32 THEN 'Hardware' WHEN 59
                                     THEN 'Hardware/Add' WHEN 60 THEN 'Hardware/Change' WHEN 61 THEN 'Hardware/Configuration' WHEN 62 THEN 'Hardware/Downtime' WHEN 63 THEN 'Hardware/Maintenance' WHEN 26 THEN 'Skype | Phones' WHEN 64 THEN 'Skype | Phones/Add'
                                     WHEN 65 THEN 'Skype | Phones/Change' WHEN 66 THEN 'Skype | Phones/Configuration' WHEN 68 THEN 'Skype | Phones/Downtime' WHEN 67 THEN 'Skype | Phones/Remove' WHEN 28 THEN 'Software' WHEN 34 THEN 'Software/Engineering | Design'
                                     WHEN 72 THEN 'Software/Engineering | Design/Install' WHEN 74 THEN 'Software/Engineering | Design/Remove' WHEN 73 THEN 'Software/Engineering | Design/Update' WHEN 69 THEN 'Software/Install' WHEN 33 THEN 'Software/Office'
                                     WHEN 75 THEN 'Software/Office/Install' WHEN 77 THEN 'Software/Office/Remove' WHEN 76 THEN 'Software/Office/Update' WHEN 71 THEN 'Software/Remove' WHEN 82 THEN 'Software/QuoteLog' WHEN 13 THEN 'Software/ShopEdge'
                                     WHEN 78 THEN 'Software/ShopEdge/Downtime' WHEN 15 THEN 'Software/ShopEdge/EDI' WHEN 17 THEN 'Software/ShopEdge/Performance' WHEN 18 THEN 'Software/ShopEdge/Printing' WHEN 19 THEN 'Software/ShopEdge/Reports'
                                     WHEN 16 THEN 'Software/ShopEdge/Security' WHEN 14 THEN 'Software/ShopEdge/Suggestion' WHEN 80 THEN 'Software/Suggestions System' WHEN 12 THEN 'Open Issue' END AS TOPIC
                  FROM      ost_ticket LEFT JOIN
                                    ost_help_topic ON ost_help_topic.topic_id = ost_ticket.topic_id
                  WHERE    year(ost_ticket.closed) = year(now()) and ost_ticket.status_id = 3 or ost_ticket.status_id = 2 AND ost_ticket.status_id <> 12 AND ost_ticket.topic_id <> 14 AND ost_ticket.topic_id <> 12 ) AS a
WHERE  TOPIC IS NOT NULL
GROUP BY TOPIC
ORDER BY COUNT DESC limit 10";
        $tresults = db_query($sql1); 

        foreach ($tresults as $tresult) {
            echo "[\"".$tresult['TOPIC']."\", ".$tresult['COUNT']."],";
        }
    ?> 
                
        ];

		$.plot("#toptenclosedtopic-chart-container", [ data ], {
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
                labelMargin: 18,
                margin: 10
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

    //Top 10 Closed prior
    $(function() {

		var data = [
        
           <?php
        $sql1="SELECT COUNT(TOPIC) AS COUNT, TOPIC
FROM     (SELECT ost_ticket.number AS Ticket, 
                                    CASE ost_help_topic.topic_id WHEN 35 THEN 'Associates' WHEN 29 THEN 'Associates/Add' WHEN 36 THEN 'Associates/Change' WHEN 31 THEN 'Associates/Termination' WHEN 27 THEN 'Connectivity' WHEN 37 THEN 'Connectivity/Add'
                                     WHEN 39 THEN 'Connectivity/Change' WHEN 40 THEN 'Connectivity/Downtime' WHEN 42 THEN 'Connectivity/Downtime/Internal' WHEN 43 THEN 'Connectivity/Downtime/Vend as TOPICor' WHEN 41 THEN 'Connectivity/Maintenance'
                                     WHEN 44 THEN 'Connectivity/Maintenance/Internal' WHEN 45 THEN 'Connectivity/Maintenance/Vend as TOPICor' WHEN 85 THEN 'Connectivity/VPN' WHEN 81 THEN 'Connectivity/WSA' WHEN 21 THEN 'Email' WHEN 46 THEN 'Email/Add'
                                     WHEN 47 THEN 'Email/Change' WHEN 48 THEN 'Email/Downtime' WHEN 83 THEN 'Email/Outlook' WHEN 84 THEN 'Email/OWA' WHEN 30 THEN 'Facility' WHEN 50 THEN 'Facility/Downtime' WHEN 86 THEN 'Facility/Door System' WHEN
                                     51 THEN 'Facility/Downtime/Power Outtage' WHEN 49 THEN 'Facility/Organization (5S)' WHEN 22 THEN 'File and Print' WHEN 52 THEN 'File and Print/Add' WHEN 57 THEN 'File and Print/Change' WHEN 58 THEN 'File and Print/Configuration'
                                     WHEN 53 THEN 'File and Print/Permissions' WHEN 54 THEN 'File and Print/Permissions/Add' WHEN 55 THEN 'File and Print/Permissions/Change' WHEN 56 THEN 'ile and Print/Permissions/Remove' WHEN 32 THEN 'Hardware' WHEN 59
                                     THEN 'Hardware/Add' WHEN 60 THEN 'Hardware/Change' WHEN 61 THEN 'Hardware/Configuration' WHEN 62 THEN 'Hardware/Downtime' WHEN 63 THEN 'Hardware/Maintenance' WHEN 26 THEN 'Skype | Phones' WHEN 64 THEN 'Skype | Phones/Add'
                                     WHEN 65 THEN 'Skype | Phones/Change' WHEN 66 THEN 'Skype | Phones/Configuration' WHEN 68 THEN 'Skype | Phones/Downtime' WHEN 67 THEN 'Skype | Phones/Remove' WHEN 28 THEN 'Software' WHEN 34 THEN 'Software/Engineering | Design'
                                     WHEN 72 THEN 'Software/Engineering | Design/Install' WHEN 74 THEN 'Software/Engineering | Design/Remove' WHEN 73 THEN 'Software/Engineering | Design/Update' WHEN 69 THEN 'Software/Install' WHEN 33 THEN 'Software/Office'
                                     WHEN 75 THEN 'Software/Office/Install' WHEN 77 THEN 'Software/Office/Remove' WHEN 76 THEN 'Software/Office/Update' WHEN 71 THEN 'Software/Remove' WHEN 82 THEN 'Software/QuoteLog' WHEN 13 THEN 'Software/ShopEdge'
                                     WHEN 78 THEN 'Software/ShopEdge/Downtime' WHEN 15 THEN 'Software/ShopEdge/EDI' WHEN 17 THEN 'Software/ShopEdge/Performance' WHEN 18 THEN 'Software/ShopEdge/Printing' WHEN 19 THEN 'Software/ShopEdge/Reports'
                                     WHEN 16 THEN 'Software/ShopEdge/Security' WHEN 14 THEN 'Software/ShopEdge/Suggestion' WHEN 80 THEN 'Software/Suggestions System' WHEN 12 THEN 'Open Issue' END AS TOPIC
                  FROM      ost_ticket LEFT JOIN
                                    ost_help_topic ON ost_help_topic.topic_id = ost_ticket.topic_id
                  WHERE    year(ost_ticket.closed) = year(CURDATE() - INTERVAL 1 YEAR) and ost_ticket.status_id = 3 or ost_ticket.status_id = 2 AND ost_ticket.status_id <> 12 AND ost_ticket.topic_id <> 14 AND ost_ticket.topic_id <> 12 ) AS a
WHERE  TOPIC IS NOT NULL
GROUP BY TOPIC
ORDER BY COUNT DESC limit 10";
        $ptresults = db_query($sql1); 

        foreach ($ptresults as $ptresult) {
            echo "[\"".$ptresult['TOPIC']."\", ".$ptresult['COUNT']."],";
        }
    ?> 
                
        ];

		$.plot("#toptenclosedpytopic-chart-container", [ data ], {
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
                labelMargin: 18,
                margin: 10
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
//Top 10 Open by Associate
    $(function() {

		var data = [
        
           <?php
        $sql1="select * from (
	select count(ticket_id) as COUNT, ASSOCIATE, LOCATION from
	(
	SELECT t.ticket_id, t.updated, o.name as LOCATION, u.name as ASSOCIATE FROM ost_ticket t 
		left join ost_user u on u.id = t.user_id 
		left join ost_organization o on o.id = u.org_id
		left join ost_ticket_status s on s.id = t.status_id
		where year(t.updated) = year(now())AND t.topic_id <> 14 AND t.topic_id <> 12 and u.id <> 674 and s.state='open'
	)a
	group by ASSOCIATE order by COUNT DESC
    ) a limit 10";
        $tresults = db_query($sql1); 

        foreach ($tresults as $tresult) {
            echo "[\"".$tresult['ASSOCIATE']."\", ".$tresult['COUNT']."],";
        }
    ?> 
                
        ];

		$.plot("#toptenopenbyassociate-chart-container", [ data ], {
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
                labelMargin: 18,
                margin: 10
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
    //Top 10 Closed Associate
    $(function() {

		var data = [
        
           <?php
        $sql1="select * from (
	select count(ticket_id) as COUNT, ASSOCIATE, LOCATION from
	(
	SELECT t.ticket_id, t.updated, o.name as LOCATION, u.name as ASSOCIATE FROM ost_ticket t 
		left join ost_user u on u.id = t.user_id 
		left join ost_organization o on o.id = u.org_id
		left join ost_ticket_status s on s.id = t.status_id
		where year(t.updated) = year(now())AND t.topic_id <> 14 AND t.topic_id <> 12 and u.id <> 674 and s.state='closed'
	)a
	group by ASSOCIATE order by COUNT DESC
    ) a limit 10";
        $tresults = db_query($sql1); 

        foreach ($tresults as $tresult) {
            echo "[\"".$tresult['ASSOCIATE']."\", ".$tresult['COUNT']."],";
        }
    ?> 
                
        ];

		$.plot("#toptenclosebyassociate-chart-container", [ data ], {
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
                labelMargin: 18,
                margin: 10
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
                labelMargin: 10,
                margin: 10
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
        ["Held", <?php echo $HeldTickets; ?>], 
        ["Agent Action", <?php echo $ReplyTickets; ?>], 
        ["Submitter Action", <?php echo $TheirReplyTickets; ?>], 
        ["3rd Party", <?php echo $ThridPartyTicketsTickets; ?>]
               
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
                labelMargin: 20,
                margin: 10
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
        ["Held", <?php echo $MyHeldTickets; ?>], 
        ["Agent Action", <?php echo $MyReplyTickets; ?>], 
        ["Submitter Action", <?php echo $MyTheirReplyTickets; ?>], 
        ["3rd PArty", <?php echo $MyThridPartyTickets; ?>]
               
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
                labelMargin: 20,
                margin: 10
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
        ["Agent Action", <?php echo $SuggestionAssignedTickets; ?>], 
        ["3rd Party", <?php echo $SuggestionThridPartyTicketsTickets; ?>], 
        
               
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
                labelMargin: 20,
                margin: 10
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

    <?php
        $sql="	select distinct lastname, owner_name  from (            select sum(count) as COUNT, STATUS, OWNER_NAME,LASTNAME from
				(Select COUNT(Status) as Count, STATUS, OWNER_NAME, LASTNAME from
					(SELECT ost_ticket.number as Ticket, 
						ost_ticket_status.name as STATUS, 
						ost_ticket.Updated, 
						ost_staff.lastname as LASTNAME,
						CONCAT(ost_staff.lastname, ', ', ost_staff.firstname) as OWNER_NAME
						FROM (ost_ticket LEFT JOIN ost_ticket_status ON ost_ticket.status_id = ost_ticket_status.id)
						 LEFT JOIN ost_staff ON ost_ticket.staff_id = ost_staff.staff_id WHERE ost_ticket.topic_id != 12 and 
						 ost_ticket.topic_id != 14 and ost_ticket.status_id != 3 and ost_ticket.status_id != 12) A
				where lastname is not null Group by lastname,  Status, OWNER_NAME )b
            group by STATUS,LASTNAME) a ";
        $techs = db_query($sql); 
        
        $sql= "select distinct LOCATION from 
                (select sum(COUNT) as COUNT, STATUS, LOCATION from
                    (Select COUNT(STATUS) as COUNT,STATUS, LOCATION from
                        (SELECT ost_ticket.number as Ticket, ost_ticket_status.name as STATUS, ost_organization.name as LOCATION
                        FROM ((ost_ticket LEFT JOIN ost_ticket_status ON ost_ticket.status_id = ost_ticket_status.id)
                        LEFT JOIN (ost_user LEFT JOIN ost_organization ON ost_user.org_id = ost_organization.id) ON ost_ticket.user_id = ost_user.id)
                        LEFT JOIN ost_staff ON ost_ticket.staff_id = ost_staff.staff_id WHERE ost_ticket.topic_id != 12 and ost_ticket.topic_id != 14 and ost_ticket.status_id != 3 and ost_ticket.status_id != 12) A
                    where LOCATION is not null
                Group by LOCATION,  STATUS ) a
               group by LOCATION, STATUS) b ";
                
        $locs = db_query($sql); 
        
        $sql="SELECT distinct ost_ticket_status.name as STATUS 
			    FROM (ost_ticket LEFT JOIN ost_ticket_status ON ost_ticket.status_id = ost_ticket_status.id)
                WHERE ost_ticket.topic_id != 12 and ost_ticket.topic_id != 14 and ost_ticket.status_id != 3 and ost_ticket.status_id != 12 order by STATUS";
        
        $cstatuses = db_query($sql); 
        
        $sql="select sum(count) as COUNT, STATUS, OWNER_NAME,LASTNAME from
				(Select COUNT(Status) as Count, STATUS, OWNER_NAME, LASTNAME from
					(SELECT ost_ticket.number as Ticket, 
						ost_ticket_status.name as STATUS, 
						ost_ticket.Updated, 
						ost_staff.lastname as LASTNAME,
						CONCAT(ost_staff.lastname, ', ', ost_staff.firstname) as OWNER_NAME
						FROM (ost_ticket LEFT JOIN ost_ticket_status ON ost_ticket.status_id = ost_ticket_status.id)
						 LEFT JOIN ost_staff ON ost_ticket.staff_id = ost_staff.staff_id WHERE ost_ticket.topic_id != 12 and 
						 ost_ticket.topic_id != 14 and ost_ticket.status_id != 3 and ost_ticket.status_id != 12) A
				where lastname is not null Group by lastname,  Status, OWNER_NAME )b
            group by STATUS,LASTNAME";
            
        $ctechsdata = db_query($sql);
        
        $sql="select sum(COUNT) as COUNT, STATUS, LOCATION from
                (Select COUNT(STATUS) as COUNT,STATUS, LOCATION from
                    (SELECT ost_ticket.number as Ticket, ost_ticket_status.name as STATUS, ost_organization.name as LOCATION
                    FROM ((ost_ticket LEFT JOIN ost_ticket_status ON ost_ticket.status_id = ost_ticket_status.id)
                    LEFT JOIN (ost_user LEFT JOIN ost_organization ON ost_user.org_id = ost_organization.id) ON ost_ticket.user_id = ost_user.id)
                    LEFT JOIN ost_staff ON ost_ticket.staff_id = ost_staff.staff_id WHERE ost_ticket.topic_id != 12 and ost_ticket.topic_id != 14 and ost_ticket.status_id != 3 and ost_ticket.status_id != 12) A
                    where LOCATION is not null
                Group by LOCATION,  STATUS ) a
              group by LOCATION, STATUS";

$clocsdata = db_query($sql);
        
?>
//Status by tech
$(function () {        
<?php                
           foreach ($cstatuses as $cstatus) {
             
             echo "var ".preg_replace('/\s+/', '', $cstatus["STATUS"])." = { \n";
             echo "label: '".$cstatus["STATUS"]."', \n";
             echo  "data: [ \n";
             
             foreach ($ctechsdata as $techsdata) {
             
                if ($techsdata["STATUS"] == $cstatus["STATUS"] ){
                     
                    echo  "[\"".$techsdata["OWNER_NAME"]."\", ".$techsdata["COUNT"]."],\n"; 
                } 
             
             }
             echo "] };\n";
        
        }
    ?>     
     

   
    var dataset = [
   <?php
  foreach ($cstatuses as $cstatus) {
             
             echo preg_replace('/\s+/', '', $cstatus["STATUS"]).",";
   }   
   ?>
   ];

    var options = {
        series: {
            stack: true,
            bars: {
                show: true
            }
        },
        
        bars: {
            align: "center",
            horizontal: false,
            barWidth: .8,
            lineWidth: 1
        },
         grid : {
				hoverable : true,
				clickable : true,
				tickColor : "#f9f9f9",
				borderWidth : 1,
				borderColor : "#eeeeee",
                labelMargin: 20,
                margin: 10
			},
            
			 tooltip: {
                 show: true,
                 cssClass: "flot",
                 content: "%s: %y",
                
                
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
        },
        legend: {
                show: true,
                container: '#statusbyagent-chart-legend'
				
				
        }
    };
    
    $.plot($("#statusbyagent-chart #statusbyagent-chart-container"), dataset, options);
});

//Status by Location
$(function () {        
<?php                
           foreach ($cstatuses as $cstatus) {
             
             echo "var ".preg_replace('/\s+/', '', $cstatus["STATUS"])." = { \n";
             echo "label: '".$cstatus["STATUS"]."', \n";
             echo  "data: [ \n";
             
             foreach ($clocsdata as $locsdata) {
             
                if ($locsdata["STATUS"] == $cstatus["STATUS"] ){
                     
                    echo  "[\"".$locsdata["LOCATION"]."\", ".$locsdata["COUNT"]."],\n"; 
                } 
             
             }
             echo "] };\n";
        
        }
    ?>     
     

   
    var dataset = [
   <?php
  foreach ($cstatuses as $cstatus) {
             
             echo preg_replace('/\s+/', '', $cstatus["STATUS"]).",";
   }   
   ?>
   ];

    var options = {
        series: {
            stack: true,
            bars: {
                show: true
            }
        },
        
        bars: {
            align: "center",
            horizontal: false,
            barWidth: .8,
            lineWidth: 1
        },
         grid : {
				hoverable : true,
				clickable : true,
				tickColor : "#f9f9f9",
				borderWidth : 1,
				borderColor : "#eeeeee",
                labelMargin: 20,
                margin: 10
			},
            
			 tooltip: {
                 show: true,
                 cssClass: "flot",
                 content: "%s: %y",
                
                
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
        },
        legend: {
                show: true,
                container: '#statusbylocation-chart-legend'
        }
    };
    
    $.plot($("#statusbylocation-chart #statusbylocation-chart-container"), dataset, options);
});  
//location 2 year
<?php

$sql="select distinct LASTNAME,OWNER_NAME from
(
	select CALENDARWEEK,CALENDARYEAR, count(LASTNAME) as COUNT,OWNER_NAME, LASTNAME from
	(
	SELECT  month(FROM_DAYS(TO_DAYS(t.closed) - MOD(TO_DAYS(t.closed) - 2, 7))) AS CALENDARWEEK,YEAR(FROM_DAYS(TO_DAYS(t.closed) - MOD(TO_DAYS(t.closed) - 2, 7))) AS CALENDARYEAR, u.lastname as LASTNAME, 
    concat(u.lastname, ', ', u.firstname) AS OWNER_NAME, s.name as STATUS FROM ost_ticket t 
	left join ost_staff u on u.staff_id = t.staff_id 
	left join ost_ticket_status s on s.id = t.status_id


	where t.status_id = 3 AND t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and t.closed >(CURDATE() - INTERVAL 11 MONTH)
	) a

	group by OWNER_NAME, CALENDARYEAR, CALENDARWEEK order by CALENDARYEAR,CALENDARWEEK
)b";

$locs = db_query($sql);

$sql="select CALENDARWEEK,CALENDARYEAR, count(LASTNAME) as COUNT,OWNER_NAME, LASTNAME from
	(
	SELECT  month(FROM_DAYS(TO_DAYS(t.closed) - MOD(TO_DAYS(t.closed) - 2, 7))) AS CALENDARWEEK,YEAR(FROM_DAYS(TO_DAYS(t.closed) - MOD(TO_DAYS(t.closed) - 2, 7))) AS CALENDARYEAR, u.lastname as LASTNAME, 
    concat(u.lastname, ', ', u.firstname) AS OWNER_NAME, s.name as STATUS FROM ost_ticket t 
	left join ost_staff u on u.staff_id = t.staff_id 
	left join ost_ticket_status s on s.id = t.status_id


	where t.status_id = 3 AND t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and t.closed >(CURDATE() - INTERVAL 11 MONTH)
	) a

	group by OWNER_NAME, CALENDARYEAR, CALENDARWEEK order by CALENDARYEAR,CALENDARWEEK
";

$locsdata = db_query($sql);

?>

       
<?php                
           foreach ($locs as $loc) {
             
             echo "var ".preg_replace('/\s+/', '', $loc["LASTNAME"])." = [\n";
                         
             foreach ($locsdata as $locdata) {
             
                if ($locdata["LASTNAME"] == $loc["LASTNAME"] ){
                     
                    echo  "[\"".date("M Y", mktime(0, 0, 0, $locdata["CALENDARWEEK"], 10, $locdata["CALENDARYEAR"]))."\", ".$locdata["COUNT"]."],\n"; 
                } 
             
             }
             echo "];\n";
        
        }
    ?> 

$(function () {        
    $.plot($("#closedbytech-chart-container"),
        [
        
        <?php                
           foreach ($locs as $loc) {
              ?> 
               {
              data: <?php echo $loc["LASTNAME"];?>,
              label: "<?php echo $loc["OWNER_NAME"];?>",
              points: { show: true },
              lines: { show: true}

            },
            <?php   
           }  
         ?> 
         
            
        ],
        {            
            grid : {
				hoverable : true,
				clickable : true,
				tickColor : "#f9f9f9",
				borderWidth : 1,
				borderColor : "#eeeeee",
                labelMargin: 20,
                margin: 10
			},
            tooltip: {
                 show: true,
                 cssClass: "flot",
                 content: "%s | %y",
                
                
              },
            xaxis: {
                mode: "categories",
				tickLength: 0,
                tickColor : '#f5f5f5',
				font : {
                color : '#868e96',
                    	},
                rotateTicks: 135
            },
            yaxes: [
                {
                    /* First y axis */
                },
                {
                    /* Second y axis */
                    position: "right"  /* left or right */
                }
            ], legend: {
                show: true,
                container: '#closedbytech-chart-legend'
				
				
        }      
        }
    );
});

//Opened by location 2 year
<?php

$sql="select distinct LOCATION from
(
	select CALENDARWEEK, CALENDARYEAR, count(LOCATION) as COUNT, LOCATION from
	(
	SELECT month(t.created) AS CALENDARWEEK,year(t.created) AS CALENDARYEAR, o.name AS LOCATION, s.name as STATUS FROM ost_ticket t 
	left join ost_user u on u.id = t.user_id 
	left join ost_organization o on o.id = u.org_id
	left join ost_ticket_status s on s.id = t.status_id


	where t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and (t.created) > (CURDATE() - INTERVAL 11 MONTH)
	) a
	where LOCATION is not null
	group by LOCATION, CALENDARWEEK,CALENDARYEAR order by CALENDARYEAR,CALENDARWEEK, LOCATION
)b ";

$olocs = db_query($sql);

$sql="select CALENDARWEEK, CALENDARYEAR, count(LOCATION) as COUNT, LOCATION from
	(
	SELECT month(t.created) AS CALENDARWEEK,year(t.created) AS CALENDARYEAR, o.name AS LOCATION, s.name as STATUS FROM ost_ticket t 
	left join ost_user u on u.id = t.user_id 
	left join ost_organization o on o.id = u.org_id
	left join ost_ticket_status s on s.id = t.status_id


	where t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and (t.created) > (CURDATE() - INTERVAL 11 MONTH)
	) a
	where LOCATION is not null
	group by LOCATION, CALENDARWEEK,CALENDARYEAR order by CALENDARYEAR,CALENDARWEEK, LOCATION
    
";

$olocsdata = db_query($sql);

?>

       
<?php                
           foreach ($olocs as $oloc) {
             
             echo "var obl".preg_replace('/\s+/', '', $oloc["LOCATION"])." = [\n";
                         
             foreach ($olocsdata as $olocdata) {
             
                if ($olocdata["LOCATION"] == $oloc["LOCATION"] ){
                     
                    echo  "[\"".date("M Y", mktime(0, 0, 0, $olocdata["CALENDARWEEK"], 10, $olocdata["CALENDARYEAR"]))."\", ".$olocdata["COUNT"]."],\n"; 
                } 
             
             }
             echo "];\n";
        
        }
    ?> 

$(function () {        
    $.plot($("#openedbylocation-chart-container"),
        [
        
        <?php                
           foreach ($olocs as $oloc) {
              ?> 
               {
              data: obl<?php echo $oloc["LOCATION"];?>,
              label: "<?php echo $oloc["LOCATION"];?>",
              points: { show: true },
              lines: { show: true},
             
            },
            <?php   
           }  
         ?> 
            
        ],
        
        {            
            grid : {
				hoverable : true,
				clickable : true,
				tickColor : "#f9f9f9",
				borderWidth : 1,
				borderColor : "#eeeeee",
                labelMargin: 20,
                margin: 10
			},
            tooltip: {
                 show: true,
                 cssClass: "flot",
                 content: "%s | %y",
                
                
              },
            xaxis: {
                mode: "categories",
				tickLength: 0,
                tickColor : '#f5f5f5',
				font : {
                color : '#868e96',
                    	},
                rotateTicks: 135
            },
            yaxes: [
                {
                    /* First y axis */
                },
                {
                    /* Second y axis */
                    position: "right"  /* left or right */
                }
            ], legend: {
                show: true,
                container: '#openedbylocation-chart-legend'
				
				
        }      
        }
    );
});

//closed by location 2 year
<?php

$csql="select distinct LOCATION from
(
	select CALENDARWEEK, CALENDARYEAR, count(LOCATION) as COUNT, LOCATION from
	(
	SELECT month(t.closed) AS CALENDARWEEK,year(t.closed) AS CALENDARYEAR, o.name AS LOCATION, s.name as STATUS FROM ost_ticket t 
	left join ost_user u on u.id = t.user_id 
	left join ost_organization o on o.id = u.org_id
	left join ost_ticket_status s on s.id = t.status_id


	where t.status_id = 3  AND t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and (t.closed) > (CURDATE() - INTERVAL 11 MONTH) and o.name is not null
	) a
	where LOCATION is not null
	group by LOCATION, CALENDARWEEK,CALENDARYEAR order by CALENDARYEAR,CALENDARWEEK, LOCATION
)b;"; 

$locs = db_query($csql);

$csql="select CALENDARWEEK, CALENDARYEAR, count(LOCATION) as COUNT, LOCATION from
	(
	SELECT month(t.closed) AS CALENDARWEEK,year(t.closed) AS CALENDARYEAR, o.name AS LOCATION, s.name as STATUS FROM ost_ticket t 
	left join ost_user u on u.id = t.user_id 
	left join ost_organization o on o.id = u.org_id
	left join ost_ticket_status s on s.id = t.status_id


	where t.status_id = 3  AND t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and (t.closed) > (CURDATE() - INTERVAL 11 MONTH)
	) a
	where LOCATION is not null
	group by LOCATION, CALENDARWEEK,CALENDARYEAR order by CALENDARYEAR,CALENDARWEEK, LOCATION
";

$locsdata = db_query($csql);

?>

       
<?php                
           foreach ($locs as $loc) {
             
             echo "var ".preg_replace('/\s+/', '', $loc["LOCATION"])." = [\n";
                         
             foreach ($locsdata as $locdata) {
             
                if ($locdata["LOCATION"] == $loc["LOCATION"] ){
                     
                    echo  "[\"".date("M Y", mktime(0, 0, 0, $locdata["CALENDARWEEK"], 10, $locdata["CALENDARYEAR"]))."\", ".$locdata["COUNT"]."],\n"; 
                } 
             
             }
             echo "];\n";
        
        }
    ?> 

$(function () {        
    $.plot($("#ticketsclosedbylocation-chart-container"),
        [
        
        <?php                
           foreach ($locs as $loc) {
              ?> 
               {
              data: <?php echo $loc["LOCATION"];?>,
              label: "<?php echo $loc["LOCATION"];?>",
              points: { show: true },
              lines: { show: true},
             
            },
            <?php   
           }  
         ?> 
            
        ],
        
        {            
            grid : {
				hoverable : true,
				clickable : true,
				tickColor : "#f9f9f9",
				borderWidth : 1,
				borderColor : "#eeeeee",
                labelMargin: 20,
                margin: 10
			},
            tooltip: {
                 show: true,
                 cssClass: "flot",
                 content: "%s | %y",
                
                
              },
            xaxis: {
                mode: "categories",
				tickLength: 0,
                tickColor : '#f5f5f5',
				font : {
                color : '#868e96',
                    	},
                rotateTicks: 135
            },
            yaxes: [
                {
                    /* First y axis */
                },
                {
                    /* Second y axis */
                    position: "right"  /* left or right */
                }
            ], legend: {
                show: true,
                container: '#ticketsclosedbylocation-chart-legend'
				
				
        }      
        }
    );
});

    
      
</script>



