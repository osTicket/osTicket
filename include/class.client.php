<?php
/*********************************************************************
    class.client.php

    Handles everything about EndUser

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
abstract class TicketUser {

    static private $token_regex = '/^(?P<type>\w{1})(?P<algo>\d+)x(?P<hash>.*)$/i';

    protected  $user;

    function __construct($user) {
        $this->user = $user;
    }

    function __call($name, $args) {
        global $cfg;

        $rv = null;
        if($this->user && is_callable(array($this->user, $name)))
            $rv = $args
                ? call_user_func_array(array($this->user, $name), $args)
                : call_user_func(array($this->user, $name));

        if ($rv) return $rv;

        $tag =  substr($name, 3);
        switch (strtolower($tag)) {
            case 'ticket_link':
                return sprintf('%s/view.php?auth=%s',
                        $cfg->getBaseUrl(),
                        urlencode($this->getAuthToken()));
                break;
        }

        return false;

    }

    function sendAccessLink() {
        global $ost;

        if (!($ticket = $this->getTicket())
                || !($dept = $ticket->getDept())
                || !($email = $dept->getAutoRespEmail())
                || !($tpl = $dept->getTemplate()->getMsgTemplate('user.accesslink')))
            return;

        $vars = array(
            'url' => $ost->getConfig()->getBaseUrl(),
            'ticket' => $this->getTicket(),
            'recipient' => $this);

        $msg = $ost->replaceTemplateVariables($tpl->asArray(), $vars);
        $email->send($this->getEmail(), $msg['subj'], $msg['body']);
    }

    protected function getAuthToken($algo=1) {

        //Format: // <user type><algo id used>x<pack of uid & tid><hash of the algo>
        $authtoken = sprintf('%s%dx%s',
                ($this->isOwner() ? 'o' : 'c'),
                $algo,
                Base32::encode(pack('VV',$this->getId(), $this->getTicketId())));

        switch($algo) {
            case 1:
                $authtoken .= substr(base64_encode(
                            md5($this->getId().$this->getTicket()->getCreateDate().$this->getTicketId().SECRET_SALT, true)), 8);
                break;
            default:
                return null;
        }

        return $authtoken;
    }

    static function lookupByToken($token) {

        //Expecting well formatted token see getAuthToken routine for details.
        $matches = array();
        if (!preg_match(static::$token_regex, $token, $matches))
            return null;

        //Unpack the user and ticket ids
        $matches +=unpack('Vuid/Vtid',
                Base32::decode(strtolower(substr($matches['hash'], 0, 13))));

        $user = null;
        switch ($matches['type']) {
            case 'c': //Collaborator c
                if (($user = Collaborator::lookup($matches['uid']))
                        && $user->getTicketId() != $matches['tid'])
                    $user = null;
                break;
            case 'o': //Ticket owner
                if (($ticket = Ticket::lookup($matches['tid']))) {
                    if (($user = $ticket->getOwner())
                            && $user->getId() != $matches['uid'])
                        $user = null;
                }
                break;
        }

        if (!$user
                || !$user instanceof TicketUser
                || strcasecmp($user->getAuthToken($matches['algo']), $token))
            return false;

        return $user;
    }

    static function lookupByEmail($email) {

        if (!($user=User::lookup(array('emails__address' => $email))))
            return null;

        return new EndUser($user);
    }

    function isOwner() {
        return  ($this->user
                    && $this->user->getId() == $this->getTicket()->getOwnerId());
    }

    abstract function getTicketId();
    abstract function getTicket();
}

class TicketOwner extends  TicketUser {

    protected $ticket;

    function __construct($user, $ticket) {
        parent::__construct($user);
        $this->ticket = $ticket;
    }

    function getTicket() {
        return $this->ticket;
    }

    function getTicketId() {
        return $this->ticket->getId();
    }
}

/*
 * Decorator class for authenticated user
 *
 */

class  EndUser extends AuthenticatedUser {

    protected $user;

    function __construct($user) {
        $this->user = $user;
    }

    /*
     * Delegate calls to the user
     */
    function __call($name, $args) {

        if(!$this->user
                || !is_callable(array($this->user, $name)))
            return false;

        return  $args
            ? call_user_func_array(array($this->user, $name), $args)
            : call_user_func(array($this->user, $name));
    }

    function getId() {
        //We ONLY care about user ID at the ticket level
        if ($this->user instanceof Collaborator)
            return $this->user->getUserId();

        return $this->user->getId();
    }

    function getUserName() {
        //XXX: Revisit when real usernames are introduced  or when email
        // requirement is removed.
        return $this->user->getEmail();
    }

    function getRole() {
        return $this->isOwner() ? 'owner' : 'collaborator';
    }

    function getAuthBackend() {
        list($authkey,) = explode(':', $this->getAuthKey());
        return UserAuthenticationBackend::getBackend($authkey);
    }

    function getTicketStats() {

        if (!isset($this->ht['stats']))
            $this->ht['stats'] = $this->getStats();

        return $this->ht['stats'];
    }

    function getNumTickets() {
        return ($stats=$this->getTicketStats())?($stats['open']+$stats['closed']):0;
    }

    function getNumOpenTickets() {
        return ($stats=$this->getTicketStats())?$stats['open']:0;
    }

    function getNumClosedTickets() {
        return ($stats=$this->getTicketStats())?$stats['closed']:0;
    }

    private function getStats() {

        $sql='SELECT count(open.ticket_id) as open, count(closed.ticket_id) as closed '
            .' FROM '.TICKET_TABLE.' ticket '
            .' LEFT JOIN '.TICKET_TABLE.' open
                ON (open.ticket_id=ticket.ticket_id AND open.status=\'open\') '
            .' LEFT JOIN '.TICKET_TABLE.' closed
                ON (closed.ticket_id=ticket.ticket_id AND closed.status=\'closed\')'
            .' LEFT JOIN '.TICKET_COLLABORATOR_TABLE.' collab
                ON (collab.ticket_id=ticket.ticket_id
                    AND collab.user_id = '.db_input($this->getId()).' )'
            .' WHERE ticket.user_id = '.db_input($this->getId())
            .' OR collab.user_id = '.db_input($this->getId());

        return db_fetch_array(db_query($sql));
    }
}
?>
