<?php
require_once(INCLUDE_DIR.'/class.config.php');
require_once(INCLUDE_DIR.'/class.businesshours.php');
require_once(INCLUDE_DIR.'/class.schedule.php'); 
require_once(INCLUDE_DIR. '/class.dynamic_forms.php'); 
require_once(INCLUDE_DIR . '/class.ticket.php');
require_once(INCLUDE_DIR . '/class.plugin.php');
require_once(INCLUDE_DIR . '/plugins/SLA-AddOn/sla_addon.php');
class SlaHelper extends VerySimpleModel {


    protected $_config;
    protected $_schedule;
	
function check_overdue($ticket_id){
 $sql = 'SELECT ticket_id,isoverdue FROM ost_ticket WHERE ticket_id="'.$ticket_id.'"' ;
    $rs = db_query($sql);
    $result = db_fetch_array($rs);
    return $isoverdue = $result['isoverdue'];     
}
 function getScheduleId($sla_id){
    $sql_sla = "SELECT schedule_id FROM ".TABLE_PREFIX."sla WHERE id = ".$sla_id;
    $rs = db_query($sql_sla);
    $result = db_fetch_array($rs);    
    return  $scheduleId = $result['schedule_id'];
  }

function slaCorrection($ticket,$ticket_id,$sla_id,$sla_units){
   global $ost, $cfg;
   $ticket_created = $ticket->created;    
   $response_list = $this->getSlaAddonResponseTypes();   
   $data = $this->slaMeasurementCorrection($ticket_id, $ticket_created,$sla_units,$sla_id);

   foreach ($response_list as $key => $response_type) {
      
      // check if ticket have first response exist or not
      $res_time = $this->checknLoadResponse($response_type,$ticket_id);
      $fields = $this->getResponseFields($response_type);  

      if(isset($res_time['time']) && $res_time['time'] != 0 ){
        //update response as per new sla
          switch ($response_type) {
              case 'first':
                $response =  $data['first_response_status'];  
                $field_response_status = FIELD_FIRST_RESPONSE_STATUS;                 
                break;
              case 'temporary':
                $response =  $data['temporary_solution_status'];
                $field_response_status = FIELD_TEMP_SOLUTION_STATUS;
                break;
              case 'final':
                $response =  $data['final_solution_status'];
                $field_response_status = FIELD_FINAL_SOLUTION_STATUS;
                break;
              default:
                # code...
                break;
            } 

            //echo '<pre>' ; print_r($response);
            // set response
            $forms = DynamicFormEntry::forTicket($ticket_id);
             foreach($forms as $form) {
                if($form->getTitle() == "Ticket Details") {                    
                   $field_frs = $form->getField( $field_response_status); 
                   $field_frs->setValue($response);  
                } 
                $form->save();                  
          }   
      } 
   } // end foreach
    return;   
} // end sla correction
	

/*
* @function - AutoupdateTicketResponse
* @Purpose  - Autoupdate all open tickets reponse status by cron
*/

function AutoupdateTicketResponse($ticket_id,$sla_id,$ticket,$sla_units){
    // get current datetime
    global $cfg,$thisstaff;
    $tz = new DateTimeZone($cfg->getDbTimezone());
    $datetime = new DateTime('now',$tz);
    $current_datetime = (array) $datetime;
    $now =  $current_datetime['date'];
    $current_timestamp = strtotime($now);
    //echo "<br>";echo "(now=".$now."  ,slaid=".$sla_id."   ,ticket_id  ".$ticket_id.")";echo "<br>";
    $response_types = $this->getSlaAddonResponseTypes();       
     
    $ticket_created = $ticket->created; 
    $target_timeline = $this->slaMeasurementCalculation($ticket_id, $ticket_created,$sla_units,$sla_id);
     //echo '<pre>'; print_r($target_timeline);
    // Process for  each response type
    
    foreach ($response_types as $response_type => $value) {
            
            switch ($response_type) {
              case 'first':
                $target_datetime = $target_timeline['first_response_timeline'];
                $flag = FLAG_FIRST_RESPONSE;
                               
                break;
              case 'temporary':
                $target_datetime = $target_timeline['temporary_solution_timeline'];
                 $flag = FLAG_TEMP_SOLUTION;
                                
                break;
              case 'final':
                $target_datetime = $target_timeline['final_solution_timeline'];
                $flag = FLAG_FINAL_SOLUTION;
               
                break;    
              
              default:
                # code...
                break;
            }// END SWITCH
            // check if ticket have first response available or not
                $res_time = $this->checknLoadResponse($response_type,$ticket_id);
                
                if($res_time['time'] == 0 || !isset($res_time['time']) || !isset($res_time['status']) ){
                    // If response time/status is not availble on ticket , measure the ticket
                   $target_timestamp = strtotime($target_datetime);
                    
                   if($target_timestamp < $current_timestamp){ 
                     $response['time'] = date('Y-m-d H:i:s', strtotime($target_datetime. ' +1 minutes'));
                     $response['status'] = VALUE_RESPONSE_LIST_MISSED ;
                     $response = $this->SetResponseByPassData($response_type,$ticket_id,$response); 
                     $note = "SYSTEM : Missed the timeline for ".$flag;
                     $var = [
                     'poster'   => $thisstaff,                
                     'note'  =>  $note,
                     'ticketId'  => $ticket_id, 
                      ]; 
                    $ticket->postThreadEntry('N', $var);
                   }
                }
             
            
      } //  FORLOOP ENDS FOR RESPONSE TYPES
      
} // ENDS FUNCTION     

/*
* @function - getSlaAddonResponseTypes
* @Purpose  - Return Reponse Type list 
*/
  function getSlaAddonResponseTypes(){
    $response_types =  array(  "first" => "first",
                              "temporary" => "temporary",
                               "final" => "final",
                          );
    return $response_types;
  } // ENDS FUNCTION 

/*
* @function - getPluginId
* @Purpose  - Return Plugin Id by plugin name 
*/
  function getPluginId(){ 
	  $active=true;
	  $plugin_name = "SLA AddON";
	  $sql = sprintf('SELECT id FROM %s WHERE name="%s"', TABLE_PREFIX . 'plugin', $plugin_name);
	  if ($active)
		  $sql = sprintf('%s AND isactive = true', $sql);
	  if (!($res = db_query($sql)))
		  return false;
	  $result = db_fetch_array($res);
	  return $result['id'];
  } // ENDS FUNCTION 

/*
* @function - getStatusIdByName
* @Purpose  - Return Status Id by Status name 
*/
  function getStatusIdByName($name){
    $sql = sprintf('SELECT id FROM %s WHERE name="%s"', TABLE_PREFIX . 'ticket_status', $name);
    if (!($res = db_query($sql)))
    return false;
   
    if($res && db_num_rows($res)) {
          $result = db_fetch_array($res);
    } 
    return $result['id'];
  } // ENDS FUNCTION 

/*
* @function - getStatusNameById
* @Purpose  - Return Status name by id
*/
  function getStatusNameById($id){
    $sql = sprintf('SELECT name FROM %s WHERE id="%s"', TABLE_PREFIX . 'ticket_status', $id); 
    if (!($res = db_query($sql)))
    return false;
   
    if($res && db_num_rows($res)) {
          $result = db_fetch_array($res);
    }          
    return $result['name'];
  } // ENDS FUNCTION 

/*
* @function - getResponseFields
* @Purpose  - Return Response Fields Name dynamically
*/
  function getResponseFields($response_type){

     switch ($response_type) {
       case 'first':
          $fields['response_time'] = FIELD_FIRST_RESPONSE_TIME;
          $fields['response_status'] = FIELD_FIRST_RESPONSE_STATUS;
         break;
        case 'temporary':
          $fields['response_time'] = FIELD_TEMP_SOLUTION_TIME;
          $fields['response_status'] = FIELD_TEMP_SOLUTION_STATUS;
         break;
        case 'final':
          $fields['response_time'] = FIELD_FINAL_SOLUTION_TIME;
          $fields['response_status'] = FIELD_FINAL_SOLUTION_STATUS;
         break;
       
       default:
         # code...
         break;
     }

     return $fields;
   } // ENDS FUNCTION 

/*
* @function - checknLoadResponse
* @Purpose  - Return Response If exists  
*/
  function checknLoadResponse($response_type,$ticket_id){
    
    $fields = $this->getResponseFields($response_type); 
    //$fields['response_time'].$response_type.$fields['response_status'];
    
      $forms = DynamicFormEntry::forTicket($ticket_id);
       foreach($forms as $form) {
        if($form->getTitle() == "Ticket Details") { 

         $field_frt = $form->getField( $fields['response_time']); 
         $field_frs = $form->getField( $fields['response_status']);  
         $res_time['time'] = $field_frt->answer->getValue();
         $status_ = $field_frs->answer->getValue(); 
          
          if(isset($status_)){
             $status = reset($status_);
             $res_time['status']  = $status; 
          }
                    
         if(!isset($res_time['time']) && $res_time['time']!=0 && !empty($res_time['status']) )           
             $res_time['exist'] = true;                
           else 
            $res_time['exist'] = false;            
            
           return $res_time; 
 
        }
      } 


  }// ENDS FUNCTION 
 
/*
* @function - getResponse
* @Purpose  - Return Response after SLA Measurement 
*/ 
  function getResponse($response_type,$ticket_id,$ticket_created,$sla_units,$sla_id,$res_time="",$await=0){ 

    // Code for SLA Measurement
    global $ost;   
    $target = $this->slaMeasurementCalculation($ticket_id, $ticket_created,$sla_units,$sla_id);
	$interval = $target['interval'];
	$str1 = " Ticket Creation Date = ".$ticket_created;
	$str11 = " First Response Date = ".$target['first_response_timeline'];
    $str2 = " temporary_solution_timeline = ".$target['temporary_solution_timeline'];
    $str3 = " final_solution_timeline = ".$target['final_solution_timeline'];
    $message = $str1.$str11.$str2.$str3." interval".$interval;
    $title = "Debug Info #SLA - ADDON: Get Response (".$ticket_id.") ";
    $ost->logDebug($title,$message , true);
    //echo '<pre>'; print_r($target);
     //echo $response_type;  
    //  calculation for first response  time ans status     
    switch ($response_type) {
      case 'await':
          // -----------calculate time--------------------
             $sql = 'SELECT id,created FROM ost_thread_entry WHERE ( TYPE="R" OR TYPE="N" ) AND thread_id="'.$ticket_id.'" ORDER BY id ASC LIMIT 1';
            
              if (!($res = db_query($sql)) || db_num_rows($res)== 0){ // may be only status changes repsonse not added cross check with sla table 
                  $sql2 = 'SELECT id,created FROM `'.SLA_ADDON_TABLE.'` WHERE ticket_id="'.$ticket_id.'" AND ticket_status="awaited" 
                 ORDER BY id ASC LIMIT 1';  
                  if(($addon_res = db_query($sql2)) && db_num_rows($addon_res)) {
                     $result = db_fetch_array($addon_res); 
                     $first_response_time  = $result['created'];
					 $time = $first_response_time ;
                     //$time = Format::datetime($first_response_time);                 
                  }else
                     return false;   

                //return false;   
              }
              if($res && db_num_rows($res)) {
                $result = db_fetch_array($res);                 
                $first_response_time  = $result['created'];
                $time = $first_response_time;
               // $time = Format::datetime($first_response_time);                 
              }              
              //-------------- calculate status  -----------

                $response_timeline = strtotime($target['first_response_timeline']); 
                $response_time = strtotime($first_response_time);
                 
                if($response_time > $response_timeline){                   
                  $status = VALUE_RESPONSE_LIST_MISSED;
               }else{                  
                   $status = VALUE_RESPONSE_LIST_ACHIEVED;
               }               
                $response['time'] = $time;
                $response['status']  = $status; 
                        
                return $response;   
                                  
               
        break;
      case 'first':
          // $first_response_time = date("Y-m-d H:i:s"); 
          // -----------calculate time--------------------
            $sql = 'SELECT id,created FROM ost_thread_entry WHERE TYPE="R" AND thread_id="'.$ticket_id.'" ORDER BY id ASC LIMIT 1';
            
              if (!($res = db_query($sql)) || db_num_rows($res)== 0){ // may be only status changes repsonse not added cross check with sla table 
                  $sql2 = 'SELECT id,created FROM `'.SLA_ADDON_TABLE.'` WHERE ticket_id="'.$ticket_id.'" AND thread_type="R"   
                 ORDER BY id ASC LIMIT 1'; //AND ticket_status="awaited"
                  if(($addon_res = db_query($sql2)) && db_num_rows($addon_res)) {
                     $result = db_fetch_array($addon_res); 
                     $first_response_time  = $result['created'];
                     //$time = Format::datetime($first_response_time);
                     $time = $first_response_time;					 
                  }else
                     return false;   

                //return false;   
              }
              if($res && db_num_rows($res)) {
                $result = db_fetch_array($res);                 
                $first_response_time  = $result['created'];
                //$time = Format::datetime($first_response_time); 
                $time = $first_response_time;                
              }              
              //-------------- calculate status  -----------

                $response_timeline = strtotime($target['first_response_timeline']); 
                $response_time = strtotime($first_response_time);
                 
                if($response_time > $response_timeline){                   
                  $status = VALUE_RESPONSE_LIST_MISSED;
               }else{                  
                   $status = VALUE_RESPONSE_LIST_ACHIEVED;
               }               
                $response['time'] = $time;
                $response['status']  = $status; 
                        
                return $response;   
                                  
               
        break;
      case 'temporary':
        // find first response time
         
        $first_response_time = $res_time['time'];
        $temporary_solution_time  = date("Y-m-d H:i:s");        
         
       // -----------calculate temporary solution time --------------------
         $sql = 'SELECT id,created FROM '.SLA_ADDON_TABLE.' WHERE 
         thread_type="R" AND 
         ticket_id="'.$ticket_id.'" 
         AND ticket_status = "'.STATUS_TEMSOL_PROVIDED.'"
         ORDER BY id ASC LIMIT 1'; 
          

              if(($res = db_query($sql)) && db_num_rows($res)) {
                $result = db_fetch_array($res);   
                $temporary_solution_time  = $result['created'];
              }
             
              if (!($res = db_query($sql)) || db_num_rows($res) == 0 )
                {
                   // in case if agent directly close the ticket without first and templorary response
                   $c_sql = 'SELECT id,created FROM '.SLA_ADDON_TABLE.' WHERE 
                   thread_type="R" AND 
                   ticket_id="'.$ticket_id.'" AND 
                   ticket_status = "'.STATUS_FINALSOL_PROVIDED.'"
                   ORDER BY id ASC LIMIT 1';
                  if(($c_res = db_query($c_sql)) && db_num_rows($c_res)) {
                   $result = db_fetch_array($c_res); 
                   $final_solution_time  = $result['created']; 
                   $temporary_solution_time =  $final_solution_time;
                  }  
                  
                }     
         //$time = Format::datetime($temporary_solution_time);  
		   $time = $temporary_solution_time; 
       // -----------calculate temporary solution status --------------------
               // Add Await / Pause time starts
                // $interval = $target_data['interval'];
				$message_ = $interval;
				//$ost->logDebug("Debug Info #SLA - ADDON:Interval",$message_ , true);
                $temporary_response_timeline = $target['temporary_solution_timeline'];
                if($interval != 0 && $interval != ""){

                $TempResponseWithPauseTime = $this->addPauseIntervaltoTimeline('seconds',$interval,$ticket_id,$target['temporary_solution_timeline']) ;   
                
                //$TempResponseWithPauseTime = date('Y-m-d H:i:s',strtotime('+'.$interval.' seconds ',strtotime($target['temporary_solution_timeline']))); 
                $str = 'interval='.$interval.' , T R Date='.$TempResponseWithPauseTime;
                $temporary_response_timeline = $TempResponseWithPauseTime;
                $message = "SLA Pause-:".$str;
                $title = "Debug Info #SLA - ADDON: Get Response -Temporary - (".$ticket_id.") ";
                //$ost->logDebug($title,$message , true);

                }
                // Add Await / Pause time ends	
               $title1 = "Debug Info #SLA - ADDON: Get Response -Temporary Timeline- (".$ticket_id.") ";
			   $message1 = "Temporary Response Timeline-:".$temporary_response_timeline;
              // $ost->logDebug($title1,$message1 , true); 
			   
               $response_timeline = strtotime($temporary_response_timeline);
               $response_time = strtotime($temporary_solution_time);
                if($response_time > $response_timeline){                   
                  $status = VALUE_RESPONSE_LIST_MISSED;
               }else{                  
                   $status = VALUE_RESPONSE_LIST_ACHIEVED;
               } 
         
                $response['time'] = $time;
                $response['status']  = $status;               
             
       return $response;

      break;

      case 'final':
      // find first response time
         
         $temp_solution_time = $res_time['time'];
          $final_solution_time  = date("Y-m-d H:i:s"); echo 'fs='.$final_solution_time;
       // -----------calculate temporary solution time --------------------
         $sql = 'SELECT id,created FROM '.SLA_ADDON_TABLE.' WHERE 
         thread_type="R" AND 
         ticket_id="'.$ticket_id.'" 
         AND ticket_status = "'.STATUS_FINALSOL_PROVIDED.'"
         ORDER BY id ASC LIMIT 1'; 
           if (!($res = db_query($sql)))
                return false;   
              if($res && db_num_rows($res)) {
                $result = db_fetch_array($res);       
                          
                $final_solution_time  = $result['created'];
                                
              }    
        //$time = Format::datetime($final_solution_time); 
		  $time = $final_solution_time; 
       // -----------calculate temporary solution status --------------------
	   
	           // Add Await / Pause time starts
      
				$sqlTotalinterval ="SELECT SUM(".TABLE_PREFIX."sla_addon.interval) totalinterval FROM ".TABLE_PREFIX."sla_addon WHERE ticket_status='awaited' 
				AND ticket_id=".$ticket_id."
				AND created > '".$temp_solution_time."'
				" ;

				$sti = db_query($sqlTotalinterval);
				$stiresult = db_fetch_array($sti);
				$PauseInterval = $stiresult['totalinterval'];

				$final_solution_timeline = $target['final_solution_timeline'];
				if($PauseInterval != 0 && $PauseInterval != ""){
         
         $FinalResponseWithPauseTime = $this->addPauseIntervaltoTimeline('seconds',$PauseInterval,$ticket_id,$target['final_solution_timeline']) ; 

				//$FinalResponseWithPauseTime = date('Y-m-d H:i:s',strtotime('+'.$PauseInterval.' seconds ',strtotime($target['final_solution_timeline']))); 
				$str = 'interval='.$PauseInterval.' ,final resposne date after addding Pause ='.$FinalResponseWithPauseTime;
				$message = "SLA Pause-:".$str;
				$title = "Debug Info #SLA - ADDON: Get Response - Final - (".$ticket_id.") ";
				$ost->logDebug($title,$message , true);
				$final_solution_timeline = $FinalResponseWithPauseTime;

				// Add Await / Pause time ends              
				}


               $response_timeline = strtotime($final_solution_timeline);
               $response_time = strtotime($final_solution_time);
                if($response_time > $response_timeline){                   
                  $status = VALUE_RESPONSE_LIST_MISSED;
               }else{                  
                   $status = VALUE_RESPONSE_LIST_ACHIEVED;
               } 
              $response['time'] = $time;
              $response['status']  = $status; 
              return $response;



        break;
      default:         
        break;
    }

                  
  }// ENDS FUNCTION 



/*
* @function - SetResponse
* @Purpose  - Set Response after SLA Measurement 
*/ 
  function SetResponse($response_type,$ticket_id,$sla_units,$sla_id,$res_time=""){  
    
   switch ($response_type) {
     case 'first':
          $field_response_time = FIELD_FIRST_RESPONSE_TIME;
          $field_response_status = FIELD_FIRST_RESPONSE_STATUS;
       break;
    case 'temporary':    
          
          $field_response_time = FIELD_TEMP_SOLUTION_TIME;
          $field_response_status = FIELD_TEMP_SOLUTION_STATUS;
       break;
    case 'final':
          $field_response_time = FIELD_FINAL_SOLUTION_TIME;
          $field_response_status = FIELD_FINAL_SOLUTION_STATUS;
       break;   
     
     default:
       # code...
       break;
   } // switch ends
   
   // check existing first response
      $ticketObj = Ticket::lookup($ticket_id);
      $ticket_created =  $ticketObj->created;   
      $forms = DynamicFormEntry::forTicket($ticket_id);
      foreach($forms as $form) {
        if($form->getTitle() == "Ticket Details") { 

         $field_frt = $form->getField($field_response_time); 
         $field_frs = $form->getField($field_response_status); 
         $field_response_time."".$field_response_status;
         
         if($field_frt != "" || $field_frs != "" ){ 
           $saved_response_time = $field_frt->answer->getValue();
           $saved_response_status  = $field_frs->answer->getValue();
          }else{   
            if(empty($saved_response_time) || empty($saved_response_status)){
               
               $response = $this->getResponse($response_type,$ticket_id,$ticket_created,$sla_units,$sla_id,$res_time); 
                                       
                  $field_frt->setValue($response['time']);     // SaveFirstResponseTime
                  $field_frs->setValue($response['status']);  //SaveFirstResponseStatus status  
              
            }else{
               return;
            }
             
          }
 
          $form->save();       
        }
         
         $ticketObj->save(); 
         return $response;      
      }     

  } // ENDS FUNCTION 


/*
* @function - SetResponse
* @Purpose  - Set Response directly by passing arguments
*/ 
  function SetResponseByPassData($response_type="temporary",$ticket_id,$response){

  $fields = $this->getResponseFields($response_type);
  $forms = DynamicFormEntry::forTicket($ticket_id);
       foreach($forms as $form) {
        if($form->getTitle() == "Ticket Details") { 

         $field_frt = $form->getField( $fields['response_time']); 
         $field_frs = $form->getField( $fields['response_status']);  
          
          if($field_frt != "" || $field_frs != "" ){
           $saved_response_time = $field_frt->answer->getValue();
           $saved_response_status  = $field_frs->answer->getValue();
          }else{  
            if(empty($saved_response_time) || empty($saved_response_status)){
              
                               
                 $field_frt->setValue($response['time']);     // SaveFirstResponseTime
                 $field_frs->setValue($response['status']);  //SaveFirstResponseStatus status  
              
            }else{
               return false;
            }
             
          }

          $form->save();  
        } // end ticket details
      } 
  } // ENDS FUNCTION 



/*
* @function - SlaPauseOnAwait
* @Purpose  - SLA Pause on Await status
*/ 
  function SlaPauseOnAwait($ticket_id){
          
    $sql_await = 'SELECT id,created FROM ost_sla_addon WHERE ticket_status="awaited" AND ticket_id="'.$ticket_id.'" ORDER BY id DESC LIMIT 1';
    $rs1 = db_query($sql_await);
    $result_await = db_fetch_array($rs1);
     
    $awaited_id = $result_await['id'];
    $awaited_time = $result_await['created'];

    $sql_open = 'SELECT id,created FROM ost_sla_addon WHERE 
    ticket_status="open" AND ticket_id="'.$ticket_id.'" AND id > "'.$awaited_id.'"
    ORDER BY id ASC LIMIT 1';
    $rs2 = db_query($sql_open);
    $result_open = db_fetch_array($rs2);
    $ticket_open_time = $result_open['created'];
     
    if(isset($awaited_time) && isset($ticket_open_time)){
      $sql_time_diff = 'SELECT TIMESTAMPDIFF(SECOND, "'.$awaited_time.'","'.$ticket_open_time.'") AS "interval"';
      $rs = db_query($sql_time_diff);
      $result = db_fetch_array($rs);
      $interval = $result['interval'];
         
     
      if(isset($interval) && $interval!=0){
       // update time
        $sql_upd_int ="UPDATE ".TABLE_PREFIX."sla_addon  SET `interval`=".$interval." WHERE id=".$awaited_id;
        db_query($sql_upd_int);
      // get other awaited time
        $sqlTotalinterval ="SELECT SUM(".TABLE_PREFIX."sla_addon.interval) totalinterval FROM ".TABLE_PREFIX."sla_addon WHERE ticket_status='awaited' AND ticket_id=".$ticket_id;
         $sti = db_query($sqlTotalinterval);
        $stiresult = db_fetch_array($sti);
         $tInterval = $stiresult['totalinterval'];
        // Update estdue date
       // $sql_upd_est ="UPDATE ".TABLE_PREFIX."ticket SET `est_duedate` = DATE_ADD(est_duedate, INTERVAL ".$interval." second) WHERE ticket_id=".$ticket_id;
       // $update = db_query($sql_upd_est);
        $this->slaEstDueDate($ticket_id, $tInterval);

        
      }      
      // Code to update estdue date and due date as per this time difference
    }

  } // ENDS FUNCTION 

 

