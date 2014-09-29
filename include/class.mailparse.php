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
require_once(INCLUDE_DIR.'tnef_decoder.php');

class Mail_Parse {

    var $mime_message;
    var $include_bodies;
    var $decode_headers;
    var $decode_bodies;

    var $struct;

    var $charset ='UTF-8'; //Default charset.

    var $tnef = false;      // TNEF encoded mail

    function Mail_parse(&$mimeMessage, $charset=null){

        $this->mime_message = &$mimeMessage;

        if($charset)
            $this->charset = $charset;

        $this->include_bodies = true;
        $this->decode_headers = false;
        $this->decode_bodies = true;

        //Desired charset
        if($charset)
            $this->charset = $charset;

        $this->notes = array();
    }

    function decode() {

        $params = array('crlf'          => "\r\n",
                        'charset'       => $this->charset,
                        'include_bodies'=> $this->include_bodies,
                        'decode_headers'=> $this->decode_headers,
                        'decode_bodies' => $this->decode_bodies);

        $info = array('raw' => &$this->mime_message);
        Signal::send('mail.received', $this, $info);

        $this->splitBodyHeader();

        $decoder = new Mail_mimeDecode($this->mime_message);
        $this->struct = $decoder->decode($params);

        if (PEAR::isError($this->struct))
            return false;

        $info = array(
            'raw_header' => &$this->header,
            'headers' => &$this->struct->headers,
            'body' => &$this->struct->parts,
            'type' => $this->struct->ctype_primary.'/'.$this->struct->ctype_secondary,
            'mail' => $this->struct,
            'decoder' => $decoder,
        );

        // Allow signal handlers to interact with the processing
        Signal::send('mail.decoded', $decoder, $info);

        // Handle wrapped emails when forwarded
        if ($this->struct && $this->struct->parts) {
            $outer = $this->struct;
            $ctype = $outer->ctype_primary.'/'.$outer->ctype_secondary;
            if (strcasecmp($ctype, 'message/rfc822') === 0) {
                // Capture Delivered-To header from the outer mail
                $dt = $this->struct->headers['delivered-to'];
                // Capture Message-Id from outer mail
                $mid = $this->struct->headers['message-id'];

                $this->struct = $outer->parts[0];

                // Add (clobber) delivered to header from the outer mail
                if ($dt)
                    $this->struct->headers['delivered-to'] = $dt;
                // Ensure the nested mail has a Message-Id
                if (!isset($this->struct->headers['message-id']))
                    $this->struct->headers['message-id'] = $mid;

                // Use headers of the wrapped message
                $headers = array();
                foreach ($this->struct->headers as $h=>$v)
                    $headers[mb_convert_case($h, MB_CASE_TITLE)] = $v;
                $this->header = Format::array_implode(
                     ": ", "\n", $headers);
            }
        }

        // Look for application/tnef attachment and process it
        if ($this->struct && $this->struct->parts) {
            foreach ($this->struct->parts as $i=>$part) {
                if (!@$part->parts && $part->ctype_primary == 'application'
                        && $part->ctype_secondary == 'ms-tnef') {
                    try {
                        $tnef = new TnefStreamParser($part->body);
                        $this->tnef = $tnef->getMessage();
                        // No longer considered an attachment
                        unset($this->struct->parts[$i]);
                    }
                    catch (TnefException $ex) {
                        // TNEF will remain an attachment
                        $this->notes[] = 'TNEF parsing exception: '
                            .$ex->getMessage();
                    }
                }
            }
        }

        return (count($this->struct->headers) > 1);
    }

    function splitBodyHeader() {
        $match = array();
        if (preg_match("/^(.*?)\r?\n\r?\n./s",
                $this->mime_message,
                $match)) {
            $this->header=$match[1];
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
            // first char might be whitespace (" " or "\t")
            if (in_array($headers[$i][0], array(" ", "\t"))) {
                # Continuation from previous header (runon to next line)
                $j=$i-1; while (!isset($headers[$j]) && $j>0) $j--;
                $headers[$j] .= " ".ltrim($headers[$i]);
                unset($headers[$i]);
            } elseif (strlen($headers[$i]) == 0) {
                unset($headers[$i]);
            }
        }
        $array = array();
        foreach ($headers as $hdr) {
            list($name, $val) = explode(": ", $hdr, 2);
            # Create list of values if header is specified more than once
            if (isset($array[$name]) && $as_array) {
                if (is_array($array[$name])) $array[$name][] = $val;
                else $array[$name] = array($array[$name], $val);
            } else {
                $array[$name] = $val;
            }
        }
        return $array;
    }

