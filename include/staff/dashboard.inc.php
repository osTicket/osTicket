<script src="<?php echo ROOT_PATH; ?>scp/js/highcharts.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/highcharts-3d.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/modules/exporting.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/modules/export-data.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/modules/pareto.js"></script>

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
        <div class="portlet" id="backlog-chart-container" ><!-- /primary heading -->
            
        </div>
    </div>
    
    
   
    
    <div class="col-lg-3">
        <div class="portlet" id="ticketsbystatus-chart-container"><!-- /primary heading -->
            
        </div>
    </div>

    <div class="col-lg-3">
        <div class="portlet" id="myticketsbystatus-chart-container"><!-- /primary heading -->
           
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
        <div class="portlet" id="backlog-chart-container1"><!-- /primary heading -->
           
        </div>
    </div>
    <div class="col-lg-6">
        <div class="portlet" id="toptentopic-chart-container"><!-- /primary heading -->
           
        </div>
    </div>
    <div class="col-lg-3">
        <div class="portlet" id="toptenclosedtopic-container"><!-- /primary heading -->
           
        </div>
    </div>
    <div class="col-lg-3">
        <div class="portlet" id="toptenclosedpytopic-chart-container"><!-- /primary heading -->
            
            </div>
        </div>
       
               <div class="col-lg-3">
        <div class="portlet" id="toptenopenbyassociate-chart-container"><!-- /primary heading -->
           </div>
        </div>
         <div class="col-lg-3">
        <div class="portlet" id="toptenclosebyassociate-chart-container"><!-- /primary heading -->
            
                </div>
            </div>   
</div>

<div class="row">
  
     <div class="col-lg-6">
        <div class="portlet" id="statusbyagent-chart-container1"><!-- /primary heading -->
           
        </div>
    </div>

    
 <div class="col-lg-6">
        <div class="portlet" id="statusbyagent-chart-container2"><!-- /primary heading -->
           
        </div>
    </div>
    
</div>
       <div class="row">
    <div class="col-lg-6">
        <div class="portlet" id="statusbylocation-chart-container1"><!-- /primary heading -->
            
        </div>
    </div>
    <div class="col-lg-6">
        <div class="portlet" id="statusbylocation-chart-container2"><!-- /primary heading -->
            
        </div>
    </div>


 </div>
 <div class="row">
 
 
 <div class="col-lg-12">
        <div class="portlet" id="closedbytech-chart-container1"><!-- /primary heading -->
            
        </div>
    </div>
 
  <div class="col-lg-12">
        <div class="portlet" id="openedbylocation-chart-container1"><!-- /primary heading -->
            
        </div>
    </div>
   <div class="col-lg-12">
        <div class="portlet" id="closedbylocation-chart-container1"><!-- /primary heading -->
            
        </div>
    </div>

 </div>

 
<script>
		
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
    
    
 $(function() {        
     Highcharts.chart('backlog-chart-container1', {
        chart: {
            type: 'areaspline'
        },
        title: {
            text: 'TICKETS (OPEN|CLOSED|BACKLOG)',
            style: {
                color: '#797979',
                fontSize: '14px',
                fontWeight: '600',
                }
        },
        legend: {
            layout: 'horizontal',
            align: 'right',
            verticalAlign: 'top',
            x: -20,
            y: 30,
            floating: true,
            borderWidth: 1,
            backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF'
        },
        xAxis: {
            categories: [ <?php foreach ($results as $result) {echo "'".$result['WEEK']."',";}?>
                
            ],
            
        },
        yAxis: {
            title: {
                text: 'Number of Tickets'
            },
              plotLines: [{
                color: 'green', // Color value
                dashStyle: 'shortdash', // Style of the plot line. Default to solid
                value: 50, // Value of where the line will appear
                width: 2, // Width of the line
                label: {
                    text: 'Target',
                    style: {
                    color: 'black',
                    fontWeight: 'bold'
                }
                } ,
                zIndex: 6                
              }]
                    },
        tooltip: {
            shared: true,
            valueSuffix: ' tickets'
        },
        credits: {
            enabled: false
        },
        plotOptions: {
            areaspline: {
                fillOpacity: 0.5
            }
        },
        series: [
        {
            type: 'column',
            name: 'CLOSED',
            data: [<?php foreach ($results as $result) { echo $result['CLOSED'].',';}?>]
        }, {
            type: 'spline',
            name: 'OPEN',
            data: [<?php foreach ($results as $result) { echo $result['OPEN'].',';}?>],
            color: '#e3c436'
            
        }, {
            name: 'BACKLOG',
            data: [<?php foreach ($results as $result) { echo $result['BACKLOG'].',';}?>],
            color: '#dd3c37'
            
          }]

    });

});      
    

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
    ?>    
$(function() {        
 Highcharts.chart('toptentopic-chart-container', {

    chart: {
        renderTo: 'toptentopic-chart-container',
        type: 'column'
    },
    title: {
        text: 'TOP 10 OPEN TOPICS',
            style: {
            color: '#797979',
            fontSize: '14px',
            fontWeight: '600',
            }
    },
    xAxis: {
        categories: [<?php foreach ($tresults as $tresult) {echo "'".$tresult['TOPIC']."',";}?>]
    },
    yAxis: [{
        title: {
            text: ''
        }
    }, {
        title: {
            text: ''
        },
        minPadding: 0,
        maxPadding: 0,
        max: 100,
        min: 0,
        opposite: true,
        labels: {
            format: "{value}%"
        }
    }],
    credits: false,
    series: [{
        type: 'pareto',
        name: 'Pareto',
        yAxis: 1,
        zIndex: 10,
        baseSeries: 1
    }, {
        name: 'Tickets',
        type: 'column',
        zIndex: 2,
        data: [<?php foreach ($tresults as $tresult) {echo $tresult['COUNT'].',';} ?>]
    }]
});

});        
    
