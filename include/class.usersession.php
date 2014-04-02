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
   var $userID='';
   var $browser = '';
   var $ip = '';
   var $validated=FALSE;

   function UserSession($userid){

      $this->browser=(!empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : $_ENV['HTTP_USER_AGENT'];
      $this->ip=(!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : getenv('REMOTE_ADDR');
      $this->session_id=session_id();
      $this->userID=$userid;
   }

   function isStaff(){
       return FALSE;
   }

   function isClient() {
       return FALSE;
   }


   function getSessionId(){
       return $this->session_id;
   }

   function getIP(){
        return  $this->ip;
   }

   function getBrowser(){
       return $this->browser;
   }
   function refreshSession(){
       //nothing to do...clients need to worry about it.
   }

   function sessionToken(){

      $time  = time();
      $hash  = md5($time.SESSION_SECRET.$this->userID);
      $token = "$hash:$time:".MD5($this->ip);

      return($token);
   }

   function getLastUpdate($htoken) {
       if (!$htoken)
           return 0;

       @list($hash,$expire,$ip)=explode(":",$htoken);
       return $expire;
   }

   function isvalidSession($htoken,$maxidletime=0,$checkip=false){
        global $cfg;

        $token = rawurldecode($htoken);

        #check if we got what we expected....
        if($token && !strstr($token,":"))
            return FALSE;

        #get the goodies
        list($hash,$expire,$ip)=explode(":",$token);

        #Make sure the session hash is valid
        if((md5($expire . SESSION_SECRET . $this->userID)!=$hash)){
            return FALSE;
        }
        #is it expired??


        if($maxidletime && ((time()-$expire)>$maxidletime)){
            return FALSE;
        }
        #Make sure IP is still same ( proxy access??????)
        if($checkip && strcmp($ip, MD5($this->ip)))
            return FALSE;

        $this->validated=TRUE;

        return TRUE;
   }

   function isValid() {
        return FALSE;
   }

}

class ClientSession extends EndUser {

    var $session;
    var $token;

    function __construct($user) {
        parent::__construct($user);
        $this->token = &$_SESSION[':token']['client'];
        // XXX: Change the key to user-id
        $this->session= new UserSession($user->getId());
    }

    function isValid(){
        global $_SESSION,$cfg;

        if(!$this->getId() || $this->session->getSessionId()!=session_id())
            return false;

        return $this->session->isvalidSession($this->token,$cfg->getClientTimeout(),false)?true:false;
    }

    function refreshSession($force=false){
        $time = $this->session->getLastUpdate($this->token);
        // Deadband session token updates to once / 30-seconds
        if (!$force && time() - $time < 30)
            return;

        $this->token = $this->getSessionToken();
        //TODO: separate expire time from hash??
    }

    function getSession() {
        return $this->session;
    }

    function getSessionToken() {
        return $this->session->sessionToken();
    }

    function getIP(){
        return $this->session->getIP();
    }
}


class StaffSession extends Staff {

    var $session;
    var $token;

    function __construct($var) {
        parent::__construct($var);
        $this->token = &$_SESSION[':token']['staff'];
        $this->session= new UserSession($this->getId());
    }

    function isValid(){
        global $_SESSION, $cfg;

        if(!$this->getId() || $this->session->getSessionId()!=session_id())
            return false;

        return $this->session->isvalidSession($this->token,$cfg->getStaffTimeout(),$cfg->enableStaffIPBinding())?true:false;
    }

    function refreshSession($force=false){
        $time = $this->session->getLastUpdate($this->token);
        // Deadband session token updates to once / 30-seconds
        if (!$force && time() - $time < 30)
            return;

        $this->token=$this->getSessionToken();
    }

    function getSession() {
        return $this->session;
    }

    function getSessionToken() {
        return $this->session->sessionToken();
    }

    function getIP(){
        return $this->session->getIP();
    }

}

?>
