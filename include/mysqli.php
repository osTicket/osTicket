<?php
/*********************************************************************
    mysqli.php

    Collection of MySQL helper interface functions.

    Mostly wrappers with error/resource checking.

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
$__db = null;

function db_connect($host, $user, $passwd, $options = array()) {
    global $__db;

    //Assert
    if(!strlen($user) || !strlen($host))
        return NULL;

    if (!($__db = mysqli_init()))
        return NULL;

    // Setup SSL if enabled
    if (isset($options['ssl']))
        $__db->ssl_set(
                $options['ssl']['key'],
                $options['ssl']['cert'],
                $options['ssl']['ca'],
                null, null);
    elseif(!$passwd)
        return NULL;

    //Connectr
    if(!@$__db->real_connect($host, $user, $passwd))
        return NULL;

    //Select the database, if any.
    if(isset($options['db'])) $__db->select_db($options['db']);

    //set desired encoding just in case mysql charset is not UTF-8 - Thanks to FreshMedia
    @$__db->query('SET NAMES "utf8"');
    @$__db->query('SET CHARACTER SET "utf8"');
    @$__db->query('SET COLLATION_CONNECTION=utf8_general_ci');

    @db_set_variable('sql_mode', '');

    return $__db;
}

function db_close() {
    global $__db;
    return @$__db->close();
}

function db_version() {

    $version=0;
    if(preg_match('/(\d{1,2}\.\d{1,2}\.\d{1,2})/',
            db_result(db_query('SELECT VERSION()')),
            $matches))                                      # nolint
        $version=$matches[1];                               # nolint

    return $version;
}

function db_timezone() {
    return db_get_variable('time_zone');
}

function db_get_variable($variable, $type='session') {
    $sql =sprintf('SELECT @@%s.%s', $type, $variable);
    return db_result(db_query($sql));
}

function db_set_variable($variable, $value, $type='session') {
    $sql =sprintf('SET %s %s=%s',strtoupper($type), $variable, db_input($value));
    return db_query($sql);
}


function db_select_database($database) {
    global $__db;
    return ($database && @$__db->select_db($database));
}

function db_create_database($database, $charset='utf8',
        $collate='utf8_general_ci') {
    global $__db;
    return @$__db->query(sprintf('CREATE DATABASE %s DEFAULT CHARACTER SET %s COLLATE %s', $database, $charset, $collate));
}

// execute sql query
function db_query($query, $logError=true) {
    global $ost, $__db;

    $res = $__db->query($query);

    if(!$res && $logError && $ost) { //error reporting
        $msg='['.$query.']'."\n\n".db_error();
        $ost->logDBError('DB Error #'.db_errno(), $msg);
        //echo $msg; #uncomment during debuging or dev.
    }

    return $res;
}

function db_squery($query) { //smart db query...utilizing args and sprintf

    $args  = func_get_args();
    $query = array_shift($args);
    $query = str_replace("?", "%s", $query);
    $args  = array_map('db_real_escape', $args);
    array_unshift($args, $query);
    $query = call_user_func_array('sprintf', $args);
    return db_query($query);
}

function db_count($query) {
    return db_result(db_query($query));
}

function db_result($res, $row=0) {
    if (!$res)
        return NULL;

    $res->data_seek($row);
    list($value) = db_output($res->fetch_row());
    return $value;
}

function db_fetch_array($res, $mode=MYSQL_ASSOC) {
    return ($res) ? db_output($res->fetch_array($mode)) : NULL;
}

function db_fetch_row($res) {
    return ($res) ? db_output($res->fetch_row()) : NULL;
}

function db_fetch_field($res) {
    return ($res) ? $res->fetch_field() : NULL;
}

function db_assoc_array($res, $mode=false) {
    if($res && db_num_rows($res)) {
        while ($row=db_fetch_array($res, $mode))
            $result[]=$row;
    }
    return $result;
}

function db_num_rows($res) {
    return ($res) ? $res->num_rows : 0;
}

function db_affected_rows() {
    global $__db;
    return $__db->affected_rows;
}

function db_data_seek($res, $row_number) {
    return ($res && $res->data_seek($row_number));
}

function db_data_reset($res) {
    return db_data_seek($res, 0);
}

function db_insert_id() {
    global $__db;
    return $__db->insert_id;
}

function db_free_result($res) {
    return ($res && $res->free());
}

function db_output($var) {

    if(!function_exists('get_magic_quotes_runtime') || !get_magic_quotes_runtime()) //Sucker is NOT on - thanks.
        return $var;

    if (is_array($var))
        return array_map('db_output', $var);

    return (!is_numeric($var))?stripslashes($var):$var;

}

//Do not call this function directly...use db_input
function db_real_escape($val, $quote=false) {
    global $__db;

    //Magic quotes crap is taken care of in main.inc.php
    $val=$__db->real_escape_string($val);

    return ($quote)?"'$val'":$val;
}

function db_input($var, $quote=true) {

    if(is_array($var))
        return array_map('db_input', $var, array_fill(0, count($var), $quote));
    elseif($var && preg_match("/^\d+(\.\d+)?$/", $var))
        return $var;

    return db_real_escape($var, $quote);
}

function db_connect_error() {
    global $__db;
    return $__db->connect_error;
}

function db_error() {
    global $__db;
    return $__db->error;
}

function db_errno() {
    global $__db;
    return $__db->errno;
}
?>

