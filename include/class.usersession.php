<?php
/*********************************************************************
    class.usersession.php

    User (client and staff) sessions handle.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
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
      $time  = time();
      $hash  = md5($time.SESSION_SECRET.$this->userID);
      $token = "$hash:$time:".MD5($this->ip);
      return $token;
   }

   function getLastUpdate($htoken) {
       if (!$htoken)
           return 0;

       @list($hash, $expire, $ip) = explode(":", $htoken);
       return $expire;
   }

   function isvalidSession($htoken,$maxidletime=0,$checkip=false){
        global $cfgi;

        // Compare session ids
        if (strcmp($this->getSessionId(), session_id()))
            return false;

        // Is the session invalidated?
        if (isset($_SESSION['KAPUT']) &&  $_SESSION['KAPUT'] < time())
            return (session_destroy() && false);

        $token = rawurldecode($htoken);
        // Check if we got what we expected....
        if ($token && !strstr($token,":"))
            return false;

        // Get the goodies
        list($hash, $expire, $ip) = explode(":",$token);

        // Make sure the session hash is valid
        if ((md5($expire . SESSION_SECRET . $this->userID) != $hash))
            return false;

        // is it expired??
        if ($maxidletime && ((time()-$expire)>$maxidletime))
            return false;

        #Make sure IP is still same ( proxy access??????)
        if ($checkip && strcmp($ip, MD5($this->ip)))
            return false;

        $this->validated = true;

        return true;
   }

   function regenerateSession($destroy=false) {
       // Delayed kaput time for current session
       $_SESSION['KAPUT'] = time() + 60;
       // Save the session id as old
       $old = session_id();
       // Regenerate the session without destroying data
       session_regenerate_id(false);
       // Get new session id and close
       $new = session_id();
       session_write_close();
       // Start new session
       session_id($new);
       session_start();
       $this->session_id  = $new;
       // Make sure new session is not set to KAPUT and TIME_BOMB
       unset($_SESSION['KAPUT'], $_SESSION['TIME_BOMB']);
       // Destroy ?
       if ($destroy) {
           // Destrory old session
           $this->destroySession($old);
           // Restore new session
           session_id($new);
           session_start();
       }
       return true;
   }

   function destroySession($id) {
       // Close current session
       session_write_close();
       // Start target session
       session_id($id);
       session_start();
       // Destroy session
       session_destroy();
       session_write_close();
       return true;
   }

   function isValid() {
        return  ($this->validated);
   }

}


trait UserSessionTrait {
    // Session Object
    var $session;
    // Session Token
    var $token;
    // Maximum idle time before session is considered invalid
    var $maxidletime = 0;
    // Indicates if session is bound to the IP address
    var $checkip = false;
    // User class
    var $class = '';

    function refreshSession($refreshRate=60): void {
        // If TIME_BOMB isset and less than the current time we need to regenerate
        // session to help mitigate session fixation
        if (isset($_SESSION['TIME_BOMB']) && ($_SESSION['TIME_BOMB'] < time()))
            $this->regenerateSession();
        // Deadband session token updates to once / 30-seconds
        $updated = $this->session->getLastUpdate($this->token);
        if ($updated + $refreshRate < time()) {
            $this->token = $this->getSessionToken();
            osTicketSession::renewCookie($updated, $this->maxidletime);
        }
    }

    function regenerateSession($destroy=false) {
        $this->session->regenerateSession($destroy);
        // Set cookie for the new session id.
        $this->refreshSession(-1);
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
                    $this->maxidletime, $this->checkip));
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
