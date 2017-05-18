<?php
//Total Open
$OpenTicket = Ticket::objects()
        ->filter(array('status_id__ne' => '3')) //closed
        ->filter(array('status_id__ne' => '12')) //autoclosed
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($OpenTicket as $cOpenTicket) { 
            $OpenTickets = $cOpenTicket["count"];
        }
$AssignedTicket = Ticket::objects()
        ->filter(array('status_id' => '11')) //assigned
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($AssignedTicket as $cAssignedTicket) { 
            $AssignedTickets = $cAssignedTicket["count"];
        }        
 
$HeldTicket = Ticket::objects()
        ->filter(array('status_id' => '8')) //held
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($HeldTicket as $cHeldTicket) { 
            $HeldTickets = $cHeldTicket["count"];
        }        
        
$ReplyTicket = Ticket::objects()
        ->filter(array('status_id' => '7')) //Awaiting Agent Reply
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($ReplyTicket as $cReplyTicket) { 
            $ReplyTickets = $cReplyTicket["count"];
        }              

$TheirReplyTicket = Ticket::objects()
        ->filter(array('status_id' => '6')) //Awaiting Submitter Reply
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($TheirReplyTicket as $cTheirReplyTicket) { 
            $TheirReplyTickets = $cTheirReplyTicket["count"];
        }           
        
$ImplementationTicket = Ticket::objects()
        ->filter(array('status_id' => '9')) //Awaiting Implementation
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($ImplementationTicket as $cImplementationTicket) { 
            $ImplementationTickets = $cImplementationTicket["count"];
        }

$AwaitingQuoteTicket = Ticket::objects()
        ->filter(array('status_id' => '10')) //Awaiting Quote
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($AwaitingQuoteTicket as $cAwaitingQuoteTicket) { 
            $AwaitingQuoteTickets = $cAwaitingQuoteTicket["count"];
        }
        
//My Tickets 
$MyOpenTicket = Ticket::objects()
        ->filter(array('staff_id' => $thisstaff->staff_id)) //this staff
        ->filter(array('status_id__ne' => '3')) //closed
        ->filter(array('status_id__ne' => '12')) //autoclosed
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($MyOpenTicket as $cMyOpenTicket) { 
            $MyOpenTickets = $cMyOpenTicket["count"];
        }       
$MyAssignedTicket = Ticket::objects()
        ->filter(array('staff_id' => $thisstaff->staff_id)) //this staff
        ->filter(array('status_id' => '11')) //assigned
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($MyAssignedTicket as $cMyAssignedTicket) { 
            $MyAssignedTickets = $cMyAssignedTicket["count"];
        }        
 
$MyHeldTicket = Ticket::objects()
        ->filter(array('staff_id' => $thisstaff->staff_id)) //this staff
        ->filter(array('status_id' => '8')) //held
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($MyHeldTicket as $cMyHeldTicket) { 
            $MyHeldTickets = $cMyHeldTicket["count"];
        }        
        
$MyReplyTicket = Ticket::objects()
        ->filter(array('staff_id' => $thisstaff->staff_id)) //this staff
        ->filter(array('status_id' => '7')) //Awaiting Agent Reply
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($MyReplyTicket as $cMyReplyTicket) { 
            $MyReplyTickets = $cMyReplyTicket["count"];
        }              

$MyTheirReplyTicket = Ticket::objects()
        ->filter(array('staff_id' => $thisstaff->staff_id)) //this staff
        ->filter(array('status_id' => '6')) //Awaiting Submitter Reply
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($MyTheirReplyTicket as $cMyTheirReplyTicket) { 
            $MyTheirReplyTickets = $cMyTheirReplyTicket["count"];
        }           
        
$MyImplementationTicket = Ticket::objects()
        ->filter(array('staff_id' => $thisstaff->staff_id)) //this staff
        ->filter(array('status_id' => '9')) //Awaiting Implementation
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($MyImplementationTicket as $cMyImplementationTicket) { 
            $MyImplementationTickets = $cMyImplementationTicket["count"];
        }

$MyAwaitingQuoteTicket = Ticket::objects()
        ->filter(array('staff_id' => $thisstaff->staff_id)) //this staff
        ->filter(array('status_id' => '10')) //Awaiting Quote
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($MyAwaitingQuoteTicket as $cMyAwaitingQuoteTicket) { 
            $MyAwaitingQuoteTickets = $cMyAwaitingQuoteTicket["count"];
        }
        
//Issues
$OpenIssuesTicket = Ticket::objects()
        ->filter(array('status_id__ne' => '3')) //Awaiting Quote
        ->filter(array('topic_id' => '12')) //open issue
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($OpenIssuesTicket as $cOpenIssuesTicket) { 
            $OpenIssuesTickets = $cOpenIssuesTicket["count"];
        }       

$MyOpenIssuesTicket = Ticket::objects()
        ->filter(array('staff_id' => $thisstaff->staff_id)) //this staff
        ->filter(array('status_id__ne' => '3')) //Awaiting Quote
        ->filter(array('topic_id' => '12')) //open issue
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($MyOpenIssuesTicket as $cMyOpenIssuesTicket) { 
            $MyOpenIssuesTickets = $cMyOpenIssuesTicket["count"];
        }        

