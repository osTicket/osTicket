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
include_once(INCLUDE_DIR.'class.role.php');

//Ticket thread.
class Thread extends VerySimpleModel
implements Searchable {
    static $meta = array(
        'table' => THREAD_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'ticket' => array(
                'constraint' => array(
                    'object_type' => "'T'",
                    'object_id' => 'Ticket.ticket_id',
                ),
            ),
            'task' => array(
                'constraint' => array(
                    'object_type' => "'A'",
                    'object_id' => 'Task.id',
                ),
            ),
            'collaborators' => array(
                'reverse' => 'Collaborator.thread',
            ),

            'referrals' => array(
                'reverse' => 'ThreadReferral.thread',
            ),
            'entries' => array(
                'reverse' => 'ThreadEntry.thread',
            ),
            'events' => array(
                'reverse' => 'ThreadEvent.thread',
                'broker' => 'ThreadEvents',
            ),
        ),
    );

    const MODE_STAFF = 1;
    const MODE_CLIENT = 2;

    var $_object;
    var $_entries;
    var $_collaborators; // Cache for collabs
    var $_participants;

    function getId() {
        return $this->id;
    }

    function getObjectId() {
        return $this->object_id;
    }

    function getObjectType() {
        return $this->object_type;
    }

    function getObject() {

        if (!$this->_object)
            $this->_object = ObjectModel::lookup(
                    $this->getObjectId(), $this->getObjectType());

        return $this->_object;
    }

    function getNumAttachments() {
        return Attachment::objects()->filter(array(
            'thread_entry__thread' => $this
        ))->count();
    }

    function getNumEntries() {
        return $this->entries->count();
    }

    function getEntries($criteria=false) {
        if (!isset($this->_entries)) {
            $this->_entries = $this->entries->annotate(array(
                'has_attachments' => SqlAggregate::COUNT(SqlCase::N()
                    ->when(array('attachments__inline'=>0), 1)
                    ->otherwise(null)
                ),
            ));
            $this->_entries->exclude(array('flags__hasbit'=>ThreadEntry::FLAG_HIDDEN));
            if ($criteria)
                $this->_entries->filter($criteria);
        }
        return $this->_entries;
    }

    // Referrals
    function getNumReferrals() {
        return $this->referrals->count();
    }

    function getReferrals() {
        return $this->referrals;
    }

    // Collaborators
    function getNumCollaborators() {
        return $this->getCollaborators()->count();
    }

    function getNumActiveCollaborators() {

        if (!isset($this->ht['active_collaborators']))
            $this->ht['active_collaborators'] = count($this->getActiveCollaborators());

        return $this->ht['active_collaborators'];
    }

    function getActiveCollaborators() {
        $collaborators = $this->getCollaborators();
        $active = array();
        foreach ($collaborators as $c) {
          if ($c->isActive())
            $active[] = $c;
        }
        return $active;
    }

    function getCollaborators($criteria=array()) {

        if ($this->_collaborators && !$criteria)
            return $this->_collaborators;

        $collaborators = $this->collaborators
            ->filter(array('thread_id' => $this->getId()));

        if (isset($criteria['isactive']))
          $collaborators->filter(array('flags__hasbit'=>Collaborator::FLAG_ACTIVE));


        // TODO: sort by name of the user
        $collaborators->order_by('user__name');

        if (!$criteria)
            $this->_collaborators = $collaborators;

        return $collaborators;
    }

    function isCollaborator($user) {
        return $this->collaborators->findFirst(array(
                    'user_id'     => $user->getId(),
                    'thread_id'   => $this->getId()));
    }

    function addCollaborator($user, $vars, &$errors, $event=true) {
        if (!$user)
            return null;

        if ($this->isCollaborator($user))
            return false;

        $vars = array_merge(array(
                'threadId' => $this->getId(),
                'userId' => $user->getId()), $vars);
        if (!($c=Collaborator::add($vars, $errors)))
            return null;

        $this->_collaborators = null;

        if ($event) {
          $this->getEvents()->log($this->getObject(),
              'collab',
              array('add' => array($user->getId() => array(
                      'name' => $user->getName()->getOriginal(),
                      'src' => @$vars['source'],
                  ))
              )
          );

          $type = array('type' => 'collab', 'add' => array($user->getId() => array(
                  'name' => $user->getName()->name,
                  'src' => @$vars['source'],
              )));
          Signal::send('object.created', $this->getObject(), $type);
        }


        return $c;
    }

    function updateCollaborators($vars, &$errors) {
        global $thisstaff;

        if (!$thisstaff) return;

        //Deletes
        if($vars['del'] && ($ids=array_filter($vars['del']))) {
            $collabs = array();
            foreach ($ids as $k => $cid) {
                if (($c=Collaborator::lookup($cid))
                        && ($c->getThreadId() == $this->getId())
                        && $c->delete())
                     $collabs[] = $c;

                 $this->getEvents()->log($this->getObject(), 'collab', array(
                     'del' => array($c->user_id => array('name' => $c->getName()->getOriginal()))
                 ));
                 $type = array('type' => 'collab', 'del' => array($c->user_id => array(
                         'name' => $c->getName()->getOriginal(),
                         'src' => @$vars['source'],
                     )));
                 Signal::send('object.deleted', $this->getObject(), $type);
            }
        }

        //statuses
        $cids = null;
        if($vars['cid'] && ($cids=array_filter($vars['cid']))) {
            $this->collaborators->filter(array(
                'thread_id' => $this->getId(),
                'id__in' => $cids
            ))->update(array(
                'updated' => SqlFunction::NOW(),
            ));

            foreach ($vars['cid'] as $c) {
              $collab = Collaborator::lookup($c);
              if (($collab instanceof Collaborator)) {
                $collab->setFlag(Collaborator::FLAG_ACTIVE, true);
                $collab->save();
              }
            }
        }

        $inactive = $this->collaborators->filter(array(
            'thread_id' => $this->getId(),
            Q::not(array('id__in' => $cids ?: array(0)))
        ));
        if($inactive) {
          foreach ($inactive as $i) {
            $i->setFlag(Collaborator::FLAG_ACTIVE, false);
            $i->save();
          }
          $inactive->update(array(
              'updated' => SqlFunction::NOW(),
          ));
        }

        unset($this->ht['active_collaborators']);
        $this->_collaborators = null;

        return true;
    }

    //UserList of participants (collaborators)
    function getParticipants() {

        if (!isset($this->_participants)) {
            $list = new UserList();
            if ($collabs = $this->getActiveCollaborators()) {
                foreach ($collabs as $c)
                    $list->add($c);
            }

            $this->_participants = $list;
        }

        return $this->_participants;
    }

    // MailingList of recipients (collaborators)
    function getRecipients() {
        $list = new MailingList();
        if ($collabs = $this->getActiveCollaborators()) {
            foreach ($collabs as $c)
                $list->addCc($c);
        }

        return $list;
    }

    function getReferral($id, $type) {

        return $this->referrals->findFirst(array(
                    'object_id'     => $id,
                    'object_type'   => $type));
    }

    function isReferred($to=null, $strict=false) {

        if (is_null($to) || !$this->referrals)
            return ($this->referrals && $this->referrals->count());

        switch (true) {
        case $to instanceof Staff:
            // Referred to the staff
            if ($this->getReferral($to->getId(),
                        ObjectModel::OBJECT_TYPE_STAFF))
                return true;

            // Strict check only checks the Agent
            if ($strict)
                return false;

            // Referred to staff's department
            if ($this->referrals->findFirst(array(
                   'object_id__in' => $to->getDepts(),
                   'object_type'   => ObjectModel::OBJECT_TYPE_DEPT)))
                return true;

            // Referred to staff's teams
            if ($to->getTeams() && $this->referrals->findFirst(array(
                            'object_id__in' => $to->getTeams(),
                            'object_type'   => ObjectModel::OBJECT_TYPE_TEAM
                            )))
                return true;

            return false;
            break;
        case $to instanceof Team:
            //Referred to a Team
            return ($this->getReferral($to->getId(),
                        ObjectModel::OBJECT_TYPE_TEAM));
            break;
        case $to instanceof Dept:
            // Refered to the dept
            return ($this->getReferral($to->getId(),
                        ObjectModel::OBJECT_TYPE_DEPT));
            break;
        }

        return false;
    }

    function refer($to) {

        if ($this->isReferred($to, true))
            return false;

        $vars = array('thread_id' => $this->getId());
        switch (true) {
        case $to instanceof Staff:
            $vars['object_id'] = $to->getId();
            $vars['object_type'] = ObjectModel::OBJECT_TYPE_STAFF;
            break;
        case $to instanceof Team:
            $vars['object_id'] = $to->getId();
            $vars['object_type'] = ObjectModel::OBJECT_TYPE_TEAM;
            break;
        case $to instanceof Dept:
            $vars['object_id'] = $to->getId();
            $vars['object_type'] = ObjectModel::OBJECT_TYPE_DEPT;
            break;
        default:
            return false;
        }

        return ThreadReferral::create($vars);
    }

    // Render thread
    function render($type=false, $options=array()) {

        $mode = $options['mode'] ?: self::MODE_STAFF;

        // Register thread actions prior to rendering the thread.
        if (!class_exists('tea_showemailheaders'))
            include_once INCLUDE_DIR . 'class.thread_actions.php';

        $entries = $this->getEntries();

        if ($type && is_array($type)) {
          $visibility = Q::all(array('type__in' => $type));

          if ($type['user_id']) {
            $visibility->add(array('user_id' => $type['user_id']));
            $visibility->ored = true;
          }

          $entries->filter($visibility);
        }

        if ($options['sort'] && !strcasecmp($options['sort'], 'DESC'))
            $entries->order_by('-id');

        // Precache all the attachments on this thread
        AttachmentFile::objects()->filter(array(
            'attachments__thread_entry__thread__id' => $this->id
        ))->all();

        $events = $this->getEvents();
        $inc = ($mode == self::MODE_STAFF) ? STAFFINC_DIR : CLIENTINC_DIR;
        include $inc . 'templates/thread-entries.tmpl.php';
    }

    function getEntry($id) {
        return ThreadEntry::lookup($id, $this->getId());
    }

    function getEvents() {
        return $this->events;
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
    function postEmail($mailinfo, $entry=null) {
        // +==================+===================+=============+
        // | Orig Thread-Type | Reply Thread-Type | Requires    |
        // +==================+===================+=============+
        // | *                | Message (M)       | From: Owner |
        // | *                | Note (N)          | From: Staff |
        // | Response (R)     | Message (M)       |             |
        // | Message (M)      | Response (R)      | From: Staff |
        // +------------------+-------------------+-------------+

        if (!$object = $this->getObject()) {
            // How should someone find this thread?
            return false;
        }
        elseif ($object instanceof Ticket && (
               !$mailinfo['staffId']
            && $object->isClosed()
            && !$object->isReopenable()
        )) {
            // Ticket is closed, not reopenable, and email was not submitted
            // by an agent. Email cannot be submitted
            return false;
        }

        $vars = array(
            'mid' =>    $mailinfo['mid'],
            'header' => $mailinfo['header'],
            'poster' => $mailinfo['name'],
            'origin' => 'Email',
            'source' => 'Email',
            'ip' =>     '',
            'reply_to' => $entry,
            'recipients' => $mailinfo['recipients'],
            'thread_entry_recipients' => $mailinfo['thread_entry_recipients'],
            'to-email-id' => $mailinfo['to-email-id'],
            'autorespond' => !isset($mailinfo['passive']),
        );

        // XXX: Is this necessary?
        if ($object instanceof Ticket)
            $vars['ticketId'] = $object->getId();
        if ($object instanceof Task)
            $vars['taskId'] = $object->getId();

        $errors = array();

        if (isset($mailinfo['attachments']))
            $vars['attachments'] = $mailinfo['attachments'];

        $body = $mailinfo['message'];

        // extra handling for determining Cc collabs
        if ($mailinfo['email']) {
          $staffSenderId = Staff::getIdByEmail($mailinfo['email']);

          if (!$staffSenderId) {
            $senderId = UserEmailModel::getIdByEmail($mailinfo['email']);
            if ($senderId) {
              $mailinfo['userId'] = $senderId;

              if ($object instanceof Ticket && $senderId != $object->user_id && $senderId != $object->staff_id) {
                $mailinfo['userClass'] = 'C';

                $collaboratorId = Collaborator::getIdByUserId($senderId, $this->getId());
                $collaborator = Collaborator::lookup($collaboratorId);

                if ($collaborator && ($collaborator->isCc()))
                  $vars['thread-type'] = 'M';
              }
            }
          }
        }

        // Attempt to determine the user posting the entry and the
        // corresponding entry type by the information determined by the
        // mail parser (via the In-Reply-To header)
        switch ($mailinfo['userClass']) {
        case 'C': # Thread collaborator
            $vars['flags'] = ThreadEntry::FLAG_COLLABORATOR;
        case 'U': # Ticket owner
            $vars['thread-type'] = 'M';
            $vars['userId'] = $mailinfo['userId'];
            break;

        case 'A': # System administrator
        case 'S': # Staff member (agent)
            $vars['thread-type'] = 'N';
            $vars['staffId'] = $mailinfo['staffId'];
            if ($vars['staffId'])
                $vars['poster'] = Staff::lookup($mailinfo['staffId']);
            break;

        // The user type was not identified by the mail parsing system. It
        // is likely that the In-Reply-To and References headers were not
        // properly brokered by the user's mail client. Use the old logic to
        // determine the post type.
        default:
            // Disambiguate if the user happens also to be a staff member of
            // the system. The current ticket owner should _always_ post
            // messages instead of notes or responses
            if ($object instanceof Ticket
                && strcasecmp($mailinfo['email'], $object->getEmail()) == 0
            ) {
                $vars['thread-type'] = 'M';
                $vars['userId'] = $object->getUserId();
            }
            // Consider collaborator role (disambiguate staff members as
            // collaborators). Normally, the block above should match based
            // on the Referenced message-id header
            elseif ($C = $this->collaborators->filter(array(
                'user__emails__address' => $mailinfo['email']
            ))->first()) {
                $vars['thread-type'] = 'M';
                // XXX: There's no way that mailinfo[userId] would be set
                $vars['userId'] = $mailinfo['userId'] ?: $C->getUserId();
                $vars['flags'] = ThreadEntry::FLAG_COLLABORATOR;
            }
            // Don't process the email -- it came FROM this system
            elseif (Email::getIdByEmail($mailinfo['email'])) {
                return false;
            }
        }

        // Ensure we record the name of the person posting
        $vars['poster'] = $vars['poster']
            ?: $mailinfo['name'] ?: $mailinfo['email'];

        // TODO: Consider security constraints
        if (!$vars['thread-type']) {
            //XXX: Are we potentially leaking the email address to
            // collaborators?
            // Try not to destroy the format of the body
            $header = sprintf(
                _S('Received From: %1$s <%2$s>') . "\n\n",
                $mailinfo['name'], $mailinfo['email']);
            if ($body instanceof HtmlThreadEntryBody)
                $header = nl2br(Format::htmlchars($header));
            // Add the banner to the top of the message
            if ($body instanceof ThreadEntryBody)
                $body->prepend($header);
            $vars['userId'] = 0; //Unknown user! //XXX: Assume ticket owner?
            $vars['thread-type'] = 'M';
        }

        if ($mailinfo['system_emails']
                && ($t = $this->getObject())
                && $t instanceof Ticket)
            $t->systemReferral($mailinfo['system_emails']);

        switch ($vars['thread-type']) {
        case 'M':
            $vars['message'] = $body;
            if ($object instanceof Threadable) {
                $entry = $object->postThreadEntry('M', $vars);
                if ($this->getObjectType() == 'C') {
                    if ($object->isChild()) {
                        $parent = Ticket::lookup($object->getPid());
                        ThreadEntry::setExtra(array($entry), array('thread' => $this->getId()), $parent->getThread()->getId());
                    }
                }
                return $entry;
            }
            elseif ($this instanceof ObjectThread)
                return $this->addMessage($vars, $errors);
            break;

        case 'N':
            $vars['note'] = $body;
            if ($object instanceof Threadable)
                return $object->postThreadEntry('N', $vars);
            elseif ($this instanceof ObjectThread)
                return $this->addNote($vars, $errors);
            break;
        }

        throw new Exception('Unable to continue thread via email.');

        // Currently impossible, but indicate that this thread object could
        // not append the incoming email.
        return false;
    }

    function deleteAttachments() {
        $deleted = Attachment::objects()->filter(array(
            'thread_entry__thread' => $this,
        ))->delete();

        if ($deleted)
            AttachmentFile::deleteOrphans();

        return $deleted;
    }

    function removeCollaborators() {
        return Collaborator::objects()
            ->filter(array('thread_id'=>$this->getId()))
            ->delete();
    }

    function setExtra($mergedThread, $info='') {

        if ($info && $info['extra']) {
            $extra = json_decode($info['extra'], true);
            $entries = ThreadEntry::objects()->filter(array('thread_id' => $info['threadId']));
            foreach ($entries as $entry)
                $entry->saveExtra($entry, array('thread' => $info['threadId']), $mergedThread->getId());
        } else
            ThreadEntry::setExtra($this->getEntries(), array('thread' => $this->getId()), $mergedThread->getId());

        $this->object_type = 'C';
        $number = Ticket::objects()->filter(array('ticket_id'=>$this->getObjectId()))->values_flat('number')->first();
        $this->extra = json_encode(array('ticket_id' => $mergedThread->getObjectId(), 'number' => $extra['number'] ?: $number[0]));
        $this->save();
    }

    /**
     * Function: lookupByEmailHeaders
     *
     * Attempt to locate a thread by the email headers. It should be
     * considered a secondary lookup to ThreadEntry::lookupByEmailHeaders(),
     * which should find an actual thread entry, which should be possible
     * for all email communcation which is associated with a thread entry.
     * The only time where this is useful is for threads which triggered
     * email communication without a thread entry, for instance, like
     * tickets created without an initial message.
     */
    function lookupByEmailHeaders(&$mailinfo) {
        $possibles = array();
        foreach (array('mid', 'in-reply-to', 'references') as $header) {
            $matches = array();
            if (!isset($mailinfo[$header]) || !$mailinfo[$header])
                continue;
            // Header may have multiple entries (usually separated by
            // spaces ( )
            elseif (!preg_match_all('/<([^>@]+@[^>]+)>/', $mailinfo[$header],
                        $matches))
                continue;

            // The References header will have the most recent message-id
            // (parent) on the far right.
            // @see rfc 1036, section 2.2.5
            // @see http://www.jwz.org/doc/threading.html
            $possibles = array_merge($possibles, array_reverse($matches[1]));
        }

        // Add the message id if it is embedded in the body
        $match = array();
        if (preg_match('`(?:class="mid-|Ref-Mid: )([^"\s]*)(?:$|")`',
                $mailinfo['message'], $match)
            && !in_array($match[1], $possibles)
        ) {
            $possibles[] = $match[1];
        }

        foreach ($possibles as $mid) {
            // Attempt to detect the ticket and user ids from the
            // message-id header. If the message originated from
            // osTicket, the Mailer class can break it apart. If it came
            // from this help desk, the 'loopback' property will be set
            // to true.
            $mid_info = Mailer::decodeMessageId($mid);
            if (!$mid_info || !$mid_info['loopback'])
                continue;
            if (isset($mid_info['uid'])
                && @$mid_info['threadId']
                && ($t = Thread::lookup($mid_info['threadId']))
            ) {
                if (@$mid_info['userId']) {
                    $mailinfo['userId'] = $mid_info['userId'];
                }
                elseif (@$mid_info['staffId']) {
                    $mailinfo['staffId'] = $mid_info['staffId'];
                }
                // ThreadEntry was positively identified
                return $t;
            }
        }

        return null;
    }

    static function getSearchableFields() {
        return array(
            'lastmessage' => new DatetimeField(array(
                'label' => __('Last Message'),
            )),
            'lastresponse' => new DatetimeField(array(
                'label' => __('Last Response'),
            )),
        );
    }

    static function supportsCustomData() {
        false;
    }

    function delete() {

        //Self delete
        if (!parent::delete())
            return false;

        // Clear email meta data (header..etc)
        ThreadEntryEmailInfo::objects()
            ->filter(array('thread_entry__thread' => $this))
            ->update(array('headers' => null));

        // Mass delete entries
        $this->deleteAttachments();
        $this->removeCollaborators();

        $this->entries->delete();

        // Null out the events
        $this->events->update(array('thread_id' => 0));

        return true;
    }

    static function create($vars=false) {
        $inst = new static($vars);
        $inst->created = SqlFunction::NOW();
        return $inst;
    }
}

