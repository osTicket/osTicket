<?php
/*********************************************************************
    class.mailfetch.php

    mail fetcher class. Uses IMAP ext for now.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require_once(INCLUDE_DIR.'class.mailparse.php');
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');
require_once(INCLUDE_DIR.'class.email.php');
require_once(INCLUDE_DIR.'class.filter.php');

class MailFetcher {

    var $ht;

    var $mbox;
    var $srvstr;

    var $charset = 'UTF-8';
    var $encodings =array('UTF-8','WINDOWS-1251', 'ISO-8859-5', 'ISO-8859-1','KOI8-R');

    function MailFetcher($email, $charset='UTF-8') {


        if($email && is_numeric($email)) //email_id
            $email=Email::lookup($email);

        if(is_object($email))
            $this->ht = $email->getMailAccountInfo();
        elseif(is_array($email) && $email['host']) //hashtable of mail account info
            $this->ht = $email;
        else
            $this->ht = null;

        $this->charset = $charset;

        if($this->ht) {
            if(!strcasecmp($this->ht['protocol'],'pop')) //force pop3
                $this->ht['protocol'] = 'pop3';
            else
                $this->ht['protocol'] = strtolower($this->ht['protocol']);

            //Max fetch per poll
            if(!$this->ht['max_fetch'] || !is_numeric($this->ht['max_fetch']))
                $this->ht['max_fetch'] = 20;

            //Mail server string
            $this->srvstr=sprintf('{%s:%d/%s', $this->getHost(), $this->getPort(), $this->getProtocol());
            if(!strcasecmp($this->getEncryption(), 'SSL'))
                $this->srvstr.='/ssl';

            $this->srvstr.='/novalidate-cert}';

        }

        //Set timeouts
        if(function_exists('imap_timeout')) imap_timeout(1,20);

    }

    function getEmailId() {
        return $this->ht['email_id'];
    }

    function getHost() {
        return $this->ht['host'];
    }

    function getPort() {
        return $this->ht['port'];
    }

    function getProtocol() {
        return $this->ht['protocol'];
    }

    function getEncryption() {
        return $this->ht['encryption'];
    }

    function getUsername() {
        return $this->ht['username'];
    }

    function getPassword() {
        return $this->ht['password'];
    }

    /* osTicket Settings */

    function canDeleteEmails() {
        return ($this->ht['delete_mail']);
    }

    function getMaxFetch() {
        return $this->ht['max_fetch'];
    }

    function getArchiveFolder() {
        return $this->ht['archive_folder'];
    }

    /* Core */

    function connect() {
        return ($this->mbox && $this->ping())?$this->mbox:$this->open();
    }

    function ping() {
        return ($this->mbox && imap_ping($this->mbox));
    }

    /* Default folder is inbox - TODO: provide user an option to fetch from diff folder/label */
    function open($box='INBOX') {

        if($this->mbox)
           $this->close();

        $this->mbox = imap_open($this->srvstr.$box, $this->getUsername(), $this->getPassword());

        return $this->mbox;
    }

    function close($flag=CL_EXPUNGE) {
        imap_close($this->mbox, $flag);
    }

    function mailcount() {
        return count(imap_headers($this->mbox));
    }

    //Get mail boxes.
    function getMailboxes() {

        if(!($folders=imap_list($this->mbox, $this->srvstr, "*")) || !is_array($folders))
            return null;

        $list = array();
        foreach($folders as $folder)
            $list[]= str_replace($this->srvstr, '', imap_utf7_decode(trim($folder)));

        return $list;
    }

    //Create a folder.
    function createMailbox($folder) {

        if(!$folder) return false;

        return imap_createmailbox($this->mbox, imap_utf7_encode($this->srvstr.trim($folder)));
    }

    /* check if a folder exists - create one if requested */
    function checkMailbox($folder, $create=false) {

        if(($mailboxes=$this->getMailboxes()) && in_array(trim($folder), $mailboxes))
            return true;

        return ($create && $this->createMailbox($folder));
    }


    function decode($text, $encoding) {

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
        }

        return $text;
    }

    //Convert text to desired encoding..defaults to utf8
    function mime_encode($text, $charset=null, $encoding='utf-8') { //Thank in part to afterburner
        return Format::encode($text, $charset, $encoding);
    }

    //Generic decoder - resulting text is utf8 encoded -> mirrors imap_utf8
    function mime_decode($text, $encoding='utf-8') {

        $str = '';
        $parts = imap_mime_header_decode($text);
        foreach ($parts as $part)
            $str.= $this->mime_encode($part->text, $part->charset, $encoding);

        return $str?$str:imap_utf8($text);
    }

    function getLastError() {
        return imap_last_error();
    }

    function getMimeType($struct) {
        $mimeType = array('TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER');
        if(!$struct || !$struct->subtype)
            return 'TEXT/PLAIN';

        return $mimeType[(int) $struct->type].'/'.$struct->subtype;
    }

    function getHeaderInfo($mid) {

        if(!($headerinfo=imap_headerinfo($this->mbox, $mid)) || !$headerinfo->from)
            return null;

        $sender=$headerinfo->from[0];
        //Just what we need...
        $header=array('name'  =>@$sender->personal,
                      'email' =>(strtolower($sender->mailbox).'@'.$sender->host),
                      'subject'=>@$headerinfo->subject,
                      'mid'    =>$headerinfo->message_id
                      );

        return $header;
    }

    //search for specific mime type parts....encoding is the desired encoding.
    function getPart($mid, $mimeType, $encoding=false, $struct=null, $partNumber=false) {

        if(!$struct && $mid)
            $struct=@imap_fetchstructure($this->mbox, $mid);

        //Match the mime type.
        if($struct && !$struct->ifdparameters && strcasecmp($mimeType, $this->getMimeType($struct))==0) {
            $partNumber=$partNumber?$partNumber:1;
            if(($text=imap_fetchbody($this->mbox, $mid, $partNumber))) {
                if($struct->encoding==3 or $struct->encoding==4) //base64 and qp decode.
                    $text=$this->decode($text, $struct->encoding);

                $charset=null;
                if($encoding) { //Convert text to desired mime encoding...
                    if($struct->ifparameters) {
                        if(!strcasecmp($struct->parameters[0]->attribute,'CHARSET') && strcasecmp($struct->parameters[0]->value,'US-ASCII'))
                            $charset=trim($struct->parameters[0]->value);
                    }
                    $text=$this->mime_encode($text, $charset, $encoding);
                }
                return $text;
            }
        }

        //Do recursive search
        $text='';
        if($struct && $struct->parts) {
            while(list($i, $substruct) = each($struct->parts)) {
                if($partNumber)
                    $prefix = $partNumber . '.';
                if(($result=$this->getPart($mid, $mimeType, $encoding, $substruct, $prefix.($i+1))))
                    $text.=$result;
            }
        }

        return $text;
    }

    /*
     getAttachments

     search and return a hashtable of attachments....
     NOTE: We're not actually fetching the body of the attachment  - we'll do it on demand to save some memory.

     */
    function getAttachments($part, $index=0) {

        if($part && !$part->parts) {
            //Check if the part is an attachment.
            $filename = '';
            if($part->ifdisposition && in_array(strtolower($part->disposition), array('attachment', 'inline'))) {
                $filename = $part->dparameters[0]->value;
                //Some inline attachments have multiple parameters.
                if(count($part->dparameters)>1) {
                    foreach($part->dparameters as $dparameter) {
                        if(!in_array(strtoupper($dparameter->attribute), array('FILENAME', 'NAME'))) continue;
                        $filename = $dparameter->value;
                        break;
                    }
                }
            } elseif($part->ifparameters && $part->parameters && $part->type > 0) { //inline attachments without disposition.
                foreach($part->parameters as $parameter) {
                    if(!in_array(strtoupper($parameter->attribute), array('FILENAME', 'NAME'))) continue;
                    $filename = $parameter->value;
                    break;
                }
            }

            if($filename) {
                return array(
                        array(
                            'name'  => $this->mime_decode($filename),
                            'type'  => $this->getMimeType($part),
                            'encoding' => $part->encoding,
                            'index' => ($index?$index:1)
                            )
                        );
            }
        }

        //Recursive attachment search!
        $attachments = array();
        if($part && $part->parts) {
            foreach($part->parts as $k=>$struct) {
                if($index) $prefix = $index.'.';
                $attachments = array_merge($attachments, $this->getAttachments($struct, $prefix.($k+1)));
            }
        }

        return $attachments;
    }

    function getHeader($mid) {
        return imap_fetchheader($this->mbox, $mid,FT_PREFETCHTEXT);
    }


    function getPriority($mid) {
        return Mail_Parse::parsePriority($this->getHeader($mid));
    }

    function getBody($mid) {

        $body ='';
        if(!($body = $this->getPart($mid,'TEXT/PLAIN', $this->charset))) {
            if(($body = $this->getPart($mid,'TEXT/HTML', $this->charset))) {
                //Convert tags of interest before we striptags
                $body=str_replace("</DIV><DIV>", "\n", $body);
                $body=str_replace(array("<br>", "<br />", "<BR>", "<BR />"), "\n", $body);
                $body=Format::safe_html($body); //Balance html tags & neutralize unsafe tags.
            }
        }

        return $body;
    }

    //email to ticket
    function createTicket($mid) {
        global $ost;

        if(!($mailinfo = $this->getHeaderInfo($mid)))
            return false;

        //Make sure the email is NOT already fetched... (undeleted emails)
        if($mailinfo['mid'] && ($id=Ticket::getIdByMessageId(trim($mailinfo['mid']), $mailinfo['email'])))
            return true; //Reporting success so the email can be moved or deleted.

	    //Is the email address banned?
        if($mailinfo['email'] && TicketFilter::isBanned($mailinfo['email'])) {
	        //We need to let admin know...
            $ost->logWarning('Ticket denied', 'Banned email - '.$mailinfo['email'], false);
	        return true; //Report success (moved or delete)
        }

        $emailId = $this->getEmailId();
        $vars = array();
        $vars['email']=$mailinfo['email'];
        $vars['name']=$this->mime_decode($mailinfo['name']);
        $vars['subject']=$mailinfo['subject']?$this->mime_decode($mailinfo['subject']):'[No Subject]';
        $vars['message']=Format::stripEmptyLines($this->getBody($mid));
        $vars['header']=$this->getHeader($mid);
        $vars['emailId']=$emailId?$emailId:$ost->getConfig()->getDefaultEmailId(); //ok to default?
        $vars['mid']=$mailinfo['mid'];

        //Missing FROM name  - use email address.
        if(!$vars['name'])
            $vars['name'] = $vars['email'];

        //An email with just attachments can have empty body.
        if(!$vars['message'])
            $vars['message'] = '(EMPTY)';

        if($ost->getConfig()->useEmailPriority())
            $vars['priorityId']=$this->getPriority($mid);

        $ticket=null;
        $newticket=true;
        //Check the subject line for possible ID.
        if($vars['subject'] && preg_match ("[[#][0-9]{1,10}]", $vars['subject'], $regs)) {
            $tid=trim(preg_replace("/[^0-9]/", "", $regs[0]));
            //Allow mismatched emails?? For now NO.
            if(!($ticket=Ticket::lookupByExtId($tid, $vars['email'])))
                $ticket=null;
        }

        $errors=array();
        if($ticket) {
            if(!($message=$ticket->postMessage($vars, 'Email')))
                return false;

        } elseif (($ticket=Ticket::create($vars, $errors, 'Email'))) {
            $message = $ticket->getLastMessage();
        } else {
            //Report success if the email was absolutely rejected.
            if(isset($errors['errno']) && $errors['errno'] == 403)
                return true;

            # check if it's a bounce!
            if($vars['header'] && TicketFilter::isAutoBounce($vars['header'])) {
                $ost->logWarning('Bounced email', $vars['message'], false);
                return true;
            }

            //TODO: Log error..
            return null;
        }

        //Save attachments if any.
        if($message
                && $ost->getConfig()->allowEmailAttachments()
                && ($struct = imap_fetchstructure($this->mbox, $mid))
                && $struct->parts
                && ($attachments=$this->getAttachments($struct))) {

            foreach($attachments as $a ) {
                $file = array('name'  => $a['name'], 'type'  => $a['type']);

                //Check the file  type
                if(!$ost->isFileTypeAllowed($file))
                    $file['error'] = 'Invalid file type (ext) for '.Format::htmlchars($file['name']);
                else //only fetch the body if necessary TODO: Make it a callback.
                    $file['data'] = $this->decode(imap_fetchbody($this->mbox, $mid, $a['index']), $a['encoding']);

                $message->importAttachment($file);
            }
        }

        return $ticket;
    }


    function fetchEmails() {


        if(!$this->connect())
            return false;

        $archiveFolder = $this->getArchiveFolder();
        $delete = $this->canDeleteEmails();
        $max = $this->getMaxFetch();

        $nummsgs=imap_num_msg($this->mbox);
        //echo "New Emails:  $nummsgs\n";
        $msgs=$errors=0;
        for($i=$nummsgs; $i>0; $i--) { //process messages in reverse.
            if($this->createTicket($i)) {

                imap_setflag_full($this->mbox, imap_uid($this->mbox, $i), "\\Seen", ST_UID); //IMAP only??
                if((!$archiveFolder || !imap_mail_move($this->mbox, $i, $archiveFolder)) && $delete)
                    imap_delete($this->mbox, $i);

                $msgs++;
                $errors=0; //We are only interested in consecutive errors.
            } else {
                $errors++;
            }

            if($max && ($msgs>=$max || $errors>($max*0.8)))
                break;
        }

        //Warn on excessive errors
        if($errors>$msgs) {
            $warn=sprintf('Excessive errors processing emails for %s/%s. Please manually check the inbox.',
                    $this->getHost(), $this->getUsername());
            $this->log($warn);
        }

        @imap_expunge($this->mbox);

        return $msgs;
    }

    function log($error) {
        global $ost;
        $ost->logWarning('Mail Fetcher', $error);
    }

    /*
       MailFetcher::run()

       Static function called to initiate email polling
     */
    function run() {
        global $ost;

        if(!$ost->getConfig()->isEmailPollingEnabled())
            return;

        //We require imap ext to fetch emails via IMAP/POP3
        //We check here just in case the extension gets disabled post email config...
        if(!function_exists('imap_open')) {
            $msg='osTicket requires PHP IMAP extension enabled for IMAP/POP3 email fetch to work!';
            $ost->logWarning('Mail Fetch Error', $msg);
            return;
        }

        //Hardcoded error control...
        $MAXERRORS = 5; //Max errors before we start delayed fetch attempts
        $TIMEOUT = 10; //Timeout in minutes after max errors is reached.

        $sql=' SELECT email_id, mail_errors FROM '.EMAIL_TABLE
            .' WHERE mail_active=1 '
            .'  AND (mail_errors<='.$MAXERRORS.' OR (TIME_TO_SEC(TIMEDIFF(NOW(), mail_lasterror))>'.($TIMEOUT*60).') )'
            .'  AND (mail_lastfetch IS NULL OR TIME_TO_SEC(TIMEDIFF(NOW(), mail_lastfetch))>mail_fetchfreq*60)'
            .' ORDER BY mail_lastfetch DESC'
            .' LIMIT 10'; //Processing up to 10 emails at a time.

       // echo $sql;
        if(!($res=db_query($sql)) || !db_num_rows($res))
            return;  /* Failed query (get's logged) or nothing to do... */

        //TODO: Lock the table here??

        while(list($emailId, $errors)=db_fetch_row($res)) {
            $fetcher = new MailFetcher($emailId);
            if($fetcher->connect()) {
                $fetcher->fetchEmails();
                $fetcher->close();
                db_query('UPDATE '.EMAIL_TABLE.' SET mail_errors=0, mail_lastfetch=NOW() WHERE email_id='.db_input($emailId));
            } else {
                db_query('UPDATE '.EMAIL_TABLE.' SET mail_errors=mail_errors+1, mail_lasterror=NOW() WHERE email_id='.db_input($emailId));
                if(++$errors>=$MAXERRORS) {
                    //We've reached the MAX consecutive errors...will attempt logins at delayed intervals
                    $msg="\nosTicket is having trouble fetching emails from the following mail account: \n".
                        "\nUser: ".$fetcher->getUsername().
                        "\nHost: ".$fetcher->getHost().
                        "\nError: ".$fetcher->getLastError().
                        "\n\n ".$errors.' consecutive errors. Maximum of '.$MAXERRORS. ' allowed'.
                        "\n\n This could be connection issues related to the mail server. Next delayed login attempt in aprox. $TIMEOUT minutes";
                    $ost->alertAdmin('Mail Fetch Failure Alert', $msg, true);
                }
            }
        } //end while.
    }
}
?>
