<?php
include_once('class.sla_addon.php');
include_once('class.sla_helper.php');

require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.thread.php'); //getobjtype
require_once(INCLUDE_DIR . 'class.ticket.php');
include_once(INCLUDE_DIR. 'class.sla.php');
include_once(INCLUDE_DIR. 'class.list.php');
include_once(INCLUDE_DIR. 'class.dynamic_forms.php'); 
require_once('config.php');

class SlaAddOnPlugin extends Plugin {

     var $config_class = "SlaAddOnPluginConfig";
     var $instance_flag = 1;     

    
    /**
     * The entrypoint of the plugin, keep short, always runs.
     */
    function bootstrap() {

      $config = $this->getConfig();  
      $slaObj = new SlaHelper();   
       
      if($this->instance_flag == 1){ 
	    
		// Listen for osTicket to tell us SLA update for a new ticket      
        Signal::connect('sla.update', function($obj, &$data) use ($config, $instance_id) { 
            $this->onSlaUpdate($obj,$config);         
        });
         
        // Listen for osTicket to tell us it's made a new ticket or updated        
        Signal::connect('ticket.created', function($obj, &$data) use ($config, $instance_id) { 
            $this->onTicketCreated($obj,$config);         
        });

       // Listen for osTicket to tell us ticket status is changed to awaited
         Signal::connect('ticket.reply', function($obj, &$data) use ($config, $instance_id) { 
           $this->onTicketReply($obj,$config);         
        });

        // Listen for osTicket to tell us ticket status is changed to awaited
         Signal::connect('status.awaited', function($obj, &$data) use ($config, $instance_id) { 
           $this->onStatusAwaited($obj,$config);         
        });

        // Listen for osTicket to tell us ticket status is changed to temporary solution
         Signal::connect('status.temporarysolution', function($obj, &$data) use ($config, $instance_id) { 
            $this->onStatusTemporarySolution($obj,$config);         
        });

       
        // Listen for osTicket to tell us ticket status is changed to final solution
         Signal::connect('status.finalsolution', function($obj, &$data) use ($config, $instance_id) { 
             $this->onStatusFinalSolution($obj,$config);         
        });  

         Signal::connect('object.created', function($obj, &$data) use ($config) {     
            $slaObj = new SlaHelper();
            if($data['type'] == "message"){
                
              if(isset($_POST['reply_status_id'])){
                
                  $status = $slaObj->getStatusNameById($_POST['reply_status_id']);
                
                  if($status == STATUS_AWAITED)
                     $type="B";                
                  if($status == STATUS_OPEN||$status == STATUS_RESOLVED||$status ==STATUS_TEMSOL_PROVIDED||$status == STATUS_CLOSED)
                     $type="R";           
              }  
              if(!isset($type)){
                  $type="M";
              } 
               if($type == "M") 
                 $this->onCustomerResponse($obj,$config,$type);
            }            

          });       
      
	 
	 	  
       $this->instance_flag = 2 ;         
      }
            
    } // ENDS SIGNAL 

 
     
 
    /*
    * @function - onCustomerResponse
    * @Purpose  -After customer reponse on await status update ticket status to open ,entry save in addon table 
    */ 
    function onCustomerResponse(Ticket $ticket ,$config,$type) {
        $slaObj = new SlaHelper();
        $sla_id = $ticket->sla_id;
        $ticket_id = $ticket->getId();
        $sla_info = $this->getSlaInfo($sla_id,$ticket_id);
        $sla_units = $sla_info['sla'];
        $instance_id = $sla_info['sla']['instance_id'];
  
        $ticket_status_id = 1;        
        $thread_type = $type;
        $staff_id = $ticket->ht['staff_id'];
        $user_id =  $ticket->ht['user_id'];
        $revision_id = NULL;
        $ip_address = $ticket->ht['ip_address'];                
         
        $ticket_status = "open";
        if(!isset($staff_id)){
           $staff_id = NULL; 
        }
        /*if($thread_type == "B")
           $note_subject =  "Message with Awaited Status";*/
        if($thread_type == "M" )
           $note_subject =  "Message";
        if($thread_type == "R")
           $note_subject =  "Response";
        $internal_notes = $note_subject." Updated";
         

        // update ticket extra fields
         if(isset($ticket_id) && (isset($ticket->lastMsgId )) && $type !="B"){
        $ticketObj = Ticket::lookup($ticket_id);   
        $ticketObj->status_id  = 1; 
        $ticketObj->save(); 
        }   
        // update ticket extra ends
        /* if($type == "B"){
          $ticket_status = "awaited";
          $thread_type = "M";
        }*/

        //if(!$is_ticket_existed){
          $sql = sprintf('INSERT INTO `%s` ( `ticket_id`, `sla_id`, `plugin_instance_id`, `status_id`, `ticket_status`, `thread_type`, `internal_notes`, `staff_id`, `user_id`, `ip`, `revision_id`, `created`) 
           VALUES
         ("'.$ticket_id.'", "'.$sla_id.'", "'.$instance_id.'", "'.$ticket_status_id .'","'.$ticket_status .'", "'.$thread_type.'", "'.$internal_notes.'", "'.$staff_id.'", "'.$user_id.'", "'.$ip_address.'", "'.$revision_id.'",
          NOW())', TABLE_PREFIX.'sla_addon');  
            
           $result = db_query($sql);
           if (db_num_rows($result)){
            $message = "Unable to add new status customer response row to `".TABLE_PREFIX.'sla_addon'."`.";
            $ost->logWarning('DB Info #SLA - onCustomerResponse ', $message, false);  
            return false;           
        }

		    /* Code to calculate time difference and update estdue date */
        if($ticket_status=='open' && isset($ticket_id)){
             $slaObj->SlaPauseOnAwait($ticket_id);		  
        }
		    /* Code ends here to calculate time difference */
           return $result;
      
    }// ENDS FUNCTION 


    /*
    * @function - onStatusFinalSolution
    * @Purpose  - Status change to final solution processing
    */ 

  function onStatusFinalSolution(Ticket $ticket ,$config){
        $slaObj = new SlaHelper();
        $sla_id = $ticket->sla_id;
        $ticket_id = $ticket->getId();
        $sla_info = $this->getSlaInfo($sla_id,$ticket_id);
         
        $sla_units = $sla_info['sla'];
        $instance_id = $sla_info['sla']['instance_id'];
        $ticket_created = $ticket->created; 
        $ticket_status_id = $ticket->getStatus()->getId();
        $ticket_status = STATUS_FINALSOL_PROVIDED;  
        $thread_type = "R";
        $staff_id = $ticket->ht['staff_id'];
        $user_id =  $ticket->ht['user_id'];
        $revision_id = NULL ;
        $ip_address = $ticket->ht['ip_address'];
        $internal_notes = "Update - Status to ".$ticket_status;        
         
        // save row for new statys
        // insert row in table starts
          $sql = sprintf('INSERT INTO `%s` ( `ticket_id`, `sla_id`, `plugin_instance_id`, `status_id`, `ticket_status`, `thread_type`, `internal_notes`, `staff_id`, `user_id`,`ip`, `created`) 
           VALUES
         ("'.$ticket_id.'", "'.$sla_id.'", "'.$instance_id.'", "'.$ticket_status_id .'","'.$ticket_status .'", "'.$thread_type.'", "'.$internal_notes.'", "'.$staff_id.'", "'.$user_id.'","'.$ip_address.'", NOW())', SLA_ADDON_TABLE);
        
        $insert_temp_resp_row = db_query($sql);

        // check first response
        $first_res_time = $slaObj->checknLoadResponse($reponse_type="first",$ticket_id);
            
        if( $first_res_time['time'] == 0 || !isset($first_res_time['time']) ){  
          $first_res_time = $slaObj->getResponse('first',$ticket_id,$ticket_created,$sla_units,$sla_id);
          // means first response not set so also need to set first response below
          $slaObj->SetResponse("first",$ticket_id,$sla_units,$sla_id,$first_res_time);// set temporary reponse     
          //$slaObj->SetResponseByPassData("first",$ticket_id,$first_response);          
             
        }
               
        //check temporary response if already have
        $res_time = $slaObj->checknLoadResponse("temporary",$ticket_id);        
        echo 'load temp<pre>' ; print_r($res_time); 
         if($res_time['time'] == 0 || !isset($res_time['time']) ){           
           
           $temporary_res_time = $slaObj->SetResponse($reponse_type="temporary",$ticket_id,$sla_units,$sla_id,$first_res_time);
           //$temporary_res_time = $slaObj->SetResponseByPassData($reponse_type="temporary",$ticket_id,$res_time);// set temporary response
           $res_time = $temporary_res_time; 
        }
       // die("here")  ;
          //echo 'first_res_time-<pre>'; print_r($temporary_res_time); die("mm");
        $final_response = $slaObj->SetResponse($reponse_type="final",$ticket_id,$sla_units,$sla_id,$res_time);// set final reponse 


        
               
      } // ENDS FUNCTION 
        
  /*
    * @function - onStatusTemporarySolution
    * @Purpose  - Status change to temporary solution Operation processing
  */ 
   
  function onStatusTemporarySolution(Ticket $ticket ,$config){
        $slaObj = new SlaHelper();
        $sla_id = $ticket->sla_id;
        $ticket_id = $ticket->getId();
        $sla_info = $this->getSlaInfo($sla_id,$ticket_id);
         
        $sla_units = $sla_info['sla'];
        $instance_id = $sla_info['sla']['instance_id'];
        $ticket_created = $ticket->created; 
        $ticket_status_id = $ticket->getStatus()->getId();
        $ticket_status = STATUS_TEMSOL_PROVIDED;  
        $thread_type = "R";
        $staff_id = $ticket->ht['staff_id'];
        $user_id =  $ticket->ht['user_id'];
        $revision_id = NULL ;
        $ip_address = $ticket->ht['ip_address'];
        $internal_notes = "Update - Status to ".$ticket_status;        
         
        // save row for new statys
        // insert row in table starts
          $sql = sprintf('INSERT INTO `%s` ( `ticket_id`, `sla_id`, `plugin_instance_id`, `status_id`, `ticket_status`, `thread_type`, `internal_notes`, `staff_id`, `user_id`,`ip`, `created`) 
           VALUES
         ("'.$ticket_id.'", "'.$sla_id.'", "'.$instance_id.'", "'.$ticket_status_id .'","'.$ticket_status .'", "'.$thread_type.'", "'.$internal_notes.'", "'.$staff_id.'", "'.$user_id.'","'.$ip_address.'", NOW())', SLA_ADDON_TABLE);
        
        $insert_temp_resp_row = db_query($sql);
          
        //check response if already have
        $res_time = $slaObj->checknLoadResponse($reponse_type="first",$ticket_id);  
        //echo '1-<pre>'; print_r($res_time);
        if( $res_time['time'] == 0 || !isset($res_time['time'])){ 
          $res_time = $slaObj->getResponse('first',$ticket_id,$ticket_created,$sla_units,$sla_id); 
          // means first response not set so also set first reponse below
          $slaObj->SetResponse($reponse_type="first",$ticket_id,$sla_units,$sla_id,$res_time);// set temporary reponse  

        }        
         //echo '2-<pre>'; print_r($res_time); die("hete") ; 

        $response = $slaObj->SetResponse($reponse_type="temporary",$ticket_id,$sla_units,$sla_id,$res_time);// set temporary reponse    
              
      } // ENDS FUNCTION 
      
       
     
    /*
    * @function - onTicketReply
    * @Purpose  - Reply Ticket Operation processing
    */ 
      
    function onTicketReply(Ticket $ticket ,$config){
      global $ost;  
      $slaObj = new SlaHelper();
      $sla_id = $ticket->sla_id;
      $ticket_id = $ticket->getId();
      $sla_info = $this->getSlaInfo($sla_id,$ticket_id);
      $sla_units = $sla_info['sla'];
      $instance_id = $sla_info['sla']['instance_id'];
      
      //check response if already have
      $is_frt = $slaObj->checknLoadResponse($reponse_type="first",$ticket_id); //   check response 
      
      if( $is_frt['time'] == 0 || !isset($is_frt['time']))   {      
         $first_response = $slaObj->SetResponse($reponse_type="first",$ticket_id,$sla_units,$sla_id); //   set response 
	  }  
           
    }// ENDS FUNCTION 
     
    /*
    * @function - onStatusAwaited
    * @Purpose  - Await Operation processing
    */ 
    function onStatusAwaited(Ticket $ticket ,$config) {
        global $ost,$cfg;  
        $slaObj = new SlaHelper();
        $sla_id = $ticket->sla_id;
        $ticket_id = $ticket->getId();
        $sla_info = $this->getSlaInfo($sla_id,$ticket_id);
        $sla_units = $sla_info['sla'];
        $instance_id = $sla_info['sla']['instance_id'];
        
        $status = STATUS_AWAITED;  
        $status_id = $slaObj->getStatusIdByName($status);        
        $ticket_id = $ticket->getId();
        $thread_type = "N";  
       
        // sla adddon row insert
        $ticket_status_id = $ticket->getStatus()->getId();
        $ticket_state = $ticket->getStatus()->getState();
        $thread_type = "N";
        $staff_id = $ticket->ht['staff_id'];
        $user_id =  $ticket->ht['user_id'];
        $revision_id = NULL;
         
        $ip_address = $ticket->ht['ip_address'];
        $internal_notes = "Update - Status to "."Awaited".$comments;        
        $ticket_status = "awaited";
        $ticket_created = $ticket->created; 
        if(!isset($staff_id)){
           $staff_id = NULL; 
        }
		  
		$ticket_awaited = date("Y-m-d H:i:s");
		/* $tz = new DateTimeZone($cfg->getTimezone());  
		$datetime = new DateTime($date);
		$datetime->setTimeZone($tz);
		$ticket_awaited = $datetime->format('Y-m-d H:i:s'); */			
		$ost->logDebug('Onstatus Await',$ticket_awaited , true);
       
        // insert row in table starts
          $sql = sprintf('INSERT INTO `%s` ( `ticket_id`, `sla_id`, `plugin_instance_id`, `status_id`, `ticket_status`, `thread_type`, `internal_notes`, `staff_id`, `user_id`,`ip`, `created`) 
           VALUES
         ("'.$ticket_id.'", "'.$sla_id.'", "'.$instance_id.'", "'.$ticket_status_id .'","'.$ticket_status .'", "'.$thread_type.'", "'.$internal_notes.'", "'.$staff_id.'", "'.$user_id.'","'.$ip_address.'", "'.$ticket_awaited.'")', TABLE_PREFIX.'sla_addon');
        
        $insert_await_row = db_query($sql);
        
        //check response if already have
        $is_frt = $slaObj->checknLoadResponse($reponse_type="first",$ticket_id); //   check response  
            
        if($is_frt['time'] == 0 || !isset($is_frt['time'])){ 
        
          $res_time = $slaObj->getResponse('await',$ticket_id,$ticket_created,$sla_units,$sla_id); 
              
          // means first response not set so also set first reponse below
        //  $slaObj->SetResponse($reponse_type="first",$ticket_id,$sla_units,$sla_id,$res_time);
        $response = $slaObj->SetResponseByPassData("first",$ticket_id,$res_time);// set temporary response
        } 
        // ends 
         

    }// ENDS FUNCTION 
    

    /**
     * @global $cfg
     * @param Ticket $ticket
     * @throws Exception
     */
   

    function onTicketCreated(Ticket $ticket ,$config) {
      
        $slaObj = new SlaHelper();
        $sla_id = $ticket->sla_id;
        $ticket_id = $ticket->getId();
        $sla_info = $this->getSlaInfo($sla_id,$ticket_id);
        $sla_units = $sla_info['sla'];
        $instance_id = $sla_info['sla']['instance_id'];

        $ticket_status = "open";
        $ticket_status_id = $ticket->getStatus()->getId();
        $ticket_state = $ticket->getStatus()->getState();
        $thread_type = "T";
        $staff_id = $ticket->ht['staff_id'];
        $user_id =  $ticket->ht['user_id'];
        $revision_id = NULL;         
        $ip_address = $ticket->ht['ip_address'];
        $internal_notes = "Entry - Ticket Create. ";        
         
        if(!isset($staff_id)){
           $staff_id = NULL; 
        }         
      
        if(!$is_ticket_existed){
           $sql = sprintf('INSERT INTO `%s` ( `ticket_id`, `sla_id`, `plugin_instance_id`, `status_id`, `ticket_status`, `thread_type`, `internal_notes`, `staff_id`, `user_id` , `ip`, `revision_id`, `created`) 
          VALUES
         ("'.$ticket_id.'", "'.$sla_id.'", "'.$instance_id.'", "'.$ticket_status_id .'","'.$ticket_status .'", "'.$thread_type.'", "'.$internal_notes.'", "'.$staff_id.'", "'.$user_id.'", "'.$ip_address.'", "'.$revision_id.'", 
         NOW())', TABLE_PREFIX.'sla_addon');  
           return db_query($sql); 
        }  
 
    }// ENDS FUNCTION 
	
	
	/**
     * @function - onSlaUpdate
     * @Purpose Ticket sla correction on sla update
     *  
    **/ 
    function onSlaUpdate( $obj,$config) {
       global $ost;
	   $new_sla_id = $obj['new_sla_id'];
	   $current_sla_id = $obj['current_sla_id'];
	   $ticket_id = $obj['ticket_id'];
	   $ticket = Ticket::lookup($ticket_id);
	   $message = "Debug Info #SLA - ADDON: slaCorrection Starts For ".$ticket_id."";
       $ost->logDebug($message,$ticket_id, true);
   
		if(isset($current_sla_id)  &&  isset($new_sla_id)){
			if($current_sla_id != $new_sla_id){
				//echo $current_sla_id."-".$new_sla_id.""."ticket-id=".$ticket_id;       
				$slaObj = new SlaHelper();
				$sla_id = $new_sla_id;      
				$sla_info = $this->getSlaInfo($sla_id,$ticket_id);
				$sla_units = $sla_info['sla'];             
				$slaObj->slaCorrection($ticket,$ticket_id,$sla_id,$sla_units);             
			  }
        }
       
    }// FUNCTION ENDS   

  
  /*
    * @function - getSlaInfo
    * @Purpose  - Get Sla Related Info
  */
  function getSlaInfo($sla_id,$ticket_id){
        global $ost;          
        $sla_units = array();  
        $slaObj = new SlaHelper();
        $ticket_id = $ticket->ht['ticket_id'];         
        $plugin_id = $slaObj->getPluginId();
        $instance  = Plugin::getActiveInstances()->getIterator(); 
            
        if(Plugin::getId() == $plugin_id)
        foreach ($instance->getIterator() as $key=>$value) {                
                $instance_data[] = array('data' => $value->getConfig()->getInfo() ,
                 'instance' =>$value->getNamespace()
                );                   
            } 
           
        // Arrange instance data to get matching instance
        foreach ( $instance_data as $key=>$value ) {  
                  
             //$sla_instance[$value['data']['sla-plans']] = $value;
             if($value['data']['sla-plans']==$sla_id){
              $sla_instance[$value['data']['sla-plans']] = $value;
            }
        }        
 
        $sla_plan_id_instance = $instance_data[0]['data']['sla-plans'];
        $sla_plan_id_instance =$sla_id;
            if(!isset($sla_plan_id_instance) || $sla_plan_id_instance == ""){
                $message = "Unable to add new row to `".TABLE_PREFIX.'sla_addon'."`.";
                $ost->logWarning('DB Info #SLA - ADDON: SLA Instance not avaiable in SLA - ADDON Plugin', $message, false);
        }else{  
          if($sla_id == $sla_plan_id_instance){  
             $sla_units = $sla_instance[$sla_id];
             $sla_instance = $sla_instance[$sla_id]['instance'];
             $instance_id = explode(".instance.",$sla_instance);
             $instance_id =  $instance_id['1']; 
             $data['sla'] = $sla_units;
             $data['sla']['instance_id'] = $instance_id;
           // print_r($data); 
            
             return $data; 
            }else{
            $message = "Unable to update First Response as Ticket SLA is not added in SLA ADDON Plugin";
            $ost->logWarning('DB Info #SLA - ADDON: SLA Instance not avaiable in SLA - ADDON Plugin', $message, false);
          } 

        }
      } // FUNCTION ENDS

   
     
  /*
    * @function - enable
    * @Purpose  - Create table and database changes related to db
  */
      function enable() {         
        SlaAddonEntry::autoCreateTable();        
        SlaAddonEntry::autoUpdateTicketStatusTable();    
        $list_id = SlaAddonEntry::createResponseStatusList();    
        return parent::enable();
    }


   
 
}


?>