class ThreadEntryEmailInfo extends VerySimpleModel {
    static $meta = array(
        'table' => THREAD_ENTRY_EMAIL_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'thread_entry' => array(
                'constraint' => array('thread_entry_id' => 'ThreadEntry.id'),
            ),
        ),
    );
}

class ThreadEntryMergeInfo extends VerySimpleModel {
    static $meta = array(
        'table' => THREAD_ENTRY_MERGE_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'thread_entry' => array(
                'constraint' => array('thread_entry_id' => 'ThreadEntry.id'),
            ),
        ),
    );
}

class ThreadEntry extends VerySimpleModel
implements TemplateVariable {
    static $meta = array(
        'table' => THREAD_ENTRY_TABLE,
        'pk' => array('id'),
        'select_related' => array('staff', 'user', 'email_info'),
        'ordering' => array('created', 'id'),
        'joins' => array(
            'thread' => array(
                'constraint' => array('thread_id' => 'Thread.id'),
            ),
            'parent' => array(
                'constraint' => array('pid' => 'ThreadEntry.id'),
                'null' => true,
            ),
            'children' => array(
                'reverse' => 'ThreadEntry.parent',
            ),
            'email_info' => array(
                'reverse' => 'ThreadEntryEmailInfo.thread_entry',
                'list' => false,
            ),
            'merge_info' => array(
                'reverse' => 'ThreadEntryMergeInfo.thread_entry',
                'list' => false,
            ),
            'attachments' => array(
                'reverse' => 'Attachment.thread_entry',
                'null' => true,
            ),
            'staff' => array(
                'constraint' => array('staff_id' => 'Staff.staff_id'),
                'null' => true,
            ),
            'user' => array(
                'constraint' => array('user_id' => 'User.id'),
                'null' => true,
            ),
        ),
    );

    const FLAG_ORIGINAL_MESSAGE         = 0x0001;
    const FLAG_EDITED                   = 0x0002;
    const FLAG_HIDDEN                   = 0x0004;
    const FLAG_GUARDED                  = 0x0008;   // No replace on edit
    const FLAG_RESENT                   = 0x0010;

    const FLAG_COLLABORATOR             = 0x0020;   // Message from collaborator
    const FLAG_BALANCED                 = 0x0040;   // HTML does not need to be balanced on ::display()
    const FLAG_SYSTEM                   = 0x0080;   // Entry is a system note.
    const FLAG_REPLY_ALL                = 0x00100;  // Agent response, reply all
    const FLAG_REPLY_USER               = 0x00200;  // Agent response, reply to User
    const FLAG_CHILD                    = 0x00400;  // Entry is from a child Ticket

    const PERM_EDIT     = 'thread.edit';

    var $_headers;
    var $_body;
    var $_thread;
    var $_actions;
    var $is_autoreply;
    var $is_bounce;

    static protected $perms = array(
        self::PERM_EDIT => array(
            'title' => /* @trans */ 'Edit Thread',
            'desc'  => /* @trans */ 'Ability to edit thread items of other agents',
        ),
    );

    // Thread entry types
    static protected $types = array(
            'M' => 'message',
            'R' => 'response',
            'N' => 'note',
    );

    function getTypeName() {
      return self::$types[$this->type];
    }

    function postEmail($mailinfo) {
        global $ost;

        if (!($thread = $this->getThread()))
            // Kind of hard to continue a discussion without a thread ...
            return false;

        elseif ($this->getEmailMessageId() == $mailinfo['mid'])
            // Reporting success so the email can be moved or deleted.
            return true;

        // Mail sent by this system will have a predictable message-id
        // If this incoming mail matches the code, then it very likely
        // originated from this system and looped
        $info = Mailer::decodeMessageId($mailinfo['mid']);
        if ($info && $info['loopback']) {
            // This mail was sent by this system. It was received due to
            // some kind of mail delivery loop. It should not be considered
            // a response to an existing thread entry
            if ($ost)
                $ost->log(LOG_ERR, _S('Email loop detected'), sprintf(
                _S('It appears as though &lt;%s&gt; is being used as a forwarded or fetched email account and is also being used as a user / system account. Please correct the loop or seek technical assistance.'),
                $mailinfo['email']),

                // This is quite intentional -- don't continue the loop
                false,
                // Force the message, even if logging is disabled
                true);
            return $this;
        }

        return $thread->postEmail($mailinfo, $this);
    }

    function getId() {
        return $this->id;
    }

    function getPid() {
        return $this->get('pid', 0);
    }

    function getParent() {
        return $this->parent;
    }

    function getType() {
        return $this->type;
    }

    function getSource() {
        return $this->source;
    }

    function getPoster() {
        return $this->poster;
    }

    function getTitle() {
        return $this->title;
    }

    function getBody() {
        if (!isset($this->_body)) {
            $body = $this->body;
            if ($body == null && $this->getNumAttachments()) {
                $attachments = Attachment::objects()
                   ->filter(array(
                               'inline' => 1,
                               'object_id' => $this->getId(),
                               'type' => ObjectModel::OBJECT_TYPE_THREAD,
                               'file__type__in' => array('text/html','text/plain'))
                           );
                foreach ($attachments as $a)
                    if ($a->inline && ($f=$a->getFile()))
                        $body .= $f->getData();
            }
            $this->_body = ThreadEntryBody::fromFormattedText($body, $this->format,
                array('balanced' => $this->hasFlag(self::FLAG_BALANCED))
            );
        }
        return $this->_body;
    }

    function setBody($body) {
        global $cfg;

        if (!$body instanceof ThreadEntryBody) {
            if ($cfg->isRichTextEnabled())
                $body = new HtmlThreadEntryBody($body);
            else
                $body = new TextThreadEntryBody($body);
        }

        $this->format = $body->getType();
        $this->body = (string) $body;
        return $this->save();
    }

    function getMessage() {
        return $this->getBody();
    }

    function getCreateDate() {
        return $this->created;
    }

    function getUpdateDate() {
        return $this->updated;
    }

    function getNumAttachments() {
        return $this->attachments->count();
    }

    function getEmailMessageId() {
        if ($this->email_info)
            return $this->email_info->mid;
    }

    function getEmailHeaderArray() {
        require_once(INCLUDE_DIR.'class.mailparse.php');

        if (!isset($this->_headers) && $this->email_info
            && isset($this->email_info->headers)
        ) {
            $this->_headers = Mail_Parse::splitHeaders($this->email_info->headers);
        }
        return $this->_headers;
    }

    function getEmailReferences($include_mid=true) {
        $references = '';
        $headers = self::getEmailHeaderArray();
        if (isset($headers['References']) && $headers['References'])
            $references = $headers['References']." ";
        if ($include_mid && ($mid = $this->getEmailMessageId()))
            $references .= $mid;
        return $references;
    }

    /**
     * Retrieve a list of all the recients of this message if the message
     * was received via email.
     *
     * Returns:
     * (array<RFC_822>) list of recipients parsed with the Mail/RFC822
     * address parsing utility. Returns an empty array if the message was
     * not received via email.
     */
    function getAllEmailRecipients() {
        $headers = self::getEmailHeaderArray();
        $recipients = array();
        if (!$headers)
            return $recipients;

        foreach (array('To', 'Cc') as $H) {
            if (!isset($headers[$H]))
                continue;

            if (!($all = Mail_Parse::parseAddressList($headers[$H])))
                continue;

            $recipients = array_merge($recipients, $all);
        }
        return $recipients;
    }

    /**
     * Recurse through the ancestry of this thread entry to find the first
     * thread entry which cites a email Message-ID field.
     *
     * Returns:
     * <ThreadEntry> or null if neither this thread entry nor any of its
     * ancestry contains an email header with an email Message-ID header.
     */
    function findOriginalEmailMessage() {
        $P = $this;
        while (!$P->getEmailMessageId()
            && ($P = $P->getParent()));
        return $P;
    }

    function getUIDFromEmailReference($ref) {

        $info = unpack('Vtid/Vuid',
                Base32::decode(strtolower(substr($ref, -13))));

        if ($info && $info['tid'] == $this->getId())
            return $info['uid'];

    }

    function getThreadId() {
        return $this->thread_id;
    }

    function getThread() {

        if (!isset($this->_thread) && $this->thread_id)
            // TODO: Consider typing the thread based on its type field
            $this->_thread = ObjectThread::lookup($this->getThreadId());

        return $this->_thread;
    }

    function getStaffId() {
        return isset($this->staff_id) ? $this->staff_id : 0;
    }

    function getStaff() {
        return $this->staff;
    }

    function getUserId() {
        return isset($this->user_id) ? $this->user_id : 0;
    }

    function getUser() {
        return $this->user;
    }

    function getEditor() {
        static $types = array(
            'U' => 'User',
            'S' => 'Staff',
        );
        if (!isset($types[$this->editor_type]))
            return null;

        return $types[$this->editor_type]::lookup($this->editor);
    }

    function getName() {
        if ($this->staff_id)
            return $this->staff->getName();
        if ($this->user_id)
            return $this->user->getName();

        return $this->poster;
    }

    function getEmailHeader() {
        if ($this->email_info)
            return $this->email_info->headers;
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

    function hasFlag($flag) {
        return ($this->get('flags', 0) & $flag) != 0;
    }
    function clearFlag($flag) {
        return $this->set('flags', $this->get('flags') & ~$flag);
    }
    function setFlag($flag) {
        return $this->set('flags', $this->get('flags') | $flag);
    }

    function isSystem() {
        return $this->hasFlag(self::FLAG_SYSTEM);
    }

    protected function normalizeFileInfo($files, $add_error=true) {
        static $error_descriptions = array(
            UPLOAD_ERR_INI_SIZE     => /* @trans */ 'File is too large',
            UPLOAD_ERR_FORM_SIZE    => /* @trans */ 'File is too large',
            UPLOAD_ERR_PARTIAL      => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR   => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE   => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION    => 'A PHP extension stopped the file upload.',
        );

        if (!is_array($files))
            $files = array($files);

        $ids = array();
        foreach ($files as $id => $info) {
            $F = array('inline' => is_array($info) && @$info['inline']);
            $AF = null;

            if ($info instanceof AttachmentFile)
                $fileId = $info->getId();
            elseif (is_array($info) && isset($info['id']))
                $fileId = $info['id'];
            elseif ($AF = AttachmentFile::create($info))
                $fileId = $AF->getId();
            elseif ($add_error) {
                $error = $info['error']
                    ?: sprintf(_S('Unable to save attachment - %s'),
                        $info['name'] ?: $info['id']);
                if (is_numeric($error) && isset($error_descriptions[$error])) {
                    $error = sprintf(_S('Error #%1$d: %2$s'), $error,
                        _S($error_descriptions[$error]));
                }
                // No need to log the missing-file error number
                if ($error != UPLOAD_ERR_NO_FILE
                    && ($thread = $this->getThread())
                ) {
                    // Log to the thread directly, since alerts should be
                    // suppressed and this is defintely a system message
                    $thread->addNote(array(
                        'title' => _S('File Import Error'),
                        'note' => new TextThreadEntryBody($error),
                        'poster' => 'SYSTEM',
                        'staffId' => 0,
                    ));
                }
                continue;
            }

            $F['id'] = $fileId;

            if (is_string($info))
                $F['name'] = $info;
            if (isset($AF))
                $F['file'] = $AF;

            // Add things like the `key` field, but don't change current
            // keys of the file array
            if (is_array($info))
                $F += $info;

            // Key is required for CID rewriting in the body
            if (!isset($F['key']) && ($AF = AttachmentFile::lookup($F['id'])))
                $F['key'] = $AF->key;

            $ids[] = $F;
        }
        return $ids;
    }

   /*
    Save attachment to the DB.
    @file is a mixed var - can be ID or file hashtable.
    */
    function createAttachment($file, $name=false) {
        $att = new Attachment(array(
            'type' => 'H',
            'object_id' => $this->getId(),
            'file_id' => $file['id'],
            'inline' => $file['inline'] ? 1 : 0,
        ));

        // Record varying file names in the attachment record
        if (is_array($file) && isset($file['name'])) {
            $filename = $file['name'];
        }
        elseif (is_string($name)) {
            $filename = $name;
        }

        if ($filename) {
            // This should be a noop since the ORM caches on PK
            $F = @$file['file'] ?: AttachmentFile::lookup($file['id']);
            // XXX: This is not Unicode safe
            // TODO: fix name lookup
            if ($F && strcasecmp($F->name, $filename) !== 0)
                $att->name = $filename;
        }

        if (!$att->save())
            return false;
        return $att;
    }

    function createAttachments(array $files) {
        $attachments = array();
        foreach ($files as $info) {
           if ($A = $this->createAttachment($info, @$info['name'] ?: false))
               $attachments[] = $A;
        }
        return $attachments;
    }

    function getAttachments() {
        return $this->attachments;
    }

    function getAttachmentUrls() {
        $json = array();
        foreach ($this->attachments as $att) {
            $json[$att->file->getKey()] = array(
                'download_url' => $att->file->getDownloadUrl(),
                'filename' => $att->getFilename(),
            );
        }

        return $json;
    }

    function getAttachmentsLinks($file='attachment.php', $target='_blank', $separator=' ') {
        // TODO: Move this to the respective UI templates

        $str='';
        foreach ($this->attachments as $att ) {
            if ($att->inline) continue;
            $size = '';
            if ($att->file->size)
                $size=sprintf('<em>(%s)</em>', Format::file_size($att->file->size));

            $str .= sprintf(
                '<a class="Icon file no-pjax" href="%s" target="%s">%s</a>%s&nbsp;%s',
                $att->file->getDownloadUrl(), $target,
                Format::htmlchars($att->file->name), $size, $separator);
        }

        return $str;
    }

    /* save email info
     * TODO: Refactor it to include outgoing emails on responses.
     */

    function saveEmailInfo($vars) {

        // Don't save empty message ID
        if (!$vars || !$vars['mid'])
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

        $this->email_info = new ThreadEntryEmailInfo(array(
            'thread_entry_id' => $id,
            'mid' => $mid,
        ));

        if ($header)
            $this->email_info->headers = trim($header);

        return $this->email_info->save();
    }

    function getActivity() {
        return new ThreadActivity('', '');
    }

    /* variables */

    function __toString() {
        return (string) $this->getBody();
    }

    // TemplateVariable interface
    function asVar() {
        return (string) $this->getBody()->display('email');
    }

    function getVar($tag) {
        switch(strtolower($tag)) {
            case 'create_date':
                return new FormattedDate($this->getCreateDate());
            case 'update_date':
                return new FormattedDate($this->getUpdateDate());
            case 'files':
                throw new OOBContent(OOBContent::FILES, $this->attachments->all());
        }
    }

    static function getVarScope() {
        return array(
          'files' => __('Attached files'),
          'body' => __('Message body'),
          'create_date' => array(
              'class' => 'FormattedDate', 'desc' => __('Date created'),
          ),
          'ip_address' => __('IP address of remote user, for web submissions'),
          'poster' => __('Submitter of the thread item'),
          'staff' => array(
            'class' => 'Staff', 'desc' => __('Agent posting the note or response'),
          ),
          'title' => __('Subject, if any'),
          'user' => array(
            'class' => 'User', 'desc' => __('User posting the message'),
          ),
        );
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
        if ($mailinfo['mid'] &&
                ($entry = ThreadEntry::objects()
                 ->filter(array('email_info__mid' => $mailinfo['mid']))
                 ->order_by(false)
                 ->first()
                 )
         ) {
            $seen = true;
            if ($mailinfo['system_emails']
                    && ($t = $entry->getThread()->getObject())
                    && $t instanceof Ticket)
                $t->systemReferral($mailinfo['system_emails']);

            return $entry;
        }

        $possibles = array();
        foreach (array('mid', 'in-reply-to', 'references') as $header) {
            $matches = array();
            if (!isset($mailinfo[$header]) || !$mailinfo[$header])
                continue;
            // Header may have multiple entries (usually separated by
            // spaces ( )
            elseif (!preg_match_all('/<([^>@]+@[^>]+)>/', $mailinfo[$header],
                        $matches))
                continue;

            // The References header will have the most recent message-id
            // (parent) on the far right.
            // @see rfc 1036, section 2.2.5
            // @see http://www.jwz.org/doc/threading.html
            $possibles = array_merge($possibles, array_reverse($matches[1]));
        }

        // Add the message id if it is embedded in the body
        $match = array();
        if (preg_match('`(?:class="mid-|Ref-Mid: )([^"\s]*)(?:$|")`',
                (string) $mailinfo['message'], $match)
            && !in_array($match[1], $possibles)
        ) {
            $possibles[] = $match[1];
        }

        $thread = null;
        foreach ($possibles as $mid) {
            // Attempt to detect the ticket and user ids from the
            // message-id header. If the message originated from
            // osTicket, the Mailer class can break it apart. If it came
            // from this help desk, the 'loopback' property will be set
            // to true.
            $mid_info = Mailer::decodeMessageId($mid);
            if (!$mid_info || !$mid_info['loopback'])
                continue;
            if (isset($mid_info['uid'])
                && @$mid_info['entryId']
                && ($t = ThreadEntry::lookup($mid_info['entryId']))
                && ($t->thread_id == $mid_info['threadId'])
            ) {
                if (@$mid_info['userId']) {
                    $mailinfo['userId'] = $mid_info['userId'];

                    $user = User::lookupByEmail($mailinfo['email']);
                    if ($user && $mailinfo['userId'] != $user->getId())
                      $mailinfo['userId'] = $user->getId();
                }
                elseif (@$mid_info['staffId']) {
                    $mailinfo['staffId'] = $mid_info['staffId'];

                    $staffId = Staff::getIdByEmail($mailinfo['email']);
                    if ($staffId && $mailinfo['staffId'] != $staffId)
                      $mailinfo['staffId'] = $staffId;
                }

                // Capture the user type
                if (@$mid_info['userClass'])
                    $mailinfo['userClass'] = $mid_info['userClass'];


                // ThreadEntry was positively identified
                return $t;
            }
        }
        // Passive threading - listen mode
        if (count($possibles)
                && ($entry = ThreadEntry::objects()
                    ->filter(array('email_info__mid__in' => array_map(
                        function ($a) { return "<$a>"; },
                    $possibles)))
                    ->first()
                )
         ) {
            $mailinfo['passive'] = true;
            return $entry;
        }

        // Search for ticket by the [#123456] in the subject line
        // This is the last resort -  emails must match to avoid message
        // injection by third-party.
        $subject = $mailinfo['subject'];
        $match = array();
        if ($subject
                && $mailinfo['email']
                // Required `#` followed by one or more of
                //      punctuation (-) then letters, numbers, and symbols
                // (Try not to match closing punctuation (`]`) in [#12345])
                && preg_match("/#((\p{P}*[^\p{C}\p{Z}\p{P}]+)+)/u", $subject, $match)
                //Lookup by ticket number
                && ($ticket = Ticket::lookupByNumber($match[1]))
                //Lookup the user using the email address
                && ($user = User::lookup(array('emails__address' => $mailinfo['email'])))) {
            //We have a valid ticket and user
            if ($ticket->getUserId() == $user->getId() //owner
                    ||  ($c = Collaborator::lookup( // check if collaborator
                            array('user_id' => $user->getId(),
                                  'thread_id' => $ticket->getThreadId())))) {

                $mailinfo['userId'] = $user->getId();
                return $ticket->getLastMessage();
            }
        }

        return null;
    }

    /**
     * Find a thread entry from a message-id created from the
     * ::asMessageId() method.
     *
     * *DEPRECATED* use Mailer::decodeMessageId() instead
     */
    function lookupByRefMessageId($mid, $from) {
        global $ost;

        $mid = trim($mid, '<>');
        list($ver, $ids, $mails) = explode('$', $mid, 3);

        // Current version is <null>
        if ($ver !== '')
            return false;

        $ids = @unpack('Vthread', base64_decode($ids));
        if (!$ids || !$ids['thread'])
            return false;

        $entry = ThreadEntry::lookup($ids['thread']);
        if (!$entry)
            return false;

        // Compute the value to be compared from $mails (which used to be in
        // ThreadEntry::asMessageId() (#nolint)
        $domain = md5($ost->getConfig()->getURL());
        $ticket = $entry->getThread()->getObject();
        if (!$ticket instanceof Ticket)
            return false;

        $check = sprintf('%s@%s',
            substr(md5($from . $ticket->getNumber() . $ticket->getId()), -10),
            substr($domain, -10)
        );

        if ($check != $mails)
            return false;

        return $entry;
    }

    function setExtra($entries, $info=NULL, $thread_id=NULL) {
        foreach ($entries as $entry) {
            $mergeInfo = new ThreadEntryMergeInfo(array(
                'thread_entry_id' => $entry->getId(),
                'data' => json_encode($info),
            ));
            $mergeInfo->save();
            $entry->saveExtra($info, $thread_id);
        }

    }

    function saveExtra($info=NULL, $thread_id=NULL) {
        $this->setFlag(ThreadEntry::FLAG_CHILD, true);
        $this->thread_id = $thread_id;
        $this->save();
    }

    function getMergeData() {
        return $this->merge_info ? $this->merge_info->data : null;
    }

    function sortEntries($entries, $ticket) {
        $buckets = array();
        $childEntries = array();
        foreach ($entries as $i=>$E) {
            if ($ticket) {
                $extra = json_decode($E->getMergeData(), true);
                //separated entries
                if ($ticket->getMergeType() == 'separate') {
                    if ($extra['thread']) {
                        $childEntries[$E->getId()] = $E;
                        if ($childEntries) {
                            uasort($childEntries, function ($a, $b) { //sort by child ticket
                                $aExtra = json_decode($a->getMergeData(), true);
                                $bExtra = json_decode($b->getMergeData(), true);
                                if ($aExtra['thread'] != $bExtra["thread"])
                                    return $bExtra["thread"] - $aExtra['thread'];
                            });
                            uasort($childEntries, function($a, $b) { //sort by child created date
                                $aExtra = json_decode($a->getMergeData(), true);
                                $bExtra = json_decode($b->getMergeData(), true);
                                if ($aExtra['thread'] == $bExtra["thread"])
                                    return strtotime($a->created) - strtotime($b->created);
                            });
                        }
                    } else
                        $buckets[$E->getId()] = $E;
                } else
                    $buckets[$E->getId()] = $E;
            } else //we may be looking at a task
                $buckets[$E->getId()] = $E;
        }

        if ($ticket && $ticket->getMergeType() == 'separate')
            $buckets = $buckets + $childEntries;

        return $buckets;
    }

    //new entry ... we're trusting the caller to check validity of the data.
    static function create($vars=false) {
        global $cfg;

        assert(is_array($vars));

        //Must have...
        if (!$vars['threadId'] || !$vars['type'])
            return false;

        if (!$vars['body'] instanceof ThreadEntryBody) {
            if ($cfg->isRichTextEnabled())
                $vars['body'] = new HtmlThreadEntryBody($vars['body']);
            else
                $vars['body'] = new TextThreadEntryBody($vars['body']);
        }

        if (!($body = Format::strip_emoticons($vars['body']->getClean())))
            $body = '-'; //Special tag used to signify empty message as stored.

        $poster = $vars['poster'];
        if ($poster && is_object($poster))
            $poster = (string) $poster;

        $entry = new static(array(
            'created' => SqlFunction::NOW(),
            'type' => $vars['type'],
            'thread_id' => $vars['threadId'],
            'title' => Format::sanitize($vars['title'], true),
            'format' => $vars['body']->getType(),
            'staff_id' => $vars['staffId'],
            'user_id' => $vars['userId'],
            'poster' => $poster,
            'source' => $vars['source'],
            'flags' => $vars['flags'] ?: 0,
        ));

        //add recipients to thread entry
        if ($vars['thread_entry_recipients']) {
            $count = 0;
            foreach ($vars['thread_entry_recipients'] as $key => $value)
                $count = $count + count($value);

            if ($count > 1)
                $entry->flags |= ThreadEntry::FLAG_REPLY_ALL;
            else
                $entry->flags |= ThreadEntry::FLAG_REPLY_USER;

            $entry->recipients = json_encode($vars['thread_entry_recipients']);
        }


        if (Collaborator::getIdByUserId($vars['userId'], $vars['threadId']))
          $entry->flags |= ThreadEntry::FLAG_COLLABORATOR;

        if ($entry->format == 'html')
            // The current codebase properly balances html
            $entry->flags |= self::FLAG_BALANCED;

        // Flag system messages
        if (!($vars['staffId'] || $vars['userId']))
            $entry->flags |= self::FLAG_SYSTEM;

        if (isset($vars['pid']))
            $entry->pid = $vars['pid'];
        // Check if 'reply_to' is in the $vars as the previous ThreadEntry
        // instance. If the body of the previous message is found in the new
        // body, strip it out.
        elseif (isset($vars['reply_to'])
                && $vars['reply_to'] instanceof ThreadEntry)
            $entry->pid = $vars['reply_to']->getId();

        if ($vars['ip_address'])
            $entry->ip_address = $vars['ip_address'];

        /************* ATTACHMENTS *****************/
        // Drop stripped email inline images
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

        $attached_files = array();
        foreach (array(
            // Web uploads and canned attachments
            $vars['files'],
            // Emailed or API attachments
            $vars['attachments'],
            // Inline images (attached to the draft)
            Draft::getAttachmentIds($body),
        ) as $files
        ) {
            if (is_array($files)) {
                // Detect *inline* email attachments
                foreach ($files as $i=>$a) {
                    if (isset($a['cid']) && $a['cid']
                            && strpos($body, 'cid:'.$a['cid']) !== false)
                        $files[$i]['inline'] = true;
                }
                foreach ($entry->normalizeFileInfo($files) as $F) {
                    // Deduplicate on the `key` attribute. The key is
                    // necessary for the CID rewrite below
                    $attached_files[$F['key']] = $F;
                }
            }
        }

        // Change <img src="cid:"> inside the message to point to a unique
        // hash-code for the attachment. Since the content-id will be
        // discarded, only the unique hash-code (key) will be available to
        // retrieve the image later
        foreach ($attached_files as $key => $a) {
            if (isset($a['cid']) && $a['cid']) {
                $body = preg_replace('/src=("|\'|\b)(?:cid:)?'
                    . preg_quote($a['cid'], '/').'\1/i',
                    'src="cid:'.$key.'"', $body);
            }
        }

        // Set body here after it was rewritten to capture the stored file
        // keys (above)

        // Store body as an attachment if bigger than allowed packet size
        if (mb_strlen($body) >= 65000) { // 65,535 chars in text field.
             $entry->body = NULL;
             $file = array(
                     'type' => 'text/html',
                     'name' => md5($body).'.txt',
                     'data' => $body,
                     );

             if (($AF = AttachmentFile::create($file))) {
                 $attached_files[$file['key']] = array(
                         'id' => $AF->getId(),
                         'inline' => true,
                         'file' => $AF);
             } else {
                 $entry->body = $body;
             }
        } else {
            $entry->body = $body;

        }

        if (!$entry->save(true))
            return false;

        // Associate the attached files with this new entry
        $entry->createAttachments($attached_files);


        // Save mail message id, if available
        $entry->saveEmailInfo($vars);

        Signal::send('threadentry.created', $entry);

        return $entry;
    }

    static function add($vars, &$errors=array()) {
        return self::create($vars);
    }

    // Extensible thread entry actions ------------------------
    /**
     * getActions
     *
     * Retrieve a list of possible actions. This list is shown to the agent
     * via drop-down list at the top-right of the thread entry when rendered
     * in the UI.
     */
    function getActions() {
        if (!isset($this->_actions)) {
            $this->_actions = array();

            foreach (self::$action_registry as $group=>$list) {
                $T = array();
                $this->_actions[__($group)] = &$T;
                foreach ($list as $id=>$action) {
                    $A = new $action($this);
                    if ($A->isVisible()) {
                        $T[$id] = $A;
                    }
                }
                unset($T);
            }
        }
        return $this->_actions;
    }

    function hasActions() {
        foreach ($this->getActions() as $group => $list) {
            if (count($list))
                return true;
        }
        return false;
    }

    function triggerAction($name) {
        foreach ($this->getActions() as $group=>$list) {
            foreach ($list as $id=>$action) {
                if (0 === strcasecmp($id, $name)) {
                    if (!$action->isEnabled())
                        return false;

                    $action->trigger();
                    return true;
                }
            }
        }
        return false;
    }

    static $action_registry = array();

    static function registerAction($group, $action) {
        if (!isset(self::$action_registry[$group]))
            self::$action_registry[$group] = array();

        self::$action_registry[$group][$action::getId()] = $action;
    }

    static function getPermissions() {
        return self::$perms;
    }

    static function getTypes() {
        return self::$types;
    }
}

RolePermission::register(/* @trans */ 'Tickets', ThreadEntry::getPermissions());


class ThreadReferral extends VerySimpleModel {
    static $meta = array(
        'table' => THREAD_REFERRAL_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'thread' => array(
                'constraint' => array('thread_id' => 'Thread.id'),
            ),
            'agent' => array(
                'constraint' => array(
                    'object_type' => "'S'",
                    'object_id' => 'Staff.staff_id',
                ),
            ),
            'team' => array(
                'constraint' => array(
                    'object_type' => "'E'",
                    'object_id' => 'Team.team_id',
                ),
            ),
            'dept' => array(
                'constraint' => array(
                    'object_type' => "'D'",
                    'object_id' => 'Dept.id',
                ),
            ),
          )
        );

    var $icons = array(
            'E' => 'group',
            'D' => 'sitemap',
            'S' => 'user'
            );

    var $_object = null;

    function getId() {
        return $this->id;
    }

    function getName() {
        return (string) $this->getObject();
    }

    function getObject() {

        if (!isset($this->_object)) {
            $this->_object = ObjectModel::lookup(
                    $this->object_id, $this->object_type);
        }

        return $this->_object;
    }

    function getIcon() {
        return $this->icons[$this->object_type];
    }

    function display() {
        return sprintf('<i class="icon-%s"></i> %s',
                $this->getIcon(), $this->getName());
    }

    static function create($vars) {

        $new = new self($vars);
        $new->created = SqlFunction::NOW();
        return $new->save();
    }
}

