<?php

class mysqli {

    function query() {}
    function real_escape_string() {}
    function fetch_row() {}
    function prepare() {}
    function ssl_set() {}
    function real_connect() {}
    function select_db() {}
    function set_charset() {}
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
    function getConstants() {}
}

class DomNode {
    function hasChildNodes() {}
    function removeChild() {}
}

class DomNodeList {
    function item() {}
}

class DomElement {
    function getAttribute() {}
}

class DomDocument {
    function loadHTML() {}
    function loadXML() {}
}

class Exception {
    function getTraceAsString() {}
}

class DateTime {
    function add() {}
    static function createFromFormat () {}
    static function getLastErrors() {}
    function modify() {}
    function setDate() {}
    function setISODate() {}
    function setTime() {}
    function setTimestamp() {}
    function setTimezone() {}
    function sub() {}
    function diff() {}
    function format() {}
    function getOffset() {}
    function getTimestamp() {}
    function getTimezone() {}
}

class DateInterval {
    static function createFromDateString() {}
    function format() {}
}

class DateTimeZone {
    function getLocation() {}
    function getName() {}
    function getOffset() {}
    function getTransitions() {}
    static function listAbbreviations() {}
    static function listIdentifiers() {}
}

class Phar {
    static function isValidPharFilename() {}
}

class ZipArchive {
    function statIndex() {}
    function getFromIndex() {}
}

class finfo {
    function file() {}
    function buffer() {}
}
?>
