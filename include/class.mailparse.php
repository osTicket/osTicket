<?php
/*********************************************************************
    class.mailparse.php

    Mail parsing helper class.
    Mail parsing will change once we move to PHP5

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require_once(PEAR_DIR.'Mail/mimeDecode.php');
require_once(PEAR_DIR.'Mail/RFC822.php');

class Mail_Parse {

    var $mime_message;
    var $include_bodies;
    var $decode_headers;
    var $decode_bodies;

    var $struct;

    var $charset ='UTF-8'; //Default charset.

    function Mail_parse($mimeMessage, $charset=null){

        $this->mime_message = $mimeMessage;

        if($charset)
            $this->charset = $charset;

        $this->include_bodies = true;
        $this->decode_headers = true;
        $this->decode_bodies = true;

        //Desired charset
        if($charset)
            $this->charset = $charset;
    }

    function decode() {

        $params = array('crlf'          => "\r\n",
                        'charset'       => $this->charset,
                        'input'         => $this->mime_message,
                        'include_bodies'=> $this->include_bodies,
                        'decode_headers'=> $this->decode_headers,
                        'decode_bodies' => $this->decode_bodies);
        $this->splitBodyHeader();
        $this->struct=Mail_mimeDecode::decode($params);

        return (PEAR::isError($this->struct) || !(count($this->struct->headers)>1))?FALSE:TRUE;
    }

    function splitBodyHeader() {

        if (preg_match("/^(.*?)\r?\n\r?\n(.*)/s",
                $this->mime_message,
                $match)) {                                  # nolint
            $this->header=$match[1];                        # nolint
        }
    }
    /**
     * Takes the header section of an email message with the form of
     * Header: Value
     * and returns a hashtable of header-name => value pairs. Also, this
     * function properly handles header values that span multiple lines
     * (such as Content-Type).
     *
     * Specify $as_array to TRUE to keep all header values. If a header is
     * specified more than once, all the values are placed in an array under
     * the header key. If left as FALSE, only the value given in the last
     * occurance of the header is retained.
     */
    /* static */ function splitHeaders($headers_text, $as_array=false) {
        $headers = preg_split("/\r?\n/", $headers_text);
        for ($i=0, $k=count($headers); $i<$k; $i++) {
            # XXX: Might tabs be used here?
            if (substr($headers[$i], 0, 1) == " ") {
                # Continuation from previous header (runon to next line)
                $j=$i-1; while (!isset($headers[$j]) && $j>0) $j--;
                $headers[$j] .= "\n".ltrim($headers[$i]);
                unset($headers[$i]);
            } elseif (strlen($headers[$i]) == 0) {
                unset($headers[$i]);
            }
        }
        $array = array();
        foreach ($headers as $hdr) {
            list($name, $val) = explode(": ", $hdr, 2);
            # Create list of values if header is specified more than once
            if ($array[$name] && $as_array) {
                if (is_array($array[$name])) $array[$name][] = $val;
                else $array[$name] = array($array[$name], $val);
            } else {
                $array[$name] = $val;
            }
        }
        return $array;
    }

    /* static */
    function findHeaderEntry($headers, $name) {
        if (!is_array($headers))
            $headers = self::splitHeaders($headers);
        foreach ($headers as $key=>$val)
            if (strcasecmp($key, $name) === 0)
                return $val;
        return false;
    }

    function getStruct(){
        return $this->struct;
    }

    function getHeader() {
        if(!$this->header) $this->splitBodyHeader();

        return $this->header;
    }

    function getError(){
        return PEAR::isError($this->struct)?$this->struct->getMessage():'';
    }


    function getFromAddressList(){
        return Mail_Parse::parseAddressList($this->struct->headers['from']);
    }

    function getToAddressList(){
        //Delivered-to incase it was a BBC mail.
       return Mail_Parse::parseAddressList($this->struct->headers['to']?$this->struct->headers['to']:$this->struct->headers['delivered-to']);
    }

    function getCcAddressList(){
        return $this->struct->headers['cc']?Mail_Parse::parseAddressList($this->struct->headers['cc']):null;
    }

    function getMessageId(){
        return $this->struct->headers['message-id'];
    }

    function getSubject(){
        return $this->struct->headers['subject'];
    }

    function getReplyTo() {
        return Mail_Parse::parseAddressList($this->struct->headers['reply-to']);
    }

    function getBody(){

        $body='';
        if($body=$this->getPart($this->struct,'text/plain'))
            $body = Format::htmlchars($body);
        elseif($body=$this->getPart($this->struct,'text/html')) {
            //Cleanup the html.
            $body=str_replace("</DIV><DIV>", "\n", $body);
            $body=str_replace(array("<br>", "<br />", "<BR>", "<BR />"), "\n", $body);
            $body=Format::safe_html($body); //Balance html tags & neutralize unsafe tags.
        }
        return $body;
    }

    function getPart($struct, $ctypepart) {

        if($struct && !$struct->parts) {
            $ctype = @strtolower($struct->ctype_primary.'/'.$struct->ctype_secondary);
            if($ctype && strcasecmp($ctype,$ctypepart)==0) {
                $content = $struct->body;
                //Encode to desired encoding - ONLY if charset is known??
                if(isset($struct->ctype_parameters['charset']) && strcasecmp($struct->ctype_parameters['charset'], $this->charset))
                    $content = Format::encode($content, $struct->ctype_parameters['charset'], $this->charset);

                return $content;
            }
        }

        $data='';
        if($struct && $struct->parts) {
            foreach($struct->parts as $i=>$part) {
                if($part && !$part->disposition && ($text=$this->getPart($part,$ctypepart)))
                    $data.=$text;
            }
        }
        return $data;
    }


    function mime_encode($text, $charset=null, $encoding='utf-8') {
        return Format::encode($text, $charset, $encoding);
    }

    function getAttachments($part=null){

        if($part==null)
            $part=$this->getStruct();

        if($part && $part->disposition
                && (!strcasecmp($part->disposition,'attachment')
                    || !strcasecmp($part->disposition,'inline')
                    || !strcasecmp($part->ctype_primary,'image'))){

            if(!($filename=$part->d_parameters['filename']) && $part->d_parameters['filename*'])
                $filename=$part->d_parameters['filename*']; //Do we need to decode?

            $file=array(
                    'name'  => $filename,
                    'type'  => strtolower($part->ctype_primary.'/'.$part->ctype_secondary),
                    );

            if ($part->ctype_parameters['charset'])
                $file['data'] = $this->mime_encode($part->body,
                    $part->ctype_parameters['charset']);
            else
                $file['data'] = $part->body;

            if(!$this->decode_bodies && $part->headers['content-transfer-encoding'])
                $file['encoding'] = $part->headers['content-transfer-encoding'];

            return array($file);
        }

        $files=array();
        if($part->parts){
            foreach($part->parts as $k=>$p){
                if($p && ($result=$this->getAttachments($p))) {
                    $files=array_merge($files,$result);
                }
            }
        }

        return $files;
    }

    function getPriority(){
        return Mail_Parse::parsePriority($this->getHeader());
    }

    function parsePriority($header=null){

        $priority=0;
        if($header && ($begin=strpos($header,'X-Priority:'))!==false){
            $begin+=strlen('X-Priority:');
            $xpriority=preg_replace("/[^0-9]/", "",substr($header, $begin, strpos($header,"\n",$begin) - $begin));
            if(!is_numeric($xpriority))
                $priority=0;
            elseif($xpriority>4)
                $priority=1;
            elseif($xpriority>=3)
                $priority=2;
            elseif($xpriority>0)
                $priority=3;
        }
        return $priority;
    }

    function parseAddressList($address){
        if (!$address)
            return false;
        return Mail_RFC822::parseAddressList($address, null, null,false);
    }

    function parse($rawemail) {
        $parser= new Mail_Parse($rawemail);
        return ($parser && $parser->decode())?$parser:null;
    }
}

