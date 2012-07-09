<?php
/*********************************************************************
    mysql.php

    Collection of MySQL helper interface functions. 

    Mostly wrappers with error/resource checking.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

    function db_connect($host, $user, $passwd, $db = "") {
        
        //Assert
        if(!strlen($user) || !strlen($passwd) || !strlen($host))
      	    return NULL;

        //Connect
        if(!($dblink =@mysql_connect($host, $user, $passwd)))
            return NULL;

        //Select the database, if any.
        if($db) db_select_database($db);

        //set desired encoding just in case mysql charset is not UTF-8 - Thanks to FreshMedia
        @mysql_query('SET NAMES "UTF8"');
        @mysql_query('SET COLLATION_CONNECTION=utf8_general_ci');

        @db_set_variable('sql_mode', '');

        return $dblink;	
    }

    function db_close(){
        global $dblink;
        return @mysql_close($dblink);
    }

    function db_version(){

        $version=0;
        if(preg_match('/(\d{1,2}\.\d{1,2}\.\d{1,2})/', 
                mysql_result(db_query('SELECT VERSION()'),0,0),
                $matches))                                      # nolint
            $version=$matches[1];                               # nolint

        return $version;
    }

    function db_timezone() {
        return db_get_variable('time_zone');
    }

    function db_get_variable($variable, $type='session') {
        $sql =sprintf('SELECT @@%s.%s',$type,$variable);
        return db_result(db_query($sql));
    }

    function db_set_variable($variable, $value, $type='session') {
        $sql =sprintf('SET %s %s=%s',strtoupper($type), $variable, db_input($value));
        return db_query($sql);
    }


    function db_select_database($database) {
        return ($database && @mysql_select_db($database));
    }

    function db_create_database($database, $charset='utf8', $collate='utf8_unicode_ci') {
        return @mysql_query(sprintf('CREATE DATABASE %s DEFAULT CHARACTER SET %s COLLATE %s',$database,$charset,$collate));
    }
   
	// execute sql query
	function db_query($query, $database="", $conn=""){
        global $ost;
       
		if($conn) { /* connection is provided*/
            $result = ($database)?mysql_db_query($database, $query, $conn):mysql_query($query, $conn);
   	    } else {
            $result = ($database)?mysql_db_query($database, $query):mysql_query($query);
   	    }
                
        if(!$result && $ost) { //error reporting
            $msg='['.$query.']'."\n\n".db_error();
            $ost->logDBError('DB Error #'.db_errno(), $msg);
            //echo $msg; #uncomment during debuging or dev.
        }

        return $result;
	}

	function db_squery($query){ //smart db query...utilizing args and sprintf
	
		$args  = func_get_args();
  		$query = array_shift($args);
  		$query = str_replace("?", "%s", $query);
  		$args  = array_map('db_real_escape', $args);
  		array_unshift($args,$query);
  		$query = call_user_func_array('sprintf',$args);
		return db_query($query);
	}

	function db_count($query){		
        return db_result(db_query($query));
	}

    function db_result($result,$row=0) {
        return ($result)?mysql_result($result,$row):NULL;
    }

	function db_fetch_array($result,$mode=false) {
   	    return ($result)?db_output(mysql_fetch_array($result,($mode)?$mode:MYSQL_ASSOC)):NULL;
  	}

    function db_fetch_row($result) {
        return ($result)?db_output(mysql_fetch_row($result)):NULL;
    }

    function db_fetch_field($result) {
        return ($result)?mysql_fetch_field($result):NULL;
    }   

    function db_assoc_array($result,$mode=false) {
	    if($result && db_num_rows($result)) {
      	    while ($row=db_fetch_array($result,$mode))
         	    $results[]=$row;
        }
        return $results;
    }

    function db_num_rows($result) {
   	    return ($result)?mysql_num_rows($result):0;
    }

	function db_affected_rows() {
      return mysql_affected_rows();
    }

  	function db_data_seek($result, $row_number) {
   	    return mysql_data_seek($result, $row_number);
  	}

  	function db_data_reset($result) {
   	    return mysql_data_seek($result,0);
  	}

  	function db_insert_id() {
   	    return mysql_insert_id();
  	}

	function db_free_result($result) {
   	    return mysql_free_result($result);
  	}
  
	function db_output($param) {

        if(!function_exists('get_magic_quotes_runtime') || !get_magic_quotes_runtime()) //Sucker is NOT on - thanks.
            return $param;

        if (is_array($param)) {
      	    reset($param);
      	    while(list($key, $value) = each($param))
        	    $param[$key] = db_output($value);

      	    return $param;

    	}elseif(!is_numeric($param)) {
            $param=trim(stripslashes($param));
        }

        return $param;
  	}

    //Do not call this function directly...use db_input
    function db_real_escape($val,$quote=false){

        //Magic quotes crap is taken care of in main.inc.php
        $val=mysql_real_escape_string($val);

        return ($quote)?"'$val'":$val;
    }

    function db_input($param,$quote=true) {

        //is_numeric doesn't work all the time...9e8 is considered numeric..which is correct...but not expected.
        if($param && preg_match("/^\d+(\.\d+)?$/",$param))
            return $param;

        if($param && is_array($param)) {
            reset($param);
            while (list($key, $value) = each($param)) {
                $param[$key] = db_input($value,$quote);
            }

            return $param;
        }

        return db_real_escape($param,$quote);
    }

	function db_error(){
   	    return mysql_error();   
	}
   
    function db_errno(){
        return mysql_errno();
    }
?>
