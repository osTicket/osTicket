<?php
/*********************************************************************
    class.thread.php

    Thread of things!
    XXX: Please DO NOT add any ticket related logic! use ticket class.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
include_once(INCLUDE_DIR.'class.ticket.php');
include_once(INCLUDE_DIR.'class.draft.php');

//Ticket thread.
class Thread {

    var $ht;

    function Thread($id) {
        $this->load($id);
    }

    function load($id=0) {

        if (!$id && !($id=$this->getId()))
            return null;

        $sql='SELECT thread.* '
            .' ,count(DISTINCT attach.id) as attachments '
            .' ,count(DISTINCT entry.id) as entries '
            .' FROM '.THREAD_TABLE.' thread '
            .' LEFT JOIN '.THREAD_ENTRY_TABLE.' entry
                ON (entry.thread_id = thread.id) '
            .' LEFT JOIN '.THREAD_ENTRY_ATTACHMENT_TABLE.' attach
                ON (attach.thread_entry_id=entry.id) '
            .' WHERE thread.id='.db_input($id)
            .' GROUP BY thread.id';

        $this->ht = array();
        if (($res=db_query($sql)) && db_num_rows($res))
            $this->ht = db_fetch_array($res);

        return ($this->ht);
    }

    function reload() {
        return $this->load();
    }

    function getId() {
        return $this->ht['id'];
    }

    function getObjectId() {
        return $this->ht['object_id'];
    }

    function getObjectType() {
        return $this->ht['object_type'];
    }

    function getObject() {

        if (!$this->_object)
            $this->_object = ObjectModel::lookup(
                    $this->getObjectId(), $this->getObjectType());

        return $this->_object;
    }

    function getNumAttachments() {
        return $this->ht['attachments'];
    }

    function getNumEntries() {
        return $this->ht['entries'];
    }

    function getEntries($type, $order='ASC') {

        if (!$order || !in_array($order, array('DESC','ASC')))
            $order='ASC';

        $sql='SELECT entry.*
               , COALESCE(user.name,
                    IF(staff.staff_id,
                        CONCAT_WS(" ", staff.firstname, staff.lastname),
                        NULL)) as name '
            .' ,count(DISTINCT attach.id) as attachments '
            .' FROM '.THREAD_ENTRY_TABLE.' entry '
            .' LEFT JOIN '.USER_TABLE.' user
                ON (entry.user_id=user.id) '
            .' LEFT JOIN '.STAFF_TABLE.' staff
                ON (entry.staff_id=staff.staff_id) '
            .' LEFT JOIN '.THREAD_ENTRY_ATTACHMENT_TABLE.' attach
                ON (attach.thread_entry_id = entry.id) '
            .' WHERE  entry.thread_id='.db_input($this->getId());

        if($type && is_array($type))
            $sql.=' AND entry.`type` IN ('.implode(',', db_input($type)).')';
        elseif($type)
            $sql.=' AND entry.`type` = '.db_input($type);

        $sql.=' GROUP BY entry.id '
             .' ORDER BY entry.created '.$order;

        $entries = array();
        if(($res=db_query($sql)) && db_num_rows($res)) {
            while($rec=db_fetch_array($res)) {
                $rec['body'] = ThreadEntryBody::fromFormattedText($rec['body'], $rec['format']);
                $entries[] = $rec;
            }
        }

        return $entries;
    }

    function getEntry($id) {
        return ThreadEntry::lookup($id, $this->getId());
    }


    function deleteAttachments() {

        // Clear reference table
        $sql = 'DELETE FROM '.THREAD_ENTRY_ATTACHMENT_TABLE. ' a '
             . 'INNER JOIN '.THREAD_ENTRY_TABLE.' e
                    ON(e.id = a.thread_entry_id) '
             . ' WHERE e.thread_id='.db_input($this->getId());

        $deleted=0;
        if (($res=db_query($sql)) && ($deleted=db_affected_rows()))
            AttachmentFile::deleteOrphans();

        return $deleted;
    }

    function delete() {

        //Self delete
        $sql = 'DELETE FROM '.THREAD_TABLE.' WHERE
            id='.db_input($this->getId());

        if (!db_query($sql) || !db_affected_rows())
            return false;

        // Clear email meta data (header..etc)
        $sql = 'UPDATE '.THREAD_ENTRY_EMAIL_TABLE.' email '
             . 'INNER JOIN '.THREAD_ENTRY_TABLE.' entry
                    ON (entry.id = email.thread_entry_id) '
             . 'SET email.headers = null '
             . 'WHERE entry.thread_id = '.db_input($this->getId());
        db_query($sql);

        // Mass delete entries
        $this->deleteAttachments();
        $sql = 'DELETE FROM '.THREAD_ENTRY_TABLE
             . ' WHERE thread_id='.db_input($this->getId());
        db_query($sql);

        return true;
    }

    function getVar($name) {
        switch ($name) {
        case 'original':
            return Message::firstByTicketId($this->ticket->getId())
                ->getBody();
            break;
        case 'last_message':
        case 'lastmessage':
            return $this->ticket->getLastMessage()->getBody();
            break;
        }
    }

    static function create($vars) {

        if (!$vars || !$vars['object_id'] || !$vars['object_type'])
            return false;

        $sql = 'INSERT INTO '.THREAD_TABLE.' SET created=NOW() '
              .', object_id='.db_input($vars['object_id'])
              .', object_type='.db_input($vars['object_type']);

        return db_query($sql) ? db_insert_id() : 0;
    }

    static function lookup($id) {

        return ($id
                && ($thread = new Thread($id))
                && $thread->getId()
                )
            ? $thread : null;
    }
}


Class ThreadEntry {

    var $id;
    var $ht;

    var $thread;
    var $attachments;

    function ThreadEntry($id, $threadId=0, $type='') {
        $this->load($id, $threadId, $type);
    }

    function load($id=0, $threadId=0, $type='') {

        if (!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT entry.*, email.mid, email.headers '
            .' ,count(DISTINCT attach.id) as attachments '
            .' FROM '.THREAD_ENTRY_TABLE.' entry '
            .' LEFT JOIN '.THREAD_ENTRY_EMAIL_TABLE.' email
                ON (email.thread_entry_id=entry.id) '
            .' LEFT JOIN '.THREAD_ENTRY_ATTACHMENT_TABLE.' attach
                ON (attach.thread_entry_id=entry.id) '
            .' WHERE  entry.id='.db_input($id);

        if ($type)
            $sql.=' AND entry.type='.db_input($type);

        if ($threadId)
            $sql.=' AND entry.thread_id='.db_input($threadId);

        $sql.=' GROUP BY entry.id ';

        if (!($res=db_query($sql)) || !db_num_rows($res))
            return false;

        $this->ht = db_fetch_array($res);
        $this->id = $this->ht['id'];

        $this->attachments = array();

        return true;
    }

    function reload() {
        return $this->load();
    }

    function getId() {
        return $this->id;
    }

    function getPid() {
        return $this->ht['pid'];
    }

    function getType() {
        return $this->ht['type'];
    }

    function getSource() {
        return $this->ht['source'];
    }

    function getPoster() {
        return $this->ht['poster'];
    }

    function getTitle() {
        return $this->ht['title'];
    }

    function getBody() {
        return ThreadEntryBody::fromFormattedText($this->ht['body'], $this->ht['format']);
    }

    function setBody($body) {
        global $cfg;

        if (!$body instanceof ThreadEntryBody) {
            if ($cfg->isHtmlThreadEnabled())
                $body = new HtmlThreadEntryBody($body);
            else
                $body = new TextThreadEntryBody($body);
        }

        $sql='UPDATE '.THREAD_ENTRY_TABLE.' SET updated=NOW()'
            .',format='.db_input($body->getType())
            .',body='.db_input((string) $body)
            .' WHERE id='.db_input($this->getId());
        return db_query($sql) && db_affected_rows();
    }

    function getCreateDate() {
        return $this->ht['created'];
    }

    function getUpdateDate() {
        return $this->ht['updated'];
    }

    function getNumAttachments() {
        return $this->ht['attachments'];
    }

    function getEmailMessageId() {
        return $this->ht['mid'];
    }

    function getEmailHeaderArray() {
        require_once(INCLUDE_DIR.'class.mailparse.php');

        if (!isset($this->ht['@headers']))
            $this->ht['@headers'] = Mail_Parse::splitHeaders($this->ht['headers']);

        return $this->ht['@headers'];
    }

    function getEmailReferences($include_mid=true) {
        $references = '';
        $headers = self::getEmailHeaderArray();
        if (isset($headers['References']) && $headers['References'])
            $references = $headers['References']." ";
        if ($include_mid)
            $references .= $this->getEmailMessageId();
        return $references;
    }

    function getTaggedEmailReferences($prefix, $refId) {

        $ref = "+$prefix".Base32::encode(pack('VV', $this->getId(), $refId));

        $mid = substr_replace($this->getEmailMessageId(),
                $ref, strpos($this->getEmailMessageId(), '@'), 0);

        return sprintf('%s %s', $this->getEmailReferences(false), $mid);
    }

    function getEmailReferencesForUser($user) {
        return $this->getTaggedEmailReferences('u',
            ($user instanceof Collaborator)
                ? $user->getUserId()
                : $user->getId());
    }

    function getEmailReferencesForStaff($staff) {
        return $this->getTaggedEmailReferences('s', $staff->getId());
    }

    function getUIDFromEmailReference($ref) {

        $info = unpack('Vtid/Vuid',
                Base32::decode(strtolower(substr($ref, -13))));

        if ($info && $info['tid'] == $this->getId())
            return $info['uid'];

    }

    function getThreadId() {
        return $this->ht['thread_id'];
    }

    function getThread() {

        if(!$this->thread && $this->getThreadId())
            $this->thread = Thread::lookup($this->getThreadId());

        return $this->thread;
    }

    function getStaffId() {
        return $this->ht['staff_id'];
    }

    function getStaff() {

        if(!$this->staff && $this->getStaffId())
            $this->staff = Staff::lookup($this->getStaffId());

        return $this->staff;
    }

    function getUserId() {
        return $this->ht['user_id'];
    }

    function getUser() {

        if (!isset($this->user)) {
            if (!($ticket = $this->getTicket()))
                return null;

            if ($ticket->getOwnerId() == $this->getUserId())
                $this->user = new TicketOwner(
                    User::lookup($this->getUserId()), $ticket);
            else
                $this->user = Collaborator::lookup(array(
                    'userId'=>$this->getUserId(), 'ticketId'=>$this->getTicketId()));
        }

        return $this->user;
    }

    function getEmailHeader() {
        return $this->ht['headers'];
    }

    function isAutoReply() {

        if (!isset($this->is_autoreply))
            $this->is_autoreply = $this->getEmailHeaderArray()
                ?  TicketFilter::isAutoReply($this->getEmailHeaderArray()) : false;

        return $this->is_autoreply;
    }

    function isBounce() {

        if (!isset($this->is_bounce))
            $this->is_bounce = $this->getEmailHeaderArray()
                ? TicketFilter::isBounce($this->getEmailHeaderArray()) : false;

        return $this->is_bounce;
    }

    function isBounceOrAutoReply() {
        return ($this->isAutoReply() || $this->isBounce());
    }

    //Web uploads - caller is expected to format, validate and set any errors.
    function uploadFiles($files) {

        if(!$files || !is_array($files))
            return false;

        $uploaded=array();
        foreach($files as $file) {
            if($file['error'] && $file['error']==UPLOAD_ERR_NO_FILE)
                continue;

            if(!$file['error']
                    && ($id=AttachmentFile::upload($file))
                    && $this->saveAttachment($id))
                $uploaded[]=$id;
            else {
                if(!$file['error'])
                    $error = sprintf(__('Unable to upload file - %s'),$file['name']);
                elseif(is_numeric($file['error']))
                    $error ='Error #'.$file['error']; //TODO: Transplate to string.
                else
                    $error = $file['error'];
                /*
                 Log the error as an internal note.
                 XXX: We're doing it here because it will eventually become a thread post comment (hint: comments coming!)
                 XXX: logNote must watch for possible loops
               */
                $this->getTicket()->logNote(__('File Upload Error'), $error, 'SYSTEM', false);
            }

        }

        return $uploaded;
    }

    function importAttachments(&$attachments) {

        if(!$attachments || !is_array($attachments))
            return null;

        $files = array();
        foreach($attachments as &$attachment)
            if(($id=$this->importAttachment($attachment)))
                $files[] = $id;

        return $files;
    }

    /* Emailed & API attachments handler */
    function importAttachment(&$attachment) {

        if(!$attachment || !is_array($attachment))
            return null;

        $id=0;
        if ($attachment['error'] || !($id=$this->saveAttachment($attachment))) {
            $error = $attachment['error'];

            if(!$error)
                $error = sprintf(_S('Unable to import attachment - %s'),$attachment['name']);

            $this->getTicket()->logNote(_S('File Import Error'), $error,
                _S('SYSTEM'), false);
        }

        return $id;
    }

   /*
    Save attachment to the DB.
    @file is a mixed var - can be ID or file hashtable.
    */
    function saveAttachment(&$file) {

        if (is_numeric($file))
            $fileId = $file;
        elseif (is_array($file) && isset($file['id']))
            $fileId = $file['id'];
        elseif (!($fileId = AttachmentFile::save($file)))
            return 0;

        $inline = is_array($file) && @$file['inline'];

        // TODO: Add a unique index to THREAD_ENTRY_ATTACHMENT_TABLE (file_id,
        // thread_entry_id), and remove this block
        if ($id = db_result(db_query('SELECT id FROM '.THREAD_ENTRY_ATTACHMENT_TABLE
                .' WHERE file_id='.db_input($fileId)
                .' AND thread_entry_id=' .db_input($this->getId()))))

            return $id;

        $sql ='INSERT IGNORE INTO '.THREAD_ENTRY_ATTACHMENT_TABLE.' SET created=NOW() '
             .' ,file_id='.db_input($fileId)
             .' ,thread_entry_id='.db_input($this->getId())
             .' ,inline='.db_input($inline ? 1 : 0);

        return (db_query($sql) && ($id=db_insert_id()))?$id:0;
    }

    function saveAttachments($files) {
        $ids=array();
        foreach ($files as $file)
           if (($id=$this->saveAttachment($file)))
               $ids[] = $id;

        return $ids;
    }

    function getAttachments() {

        if ($this->attachments)
            return $this->attachments;

        //XXX: inner join the file table instead?
        $sql='SELECT a.id, f.id as file_id, f.size, lower(f.`key`) as file_hash, f.name, a.inline '
            .' FROM '.FILE_TABLE.' f '
            .' INNER JOIN '.THREAD_ENTRY_ATTACHMENT_TABLE.' a
                ON(a.file_id=f.id) '
            .' WHERE a.thread_entry_id='.db_input($this->getId());

        $this->attachments = array();
        if (($res=db_query($sql)) && db_num_rows($res)) {
            while ($rec=db_fetch_array($res))
                $this->attachments[] = $rec;
        }

        return $this->attachments;
    }

    function getAttachmentUrls($script='image.php') {
        $json = array();
        foreach ($this->getAttachments() as $att) {
            $json[$att['file_hash']] = array(
                'download_url' => sprintf('attachment.php?id=%d&h=%s', $att['id'],
                    strtolower(md5($att['file_id'].session_id().$att['file_hash']))),
                'filename' => $att['name'],
            );
        }
        return $json;
    }

    function getAttachmentsLinks($file='attachment.php', $target='', $separator=' ') {

        $str='';
        foreach($this->getAttachments() as $attachment ) {
            if ($attachment['inline'])
                continue;
            /* The hash can be changed  but must match validation in @file */
            $hash=md5($attachment['file_id'].session_id().$attachment['file_hash']);
            $size = '';
            if($attachment['size'])
                $size=sprintf('<em>(%s)</em>', Format::file_size($attachment['size']));

            $str.=sprintf('<a class="Icon file no-pjax" href="%s?id=%d&h=%s" target="%s">%s</a>%s&nbsp;%s',
                    $file, $attachment['id'], $hash, $target, Format::htmlchars($attachment['name']), $size, $separator);
        }

        return $str;
    }
    /**
     * postEmail
     *
     * After some security and sanity checks, attaches the body and subject
     * of the message in reply to this thread item
     *
     * Parameters:
     * mailinfo - (array) of information about the email, with at least the
     *          following keys
     *      - mid - (string) email message-id
     *      - name - (string) personal name of email originator
     *      - email - (string<email>) originating email address
     *      - subject - (string) email subject line (decoded)
     *      - body - (string) email message body (decoded)
     */
    function postEmail($mailinfo) {
        global $ost;

        // +==================+===================+=============+
        // | Orig Thread-Type | Reply Thread-Type | Requires    |
        // +==================+===================+=============+
        // | *                | Message (M)       | From: Owner |
        // | *                | Note (N)          | From: Staff |
        // | Response (R)     | Message (M)       |             |
        // | Message (M)      | Response (R)      | From: Staff |
        // +------------------+-------------------+-------------+

        if (!$ticket = $this->getTicket())
            // Kind of hard to continue a discussion without a ticket ...
            return false;

        // Make sure the email is NOT already fetched... (undeleted emails)
        elseif ($this->getEmailMessageId() == $mailinfo['mid'])
            // Reporting success so the email can be moved or deleted.
            return true;

        // Mail sent by this system will have a message-id format of
        // <code-random-mailbox@domain.tld>
        // where code is a predictable string based on the SECRET_SALT of
        // this osTicket installation. If this incoming mail matches the
        // code, then it very likely originated from this system and looped
        @list($code) = explode('-', $mailinfo['mid'], 2);
        if (0 === strcasecmp(ltrim($code, '<'), substr(md5('mail'.SECRET_SALT), -9))) {
            // This mail was sent by this system. It was received due to
            // some kind of mail delivery loop. It should not be considered
            // a response to an existing thread entry
            if ($ost) $ost->log(LOG_ERR, _S('Email loop detected'), sprintf(
                _S('It appears as though &lt;%s&gt; is being used as a forwarded or fetched email account and is also being used as a user / system account. Please correct the loop or seek technical assistance.'),
                $mailinfo['email']),

                // This is quite intentional -- don't continue the loop
                false,
                // Force the message, even if logging is disabled
                true);
            return true;
        }

        $vars = array(
            'mid' =>    $mailinfo['mid'],
            'header' => $mailinfo['header'],
            'ticketId' => $ticket->getId(),
            'poster' => $mailinfo['name'],
            'origin' => 'Email',
            'source' => 'Email',
            'ip' =>     '',
            'reply_to' => $this,
            'recipients' => $mailinfo['recipients'],
            'to-email-id' => $mailinfo['to-email-id'],
        );
        $errors = array();

        if (isset($mailinfo['attachments']))
            $vars['attachments'] = $mailinfo['attachments'];

        $body = $mailinfo['message'];

        // Disambiguate if the user happens also to be a staff member of the
        // system. The current ticket owner should _always_ post messages
        // instead of notes or responses
        if ($mailinfo['userId']
                || strcasecmp($mailinfo['email'], $ticket->getEmail()) == 0) {
            $vars['message'] = $body;
            $vars['userId'] = $mailinfo['userId'] ? $mailinfo['userId'] : $ticket->getUserId();
            return $ticket->postMessage($vars, 'Email');
        }
        // XXX: Consider collaborator role
        elseif ($mailinfo['staffId']
                || ($mailinfo['staffId'] = Staff::getIdByEmail($mailinfo['email']))) {
            $vars['staffId'] = $mailinfo['staffId'];
            $poster = Staff::lookup($mailinfo['staffId']);
            $vars['note'] = $body;
            return $ticket->postNote($vars, $errors, $poster);
        }
        elseif (Email::getIdByEmail($mailinfo['email'])) {
            // Don't process the email -- it came FROM this system
            return true;
        }
        // Support the mail parsing system declaring a thread-type
        elseif (isset($mailinfo['thread-type'])) {
            switch ($mailinfo['thread-type']) {
            case 'N':
                $vars['note'] = $body;
                $poster = $mailinfo['email'];
                return $ticket->postNote($vars, $errors, $poster);
            }
        }
        // TODO: Consider security constraints
        else {
            //XXX: Are we potentially leaking the email address to
            // collaborators?
            $vars['message'] = sprintf("Received From: %s\n\n%s",
                $mailinfo['email'], $body);
            $vars['userId'] = 0; //Unknown user! //XXX: Assume ticket owner?
            return $ticket->postMessage($vars, 'Email');
        }
        // Currently impossible, but indicate that this thread object could
        // not append the incoming email.
        return false;
    }

    /* Returns file names with id as key */
    function getFiles() {

        $files = array();
        foreach($this->getAttachments() as $attachment)
            $files[$attachment['file_id']] = $attachment['name'];

        return $files;
    }


    /* save email info
     * TODO: Refactor it to include outgoing emails on responses.
     */

    function saveEmailInfo($vars) {

        if(!$vars || !$vars['mid'])
            return 0;

        $this->ht['email_mid'] = $vars['mid'];

        $header = false;
        if (isset($vars['header']))
            $header = $vars['header'];
        self::logEmailHeaders($this->getId(), $vars['mid'], $header);
    }

    /* static */
    function logEmailHeaders($id, $mid, $header=false) {

        if (!$id || !$mid)
            return false;

        $sql='INSERT INTO '.THREAD_ENTRY_EMAIL_TABLE
            .' SET thread_entry_id='.db_input($id)
            .', mid='.db_input($mid);
        if ($header)
            $sql .= ', headers='.db_input($header);

        return db_query($sql) ? db_insert_id() : 0;
    }

    /* variables */

    function __toString() {
        return (string) $this->getBody();
    }

    function asVar() {
        return (string) $this->getBody()->display('email');
    }

    function getVar($tag) {
        global $cfg;

        if($tag && is_callable(array($this, 'get'.ucfirst($tag))))
            return call_user_func(array($this, 'get'.ucfirst($tag)));

        switch(strtolower($tag)) {
            case 'create_date':
                // XXX: Consider preferences of receiving user
                return Format::datetime($this->getCreateDate(), true, 'UTC');
            case 'update_date':
                return Format::datetime($this->getUpdateDate(), true, 'UTC');
        }

        return false;
    }

    static function lookup($id, $tid=0, $type='') {
        return ($id
                && is_numeric($id)
                && ($e = new ThreadEntry($id, $tid, $type))
                && $e->getId()==$id
                )?$e:null;
    }

    /**
     * Parameters:
     * mailinfo (hash<String>) email header information. Must include keys
     *  - "mid" => Message-Id header of incoming mail
     *  - "in-reply-to" => Message-Id the email is a direct response to
     *  - "references" => List of Message-Id's the email is in response
     *  - "subject" => Find external ticket number in the subject line
     *
     *  seen (by-ref:bool) a flag that will be set if the message-id was
     *      positively found, indicating that the message-id has been
     *      previously seen. This is useful if no thread-id is associated
     *      with the email (if it was rejected for instance).
     */
    function lookupByEmailHeaders(&$mailinfo, &$seen=false) {
        // Search for messages using the References header, then the
        // in-reply-to header
        $search = 'SELECT thread_entery_id, mid FROM '.THREAD_ENTRY_EMAIL_TABLE
               . ' WHERE mid=%s '
               . ' ORDER BY thread_entry_id DESC';

        if (list($id, $mid) = db_fetch_row(db_query(
                sprintf($search, db_input($mailinfo['mid']))))) {
            $seen = true;
            return ThreadEntry::lookup($id);
        }

        foreach (array('in-reply-to', 'references') as $header) {
            $matches = array();
            if (!isset($mailinfo[$header]) || !$mailinfo[$header])
                continue;
            // Header may have multiple entries (usually separated by
            // spaces ( )
            elseif (!preg_match_all('/<[^>@]+@[^>]+>/', $mailinfo[$header],
                        $matches))
                continue;

            // The References header will have the most recent message-id
            // (parent) on the far right.
            // @see rfc 1036, section 2.2.5
            // @see http://www.jwz.org/doc/threading.html
            $thread = null;
            foreach (array_reverse($matches[0]) as $mid) {
                //Try to determine if it's a reply to a tagged email.
                $ref = null;
                if (strpos($mid, '+')) {
                    list($left, $right) = explode('@',$mid);
                    list($left, $ref) = explode('+', $left);
                    $mid = "$left@$right";
                }
                $res = db_query(sprintf($search, db_input($mid)));
                while (list($id) = db_fetch_row($res)) {
                    if (!($t = ThreadEntry::lookup($id)))
                        continue;
                    // Capture the first match thread item
                    if (!$thread)
                        $thread = $t;
                    // We found a match  - see if we can ID the user.
                    // XXX: Check access of ref is enough?
                    if ($ref && ($uid = $t->getUIDFromEmailReference($ref))) {
                        if ($ref[0] =='s') //staff
                            $mailinfo['staffId'] = $uid;
                        else // user or collaborator.
                            $mailinfo['userId'] = $uid;

                        // Best possible case — found the thread and the
                        // user
                        return $t;
                    }
                }
            }
            // Second best case — found a thread but couldn't identify the
            // user from the header. Return the first thread entry matched
            if ($thread)
                return $thread;
        }

        // Search for ticket by the [#123456] in the subject line
        // This is the last resort -  emails must match to avoid message
        // injection by third-party.
        $subject = $mailinfo['subject'];
        $match = array();
        if ($subject
                && $mailinfo['email']
                && preg_match("/\b#(\S+)/u", $subject, $match)
                //Lookup by ticket number
                && ($ticket = Ticket::lookupByNumber($match[1]))
                //Lookup the user using the email address
                && ($user = User::lookup(array('emails__address' => $mailinfo['email'])))) {
            //We have a valid ticket and user
            if ($ticket->getUserId() == $user->getId() //owner
                    ||  ($c = Collaborator::lookup( // check if collaborator
                            array('userId' => $user->getId(),
                                  'ticketId' => $ticket->getId())))) {

                $mailinfo['userId'] = $user->getId();
                return $ticket->getLastMessage();
            }
        }

        // Search for the message-id token in the body
        if (preg_match('`(?:data-mid="|Ref-Mid: )([^"\s]*)(?:$|")`',
                $mailinfo['message'], $match))
            if ($thread = ThreadEntry::lookupByRefMessageId($match[1],
                    $mailinfo['email']))
                return $thread;

        return null;
    }

    /**
     * Find a thread entry from a message-id created from the
     * ::asMessageId() method
     */
    function lookupByRefMessageId($mid, $from) {
        $mid = trim($mid, '<>');
        list($ver, $ids, $mails) = explode('$', $mid, 3);

        // Current version is <null>
        if ($ver !== '')
            return false;

        $ids = @unpack('Vthread', base64_decode($ids));
        if (!$ids || !$ids['thread'])
            return false;

        $thread = ThreadEntry::lookup($ids['thread']);
        if (!$thread)
            return false;

        if (0 === strcasecmp($thread->asMessageId($from, $ver), $mid))
            return $thread;
    }

    /**
     * Get an email message-id that can be used to represent this thread
     * entry. The same message-id can be passed to ::lookupByRefMessageId()
     * to find this thread entry
     *
     * Formats:
     * Initial (version <null>)
     * <$:b32(thread-id)$:md5(to-addr.ticket-num.ticket-id)@:md5(url)>
     *      thread-id - thread-id, little-endian INT, packed
     *      :b32() - base32 encoded
     *      to-addr - individual email recipient
     *      ticket-num - external ticket number
     *      ticket-id - internal ticket id
     *      :md5() - last 10 hex chars of MD5 sum
     *      url - helpdesk URL
     */
    function asMessageId($to, $version=false) {
        global $ost;

        $domain = md5($ost->getConfig()->getURL());
        $ticket = $this->getThread()->getObject();
        return sprintf('$%s$%s@%s',
            base64_encode(pack('V', $this->getId())),
            substr(md5($to . $ticket->getNumber() . $ticket->getId()), -10),
            substr($domain, -10)
        );
    }

    //new entry ... we're trusting the caller to check validity of the data.
    static function create($vars) {
        global $cfg;

        //Must have...
        if (!$vars['threadId'] || !$vars['type'])
            return false;


        if (!$vars['body'] instanceof ThreadEntryBody) {
            if ($cfg->isHtmlThreadEnabled())
                $vars['body'] = new HtmlThreadEntryBody($vars['body']);
            else
                $vars['body'] = new TextThreadEntryBody($vars['body']);
        }

        // Drop stripped images
        if ($vars['attachments']) {
            foreach ($vars['body']->getStrippedImages() as $cid) {
                foreach ($vars['attachments'] as $i=>$a) {
                    if (@$a['cid'] && $a['cid'] == $cid) {
                        // Inline referenced attachment was stripped
                        unset($vars['attachments'][$i]);
                    }
                }
            }
        }

        // Handle extracted embedded images (<img src="data:base64,..." />).
        // The extraction has already been performed in the ThreadEntryBody
        // class. Here they should simply be added to the attachments list
        if ($atts = $vars['body']->getEmbeddedHtmlImages()) {
            if (!is_array($vars['attachments']))
                $vars['attachments'] = array();
            foreach ($atts as $info) {
                $vars['attachments'][] = $info;
            }
        }

        if (!($body = $vars['body']->getClean()))
            $body = '-'; //Special tag used to signify empty message as stored.

        $poster = $vars['poster'];
        if ($poster && is_object($poster))
            $poster = (string) $poster;

        $sql=' INSERT INTO '.THREAD_ENTRY_TABLE.' SET `created` = NOW() '
            .' ,`type` = '.db_input($vars['type'])
            .' ,`thread_id` = '.db_input($vars['threadId'])
            .' ,`title` = '.db_input(Format::sanitize($vars['title'], true))
            .' ,`format` = '.db_input($vars['body']->getType())
            .' ,`staff_id` = '.db_input($vars['staffId'])
            .' ,`user_id` = '.db_input($vars['userId'])
            .' ,`poster` = '.db_input($poster)
            .' ,`source` = '.db_input($vars['source']);

        if (!isset($vars['attachments']) || !$vars['attachments'])
            // Otherwise, body will be configured in a block below (after
            // inline attachments are saved and updated in the database)
            $sql.=' ,body='.db_input($body);

        if (isset($vars['pid']))
            $sql.=' ,pid='.db_input($vars['pid']);
        // Check if 'reply_to' is in the $vars as the previous ThreadEntry
        // instance. If the body of the previous message is found in the new
        // body, strip it out.
        elseif (isset($vars['reply_to'])
                && $vars['reply_to'] instanceof ThreadEntry)
            $sql.=' ,pid='.db_input($vars['reply_to']->getId());

        if ($vars['ip_address'])
            $sql.=' ,ip_address='.db_input($vars['ip_address']);

        //echo $sql;
        if (!db_query($sql)
                || !($entry=self::lookup(db_insert_id(), $vars['threadId'])))
            return false;

        /************* ATTACHMENTS *****************/

        //Upload/save attachments IF ANY
        if($vars['files']) //expects well formatted and VALIDATED files array.
            $entry->uploadFiles($vars['files']);

        //Canned attachments...
        if($vars['cannedattachments'] && is_array($vars['cannedattachments']))
            $entry->saveAttachments($vars['cannedattachments']);

        //Emailed or API attachments
        if (isset($vars['attachments']) && $vars['attachments']) {
            foreach ($vars['attachments'] as &$a)
                if (isset($a['cid']) && $a['cid']
                        && strpos($body, 'cid:'.$a['cid']) !== false)
                    $a['inline'] = true;
            unset($a);

            $entry->importAttachments($vars['attachments']);
            foreach ($vars['attachments'] as $a) {
                // Change <img src="cid:"> inside the message to point to
                // a unique hash-code for the attachment. Since the
                // content-id will be discarded, only the unique hash-code
                // will be available to retrieve the image later
                if ($a['cid'] && $a['key']) {
                    $body = preg_replace('/src=("|\'|\b)(?:cid:)?'
                        . preg_quote($a['cid'], '/').'\1/i',
                        'src="cid:'.$a['key'].'"', $body);
                }
            }

            $sql = 'UPDATE '.THREAD_ENTRY_TABLE
                .' SET body='.db_input($body)
                .' WHERE `id`='.db_input($entry->getId());

            if (!db_query($sql) || !db_affected_rows())
                return false;
        }

        // Email message id (required for all thread posts)
        if (!isset($vars['mid']))
            $vars['mid'] = sprintf('<%s@%s>',
                    Misc::randCode(24), substr(md5($cfg->getUrl()), -10));

        $entry->saveEmailInfo($vars);

        // Inline images (attached to the draft)
        $entry->saveAttachments(Draft::getAttachmentIds($body));

        Signal::send('model.created', $entry);

        return $entry;
    }

    static function add($vars) {
        return ($entry=self::create($vars)) ? $entry->getId() : 0;
    }
}