//Top 10 Closed
  
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
?>


$(function() {        
 Highcharts.chart('toptenclosedtopic-container', {

    chart: {
        renderTo: 'toptenclosedtopic-container',
        type: 'column'
    },
    title: {
        text: 'TOP 10 CLOSED TOPICS',
            style: {
            color: '#797979',
            fontSize: '14px',
            fontWeight: '600',
            }
    },
    xAxis: {
        categories: [<?php foreach ($tresults as $tresult) {echo "'".$tresult['TOPIC']."',";}?>]
    },
    yAxis: [{
        title: {
            text: ''
        }
    }, {
        title: {
            text: ''
        },
        minPadding: 0,
        maxPadding: 0,
        max: 100,
        min: 0,
        opposite: true,
        labels: {
            format: "{value}%"
        }
    }],
    credits: false,
    series: [{
        type: 'pareto',
        name: 'Pareto',
        yAxis: 1,
        zIndex: 10,
        baseSeries: 1
    }, {
        name: 'Tickets',
        type: 'column',
        zIndex: 2,
        data: [<?php foreach ($tresults as $tresult) {echo $tresult['COUNT'].',';} ?>]
    }]
});

});        
        

//Top 10 Closed prior
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
?>

$(function() {
 Highcharts.chart('toptenclosedpytopic-chart-container', {

    chart: {
        renderTo: 'toptenclosedpytopic-chart-container',
        type: 'column'
    },
    title: {
        text: 'TOP 10 CLOSED PRIOR YEAR TOPICS',
            style: {
            color: '#797979',
            fontSize: '14px',
            fontWeight: '600',
            }
    },
    xAxis: {
        categories: [<?php foreach ($ptresults as $tresult) {echo "'".$tresult['TOPIC']."',";}?>]
    },
    yAxis: [{
        title: {
            text: ''
        }
    }, {
        title: {
            text: ''
        },
        minPadding: 0,
        maxPadding: 0,
        max: 100,
        min: 0,
        opposite: true,
        labels: {
            format: "{value}%"
        }
    }],
    credits: false,
    series: [{
        type: 'pareto',
        name: 'Pareto',
        yAxis: 1,
        zIndex: 10,
        baseSeries: 1
    }, {
        name: 'Tickets',
        type: 'column',
        zIndex: 2,
        data: [<?php foreach ($ptresults as $tresult) {echo $tresult['COUNT'].',';} ?>]
    }]
});

	
	});
//Top 10 Open by Associate
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
   ?>     
$(function() {
     Highcharts.chart('toptenopenbyassociate-chart-container', {

        chart: {
            renderTo: 'toptenopenbyassociate-chart-container',
            type: 'column'
        },
        title: {
            text: 'TOP 10 OPEN BY ASSOCIATE',
                style: {
                color: '#797979',
                fontSize: '14px',
                fontWeight: '600',
                }
        },
        xAxis: {
            categories: [<?php foreach ($tresults as $tresult) {echo "'".$tresult['ASSOCIATE']."',";}?>]
        },
        yAxis: [{
            title: {
                text: ''
            }
        }, {
            title: {
                text: ''
            },
            minPadding: 0,
            maxPadding: 0,
            max: 100,
            min: 0,
            opposite: true,
            labels: {
                format: "{value}%"
            }
        }],
        credits: false,
        series: [{
            type: 'pareto',
            name: 'Pareto',
            yAxis: 1,
            zIndex: 10,
            baseSeries: 1
        }, {
            name: 'Tickets',
            type: 'column',
            zIndex: 2,
            data: [<?php foreach ($tresults as $tresult) {echo $tresult['COUNT'].',';} ?>]
        }]
    });
});

 //Top 10 Closed Associate
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
   ?>     
$(function() {
     Highcharts.chart('toptenclosebyassociate-chart-container', {

        chart: {
            renderTo: 'toptenclosebyassociate-chart-container',
            type: 'column'
        },
        title: {
            text: 'TOP 10 CLOSED BY ASSOCIATE',
                style: {
                color: '#797979',
                fontSize: '14px',
                fontWeight: '600',
                }
        },
        xAxis: {
            categories: [<?php foreach ($tresults as $tresult) {echo "'".$tresult['ASSOCIATE']."',";}?>]
        },
        yAxis: [{
            title: {
                text: ''
            }
        }, {
            title: {
                text: ''
            },
            minPadding: 0,
            maxPadding: 0,
            max: 100,
            min: 0,
            opposite: true,
            labels: {
                format: "{value}%"
            }
        }],
        credits: false,
        series: [{
            type: 'pareto',
            name: 'Pareto',
            yAxis: 1,
            zIndex: 10,
            baseSeries: 1
        }, {
            name: 'Tickets',
            type: 'column',
            zIndex: 2,
            data: [<?php foreach ($tresults as $tresult) {echo $tresult['COUNT'].',';} ?>]
        }]
    });
});
		
   

