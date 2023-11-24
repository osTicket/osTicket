<?php
  // DEFINE TABLES
      define('SLA_ADDON_TABLE', TABLE_PREFIX . 'sla_addon');
      define('SLA_ADDON_RESPONSE_ENTRY_TABLE', TABLE_PREFIX . 'sla_addon_reponse_entry');
      define('STATUS_TABLE', TABLE_PREFIX . 'ticket_status');
      
      
      // STATUS CONSTANT
      define('STATUS_AWAITED','Awaiting response');
      define('STATUS_TEMSOL_PROVIDED','Temporary solution provided');
      define('STATUS_FINALSOL_PROVIDED','Closed');            
      define('STATUS_OPEN','Open'); 
      define('STATUS_RESOLVED','Resolved');
      define('STATUS_CLOSED','Closed');
      define('STATUS_ARCHIVED','Archived'); 
      define('STATUS_DELETED','Deleted');  
      
      // DEFINE RESPONSE LIST
      define('NAME_RESPONSE_LIST','SLA Response Status');
      define('PLURAL_NAME_RESPONSE_LIST','SLA Response Status List');
      // VALUE OF RESPONSE LIST STATUS
      define('VALUE_RESPONSE_LIST_MISSED','Missed');
      define('ABBREVIATION_RESPONSE_LIST_MISSED','Missed');

      define('VALUE_RESPONSE_LIST_ACHIEVED','Achieved');
      define('ABBREVIATION_RESPONSE_LIST_ACHIEVED','Achieved');

		// Define fields name constants
	  define('FIELD_FIRST_RESPONSE_TIME','first_response_time');
	  define('FIELD_FIRST_RESPONSE_STATUS','first_response_status');
	  define('FIELD_TEMP_SOLUTION_TIME','temporary_solution_time');
	  define('FIELD_TEMP_SOLUTION_STATUS','temporary_solution_status');
	  define('FIELD_FINAL_SOLUTION_TIME','final_solution_time');
	  define('FIELD_FINAL_SOLUTION_STATUS','final_solution_status');
      
      //  extra variables
        define('FLAG_FIRST_RESPONSE','First Response'); // only for constant
        define('FLAG_TEMP_SOLUTION','Temporary Solution');
        define('FLAG_FINAL_SOLUTION','Final Solution');


?>