  /*
    * @function - Estimate Due Date
    * @Purpose  - Function to calculate est due date after sla pause time. Ticket id and interval will be the time duration which we need to add in estimate date 
  */     
    function slaEstDueDate($ticket_id, $interval){
      //echo $ticket_id." - ".$interval."<br>";
       $sql_sla = "SELECT T.ticket_id, T.sla_id,T.est_duedate, T.reopened,T.closed, T.created, S.schedule_id  FROM ".TABLE_PREFIX."ticket T, ".TABLE_PREFIX."sla S WHERE T.sla_id = S.id AND T.ticket_id =".$ticket_id;

      $rs1 = db_query($sql_sla);
      $result_sla = db_fetch_array($rs1);
      // Get schedule
      $schedule='';
      $newEstDuedate='';
      $slaId = $result_sla['sla_id'];
      $scheduleId = $result_sla['schedule_id'];
      $createdDate = $result_sla['created'];
      $reopenDate = $result_sla['reopened'];
      $updateCreateDate =  date("Y-m-d H:i:s", (strtotime(date($createdDate)) + $interval));

      $updateReopenDate = '';
      if(isset($reopenDate) && $reopenDate!='')
      {
        $updateReopenDate = date("Y-m-d H:i:s", (strtotime(date($reopenDate)) + $interval));  
      }
      
        $sla = Sla::lookup($slaId);
        global $cfg;
        $schedule = BusinessHoursSchedule::lookup($scheduleId);
        $tz = new DateTimeZone($cfg->getDbTimezone());
        $dt = new DateTime($reopenDate ? $updateReopenDate: $updateCreateDate, $tz);
        // $time = $sla->getGracePeriod()*3600+($interval/2); //grace period + internal time
		$time = ($sla->getGracePeriod()*3600)+$interval; //grace period + internal time
        
        //$gracehours = round(($time/3600),2);
		$gracehours = $time/3600;
        $sla->grace_period=$gracehours;
        $dt = $sla->addGracePeriod($dt, $schedule);
        // Make sure time is in DB timezone
        $dt->setTimezone($tz);
        $newEstDuedate =  $dt->format('Y-m-d H:i:s');
        // Update estdue date
         $sql_upd_est ="UPDATE ".TABLE_PREFIX."ticket SET `est_duedate` = '".$newEstDuedate."' WHERE ticket_id=".$ticket_id;
        $update = db_query($sql_upd_est);
  
    }// ENDS FUNCTION 

  
  /*
    * @function -  SLA Measurement core function 
    * @Purpose  - To do the calculation of SLA measurement using SLA Id an Schedule Id  
  */  
    