class ThreadEvent extends VerySimpleModel {
    static $meta = array(
        'table' => THREAD_EVENT_TABLE,
        'pk' => array('id'),
        'joins' => array(
            // Originator of activity
            'agent' => array(
                'constraint' => array(
                    'uid' => 'Staff.staff_id',
                ),
                'null' => true,
            ),
            // Agent assignee
            'staff' => array(
                'constraint' => array(
                    'staff_id' => 'Staff.staff_id',
                ),
                'null' => true,
            ),
            'team' => array(
                'constraint' => array(
                    'team_id' => 'Team.team_id',
                ),
                'null' => true,
            ),
            'thread' => array(
                'constraint' => array('thread_id' => 'Thread.id'),
            ),
            'user' => array(
                'constraint' => array(
                    'uid' => 'User.id',
                ),
                'null' => true,
            ),
            'dept' => array(
                'constraint' => array(
                    'dept_id' => 'Dept.id',
                ),
                'null' => true,
            ),
            'topic' => array(
                'constraint' => array(
                    'topic_id' => 'Topic.topic_id',
                ),
                'null' => true,
            ),
        ),
    );

    // Valid events for database storage
    const ASSIGNED  = 'assigned';
    const RELEASED  = 'released';
    const CLOSED    = 'closed';
    const CREATED   = 'created';
    const COLLAB    = 'collab';
    const EDITED    = 'edited';
    const ERROR     = 'error';
    const OVERDUE   = 'overdue';
    const REOPENED  = 'reopened';
    const STATUS    = 'status';
    const TRANSFERRED = 'transferred';
    const REFERRED = 'referred';
    const VIEWED    = 'viewed';
    const MERGED    = 'merged';
    const UNLINKED    = 'unlinked';

