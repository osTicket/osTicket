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

class SetupWizard {

    //Mimimum requirements
    static protected $prereq = array(
            'php' => '5.6',
            'mysql' => '5.0');

    //Version info - same as the latest version.

    var $version =THIS_VERSION;
    var $version_verbose = THIS_VERSION;

    //Errors
    var $errors=array();

    function __construct(){
        $this->errors=array();
        $this->version_verbose = sprintf(__('osTicket %s' /* <%s> is for the version */),
            THIS_VERSION);

    }

    function load_sql_file($file, $prefix, $abort=true, $debug=false) {

        if(!file_exists($file) || !($schema=file_get_contents($file)))
            return $this->abort(sprintf(__('Error accessing SQL file %s'),basename($file)), $debug);

        return $this->load_sql($schema, $prefix, $abort, $debug);
    }

    /*
        load SQL schema - assumes MySQL && existing connection
        */
    function load_sql($schema, $prefix, $abort=true, $debug=false) {
        global $ost;

        # Strip comments and remarks
        $schema=preg_replace('%^\s*(#|--).*$%m', '', $schema);
        # Replace table prefix
        $schema = str_replace('%TABLE_PREFIX%', $prefix, $schema);
        # Split by semicolons - and cleanup
        if(!($statements = array_filter(array_map('trim',
                // Thanks, http://stackoverflow.com/a/3147901
                preg_split("/;(?=(?:[^']*'[^']*')*[^']*$)/", $schema)))))
            return $this->abort(__('Error parsing SQL schema'), $debug);


        db_query('SET SESSION SQL_MODE =""', false);
        foreach($statements as $k=>$sql) {
            if(db_query($sql, false)) continue;
            $error = "[$sql] ".db_error();
            if ($abort)
                return $this->abort($error, $debug);
            elseif ($debug && $ost)
                $ost->logDBError('DB Error #'.db_errno(), $error, false);
        }

        return true;
    }

    function getVersion() {
        return $this->version;
    }

    function getVersionVerbose() {
        return $this->version_verbose;
    }

    static function getPHPVersion() {
        return self::$prereq['php'];
    }

    static function getMySQLVersion() {
        return self::$prereq['mysql'];
    }

    function check_php() {
        return (version_compare(PHP_VERSION, self::getPHPVersion())>=0);
    }

    function check_mysql() {
        return (extension_loaded('mysqli'));
    }

    function check_mysql_version() {
        return (version_compare(db_version(), self::getMySQLVersion())>=0);
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