//Backlog
$(function() {

    Highcharts.chart('backlog-chart-container', {
        chart: {
            type: 'pie',
            options3d: {
                enabled: true,
                alpha: 45,
                beta: 0
            }
        },
        title: {
            text: 'BACKLOG',
            style: {
            color: '#797979',
            fontSize: '14px',
            fontWeight: '600',
            }
        },
        credits: false,
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b> <b> ({point.y})</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                depth: 35,
                dataLabels: {
                    enabled: true,
                    format: '{point.name}'
                }
            }
        },
        series: [{
            type: 'pie',
            name: 'Backlog',
            data: [
            <?php if ($BacklogTickets["CAN"]) { ?>
            ["CAN", <?php echo $BacklogTickets["CAN"]; ?>],
            <?php } if ($BacklogTickets["BRY"]) { ?>
            ["BRY", <?php echo $BacklogTickets["BRY"]; ?>],              
            <?php } if ($BacklogTickets["EXT"]) { ?>
            ["EXT", <?php echo $BacklogTickets["EXT"]; ?>], 
            <?php } if ($BacklogTickets["IND"]) { ?>
            ["IND", <?php echo $BacklogTickets["IND"]; ?>], 
            <?php } if ($BacklogTickets["MEX"]) { ?>
            ["MEX", <?php echo $BacklogTickets["MEX"]; ?>], 
            <?php } if ($BacklogTickets["NTC"]) { ?>
            ["NTC", <?php echo $BacklogTickets["NTC"]; ?>], 
            <?php } if ($BacklogTickets["OH"]) { ?>
            ["OH", <?php echo $BacklogTickets["OH"]; ?>],
            <?php } if ($BacklogTickets["PAU"]) { ?>
            ["PAU", <?php echo $BacklogTickets["PAU"]; ?>],
            <?php } if ($BacklogTickets["RTA"]) { ?>
            ["RTA", <?php echo $BacklogTickets["RTA"]; ?>], 
            <?php } if ($BacklogTickets["RTC"]) { ?>
            ["RTC", <?php echo $BacklogTickets["RTC"]; ?>],         
             <?php } if ($BacklogTickets["RVC"]) { ?>
            ["RVC", <?php echo $BacklogTickets["RVC"]; ?>],           
            <?php } if ($BacklogTickets["SS"]) { ?>
            ["SS", <?php echo $BacklogTickets["SS"]; ?>], 
            <?php } if ($BacklogTickets["TNN1"]) { ?>   
            ["TNN1", <?php echo $BacklogTickets["TNN1"]; ?>], 
            <?php } if ($BacklogTickets["TNN2"]) { ?>
            ["TNN2", <?php echo $BacklogTickets["TNN2"]; ?>], 
            <?php } if ($BacklogTickets["TNS"]) { ?>
            ["TNS", <?php echo $BacklogTickets["TNS"]; ?>],
            <?php } if ($BacklogTickets["VIP"]) { ?>
            ["VIP", <?php echo $BacklogTickets["VIP"]; ?>],
            <?php } ?>
            ]
        }]
    });
});

		
//Tickets By Status
$(function() {

    Highcharts.chart('ticketsbystatus-chart-container', {
        chart: {
            type: 'pie',
            options3d: {
                enabled: true,
                alpha: 45,
                beta: 0
            }
        },
        title: {
            text: 'TICKETS (BY STATUS)',
            style: {
            color: '#797979',
            fontSize: '14px',
            fontWeight: '600',
            }
        },
        credits: false,
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b> <b> ({point.y})</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                depth: 35,
                dataLabels: {
                    enabled: true,
                    format: '{point.name}'
                }
            }
        },
        series: [{
            type: 'pie',
            name: 'Backlog',
            data: [
            <?php if ($UnassignedTickets) { ?>
            ["Unassigned", <?php echo $UnassignedTickets; ?>],
            <?php } if ($HeldTickets) { ?>
            ["Held", <?php echo $HeldTickets; ?>],             
            <?php } if ($ReplyTickets) { ?>
            ["Agent Action", <?php echo $ReplyTickets; ?>],
            <?php } if ($TheirReplyTickets) { ?>
            ["Submitter Action", <?php echo $TheirReplyTickets; ?>],
            <?php } if ($ThridPartyTicketsTickets) { ?>
            ["3rd Party", <?php echo $ThridPartyTicketsTickets; ?>] 
             <?php } ?>
            ]
        }]
    });
});
//My Tickets By Status