class ThreadEntryBody /* extends SplString */ {

    static $types = array('text', 'html');

    var $body;
    var $type;
    var $stripped_images = array();
    var $embedded_images = array();
    var $options = array(
        'strip-embedded' => true
    );

    function __construct($body, $type='text', $options=array()) {
        $type = strtolower($type);
        if (!in_array($type, static::$types))
            throw new Exception("$type: Unsupported ThreadEntryBody type");
        $this->body = (string) $body;
        if (strlen($this->body) > 250000) {
            $max_packet = db_get_variable('max_allowed_packet', 'global');
            // Truncate just short of the max_allowed_packet
            $this->body = substr($this->body, 0, $max_packet - 2048) . ' ... '
               . _S('(truncated)');
        }
        $this->type = $type;
        $this->options = array_merge($this->options, $options);
    }

    function isEmpty() {
        return !$this->body || $this->body == '-';
    }

    function convertTo($type) {
        if ($type === $this->type)
            return $this;

        $conv = $this->type . ':' . strtolower($type);
        switch ($conv) {
        case 'text:html':
            return new ThreadEntryBody(sprintf('<pre>%s</pre>',
                Format::htmlchars($this->body)), $type);
        case 'html:text':
            return new ThreadEntryBody(Format::html2text((string) $this), $type);
        }
    }