    /* static */
    function findHeaderEntry($headers, $name, $allEntries=false) {
        if (!is_array($headers))
            $headers = self::splitHeaders($headers, $allEntries);
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
        if (!($header = $this->struct->headers['from']))
            return null;

        return Mail_Parse::parseAddressList($header);
    }

    function getDeliveredToAddressList() {
        if (!($header = $this->struct->headers['delivered-to']))
            return null;

        return Mail_Parse::parseAddressList($header);
    }

    function getToAddressList(){
        // Delivered-to incase it was a BBC mail.
        $tolist = array();
        if ($header = $this->struct->headers['to'])
            $tolist = array_merge($tolist,
                Mail_Parse::parseAddressList($header));
        return $tolist ? $tolist : null;
    }

    function getCcAddressList(){
        if (!($header = @$this->struct->headers['cc']))
            return null;

        return Mail_Parse::parseAddressList($header);
    }

    function getBccAddressList(){
        if (!($header = @$this->struct->headers['bcc']))
            return null;

        return Mail_Parse::parseAddressList($header);
    }

    function getMessageId(){
        if (!($mid = $this->struct->headers['message-id']))
            $mid = sprintf('<%s@local>', md5($this->getHeader()));
        return $mid;
    }

    function getSubject(){
        return Format::mimedecode($this->struct->headers['subject'], $this->charset);
    }

    function getReplyTo() {
        if (!($header = @$this->struct->headers['reply-to']))
            return null;

        return Mail_Parse::parseAddressList($header);
    }

    function isBounceNotice() {
        if (!($body = $this->getPart($this->struct, 'message/delivery-status')))
            return false;

        $info = self::splitHeaders($body);
        if (!isset($info['Action']))
            return false;

        return strcasecmp($info['Action'], 'failed') === 0;
    }

    function getDeliveryStatusMessage() {
        $ctype = @strtolower($this->struct->ctype_primary.'/'.$this->struct->ctype_secondary);
        if ($ctype == 'multipart/report'
            && isset($this->struct->ctype_parameters['report-type'])
            && $this->struct->ctype_parameters['report-type'] == 'delivery-status'
        ) {
            return sprintf('<pre>%s</pre>',
                Format::htmlchars(
                    $this->getPart($this->struct, 'text/plain', 1)
                ));
        }
        return false;
    }

    function getOriginalMessageHeaders() {
        foreach ($this->struct->parts as $p) {
            $ctype = $p->ctype_primary.'/'.$p->ctype_secondary;
            if (strtolower($ctype) === 'message/rfc822')
                return $p->parts[0]->headers;
            // Handle rfc1892 style bounces
            if (strtolower($ctype) === 'text/rfc822-headers') {
                $body = $p->body . "\n\nIgnored";
                $T = new Mail_mimeDecode($body);
                if ($struct = $T->decode())
                    return $struct->headers;
            }
        }
        return null;
    }

    function getBody(){
        global $cfg;

        if ($cfg && $cfg->isHtmlThreadEnabled()) {
            if ($html=$this->getPart($this->struct,'text/html'))
                $body = new HtmlThreadBody($html);
            elseif ($text=$this->getPart($this->struct,'text/plain'))
                $body = new TextThreadBody($text);
        }
        elseif ($text=$this->getPart($this->struct,'text/plain'))
            $body = new TextThreadBody($text);
        elseif ($html=$this->getPart($this->struct,'text/html'))
            $body = new TextThreadBody(
                    Format::html2text(Format::safe_html($html),
                        100, false));

        if (!isset($body))
            $body = new TextThreadBody('');

        if ($cfg && $cfg->stripQuotedReply())
            $body->stripQuotedReply($cfg->getReplySeparator());

        return $body;
    }

    function getPart($struct, $ctypepart, $recurse=-1) {

        if($struct && !@$struct->parts) {
            $ctype = @strtolower($struct->ctype_primary.'/'.$struct->ctype_secondary);
            if (@$struct->disposition
                    && (strcasecmp($struct->disposition, 'inline') !== 0))
                return '';
            if ($ctype && strcasecmp($ctype,$ctypepart)==0) {
                $content = $struct->body;
                //Encode to desired encoding - ONLY if charset is known??
                if (isset($struct->ctype_parameters['charset']))
                    $content = Format::encode($content,
                        $struct->ctype_parameters['charset'], $this->charset);

                return $content;
            }
        }

        if ($this->tnef && !strcasecmp($ctypepart, 'text/html')
                && ($content = $this->tnef->getBody('text/html', $this->charset)))
            return $content;

        $data='';
        if($struct && @$struct->parts && $recurse) {
            foreach($struct->parts as $i=>$part) {
                if($part && ($text=$this->getPart($part,$ctypepart,$recurse - 1)))
                    $data.=$text;
            }
        }
        return $data;
    }