$UnassignedTicket = Ticket::objects()
        ->filter(array('status_id' => '1')) //Awaiting Quote
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($UnassignedTicket as $cUnassignedTicket) { 
            $UnassignedTickets = $cUnassignedTicket["count"];
        } 
        
$SuggestionAssignedTicket = Ticket::objects()
        ->filter(array('status_id' => '11')) //assigned
        ->filter(array('topic_id' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($SuggestionAssignedTicket as $cSuggestionAssignedTicket) { 
            $SuggestionAssignedTickets = $cSuggestionAssignedTicket["count"];
        }
        
$SuggestionImplementationTicket = Ticket::objects()
        ->filter(array('status_id' => '9')) //Awaiting Implementation
        ->filter(array('topic_id' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($SuggestionImplementationTicket as $cSuggestionImplementationTicket) { 
            $SuggestionImplementationTickets = $cSuggestionImplementationTicket["count"];
        }

$SuggestionAwaitingQuoteTicket = Ticket::objects()
        ->filter(array('status_id' => '10')) //Awaiting Quote
        ->filter(array('topic_id' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($SuggestionAwaitingQuoteTicket as $cSuggestionAwaitingQuoteTicket) { 
            $SuggestionAwaitingQuoteTickets = $cSuggestionAwaitingQuoteTicket["count"];
        }
//Backlog     
$BacklogTickets = array(); 
$orgs = Organization::objects();
   
   $orgs->values('id','name');
   foreach ($orgs as $org) {
     //echo $org['id'];  
   

    $OpenTicket = Ticket::objects()
        ->filter(array('user__org_id' => $org['id']))
        ->filter(array('status_id__ne' => '8')) //hold
        ->filter(array('status_id__ne' => '3')) //closed
        ->filter(array('status_id__ne' => '12')) //autoclosed
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
 
        foreach ($OpenTicket as $orgOpenTicket) { 
            $BacklogTickets[$org['name']] = $orgOpenTicket["count"];
        }
}        
                
?>

<div class="dashboard" >
<div id="title"><h3>IT Dashboard</h3></div>
<div id="table">
<table width="100%" style="font-size: smaller" cellspacing="0">
    <tr>
        <td>
            <table width="100%" style="font-size: smaller" cellspacing="0" border = "0">           
                <tr style="font-weight: bold; text-align: center;">
                    <td></td>
                    <td width="30px">CAN</td>
                    <td width="30px">IND</td>
                    <td width="30px">MEX</td>
                    <td width="30px">NTC</td>
                    <td width="30px">OH</td>
                    <td width="30px">TNN1</td>
                    <td width="30px">TNN2</td>
                    <td width="30px">TNS</td>
                    <td rowspan = "3" width="55px" style="font-size: xx-large; text-align: center; color: #ff0202; font-weight: bold;"><a href="/scp/tickets.php?queue=199&p=1" style="color:#ff0202"><?php echo $BacklogTickets["CAN"]+
                                   $BacklogTickets["IND"]+
                                   $BacklogTickets["MEX"]+
                                   $BacklogTickets["OH"]+
                                   $BacklogTickets["NTC"]+
                                   $BacklogTickets["TNN1"]+
                                   $BacklogTickets["TNN2"]+
                                   $BacklogTickets["TNS"];?></a></td>
                    <td rowspan="3" width="2px">&nbsp;</td>
                    <td rowspan="3" width="5px"  style="border-right: 1px solid #bbb; border-collapse:collapse;">&nbsp;</td>
                    <td rowspan="3" width="2px">&nbsp;</td>
                    <td rowspan="3" width="125px">
                    
                    <table>
                    <tr><td width="75px" style="text-align: right;"><span style="color: red; font-weight: bold;"> Unassigned Tickets </span></td><td width="50px"><a href="/scp/tickets.php?queue=43&p=1" style="font-size: xx-large; text-align: center; color: #ff0202;"><?php echo $UnassignedTickets; ?></a></td></tr>
                    </table>
                    </td>
                    <td rowspan="3" width="2px">&nbsp;</td>
                    <td rowspan="3" width="5px"  style="border-right: 1px solid #bbb; border-collapse:collapse;">&nbsp;</td>
                    <td rowspan="3" width="2px">&nbsp;</td>
                    <td>&nbsp;</td>
                    <td width="80px">Assinged</td>
                    <td width="65px">Held</td>
                    <td width="80px">Agent Reply</td>
                    <td width="90px">Submitter Reply</td>
                    <td width="80px">Implementation</td>
                    <td width="80px">Quote</td>
                    <td width="30px">TOTAL</td>
                    <td rowspan="3" width="2px">&nbsp;</td>
                    <td rowspan="3" width="5px"  style="border-right: 1px solid #bbb; border-collapse:collapse;">&nbsp;</td>
                    <td rowspan="3" width="2px">&nbsp;</td>
                    <td colspan="2">&nbsp;</td>
                    <td rowspan="3" width="2px">&nbsp;</td>
                    <td rowspan="3" width="5px"  style="border-right: 1px solid #bbb; border-collapse:collapse;">&nbsp;</td>
                    <td rowspan="3" width="2px"></td>
                   
                    <td width="30px" >&nbsp;</td>
                    <td width="80px">Assigned</td>
                    <td width="80px">Implementation</td>
                    <td width="45px">Quote</td>
                    <td rowspan="3" width="55px" style="text-align: left;"><a href="/scp/tickets.php?queue=17&p=1" style="font-size: xx-large; color: #ff0202;"> <?php echo $SuggestionAssignedTicket + $SuggestionImplementationTicket + $SuggestionAwaitingQuoteTickets ?></a></td>
                    <td rowspan="3">&nbsp;</td>
                    
                </tr>
                <tr style="text-align: center;">
                    <td width="40px" style="text-align: right;"><span style="color: red; font-weight: bold;">Backlog</span></td> 
                    <td><a href="/scp/tickets.php?queue=200&p=1" style="color:#000"><?php echo $BacklogTickets["CAN"]; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=201&p=1" style="color:#000"><?php echo $BacklogTickets["IND"]; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=202&p=1" style="color:#000"><?php echo $BacklogTickets["MEX"]; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=203&p=1" style="color:#000"><?php echo $BacklogTickets["NTC"]; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=204&p=1" style="color:#000"><?php echo $BacklogTickets["OH"]; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=205&p=1" style="color:#000"><?php echo $BacklogTickets["TNN1"]; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=206&p=1" style="color:#000"><?php echo $BacklogTickets["TNN2"]; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=207&p=1" style="color:#000"><?php echo $BacklogTickets["TNS"]; ?></a></td>
                    
                    
                    <td width="80px" style="text-align: right;"><span style="color: red; font-weight: bold;">Open Tickets</span></a></td>
                    <td><a href="/scp/tickets.php?queue=211&p=1" style="color:#000"><?php echo $AssignedTickets; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=216&p=1" style="color:#000"><?php echo $HeldTickets; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=213&p=1" style="color:#000"><?php echo $ReplyTickets; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=212&p=1" style="color:#000"><?php echo $TheirReplyTickets; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=215&p=1" style="color:#000"><?php echo $ImplementationTickets; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=214&p=1" style="color:#000"><?php echo $AwaitingQuoteTickets; ?></a></td>
                    <td style="border: 1px solid #bbb; border-collapse:collapse; background: #8b4513;"><a href="/scp/tickets.php?queue=3&p=1" style="color:#fff"><?php echo $OpenTickets;?></a></td>
             
                    <td width="75px" style="text-align: right;"><span style="color: red; font-weight: bold;">Open Issues</span></td>
                    <td width="30px"><a href="/scp/tickets.php?queue=14&p=1" style="color:#000"><?php echo $OpenIssuesTickets; ?></a></td>
                    <td style="text-align: right;"><span style="color: red; font-weight: bold;"> Suggestions </span></td>
                    <td><a href="/scp/tickets.php?queue=19&p=1" style="color:#000"><?php echo $SuggestionAssignedTickets ?></a></td>
                    <td><a href="/scp/tickets.php?queue=21&p=1" style="color:#000"><?php echo $SuggestionImplementationTickets ?></a></td>
                    <td><a href="/scp/tickets.php?queue=20&p=1" style="color:#000"><?php echo $SuggestionAwaitingQuoteTickets ?></a></td>
                 
                    
                   
                </tr>
           
                <tr style="text-align: center;">
                    <td colspan="9">&nbsp;</td>
                    
                    <td width="80px" style="text-align: right;"><span style="color: red; font-weight: bold;">My Open Tickets</span></td>                   
                    <td><a href="/scp/tickets.php?queue=35&p=1" style="color:#000"><?php echo $MyAssignedTickets; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=36&p=1" style="color:#000"><?php echo $MyHeldTickets; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=37&p=1" style="color:#000"><?php echo $MyReplyTickets; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=38&p=1" style="color:#000"><?php echo $MyTheirReplyTickets; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=39&p=1" style="color:#000"><?php echo $MyImplementationTickets; ?></a></td>
                    <td><a href="/scp/tickets.php?queue=40&p=1" style="color:#000"><?php echo $MyAwaitingQuoteTickets; ?></a></td>
                    <td style="border: 1px solid #bbb; border-collapse:collapse; background: #8b4513; color: #fff;"><a href="/scp/tickets.php?queue=31&p=1" style="color:#fff"><?php echo $MyOpenTickets; ?></a></td>
           
                    <td width="75px" style="text-align: right;"><span style="color: red; font-weight: bold;">My Open Issues</span></td>
                    <td width="30px"><a href="/scp/tickets.php?queue=33&p=1" style="color:#000"><?php echo $MyOpenIssuesTickets; ?></a></td>
                     
                </tr>
            </table>
        </td>  
<td> &nbsp; </td>        
    </tr>                
</table>        
</div>                
        
</div>