    function stripQuotedReply($tag) {

        //Strip quoted reply...on emailed  messages
        if (!$tag || strpos($this->body, $tag) === false)
            return;

        // Capture a list of inline images
        $images_before = $images_after = array();
        preg_match_all('/src=("|\'|\b)cid:(\S+)\1/', $this->body, $images_before,
            PREG_PATTERN_ORDER);

        // Strip the quoted part of the body
        if ((list($msg) = explode($tag, $this->body, 2)) && trim($msg)) {
            $this->body = $msg;

            // Capture a list of dropped inline images
            if ($images_before) {
                preg_match_all('/src=("|\'|\b)cid:(\S+)\1/', $this->body,
                    $images_after, PREG_PATTERN_ORDER);
                $this->stripped_images = array_diff($images_before[2],
                    $images_after[2]);
            }
        }
    }

    function getStrippedImages() {
        return $this->stripped_images;
    }

    function getEmbeddedHtmlImages() {
        return $this->embedded_images;
    }

    function getType() {
        return $this->type;
    }

    function getClean() {
        return trim($this->body);
    }

    function __toString() {
        return (string) $this->body;
    }

    function toHtml() {
        return $this->display('html');
    }

    function display($format=false) {
        throw new Exception('display: Abstract display() method not implemented');
    }

