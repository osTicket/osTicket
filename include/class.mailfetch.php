<?php
/*********************************************************************
    class.mailfetch.php

    mail fetcher class. Uses IMAP ext for now.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require_once(INCLUDE_DIR.'class.mailparse.php');
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');
require_once(INCLUDE_DIR.'class.filter.php');

class MailFetcher {
    var $hostname;
    var $username;
    var $password;

    var $port;
    var $protocol;
    var $encryption;

    var $mbox;

    var $charset= 'UTF-8';
    
    function MailFetcher($username,$password,$hostname,$port,$protocol,$encryption='') {

        if(!strcasecmp($protocol,'pop')) //force pop3
            $protocol='pop3';

        $this->hostname=$hostname;
        $this->username=$username;
        $this->password=$password;
        $this->protocol=strtolower($protocol);
        $this->port = $port;
        $this->encryption = $encryption;

        $this->serverstr=sprintf('{%s:%d/%s',$this->hostname,$this->port,strtolower($this->protocol));
        if(!strcasecmp($this->encryption,'SSL')){
            $this->serverstr.='/ssl';
        }
        $this->serverstr.='/novalidate-cert}INBOX'; //add other flags here as needed.

        //echo $this->serverstr;
        //Charset to convert the mail to.
        $this->charset='UTF-8';
        //Set timeouts 
        if(function_exists('imap_timeout'))
            imap_timeout(1,20); //Open timeout.
    }
    
    function connect() {
        return $this->open()?true:false;
    }

    function open() {
       
        //echo $this->serverstr;
        if($this->mbox && imap_ping($this->mbox))
            return $this->mbox;
            
        $this->mbox =@imap_open($this->serverstr,$this->username,$this->password);
        
        return $this->mbox;
    }

    function close() {
        imap_close($this->mbox,CL_EXPUNGE);
    }

    function mailcount(){
        return count(imap_headers($this->mbox));
    }

    //Get mail boxes.
    function getMailboxes(){


        $mstr=sprintf('{%s:%d/%s',$this->hostname,$this->port,strtolower($this->protocol));
        if(!strcasecmp($this->encryption,'SSL'))
            $mstr.='/ssl';
        $mstr.='/novalidate-cert}';
        $list=array();
        if(($folders=imap_listmailbox($this->mbox,$mstr,'*')) && is_array($folders)){
            foreach($folders as $k=>$folder){
                $list[]= str_replace($mstr, "", imap_utf7_decode(trim($folder)));
            }
        }

        return $list;
    }

    //Create a folder.
    function createMailbox($folder){

        if(!$folder) return false;

        $mstr=sprintf('{%s:%d/%s',$this->hostname,$this->port,strtolower($this->protocol));
        if(!strcasecmp($this->encryption,'SSL'))
            $mstr.='/ssl';
        $mstr.='/novalidate-cert}'.$folder;
            
        return imap_createmailbox($this->mbox,imap_utf7_encode($mstr));
    }

    /* check if a folder exits - create on if requested */
    function checkMailbox($folder,$create=false){

        if(($mailboxes=$this->getMailboxes()) && in_array($folder,$mailboxes))
            return true;

        return ($create && $this->createMailbox($folder));
    }


    function decode($encoding,$text) {

        switch($encoding) {
            case 1:
            $text=imap_8bit($text);
            break;
            case 2:
            $text=imap_binary($text);
            break;
            case 3:
            $text=imap_base64($text);
            break;
            case 4:
            $text=imap_qprint($text);
            break;
            case 5:
            default:
             $text=$text;
        } 
        return $text;
    }

    //Convert text to desired encoding..defaults to utf8
    function mime_encode($text,$charset=null,$enc='utf-8') { //Thank in part to afterburner  
                
        $encodings=array('UTF-8','WINDOWS-1251', 'ISO-8859-5', 'ISO-8859-1','KOI8-R');
        if(function_exists("iconv") and $text) {
            if($charset)
                return iconv($charset,$enc.'//IGNORE',$text);
            elseif(function_exists("mb_detect_encoding"))
                return iconv(mb_detect_encoding($text,$encodings),$enc,$text);
        }

        return utf8_encode($text);
    }
    
    //Generic decoder - mirrors imap_utf8
    function mime_decode($text) {
        
        $a = imap_mime_header_decode($text);
        $str = '';
        foreach ($a as $k => $part)
            $str.= $part->text;
        
        return $str?$str:imap_utf8($text);
    }

    function getLastError(){
        return imap_last_error();
    }

    function getMimeType($struct) {
        $mimeType = array('TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER');
        if(!$struct || !$struct->subtype)
            return 'TEXT/PLAIN';
        
        return $mimeType[(int) $struct->type].'/'.$struct->subtype;
    }

    function getHeaderInfo($mid) {
        
        $headerinfo=imap_headerinfo($this->mbox,$mid);
        $sender=$headerinfo->from[0];

        //Parse what we need...
        $header=array(
                      'from'   =>array('name'  =>@$sender->personal,'email' =>strtolower($sender->mailbox).'@'.$sender->host),
                      'subject'=>@$headerinfo->subject,
                      'mid'    =>$headerinfo->message_id);
        return $header;
    }

    //search for specific mime type parts....encoding is the desired encoding.
    function getPart($mid,$mimeType,$encoding=false,$struct=null,$partNumber=false){
          
        if(!$struct && $mid)
            $struct=@imap_fetchstructure($this->mbox, $mid);
        //Match the mime type.
        if($struct && !$struct->ifdparameters && strcasecmp($mimeType,$this->getMimeType($struct))==0){
            $partNumber=$partNumber?$partNumber:1;
            if(($text=imap_fetchbody($this->mbox, $mid, $partNumber))){
                if($struct->encoding==3 or $struct->encoding==4) //base64 and qp decode.
                    $text=$this->decode($struct->encoding,$text);

                $charset=null;
                if($encoding) { //Convert text to desired mime encoding...
                    if($struct->ifparameters){
                        if(!strcasecmp($struct->parameters[0]->attribute,'CHARSET') && strcasecmp($struct->parameters[0]->value,'US-ASCII'))
                            $charset=trim($struct->parameters[0]->value);
                    }
                    $text=$this->mime_encode($text,$charset,$encoding);
                }
                return $text;
            }
        }
        //Do recursive search
        $text='';
        if($struct && $struct->parts){
            while(list($i, $substruct) = each($struct->parts)) {
                if($partNumber) 
                    $prefix = $partNumber . '.';
                if(($result=$this->getPart($mid,$mimeType,$encoding,$substruct,$prefix.($i+1))))
                    $text.=$result;
            }
        }
        return $text;
    }

    function getHeader($mid){
        return imap_fetchheader($this->mbox, $mid,FT_PREFETCHTEXT);
    }

    
    function getPriority($mid){
        return Mail_Parse::parsePriority($this->getHeader($mid));
    }

    function getBody($mid) {
        
        $body ='';
        if(!($body = $this->getPart($mid,'TEXT/PLAIN',$this->charset))) {
            if(($body = $this->getPart($mid,'TEXT/HTML',$this->charset))) {
                //Convert tags of interest before we striptags
                $body=str_replace("</DIV><DIV>", "\n", $body);
                $body=str_replace(array("<br>", "<br />", "<BR>", "<BR />"), "\n", $body);
                $body=Format::html($body); //Balance html tags before stripping.
                $body=Format::striptags($body); //Strip tags??
            }
        }
        return $body;
    }

    function createTicket($mid,$emailid=0){
        global $cfg;

        $mailinfo=$this->getHeaderInfo($mid);

        //Make sure the email is NOT one of the undeleted emails.
        if($mailinfo['mid'] && ($id=Ticket::getIdByMessageId(trim($mailinfo['mid']),$mailinfo['from']['email']))){
            //TODO: Move emails to a fetched folder when delete is false?? 
            return true;
        }

	//Is the email address banned?
        if($mailinfo['from']['email'] && EmailFilter::isBanned($mailinfo['from']['email'])) {
	    //We need to let admin know...
            Sys::log(LOG_WARNING,'Ticket denied','Banned email - '.$mailinfo['from']['email']);
	    return true;
        }
	

        $var['name']=$this->mime_decode($mailinfo['from']['name']);
        $var['email']=$mailinfo['from']['email'];
        $var['subject']=$mailinfo['subject']?$this->mime_decode($mailinfo['subject']):'[No Subject]';
        $var['message']=Format::stripEmptyLines($this->getBody($mid));
        $var['header']=$this->getHeader($mid);
        $var['emailId']=$emailid?$emailid:$cfg->getDefaultEmailId(); //ok to default?
        $var['name']=$var['name']?$var['name']:$var['email']; //No name? use email
        $var['mid']=$mailinfo['mid'];

        if($cfg->useEmailPriority())
            $var['pri']=$this->getPriority($mid);
       
        $ticket=null;
        $newticket=true;
        //Check the subject line for possible ID.
        if(preg_match ("[[#][0-9]{1,10}]",$var['subject'],$regs)) {
            $extid=trim(preg_replace("/[^0-9]/", "", $regs[0]));
            $ticket= new Ticket(Ticket::getIdByExtId($extid));
            //Allow mismatched emails?? For now NO.
            if(!$ticket || strcasecmp($ticket->getEmail(),$var['email']))
                $ticket=null;
        }
        
        $errors=array();
        if(!$ticket) {
            # Apply email filters for the new ticket
            $ef = new EmailFilter($var); $ef->apply($var);
            if(!($ticket=Ticket::create($var,$errors,'Email')) || $errors)
                return null;
            $msgid=$ticket->getLastMsgId();
        }else{
            $message=$var['message'];
            //Strip quoted reply...TODO: figure out how mail clients do it without special tag..
            if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()) && strpos($var['message'],$tag))
                list($message)=split($tag,$var['message']);
            $msgid=$ticket->postMessage($message,'Email',$var['mid'],$var['header']);
        }
        //Save attachments if any.
        if($msgid && $cfg->allowEmailAttachments()){
            if(($struct = imap_fetchstructure($this->mbox,$mid)) && $struct->parts) {
                if($ticket->getLastMsgId()!=$msgid)
                    $ticket->setLastMsgId($msgid);
                $this->saveAttachments($ticket,$mid,$struct);

            }
        } 
        return $ticket;
    }

    function saveAttachments($ticket,$mid,$part,$index=0) {
        global $cfg;

        if($part && $part->ifdparameters && ($filename=$part->dparameters[0]->value)){ //attachment
            $index=$index?$index:1;
            if($ticket && $cfg->canUploadFileType($filename) && $cfg->getMaxFileSize()>=$part->bytes) {
                //extract the attachments...and do the magic.
                $data=$this->decode($part->encoding, imap_fetchbody($this->mbox,$mid,$index));
                $ticket->saveAttachment($filename,$data,$ticket->getLastMsgId(),'M');
                return;
            }
            //TODO: Log failure??
        }

        //Recursive attachment search!
        if($part && $part->parts) {
            foreach($part->parts as $k=>$struct) {
                if($index) $prefix = $index.'.';
                $this->saveAttachments($ticket,$mid,$struct,$prefix.($k+1));
            }
        }

    }

    function fetchTickets($emailid,$max=20,$deletemsgs=false){

        $nummsgs=imap_num_msg($this->mbox);
        //echo "New Emails:  $nummsgs\n";
        $msgs=$errors=0;
        for($i=$nummsgs; $i>0; $i--){ //process messages in reverse. Latest first. FILO.
            if($this->createTicket($i,$emailid)){
                imap_setflag_full($this->mbox, imap_uid($this->mbox,$i), "\\Seen", ST_UID); //IMAP only??
                if($deletemsgs)
                    imap_delete($this->mbox,$i);
                $msgs++;
                $errors=0; //We are only interested in consecutive errors.
            }else{
                $errors++;
            }
            if(($max && $msgs>=$max) || $errors>20)
                break;
        }
        @imap_expunge($this->mbox);

        return $msgs;
    }

    function fetchMail(){
        global $cfg;
      
        if(!$cfg->canFetchMail())
            return;

        //We require imap ext to fetch emails via IMAP/POP3
        if(!function_exists('imap_open')) {
            $msg='PHP must be compiled with IMAP extension enabled for IMAP/POP3 fetch to work!';
            Sys::log(LOG_WARN,'Mail Fetch Error',$msg);
            return;
        }

        $MAX_ERRORS=5; //Max errors before we start delayed fetch attempts - hardcoded for now.

        $sql=' SELECT email_id,mail_host,mail_port,mail_protocol,mail_encryption,mail_delete,mail_errors,userid,userpass FROM '.EMAIL_TABLE.
             ' WHERE mail_active=1 AND (mail_errors<='.$MAX_ERRORS.' OR (TIME_TO_SEC(TIMEDIFF(NOW(),mail_lasterror))>5*60) )'.
             ' AND (mail_lastfetch IS NULL OR TIME_TO_SEC(TIMEDIFF(NOW(),mail_lastfetch))>mail_fetchfreq*60) ';
        //echo $sql;
        if(!($accounts=db_query($sql)) || !db_num_rows($accounts))
            return;

        //TODO: Lock the table here??
        while($row=db_fetch_array($accounts)) {
            $fetcher = new MailFetcher($row['userid'],Misc::decrypt($row['userpass'],SECRET_SALT),
                                       $row['mail_host'],$row['mail_port'],$row['mail_protocol'],$row['mail_encryption']);
            if($fetcher->connect()){   
                $fetcher->fetchTickets($row['email_id'],$row['mail_fetchmax'],$row['mail_delete']?true:false);
                $fetcher->close();
                db_query('UPDATE '.EMAIL_TABLE.' SET mail_errors=0, mail_lastfetch=NOW() WHERE email_id='.db_input($row['email_id']));
            }else{
                $errors=$row['mail_errors']+1;
                db_query('UPDATE '.EMAIL_TABLE.' SET mail_errors=mail_errors+1, mail_lasterror=NOW() WHERE email_id='.db_input($row['email_id']));
                if($errors>=$MAX_ERRORS){
                    //We've reached the MAX consecutive errors...will attempt logins at delayed intervals
                    $msg="\nThe system is having trouble fetching emails from the following mail account: \n".
                        "\nUser: ".$row['userid'].
                        "\nHost: ".$row['mail_host'].
                        "\nError: ".$fetcher->getLastError().
                        "\n\n ".$errors.' consecutive errors. Maximum of '.$MAX_ERRORS. ' allowed'.
                        "\n\n This could be connection issues related to the host. Next delayed login attempt in aprox. 10 minutes";
                    Sys::alertAdmin('Mail Fetch Failure Alert',$msg,true);
                }
            }
        }
    }
}
?>