    function slaMeasurementCalculation($ticket_id, $createddate,$sla_units,$sla_name)
    {
      global $ost; 
      $sql_sla = "SELECT T.ticket_id, T.sla_id,T.est_duedate, T.reopened,T.closed, T.created, S.schedule_id  FROM ".TABLE_PREFIX."ticket T, ".TABLE_PREFIX."sla S WHERE T.sla_id = S.id AND T.ticket_id =".$ticket_id;

       $rs1 = db_query($sql_sla);
       $result_sla = db_fetch_array($rs1);
       $scheduleId = $result_sla['schedule_id'];
        $sla_id = $result_sla['sla_id'];


       // I assume we are getting hours count of first response
        $firstResponseHours = $sla_units['data']['first-response-time'];
        $tempResponseHours = $sla_units['data']['temporary-solution-time'];
        $finalResponseHours =$sla_units['data']['final-solution-time'];
        //$scheduleId = 3;
        $ticketCreated =$createddate;

        // Code to fetch the difference of interval if status was await to open 
         $sqlTotalinterval ="SELECT SUM(".TABLE_PREFIX."sla_addon.interval) totalinterval FROM ".TABLE_PREFIX."sla_addon WHERE ticket_status='awaited' AND ticket_id=".$ticket_id;
         $sti = db_query($sqlTotalinterval);
        $stiresult = db_fetch_array($sti);
         $interval = $stiresult['totalinterval'];
      if($interval=='')
      {
        $interval=0;
      }
        // Code ends here for interval value
        $sla = Sla::lookup($sla_id);  
        global $cfg,$ost;
		$message =  " scheduleId= ".$scheduleId." sla_id = ".$sla_id;
        $ost->logDebug("Debug Info #SLA - ADDON: SLA MC",$message , true);
        $schedule = BusinessHoursSchedule::lookup($scheduleId);
        $tz = new DateTimeZone($cfg->getDbTimezone());
        
        $dt = new DateTime($ticketCreated, $tz);
        // first response date
        $time = $firstResponseHours*3600+($interval/2); //grace period + interal time
        //$gracehours = round(($time/3600),2);
		$gracehours = $sla_units['data']['first-response-time'];
        $sla->grace_period=$gracehours;
        $dt = $sla->addGracePeriod($dt, $schedule);
        $dt->setTimezone($tz);
        $firstResdate =  $dt->format('Y-m-d H:i:s');
        // temp response date
        //echo "<br>l - ";
        $dtT = new DateTime($firstResdate, $tz);
         $timeT = $tempResponseHours*3600+($interval/2); //grace period + interal time
        //$gracehoursT = round(($timeT/3600),2);
		$gracehoursT = $sla_units['data']['temporary-solution-time']; 
        $sla->grace_period=$gracehoursT;
        $dtT = $sla->addGracePeriod($dtT, $schedule);
        $dtT->setTimezone($tz);
        $tempResdate =  $dtT->format('Y-m-d H:i:s');
        // final response date
        $dtF = new DateTime($tempResdate, $tz);
        $timeF = $finalResponseHours*3600+($interval/2); //grace period + interal time
        //$gracehoursF = round(($timeF/3600),2);
		$gracehoursF = $sla_units['data']['final-solution-time'];
        $sla->grace_period=$gracehoursF;
        
        $dtF = $sla->addGracePeriod($dtF, $schedule);
        $dtF->setTimezone($tz);
        $finalResdate =  $dtF->format('Y-m-d H:i:s');

       // echo "<br> createddate : ".$createddate;
       // echo "<br> firstRs : ".$firstResdate;
       // echo "<br> tempRs : ".$tempResdate;
        // echo "<br> finalRs : ".$finalResdate;
        $data['first_response_timeline'] = $firstResdate;
        $data['temporary_solution_timeline'] = $tempResdate;
        $data['final_solution_timeline'] = $finalResdate;
         
        $UpdatedfirstResdate = $firstResdate;
        //check If TempSol Missed no need to reschedule ticket
        $forms = DynamicFormEntry::forTicket($ticket_id);
        foreach($forms as $form) {
          if($form->getTitle() == "Ticket Details") { 
		    // Get current First Response Status
			$field_fr_timeline = $form->getField(FIELD_FIRST_RESPONSE_STATUS);          
            $fr_timeline = $field_fr_timeline->answer->getValue();          

			if($fr_timeline != NULL){
			$key =  array_key_first($fr_timeline);          
			$first_timeline_status = $fr_timeline[$key];
			$ost->logDebug("Debug Info #SLA - ADDON: First Response Status",$temp_timeline_status , true); 
			} 
		    // Get current Temporary response Status
            $field_ts_timeline = $form->getField(FIELD_TEMP_SOLUTION_STATUS);          
            $ts_timeline = $field_ts_timeline->answer->getValue(); 
          if($ts_timeline != NULL){
            $key =  array_key_first($ts_timeline);          
            $temp_timeline_status = $ts_timeline[$key];
            $ost->logDebug("Debug Info #SLA - ADDON: Current Temp Response status",$temp_timeline_status , true); 
           }          
          } 
        }
		
		//   First response    
        if($first_timeline_status)
        if($first_timeline_status  != VALUE_RESPONSE_LIST_MISSED) {  // FRT Achieved Case
          $UpdatedTempResponse = $this->SlaTimelineResheule("temporary",$ticket_id,$sla_units,$data);          
          $ost->logDebug("Debug Info #SLA - ADDON: Achieved FRT ",$UpdatedTempResponse , true);
          $first_res_time = $this->checknLoadResponse($reponse_type="first",$ticket_id);
          if($first_res_time['time'])
          $UpdatedfirstResdate = $first_res_time['time'];
            
        }else{  // FRT Missed Case
          $UpdatedfirstResdate = $createddate;
          $UpdatedTempResponse = $this->SlaMissedTimelineResheule('temporary',$ticket_id,$sla_units,$data,$createddate,$interval);
          $ost->logDebug('Result from FRT Missed Case',$this->showDateByserverTinezone($UpdatedTempResponse) , true);        
        } 
		// If temporary response
        if($temp_timeline_status)
        if($temp_timeline_status  != VALUE_RESPONSE_LIST_MISSED) { // case ACHIEVED
          $UpdatedFinalResponse = $this->SlaTimelineResheule("final",$ticket_id,$sla_units,$data) ;
          $ost->logDebug("Debug Info #SLA - ADDON: Temporary achieved response ","condition achieved case" , true);
        }        
        else{
            // In case if temporary response MISSED than update final response    
            $ost->logDebug("Debug Info #SLA - ADDON: Temp else ",$UpdatedfirstResdate , true);
            if( $UpdatedTempResponse != 0 || isset($UpdatedTempResponse) ){               
                $UpdatedFinalResponse = $this->SlaMissedTimelineResheule('final',$ticket_id,$sla_units,$data,$UpdatedTempResponse,$interval);
                $ost->logDebug('Result from TRT Missed Case',$this->showDateByserverTinezone($UpdatedFinalResponse) , true);   

            }else
            $UpdatedFinalResponse = $finalResdate; //TempSol Missed , so no need to reschedule ticket
          }        
        $target_data['first_response_timeline'] = $UpdatedfirstResdate;
        $target_data['temporary_solution_timeline'] = $UpdatedTempResponse;
        $target_data['final_solution_timeline'] = $UpdatedFinalResponse;
		$target_data['interval'] = $interval;        
         
        return $target_data;
		
		
        // Code to insert these values in new table $firstResdate, $tempResdate, $finalResdate 
        // Column for new table - ticketid, firstresponse, tempresponse, final response, in firsinterval, tempinterval, finalinterval

    }// ENDS FUNCTION