    function getSearchable() {
        return Format::searchable($this->body);
    }

    static function fromFormattedText($text, $format=false) {
        switch ($format) {
        case 'text':
            return new TextThreadEntryBody($text);
        case 'html':
            return new HtmlThreadEntryBody($text, array('strip-embedded'=>false));
        default:
            return new ThreadEntryBody($text);
        }
    }
}

class TextThreadEntryBody extends ThreadEntryBody {
    function __construct($body, $options=array()) {
        parent::__construct($body, 'text', $options);
    }

    function getClean() {
        return Format::stripEmptyLines($this->body);
    }

    function display($output=false) {
        if ($this->isEmpty())
            return '(empty)';

        switch ($output) {
        case 'html':
            return '<div style="white-space:pre-wrap">'
                .Format::clickableurls(Format::htmlchars($this->body)).'</div>';
        case 'email':
            return '<div style="white-space:pre-wrap">'.$this->body.'</div>';
        case 'pdf':
            return nl2br($this->body);
        default:
            return '<pre>'.$this->body.'</pre>';
        }
    }

    function asVar() {
        // Email template, assume HTML
        return $this->display('email');
    }
}
class HtmlThreadEntryBody extends ThreadEntryBody {
    function __construct($body, $options=array()) {
        if (!isset($options['strip-embedded']) || $options['strip-embedded'])
            $body = $this->extractEmbeddedHtmlImages($body);
        parent::__construct($body, 'html', $options);
    }