class EmailDataParser {
    var $stream;
    var $error;

    function EmailDataParser($stream=null) {
        $this->stream = $stream;
    }

    function parse($stream) {
        global $cfg;

        $contents ='';
        if(is_resource($stream)) {
            while(!feof($stream))
                $contents .= fread($stream, 8192);

        } else {
            $contents = $stream;
        }

        $parser= new Mail_Parse($contents);
        if(!$parser->decode()) //Decode...returns false on decoding errors
            return $this->err('Email parse failed ['.$parser->getError().']');

        $data =array();
        //FROM address: who sent the email.
        if(($fromlist = $parser->getFromAddressList()) && !PEAR::isError($fromlist)) {
            $from=$fromlist[0]; //Default.
            foreach($fromlist as $fromobj) {
                if(!Validator::is_email($fromobj->mailbox.'@'.$fromobj->host)) continue;
                $from = $fromobj;
                break;
            }

            $data['email'] = $from->mailbox.'@'.$from->host;

            $data['name'] = trim($from->personal,'"');
            if($from->comment && $from->comment[0])
                $data['name'].= ' ('.$from->comment[0].')';

            //Use email address as name  when FROM address doesn't  have a name.
            if(!$data['name'] && $data['email'])
                $data['name'] = $data['email'];
        }

        //TO Address:Try to figure out the email address... associated with the incoming email.
        $emailId = 0;
        if(($tolist = $parser->getToAddressList())) {
            foreach ($tolist as $toaddr) {
                if(($emailId=Email::getIdByEmail($toaddr->mailbox.'@'.$toaddr->host)))
                    break;
            }
        }
        //maybe we got CC'ed??
        if(!$emailId && ($cclist=$parser->getCcAddressList())) {
            foreach ($cclist as $ccaddr) {
                if(($emailId=Email::getIdByEmail($ccaddr->mailbox.'@'.$ccaddr->host)))
                    break;
            }
        }

        $data['subject'] = $parser->getSubject();
        $data['message'] = Format::stripEmptyLines($parser->getBody());
        $data['header'] = $parser->getHeader();
        $data['mid'] = $parser->getMessageId();
        $data['priorityId'] = $parser->getPriority();
        $data['emailId'] = $emailId;

        $data['in-reply-to'] = $parser->struct->headers['in-reply-to'];
        $data['references'] = $parser->struct->headers['references'];

        if ($replyto = $parser->getReplyTo()) {
            $replyto = $replyto[0];
            $data['reply-to'] = $replyto->mailbox.'@'.$replyto->host;
            if ($replyto->personal)
                $data['reply-to-name'] = trim($replyto->personal, " \t\n\r\0\x0B\x22");
        }

        if($cfg && $cfg->allowEmailAttachments())
            $data['attachments'] = $parser->getAttachments();

        return $data;
    }

    function err($error) {
        $this->error = $error;

        return false;
    }

    function getError() {
        return $this->lastError();
    }

    function lastError() {
        return $this->error;
    }
}
?>
