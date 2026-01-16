<?php
/*********************************************************************
    class.usersession.php

    User (client and staff) sessions manager

    User-Space session management, not to confused with Session Storage
    Backends.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2022 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

include_once(INCLUDE_DIR.'class.client.php');
include_once(INCLUDE_DIR.'class.staff.php');

class UserSession {
   var $session_id = '';
   var $userID = 0;
   var $browser = '';
   var $ip = '';
   var $validated = false;

   function __construct($userid) {
      $this->browser = (!empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : $_ENV['HTTP_USER_AGENT'];
      $this->ip = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : getenv('REMOTE_ADDR');
      $this->session_id = session_id();
      $this->userID = $userid;
   }

   function isStaff() {
       return false;
   }

   function isClient() {
       return false;
   }

   function getSessionId() {
       return $this->session_id;
   }

   function getIP() {
        return  $this->ip;
   }

   function getBrowser() {
       return $this->browser;
   }

   function sessionToken(){
      // Please note that user-space token is not meant to be secure at all
      // we're simply encoding stuff we want to track as we refresh the
      // session.
      $time  = time();
      $hash  = md5($time.SESSION_SECRET.$this->userID);
      $token = "$hash:$time:".MD5($this->getIP());
      return $token;
   }

   function getLastUpdate($htoken) {
       if (!$htoken)
           return 0;

       @list($hash, $expire, $ip) = explode(":", $htoken);
       return $expire;
   }

   function isvalidSession($htoken, $maxidletime=0, $checkip=false){
        global $cfg;

        // Compare session ids
        if (strcmp($this->getSessionId(), session_id()))
            return false;

        $token = rawurldecode($htoken);
        // Check if we got what we expected....
        if ($token && !strstr($token,":"))
            return false;

        // Get the goodies
        list($hash, $expire, $ip) = explode(':', $token);

        // Make sure the session hash is valid
        if ((md5($expire . SESSION_SECRET . $this->userID) != $hash))
            return false;

        // is it expired??
        if ($maxidletime && ((time()-$expire) > $maxidletime))
            return false;

        // Make sure IP is still same - if requested
        if ($checkip && strcmp($ip, MD5($this->getIP())))
            return false;

        $this->validated = true;

        return true;
   }

   function isValid() {
        return  ($this->validated);
   }
}

trait UserSessionTrait {
    // User Session Object
    var $session;
    // Session Token
    var $token;
    // Maximum idle time before session is considered invalid
    var $maxidletime = 0;
    // Indicates if session is bound to the IP address
    var $checkip = false;
    // User class
    var $class = '';


    public function getMaxIdleTime() {
        return $this->maxidletime ;
    }

    function refreshSession($refreshRate=60) {
        // Check Time To Die (TTD) if any - OLD people.. I mean sessions,
        // must die! Don't fight it bro!
        if (isset($_SESSION['TTD']) &&  $_SESSION['TTD'] < time()) {
            error_log(sprintf('Session %s with TTD %s was used',
                        session_id(), $_SESSION['TTD']));
            return (session_destroy() && false);
        }

        // If TIME_BOMB is set and less than the current time we need to regenerate
        // session id to help mitigate session fixation attacks.
        // Only regenerate on GET to avoid invalidating data in-flight on a
        // POST request
        if ($_SERVER['REQUEST_METHOD'] === 'GET'
                && isset($_SESSION['TIME_BOMB'])
                && ($_SESSION['TIME_BOMB'] < time())
                && ($id=$this->regenerateSession())) {
            // unset timer and set next one based on maxlife for the user or
            // 24 hrs later
            // TODO: Make regenerate frequency configurable in 2032 /j
            // PS: Living and dying and the stories that are true Secrets to
            // a good life is knowing when you're through ~ time bomb
            $ttl = ($this->getMaxIdleTime() ?: 86400);
            $_SESSION['TIME_BOMB'] = time() + $ttl;
            // Set new id locally
            $this->session_id  = $id;
            // Force cookie renewal NOW!
            $refreshRate = -1;
        }

        // Deadband session token updates to once / 30-seconds
        $updated = $this->session->getLastUpdate($this->token);
        if ($updated + $refreshRate < time()) {
            // Renew the session token
            $this->token = $this->getSessionToken();
            // Update the expire time for the session cookie
            osTicketSession::renewCookie(time(), $this->getMaxIdleTime());
        }
    }

    function regenerateSession(int $ttl = 120) {
        // Set TTD (Time To Die) on current session
        // If ttl is 0 then session is destroyed immediatetly
        $_SESSION['TTD'] = time() + $ttl; // now + ttl
        if (($id=osTicketSession::regenerate($ttl)))
            $this->session->session_id = $id;
        // unset TTD on the new session - new life my boy!
        unset($_SESSION['TTD']);
        return $id;
    }

    function getSession() {
        return $this->session;
    }

    function getSessionToken() {
        return $this->session->sessionToken();
    }

    function setSessionToken($token=null) {
        // Assign memory to token variable
        $this->token = &$_SESSION[':token'][$this->class];
        // Set token
        $token = $token ?: $this->token;
        $this->token = $token ?: $this->getSessionToken();
    }

    function getIP() {
        return $this->session->getIP();
    }

    function isValidSession() {
        return ($this->getId()
                && $this->session->isvalidSession($this->token,
                    $this->getMaxIdleTime(), $this->checkip));
    }

    abstract function isValid();
}

class ClientSession extends EndUser {
    use UserSessionTrait;

    function __construct($user) {
        global $cfg;
        parent::__construct($user);
        $this->class ='client';
        // XXX: Change the key to user-id
        $this->session = new UserSession($user->getUserId());
        $this->setSessionToken();
        $this->maxidletime = $cfg->getClientTimeout();
    }

    function getSessionUser() {
        return $this->user;
    }

    function isValid() {
        return $this->isValidSession();
    }
}

class StaffSession extends Staff {
    use UserSessionTrait;

    static function lookup($var) {
        global $cfg;
        if (($staff = parent::lookup($var))) {
            $staff->class = 'staff';
            $staff->session = new UserSession($staff->getId());
            $staff->setSessionToken();
            $staff->maxidletime = $cfg->getStaffTimeout();
            $staff->checkip = $cfg->enableStaffIPBinding();
        }
        return $staff;
    }

    function clear2FA() {
        unset($_SESSION['_auth']['staff']['2fa']);
        $_SESSION['_auth']['staff']['2fa'] = null;
        return true;
    }

    // If 2fa is set then it means it's pending
    function is2FAPending() {
        return isset($_SESSION['_auth']['staff']['2fa']);
    }

    function isValid() {
        return (!$this->is2FAPending() && $this->isValidSession());
    }
}
?>