    function extractEmbeddedHtmlImages($body) {
        $self = $this;
        return preg_replace_callback('/src="(data:[^"]+)"/',
        function ($m) use ($self) {
            $info = Format::parseRfc2397($m[1], false, false);
            $info['cid'] = 'img'.Misc::randCode(12);
            list(,$type) = explode('/', $info['type'], 2);
            $info['name'] = 'image'.Misc::randCode(4).'.'.$type;
            $self->embedded_images[] = $info;
            return 'src="cid:'.$info['cid'].'"';
        }, $body);
    }

    function getClean() {
        return trim($this->body, " <>br/\t\n\r") ? Format::sanitize($this->body) : '';
    }

    function getSearchable() {
        // <br> -> \n
        $body = preg_replace(array('`<br(\s*)?/?>`i', '`</div>`i'), "\n", $this->body);
        $body = Format::htmldecode(Format::striptags($body));
        return Format::searchable($body);
    }

    function display($output=false) {
        if ($this->isEmpty())
            return '(empty)';

        switch ($output) {
        case 'email':
            return $this->body;
        case 'pdf':
            return Format::clickableurls($this->body, false);
        default:
            return Format::display($this->body);
        }
    }
}


/* Message - Ticket thread entry of type message */
class MessageThreadEntry extends ThreadEntry {

