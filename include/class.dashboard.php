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


//Support Backlog     

$sql="select org.name as LOCATION, IFNULL(s.count,0) as COUNT
	from ost_organization org left join (
	select o.name ,count(a.ticket_id) as count  from ost_ticket a join ost_user u on a.user_id = u.id right join ost_organization o on u.org_id = o.id
	WHERE 
	a.status_id in (7)
	AND a.topic_id not in (163,94,93,12,92,13,14,161,15,99,78,17,18,19,100,16,101,102)
	group by o.name ) s on org.name = s.name";

$results = db_query($sql); 
 $BacklogITTotal = 0;
 foreach ($results as $result) {
	 $BacklogITotal = (int) $result['COUNT'];
	  $BacklogITTotal =  $BacklogITTotal +  $BacklogITotal;
 }

foreach ($results as $result) {
	 
	  if ($result['LOCATION'] == 'CAN') {$BacklogITCAN = $result['COUNT'];}
	  if ($result['LOCATION'] == 'TNN2') {$BacklogITTNN2 = $result['COUNT'];}
	  if ($result['LOCATION'] == 'TNN1') {$BacklogITTNN1 = $result['COUNT'];}
	  if ($result['LOCATION'] == 'NTC') {$BacklogITNTC = $result['COUNT'];}
	  if ($result['LOCATION'] == 'MEX') {$BacklogITMEX = $result['COUNT'];}
	  if ($result['LOCATION'] == 'TNS') {$BacklogITTNS = $result['COUNT'];}
	  if ($result['LOCATION'] == 'IND') {$BacklogITIND = $result['COUNT'];}
	  if ($result['LOCATION'] == 'OH') {$BacklogITOH= $result['COUNT'];}
	  if ($result['LOCATION'] == 'EXT') {$BacklogITEXT= $result['COUNT'];}
	  if ($result['LOCATION'] == 'SS') {$BacklogITSS = $result['COUNT'];}
	  if ($result['LOCATION'] == 'VIP') {$BacklogITVIP = $result['COUNT'];}
	  if ($result['LOCATION'] == 'RVC') {$BacklogITRVC= $result['COUNT'];}
	  if ($result['LOCATION'] == 'BRY') {$BacklogITBRY = $result['COUNT'];}
	  if ($result['LOCATION'] == 'PAU') {$BacklogITPAU = $result['COUNT'];}
	  if ($result['LOCATION'] == 'NTA') {$BacklogITNTA = $result['COUNT'];}
 }

//SE Backlog
$sql="select org.name as LOCATION, IFNULL(s.count,0) as COUNT
	from ost_organization org left join (
	select o.name ,count(a.ticket_id) as count  from ost_ticket a join ost_user u on a.user_id = u.id right join ost_organization o on u.org_id = o.id
	WHERE 
	a.status_id in (7)
	AND a.topic_id in (13,161,15,99,78,17,18,19,100,16,101,102)
	group by o.name ) s on org.name = s.name";

$results = db_query($sql); 
 $BacklogSETotal = 0;
 foreach ($results as $result) {
	 $BacklogSTotal = (int) $result['COUNT'];
	  $BacklogSETotal =  $BacklogSETotal +  $BacklogSTotal;
 }

foreach ($results as $result) {
	 
	  if ($result['LOCATION'] == 'CAN') {$BacklogSECAN = $result['COUNT'];}
	  if ($result['LOCATION'] == 'TNN2') {$BacklogSETNN2 = $result['COUNT'];}
	  if ($result['LOCATION'] == 'TNN1') {$BacklogSETNN1 = $result['COUNT'];}
	  if ($result['LOCATION'] == 'NTC') {$BacklogSENTC = $result['COUNT'];}
	  if ($result['LOCATION'] == 'MEX') {$BacklogSEMEX = $result['COUNT'];}
	  if ($result['LOCATION'] == 'TNS') {$BacklogSETNS = $result['COUNT'];}
	  if ($result['LOCATION'] == 'IND') {$BacklogSEIND = $result['COUNT'];}
	  if ($result['LOCATION'] == 'OH') {$BacklogSEOH= $result['COUNT'];}
	  if ($result['LOCATION'] == 'EXT') {$BacklogSEEXT= $result['COUNT'];}
	  if ($result['LOCATION'] == 'SS') {$BacklogSESS = $result['COUNT'];}
	  if ($result['LOCATION'] == 'VIP') {$BacklogSEVIP = $result['COUNT'];}
	  if ($result['LOCATION'] == 'RVC') {$BacklogSERVC= $result['COUNT'];}
	  if ($result['LOCATION'] == 'BRY') {$BacklogSEBRY = $result['COUNT'];}
	  if ($result['LOCATION'] == 'PAU') {$BacklogSEPAU = $result['COUNT'];}
	  if ($result['LOCATION'] == 'NTA') {$BacklogSENTA = $result['COUNT'];}


 }

$BacklogTotal = $BacklogITTotal+$BacklogSETotal+$UnassignedTickets;

?>