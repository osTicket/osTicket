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
        
$sql='SELECT round(avg(datediff(ost_ticket.created,NOW())*-1)) as AvgDaysOpen FROM ost_ticket 
        WHERE status_id !=3 and status_id !=12 
        and status_id !=8 and status_id !=9 
        and topic_id != 12 and topic_id != 14';
$avgdaysopen = db_query($sql);
    
    foreach ($avgdaysopen as $avgdays) {
        $averagedaysopen = $avgdays['AvgDaysOpen'];
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
        
$ThridPartyTicketsTicket = Ticket::objects()
        ->filter(array('status_id' => '9')) //Awaiting ThridPartyTickets
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion s
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($ThridPartyTicketsTicket as $cThridPartyTicketsTicket) { 
            $ThridPartyTicketsTickets = $cThridPartyTicketsTicket["count"];
        }

$AwaitingQuoteTicket = Ticket::objects()
        ->filter(array('status_id' => '9')) //Awaiting Quote
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
        
$MyThridPartyTicketsTicket = Ticket::objects()
        ->filter(array('staff_id' => $thisstaff->staff_id)) //this staff
        ->filter(array('status_id' => '9')) //Awaiting ThridPartyTickets
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($MyThridPartyTicketsTicket as $cMyThridPartyTicketsTicket) { 
            $MyThridPartyTickets = $cMyThridPartyTicketsTicket["count"];
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
        ->filter(array('status_id' => '7')) //assigned
        ->filter(array('topic_id' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($SuggestionAssignedTicket as $cSuggestionAssignedTicket) { 
            $SuggestionAssignedTickets = $cSuggestionAssignedTicket["count"];
        }
        
$SuggestionThridPartyTicketsTicket = Ticket::objects()
        ->filter(array('status_id' => '9')) //Awaiting ThridPartyTickets
        ->filter(array('topic_id' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($SuggestionThridPartyTicketsTicket as $cSuggestionThridPartyTicketsTicket) { 
            $SuggestionThridPartyTicketsTickets = $cSuggestionThridPartyTicketsTicket["count"];
        }

$SuggestionAwaitingQuoteTicket = Ticket::objects()
        ->filter(array('status_id' => '10')) //Awaiting Quote
        ->filter(array('topic_id' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($SuggestionAwaitingQuoteTicket as $cSuggestionAwaitingQuoteTicket) { 
            $SuggestionAwaitingQuoteTickets = $cSuggestionAwaitingQuoteTicket["count"];
        }
$OpenTask = Task::objects()
        ->filter(array('flags' => '1')) //Awaiting Quote
        
        ->aggregate(array('count' => SqlAggregate::COUNT('id')));
         
         foreach ($OpenTask as $cOpenTask) { 
            $OpenTasks = $cOpenTask["count"];
        }
$CloseTask = Task::objects()
        ->filter(array('flags' => '0')) //Awaiting Quote
        
        ->aggregate(array('count' => SqlAggregate::COUNT('id')));
         
         foreach ($CloseTask as $cCloseTask) { 
            $CloseTasks = $cCloseTask["count"];
        }
//Backlog     
$BacklogTickets = array(); 
$bl_orgs = Organization::objects();
   
   $bl_orgs->values('id','name');
   foreach ($bl_orgs as $bl_org) {
     //echo $org['id'];  
   

    $OpenTicket = Ticket::objects()
        ->filter(array('user__org_id' => $bl_org['id']))
        ->filter(array('status_id__ne' => '8')) //hold
        ->filter(array('status_id__ne' => '9')) //3rd Party
        ->filter(array('status_id__ne' => '6')) //Submitter Action
        ->filter(array('status_id__ne' => '3')) //closed
        ->filter(array('status_id__ne' => '12')) //autoclosed
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
 
        foreach ($OpenTicket as $orgOpenTicket) { 
            $BacklogTickets[$bl_org['name']] = $orgOpenTicket["count"];
        }
}        
         
$BacklogTotal = $BacklogTickets["CAN"]+
$BacklogTickets["IND"]+
$BacklogTickets["EXT"]+
$BacklogTickets["SS"]+
$BacklogTickets["MEX"]+
$BacklogTickets["OH"]+
$BacklogTickets["NTC"]+
$BacklogTickets["TNN1"]+
$BacklogTickets["TNN2"]+
$BacklogTickets["TNS"];   

?>