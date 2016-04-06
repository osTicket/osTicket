<?php
/*********************************************************************
    class.collaborator.php

    Ticket collaborator

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once(INCLUDE_DIR . 'class.user.php');
require_once(INCLUDE_DIR . 'class.client.php');

class Collaborator
extends VerySimpleModel
implements EmailContact, ITicketUser {

    static $meta = array(
        'table' => THREAD_COLLABORATOR_TABLE,
        'pk' => array('id'),
        'select_related' => array('user'),
        'joins' => array(
            'thread' => array(
                'constraint' => array('thread_id' => 'Thread.id'),
            ),
            'user' => array(
                'constraint' => array('user_id' => 'User.id'),
            ),
        ),
    );

    function __toString() {
        return Format::htmlchars($this->toString());
    }
    function toString() {
        return sprintf('%s <%s>', $this->getName(), $this->getEmail());
    }

    function getId() {
        return $this->id;
    }

    function isActive() {
        return $this->isactive;
    }

    function getCreateDate() {
        return $this->created;
    }

    function getThreadId() {
        return $this->thread_id;
    }

    function getTicketId() {
        if ($this->thread->object_type == ObjectModel::OBJECT_TYPE_TICKET)
            return $this->thread->object_id;
    }

    function getTicket() {
        // TODO: Change to $this->thread->ticket when Ticket goes to ORM
        if ($id = $this->getTicketId())
            return Ticket::lookup($id);
    }

    function getUser() {
        return $this->user;
    }

    // EmailContact interface
    function getEmail() {
        return $this->user->getEmail();
    }
    function getName() {
        return $this->user->getName();
    }

    // VariableReplacer interface
    function getVar($what) {
        global $cfg;

        switch (strtolower($what)) {
        case 'ticket_link':
            return sprintf('%s/view.php?%s',
                $cfg->getBaseUrl(),
                Http::build_query(
                    // TODO: Chance to $this->getTicket when
                    array('auth' => $this->getTicket()->getAuthToken($this)),
                    false
                )
            );
            break;
        }
    }

    // ITicketUser interface
    var $_isguest;

    function isOwner() {
        return false;
    }
    function flagGuest() {
        $this->_isguest = true;
    }
    function isGuest() {
        return $this->_isguest;
    }
    function getUserId() {
        return $this->user_id;
    }

    static function create($vars=false) {
        $inst = new static($vars);
        $inst->created = SqlFunction::NOW();
        return $inst;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    static function add($info, &$errors) {

        if (!$info || !$info['threadId'] || !$info['userId'])
            $errors['err'] = __('Invalid or missing information');
        elseif ($c = static::lookup(array(
            'thread_id' => $info['threadId'],
            'user_id' => $info['userId'],
        )))
            $errors['err'] = sprintf(__('%s is already a collaborator'),
                    $c->getName());

        if ($errors) return false;

        $collab = static::create(array(
            'isactive' => isset($info['isactive']) ? $info['isactive'] : 0,
            'thread_id' => $info['threadId'],
            'user_id' => $info['userId'],
        ));
        if ($collab->save(true))
            return $collab;

        $errors['err'] = __('Unable to add collaborator. Internal error');

        return false;
    }

}
?>
