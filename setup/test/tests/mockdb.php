<?php

define('TABLE_PREFIX', '%');

Bootstrap::defineTables(TABLE_PREFIX);

function db_connect($source) {
    global $__db;
    $__db = $source;
}

function db_input($what) {
    return sprintf("'%8.8s'", md5($what));
}

function db_query($sql) {
    global $__db;

    return $__db->query($sql);
}

function db_prepare($sql) {
    global $__db;
    return $__db->prepare($sql);
}

function db_fetch_row($res) {
    return $res->fetch_row();
}

function db_fetch_array($res) {
    return $res->fetch_array();
}

function db_affected_row() {
    global $__db;
    return $__db->affected_rows;
}

function db_insert_id() {
    global $__db;
    return $__db->insert_id;
}

function db_num_rows($res) {
    return $res->num_rows();
}

function db_real_escape($val, $quote=false) {
    return $quote ? "'$val'" : $val;
}

class MockDbSource {
    var $insert_id = 1;
    var $affected_rows = 1;

    var $data;

    function __construct($data=array()) {
        $this->data = $data;
    }

    function query($sql) {
        $hash = md5($sql);
        if (!isset($this->data[$sql]))
            print ($hash.": No data found:\n".$sql."\n");

        return new MockDbCursor($this->data[$hash] ?: array());
    }

    function prepare($sql) {
        $cursor = $this->query($sql);
        $cursor->param_count = preg_match_all('/ \? /', $sql);
        return $cursor;
    }

    function addRecordset($hash, &$data) {
        $this->data[$hash] = $data;
    }
}

class MockDbCursor {
    var $data;

    function __construct($data) {
        $this->data = $data;
    }

    function fetch_fields() {
        return array();
    }

    function fetch_row() {
        list($i, $row) = each($this->data);
        return $row;
    }

    function fetch_array() {
        list($i, $row) = each($this->data);
        return $row;
    }

    function num_rows() {
        return count($this->data);
    }

    function fetch() {
        return $this->fetch_array();
    }

    function execute() {
        return $this;
    }
    function bind_result() {
        return true;
    }
    function bind_param() {
        return true;
    }
    function store_result() {
        return true;
    }
    function result_metadata() {
        return new DbMetaData();
    }

    function close() {
        return true;
    }
}

class DbMetaData {
    function fetch_fields() {
        return array();
    }
    function free_result() {
    }
}
