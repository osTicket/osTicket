<?php

  include_once('constants.php');

  class SlaAddonEntry extends VerySimpleModel {
    static $meta = array(
      'table' => SLA_ADDON_TABLE,
      'pk' => array('id'),
      'ordering' => array('-timestamp'),
      'select_related' => array('staff', 'user'),
      'joins' => array(
        'staff' => array(
          'constraint' => array('staff_id' => 'Staff.staff_id'),
          'null' => true,
        ),
        'user' => array(
          'constraint' => array('user_id' => 'User.id'),
          'null' => true,
        ),
      ),
    );  
    

    static function autoCreateTable() {
      global $ost;

      $sql = 'SHOW TABLES LIKE \''.TABLE_PREFIX.'sla_addon\'';
      if (db_num_rows(db_query($sql))){
        $message = "Unable to add new table to `".TABLE_PREFIX.'sla_addon'."`.";
        $ost->logWarning('DB Info #SLA - ADDON: Table already created', $message, false);

         return false;
         
      }
      else{
      $sql = sprintf('CREATE TABLE `%s` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `ticket_id` int(10) unsigned DEFAULT NULL,
          `sla_id` int(10) unsigned DEFAULT NULL,
          `plugin_instance_id` int(10) unsigned DEFAULT NULL,
          `status_id` int(10) unsigned NOT NULL,
          `ticket_status` varchar(30) DEFAULT NULL ,
          `thread_type` varchar(30) DEFAULT NULL ,
          `internal_notes` text DEFAULT NULL ,
          `staff_id` int(10) unsigned DEFAULT NULL ,
          `user_id` int(10) unsigned DEFAULT NULL ,
          `interval` varchar(30) DEFAULT 0 ,              
          `ip` varchar(64) DEFAULT NULL,
          `revision_id` int(10) unsigned DEFAULT 0 ,
          `created` datetime NOT NULL,
          `updated` datetime NOT NULL,
           
          PRIMARY KEY (`id`),
          KEY `staff_id` (`staff_id`),
          KEY `user_id` (`user_id`)
        ) CHARSET=utf8', TABLE_PREFIX.'sla_addon');             
        return db_query($sql);
      }
    }

     //  update ticket status
    static function autoUpdateTicketStatusTable() {
      global $ost;
      
        $sql = 'select `id` from '.TABLE_PREFIX.'ticket_status where 
        `name` = "'.STATUS_AWAITED.'" OR 
        `name` = "'.STATUS_TEMSOL_PROVIDED.'" ' ;
         if (db_num_rows(db_query($sql))){
        $message = "Unable to add new status row to `".TABLE_PREFIX.'ticket_status'."`.".$sql;
        $ost->logWarning('DB Info #SLA - ADDON: Database Row already exists', $message, false);  
        return false;           
      }
      else{
      }         
        $sql = sprintf('INSERT INTO `%s` (
      `name`, `state`, `mode`, `flags`, `sort`, `properties`) VALUES
       ("'.STATUS_AWAITED.'","open",1, 0, 6,"{\"allowreopen\":false,\"allowawaiting\":true,\"reopenstatus\":null,\"35\":\"Awaiting response\"}"),
       ("'.STATUS_TEMSOL_PROVIDED.'","open",1, 0, 7,"{\"allowreopen\":false,\"temporarysolution\":true,\"reopenstatus\":null,\"35\":\"Temporary Solution Provided\"}")', TABLE_PREFIX.'ticket_status');  

        return db_query($sql);           
     }


   //  Create Dynamic Fields for ticket 
    static function createResponseStatusList() {
     global $ost;

      $sql = 'select `id` from '.TABLE_PREFIX.'list where `name` = "SLA Response Status"' ;
         if (db_num_rows(db_query($sql))){
        $message = "Unable to add new SLA Response Status List row to `".TABLE_PREFIX.'list'."`.";
        $ost->logWarning('DB Info #SLA - ADDON: Database Row already exists', $message, false);
        return false;           
      }
      else{
        
         $sql = sprintf('INSERT INTO `%s` (`name`, `name_plural`, `sort_mode`, `masks`, `type`, `configuration`, `notes`, `created`) VALUES
         ("'.NAME_RESPONSE_LIST.'", "'.PLURAL_NAME_RESPONSE_LIST.'", "Alpha",0, NULL, "", "This field is used to manage the response status for ticket.",NOW())', TABLE_PREFIX.'list');
        $rs = db_query($sql);
     
         if ($rs){
        $sql2 = 'select `id` from '.TABLE_PREFIX.'list ORDER BY id DESC LIMIT 1' ;
        $result2 =  db_fetch_row(db_query($sql2));          
        $list_id  = $result2[0];

        if(isset($list_id)){          
          $sql_insert_items = sprintf('INSERT INTO `%s` (`list_id`, `status`, `value`, `extra`, `sort`, `properties`) VALUES
           ( '.$list_id.', 1 , "'.VALUE_RESPONSE_LIST_MISSED.'", "'.ABBREVIATION_RESPONSE_LIST_MISSED.'", 1, "[]"),
           ( '.$list_id.', 1, "'.VALUE_RESPONSE_LIST_ACHIEVED.'", "'.ABBREVIATION_RESPONSE_LIST_ACHIEVED.'", 1, "[]")', TABLE_PREFIX.'list_items');
          db_query($sql_insert_items); 
          return $list_id;        
        }
        }
      }
       
    } // FUNCTION ENDS
    
    
       

  } // end class
