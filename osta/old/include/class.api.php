<?php
/*********************************************************************
    class.api.php

    API

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
include_once INCLUDE_DIR.'class.controller.php';

class API {

    var $id;

    var $ht;

    function __construct($id) {
        $this->id = 0;
        $this->load($id);
    }

    function load($id=0) {

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT * FROM '.API_KEY_TABLE.' WHERE id='.db_input($id);
        if(!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        $this->ht = db_fetch_array($res);
        $this->id = $this->ht['id'];

        return true;
    }

    function reload() {
        return $this->load();
    }

    function getId() {
        return $this->id;
    }

    function getKey() {
        return $this->ht['apikey'];
    }

    function getIPAddr() {
        return $this->ht['ipaddr'];
    }

    function getNotes() {
        return $this->ht['notes'];
    }

    function getHashtable() {
        return $this->ht;
    }

    function isActive() {
        return ($this->ht['isactive']);
    }

    function canCreateTickets() {
        return ($this->ht['can_create_tickets']);
    }

    function canExecuteCron() {
        return ($this->ht['can_exec_cron']);
    }

    function update($vars, &$errors) {

        if(!API::save($this->getId(), $vars, $errors))
            return false;

        $this->reload();

        return true;
    }

    function delete() {
        $sql='DELETE FROM '.API_KEY_TABLE.' WHERE id='.db_input($this->getId()).' LIMIT 1';
        return (db_query($sql) && ($num=db_affected_rows()));
    }

    /** Static functions **/
    static function add($vars, &$errors) {
        return API::save(0, $vars, $errors);
    }

    static function validate($key, $ip) {
        return ($key && $ip && self::getIdByKey($key, $ip));
    }

    static function getIdByKey($key, $ip='') {

        $sql='SELECT id FROM '.API_KEY_TABLE.' WHERE apikey='.db_input($key);
        if($ip)
            $sql.=' AND ipaddr='.db_input($ip);

        if(($res=db_query($sql)) && db_num_rows($res))
            list($id) = db_fetch_row($res);

        return $id;
    }

    static function lookupByKey($key, $ip='') {
        return self::lookup(self::getIdByKey($key, $ip));
    }

    static function lookup($id) {
        return ($id && is_numeric($id) && ($k= new API($id)) && $k->getId()==$id)?$k:null;
    }

    static function save($id, $vars, &$errors) {

        if(!$id && (!$vars['ipaddr'] || !Validator::is_ip($vars['ipaddr'])))
            $errors['ipaddr'] = __('Valid IP is required');

        if($errors) return false;

        $sql=' updated=NOW() '
            .',isactive='.db_input($vars['isactive'])
            .',can_create_tickets='.db_input($vars['can_create_tickets'])
            .',can_exec_cron='.db_input($vars['can_exec_cron'])
            .',notes='.db_input(Format::sanitize($vars['notes']));

        if($id) {
            $sql='UPDATE '.API_KEY_TABLE.' SET '.$sql.' WHERE id='.db_input($id);
            if(db_query($sql))
                return true;

            $errors['err']=sprintf(__('Unable to update %s.'), __('this API key'))
               .' '.__('Internal error occurred');

        } else {
            $sql='INSERT INTO '.API_KEY_TABLE.' SET '.$sql
                .',created=NOW() '
                .',ipaddr='.db_input($vars['ipaddr'])
                .',apikey='.db_input(strtoupper(md5(time().$vars['ipaddr'].md5(Misc::randCode(16)))));

            if(db_query($sql) && ($id=db_insert_id()))
                return $id;

            $errors['err']=sprintf('%s %s',
                sprintf(__('Unable to add %s.'), __('this API key')),
                __('Correct any errors below and try again.'));
        }

        return false;
    }
}

/**
 * Controller for API methods. Provides methods to check to make sure the
 * API key was sent and that the Client-IP and API-Key have been registered
 * in the database, and methods for parsing and validating data sent in the
 * API request.
 */

class ApiController extends Controller {

    private $sapi;
    private $key;

    public function __construct($sapi = null) {
        // Set API Interface the request is coming from
        $this->sapi = $sapi ?: (osTicket::is_cli() ? 'cli' : 'http');
    }

    public function isCli() {
        return (strcasecmp($this->sapi, 'cli') === 0);
    }

    private function getInputStream() {
        return $this->isCli() ? 'php://stdin' : 'php://input';
    }

    protected function getRemoteAddr() {
       return $_SERVER['REMOTE_ADDR'];
    }

