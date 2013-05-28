<?php
/*********************************************************************
    class.ostsession.php

    Custom osTicket session handler.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class osTicketSession {

    var $ttl = SESSION_TTL;
    var $data = '';
    var $id = '';

    function osTicketSession($ttl=0){

        $this->ttl =$ttl?$ttl:get_cfg_var('session.gc_maxlifetime');
        if(!$this->ttl)
            $this->ttl=SESSION_TTL;

        if (!OsticketConfig::getDBVersion()) {
            //Set handlers.
            session_set_save_handler(
                array(&$this, 'open'),
                array(&$this, 'close'),
                array(&$this, 'read'),
                array(&$this, 'write'),
                array(&$this, 'destroy'),
                array(&$this, 'gc')
            );
            //Forced cleanup.
            register_shutdown_function('session_write_close');
        }
        //Start the session.
        session_start();
    }

    function regenerate_id(){
        $oldId = session_id();
        session_regenerate_id();
        $this->destroy($oldId);
    }

    function open($save_path, $session_name){
        return (true);
    }

    function close(){
        return (true);
    }

    function read($id){
        if (!$this->data || $this->id != $id) {
            $sql='SELECT session_data FROM '.SESSION_TABLE
                .' WHERE session_id='.db_input($id)
                .'  AND session_expire>NOW()';
            if(!($res=db_query($sql)))
                return false;
            elseif (db_num_rows($res))
                list($this->data)=db_fetch_row($res);
            $this->id = $id;
        }
        return $this->data;
    }

    function write($id, $data){
        global $thisstaff;

        $ttl = ($this && get_class($this) == 'osTicketSession')
            ? $this->getTTL() : SESSION_TTL;

        $sql='REPLACE INTO '.SESSION_TABLE.' SET session_updated=NOW() '.
             ',session_id='.db_input($id).
             ',session_data=0x'.bin2hex($data).
             ',session_expire=(NOW() + INTERVAL '.$ttl.' SECOND)'.
             ',user_id='.db_input($thisstaff?$thisstaff->getId():0).
             ',user_ip='.db_input($_SERVER['REMOTE_ADDR']).
             ',user_agent='.db_input($_SERVER['HTTP_USER_AGENT']);

        $this->data = '';
        return (db_query($sql) && db_affected_rows());
    }

    function destroy($id){
        $sql='DELETE FROM '.SESSION_TABLE.' WHERE session_id='.db_input($id);
        return (db_query($sql) && db_affected_rows());
    }

    function gc($maxlife){
        $sql='DELETE FROM '.SESSION_TABLE.' WHERE session_expire<NOW()';
        db_query($sql);
    }

    /* helper functions */

    function getTTL(){
        return $this->ttl;
    }

    function get_online_users($sec=0){
        $sql='SELECT user_id FROM '.SESSION_TABLE.' WHERE user_id>0 AND session_expire>NOW()';
        if($sec)
            $sql.=" AND TIME_TO_SEC(TIMEDIFF(NOW(),session_updated))<$sec";

        $users=array();
        if(($res=db_query($sql)) && db_num_rows($res)) {
            while(list($uid)=db_fetch_row($res))
                $users[] = $uid;
        }

        return $users;
    }

    /* ---------- static function ---------- */
    function start($ttl=0) {
        return New osTicketSession($ttl);
    }
}
?>