    const MODE_STAFF = 1;
    const MODE_CLIENT = 2;

    var $_data;

    function getAvatar($size=null) {
        if ($this->uid && $this->uid_type == 'S')
            return $this->agent ? $this->agent->getAvatar($size) : '';
        if ($this->uid && $this->uid_type == 'U')
            return $this->user ? $this->user->getAvatar($size) : '';
    }

    function getUserName() {
        if ($this->uid && $this->uid_type == 'S')
            return $this->agent ? $this->agent->getName() : $this->username;
        if ($this->uid && $this->uid_type == 'U')
            return $this->user ? $this->user->getName() : $this->username;
        return $this->username;
    }

    function getIcon() {
        $icons = array(
            'assigned'    => 'hand-right',
            'released'    => 'unlock',
            'collab'      => 'group',
            'created'     => 'magic',
            'overdue'     => 'time',
            'transferred' => 'share-alt',
            'referred'    => 'exchange',
            'edited'      => 'pencil',
            'closed'      => 'thumbs-up-alt',
            'reopened'    => 'rotate-right',
            'resent'      => 'reply-all icon-flip-horizontal',
            'merged'      => 'code-fork',
            'linked'      => 'link',
            'unlinked'    => 'unlink',
        );
        return @$icons[$this->state] ?: 'chevron-sign-right';
    }

