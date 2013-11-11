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

class Collaborator {

    var $ht;

    var $user;
    var $ticket;

    function __construct($id) {

        $this->load($id);
    }

    function load($id) {

        if(!$id && !($id=$this->getId()))
            return;

        $sql='SELECT * FROM '.TICKET_COLLABORATOR_TABLE
            .' WHERE id='.db_input($id);

        $this->ht = db_fetch_array(db_query($sql));
        $this->ticket = $this->user = null;
    }

    function reload() {
        return $this->load();
    }

    function __call($name, $args) {

        if(!($user=$this->getUser()) || !method_exists($user, $name))
            return null;

        if($args)
            return  call_user_func_array(array($user, $name), $args);

        return call_user_func(array($user, $name));
    }

    function getId() {
        return $this->ht['id'];
    }

    function isActive() {
        return ($this->ht['isactive']);
    }

    function getTicketId() {
        return $this->ht['ticket_id'];
    }

    function getTicket() {
        if(!$this->ticket && $this->getTicketId())
            $this->ticket = Ticket::lookup($this->getTicketId());

        return $this->ticket;
    }

    function getUserId() {
        return $this->ht['user_id'];
    }

    function getUser() {

        if(!$this->user && $this->getUserId())
            $this->user = User::lookup($this->getUserId());

        return $this->user;
    }

    static function add($info, &$errors) {

        if(!$info || !$info['ticketId'] || !$info['userId'])
            $errors['err'] = 'Invalid or missing information';
        elseif(($c=self::lookup($info)))
            $errors['err'] = sprintf('%s is already a collaborator',
                    $c->getName());

        if($errors) return false;

        $sql='INSERT INTO '.TICKET_COLLABORATOR_TABLE
            .' SET isactive=1, updated=NOW() '
            .' ,ticket_id='.db_input($info['ticketId'])
            .' ,user_id='.db_input($info['userId']);

        if(db_query($sql) && ($id=db_insert_id()))
            return self::lookup($id);

        $errors['err'] = 'Unable to add collaborator. Internal error';

        return false;
    }

    static function getIdByInfo($info) {

        $sql='SELECT id FROM '.TICKET_COLLABORATOR_TABLE
            .' WHERE ticket_id='.db_input($info['ticketId'])
            .' AND user_id='.db_input($info['userId']);

        list($id) = db_fetch_row(db_query($sql));

        return $id;
    }

    static function lookup($criteria) {
        $id = is_numeric($criteria)
            ? $criteria : self::getIdByInfo($criteria);

        return ($id
                && ($c = new Collaborator($id))
                && $c->getId() == $id)
            ? $c : null;
    }
}
?>
