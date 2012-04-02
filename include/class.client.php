<?php
/*********************************************************************
    class.client.php

    Handles everything about client

    NOTE: Please note that osTicket uses email address and ticket ID to authenticate the user*!
          Client is modeled on the info of the ticket used to login .

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Client {

    var $id;
    var $fullname;
    var $username;
    var $email;

    var $ticket_id;
    var $ticketID;

    var $ht;


    function Client($id, $email=null) {
        $this->id =0;
        $this->load($id,$email);
    }

    function load($id=0, $email=null) {

        if(!$id && !($id=$this->getId()))
            return false;

        $sql='SELECT ticket_id, ticketID, name, email, phone, phone_ext '
            .' FROM '.TICKET_TABLE
            .' WHERE ticketID='.db_input($id);
        if($email)
            $sql.=' AND email='.db_input($email);

        if(!($res=db_query($sql)) || !db_num_rows($res))
            return NULL;

        $this->ht = db_fetch_array($res);
        $this->id         = $this->ht['ticketID']; //placeholder
        $this->ticket_id  = $this->ht['ticket_id'];
        $this->ticketID   = $this->ht['ticketID'];
        $this->fullname   = ucfirst($this->ht['name']);
        $this->username   = $this->ht['email'];
        $this->email      = $this->ht['email'];

        $this->stats = array();
      
        return($this->id);
    }

    function reload() {
        return $this->load();
    }

    function isClient() {
        return TRUE;
    }

    function getId() {
        return $this->id;
    }

    function getEmail() {
        return $this->email;
    }

    function getUserName() {
        return $this->username;
    }

    function getName() {
        return $this->fullname;
    }

    function getPhone() {
        return $this->ht['phone'];
    }

    function getPhoneExt() {
        return $this->ht['phone_ext'];
    }
    
    function getTicketID() {
        return $this->ticketID;
    }

    function getTicketStats() {

        if(!$this->stats['tickets'])
            $this->stats['tickets'] = Ticket::getClientStats($this->getEmail());

        return $this->stats['tickets'];
    }

    function getNumTickets() {
        return ($stats=$this->getTicketStats())?($stats['open']+$stats['closed']):0;
    }

    function getNumOpenTickets() {
        return ($stats=$this->getTicketStats())?$stats['open']:0;
    }

    /* ------------- Static ---------------*/
    function getLastTicketIdByEmail($email) {
        $sql='SELECT ticketID FROM '.TICKET_TABLE
            .' WHERE email='.db_input($email)
            .' ORDER BY created '
            .' LIMIT 1';
        if(($res=db_query($sql)) && db_num_rows($res))
            list($tid) = db_fetch_row($res);

        return $tid;
    }

    function lookup($id, $email=null) {
        return ($id && is_numeric($id) && ($c=new Client($id,$email)) && $c->getId()==$id)?$c:null;
    }

    function lookupByEmail($email) {
        return (($id=self::getLastTicketIdByEmail($email)))?self::lookup($id, $email):null;
    }
}
?>
