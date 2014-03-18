<?php
/*********************************************************************
    display_open_topics.php

    Displays a block of the last X number of open tickets.

    Neil Tozier <tmib@tmib.net>
    Copyright (c)  2010-2014
    For use with osTicket version 1.8.1ST (http://www.osticket.com)

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See osTickets's LICENSE.TXT for details.
**********************************************************************/
//require('main.inc.php');

// needed for 1.8 for now until we tie this back into the built in DB query
$username="XXXXXXX";
$password="XXXXXXX";
$database="XXXXXXXX";

// The maximum amount of open tickets that you want to display.
$limit ='10';

//mysql_connect(localhost,$username,$password);
mysql_connect('localhost',$username,$password) or die(mysql_error());
@mysql_select_db($database) or die( "Unable to select database");
// end 1.8 fix for now

// The columns that you want to collect data for from the db
//$columns = "ticket_id, user_id, subject, created, updated, priority_id";
$columns = "ticket_id, user_id, created, updated";

// mysql query.  The columns tha
$query = "SELECT $columns
			 FROM ost_ticket
			 WHERE status = 'open'
			 ORDER BY created DESC
			 LIMIT 0,$limit";

if($result=mysql_query($query)) { 
  $num = mysql_numrows($result);
}

if ($num >> 0) {

// table headers, if you add or remove columns edit this
echo "<table border-color=#BFBFBF border=0 cell-spacing=2><tr style='background-color: #BFBFBF;'>";
echo "<td id='openticks-a' style='min-width:45px;'><b>Priority</b></td><!--<td id='openticks-a' style='min-width:150px;'><b>Name</b></td>--><td id='openticks-a' style='min-width:230px;'><b>Site</b></td><td id='openticks-a' style='min-width:230px;'><b>Issue</b></td><td id='openticks-a' style='min-width:135px;'><b>Opened on</b></td><td id='openticks-b' style='min-width:135px;'><b>Last Update</b></td></tr>";

$i=0;
while ($i < $num) {
 
 // You will need one line below for each column name that you collect and want to display.
 // If you are unfamiliar with php its  essentially $uniqueVariable = mysql junk ( columnName );
 // Just copy one of the lines below and change the $uniqueVariable and columnName
 $user_id = mysql_result($result,$i,"user_id");
 $ticket_id = mysql_result($result,$i,"ticket_id");
 $created = Format::db_datetime(mysql_result($result,$i,"created"));
 $updated = Format::db_datetime(mysql_result($result,$i,"updated"));
 //$agency = mysql_result($result,$i,"agency");
 
 // if no update say so
 if ($updated == '0000-00-00 00:00:00') {
   $updated = 'no update yet';
 }
 
  // look up internal form id
  $entryIdsql = "SELECT id,form_id FROM ost_form_entry WHERE object_id=$ticket_id LIMIT 1";
  $entryIdresult = mysql_query($entryIdsql);
  $entry_id = mysql_result($entryIdresult,0,"id");
  $form_id = mysql_result($entryIdresult,0,"form_id");
  
  // get subject
  $subjectsql = "SELECT value FROM ost_form_entry_values WHERE entry_id=$entry_id and field_id=5";
  $subjectresult = mysql_query($subjectsql);
  $subject = mysql_result($subjectresult,0,"value");
  
  // get priority
  $prioritysql = "SELECT value FROM ost_form_entry_values WHERE entry_id=$entry_id and field_id=7";
  $priorityresult = mysql_query($prioritysql);
  $priority = mysql_result($priorityresult,0,"value");
  
  // look up site/agency and display proper name
  // mysql query.
  $getsite = "SELECT value FROM ost_form_entry_values WHERE entry_id=$entry_id and field_id=12";
  $siteresult = mysql_query($getsite);
  $site = mysql_result($siteresult,0,"value");

  // get ticket openers name
  $namesql = "SELECT name FROM ost_user WHERE id=$user_id";
  $nameresult = mysql_query($namesql);
  $name = mysql_result($nameresult,0,"name");
  //mysql_close();

	// change row back ground color to make more readable
	if(($i % 2) == 1)  //odd
      {$bgcolour = '#F6F6F6';}
    else   //even
      {$bgcolour = '#FEFEFE';}
 
  //populate the table with data
  echo "<tr align=center><td BGCOLOR=$bgcolour id='openticks-a' nowrap> &nbsp; $priority &nbsp; </td>"
    ."<!--<td BGCOLOR=$bgcolour id='openticks-a' nowrap style='min-width:150px;'> &nbsp; $name &nbsp; </td>-->"
	."<td BGCOLOR=$bgcolour id='openticks-a' style='min-width:200px;'> &nbsp; $site &nbsp; </td>"
    ."<td BGCOLOR=$bgcolour id='openticks-a' style='min-width:200px;'> &nbsp; $subject &nbsp; </td>"
    ."<td BGCOLOR=$bgcolour id='openticks-a'> &nbsp; $created &nbsp; </td><td BGCOLOR=$bgcolour id='openticks-b'>"
	." &nbsp; $updated &nbsp; </td></tr>";
 
 	++$i;
}
echo "</table>";
}

else {
 echo "<p style='text-align:center;'><span id='msg_warning'>There are no tickets open at this time.</span></p>";
}
?>
