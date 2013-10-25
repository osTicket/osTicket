<?php

class mysqli {

    function query() {}
    function real_escape_string() {}
    function fetch_row() {}
    function prepare() {}
    function ssl_set() {}
    function real_connect() {}
    function select_db() {}
}

class mysqli_stmt {
    var $num_rows;

    function store_result() {}
    function data_seek() {}
    function fetch() {}
    function fetch_array() {}
    function fetch_field() {}
    function fetch_field_direct() {}
    function fetch_row() {}
    function result_metadata() {}
    function free() {}
}

class ReflectionClass {
    function getMethods() {}
}

class DomNode {
    function hasChildNodes() {}
}

class DomNodeList {
    function item() {}
}

class DomElement {
    function getAttribute() {}
}

class DomDocument {
    function loadHTML() {}
}

class Exception {
    function getTraceAsString() {}
}

?>
