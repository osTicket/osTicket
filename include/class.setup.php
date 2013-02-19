<?php
/*********************************************************************
    class.setup.php

    osTicket setup wizard.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

Class SetupWizard {

    //Mimimum requirements
    var $prereq = array('php'   => '4.3',
                        'mysql' => '4.4');

    //Version info - same as the latest version.
    
    var $version =THIS_VERSION;
    var $version_verbose = THIS_VERSION;

    //Errors
    var $errors=array();

    function SetupWizard(){
        $this->errors=array();
        $this->version_verbose = ('osTicket v'. strtoupper(THIS_VERSION));

    }

    function load_sql_file($file, $prefix, $abort=true, $debug=false) {
        
        if(!file_exists($file) || !($schema=file_get_contents($file)))
            return $this->abort('Error accessing SQL file '.basename($file), $debug);

        return $this->load_sql($schema, $prefix, $abort, $debug);
    }

    /*
        load SQL schema - assumes MySQL && existing connection
        */
    function load_sql($schema, $prefix, $abort=true, $debug=false) {

        # Strip comments and remarks
        $schema=preg_replace('%^\s*(#|--).*$%m', '', $schema);
        # Replace table prefis
        $schema = str_replace('%TABLE_PREFIX%', $prefix, $schema);
        # Split by semicolons - and cleanup 
        if(!($statements = array_filter(array_map('trim', @explode(';', $schema)))))
            return $this->abort('Error parsing SQL schema', $debug);


        @mysql_query('SET SESSION SQL_MODE =""');
        foreach($statements as $k=>$sql) {
            //Note that we're not using db_query - because we want to control how errors are reported.
            if(mysql_query($sql)) continue;
            $error = "[$sql] ".mysql_error();
            if($abort)
                    return $this->abort($error, $debug);
        }

        return true;
    }

    function getVersion() {
        return $this->version;
    }

    function getVersionVerbose() {
        return $this->version_verbose;
    }

    function getPHPVersion() {
        return $this->prereq['php'];
    }

    function getMySQLVersion() {
        return $this->prereq['mysql'];
    }

    function check_php() {
        return (version_compare(PHP_VERSION, $this->getPHPVersion())>=0);
    }

    function check_mysql() {
        return (extension_loaded('mysql'));
    }

    function check_prereq() {
        return ($this->check_php() && $this->check_mysql());
    }

    /*
        @error is a mixed var.
    */
    function abort($error, $debug=false) {
       
        if($debug) echo $error;
        $this->onError($error);

        return false; // Always false... It's an abort.
    }

    function setError($error) {
    
        if($error && is_array($error))
            $this->errors = array_merge($this->errors, $error);
        elseif($error)
            $this->errors[] = $error;
    }

    function getErrors(){
        return $this->errors;
    }

    function onError($error) {
       return $this->setError($error);
    }
}
?>