    const ENTRY_TYPE = 'M';

    function MessageThreadEntry($id, $threadId=0) {
        parent::ThreadEntry($id, $threadId, self::ENTRY_TYPE);
    }

    function getSubject() {
        return $this->getTitle();
    }

    static function create($vars, &$errors) {
        return self::lookup(self::add($vars, $errors));
    }

    static function add($vars, &$errors) {

        if (!$vars || !is_array($vars) || !$vars['threadId'])
            $errors['err'] = __('Missing or invalid data');
        elseif (!$vars['message'])
            $errors['message'] = __('Message content is required');

        if ($errors) return false;

        $vars['type'] = self::ENTRY_TYPE;
        $vars['body'] = $vars['message'];

        if (!$vars['poster']
                && $vars['userId']
                && ($user = User::lookup($vars['userId'])))
            $vars['poster'] = (string) $user->getName();

        return parent::add($vars);
    }

    static function lookup($id, $tid=0) {

        return ($id
                && is_numeric($id)
                && ($m = new MessageThreadEntry($id, $tid))
                && $m->getId()==$id
                )?$m:null;
    }

    //TODO: redo shit below.

    function lastByTicketId($ticketId) {
        return self::byTicketId($ticketId);
    }

    function firstByTicketId($ticketId) {
        return self::byTicketId($ticketId, false);
    }

