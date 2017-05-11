<?php
     
$OpenTickets = array(); 
$orgs = Organization::objects();
   
   $orgs->values('id','name');
   foreach ($orgs as $org) {
     //echo $org['id'];  
   

    $OpenTicket = Ticket::objects()
        ->filter(array('user__org_id' => $org['id']))
        ->filter(array('status_id__ne' => '3')) //closed
        ->filter(array('status_id__ne' => '3')) //closed
        ->filter(array('status_id__ne' => '12')) //autoclosed
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
 
        foreach ($OpenTicket as $orgOpenTicket) { 
            $OpenTickets[$org['name']] = $orgOpenTicket["count"];
        }
}        
                
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
                <td><?php echo $OpenTickets["CAN"] ?></td>
                <td><?php echo $OpenTickets["IND"]; ?></td>
                <td><?php echo $OpenTickets["MEX"]; ?></td>
                <td><?php echo $OpenTickets["OH"]; ?></td>
                <td><?php echo $OpenTickets["NTC"]; ?></td>
                <td><?php echo $OpenTickets["TNN1"]; ?></td>
                <td><?php echo $OpenTickets["TNN2"]; ?></td>
                <td><?php echo $OpenTickets["TNS"]; ?></td>
                <td><?php echo $OpenTickets["CAN"]+
                               $OpenTickets["IND"]+
                               $OpenTickets["MEX"]+
                               $OpenTickets["OH"]+
                               $OpenTickets["NTC"]+
                               $OpenTickets["TNN1"]+
                               $OpenTickets["TNN2"]+
                               $OpenTickets["TNS"];?></td>
                 <td></td>
            </tr>
        </table>
</div>