$(function() {

    Highcharts.chart('myticketsbystatus-chart-container', {
        chart: {
            type: 'pie',
            options3d: {
                enabled: true,
                alpha: 45,
                beta: 0
            }
        },
        title: {
            text: 'MY TICKETS (BY STATUS)',
            style: {
            color: '#797979',
            fontSize: '14px',
            fontWeight: '600',
            }
        },
        credits: false,
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b> <b> ({point.y})</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                depth: 35,
                dataLabels: {
                    enabled: true,
                    format: '{point.name}'
                }
            }
        },
        series: [{
            type: 'pie',
            name: 'Backlog',
            data: [
            
            <?php if ($MyHeldTickets) { ?>
            ["Held", <?php echo $MyHeldTickets; ?>],             
            <?php } if ($MyReplyTickets) { ?>
            ["Agent Action", <?php echo $MyReplyTickets; ?>],
            <?php } if ($MyTheirReplyTickets) { ?>
            ["Submitter Action", <?php echo $MyTheirReplyTickets; ?>],
            <?php } if ($MyThridPartyTicketsTickets) { ?>
            ["3rd Party", <?php echo $MyThridPartyTicketsTickets; ?>] 
             <?php } ?>
            ]
        }]
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
                WHERE ost_ticket.topic_id != 12 and ost_ticket.topic_id != 14 and ost_ticket.status_id != 3 and ost_ticket.status_id != 12  order by STATUS";
        
        $cstatuses = db_query($sql); 
        
        $sql="select sum(count) as COUNT, STATUS, OWNER_NAME from(
                select sum(count) as COUNT, STATUS, OWNER_NAME from
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
                            group by STATUS,LASTNAME
                union all 
                select b.count as COUNT, a.name as STATUS, b.OWNER_NAME  from (select 0 as count, name  from ost_ticket_status where id != 3 and id != 12 and id != 1 and id != 2 and id != 4 and id != 5) a join 

                (select distinct CONCAT(ost_staff.lastname, ', ', ost_staff.firstname) as OWNER_NAME, 0 as count
                FROM (ost_ticket LEFT JOIN ost_ticket_status ON ost_ticket.status_id = ost_ticket_status.id)
                 LEFT JOIN ost_staff ON ost_ticket.staff_id = ost_staff.staff_id WHERE ost_ticket.topic_id != 12 and 
                 ost_ticket.topic_id != 14 and ost_ticket.status_id != 3 and ost_ticket.status_id != 12 and lastname is not null) b  on a.count = b.count)d   Group by Status, OWNER_NAME";
            
        $ctechsdata = db_query($sql);
        
        
         $sql="select distinct OWNER_NAME from (select sum(count) as COUNT, STATUS, OWNER_NAME,LASTNAME from
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
            group by STATUS,LASTNAME order by lastname) OWNER";
        
        $ctechs = db_query($sql);
        
        $sql="select sum(count) as COUNT, STATUS, LOCATION from (select sum(COUNT) as COUNT, STATUS, LOCATION from
                (Select COUNT(STATUS) as COUNT,STATUS, LOCATION from
                    (SELECT ost_ticket.number as Ticket, ost_ticket_status.name as STATUS, ost_organization.name as LOCATION
                    FROM ((ost_ticket LEFT JOIN ost_ticket_status ON ost_ticket.status_id = ost_ticket_status.id)
                    LEFT JOIN (ost_user LEFT JOIN ost_organization ON ost_user.org_id = ost_organization.id) ON ost_ticket.user_id = ost_user.id)
                    LEFT JOIN ost_staff ON ost_ticket.staff_id = ost_staff.staff_id WHERE ost_ticket.topic_id != 12 and ost_ticket.topic_id != 14 and ost_ticket.status_id != 3 and ost_ticket.status_id != 12) A
                    where LOCATION is not null
                Group by LOCATION,  STATUS ) a
              group by LOCATION, STATUS
              
              union all 
                select b.count as COUNT, a.name as STATUS, b.LOCATION  from (select 0 as count, name  from ost_ticket_status where id != 3 and id != 12 and id != 1 and id != 2 and id != 4 and id != 5) a join 
                
                (SELECT  distinct ost_organization.name as LOCATION, 0 as count
                    FROM ((ost_ticket LEFT JOIN ost_ticket_status ON ost_ticket.status_id = ost_ticket_status.id)
                    LEFT JOIN (ost_user LEFT JOIN ost_organization ON ost_user.org_id = ost_organization.id) ON ost_ticket.user_id = ost_user.id)
                    LEFT JOIN ost_staff ON ost_ticket.staff_id = ost_staff.staff_id WHERE ost_ticket.topic_id != 12 and ost_ticket.topic_id != 14 and ost_ticket.status_id != 3 and ost_ticket.status_id != 12)b   
                    Group by Status, LOCATION)d where location is not null Group by Status, LOCATION";

$clocsdata = db_query($sql);
        
?>


//Status by tech

$(function () {
    
    
    Highcharts.chart('statusbyagent-chart-container1', {
        chart: {
            type: 'column',
            marginRight: 180
        },
        title: {
            text: 'TICKETS (TECH BY STATUS)',
            style: {
            color: '#797979',
            fontSize: '14px',
            fontWeight: '600',
            }
        },
        credits: false,
        xAxis: {
            categories: [<?php
  foreach ($cstatuses as $cstatus) {
             
             echo "'".preg_replace('/\s+/', ' ', $cstatus["STATUS"])."',";
   }   
   ?>]
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Total Tickets'
            },
            stackLabels: {
                enabled: true,
                style: {
                    fontWeight: 'bold',
                    color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                }
            }
        },
        legend: {
            align: 'right',
            x: -20,
            verticalAlign: 'top',
            layout: 'verticle',
            y: 30,
            floating: true,
            backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
            borderColor: '#CCC',
            borderWidth: 1,
            shadow: true
        },
        tooltip: {
            headerFormat: '<b>{point.x}</b><br/>',
            pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
        },
        plotOptions: {
            column: {
                stacking: 'normal',
                dataLabels: {
                    enabled: true,
                    color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white'
                }
            }
        },
        series: [
        
        <?php
        foreach ($ctechs as $ctech) { ?>
        
        {
            name: '<?php echo $ctech["OWNER_NAME"]?>',
            data: [<?php foreach ($ctechsdata as $techsdata) {

                if ($techsdata["OWNER_NAME"] == $ctech["OWNER_NAME"]) echo $techsdata["COUNT"].',';
            }?>]
        }, 
        
        <?php } ?>
        ]
    });

});

