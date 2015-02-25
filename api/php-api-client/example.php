<?php
die('Not allowed');
include('APIosTicket.class.php');

$api = APIosTicket::connect(
  'https://your.own.api.url/api/http.php/',
  'YOURAPIKEY123123',
  'APIUSER',
  'APIUSERPASSWORD'
);

if($api) {
  echo "Connection open!\n";
  
  //searching tickets
  var_dump($api->search('search terms'));

  //Getting ticket data
  $ticket = $api->getTicket(23);
  var_dump($ticket);

  //Ticket statusid
  var_dump("status ".$ticket->StatusId);

  //Changing ticket status
  var_dump($api->changeTicketStatus(23, 3, 'myComment'));

  //Reading new status
  $ticket = $api->getTicket(23);
  var_dump("status ".$ticket->StatusId);

  //Getting thread first entry of ticket (message note etc.)
  var_dump($te = $ticket->ThreadEntries[0]);
  var_dump($api->getThreadEntry($te));
}

