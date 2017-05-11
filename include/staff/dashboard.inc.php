<?php
      
$dqueues = CustomQueue::objects()
        ->filter(Q::any(array(
            'flags__hasbit' => CustomQueue::FLAG_PUBLIC,
            'staff_id' => $thisstaff->getId(),
        )));

    if ($ids && is_array($ids))
        $dqueues->filter(array('id__in' => $ids));

    $query = Ticket::objects();
   
    foreach ($dqueues as $dqueue) {
        $Q = $dqueue->getBasicQuery();
        if (count($Q->extra) || $Q->isWindowed()) {
            // XXX: This doesn't work
            $query->annotate(array(
                'Z'.$dqueue->title => $Q->values_flat()
                    ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')))
            ));
        }
        else {
            $expr = SqlCase::N()->when(new SqlExpr(new Q($Q->constraints)), 1);
            $query->aggregate(array(
                $dqueue->getDashboardName()=> SqlAggregate::COUNT($expr)
            ));
        }
    }    
    

$Counts = $query->values()->one();
                
?>

<style>
#dashboard .ui-icon{
    background-image: url("images/ui-icons_777777_256x240.png");
}

</style>

<script>
  $( function() {
    var icons = {
         header: "ui-icon-plus",    // custom icon class
         activeHeader: "ui-icon-minus" // custom icon class
     };
    $("#dashboard").show();
    $( "#dashboard" ).accordion({
       active: false,
       autoHeight: true,
       navigation: true,
       collapsible: true,
       icons: icons
       
    });

  } );
  </script>

 

<div id="dashboard" style="display:none">
<h3>IT Dashboard</h3>
        <table width="100%" style="font-size: smaller" cellpadding="1">
           
            <tr style="font-weight: bold; text-align: center;">
            <td rowspan="2" width="75px"><span style="color: red; font-weight: bold;">Backlog</span></td> 
                <td width="30px">CAN</td>
                <td width="30px">IND</td>
                <td width="30px">MEX</td>
                <td width="30px">OH</td>
                <td width="30px">NTC</td>
                <td width="30px">TNN1</td>
                <td width="30px">TNN2</td>
                <td width="30px">TNS</td>
                <td width="30px">TOTAL</td>
                <td></td>
            </tr>
            <tr style="text-align: center;">
                <td><?php echo $Counts["Tickets.Open Tickets.CAN"]-$Counts["Tickets.Open Tickets.CAN.Hold"]; ?></td>
                <td><?php echo $Counts["Tickets.Open Tickets.IND"]-$Counts["Tickets.Open Tickets.IND.Hold"]; ?></td>
                <td><?php echo $Counts["Tickets.Open Tickets.MEX"]-$Counts["Tickets.Open Tickets.MEX.Hold"]; ?></td>
                <td><?php echo $Counts["Tickets.Open Tickets.OH"]-$Counts["Tickets.Open Tickets.OH.Hold"]; ?></td>
                <td><?php echo $Counts["Tickets.Open Tickets.NTC"]-$Counts["Tickets.Open Tickets.NTC.Hold"]; ?></td>
                <td><?php echo $Counts["Tickets.Open Tickets.TNN1"]-$Counts["Tickets.Open Tickets.TNN1.Hold"]; ?></td>
                <td><?php echo $Counts["Tickets.Open Tickets.TNN2"]-$Counts["Tickets.Open Tickets.TNN2.Hold"]; ?></td>
                <td><?php echo $Counts["Tickets.Open Tickets.TNS"]-$Counts["Tickets.Open Tickets.TNS.Hold"]; ?></td>
                <td style="color: red; font-weight: bold;"><?php echo $Counts["Tickets.Open Tickets.CAN"]-$Counts["Tickets.Open Tickets.CAN.Hold"]+
                               $Counts["Tickets.Open Tickets.IND"]-$Counts["Tickets.Open Tickets.IND.Hold"]+
                               $Counts["Tickets.Open Tickets.MEX"]-$Counts["Tickets.Open Tickets.MEX.Hold"]+
                               $Counts["Tickets.Open Tickets.OH"]-$Counts["Tickets.Open Tickets.OH.Hold"]+
                               $Counts["Tickets.Open Tickets.NTC"]-$Counts["Tickets.Open Tickets.NTC.Hold"]+
                               $Counts["Tickets.Open Tickets.TNN1"]-$Counts["Tickets.Open Tickets.TNN1.Hold"]+
                               $Counts["Tickets.Open Tickets.TNN2"]-$Counts["Tickets.Open Tickets.TNN2.Hold"]+
                               $Counts["Tickets.Open Tickets.TNS"]-$Counts["Tickets.Open Tickets.TNS.Hold"]?></td>
                        <td></td>
            </tr>
        </table>
</div>