$(function () {
    
    
    Highcharts.chart('statusbyagent-chart-container2', {
        chart: {
            type: 'column',
            marginRight: 230
        },
        title: {
            text: 'TICKETS (STATUS BY TECH)',
            style: {
            color: '#797979',
            fontSize: '14px',
            fontWeight: '600',
            }
        },
        credits: false,
        xAxis: {
            categories: [<?php
  foreach ($ctechs as $ctech) {
             
             echo "'".preg_replace('/\s+/', ' ', $ctech["OWNER_NAME"])."',";
   }   
   ?>]
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Total Tickets'
            },
            stackLabels: {
                enabled: true,
                style: {
                    fontWeight: 'bold',
                    color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                }
            }
        },
        legend: {
            align: 'right',
            x: -0,
            verticalAlign: 'top',
            layout: 'verticle',
            y: 30,
            floating: true,
            backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
            borderColor: '#CCC',
            borderWidth: 1,
            shadow: true
        },
        tooltip: {
            headerFormat: '<b>{point.x}</b><br/>',
            pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
        },
        plotOptions: {
            column: {
                stacking: 'normal',
                dataLabels: {
                    enabled: true,
                     formatter: function(){
                    console.log(this);
                    var val = this.y;
                    if (val < 2) {
                        return '';
                    }
                    return val;
                },
                    color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white'
                }
            }
        },
        series: [
        
        <?php
        foreach ($cstatuses as $cstatus) { ?>
        
        {
            name: '<?php echo $cstatus["STATUS"]?>',
            data: [<?php foreach ($ctechsdata as $techsdata) {

                if ($techsdata["STATUS"] == $cstatus["STATUS"]) echo $techsdata["COUNT"].',';
            }?>]
        }, 
        
        <?php } ?>
        ]
    });

});



//Status by Location
$(function () {
    
    
    Highcharts.chart('statusbylocation-chart-container1', {
        chart: {
            type: 'column',
            marginRight: 180
        },
        title: {
            text: 'TICKETS (LOCATION BY STATUS)',
            style: {
            color: '#797979',
            fontSize: '14px',
            fontWeight: '600',
            }
        },
        credits: false,
        xAxis: {
            categories: [<?php
  foreach ($cstatuses as $cstatus) {
             
             echo "'".preg_replace('/\s+/', ' ', $cstatus["STATUS"])."',";
   }   
   ?>]
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Total Tickets'
            },
            stackLabels: {
                enabled: true,
                style: {
                    fontWeight: 'bold',
                    color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                }
            }
        },
        legend: {
            align: 'right',
            x: -30,
            verticalAlign: 'top',
            layout: 'verticle',
            y: 30,
            floating: true,
            backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
            borderColor: '#CCC',
            borderWidth: 1,
            shadow: true
        },
        tooltip: {
            headerFormat: '<b>{point.x}</b><br/>',
            pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
        },
        plotOptions: {
            column: {
                stacking: 'normal',
                dataLabels: {
                    enabled: true,
                     formatter: function(){
                    console.log(this);
                    var val = this.y;
                    if (val < 2) {
                        return '';
                    }
                    return val;
                },
                    color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white'
                }
            }
        },
        series: [
        
         <?php
        foreach ($locs as $loc) { ?>
        
        {
            name: '<?php echo $loc["LOCATION"]?>',
            data: [<?php foreach ($clocsdata as $locsdata) {

                if ($locsdata["LOCATION"] == $loc["LOCATION"]) echo $locsdata["COUNT"].',';
            }?>]
        }, 
        
        <?php } ?>
        ]
    });

});