    function mime_encode($text, $charset=null, $encoding='utf-8') {
        return Format::encode($text, $charset, $encoding);
    }

    function getAttachments($part=null){
        $files=array();

        /* Consider this part as an attachment if
         *   * It has a Content-Disposition header
         *     * AND it is specified as either 'attachment' or 'inline'
         *   * The Content-Type header specifies
         *     * type is image/* or application/*
         *     * has a name parameter
         */
        if($part && (
                ($part->disposition
                    && (!strcasecmp($part->disposition,'attachment')
                        || !strcasecmp($part->disposition,'inline'))
                )
                || (!strcasecmp($part->ctype_primary,'image')
                    || !strcasecmp($part->ctype_primary,'application')))) {

            if (isset($part->d_parameters['filename']))
                $filename = Format::mimedecode($part->d_parameters['filename'], $this->charset);
            elseif (isset($part->d_parameters['filename*']))
                // Support RFC 6266, section 4.3 and RFC, and RFC 5987
                $filename = Format::decodeRfc5987(
                    $part->d_parameters['filename*']);

            // Support attachments that do not specify a content-disposition
            // but do specify a "name" parameter in the content-type header.
            elseif (isset($part->ctype_parameters['name']))
                $filename = Format::mimedecode($part->ctype_parameters['name'], $this->charset);
            elseif (isset($part->ctype_parameters['name*']))
                $filename = Format::decodeRfc5987(
                    $part->ctype_parameters['name*']);

            // Some mail clients / servers (like Lotus Notes / Domino) will
            // send images without a filename. For such a case, generate a
            // random filename for the image
            elseif (isset($part->headers['content-id'])
                    && $part->headers['content-id']
                    && 0 === strcasecmp($part->ctype_primary, 'image'))
                $filename = 'image-'.Misc::randCode(4).'.'
                    .strtolower($part->ctype_secondary);
            else
                // Not an attachment?
                return false;

            $file=array(
                    'name'  => $filename,
                    'type'  => strtolower($part->ctype_primary.'/'.$part->ctype_secondary),
                    );

            if ($part->ctype_parameters['charset']
                    && 0 === strcasecmp($part->ctype_primary, 'text'))
                $file['data'] = $this->mime_encode($part->body,
                    $part->ctype_parameters['charset']);
            else
                $file['data'] = $part->body;

            // Capture filesize in order to support de-duplication
            if (extension_loaded('mbstring'))
                $file['size'] = mb_strlen($file['data'], '8bit');
            // bootstrap.php include a compat version of mb_strlen
            else
                $file['size'] = strlen($file['data']);

            if(!$this->decode_bodies && $part->headers['content-transfer-encoding'])
                $file['encoding'] = $part->headers['content-transfer-encoding'];

            // Include Content-Id (for inline-images), stripping the <>
            $file['cid'] = (isset($part->headers['content-id']))
                ? rtrim(ltrim($part->headers['content-id'], '<'), '>') : false;

            return array($file);
        }

        elseif ($this->tnef) {
            foreach ($this->tnef->attachments as $at) {
                $files[] = array(
                    'cid' => @$at->AttachContentId ?: false,
                    'data' => $at->getData(),
                    'size' => @$at->DataSize ?: null,
                    'type' => @$at->AttachMimeTag ?: false,
                    'name' => $at->getName(),
                );
            }
            return $files;
        }

        if($part==null)
            $part=$this->getStruct();

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
        if ($this->tnef && isset($this->tnef->Importance))
            // PidTagImportance is 0, 1, or 2
            // http://msdn.microsoft.com/en-us/library/ee237166(v=exchg.80).aspx
            return $this->tnef->Importance + 1;

        return Mail_Parse::parsePriority($this->getHeader());
    }

    static function parsePriority($header=null){

    	if (! $header)
    		return 0;
    	// Test for normal "X-Priority: INT" style header & stringy version.
    	// Allows for Importance possibility.
    	$matching_char = '';
    	if (preg_match ( '/priority: (\d|\w)/i', $header, $matching_char )
    			|| preg_match ( '/importance: (\d|\w)/i', $header, $matching_char )) {
    		switch ($matching_char[1]) {
    			case 'h' :
    			case 'H' :// high
    			case 'u':
    			case 'U': //Urgent
    			case 6 :
    			case 5 :
    				return 1;
    			case 'n' : // normal
    			case 'N' :
    			case 4 :
    			case 3 :
    				return 2;
    			case 'l' : // low
    			case 'L' :
    			case 2 :
    			case 1 :
    				return 3;
    		}
    	}
    	return 0;
    }

