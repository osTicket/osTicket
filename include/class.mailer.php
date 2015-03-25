<?php
/*********************************************************************
    class.mailer.php

    osTicket mailer

    It's mainly PEAR MAIL wrapper for now (more improvements planned).

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

include_once(INCLUDE_DIR.'class.email.php');
require_once(INCLUDE_DIR.'html2text.php');

class Mailer {

    var $email;

    var $ht = array();
    var $attachments = array();
    var $options = array();

    var $smtp = array();
    var $eol="\n";

    function Mailer($email=null, array $options=array()) {
        global $cfg;

        if(is_object($email) && $email->isSMTPEnabled() && ($info=$email->getSMTPInfo())) { //is SMTP enabled for the current email?
            $this->smtp = $info;
        } elseif($cfg && ($e=$cfg->getDefaultSMTPEmail()) && $e->isSMTPEnabled()) { //What about global SMTP setting?
            $this->smtp = $e->getSMTPInfo();
            if(!$e->allowSpoofing() || !$email)
                $email = $e;
        } elseif(!$email && $cfg && ($e=$cfg->getDefaultEmail())) {
            if($e->isSMTPEnabled() && ($info=$e->getSMTPInfo()))
                $this->smtp = $info;
            $email = $e;
        }

        $this->email = $email;
        $this->attachments = array();
        $this->options = $options;
    }

    function getEOL() {
        return $this->eol;
    }

    function getEmail() {
        return $this->email;
    }

    function getSMTPInfo() {
        return $this->smtp;
    }
    /* FROM Address */
    function setFromAddress($from) {
        $this->ht['from'] = $from;
    }

    function getFromAddress() {

        if(!$this->ht['from'] && ($email=$this->getEmail()))
            $this->ht['from'] =sprintf('"%s" <%s>', ($email->getName()?$email->getName():$email->getEmail()), $email->getEmail());

        return $this->ht['from'];
    }

    /* attachments */
    function getAttachments() {
        return $this->attachments;
    }

    function addAttachment($attachment) {
        // XXX: This looks too assuming; however, the attachment processor
        // in the ::send() method seems hard coded to expect this format
        $this->attachments[$attachment['file_id']] = $attachment;
    }

    function addAttachments($attachments) {
        foreach ($attachments as $a)
            $this->addAttachment($a);
    }

    /**
     * getMessageId
     *
     * Generates a unique message ID for an outbound message. Optionally,
     * the recipient can be used to create a tag for the message ID where
     * the user-id and thread-entry-id are encoded in the message-id so
     * the message can be threaded if it is replied to without any other
     * indicator of the thread to which it belongs. This tag is signed with
     * the secret-salt of the installation to guard against false positives.
     *
     * Parameters:
     * $recipient - (EmailContact|null) recipient of the message. The ID of
     *      the recipient is placed in the message id TAG section so it can
     *      be recovered if the email replied to directly by the end user.
     * $options - (array) - options passed to ::send(). If it includes a
     *      'thread' element, the threadId will be recorded in the TAG
     *
     * Returns:
     * (string) - email message id, with leading and trailing <> chars. See
     * the format below for the structure.
     *
     * Format:
     * VA-B-C-D, with dash separators and A-D explained below:
     *
     * V: Version code of the generated Message-Id
     * A: Predictable random code — used for loop detection
     * B: Random data for unique identifier
     *    Version Code: A (at char position 10)
     * C: TAG: Base64(Pack(userid, entryId, type)), = chars discarded
     * D: Signature:
     *   '@' + Signed Tag value, last 10 chars from
     *        HMAC(sha1, tag+rand, SECRET_SALT)
     *   -or- Original From email address
     */
    function getMessageId($recipient, $options=array(), $version='A') {
        $rand = Misc::randCode(9,
            // RFC822 specifies the LHS of the addr-spec can have any char
            // except the specials — ()<>@,;:\".[], dash is reserved as the
            // section separator, and + is reserved for historical reasons
            'abcdefghiklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_=');
        // Create a tag for the outbound email
        $tag = pack('VVa',
            ($recipient instanceof EmailContact) ? $recipient->getUserId() : 0,
            (isset($options['thread']) && $options['thread'] instanceof ThreadEntry)
                ? $options['thread']->getId() : 0,
            ($recipient instanceof Staff ? 'S'
                : ($recipient instanceof TicketOwner ? 'U'
                : ($recipient instanceof Collaborator ? 'C'
                : '?')))
        );
        $tag = str_replace('=','',base64_encode($tag));
        // Sign the tag with the system secret salt
        $sig = '@' . substr(hash_hmac('sha1', $tag.$rand, SECRET_SALT), -10);
        return sprintf('<A%s-%s-%s-%s>',
            static::getSystemMessageIdCode(),
            $rand, $tag, $sig);
    }

    /**
     * decodeMessageId
     *
     * Decodes a message-id generated by osTicket using the ::getMessageId()
     * method of this class. This will digest the received message-id token
     * and return an array with some information about it.
     *
     * Parameters:
     * $mid - (string) message-id from an email Message-Id, In-Reply-To, and
     *      References header.
     *
     * Returns:
     * (array) of information containing all or some of the following keys
     *      'loopback' - (bool) true or false if the message originated by
     *          this osTicket installation.
     *      'version' - (string|FALSE) version code of the message id
     *      'code' - (string) unique but predictable help desk message-id
     *      'id' - (string) random characters serving as the unique id
     *      'threadId' - (int) thread-id from which the message originated
     *      'staffId' - (int|null) staff the email was originally sent to
     *      'userId' - (int|null) user the email was originally sent to
     *      'userClass' - (string) class of user the email was sent to
     *          'U' - TicketOwner
     *          'S' - Staff
     *          'C' - Collborator
     *          '?' - Something else
     */
    static function decodeMessageId($mid) {
        // Drop <> tokens
        $mid = trim($mid, '<> ');
        // Drop email domain on rhs
        list($lhs, $sig) = explode('@', $mid, 2);
        // LHS should be tokenized by '-'
        $parts = explode('-', $lhs);

        $rv = array('loopback' => false, 'version' => false);

        // There should be at least two tokens if the message was sent by
        // this system. Otherwise, there's nothing to be detected
        if (count($parts) < 2)
            return $rv;

        // Detect the MessageId version, which should be the tenth char of
        // the second segment
        $rv['version'] = @$parts[0][0];
        switch ($rv['version']) {
            case 'A':
            default:
                list($rv['code'], $rv['id'], $tag) = $parts;
                // Drop the leading version code
                $rv['code'] = substr($rv['code'], 1);
                // Verify tag signature
                $chksig = substr(hash_hmac('sha1', $tag.$rv['id'], SECRET_SALT), -10);
                if ($tag && $sig == $chksig && ($tag = base64_decode($tag))) {
                    // Find user and ticket id
                    $rv += unpack('Vuid/VthreadId/auserClass', $tag);
                    // Attempt to make the user-id more specific
                    $classes = array(
                        'S' => 'staffId', 'U' => 'userId', 'C' => 'userId',
                    );
                    if (isset($classes[$rv['userClass']]))
                        $rv[$classes[$rv['userClass']]] = $rv['uid'];
                }
                // Round-trip detection - the first section is the local
                // system's message-id code
                $rv['loopback'] = (0 === strcasecmp($rv['code'],
                    static::getSystemMessageIdCode()));
                break;
        }
        return $rv;
    }

    static function getSystemMessageIdCode() {
        return substr(str_replace('+', '=',
            base64_encode(md5('mail'.SECRET_SALT, true))),
            0, 6);
    }

    function send($to, $subject, $message, $options=null) {
        global $ost, $cfg;

        //Get the goodies
        require_once (PEAR_DIR.'Mail.php'); // PEAR Mail package
        require_once (PEAR_DIR.'Mail/mime.php'); // PEAR Mail_Mime packge

        $messageId = $this->getMessageId($to, $options);

        if (is_object($to) && is_callable(array($to, 'getEmail'))) {
            // Add personal name if available
            if (is_callable(array($to, 'getName'))) {
                $to = sprintf('"%s" <%s>',
                    $to->getName()->getOriginal(), $to->getEmail()
                );
            }
            else {
                $to = $to->getEmail();
            }
        }

        //do some cleanup
        $to = preg_replace("/(\r\n|\r|\n)/s",'', trim($to));
        $subject = preg_replace("/(\r\n|\r|\n)/s",'', trim($subject));

        $headers = array (
            'From' => $this->getFromAddress(),
            'To' => $to,
            'Subject' => $subject,
            'Date'=> date('D, d M Y H:i:s O'),
            'Message-ID' => $messageId,
            'X-Mailer' =>'osTicket Mailer',
        );

        // Add in the options passed to the constructor
        $options = ($options ?: array()) + $this->options;

        if (isset($options['nobounce']) && $options['nobounce'])
            $headers['Return-Path'] = '<>';
        elseif ($this->getEmail() instanceof Email)
            $headers['Return-Path'] = $this->getEmail()->getEmail();

        //Bulk.
        if (isset($options['bulk']) && $options['bulk'])
            $headers+= array('Precedence' => 'bulk');

        //Auto-reply - mark as autoreply and supress all auto-replies
        if (isset($options['autoreply']) && $options['autoreply']) {
            $headers+= array(
                    'Precedence' => 'auto_reply',
                    'X-Autoreply' => 'yes',
                    'X-Auto-Response-Suppress' => 'DR, RN, OOF, AutoReply',
                    'Auto-Submitted' => 'auto-replied');
        }

        //Notice (sort of automated - but we don't want auto-replies back
        if (isset($options['notice']) && $options['notice'])
            $headers+= array(
                    'X-Auto-Response-Suppress' => 'OOF, AutoReply',
                    'Auto-Submitted' => 'auto-generated');

        if ($options) {
            if (isset($options['inreplyto']) && $options['inreplyto'])
                $headers += array('In-Reply-To' => $options['inreplyto']);
            if (isset($options['references']) && $options['references']) {
                if (is_array($options['references']))
                    $headers += array('References' =>
                        implode(' ', $options['references']));
                else
                    $headers += array('References' => $options['references']);
            }
        }

        // Make the best effort to add In-Reply-To and References headers
        $reply_tag = $mid_token = '';
        if (isset($options['thread'])
            && $options['thread'] instanceof ThreadEntry
        ) {
            $headers += array('References' => $options['thread']->getEmailReferences());
            if ($irt = $options['thread']->getEmailMessageId()) {
                // This is an response from an email, like and autoresponse.
                // Web posts will not have a email message-id
                $headers += array('In-Reply-To' => $irt);
            }
            elseif ($parent = $options['thread']->getParent()) {
                // Use the parent item as the email information source. This
                // will apply for staff replies
                $headers += array(
                    'In-Reply-To' => $parent->getEmailMessageId(),
                    'References' => $parent->getEmailReferences(),
                );
            }

            // Configure the reply tag and embedded message id token
            $mid_token = $options['thread']->asMessageId($to);
            if ($cfg && $cfg->stripQuotedReply()
                    && (!isset($options['reply-tag']) || $options['reply-tag']))
                $reply_tag = $cfg->getReplySeparator() . '<br/><br/>';
        }

        // Use general failsafe default initially
        $eol = "\n";

        // MAIL_EOL setting can be defined in `ost-config.php`
        if (defined('MAIL_EOL') && is_string(MAIL_EOL)) {
            $eol = MAIL_EOL;
        }
        $mime = new Mail_mime($eol);

        // If the message is not explicitly declared to be a text message,
        // then assume that it needs html processing to create a valid text
        // body
        $isHtml = true;
        if (!(isset($options['text']) && $options['text'])) {
            if ($reply_tag || $mid_token) {
                $message = "<div style=\"display:none\"
                    data-mid=\"$mid_token\">$reply_tag</div>$message";
            }
            $txtbody = rtrim(Format::html2text($message, 90, false))
                . ($mid_token ? "\nRef-Mid: $mid_token\n" : '');
            $mime->setTXTBody($txtbody);
        }
        else {
            $mime->setTXTBody($message);
            $isHtml = false;
        }

        if ($isHtml && $cfg && $cfg->isHtmlThreadEnabled()) {
            // Pick a domain compatible with pear Mail_Mime
            $matches = array();
            if (preg_match('#(@[0-9a-zA-Z\-\.]+)#', $this->getFromAddress(), $matches)) {
                $domain = $matches[1];
            } else {
                $domain = '@localhost';
            }
            // Format content-ids with the domain, and add the inline images
            // to the email attachment list
            $self = $this;
            $message = preg_replace_callback('/cid:([\w.-]{32})/',
                function($match) use ($domain, $mime, $self) {
                    if (!($file = AttachmentFile::lookup($match[1])))
                        return $match[0];
                    $mime->addHTMLImage($file->getData(),
                        $file->getType(), $file->getName(), false,
                        $match[1].$domain);
                    // Don't re-attach the image below
                    unset($self->attachments[$file->getId()]);
                    return $match[0].$domain;
                }, $message);
            // Add an HTML body
            $mime->setHTMLBody($message);
        }
        //XXX: Attachments
        if(($attachments=$this->getAttachments())) {
            foreach($attachments as $attachment) {
                if ($attachment['file_id']
                        && ($file=AttachmentFile::lookup($attachment['file_id']))) {
                    $mime->addAttachment($file->getData(),
                        $file->getType(), $file->getName(),false);
                }
            }
        }

        //Desired encodings...
        $encodings=array(
                'head_encoding' => 'quoted-printable',
                'text_encoding' => 'base64',
                'html_encoding' => 'base64',
                'html_charset'  => 'utf-8',
                'text_charset'  => 'utf-8',
                'head_charset'  => 'utf-8'
                );
        //encode the body
        $body = $mime->get($encodings);
        //encode the headers.
        $headers = $mime->headers($headers, true);

        // Cache smtp connections made during this request
        static $smtp_connections = array();
        if(($smtp=$this->getSMTPInfo())) { //Send via SMTP
            $key = sprintf("%s:%s:%s", $smtp['host'], $smtp['port'],
                $smtp['username']);
            if (!isset($smtp_connections[$key])) {
                $mail = mail::factory('smtp', array(
                    'host' => $smtp['host'],
                    'port' => $smtp['port'],
                    'auth' => $smtp['auth'],
                    'username' => $smtp['username'],
                    'password' => $smtp['password'],
                    'timeout'  => 20,
                    'debug' => false,
                    'persist' => true,
                ));
                if ($mail->connect())
                    $smtp_connections[$key] = $mail;
            }
            else {
                // Use persistent connection
                $mail = $smtp_connections[$key];
            }

            $result = $mail->send($to, $headers, $body);
            if(!PEAR::isError($result))
                return $messageId;

            // Force reconnect on next ->send()
            unset($smtp_connections[$key]);

            $alert=sprintf(__("Unable to email via SMTP:%1\$s:%2\$d [%3\$s]\n\n%4\$s\n"),
                    $smtp['host'], $smtp['port'], $smtp['username'], $result->getMessage());
            $this->logError($alert);
        }

        //No SMTP or it failed....use php's native mail function.
        $mail = mail::factory('mail');
        return PEAR::isError($mail->send($to, $headers, $body))?false:$messageId;

    }

    function logError($error) {
        global $ost;
        //NOTE: Admin alert override - don't email when having email trouble!
        $ost->logError(__('Mailer Error'), $error, false);
    }

    /******* Static functions ************/

    //Emails using native php mail function - if DB connection doesn't exist.
    //Don't use this function if you can help it.
    function sendmail($to, $subject, $message, $from) {
        $mailer = new Mailer(null, array('notice'=>true, 'nobounce'=>true));
        $mailer->setFromAddress($from);
        return $mailer->send($to, $subject, $message);
    }
}
?>