$(function () {
    
    
    Highcharts.chart('statusbylocation-chart-container2', {
        chart: {
            type: 'column',
            marginRight: 230
        },
        title: {
            text: 'TICKETS (STATUS BY LOCATION)',
            style: {
            color: '#797979',
            fontSize: '14px',
            fontWeight: '600',
            }
        },
        credits: false,
        xAxis: {
            categories: [<?php
  foreach ($locs as $loc) {
             
             echo "'".preg_replace('/\s+/', ' ', $loc["LOCATION"])."',";
   }   
   ?>]
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Total Tickets'
            },
            stackLabels: {
                enabled: true,
                style: {
                    fontWeight: 'bold',
                    color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                }
            }
        },
        legend: {
            align: 'right',
            x: -5,
            verticalAlign: 'top',
            layout: 'verticle',
            y: 30,
            floating: true,
            backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
            borderColor: '#CCC',
            borderWidth: 1,
            shadow: true
        },
        tooltip: {
            headerFormat: '<b>{point.x}</b><br/>',
            pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
        },
        plotOptions: {
            column: {
                stacking: 'normal',
                dataLabels: {
                    enabled: true,
                     formatter: function(){
                    console.log(this);
                    var val = this.y;
                    if (val < 1) {
                        return '';
                    }
                    return val;
                },
                    color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white'
                }
            }
        },
        series: [
        
         <?php
        foreach ($cstatuses as $cstatus) { ?>
        
        {
            name: '<?php echo $cstatus["STATUS"]?>',
            data: [<?php foreach ($clocsdata as $locsdata) {

                if ($locsdata["STATUS"] == $cstatus["STATUS"]) echo $locsdata["COUNT"].',';
            }?>]
        }, 
        
        <?php } ?>
        ]
    });

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

$sql="select distinct cat from (select concat(MONTHNAME(STR_TO_DATE(CALENDARWEEK, '%m')),' ',CALENDARYEAR) as cat, count(OWNER_NAME) as COUNT,OWNER_NAME from
	(
	SELECT  month(FROM_DAYS(TO_DAYS(t.closed) - MOD(TO_DAYS(t.closed) - 2, 7))) AS CALENDARWEEK,YEAR(FROM_DAYS(TO_DAYS(t.closed) - MOD(TO_DAYS(t.closed) - 2, 7))) AS CALENDARYEAR, 
    concat(u.lastname, ', ', u.firstname) AS OWNER_NAME, s.name as STATUS FROM ost_ticket t 
	left join ost_staff u on u.staff_id = t.staff_id 
	left join ost_ticket_status s on s.id = t.status_id


	where t.status_id = 3 AND t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and t.closed >(CURDATE() - INTERVAL 11 MONTH)
	) a

	group by OWNER_NAME, CALENDARYEAR, CALENDARWEEK order by CALENDARYEAR,CALENDARWEEK)a";
    
 $periods = db_query($sql);   

$sql="select * from (select cat,sum(COUNT) as COUNT, OWNER_NAME,CALENDARWEEK,CALENDARYEAR from (select concat(MONTHNAME(STR_TO_DATE(CALENDARWEEK, '%m')),' ',CALENDARYEAR) as cat, count(OWNER_NAME) as COUNT,OWNER_NAME, CALENDARWEEK,CALENDARYEAR from
	(
	SELECT  month(FROM_DAYS(TO_DAYS(t.closed) - MOD(TO_DAYS(t.closed) - 2, 7))) AS CALENDARWEEK,YEAR(FROM_DAYS(TO_DAYS(t.closed) - MOD(TO_DAYS(t.closed) - 2, 7))) AS CALENDARYEAR, 
    concat(u.lastname, ', ', u.firstname) AS OWNER_NAME, s.name as STATUS FROM ost_ticket t 
	left join ost_staff u on u.staff_id = t.staff_id 
	left join ost_ticket_status s on s.id = t.status_id


	where t.status_id = 3 AND t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and t.closed >(CURDATE() - INTERVAL 11 MONTH)
	) a

	group by OWNER_NAME, CALENDARYEAR, CALENDARWEEK
    
    union all
    
    select distinct cat, 0 as COUNT, b.OWNER_NAME,CALENDARWEEK,CALENDARYEAR   from (select concat(MONTHNAME(STR_TO_DATE(CALENDARWEEK, '%m')),' ',CALENDARYEAR) as cat, count(OWNER_NAME) as COUNT,OWNER_NAME, CALENDARWEEK,CALENDARYEAR  from
	(
	SELECT  month(FROM_DAYS(TO_DAYS(t.closed) - MOD(TO_DAYS(t.closed) - 2, 7))) AS CALENDARWEEK,YEAR(FROM_DAYS(TO_DAYS(t.closed) - MOD(TO_DAYS(t.closed) - 2, 7))) AS CALENDARYEAR,
    concat(u.lastname, ', ', u.firstname) AS OWNER_NAME, s.name as STATUS FROM ost_ticket t 
	left join ost_staff u on u.staff_id = t.staff_id 
	left join ost_ticket_status s on s.id = t.status_id


	where t.status_id = 3 AND t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and t.closed >(CURDATE() - INTERVAL 11 MONTH)
	) a

	group by OWNER_NAME, CALENDARYEAR, CALENDARWEEK)a left join
    
    (SELECT distinct
    concat(u.lastname, ', ', u.firstname) AS OWNER_NAME FROM ost_ticket t 
	left join ost_staff u on u.staff_id = t.staff_id 
	left join ost_ticket_status s on s.id = t.status_id
	where t.status_id = 3 AND t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and t.closed >(CURDATE() - INTERVAL 11 MONTH))b on 1=1) dat
    
    group by  cat, OWNER_NAME) datb order by CALENDARYEAR, CALENDARWEEK";

    $techsdata = db_query($sql);
    
    
    $sql="SELECT distinct
    concat(u.lastname, ', ', u.firstname) AS OWNER_NAME FROM ost_ticket t 
	left join ost_staff u on u.staff_id = t.staff_id 
	left join ost_ticket_status s on s.id = t.status_id
	where t.status_id = 3 AND t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and t.closed >(CURDATE() - INTERVAL 11 MONTH)";

    $techs = db_query($sql);
    
    
?>