    function parseAddressList($address){
        if (!$address)
            return array();

        // Delivered-To may appear more than once in the email headers
        if (is_array($address))
            $address = implode(', ', $address);

        $parsed = Mail_RFC822::parseAddressList($address, null, null,false);

        if (PEAR::isError($parsed))
            return array();

        // Decode name and mailbox
        foreach ($parsed as $p) {
            $p->personal = Format::mimedecode($p->personal, $this->charset);
            // Some mail clients may send ISO-8859-1 strings without proper encoding.
            // Also, handle the more sane case where the mailbox is properly encoded
            // against RFC2047
            $p->mailbox = Format::mimedecode($p->mailbox, $this->charset);
        }

        return $parsed;
    }

    function parse($rawemail) {
        $parser= new Mail_Parse($rawemail);
        if (!$parser->decode())
            return null;

        return $parser;
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
        $data['emailId'] = 0;
        $data['recipients'] = array();
        $data['subject'] = $parser->getSubject();
        $data['header'] = $parser->getHeader();
        $data['mid'] = $parser->getMessageId();
        $data['priorityId'] = $parser->getPriority();
        $data['flags'] = new ArrayObject();

        //FROM address: who sent the email.
        if(($fromlist = $parser->getFromAddressList())) {
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

        /* Scan through the list of addressees (via To, Cc, and Delivered-To headers), and identify
         * how the mail arrived at the system. One of the mails should be in the system email list.
         * The recipient list (without the Delivered-To addressees) will be made available to the
         * ticket filtering system. However, addresses in the Delivered-To header should never be
         * considered for the collaborator list.
         */

        $tolist = array();
        if (($to = $parser->getToAddressList()))
            $tolist['to'] = $to;

        if (($cc = $parser->getCcAddressList()))
            $tolist['cc'] = $cc;

        if (($dt = $parser->getDeliveredToAddressList()))
            $tolist['delivered-to'] = $dt;

        foreach ($tolist as $source => $list) {
            foreach($list as $addr) {
                if (!($emailId=Email::getIdByEmail(strtolower($addr->mailbox).'@'.$addr->host))) {
                    //Skip virtual Delivered-To addresses
                    if ($source == 'delivered-to') continue;

                    $data['recipients'][] = array(
                        'source' => sprintf(_S("Email (%s)"), $source),
                        'name' => trim(@$addr->personal, '"'),
                        'email' => strtolower($addr->mailbox).'@'.$addr->host);
                } elseif(!$data['emailId']) {
                    $data['emailId'] = $emailId;
                }
            }
        }

        /*
         * In the event that the mail was delivered to the system although none of the system
         * mail addresses are in the addressee lists, be careful not to include the addressee
         * in the collaborator list. Therefore, the delivered-to addressees should be flagged so they
         * are not added to the collaborator list in the ticket creation process.
         */
        if ($tolist['delivered-to']) {
            foreach ($tolist['delivered-to'] as $addr) {
                foreach ($data['recipients'] as $i=>$r) {
                    if (strcasecmp($r['email'], $addr->mailbox.'@'.$addr->host) === 0)
                        $data['recipients'][$i]['source'] = 'delivered-to';
                }
            }
        }


        //maybe we got BCC'ed??
        if(!$data['emailId']) {
            $emailId =  0;
            if($bcc = $parser->getBccAddressList()) {
                foreach ($bcc as $addr)
                    if(($emailId=Email::getIdByEmail($addr->mailbox.'@'.$addr->host)))
                        break;
            }
            $data['emailId'] = $emailId;
        }

        if ($parser->isBounceNotice()) {
            // Fetch the original References and assign to 'references'
            if ($headers = $parser->getOriginalMessageHeaders()) {
                $data['references'] = $headers['references'];
                $data['in-reply-to'] = @$headers['in-reply-to'] ?: null;
            }
            // Fetch deliver status report
            $data['message'] = $parser->getDeliveryStatusMessage();
            $data['thread-type'] = 'N';
            $data['flags']['bounce'] = true;
        }
        else {
            // Typical email
            $data['message'] = $parser->getBody();
            $data['in-reply-to'] = @$parser->struct->headers['in-reply-to'];
            $data['references'] = @$parser->struct->headers['references'];
            $data['flags']['bounce'] = TicketFilter::isBounce($data['header']);
        }

        $data['to-email-id'] = $data['emailId'];

        if (($replyto = $parser->getReplyTo())) {
            $replyto = $replyto[0];
            $data['reply-to'] = $replyto->mailbox.'@'.$replyto->host;
            if ($replyto->personal)
                $data['reply-to-name'] = trim($replyto->personal, " \t\n\r\0\x0B\x22");
        }

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