    function getDescription($mode=self::MODE_STAFF) {
        // Abstract description
        return $this->template(sprintf(
            __('%s by {somebody} {timestamp}'),
            $this->state
        ), $mode);
    }

    function template($description, $mode=self::MODE_STAFF) {
        global $thisstaff, $cfg;

        $self = $this;
        $hideName = $cfg->hideStaffName();
        return preg_replace_callback('/\{(<(?P<type>([^>]+))>)?(?P<key>[^}.]+)(\.(?P<data>[^}]+))?\}/',
            function ($m) use ($self, $thisstaff, $cfg, $hideName, $mode) {
                switch ($m['key']) {
                case 'assignees':
                    $assignees = array();
                    if ($S = $self->staff) {
                        $avatar = '';
                        if ($cfg->isAvatarsEnabled())
                            $avatar = $S->getAvatar();
                        $assignees[] =
                            $avatar.$S->getName();
                    }
                    if ($T = $self->team) {
                        $assignees[] = $T->getLocalName();
                    }
                    return implode('/', $assignees);
                case 'somebody':
                    if ($hideName && $self->agent && $mode == self::MODE_CLIENT)
                        $name = __('Staff');
                    else
                        $name = $self->getUserName();
                    if ($cfg->isAvatarsEnabled()
                            && ($avatar = $self->getAvatar()))
                        $name = $avatar.$name;
                    return $name;
                case 'timestamp':
                    $timeFormat = null;
                    if ($mode != self::MODE_CLIENT && $thisstaff
                            && !strcasecmp($thisstaff->datetime_format,
                                'relative')) {
                        $timeFormat = function ($timestamp) {
                            return Format::relativeTime(Misc::db2gmtime($timestamp));
                        };
                    }

                    return sprintf('<time %s datetime="%s"
                            data-toggle="tooltip" title="%s">%s</time>',
                        $timeFormat ? 'class="relative"' : '',
                        date(DateTime::W3C, Misc::db2gmtime($self->timestamp)),
                        Format::daydatetime($self->timestamp),
                        $timeFormat ? $timeFormat($self->timestamp) :
                        Format::datetime($self->timestamp)
                    );
                case 'agent':
                    $name = $self->agent->getName();
                    if ($cfg->isAvatarsEnabled()
                            && ($avatar = $self->getAvatar()))
                        $name = $avatar.$name;
                    return $name;
                case 'dept':
                    if ($dept = $self->getDept())
                        return $dept->getLocalName();
                    return __('None');
                case 'data':
                    $val = $self->getData($m['data']);
                    if (is_array($val))
                        list($val, $fallback) = $val;
                    if ($m['type'] && class_exists($m['type']))
                        $val = $m['type']::lookup($val);
                    if (!$val && $fallback)
                        $val = $fallback;
                    return Format::htmlchars((string) $val);
                }
                return $m[0];
            },
            $description
        );
    }