$(function () {
    Highcharts.chart('closedbytech-chart-container1', {
        chart: {
            type: 'spline'
        },
        title: {
            text: 'TICKETS CLOSED (TECH 1 YEARS)',
            style: {
            color: '#797979',
            fontSize: '14px',
            fontWeight: '600',
            }
        },

        xAxis: {
            categories: [<?php
      foreach ($periods as $period) {
                 
                 echo "'".preg_replace('/\s+/', ' ', $period["cat"])."',";
       }   
       ?>]
        },
        yAxis: {
            title: {
                text: 'Number of Tickets'
            }
        },
        plotOptions: {
        spline: {
            marker: {
                radius: 4,
                lineColor: '#666666',
                lineWidth: 1
            }
        }
        },
        
               series: [
        
         <?php
        foreach ($techs as $tech) { ?>
        
        {
            name: '<?php echo $tech["OWNER_NAME"]?>',
            data: [<?php foreach ($techsdata as $techdata) {

                if ($techdata["OWNER_NAME"] == $tech["OWNER_NAME"]) echo $techdata["COUNT"].',';
            }?>]
        }, 
        
        <?php } ?>
        ]


    });
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

$sql="select * from (select cat,sum(COUNT) as COUNT, LOCATION,CALENDARWEEK,CALENDARYEAR from (
	select concat(MONTHNAME(STR_TO_DATE(CALENDARWEEK, '%m')),' ',CALENDARYEAR) as cat, count(STATUS) as COUNT,LOCATION, CALENDARWEEK,CALENDARYEAR from
	(
		SELECT  month(t.created) AS CALENDARWEEK,year(t.created) AS CALENDARYEAR, 
		o.name AS LOCATION, s.name as STATUS FROM ost_ticket t 
		left join ost_user u on u.id = t.user_id 
		left join ost_organization o on o.id = u.org_id
		left join ost_ticket_status s on s.id = t.status_id
		where t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and t.created >(CURDATE() - INTERVAL 11 MONTH)
	) a

	group by LOCATION, CALENDARYEAR, CALENDARWEEK
    
    union all
    
    select distinct cat, 0 as COUNT, b.LOCATION,CALENDARWEEK,CALENDARYEAR   from (select concat(MONTHNAME(STR_TO_DATE(CALENDARWEEK, '%m')),' ',CALENDARYEAR) as cat, count(STATUS) as COUNT,LOCATION, CALENDARWEEK,CALENDARYEAR  from
	(
	SELECT  month(t.created) AS CALENDARWEEK,year(t.created) AS CALENDARYEAR,
    o.name AS LOCATION, s.name as STATUS FROM ost_ticket t 
    left join ost_user u on u.id = t.user_id 
	left join ost_organization o on o.id = u.org_id
	left join ost_ticket_status s on s.id = t.status_id


	where t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and t.created >(CURDATE() - INTERVAL 11 MONTH)
	) a

	group by LOCATION, CALENDARYEAR, CALENDARWEEK)a left join
    
    (SELECT distinct
     o.name AS LOCATION, s.name as STATUS FROM ost_ticket t 
	left join ost_user u on u.id = t.user_id 
	left join ost_organization o on o.id = u.org_id
	left join ost_ticket_status s on s.id = t.status_id

	where t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and t.created >(CURDATE() - INTERVAL 11 MONTH))b on 1=1) dat
    
    group by  cat, LOCATION) datb  Where LOCATION IS NOT NULL order by CALENDARYEAR, CALENDARWEEK
    
";

$olocsdata = db_query($sql);

$sql="select distinct concat(MONTHNAME(STR_TO_DATE(CALENDARWEEK, '%m')),' ',CALENDARYEAR) as cat  from
	(
	SELECT month(t.created) AS CALENDARWEEK,year(t.created) AS CALENDARYEAR, o.name AS LOCATION, s.name as STATUS FROM ost_ticket t 
	left join ost_user u on u.id = t.user_id 
	left join ost_organization o on o.id = u.org_id
	left join ost_ticket_status s on s.id = t.status_id


	where t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and (t.created) > (CURDATE() - INTERVAL 11 MONTH)
	) a
	where LOCATION is not null
	group by LOCATION, CALENDARWEEK,CALENDARYEAR order by CALENDARYEAR,CALENDARWEEK, LOCATION";
 
$periods = db_query($sql);   

?>


$(function () {
    Highcharts.chart('openedbylocation-chart-container1', {
        chart: {
            type: 'spline'
        },
        title: {
            text: 'TICKETS OPENED (LOCATION 1 YEARS)',
            style: {
            color: '#797979',
            fontSize: '14px',
            fontWeight: '600',
            }
        },

        xAxis: {
            categories: [<?php
      foreach ($periods as $period) {
                 
                 echo "'".preg_replace('/\s+/', ' ', $period["cat"])."',";
       }   
       ?>]
        },
        yAxis: {
            title: {
                text: 'Number of Tickets'
            }
        },
        plotOptions: {
        spline: {
            marker: {
                radius: 4,
                lineColor: '#666666',
                lineWidth: 1
            }
        }
        },
        
               series: [
        
         <?php
        foreach ($olocs as $oloc) { ?>
        
        {
            name: '<?php echo $oloc["LOCATION"]?>',
            data: [<?php foreach ($olocsdata as $olocdata) {

                if ($olocdata["LOCATION"] == $oloc["LOCATION"]) echo $olocdata["COUNT"].',';
            }?>]
        }, 
        
        <?php } ?>
        ]


    });
});  

//closed by location 2 year

