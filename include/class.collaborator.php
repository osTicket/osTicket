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

    static private $token_regex = '/^c(?P<id>\d+)x(?P<algo>\d+)h(?P<hash>.*)$/i';

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
            return false;

        return  $args
            ? call_user_func_array(array($user, $name), $args)
            : call_user_func(array($user, $name));
    }

    function __toString() {
        return Format::htmlchars(sprintf('%s <%s>', $this->getName(),
                    $this->getEmail()));
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

    function remove() {

        $sql='DELETE FROM '.TICKET_COLLABORATOR_TABLE
            .' WHERE id='.db_input($this->getId())
            .' LIMIT 1';

        return  (db_query($sql) && db_affected_rows());
    }

    static function add($info, &$errors) {

        if (!$info || !$info['ticketId'] || !$info['userId'])
            $errors['err'] = 'Invalid or missing information';
        elseif (($c=self::lookup($info)))
            $errors['err'] = sprintf('%s is already a collaborator',
                    $c->getName());

        if ($errors) return false;

        $sql='INSERT INTO '.TICKET_COLLABORATOR_TABLE
            .' SET updated=NOW() '
            .' ,isactive='.db_input(isset($info['isactive']) ?  $info['isactive'] : 0)
            .' ,ticket_id='.db_input($info['ticketId'])
            .' ,user_id='.db_input($info['userId']);

        if(db_query($sql) && ($id=db_insert_id()))
            return self::lookup($id);

        $errors['err'] = 'Unable to add collaborator. Internal error';

        return false;
    }

    static function forTicket($tid, $criteria=array()) {

        $collaborators = array();

        $sql='SELECT id FROM '.TICKET_COLLABORATOR_TABLE
            .' WHERE ticket_id='.db_input($tid);

        if(isset($criteria['isactive']))
            $sql.=' AND isactive='.db_input($criteria['isactive']);

        //TODO: sort by name of the user

        if(($res=db_query($sql)) && db_num_rows($res))
            while(list($id)=db_fetch_row($res))
                $collaborators[] = self::lookup($id);

        return $collaborators;
    }

    static function getIdByInfo($info) {

        $sql='SELECT id FROM '.TICKET_COLLABORATOR_TABLE
            .' WHERE ticket_id='.db_input($info['ticketId'])
            .' AND user_id='.db_input($info['userId']);

        list($id) = db_fetch_row(db_query($sql));

        return $id;
    }

    static function lookupByAuthToken($token) {

        //Expecting well formatted token see getAuthToken routine for details.
        $matches = array();
        if (preg_match(static::$token_regex, $token, $matches)
                && $matches['id']
                && ($c = self::lookup($matches['id']))
                && strcasecmp($c->getAuthToken($matches['algo']), $token)  == 0
                )
            return $c;

        return null;

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
