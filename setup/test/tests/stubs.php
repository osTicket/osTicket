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
    function autocommit() {}
    function rollback() {}
    function ping() {}
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
    function fetch_assoc() {}
    function result_metadata() {}
    function free() {}
}

class mysqli_result {
    function free() {}
    function free_result() {}
    function fetch_fields() {}
}

class ReflectionClass {
    function getMethods() {}
    function getConstants() {}
    function newInstanceArgs() {}
    function newInstanceWithoutConstructor() {}
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
    function getElementsByTagName() {}
    function loadHTML() {}
    function loadXML() {}
    function saveHTML() {}
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

class DateTimeImmutable {
    static function createFromMutable() {}
}

class Phar {
    static function isValidPharFilename() {}
    function setStub() {}
    function startBuffering() {}
    function stopBuffering() {}
    function setSignatureAlgorithm() {}
    function compress() {}
}

class ZipArchive {
    function statIndex() {}
    function addFromString() {}
    function getFromIndex() {}
    function setCommentName() {}
    function setExternalAttributesName() {}
}

class Spyc {
    static function YAMLLoad() {}
}

class finfo {
    function file() {}
    function buffer() {}
}

class Locale {
    static function getDisplayName() {}
    static function acceptFromHttp() {}
}
class IntlBreakIterator {
    static function createWordInstance() {}
    function setText() {}
}

class SqlFunction {
    static function NOW() {}
    static function LENGTH() {}
    static function COALESCE() {}
    static function DATEDIFF() {}
    static function timestampdiff() {}
}

class SqlExpression {
    static function plus() {}
    static function minus() {}
    static function times() {}
    static function bitor() {}
    static function bitand() {}
}

class SqlInterval {
    static function SECOND() {}
    static function MINUTE() {}
    static function DAY() {}
}

class SqlAggregate {
    static function COUNT() {}
    static function SUM() {}
    static function MAX() {}
}

class Q {
    static function ANY() {}
}

class IntlDateFormatter {
    function setPattern() {}
    function getPattern() {}
    function parse() {}
}

class ResourceBundle {
    static function getLocales() {}
}

class NumberFormatter {
    function getSymbol() {}
}

class Collator {
    function setStrength() {}
    function compare() {}
}

class Aws {
    function createRoute53() {}
}

class Aws_Route53_Client {
    function changeResourceRecordSets() {}
}

class Memcache {
    function addServer() {}
    function pconnect() {}
    function replace() {}
    function set() {}
    function get() {}
}

class Crypt_Hash {
    function setKey() {}
    function setIV() {}
}

class Crypt_AES {
    function setKey() {}
    function setIV() {}
    function enableContinuousBuffer() {}
}

class PEAR {
    static function isError() {}
    function mail() {}
}

class mail {
    static function factory() {}
    function connect() {}
    function disconnect() {}
}

class Mail_mime {
    function headers() {}
    function setTXTBody() {}
    function setHTMLBody() {}
    function addCc() {}
    function addTo() {}
    function addBcc() {}
}

class mPDF {
    function Output() {}
    function SetAutoFont() {}
}

class HashPassword {
    function CheckPassword() {}
    function HashPassword() {}
}

class SplFileObject {
    function fseek() {}
    function getRealPath() {}
}

class AuditEntry {
    function getDataById() {}
    static function getTableInfo() {}
}

class Smtp {
    function _expect() {}
    function _send() {}
}

class Imap {
    function setFlags() {}
}

class Message {
    function setSubject() {}
    function isMultiPart() {}
}

class MailBoxProtocolTrait {
    function sendRequest() {}
}

class MailBoxStorageTrait {
    function selectFolder() {}
    function countMessages() {}
    function getRawHeader() {}
    function getRawContent() {}
    function moveMessage() {}
    function removeMessage() {}
}
?>
