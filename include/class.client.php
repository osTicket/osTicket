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

    /* static */ function tryLogin($ticketID, $email, $auth=null) {
        global $ost;
        $cfg = $ost->getConfig();

        # Only consider auth token for GET requests, and for GET requests,
        # REQUIRE the auth token
        $auto_login = $_SERVER['REQUEST_METHOD'] == 'GET';

        //Check time for last max failed login attempt strike.
        $loginmsg='Invalid login';
        # XXX: SECURITY: Max attempts is enforced client-side via the PHP
        #      session cookie.
        if($_SESSION['_client']['laststrike']) {
            if((time()-$_SESSION['_client']['laststrike'])<$cfg->getClientLoginTimeout()) {
                $loginmsg='Excessive failed login attempts';
                $errors['err']='You\'ve reached maximum failed login attempts allowed. Try again later or <a href="open.php">open a new ticket</a>';
            }else{ //Timeout is over.
                //Reset the counter for next round of attempts after the timeout.
                $_SESSION['_client']['laststrike']=null;
                $_SESSION['_client']['strikes']=0;
            }
        }
        //See if we can fetch local ticket id associated with the ID given
        if(!$errors && is_numeric($ticketID) && Validator::is_email($email) && ($ticket=Ticket::lookupByExtId($ticketID))) {
            //At this point we know the ticket is valid.
            //TODO: 1) Check how old the ticket is...3 months max?? 2) Must be the latest 5 tickets?? 
            //Check the email given.
            # Require auth token for automatic logins
            if (!$auto_login || $auth === $ticket->getAuthToken()) {
                if($ticket->getId() && strcasecmp($ticket->getEmail(),$email)==0){
                    //valid match...create session goodies for the client.
                    $user = new ClientSession($email,$ticket->getId());
                    $_SESSION['_client']=array(); //clear.
                    $_SESSION['_client']['userID']   =$ticket->getEmail(); //Email
                    $_SESSION['_client']['key']      =$ticket->getExtId(); //Ticket ID --acts as password when used with email. See above.
                    $_SESSION['_client']['token']    =$user->getSessionToken();
                    $_SESSION['TZ_OFFSET']=$cfg->getTZoffset();
                    $_SESSION['TZ_DST']=$cfg->observeDaylightSaving();
                    //Log login info...
                    $msg=sprintf("%s/%s logged in [%s]",$ticket->getEmail(),$ticket->getExtId(),$_SERVER['REMOTE_ADDR']);
                    $ost->logDebug('User login', $msg);
                    //Redirect tickets.php
                    session_write_close();
                    session_regenerate_id();
                    @header("Location: tickets.php?id=".$ticket->getExtId());
                    require_once('tickets.php'); //Just incase. of header already sent error.
                    exit;
                }
            }
        }
        //If we get to this point we know the login failed.
        $_SESSION['_client']['strikes']+=1;
        if(!$errors && $_SESSION['_client']['strikes']>$cfg->getClientMaxLogins()) {
            $loginmsg='Access Denied';
            $errors['err']='Forgot your login info? Please <a href="open.php">open a new ticket</a>.';
            $_SESSION['_client']['laststrike']=time();
            $alert='Excessive login attempts by a client?'."\n".
                    'Email: '.$_POST['lemail']."\n".'Ticket#: '.$_POST['lticket']."\n".
                    'IP: '.$_SERVER['REMOTE_ADDR']."\n".'Time:'.date('M j, Y, g:i a T')."\n\n".
                    'Attempts #'.$_SESSION['_client']['strikes'];
            $ost->logError('Excessive login attempts (client)', $alert, ($cfg->alertONLoginError()));
        }elseif($_SESSION['_client']['strikes']%2==0){ //Log every other failed login attempt as a warning.
            $alert='Email: '.$_POST['lemail']."\n".'Ticket #: '.$_POST['lticket']."\n".'IP: '.$_SERVER['REMOTE_ADDR'].
                   "\n".'TIME: '.date('M j, Y, g:i a T')."\n\n".'Attempts #'.$_SESSION['_client']['strikes'];
            $ost->logWarning('Failed login attempt (client)', $alert);
        }
    }
}
?>