	/*
	* @function - SlaMissedTimelineResheule
	* @Purpose  - Reshedule the timeline if first or temporary missed
	*/ 

	function SlaMissedTimelineResheule($request_type,$ticket_id,$sla_units,$data,$responseDate,$interval){
		global $ost;
		$timestamp = strtotime($responseDate);
		if($request_type == "temporary"){
			$hours = $sla_units['data']['first-response-time'] + $sla_units['data']['temporary-solution-time'];
			$arg = "First Response";
		}
		if($request_type == "final"){
			$hours = $sla_units['data']['final-solution-time'];			
			$arg = "Temporary Response";
		}
		$str = 'request_type='.$request_type." , hours = ".$hours." , responseDate = ".$this->showDateByserverTinezone($responseDate)."  , interval = ".$interval ;
		$ost->logDebug('Info',$str, true);

		if($interval != 0){
			$new_target = strtotime('+'.$hours.' hours'. '+'.$interval.' seconds', $timestamp); $message = "Tf ".$arg." Missed than ".$request_type." timeline with Await Time";
		}else{
			$new_target = strtotime('+'.$hours.' hours', $timestamp);  
			$message = "Tf ".$arg." Missed than ".$request_type." timeline with No Await";
		}
		$ost->logDebug($message,$new_target , true); 
		$UpdatedResponseTimeline =  date('Y-m-d H:i:s', $new_target);
		$title = 'DEBUG Info #SLA - ADDON: Updated '.$request_type." Timeline";
		$ost->logDebug($title,$this->showDateByserverTinezone($UpdatedResponseTimeline) , true); 
		return $UpdatedResponseTimeline;
	}