    protected function getApiKey() {
        return $_SERVER['HTTP_X_API_KEY'];
    }

    function requireApiKey() {
        // Require a valid API key sent as X-API-Key HTTP header
        // see getApiKey method.
        if (!($key=$this->getKey()))
            return $this->exerr(401, __('Valid API key required'));
        elseif (!$key->isActive() || $key->getIPAddr() != $this->getRemoteAddr())
            return $this->exerr(401, __('API key not found/active or source IP not authorized'));

        return $key;
    }

    function getKey() {
        // Lookup record using sent API Key && IP Addr
        if (!$this->key
                && ($key=$this->getApiKey())
                && ($ip=$this->getRemoteAddr()))
            $this->key = API::lookupByKey($key, $ip);

        return $this->key;
    }

    /**
     * Retrieves the body of the API request and converts it to a common
     * hashtable. For JSON formats, this is mostly a noop, the conversion
     * work will be done for XML requests
     */
    function getRequest($format, $validate=true) {
        $input = $this->getInputStream();
        if (!($stream = @fopen($input, 'r')))
            $this->exerr(400, sprintf('%s (%s)',
                        __("Unable to read request body"), $input));

        return $this->parseRequest($stream, $format, $validate);
    }

    function getEmailRequest() {
        if (!($data=$this->getRequest('email', false)))
            $this->exerr(400, __("Unable to read email request"));

        return $data;
    }

    function parseRequest($stream, $format, $validate=true) {
        $parser = null;
        switch(strtolower($format)) {
            case 'xml':
                if (!function_exists('xml_parser_create'))
                    return $this->exerr(501, __('XML extension not supported'));
                $parser = new ApiXmlDataParser();
                break;
            case 'json':
                $parser = new ApiJsonDataParser();
                break;
            case 'email':
                $parser = new ApiEmailDataParser();
                break;
            default:
                return $this->exerr(415, __('Unsupported data format'));
        }

        if (!($data = $parser->parse($stream)) || !is_array($data)) {
            $this->exerr(400, $parser->lastError());
        }

        //Validate structure of the request.
        if ($validate && $data)
            $this->validate($data, $format, false);

        return $data;
    }

    function parseEmail($content) {
        return $this->parseRequest($content, 'email', false);
    }

    /**
     * Structure to validate the request against -- must be overridden to be
     * useful
     */
    function getRequestStructure($format, $data=null) { return array(); }
    /**
     * Simple validation that makes sure the keys of a parsed request are
     * expected. It is assumed that the functions actually implementing the
     * API will further validate the contents of the request
     */
    function validateRequestStructure($data, $structure, $prefix="", $strict=true) {
        global $ost;

        foreach ($data as $key=>$info) {
            if (is_array($structure) && (is_array($info) || $info instanceof ArrayAccess)) {
                $search = (isset($structure[$key]) && !is_numeric($key)) ? $key : "*";
                if (isset($structure[$search])) {
                    $this->validateRequestStructure($info, $structure[$search], "$prefix$key/", $strict);
                    continue;
                }
            } elseif (in_array($key, $structure)) {
                continue;
            }

            $error = sprintf(__("%s: Unexpected data received in API request"),
                    "$prefix$key");
            $this->onError(400, $error, __('Unexpected Data'), !$strict);
        }
        return true;
    }

    /**
     * Validate request.
     *
     */
    function validate(&$data, $format, $strict=true) {
        return $this->validateRequestStructure(
                $data,
                $this->getRequestStructure($format, $data),
                "",
                $strict);
    }

    protected function debug($subj, $msg) {
        return $this->log($subj, $msg, LOG_DEBUG);
    }

    protected function logError($subj, $msg, $fatal=false) {
        // If error is not fatal then log it as a warning
        return $this->log($subj, $msg, $fatal ? LOG_ERR : LOG_WARN);
    }

    protected function log($title, $msg, $level = LOG_WARN) {
        global $ost;
        switch ($level) {
            case LOG_WARN:
            case LOG_ERR:
                // We are disabling email alerts on API errors / warnings
                // due to potential abuse or loops that might cause email DOS
                $ost->log($level, $title, $msg, false);
                break;
            case  LOG_DEBUG:
            default:
                $ost->logDebug($title, $msg);
        }
        return true;
    }