    function getDept() {
        return $this->dept;
    }

    function getData($key=false) {
        if (!isset($this->_data))
            $this->_data = JsonDataParser::decode($this->data);
        return ($key) ? @$this->_data[$key] : $this->_data;
    }

    function render($mode) {
        $inc = ($mode == self::MODE_STAFF) ? STAFFINC_DIR : CLIENTINC_DIR;
        $event = $this->getTypedEvent();
        include $inc . 'templates/thread-event.tmpl.php';
    }

    static function create($ht=false, $user=false) {
        $inst = new static($ht);
        $inst->timestamp = SqlFunction::NOW();

        global $thisstaff, $thisclient;
        $user = is_object($user) ? $user : $thisstaff ?: $thisclient;
        if ($user instanceof Staff) {
            $inst->uid_type = 'S';
            $inst->uid = $user->getId();
        }
        elseif ($user instanceof User) {
            $inst->uid_type = 'U';
            $inst->uid = $user->getId();
        }

        return $inst;
    }

    static function forTicket($ticket, $state, $user=false) {
      global $thisstaff;

      if($thisstaff && !$ticket->getStaffId())
        $staff = $thisstaff->getId();
      else
        $staff = $ticket->getStaffId();

        $inst = self::create(array(
            'thread_type' => ObjectModel::OBJECT_TYPE_TICKET,
            'staff_id' => $staff,
            'team_id' => $ticket->getTeamId(),
            'dept_id' => $ticket->getDeptId(),
            'topic_id' => $ticket->getTopicId(),
        ), $user);
        return $inst;
    }

    static function forTask($task, $state, $user=false) {
        $inst = self::create(array(
            'thread_type' => ObjectModel::OBJECT_TYPE_TASK,
            'staff_id' => $task->getStaffId(),
            'team_id' => $task->getTeamId(),
            'dept_id' => $task->getDeptId(),
        ), $user);
        return $inst;
    }

    function getTypedEvent() {
        static $subclasses;

        if (!isset($subclasses)) {
            $parent = get_class($this);
            $subclasses = array();
            foreach (get_declared_classes() as $class) {
                if (is_subclass_of($class, $parent))
                    $subclasses[$class::$state] = $class;
            }
        }
        $this->state = Event::getNameById($this->event_id);
        if (!($class = $subclasses[$this->state]))
            return $this;
        return new $class($this->ht);
    }
}

class Event extends VerySimpleModel {
    static $meta = array(
        'table' => EVENT_TABLE,
        'pk' => array('id'),
    );

    function getInfo() {
        return $this->ht;
    }

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->name;
    }

    function getDescription() {
        return $this->description;
    }

    static function getNameById($id) {
        return array_search($id, self::getIds());
    }

    static function getIdByName($name) {
         $ids =  self::getIds();
         return $ids[$name] ?: 0;
    }

    static function getIds() {
        static $ids;

        if (!isset($ids)) {
            $ids = array();
            $events = self::objects()->values_flat('id', 'name');
            foreach ($events as $row) {
                list($id, $name) = $row;
                $ids[$name] = $id;
            }
        }

        return $ids;
    }

    static function getStates($dropdown=false) {
        $names = array();
        if ($dropdown)
            $names = array(__('All'));

        $events = self::objects()->values_flat('name');
        foreach ($events as $val)
            $names[] = ucfirst($val[0]);

        return $names;
    }

    static function create($vars=false, &$errors=array()) {
        $event = new static($vars);
        return $event;
    }

    static function __create($vars, &$errors=array()) {
        $event = self::create($vars);
        $event->save();
        return $event;
    }

    function save($refetch=false) {
        return parent::save($refetch);
    }
}

class ThreadEvents extends InstrumentedList {
    function annul($event) {
        $event_id = Event::getIdByName($event);
        $this->queryset
            ->filter(array('event_id' => $event_id))
            ->update(array('annulled' => 1));
    }

    /**
     * Add an event to the thread activity log.
     *
     * Parameters:
     * $object - Object to log activity for
     * $state - State name of the activity (one of 'created', 'edited',
     *      'deleted', 'closed', 'reopened', 'error', 'collab', 'resent',
     *      'assigned', 'released', 'transferred')
     * $data - (array?) Details about the state change
     * $user - (string|User|Staff) user triggering the state change
     * $annul - (state) a corresponding state change that is annulled by
     *      this event
     */
    function log($object, $state, $data=null, $user=null, $annul=null) {
        global $thisstaff, $thisclient;

        if ($object && ($object instanceof Ticket))
            // TODO: Use $object->createEvent() (nolint)
            $event = ThreadEvent::forTicket($object, $state, $user);
        elseif ($object && ($object instanceof Task))
            $event = ThreadEvent::forTask($object, $state, $user);

        if (is_null($event))
            return;

        # Annul previous entries if requested (for instance, reopening a
        # ticket will annul an 'closed' entry). This will be useful to
        # easily prevent repeated statistics.
        if ($annul) {
            $this->annul($annul);
        }

        $username = $user;
        $user = is_object($user) ? $user : $thisclient ?: $thisstaff;
        if (!is_string($username)) {
            if ($user instanceof Staff) {
                $username = $user->getUserName();
            }
            // XXX: Use $user here
            elseif ($thisclient) {
                if ($thisclient->hasAccount())
                    $username = $thisclient->getFullName();
                if (!$username)
                    $username = $thisclient->getEmail();
            }
            else {
                # XXX: Security Violation ?
                $username = 'SYSTEM';
            }
        }
        $event->username = $username;
        $event->event_id = Event::getIdByName($state);

        if ($data) {
            if (is_array($data))
                $data = JsonDataEncoder::encode($data);
            if (!is_string($data))
                throw new InvalidArgumentException('Data must be string or array');
            $event->data = $data;
        }

        $this->add($event);

        // Save event immediately
        return $event->save();
    }
}

class AssignmentEvent extends ThreadEvent {
    static $icon = 'hand-right';
    static $state = 'assigned';

    function getDescription($mode=self::MODE_STAFF) {
        $data = $this->getData();
        switch (true) {
        case !is_array($data):
        default:
            $desc = __('Assignee changed by <b>{somebody}</b> to <strong>{assignees}</strong> {timestamp}');
            break;
        case isset($data['staff']):
            $desc = __('<b>{somebody}</b> assigned this to <strong>{<Staff>data.staff}</strong> {timestamp}');
            break;
        case isset($data['team']):
            $desc = __('<b>{somebody}</b> assigned this to <strong>{<Team>data.team}</strong> {timestamp}');
            break;
        case isset($data['claim']):
            $desc = __('<b>{somebody}</b> claimed this {timestamp}');
            break;
        }
        return $this->template($desc, $mode);
    }
}

class ReleaseEvent extends ThreadEvent {
    static $icon = 'unlock';
    static $state = 'released';

    function getDescription($mode=self::MODE_STAFF) {
        $data = $this->getData();
        switch (true) {
        case isset($data['staff'], $data['team']):
            $desc = __('Ticket released from <strong>{<Team>data.team}</strong> and <strong>{<Staff>data.staff}</strong> by <b>{somebody}</b> {timestamp}');
            break;
        case isset($data['staff']):
            $desc = __('Ticket released from <strong>{<Staff>data.staff}</strong> by <b>{somebody}</b> {timestamp}');
            break;
        case isset($data['team']):
            $desc = __('Ticket released from <strong>{<Team>data.team}</strong> by <b>{somebody}</b> {timestamp}');
            break;
        default:
            $desc = __('<b>{somebody}</b> released ticket assignment {timestamp}');
            break;
        }
        return $this->template($desc, $mode);
    }
}

class ReferralEvent extends ThreadEvent {
    static $icon = 'exchange';
    static $state = 'referred';

    function getDescription($mode=self::MODE_STAFF) {
        $data = $this->getData();
        switch (true) {
        case isset($data['staff']):
            $desc = __('<b>{somebody}</b> referred this to <strong>{<Staff>data.staff}</strong> {timestamp}');
            break;
        case isset($data['team']):
            $desc = __('<b>{somebody}</b> referred this to <strong>{<Team>data.team}</strong> {timestamp}');
            break;
        case isset($data['dept']):
            $desc = __('<b>{somebody}</b> referred this to <strong>{<Dept>data.dept}</strong> {timestamp}');
            break;
        }
        return $this->template($desc, $mode);
    }
}

