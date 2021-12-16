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

class ApiController {

    var $apikey;

    function requireApiKey() {
        # Validate the API key -- required to be sent via the X-API-Key
        # header

        if(!($key=$this->getApiKey()))
            return $this->exerr(401, __('Valid API key required'));
        elseif (!$key->isActive() || $key->getIPAddr()!=$_SERVER['REMOTE_ADDR'])
            return $this->exerr(401, __('API key not found/active or source IP not authorized'));

        return $key;
    }

    function getApiKey() {

        if (!$this->apikey && isset($_SERVER['HTTP_X_API_KEY']) && isset($_SERVER['REMOTE_ADDR']))
            $this->apikey = API::lookupByKey($_SERVER['HTTP_X_API_KEY'], $_SERVER['REMOTE_ADDR']);

        return $this->apikey;
    }

    /**
     * Retrieves the body of the API request and converts it to a common
     * hashtable. For JSON formats, this is mostly a noop, the conversion
     * work will be done for XML requests
     */
    function getRequest($format) {
        global $ost;

        $input = osTicket::is_cli()?'php://stdin':'php://input';

        if (!($stream = @fopen($input, 'r')))
            $this->exerr(400, __("Unable to read request body"));

        $parser = null;
        switch(strtolower($format)) {
            case 'xml':
                if (!function_exists('xml_parser_create'))
                    $this->exerr(501, __('XML extension not supported'));

                $parser = new ApiXmlDataParser();
                break;
            case 'json':
                $parser = new ApiJsonDataParser();
                break;
            case 'email':
                $parser = new ApiEmailDataParser();
                break;
            default:
                $this->exerr(415, __('Unsupported data format'));
        }

        if (!($data = $parser->parse($stream)))
            $this->exerr(400, $parser->lastError());

        //Validate structure of the request.
        $this->validate($data, $format, false);

        return $data;
    }

    function getEmailRequest() {
        return $this->getRequest('email');
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
            if ($strict)
                return $this->exerr(400, sprintf(__("%s: Unexpected data received in API request"), "$prefix$key"));
            else
                $ost->logWarning(__('API Unexpected Data'),
                    sprintf(__("%s: Unexpected data received in API request"), "$prefix$key"),
                    false);
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

    /**
     * API error & logging and response!
     *
     */

    /* If possible - DO NOT - overwrite the method downstream */
    function exerr($code, $error='') {
        global $ost;

        if($error && is_array($error))
            $error = Format::array_implode(": ", "\n", $error);

        //Log the error as a warning - include api key if available.
        $msg = $error;
        if($_SERVER['HTTP_X_API_KEY'])
            $msg.="\n*[".$_SERVER['HTTP_X_API_KEY']."]*\n";
        $ost->logWarning(__('API Error')." ($code)", $msg, false);

        if (PHP_SAPI == 'cli') {
            fwrite(STDERR, "({$code}) $error\n");
        }
        else {
            $this->response($code, $error); //Responder should exit...
        }
        return false;
    }

    //Default response method - can be overwritten in subclasses.
    function response($code, $resp) {
        Http::response($code, $resp);
        exit();
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

        if(!$data) return $data;

        $data['source'] = 'Email';

        if(!$data['subject'])
            $data['subject'] = '[No Subject]';

        if(!$data['emailId'])
            $data['emailId'] = $cfg->getDefaultEmailId();

        if(!$cfg->useEmailPriority())
            unset($data['priorityId']);

        return $data;
    }
}
?>