<?php

$sql="select distinct LOCATION from
(
	select CALENDARWEEK, CALENDARYEAR, count(LOCATION) as COUNT, LOCATION from
	(
	SELECT month(t.closed) AS CALENDARWEEK,year(t.closed) AS CALENDARYEAR, o.name AS LOCATION, s.name as STATUS FROM ost_ticket t 
	left join ost_user u on u.id = t.user_id 
	left join ost_organization o on o.id = u.org_id
	left join ost_ticket_status s on s.id = t.status_id


	where t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and (t.closed) > (CURDATE() - INTERVAL 11 MONTH)
	) a
	where LOCATION is not null
	group by LOCATION, CALENDARWEEK,CALENDARYEAR order by CALENDARYEAR,CALENDARWEEK, LOCATION
)b ";

$olocs = db_query($sql);

$sql="select * from (select cat,sum(COUNT) as COUNT, LOCATION,CALENDARWEEK,CALENDARYEAR from (
	select concat(MONTHNAME(STR_TO_DATE(CALENDARWEEK, '%m')),' ',CALENDARYEAR) as cat, count(STATUS) as COUNT,LOCATION, CALENDARWEEK,CALENDARYEAR from
	(
		SELECT  month(t.closed) AS CALENDARWEEK,year(t.closed) AS CALENDARYEAR, 
		o.name AS LOCATION, s.name as STATUS FROM ost_ticket t 
		left join ost_user u on u.id = t.user_id 
		left join ost_organization o on o.id = u.org_id
		left join ost_ticket_status s on s.id = t.status_id
		where t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and t.closed >(CURDATE() - INTERVAL 11 MONTH)
	) a

	group by LOCATION, CALENDARYEAR, CALENDARWEEK
    
    union all
    
    select distinct cat, 0 as COUNT, b.LOCATION,CALENDARWEEK,CALENDARYEAR   from (select concat(MONTHNAME(STR_TO_DATE(CALENDARWEEK, '%m')),' ',CALENDARYEAR) as cat, count(STATUS) as COUNT,LOCATION, CALENDARWEEK,CALENDARYEAR  from
	(
	SELECT  month(t.closed) AS CALENDARWEEK,year(t.closed) AS CALENDARYEAR,
    o.name AS LOCATION, s.name as STATUS FROM ost_ticket t 
    left join ost_user u on u.id = t.user_id 
	left join ost_organization o on o.id = u.org_id
	left join ost_ticket_status s on s.id = t.status_id


	where t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and t.closed >(CURDATE() - INTERVAL 11 MONTH)
	) a

	group by LOCATION, CALENDARYEAR, CALENDARWEEK)a left join
    
    (SELECT distinct
     o.name AS LOCATION, s.name as STATUS FROM ost_ticket t 
	left join ost_user u on u.id = t.user_id 
	left join ost_organization o on o.id = u.org_id
	left join ost_ticket_status s on s.id = t.status_id

	where t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and t.closed >(CURDATE() - INTERVAL 11 MONTH))b on 1=1) dat
    
    group by  cat, LOCATION) datb  Where LOCATION IS NOT NULL order by CALENDARYEAR, CALENDARWEEK
    
";

$olocsdata = db_query($sql);

$sql="select distinct concat(MONTHNAME(STR_TO_DATE(CALENDARWEEK, '%m')),' ',CALENDARYEAR) as cat  from
	(
	SELECT month(t.closed) AS CALENDARWEEK,year(t.closed) AS CALENDARYEAR, o.name AS LOCATION, s.name as STATUS FROM ost_ticket t 
	left join ost_user u on u.id = t.user_id 
	left join ost_organization o on o.id = u.org_id
	left join ost_ticket_status s on s.id = t.status_id


	where t.topic_id <> 14 AND t.topic_id <> 12 AND t.topic_id <> 94 and (t.closed) > (CURDATE() - INTERVAL 11 MONTH)
	) a
	where LOCATION is not null
	group by LOCATION, CALENDARWEEK,CALENDARYEAR order by CALENDARYEAR,CALENDARWEEK, LOCATION";
 
$periods = db_query($sql);   

?>


$(function () {
    Highcharts.chart('closedbylocation-chart-container1', {
        chart: {
            type: 'spline'
        },
        title: {
            text: 'TICKETS CLOSED (LOCATION 1 YEARS)',
            style: {
            color: '#797979',
            fontSize: '14px',
            fontWeight: '600',
            }
        },

        xAxis: {
            categories: [<?php
      foreach ($periods as $period) {
                 
                 echo "'".preg_replace('/\s+/', ' ', $period["cat"])."',";
       }   
       ?>]
        },
        yAxis: {
            title: {
                text: 'Number of Tickets'
            }
        },
        plotOptions: {
        spline: {
            marker: {
                radius: 4,
                lineColor: '#666666',
                lineWidth: 1
            }
        }
        },
        
               series: [
        
         <?php
        foreach ($olocs as $oloc) { ?>
        
        {
            name: '<?php echo $oloc["LOCATION"]?>',
            data: [<?php foreach ($olocsdata as $olocdata) {

                if ($olocdata["LOCATION"] == $oloc["LOCATION"]) echo $olocdata["COUNT"].',';
            }?>]
        }, 
        
        <?php } ?>
        ]


    });
});  
       
    
      
</script>