    function byTicketId($ticketId, $last=true) {

        $sql=' SELECT thread.id FROM '.TICKET_THREAD_TABLE.' thread '
            .' WHERE thread_type=\'M\' AND thread.ticket_id = '.db_input($ticketId)
            .sprintf(' ORDER BY thread.id %s LIMIT 1', $last ? 'DESC' : 'ASC');

        if (($res = db_query($sql)) && ($id = db_result($res)))
            return Message::lookup($id);

        return null;
    }
}


/* thread entry of type response */
class ResponseThreadEntry extends ThreadEntry {

    const ENTRY_TYPE = 'R';

    function ResponseThreadEntry($id, $threadId=0) {
        parent::ThreadEntry($id, $threadId, self::ENTRY_TYPE);
    }

    function getSubject() {
        return $this->getTitle();
    }

    function getRespondent() {
        return $this->getStaff();
    }

    static function create($vars, &$errors) {
        return self::lookup(self::add($vars, $errors));
    }

    static function add($vars, &$errors) {

        if (!$vars || !is_array($vars) || !$vars['threadId'])
            $errors['err'] = __('Missing or invalid data');
        elseif (!$vars['response'])
            $errors['response'] = __('Response content is required');

        if ($errors) return false;

        $vars['type'] = self::ENTRY_TYPE;
        $vars['body'] = $vars['response'];
        if (!$vars['pid'] && $vars['msgId'])
            $vars['pid'] = $vars['msgId'];

        if (!$vars['poster']
                && $vars['staffId']
                && ($staff = Staff::lookup($vars['staffId'])))
            $vars['poster'] = (string) $staff->getName();

        return parent::add($vars);
    }