	/*
	* @function - getExistingResponseData
	* @Purpose  - Get Saved Response  
	*/ 
    function SlaTimelineResheule($response_type,$ticket_id,$sla_units,$data){  
    
      //echo '<pre>'; print_r($data);
      $forms = DynamicFormEntry::forTicket($ticket_id);
       foreach($forms as $form) {
        if($form->getTitle() == "Ticket Details") { 

          if($response_type == 'temporary'){
          $field_response = $form->getField(FIELD_FIRST_RESPONSE_TIME); 
          $target_timeline = $data['first_response_timeline'];
          $sla_hours  = $sla_units['data']['temporary-solution-time'];
          $current_timeline = $data['temporary_solution_timeline'];
           
          }
          if($response_type == 'final'){
          $field_response = $form->getField(FIELD_TEMP_SOLUTION_TIME); 
          $target_timeline = $data['temporary_solution_timeline'];
          $sla_hours  = $sla_units['data']['final-solution-time'];
          $current_timeline = $data['final_solution_timeline'];
           
          }
           
          $response =  $field_response->getAnswer()->value;
          
          if($response != ""){
            // check if saved first reponse is < target first reponse then shift temporary reponse time 
               echo "<br> target timeline : ".$target_timeline;
               echo "<br> Achieved Response : ".$response;
               $target_timestamp = strtotime($target_timeline);
               $response_timestamp = strtotime($response);
               
               $res_date =  date('Y-m-d H:i:s', $response_timestamp);
               if($target_timestamp > $response_timestamp){
                 
                // reshift temporary solution time
                // Add temporary-solution-time hours to newly first response achieved
                 echo "<br> sla_hours  : ".$sla_hours;
                 
                $new_target_ts = strtotime('+'.$sla_hours.' hours', $response_timestamp);
                 
                echo "<br> new target timestamp : ".$new_target_ts ;
                $target_response_date =  date('Y-m-d H:i:s', $new_target_ts);
                echo "<br> new ".$response_type."date : ".$target_response_date ;
               }else
                 $target_response_date = $current_timeline ;            
          }else{
            $target_response_date = $current_timeline ;
          }
          
          return $target_response_date;   
        } // end ticket details
      } // end form
  } // ENDS FUNCTION 	



/*
* @function - addIntervaltoSchedule
* @Purpose  - add Pause Time Interval to Schedule
*/ 
  function addPauseIntervaltoTimeline($format,$tInterval,$ticket_id,$datetime){
   global $cfg,$ost;

      $sql_sla = "SELECT T.ticket_id, T.sla_id,T.est_duedate, T.reopened,T.closed, T.created, S.schedule_id  FROM ".TABLE_PREFIX."ticket T, ".TABLE_PREFIX."sla S WHERE T.sla_id = S.id AND T.ticket_id =".$ticket_id;

       $rs1 = db_query($sql_sla);
       $result_sla = db_fetch_array($rs1);
       $scheduleId = $result_sla['schedule_id'];
       $sla_id = $result_sla['sla_id'];
       $sla = Sla::lookup($sla_id);


       $message =  " scheduleId= ".$scheduleId." sla_id = ".$sla_id." tInterval".$tInterval.
             ",  datetime=".$datetime." ";
            
        $ost->logDebug("Debug Info #SLA - ADDON: SLA addIntervaltoSchedule ",$message , true);
        $schedule = BusinessHoursSchedule::lookup($scheduleId);
        $tz = new DateTimeZone($cfg->getDbTimezone());
        
        $dt = new DateTime($datetime, $tz);
        // first response date
       // $time = $tInterval; //grace period + interal time
        $gracehours = round(($tInterval/3600),2);
        $sla->grace_period= $gracehours;
        $dt = $sla->addGracePeriod($dt, $schedule);
        $dt->setTimezone($tz);
        $newTemResdate =  $dt->format('Y-m-d H:i:s');  
        $ost->logDebug("Debug Info #SLA - ADDON: addPauseIntervaltoTimeline ! After add Interval to Schedule ",$newTemResdate , true);
        return $newTemResdate;
  

}


function slaMeasurementCorrection($ticket_id, $createddate,$sla_units,$sla_name)
    {
      global $cfg,$ost; 
      $ticketCreated = $createddate;
      $sla_id = $sla_units['data']['sla-plans'];
      //$ost->logDebug("Debug Info #SLA - ADDON: sla_id",$sla_id , true);
      if(isset($sla_id))
        $scheduleId = $this->getScheduleId($sla_id);
      //$ost->logDebug("Debug Info #SLA - ADDON: scheduleId",$scheduleId , true);

       $temp_res_time = $this->checknLoadResponse('temporary',$ticket_id);
       $ost->logDebug("Debug Info #SLA - ADDON: trst",$temp_res_time['time'] , true);	   
	   if($temp_res_time['time'] != 0 AND $temp_res_time['time'] != ""){
		  
			$sqlTotalinterval ="SELECT SUM(".TABLE_PREFIX."sla_addon.interval) totalinterval FROM ".TABLE_PREFIX."sla_addon WHERE ticket_status='awaited' 
			AND ticket_id=".$ticket_id."
			AND created < '".$temp_res_time['time']."'
			" ;
		  
	   }else{		           
	  // Code to fetch the difference of interval if status was await to open 
      $sqlTotalinterval ="SELECT SUM(".TABLE_PREFIX."sla_addon.interval) totalinterval FROM ".TABLE_PREFIX."sla_addon WHERE ticket_status='awaited' AND ticket_id=".$ticket_id;
	  }
	
	  $ost->logDebug("Debug Info #SLA - ADDON: Temp Interval",$sqlTotalinterval , true);
      $sti = db_query($sqlTotalinterval);
      $stiresult = db_fetch_array($sti);	   
      $interval = $stiresult['totalinterval'];
	  $ost->logDebug("Debug Info #SLA - ADDON: before TempInterval",$interval , true);
      if($interval=='')       
        $interval=0;
       
        // Code ends here for interval value
        $sla = Sla::lookup($sla_id);  
       
        $message =  " scheduleId= ".$scheduleId." sla_id = ".$sla_id;
        $ost->logDebug("Debug Info #SLA - ADDON: SLA MC",$message , true);
        $schedule = BusinessHoursSchedule::lookup($scheduleId);
        $tz = new DateTimeZone($cfg->getDbTimezone());
        
        $dt = new DateTime($ticketCreated, $tz);
        // first response date
        $time = $firstResponseHours*3600+($interval/2); //grace period + interal time        
        $gracehours = $sla_units['data']['first-response-time'];
        $sla->grace_period=$gracehours;
        $dt = $sla->addGracePeriod($dt, $schedule);
        $dt->setTimezone($tz);
        $firstResdate =  $dt->format('Y-m-d H:i:s');

        // temp response date       
        $dtT = new DateTime($firstResdate, $tz);
        $timeT = $tempResponseHours*3600+($interval/2); //grace period + interal time        
        $gracehoursT = $sla_units['data']['temporary-solution-time']; 
        $sla->grace_period=$gracehoursT;
        $dtT = $sla->addGracePeriod($dtT, $schedule);
        $dtT->setTimezone($tz);
        $tempResdate =  $dtT->format('Y-m-d H:i:s');

        // final response date
        $dtF = new DateTime($tempResdate, $tz);
        $timeF = $finalResponseHours*3600+($interval/2); //grace period + interal time         
        $gracehoursF = $sla_units['data']['final-solution-time'];
        $sla->grace_period=$gracehoursF;
        
        $dtF = $sla->addGracePeriod($dtF, $schedule);
        $dtF->setTimezone($tz);
        $finalResdate =  $dtF->format('Y-m-d H:i:s');
       
        $UpdatedfirstResTimeline = $firstResdate;
        $UpdatedTempResponseTimeline = $tempResdate;
        $UpdatedFinalResponseTimeline = $finalResdate;

        $data['first_response_timeline'] = $firstResdate; 
        $data['temporary_solution_timeline'] = $tempResdate;
        $data['final_solution_timeline'] = $finalResdate;

        // first response calculation starts   
        /////////////////////////////////////////////////////////////////////////////////
        $first_res_time = $this->checknLoadResponse('first',$ticket_id);
          if(isset($first_res_time['time']) && $first_res_time['time'] != 0 ){   

          $frt = "< ".$first_res_time['time']." -  ".$firstResdate;
          $ost->logDebug("Debug Info #SLA - ADDON: First Response Target Timeline",
		  $this->showDateByserverTinezone($firstResdate) , true);             
               
                $first_response_timeline = strtotime($firstResdate); 
                $actual_response_time = strtotime($first_res_time['time']);
                 
                if($actual_response_time > $first_response_timeline){                   
                  $first_response_status = VALUE_RESPONSE_LIST_MISSED;
               }else{                  
                   $first_response_status = VALUE_RESPONSE_LIST_ACHIEVED;
               }
          }
        ////////////////////////////////////////////////////////////////////////////////////
        if($first_response_status)
        if($first_response_status  != VALUE_RESPONSE_LIST_MISSED) {  // FRT Achieved Case
          $TempResponseTimeline = $this->SlaTimelineResheule("temporary",$ticket_id,$sla_units,$data);           
          if($interval != 0 && $interval != ""){
                $UpdatedTempResponseTimeline = $this->addPauseIntervaltoTimeline('seconds',$interval,$ticket_id,$TempResponseTimeline) ;  
           }else 
             $UpdatedTempResponseTimeline = $TempResponseTimeline;

          $ost->logDebug("Debug Info #SLA - ADDON: Target Temp Timeline",$UpdatedTempResponseTimeline , true);     
        
        }else{  // FRT Missed Case
          $UpdatedfirstResTimeline = $createddate;
          $UpdatedTempResponseTimeline = $this->SlaMissedTimelineResheule('temporary',$ticket_id,$sla_units,$data,$createddate,$interval);
          $ost->logDebug('Temp Response Timeline Result from FRT Missed Case',$UpdatedTempResponseTimeline , true); 
        } 

        // check and load temporary solution
        
      //  $temp_res_time = $this->checknLoadResponse('temporary',$ticket_id);  
        $timeline_temporary_timestamp = strtotime($UpdatedTempResponseTimeline);         
        $actual_temporary_timestamp = strtotime($temp_res_time['time']);
              
                if($actual_temporary_timestamp > $timeline_temporary_timestamp){ 
                  $temporary_solution_status = VALUE_RESPONSE_LIST_MISSED;
               }else{                  
                   $temporary_solution_status = VALUE_RESPONSE_LIST_ACHIEVED;
               }

         $data['temporary_solution_timeline'] = $UpdatedTempResponseTimeline;       
        ///////////////////////////////////////////////////////////////////////////
        
        $sqlTotalinterval ="SELECT SUM(".TABLE_PREFIX."sla_addon.interval) totalinterval FROM ".TABLE_PREFIX."sla_addon WHERE ticket_status='awaited' 
        AND ticket_id=".$ticket_id."
        AND created > '".$temp_res_time['time']."'
        " ;
		$tdate = $this->showDateByserverTinezone($reponse_date);
        $ost->logDebug("Debug Info #SLA - ADDON: QUERY PauseInterval".$tdate,$sqlTotalinterval , true);
        $sti = db_query($sqlTotalinterval);
        $stiresult = db_fetch_array($sti);
        $PauseInterval = $stiresult['totalinterval'];
        $ost->logDebug("Debug Info #SLA - ADDON: PauseInterval",$PauseInterval , true);     

        if($temporary_solution_status)
        if($temporary_solution_status  != VALUE_RESPONSE_LIST_MISSED) {  // TRT Achieved Case
          $FinalResponseTimeline = $this->SlaTimelineResheule("final",$ticket_id,$sla_units,$data);           
          if($PauseInterval != 0 && $PauseInterval != ""){
                $UpdatedFinalResponseTimeline = $this->addPauseIntervaltoTimeline('seconds',$PauseInterval,$ticket_id,$FinalResponseTimeline) ;  
           }else 
             $UpdatedFinalResponseTimeline = $FinalResponseTimeline;

          $ost->logDebug("Debug Info #SLA - ADDON: Target Final Timeline",$UpdatedFinalResponseTimeline , true);     
        
        }else{  // TRT Missed Case
          
          $UpdatedFinalResponseTimeline = $this->SlaMissedTimelineResheule('final',$ticket_id,$sla_units,$data,$UpdatedTempResponseTimeline,$PauseInterval);
          $ost->logDebug('Final Response Timeline Result from TRT Missed Case',$this->showDateByserverTinezone($UpdatedFinalResponseTimeline) , true); 
        } 

        // check and load temporary solution
        
        $final_res_time = $this->checknLoadResponse('final',$ticket_id);  
        $timeline_final_timestamp = strtotime($UpdatedFinalResponseTimeline);         
        $actual_final_timestamp = strtotime($final_res_time['time']);
              
                if($actual_final_timestamp > $timeline_final_timestamp){ 
                  $final_solution_status = VALUE_RESPONSE_LIST_MISSED;
               }else{                  
                   $final_solution_status = VALUE_RESPONSE_LIST_ACHIEVED;
               } 
        ///////////////////////////////////////////////////////////////////////////       
         
        $data['first_response_status'] = $first_response_status;
        $data['temporary_solution_status'] = $temporary_solution_status;
        $data['final_solution_status'] = $final_solution_status;
 
        return $data;     

    }// ENDS FUNCTION


  function showDateByserverTinezone($reponse_date){
	global $cfg;
	//$tz = new DateTimeZone($cfg->getTimezone());  
	$tz = new DateTimeZone('Europe/Berlin');
	$datetime = new DateTime($reponse_date);
	$datetime->setTimeZone($tz);
	return $date = $datetime->format('m/d/y h:i:s A');   
  }


} // ENDS CLASS 



?>