class CloseEvent extends ThreadEvent {
    static $icon = 'thumbs-up-alt';
    static $state = 'closed';

    function getDescription($mode=self::MODE_STAFF) {
        if ($this->getData('status'))
            return $this->template(__('Closed by <b>{somebody}</b> with status of {<TicketStatus>data.status} {timestamp}'), $mode);
        else
            return $this->template(__('Closed by <b>{somebody}</b> {timestamp}'), $mode);
    }
}

class CollaboratorEvent extends ThreadEvent {
    static $icon = 'group';
    static $state = 'collab';

    function getDescription($mode=self::MODE_STAFF) {
        $data = $this->getData();
        switch (true) {
        case isset($data['org']):
            $desc = __('Collaborators for {<Organization>data.org} organization added');
            break;
        case isset($data['del']):
            $base = __('<b>{somebody}</b> removed <strong>%s</strong> from the collaborators {timestamp}');
            $collabs = array();
            $users = User::objects()->filter(array('id__in' => array_keys($data['del'])));
            foreach ($data['del'] as $id=>$c) {
                $U = false;
                foreach ($users as $user) {
                    if ($user->id == $id) {
                        $U = $user;
                        break;
                    }
                }
                $collabs[] = Format::htmlchars($U ? $U->getName() : @$c['name'] ?: $c);
            }
            $desc = sprintf($base, implode(', ', $collabs));
            break;
        case isset($data['add']):
            $base = __('<b>{somebody}</b> added <strong>%s</strong> as collaborators {timestamp}');
            $collabs = array();
            if ($data['add']) {
                $users = User::objects()->filter(array('id__in' => array_keys($data['add'])));
                foreach ($data['add'] as $id=>$c) {
                    $U = false;
                    foreach ($users as $user) {
                        if ($user->id == $id) {
                            $U = $user;
                            break;
                        }
                    }
                    $c = sprintf("%s %s",
                        Format::htmlchars($U ? $U->getName() : @$c['name'] ?: $c),
                        $c['src'] ? sprintf(__('via %s'
                            /* e.g. "Added collab "Me <me@company.me>" via Email (to)" */
                            ), $c['src']) : ''
                    );
                    $collabs[] = $c;
                }
            }
            $desc = $collabs
                ? sprintf($base, implode(', ', $collabs))
                : 'somebody';
            break;
        }
        return $this->template($desc, $mode);
    }
}

class CreationEvent extends ThreadEvent {
    static $icon = 'magic';
    static $state = 'created';

    function getDescription($mode=self::MODE_STAFF) {
        return $this->template(__('Created by <b>{somebody}</b> {timestamp}'), $mode);
    }
}

class EditEvent extends ThreadEvent {
    static $icon = 'pencil';
    static $state = 'edited';

    function getDescription($mode=self::MODE_STAFF) {
        $data = $this->getData();
        switch (true) {
        case isset($data['owner']):
            $desc = __('<b>{somebody}</b> changed ownership to {<User>data.owner} {timestamp}');
            break;
        case isset($data['status']):
            $desc = __('<b>{somebody}</b> changed the status to <strong>{<TicketStatus>data.status}</strong> {timestamp}');
            break;
        case isset($data['fields']):
            $fields = $changes = array();
            foreach (DynamicFormField::objects()->filter(array(
                'id__in' => array_keys($data['fields'])
            )) as $F) {
                $fields[$F->id] = $F;
            }
            foreach ($data['fields'] as $id=>$f) {
                if (!($field = $fields[$id]))
                   continue;
                if ($mode == self::MODE_CLIENT &&  !$field->isVisibleToUsers())
                    continue;
                list($old, $new) = $f;
                $impl = $field->getImpl($field);
                $before = $impl->to_php($old);
                $after = $impl->to_php($new);
                $changes[] = sprintf('<strong>%s</strong> %s',
                    $field->getLocal('label'), $impl->whatChanged($before, $after));
            }
            // Fallthrough to other editable fields
        case isset($data['topic_id']):
        case isset($data['sla_id']):
        case isset($data['source']):
        case isset($data['user_id']):
        case isset($data['duedate']):
            $base = __('Updated by <b>{somebody}</b> {timestamp} — %s');
            foreach (array(
                'topic_id' => array(__('Help Topic'), array('Topic', 'getTopicName')),
                'sla_id' => array(__('SLA'), array('SLA', 'getSLAName')),
                'duedate' => array(__('Due Date'), array('Format', 'date')),
                'user_id' => array(__('Ticket Owner'), array('User', 'getNameById')),
                'source' => array(__('Source'), null)
            ) as $f => $info) {
                if (isset($data[$f])) {
                    list($name, $desc) = $info;
                    list($old, $new) = $data[$f];
                    if ($desc && is_callable($desc)) {
                        $new = call_user_func($desc, $new);
                        if ($old)
                            $old = call_user_func($desc, $old);
                    }
                    if ($old and $new) {
                        $changes[] = sprintf(
                            __('<strong>%1$s</strong> changed from <strong>%2$s</strong> to <strong>%3$s</strong>'),
                            Format::htmlchars($name), Format::htmlchars($old), Format::htmlchars($new)
                        );
                    }
                    elseif ($new) {
                        $changes[] = sprintf(
                            __('<strong>%1$s</strong> set to <strong>%2$s</strong>'),
                            Format::htmlchars($name), Format::htmlchars($new)
                        );
                    }
                    else {
                        $changes[] = sprintf(
                            __('unset <strong>%1$s</strong>'),
                            Format::htmlchars($name)
                        );
                    }
                }
            }
            $desc = $changes
                ? sprintf($base, implode(', ', $changes)) : '';
            break;
        }

        return $this->template($desc, $mode);
    }
}

class OverdueEvent extends ThreadEvent {
    static $icon = 'time';
    static $state = 'overdue';

    function getDescription($mode=self::MODE_STAFF) {
        return $this->template(__('Flagged as overdue by the system {timestamp}'), $mode);
    }
}

class ReopenEvent extends ThreadEvent {
    static $icon = 'rotate-right';
    static $state = 'reopened';

    function getDescription($mode=self::MODE_STAFF) {
        return $this->template(__('Reopened by <b>{somebody}</b> {timestamp}'), $mode);
    }
}

class ResendEvent extends ThreadEvent {
    static $icon = 'reply-all icon-flip-horizontal';
    static $state = 'resent';

    function getDescription($mode=self::MODE_STAFF) {
        return $this->template(__('<b>{somebody}</b> resent <strong><a href="#thread-entry-{data.entry}">a previous response</a></strong> {timestamp}'), $mode);
    }
}

class TransferEvent extends ThreadEvent {
    static $icon = 'share-alt';
    static $state = 'transferred';

    function getDescription($mode=self::MODE_STAFF) {
        return $this->template(__('<b>{somebody}</b> transferred this to <strong>{dept}</strong> {timestamp}'), $mode);
    }
}

class ViewEvent extends ThreadEvent {
    static $state = 'viewed';
}

class MergedEvent extends ThreadEvent {
    static $icon = 'code-fork';
    static $state = 'merged';

    function getDescription($mode=self::MODE_STAFF) {
        return sprintf($this->template(__('<b>{somebody}</b> merged this ticket with %s{data.id}%s<b>{data.ticket}</b>%s {timestamp}'), $mode),
                '<a href="tickets.php?id=', '">', '</a>');
    }
}

class LinkedEvent extends ThreadEvent {
    static $icon = 'link';
    static $state = 'linked';

    function getDescription($mode=self::MODE_STAFF) {
        return sprintf($this->template(__('<b>{somebody}</b> linked this ticket with %s{data.id}%s<b>{data.ticket}</b>%s {timestamp}'), $mode),
                '<a href="tickets.php?id=', '">', '</a>');
    }
}

class UnlinkEvent extends ThreadEvent {
    static $icon = 'unlink';
    static $state = 'unlinked';

    function getDescription($mode=self::MODE_STAFF) {
        return sprintf($this->template(__('<b>{somebody}</b> unlinked this ticket from %s{data.id}%s<b>{data.ticket}</b>%s {timestamp}'), $mode),
                '<a href="tickets.php?id=', '">', '</a>');
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
        switch ($this->type) {
        case 'html':
            return trim($this->body, " <>br/\t\n\r") ? $this->body: '';
        case 'text':
            return trim($this->body) ? $this->body: '';
        default:
            return trim($this->body);
        }
    }

    function __toString() {
        return (string) $this->body;
    }

    function toHtml() {
        return $this->display('html');
    }

    function prepend($what) {
        $this->body = $what . $this->body;
    }

    function append($what) {
        $this->body .= $what;
    }

    function asVar() {
        // Email template, assume HTML
        return $this->display('email');
    }

    function display($format=false) {
        throw new Exception('display: Abstract display() method not implemented');
    }

    function getSearchable() {
        return Format::searchable($this->body);
    }

    static function fromFormattedText($text, $format=false, $options=array()) {
        switch ($format) {
        case 'text':
            return new TextThreadEntryBody($text);
        case 'html':
            return new HtmlThreadEntryBody($text, array('strip-embedded'=>false) + $options);
        default:
            return new ThreadEntryBody($text);
        }
    }

    static function clean($text, $format=null) {
        global $cfg;
        $format = $format ?: ($cfg->isRichTextEnabled() ? 'html' : 'text');
        $body = static::fromFormattedText($text, $format);
        return $body->getClean();
    }
}

class TextThreadEntryBody extends ThreadEntryBody {
    function __construct($body, $options=array()) {
        parent::__construct($body, 'text', $options);
    }

    function getClean() {
        return Format::htmlchars(Format::html_balance(Format::stripEmptyLines(parent::getClean())));
    }

    function prepend($what) {
        $this->body = $what . "\n\n" . $this->body;
    }

    function display($output=false) {
        if ($this->isEmpty())
            return '(empty)';

        $escaped = Format::htmlchars($this->body);
        switch ($output) {
        case 'html':
            return '<div style="white-space:pre-wrap">'
                .Format::clickableurls($escaped).'</div>';
        case 'email':
            return '<div style="white-space:pre-wrap">'
                .$escaped.'</div>';
        case 'pdf':
            return nl2br($escaped);
        default:
            return '<pre>'.$escaped.'</pre>';
        }
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
        return Format::sanitize(parent::getClean());
    }

    function getSearchable() {
        // Replace tag chars with spaces (to ensure words are separated)
        $body = Format::html($this->body, array('hook_tag' => function($el, $attributes=0) {
            static $non_ws = array('wbr' => 1);
            return (isset($non_ws[$el])) ? '' : ' ';
        }));
        // Collapse multiple white-spaces
        $body = html_entity_decode($body, ENT_QUOTES);
        $body = preg_replace('`\s+`u', ' ', $body);
        return Format::searchable($body);
    }