    static function lookup($id, $tid=0) {

        return ($id
                && is_numeric($id)
                && ($r = new ResponseThreadEntry($id, $tid))
                && $r->getId()==$id
                )?$r:null;
    }
}

/* Thread entry of type note (Internal Note) */
class NoteThreadEntry extends ThreadEntry {
    const ENTRY_TYPE = 'N';

    function NoteThreadEntry($id, $threadId=0) {
        parent::ThreadEntry($id, $threadId, self::ENTRY_TYPE);
    }

    function getMessage() {
        return $this->getBody();
    }

    static function create($vars, &$errors) {
        return self::lookup(self::add($vars, $errors));
    }

    static function add($vars, &$errors) {

        //Check required params.
        if (!$vars || !is_array($vars) || !$vars['threadId'])
            $errors['err'] = __('Missing or invalid data');
        elseif (!$vars['note'])
            $errors['note'] = __('Note content is required');

        if ($errors) return false;

        //TODO: use array_intersect_key  when we move to php 5 to extract just what we need.
        $vars['type'] = self::ENTRY_TYPE;
        $vars['body'] = $vars['note'];

        return parent::add($vars);
    }

    static function lookup($id, $tid=0) {

        return ($id
                && is_numeric($id)
                && ($n = new NoteThreadEntry($id, $tid))
                && $n->getId()==$id
                )?$n:null;
    }
}

// Ticket specific thread utils.
class TicketThread extends Thread {
    private $_entries = array();

    function __construct($id) {

        parent::__construct($id);

        if ($this->getId()) {
            $sql= ' SELECT `type`, count(DISTINCT e.id) as count '
                 .' FROM '.THREAD_TABLE. ' t '
                 .' INNER JOIN '.THREAD_ENTRY_TABLE. ' e ON (e.thread_id = t.id) '
                 .' WHERE t.id='.db_input($this->getId())
                 .' GROUP BY e.`type`';

            if (($res=db_query($sql)) && db_num_rows($res)) {
                while ($row=db_fetch_row($res))
                    $this->_entries[$row[0]] = $row[1];
            }
        }
    }

    function getNumMessages() {
        return $this->_entries[MessageThreadEntry::ENTRY_TYPE];
    }

    function getNumResponses() {
        return $this->_entries[ResponseThreadEntry::ENTRY_TYPE];
    }

    function getNumNotes() {
        return $this->_entries[NoteThreadEntry::ENTRY_TYPE];
    }

    function getMessages() {
        return $this->getEntries(MessageThreadEntry::ENTRY_TYPE);
    }

    function getResponses() {
        return $this->getEntries(ResponseThreadEntry::ENTRY_TYPE);
    }

    function getNotes() {
        return $this->getEntries(NoteThreadEntry::ENTRY_TYPE);
    }

    function addNote($vars, &$errors) {

        //Add ticket Id.
        $vars['threadId'] = $this->getId();
        return NoteThreadEntry::create($vars, $errors);
    }

    function addMessage($vars, &$errors) {

        $vars['threadId'] = $this->getId();
        $vars['staffId'] = 0;

        return MessageThreadEntry::create($vars, $errors);
    }

    function addResponse($vars, &$errors) {

        $vars['threadId'] = $this->getId();
        $vars['userId'] = 0;

        return ResponseThreadEntry::create($vars, $errors);
    }

    //TODO: revisit
    function getVar($name) {
        switch ($name) {
            case 'original':
                return MessageThreadEntry::first($this->getId())->getBody();
            break;
        case 'last_message':
        case 'lastmessage':
            return $this->ticket->getLastMessage()->getBody();
            break;
        }
    }

    static function create($ticket) {
        $id = is_object($ticket) ? $ticket->getId() : $ticket;
        return parent::create(array(
                    'object_id' => $id,
                    'object_type' => 'T'));
    }

    static function lookup($id) {

        return ($id
                && ($t= new TicketThread($id))
                && $t->getId()
                ) ? $t : null;
    }
}
?>
