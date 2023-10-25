<?php
/*********************************************************************
    class.mailer.php

    osTicket/Mail/Mailer

    Wrapper for sending emails via SMTP / SendMail

    Peter Rotich <peter@osticket.com>
    Copyright (c)  osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
namespace osTicket\Mail;

class Mailer {
    private $from = null;
    var $email = null;
    var $smtpAccounts = [];

    var $ht = array();
    var $attachments = array();
    var $options = array();
    var $eol="\n";

    function __construct(\Email $email=null, array $options=array()) {
        global $cfg;

        // Get all possible outgoing emails accounts (SMTP) to try
        if (($email instanceof \Email)
                && ($smtp=$email->getSmtpAccount(false))
                && $smtp->isActive()) {
            $this->smtpAccounts[$smtp->getId()] = $smtp;
        }
        if ($cfg)  {
            // Get Default MTA  (SMTP)
            if (($smtp=$cfg->getDefaultMTA()) && $smtp->isActive()) {
                $this->smtpAccounts[$smtp->getId()] = $smtp;
                // If email is not set then use Default MTA
                if (!$email)
                    $email = $smtp->getEmail();
            } elseif (!$email && $cfg && ($email=$cfg->getDefaultEmail())) {
                // as last resort we will send  via Default Email
                if (($smtp=$email->getSmtpAccount(false)) && $smtp->isActive())
                    $this->smtpAccounts[$smtp->getId()] = $smtp;
            }
        }

        $this->email = $email;
        $this->attachments = array();
        $this->options = $options;
        if (isset($this->options['eol']))
            $this->eol = $this->options['eol'];
        elseif (defined('MAIL_EOL') && is_string(MAIL_EOL))
            $this->eol = MAIL_EOL;
    }

    function getEOL() {
        return $this->eol;
    }

    function getSmtpAccounts() {
        return $this->smtpAccounts;
    }

    function getEmail() {
        return $this->email;
    }

    /* FROM Address */
    function setFromAddress($from=null, $name=null) {
        if ($from instanceof \EmailAddress)
            $this->from = $from;
        elseif (\Validator::is_email($from)) {
            $this->from = new \EmailAddress(
                    sprintf('"%s" <%s>', $name ?: '', $from));
        } elseif (is_string($from))
            $this->from = new \EmailAddress($from);
        elseif (($email=$this->getEmail())) {
            // we're assuming from was null or unexpected monster
            $address = sprintf('"%s" <%s>',
                    $name ?: $email->getName(),
                    $email->getEmail());
            $this->from = new \EmailAddress($address);
        }
    }

    function getFromAddress($options=array()) {
        if (!isset($this->from))
            $this->setFromAddress(null, $options['from_name'] ?: null);

        return $this->from;
    }

    function getFromName() {
        return $this->getFromAddress()->getName();
    }

    function getFromEmail() {
        return $this->getFromAddress()->getEmail();
    }

    /* attachments */
    function getAttachments() {
        return $this->attachments;
    }

    function addAttachment(\Attachment $attachment) {
        $this->attachments[$attachment->getFile()->getUId()] = $attachment;
    }

    function addAttachmentFile(\AttachmentFile $file) {
        $this->attachments[$file->getUId()] = $file;
    }

    function addFileObject(\FileObject $file) {
        $this->attachments[$file->getUId()] = $file;
    }

    function addAttachments($attachments) {
        foreach ($attachments as $a) {
            if ($a instanceof \Attachment)
                $this->addAttachment($a);
            elseif ($a instanceof \AttachmentFile)
                $this->addAttachmentFile($a);
            elseif ($a instanceof \FileObject)
                $this->addFileObject($a);
        }
    }

    /**
     *  lookup Attached File by Key
     */
    function getFile(String $key) {
        foreach ($this->getAttachments() as $uid => $F) {
            if ($F instanceof \Attachment)
                $F = $F->getFile();
            if (strcasecmp($F->getKey(), $key) === 0)
                return $F;
        }
        return \AttachmentFile::lookup($key);
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
     * (string) - email message id, without leading and trailing <> chars.
     * See the Format below for the structure.
     *
     * Format:
     * VA-B-C, with dash separators and A-C explained below:
     *
     * V: Version code of the generated Message-Id
     * A: Predictable random code — used for loop detection (sysid)
     * B: Random data for unique identifier (rand)
     * C: TAG: Base64(Pack(userid, entryId, threadId, type, Signature)),
     *    '=' chars discarded
     * where Signature is:
     *   Signed Tag value, last 5 chars from
     *        HMAC(sha1, Tag + rand + sysid, SECRET_SALT),
     *   where Tag is:
     *     pack(userId, entryId, threadId, type)
     */
    function getMessageId($recipient, $options=array(), $version='B') {
        $tag = '';
        $rand = \Misc::randCode(5,
            // RFC822 specifies the LHS of the addr-spec can have any char
            // except the specials — ()<>@,;:\".[], dash is reserved as the
            // section separator, and + is reserved for historical reasons
            'abcdefghiklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_=');
        $sig = $this->getEmail()?$this->getEmail()->getEmail():'@osTicketMailer';
        $sysid = static::getSystemMessageIdCode();
        // Create a tag for the outbound email
        $entry = (isset($options['thread'])
                && ($options['thread'] instanceof \ThreadEntry))
            ? $options['thread'] : false;
        $thread = $entry ? $entry->getThread()
            : (isset($options['thread'])
                    && ($options['thread'] instanceof \Thread)
                ? $options['thread'] : false);

        switch (true) {
        case $recipient instanceof \Staff:
            $utype = 'S';
            break;
        case $recipient instanceof \TicketOwner:
            $utype = 'U';
            break;
        case $recipient instanceof \Collaborator:
            $utype = 'C';
            break;
        case  $recipient instanceof \MailingList:
            $utype = 'M';
            break;
        default:
            $utype = ($options['utype'] ?: is_array($recipient)) ? 'M' : '?';
        }


        $tag = pack('VVVa',
            $recipient instanceof \EmailContact ? $recipient->getUserId() : 0,
            $entry ? $entry->getId() : 0,
            $thread ? $thread->getId() : 0,
            $utype ?: '?'
        );
        // Sign the tag with the system secret salt
        $tag .= substr(hash_hmac('sha1', $tag.$rand.$sysid, SECRET_SALT, true), -5);
        $tag = str_replace('=','',base64_encode($tag));
        return sprintf('B%s-%s-%s-%s',
            $sysid, $rand, $tag, $sig);
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
     *      'entryId' - (int) thread-entry-id from which the message originated
     *      'threadId' - (int) thread-id from which the message originated
     *      'staffId' - (int|null) staff the email was originally sent to
     *      'userId' - (int|null) user the email was originally sent to
     *      'userClass' - (string) class of user the email was sent to
     *          'U' - TicketOwner
     *          'S' - Staff
     *          'C' - Collborator
     *          'M' - Multiple
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

        $self = get_called_class();
        $decoders = array(
        'A' => function($id, $tag) use ($sig) {
            // Old format was VA-B-C-D@sig, where C was the packed tag and D
            // was blank
            $format = 'Vuid/VentryId/auserClass';
            $chksig = substr(hash_hmac('sha1', $tag.$id, SECRET_SALT), -10);
            if ($tag && $sig == $chksig && ($tag = base64_decode($tag))) {
                // Find user and ticket id
                return unpack($format, $tag);
            }
            return false;
        },
        'B' => function($id, $tag) use ($self) {
            $format = 'Vuid/VentryId/VthreadId/auserClass/a*sig';
            if ($tag && ($tag = base64_decode($tag))) {
                if (!($info = @unpack($format, $tag)) || !isset($info['sig']))
                    return false;
                $sysid = $self::getSystemMessageIdCode();
                $shorttag = substr($tag, 0, 13);
                $chksig = substr(hash_hmac('sha1', $shorttag.$id.$sysid,
                    SECRET_SALT, true), -5);
                if ($chksig == $info['sig']) {
                    return $info;
                }
            }
            return false;
        },
        );

        // Detect the MessageId version, which should be the first char
        $rv['version'] = @$parts[0][0];
        if (!isset($decoders[$rv['version']]))
            // invalid version code
            return null;

        // Drop the leading version code
        list($rv['code'], $rv['id'], $tag) = $parts;
        $rv['code'] = substr($rv['code'], 1);

        // Verify tag signature and unpack the tag
        $info = $decoders[$rv['version']]($rv['id'], $tag);
        if ($info === false)
            return $rv;

        $rv += $info;

        // Attempt to make the user-id more specific
        $classes = array(
            'S' => 'staffId', 'U' => 'userId', 'C' => 'userId',
        );
        if (isset($classes[$rv['userClass']]))
            $rv[$classes[$rv['userClass']]] = $rv['uid'];

        // Round-trip detection - the first section is the local
        // system's message-id code
        $rv['loopback'] = (0 === strcmp($rv['code'],
            static::getSystemMessageIdCode()));

        return $rv;
    }

    static function getSystemMessageIdCode() {
        return substr(str_replace('+', '=',
            base64_encode(md5('mail'.SECRET_SALT, true))),
            0, 6);
    }

    function send($recipients, $subject, $body, $options=null) {
        global $ost, $cfg;

        $messageId = $this->getMessageId($recipients, $options);
        $subject = preg_replace("/(\r\n|\r|\n)/s",'', trim($subject));
        $from = $this->getFromAddress($options);

         // Create new ostTicket/Mail/Message object
        $message = new Message();
        // Set our custom Message-Id
        $message->setMessageId($messageId);
        // Set From Address
        $message->setFrom($from->getEmail(), $from->getName());
        // Set Subject
        $message->setSubject($subject);

        // Collect Generic Headers
        $headers = ['X-Mailer' =>'osTicket Mailer'];

        // Add in the options passed to the constructor
        $options = ($options ?: array()) + $this->options;
        // Message Id Token
        $mid_token = '';
        // Check if the email is threadable
        if (isset($options['thread'])
            && ($options['thread'] instanceof \ThreadEntry)
            && ($thread = $options['thread']->getThread())) {

            // Add email in-reply-to references if not set
            if (!isset($options['inreplyto'])) {

                $entry = null;
                switch (true) {
                case $recipients instanceof \MailingList:
                    $entry = $thread->getLastEmailMessage();
                    break;
                case $recipients instanceof \TicketOwner:
                case $recipients instanceof \Collaborator:
                    $entry = $thread->getLastEmailMessage(array(
                                'user_id' => $recipients->getUserId()));
                    break;
                case $recipients instanceof \Staff:
                    //XXX: is it necessary ??
                    break;
                }

                if ($entry && ($mid=$entry->getEmailMessageId())) {
                    $options['inreplyto'] = $mid;
                    $options['references'] = $entry->getEmailReferences();
                }
            }

            // Embedded message id token
            $mid_token = $messageId;
            // Set Reply-Tag
            if (!isset($options['reply-tag'])) {
                if ($cfg && $cfg->stripQuotedReply())
                    $options['reply-tag'] = $cfg->getReplySeparator() . '<br/><br/>';
                else
                    $options['reply-tag'] = '';
            } elseif ($options['reply-tag'] === false) {
                $options['reply-tag'] = '';
            }
        }

        // Return-Path
        if (isset($options['nobounce']) && $options['nobounce'])
            $message->setReturnPath('<>');
        elseif ($this->getEmail() instanceof \Email)
            $message->setReturnPath($this->getEmail()->getEmail());

        // Bulk.
        if (isset($options['bulk']) && $options['bulk'])
            $headers+= array('Precedence' => 'bulk');

        // Auto-reply - mark as autoreply and supress all auto-replies
        if (isset($options['autoreply']) && $options['autoreply']) {
            $headers+= array(
                    'Precedence' => 'auto_reply',
                    'X-Autoreply' => 'yes',
                    'X-Auto-Response-Suppress' => 'DR, RN, OOF, AutoReply',
                    'Auto-Submitted' => 'auto-replied');
        }

        // Notice (sort of automated - but we don't want auto-replies back
        if (isset($options['notice']) && $options['notice'])
            $headers+= array(
                    'X-Auto-Response-Suppress' => 'OOF, AutoReply',
                    'Auto-Submitted' => 'auto-generated');
        // In-Reply-To
        if (isset($options['inreplyto']) && $options['inreplyto'])
            $message->addInReplyTo($options['inreplyto']);

        // References
        if (isset($options['references']) && $options['references'])
            $message->addReferences($options['references']);

        // Add Headers
        $message->addHeaders($headers);

        // Add recipients
        if (!is_array($recipients) && (!$recipients instanceof \MailingList))
            $recipients =  array($recipients);
        foreach ($recipients as $recipient) {
            if ($recipient instanceof \ClientSession)
                $recipient = $recipient->getSessionUser();
            try {
                switch (true) {
                    case $recipient instanceof \EmailRecipient:
                        $email = (string) $recipient->getEmail()->getEmail();
                        $name =  (string) $recipient->getName();
                        switch ($recipient->getType()) {
                            case 'to':
                                $message->addTo($email, $name);
                                break;
                            case 'cc':
                                $message->addCc($email, $name);
                                break;
                            case 'bcc':
                                $message->addBcc($email, $name);
                                break;
                        }
                        break;
                    case $recipient instanceof \TicketOwner:
                    case $recipient instanceof \Staff:
                        $message->addTo((string) $recipient->getEmail(),
                                (string) $recipient->getName());
                        break;
                    case $recipient instanceof \Collaborator:
                        $message->addCc((string) $recipient->getEmail(),
                                 (string) $recipient->getName());
                        break;
                    case $recipient instanceof \EmailAddress:
                        $message->addTo((string) $recipient->getEmail(),
                                (string) $recipient->getName());
                        break;
                    default:
                        // Assuming email address.
                        if (is_string($recipient))
                            $message->addTo($recipient);
                }
            } catch(\Exception $ex) {
                $this->logWarning(sprintf("%s1\$s: %2\$s\n\n%3\$s\n",
                        _S("Unable to add email recipient"),
                        ($recipient instanceof EmailContact)
                            ? $recipient->getEmailAddress()
                            : (string) $recipient,
                        $ex->getMessage()
                    ));
            }
        }

        // Add in extra attachments, if any from template variables
        if ($body instanceof \TextWithExtras
            && ($attachments = $body->getAttachments())) {
            foreach ($attachments as $a) {
                $message->addAttachment($a->getFile());
            }
        }

        // If the message is not explicitly declared to be a text message,
        // then assume that it needs html processing to create a valid text
        // body
        $isHtml = true;
        if (!(isset($options['text']) && $options['text'])) {
            // Embed the data-mid in such a way that it should be included
            // in a response
            if ($options['reply-tag'] || $mid_token) {
                $body = sprintf('<div style="display:none"
                        class="mid-%s">%s</div>%s',
                        $mid_token,
                        $options['reply-tag'],
                        $body);
            }

            $txtbody = rtrim(\Format::html2text($body, 90, false))
                . ($messageId ? "\nRef-Mid: $messageId\n" : '');
            $message->setTextBody($txtbody);
        }
        else {
            $message->setTextBody($body);
            $isHtml = false;
        }

        if ($isHtml && $cfg && $cfg->isRichTextEnabled()) {
            // Pick a domain compatible with pear Mail_Mime
            $matches = array();
            if (preg_match('#(@[0-9a-zA-Z\-\.]+)#', (string) $this->getFromAddress(), $matches)) {
                $domain = $matches[1];
            } else {
                $domain = '@localhost';
            }
            // Format content-ids with the domain, and add the inline images
            // to the email attachment list
            $self = $this;
            $body = preg_replace_callback('/cid:([\w.-]{32})/',
                function($match) use ($domain, $message, $self) {
                    if (!($file=$self->getFile($match[1])))
                        return $match[0];

                    try {
                        $message->addInlineImage($match[1].$domain, $file);
                        // Don't re-attach the image below
                        unset($self->attachments[$file->getUId()]);
                        return $match[0].$domain;
                    }  catch(\Exception $ex) {
                         $self->logWarning(sprintf("%1\$s:%2\$s\n\n%3\$s\n",
                                     _S("Unable to retrieve email inline image"),
                                     $match[1].$domain,
                                     $ex->getMessage()));
                    }
                }, $body);
            // Add an HTML body
            $message->setHtmlBody($body);
        }
        //XXX: Attachments
        if(($attachments=$this->getAttachments())) {
            foreach($attachments as $file) {
                if ($file instanceof \Attachment) {
                    $filename = $file->getFilename();
                    $file = $file->getFile();
                } else {
                    $filename = $file->getName();
                }

                try {
                    $message->addAttachment($file, $filename);
                } catch(\Exception $ex) {
                    $this->logWarning(sprintf("%1\$s:%2\$s\n\n%3\$s\n",
                                     _S("Unable to retrieve email attachment"),
                                     $filename,
                                     $ex->getMessage()));
                }
            }
        }

        // Try possible SMTP Accounts - connections are cached per request
        // at the account level.
        foreach ($this->getSmtpAccounts() ?: [] as $smtpAccount) {
            try {
                //  Check if SPOOFING is allowed.
                if (!$smtpAccount->allowSpoofing()
                        && strcasecmp($smtpAccount->getEmail()->getEmail(),
                            $this->getFromEmail())) {
                    // If the Account DOES NOT allow spoofing then set the
                    // Sender as the smtp account sending out the email
                    // TODO: Allow Aliases to Spoof parent account by
                    // default.
                    $message->setSender(
                            // get Account Email
                            (string) $smtpAccount->getEmail()->getEmail(),
                            // Try to keep the name if available
                            $this->getFromName() ?: $smtpAccount->getName() ?: $this->getEmail());
                }
                // Attempt to send the Message.
                if (($smtp=$smtpAccount->getSmtpConnection())
                        && $smtp->sendMessage($message))
                     return $message->getId();
            } catch (\Exception $ex) {
                // Log the SMTP error
                $this->logError(sprintf("%1\$s: %2\$s (%3\$s)\n\n%4\$s\n",
                        _S("Unable to email via SMTP"),
                        $smtpAccount->getEmail()->getEmail(),
                        $smtpAccount->getHostInfo(),
                        $ex->getMessage()
                    ));
            }
            // Attempt  Failed:  Reset FROM to original email and clear Sender
            $message->setOriginator($this->getFromEmail(), $this->getFromName());
        }

        // No SMTP or it FAILED....use Sendmail transport (PHP mail())
        // Set Sender / Originator
        if (isset($options['from_address'])) {
            // This is often set via Mailer::sendmail()
            $message->setSender($options['from_address']);
        } elseif (($from=$this->getFromAddress())) {
            // This should be already set but we're making doubly sure
            $message->setOriginator($from->getEmail(), $from->getName());
        }

        try {
            // ostTicket/Mail/Sendmail transport (mail())
            // Set extra params as needed
            // NOTE: Laminas Mail doesn't respect -f param set Sender (above)
            $args = [];
            $sendmail =  new  Sendmail($args);
            if ($sendmail->sendMessage($message))
                return $message->getId();
        } catch (\Exception $ex) {
            $this->logError(sprintf("%1\$s\n\n%2\$s\n",
                        _S("Unable to email via Sendmail"),
                        $ex->getMessage()
                ));
        }
        return false;
    }

    function log($msg, $type='warning') {
        global $ost;

        if (!$ost || !$msg)
            return;

        // No email alerts on email errors
        switch ($type) {
            case 'error':
                return $ost->logError(_S('Mailer Error'), $msg, false);
                break;
            case 'warning':
            default:
                return $ost->logWarning(_S('Mailer Warning'), $msg, false);
        }
        return false;
    }

    function logError($error) {
        return $this->log($error, 'error');
    }

    function logWarning($warning) {
        return $this->log($warning, 'warning');
    }

    //Emails using native php mail function - if DB connection doesn't exist.
    //Don't use this function if you can help it.
    static function sendmail($to, $subject, $message, $from=null, $options=null) {
        $mailer = new Mailer(null, array('notice'=>true, 'nobounce'=>true));
        $mailer->setFromAddress($from, $options['from_name'] ?: null);
        return $mailer->send($to, $subject, $message, $options);
    }
}