    /**
     * API error & logging and response!
     *
     * final - don not override downstream.
     */
    final public function onError($code, $error, $title = null,
            $logOnly = false) {

        // Unpack the errors to string if error is an array
        if ($error && is_array($error))
            $error = Format::array_implode(": ", "\n", $error);

        // Log the error
        $msg = $error;
        // TODO: Only include API Key when in debug mode to avoid
        // potentialialy leaking a valid key in system logs
        if (($key=$this->getApiKey()))
            $msg .= "\n[$key]\n";

        $title = sprintf('%s (%s)', $title ?: __('API Error'), $code);
        $this->logError($title, $msg, $logOnly);

        // If the error is not deemed fatal then simply return
        if ($logOnly)
            return;

        // If the API Interface is CLI then throw a TicketApiError exception
        // so the caller can handle the error gracefully.
        // Note that we set the error code as well
        if ($this->isCli())
            throw new TicketApiError($error, $code);

        // Respond and exit since HTTP endpoint requests
        // are considered stateless
        $this->response($code, $error);
    }
}

include_once "class.xml.php";
class ApiXmlDataParser extends XmlDataParser {

    function parse($stream) {
        return $this->fixup(parent::parse($stream));
    }
    /**
     * Perform simple operations to make data consistent between JSON and
     * XML data types
     */
    function fixup($current) {
        global $cfg;

		if($current['ticket'])
			$current = $current['ticket'];

        if (!is_array($current))
            return $current;
        foreach ($current as $key=>&$value) {
            if ($key == "phone" && is_array($value)) {
                $value = $value[":text"];
            } else if ($key == "alert") {
                $value = (bool) (strtolower($value) === 'false' ? false : $value);
            } else if ($key == "autorespond") {
                $value = (bool) (strtolower($value) === 'false' ? false : $value);
            } else if ($key == "message") {
                if (!is_array($value)) {
                    $value = array(
                        "body" => $value,
                        "type" => "text/plain",
                        # Use encoding from root <xml> node
                    );
                } else {
                    $value["body"] = $value[":text"];
                    unset($value[":text"]);
                }
                if (isset($value['encoding']))
                    $value['body'] = Charset::utf8($value['body'], $value['encoding']);

                if (!strcasecmp($value['type'], 'text/html'))
                    $value = new HtmlThreadEntryBody($value['body']);
                else
                    $value = new TextThreadEntryBody($value['body']);

            } else if ($key == "attachments") {
                if(isset($value['file']) && !isset($value['file'][':text']))
                    $value = $value['file'];

                if($value && is_array($value)) {
                    foreach ($value as &$info) {
                        $info["data"] = $info[":text"];
                        unset($info[":text"]);
                    }
                    unset($info);
                }
            } else if(is_array($value)) {
                $value = $this->fixup($value);
            }
        }
        unset($value);

        return $current;
    }
}

include_once "class.json.php";
class ApiJsonDataParser extends JsonDataParser {
    static function parse($stream, $tidy=false) {
        return self::fixup(parent::parse($stream));
    }
    static function fixup($current) {
        if (!is_array($current))
            return $current;
        foreach ($current as $key=>&$value) {
            if ($key == "phone") {
                $value = strtoupper($value);
            } else if ($key == "alert") {
                $value = (bool)$value;
            } else if ($key == "autorespond") {
                $value = (bool)$value;
            } elseif ($key == "message") {
                // Allow message specified in RFC 2397 format
                $data = Format::strip_emoticons(Format::parseRfc2397($value, 'utf-8'));

                if (isset($data['type']) && $data['type'] == 'text/html')
                    $value = new HtmlThreadEntryBody($data['data']);
                else
                    $value = new TextThreadEntryBody($data['data']);

            } else if ($key == "attachments") {
                foreach ($value as &$info) {
                    $data = reset($info);
                    # PHP5: fopen("data://$data[5:]");
                    $contents = Format::parseRfc2397($data, 'utf-8', false);
                    $info = array(
                        "data" => $contents['data'],
                        "type" => $contents['type'],
                        "name" => key($info),
                    );
                }
                unset($info);
            }
        }
        unset($value);

        return $current;
    }
}

/* Email parsing */
include_once "class.mailparse.php";
class ApiEmailDataParser extends EmailDataParser {

    function parse($stream) {
        return $this->fixup(parent::parse($stream));
    }

    function fixup($data) {
        global $cfg;

        if (!$data) return $data;

        $data['source'] = 'Email';

        if (!$data['subject'])
            $data['subject'] = '[No Subject]';

        if (!$data['emailId'])
            $data['emailId'] = $cfg->getDefaultEmailId();

        if( !$cfg->useEmailPriority())
            unset($data['priorityId']);

        return $data;
    }
}
?>
