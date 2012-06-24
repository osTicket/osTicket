#!/usr/bin/php -q
<?php
/*********************************************************************
    pipe.php

    Converts piped emails to ticket. Both local and remote!

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
@chdir(realpath(dirname(__FILE__)).'/'); //Change dir.
ini_set('memory_limit', '256M'); //The concern here is having enough mem for emails with attachments.
require('api.inc.php');
require_once(INCLUDE_DIR.'class.mailparse.php');
require_once(INCLUDE_DIR.'class.email.php');

//Make sure piping is enabled!
if(!$cfg->enableEmailPiping())
    api_exit(EX_UNAVAILABLE,'Email piping not enabled - check MTA settings.');
//Get the input
$data=isset($_SERVER['HTTP_HOST'])?file_get_contents('php://input'):file_get_contents('php://stdin');
if(empty($data)){
    api_exit(EX_NOINPUT,'No data');
}

//Parse the email.
$parser= new Mail_Parse($data);
if(!$parser->decode()){ //Decode...returns false on decoding errors
    api_exit(EX_DATAERR,'Email parse failed ['.$parser->getError()."]\n\n".$data);    
}



//Check from address. make sure it is not a banned address.
$fromlist = $parser->getFromAddressList();
//Check for parsing errors on FROM address.
if(!$fromlist || PEAR::isError($fromlist)){
    api_exit(EX_DATAERR,'Invalid FROM address ['.$fromlist?$fromlist->getMessage():''."]\n\n".$data);
}

$from=$fromlist[0]; //Default.
foreach($fromlist as $fromobj){
    if(!Validator::is_email($fromobj->mailbox.'@'.$fromobj->host))
        continue;
    $from=$fromobj;
    break;
}

//TO Address:Try to figure out the email associated with the message.
$tolist = $parser->getToAddressList();
foreach ($tolist as $toaddr){
    if(($emailId=Email::getIdByEmail($toaddr->mailbox.'@'.$toaddr->host))){
        //We've found target email.
        break;
    }
}
if(!$emailId && ($cclist=$parser->getCcAddressList())) {
    foreach ($cclist as $ccaddr){
        if(($emailId=Email::getIdByEmail($ccaddr->mailbox.'@'.$ccaddr->host))){
            break;
        }
    }
}
//TODO: Options to reject emails without a matching To address in db? May be it was Bcc? Current Policy: If you pipe, we accept policy

require_once(INCLUDE_DIR.'class.ticket.php'); //We now need this bad boy!

$var=array();
$deptId=0;
$name=trim($from->personal,'"');
if($from->comment && $from->comment[0])
    $name.=' ('.$from->comment[0].')';
$subj=utf8_encode($parser->getSubject());
if(!($body=Format::stripEmptyLines($parser->getBody())) && $subj)
    $body=$subj;

$var['mid']=$parser->getMessageId();
$var['email']=$from->mailbox.'@'.$from->host;
$var['name']=$name?utf8_encode($name):$var['email'];
$var['emailId']=$emailId?$emailId:$cfg->getDefaultEmailId();
$var['subject']=$subj?$subj:'[No Subject]';
$var['message']=utf8_encode(Format::stripEmptyLines($body));
$var['header']=$parser->getHeader();
$var['priorityId']=$cfg->useEmailPriority()?$parser->getPriority():0;

$ticket=null;
if(preg_match ("[[#][0-9]{1,10}]",$var['subject'],$regs)) {
    $extid=trim(preg_replace("/[^0-9]/", "", $regs[0]));
    $ticket= new Ticket(Ticket::getIdByExtId($extid));
    //Allow mismatched emails?? For now hell NO.
    if(!is_object($ticket) || strcasecmp($ticket->getEmail(),$var['email']))
        $ticket=null;
}        
$errors=array();
$msgid=0;
if(!$ticket){ //New tickets...
    # Apply filters against the new ticket
    $ef = new EmailFilter($var); $ef->apply($var);
    $ticket=Ticket::create($var,$errors,'email');
    if(!is_object($ticket) || $errors){
        api_exit(EX_DATAERR,'Ticket create Failed '.implode("\n",$errors)."\n\n");
    }
    $msgid=$ticket->getLastMsgId();
}else{
    $message=$var['message'];
    //Strip quoted reply...TODO: figure out how mail clients do it without special tag..
    if($cfg->stripQuotedReply() && ($tag=$cfg->getReplySeparator()) && strpos($var['message'],$tag))
        list($message)=split($tag,$var['message']);
    //post message....postMessage does the cleanup.
    if(!($msgid=$ticket->postMessage($message,'Email',$var['mid'],$var['header']))) {
        api_exit(EX_DATAERR,"Unable to post message \n\n $message\n");
    }
}
//Ticket created...save attachments if enabled.
if($cfg->allowEmailAttachments()) {                   
    if($attachments=$parser->getAttachments()){
        //print_r($attachments);
        foreach($attachments as $k=>$attachment){
            if($attachment['filename'] && $cfg->canUploadFileType($attachment['filename'])) {
                $ticket->saveAttachment($attachment['filename'],$attachment['body'],$msgid,'M');
            }
        }
    }
}
api_exit(EX_SUCCESS);
?>