    function prepend($what) {
        $this->body = sprintf('<div>%s<br/><br/></div>%s', $what, $this->body);
    }

    function display($output=false) {
        if ($this->isEmpty())
            return '(empty)';

        switch ($output) {
        case 'email':
            return $this->body;
        case 'pdf':
            return Format::clickableurls($this->body);
        default:
            return Format::display($this->body, true, !$this->options['balanced']);
        }
    }
}


/* Message - Ticket thread entry of type message */
class MessageThreadEntry extends ThreadEntry {

    const ENTRY_TYPE = 'M';

    function getSubject() {
        return $this->getTitle();
    }

    static function add($vars, &$errors=array()) {

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

    static function getVarScope() {
        $base = parent::getVarScope();
        unset($base['staff']);
        return $base;
    }
}

/* thread entry of type response */
class ResponseThreadEntry extends ThreadEntry {

    const ENTRY_TYPE = 'R';

    function getActivity() {
        return new ThreadActivity(
                _S('New Response'),
                _S('New response posted'));
    }

    function getSubject() {
        return $this->getTitle();
    }

    function getRespondent() {
        return $this->getStaff();
    }

    static function add($vars, &$errors=array()) {

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

    static function getVarScope() {
        $base = parent::getVarScope();
        unset($base['user']);
        return $base;
    }
}

/* Thread entry of type note (Internal Note) */
class NoteThreadEntry extends ThreadEntry {
    const ENTRY_TYPE = 'N';

    function getMessage() {
        return $this->getBody();
    }

    function getActivity() {
        return new ThreadActivity(
                _S('New Internal Note'),
                _S('New internal note posted'));
    }

    static function add($vars, &$errors=array()) {

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

    static function getVarScope() {
        $base = parent::getVarScope();
        unset($base['user']);
        return $base;
    }
}

// Object specific thread utils.
class ObjectThread extends Thread
implements TemplateVariable {
    static $types = array(
        ObjectModel::OBJECT_TYPE_TASK => 'TaskThread',
        ObjectModel::OBJECT_TYPE_TICKET => 'TicketThread',
    );

    var $counts;

    function getCounts() {
        if (!isset($this->counts) && $this->getId()) {
            $this->counts = array();

            $stuff = $this->entries
                ->values_flat('type')
                ->annotate(array(
                    'count' => SqlAggregate::COUNT('id')
                ));

            foreach ($stuff as $row) {
                list($type, $count) = $row;
                $this->counts[$type] = $count;
            }
        }
        return $this->counts;
    }

    function getNumMessages() {
        $this->getCounts();
        return $this->counts[MessageThreadEntry::ENTRY_TYPE];
    }

    function getNumResponses() {
        $this->getCounts();
        return $this->counts[ResponseThreadEntry::ENTRY_TYPE];
    }

    function getNumNotes() {
        $this->getCounts();
        return $this->counts[NoteThreadEntry::ENTRY_TYPE];
    }


    function getLastMessage($criteria=false) {
        $entries = clone $this->getEntries();
        $entries->filter(array(
            'type' => MessageThreadEntry::ENTRY_TYPE
        ));

        if ($criteria)
            $entries->filter($criteria);

        $entries->order_by('-id');

        return $entries->first();
    }

    function getLastEmailMessage($criteria=array()) {

        $criteria += array(
                'source' => 'Email',
                'email_info__headers__isnull' => false);

        return $this->getLastMessage($criteria);
    }

    function getLastEmailMessageByUser($user) {

        $uid = is_numeric($user) ? $user : 0;
        if (!$uid && ($user instanceof EmailContact))
            $uid = $user->getUserId();

        return $uid
                ? $this->getLastEmailMessage(array('user_id' => $uid))
                : null;
    }

    function getEntry($criteria) {
        // XXX: PUNT
        if (is_numeric($criteria))
            return parent::getEntry($criteria);

        $entries = clone $this->getEntries();
        $entries->filter($criteria);
        return $entries->first();
    }

    function getMessages() {
        $entries = clone $this->getEntries();
        return $entries->filter(array(
            'type' => MessageThreadEntry::ENTRY_TYPE
        ));
    }

    function getResponses() {
        $entries = clone $this->getEntries();
        return $entries->filter(array(
            'type' => ResponseThreadEntry::ENTRY_TYPE
        ));
    }

    function getNotes() {
        $entries = clone $this->getEntries();
        return $entries->filter(array(
            'type' => NoteThreadEntry::ENTRY_TYPE
        ));
    }

    function addNote($vars, &$errors=array()) {
        //Add ticket Id.
        $vars['threadId'] = $this->getId();
        return NoteThreadEntry::add($vars, $errors);
    }

    function addMessage($vars, &$errors) {
        $vars['threadId'] = $this->getId();
        $vars['staffId'] = 0;

        if (!($message = MessageThreadEntry::add($vars, $errors)))
            return $message;

        $this->lastmessage = SqlFunction::NOW();
        $this->save(true);
        return $message;
    }

    function addResponse($vars, &$errors) {
        $vars['threadId'] = $this->getId();
        $vars['userId'] = 0;
        if ($message = $this->getLastMessage())
            $vars['pid'] = $message->getId();

        $vars['flags'] = 0;

        if (!($resp = ResponseThreadEntry::add($vars, $errors)))
            return $resp;

        $this->lastresponse = SqlFunction::NOW();
        $this->save(true);
        return $resp;
    }

    function __toString() {
        return $this->asVar();
    }

    function asVar() {
        return new ThreadEntries($this);
    }

    function getVar($name) {
        switch ($name) {
        case 'original':
            $entry = $this->entries->filter(array(
                'type' => MessageThreadEntry::ENTRY_TYPE,
                'flags__hasbit' => ThreadEntry::FLAG_ORIGINAL_MESSAGE,
                ))
                ->order_by('id')
                ->first();
            if ($entry)
                return $entry->getBody();

            break;
        case 'last_message':
        case 'lastmessage':
            $entry = $this->getLastMessage();
            if ($entry)
                return $entry->getBody();

            break;
        case 'complete':
            return $this->asVar();
            break;
        }
    }

    static function getVarScope() {
      return array(
        'complete' =>array('class' => 'ThreadEntries', 'desc' => __('Thread Correspondence')),
        'original' => array('class' => 'MessageThreadEntry', 'desc' => __('Original Message')),
        'lastmessage' => array('class' => 'MessageThreadEntry', 'desc' => __('Last Message')),
      );
    }

    static function lookup($criteria, $type=false) {
        if (!$type)
            return parent::lookup($criteria);

        $class = false;
        if (isset(self::$types[$type]))
            $class = self::$types[$type];
        if (!class_exists($class))
            $class = get_called_class();

        return $class::lookup($criteria);
    }
}

class ThreadEntries {
    var $thread;

    function __construct($thread) {
        $this->thread = $thread;
    }

    function __tostring() {
        return (string) $this->getVar();
    }

    function asVar() {
        return $this->getVar();
    }

    function getVar($name='') {

        $order = '';
        switch ($name) {
        case 'reversed':
            $order = '-';
        default:
            $content = '';
            $thread = $this->thread;
            ob_start();
            include INCLUDE_DIR.'client/templates/thread-export.tmpl.php';
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
            break;
        }
    }

    static function getVarScope() {
      return array(
        'reversed' => sprintf('%s %s', __('Thread Correspondence'),
            __('in reversed order')),
      );
    }
}

// Ticket thread class
class TicketThread extends ObjectThread {
    static function create($ticket=false) {
        assert($ticket !== false);

        $id = is_object($ticket) ? $ticket->getId() : $ticket;
        $thread = parent::create(array(
                    'object_id' => $id,
                    'object_type' => ObjectModel::OBJECT_TYPE_TICKET
                    ));
        if ($thread->save())
            return $thread;
    }
}

/**
 * Class: ThreadEntryAction
 *
 * Defines a simple action to be performed on a thread entry item, such as
 * viewing the raw email headers used to generate the message, resend the
 * confirmation emails, etc.
 */
abstract class ThreadEntryAction {
    static $name;               // Friendly, translatable name
    static $id;                 // Unique identifier used for plumbing
    static $icon = 'cog';

    var $entry;

    function getName() {
        $class = get_class($this);
        return __($class::$name);
    }

    static function getId() {
        return static::$id;
    }

    function getIcon() {
        $class = get_class($this);
        return 'icon-' . $class::$icon;
    }

    function getObJectId() {
        return $this->entry->getThread()->getObjectId();
    }

    function __construct(ThreadEntry $thread) {
        $this->entry = $thread;
    }

    abstract function trigger();

    function isEnabled() {
        return $this->isVisible();
    }
    function isVisible() {
        return true;
    }

    /**
     * getJsStub
     *
     * Retrieves a small JavaScript snippet to insert into the rendered page
     * which should, via an AJAX callback, trigger this action to be
     * performed. The URL for this sort of activity is already provided for
     * you via the ::getAjaxUrl() method in this class.
     */
    abstract function getJsStub();

    /**
     * getAjaxUrl
     *
     * Generate a URL to be used as an AJAX callback. The URL can be used to
     * trigger this thread entry action via the callback.
     *
     * Parameters:
     * $dialog - (bool) used in conjunction with `$.dialog()` javascript
     *      function which assumes the `ajax.php/` should be replace a leading
     *      `#` in the url
     */
    function getAjaxUrl($dialog=false) {
        return sprintf('%s%s/%d/thread/%d/%s',
            $dialog ? '#' : 'ajax.php/',
            $this->entry->getThread()->getObjectType() == 'T' ? 'tickets' : 'tasks',
            $this->entry->getThread()->getObjectId(),
            $this->entry->getId(),
            static::getId()
        );
    }

    function getTicketsAPI() {
        return new TicketsAjaxAPI();
    }

    function getTasksAPI() {
        return new TasksAjaxAPI();
    }
}

interface Threadable {
    function getThreadId();
    function getThread();
    function postThreadEntry($type, $vars, $options=array());
}

/**
 * ThreadActivity
 *
 * Object to thread activity
 *
 */
class ThreadActivity implements TemplateVariable {
    var $title;
    var $desc;

    function __construct($title, $desc) {
        $this->title = $title;
        $this->desc = $desc;
    }

    function getTitle() {
        return $this->title;
    }

    function getDescription() {
        return $this->desc;
    }
    function asVar() {
        return (string) $this->getTitle();
    }

    function getVar($tag) {
        if ($tag && is_callable(array($this, 'get'.ucfirst($tag))))
            return call_user_func(array($this, 'get'.ucfirst($tag)));

        return false;
    }

    static function getVarScope() {
        return array(
          'title' => __('Activity Title'),
          'description' => __('Activity Description'),
        );
    }
}

?>
