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
    static $backends = array(
        'db'        => 'DbSessionBackend',
        'memcache'  => 'MemcacheSessionBackend',
        'system'    => 'FallbackSessionBackend',
    );

    var $ttl = SESSION_TTL;
    var $data = '';
    var $data_hash = '';
    var $id = '';
    var $backend;

    function __construct($ttl=0){
        $this->ttl = $ttl ?: ini_get('session.gc_maxlifetime') ?: SESSION_TTL;

        // Set osTicket specific session name.
        session_name('OSTSESSID');

        // Forced cleanup on shutdown
        register_shutdown_function('session_write_close');

        // Set session cleanup time to match TTL
        ini_set('session.gc_maxlifetime', $ttl);

        if (OsticketConfig::getDBVersion())
            return session_start();

        # Cookies
        // Avoid setting a cookie domain without a dot, thanks
        // http://stackoverflow.com/a/1188145
        $domain = null;
        if (isset($_SERVER['HTTP_HOST'])
                && strpos($_SERVER['HTTP_HOST'], '.') !== false
                && !Validator::is_ip($_SERVER['HTTP_HOST']))
            // Remote port specification, as it will make an invalid domain
            list($domain) = explode(':', $_SERVER['HTTP_HOST']);

        session_set_cookie_params($ttl, ROOT_PATH, $domain,
            osTicket::is_https());

        if (!defined('SESSION_BACKEND'))
            define('SESSION_BACKEND', 'db');

        try {
            $bk = SESSION_BACKEND;
            if (!class_exists(self::$backends[$bk]))
                $bk = 'db';
            $this->backend = new self::$backends[$bk]($this->ttl);
        }
        catch (Exception $x) {
            // Use the database for sessions
            trigger_error($x->getMessage(), E_USER_WARNING);
            $this->backend = new self::$backends['db']($this->ttl);
        }

        if ($this->backend instanceof SessionBackend) {
            // Set handlers.
            session_set_save_handler(
                array($this->backend, 'open'),
                array($this->backend, 'close'),
                array($this->backend, 'read'),
                array($this->backend, 'write'),
                array($this->backend, 'destroy'),
                array($this->backend, 'gc')
            );
        }

        // Start the session.
        session_start();
    }

    function regenerate_id(){
        $oldId = session_id();
        session_regenerate_id();
        $this->backend->destroy($oldId);
    }

    static function destroyCookie() {
        setcookie(session_name(), 'deleted', 1,
            ini_get('session.cookie_path'),
            ini_get('session.cookie_domain'),
            ini_get('session.cookie_secure'),
            ini_get('session.cookie_httponly'));
    }

    static function renewCookie($baseTime=false, $window=false) {
        setcookie(session_name(), session_id(),
            ($baseTime ?: time()) + ($window ?: SESSION_TTL),
            ini_get('session.cookie_path'),
            ini_get('session.cookie_domain'),
            ini_get('session.cookie_secure'),
            ini_get('session.cookie_httponly'));
    }

    /* helper functions */

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
    static function start($ttl=0) {
        return new static($ttl);
    }
}

abstract class SessionBackend {
    var $isnew = false;
    var $ttl;

    function __construct($ttl=SESSION_TTL) {
        $this->ttl = $ttl;
    }

    function open($save_path, $session_name) {
        return true;
    }

    function close() {
        return true;
    }

    function getTTL() {
        return $this->ttl;
    }

    function write($id, $data) {
        // Last chance session update
        $i = new ArrayObject(array('touched' => false));
        Signal::send('session.close', null, $i);
        return $this->update($id, $i['touched'] ? session_encode() : $data);
    }

    abstract function read($id);
    abstract function update($id, $data);
    abstract function destroy($id);
    abstract function gc($maxlife);
}

class SessionData
extends VerySimpleModel {
    static $meta = array(
        'table' => SESSION_TABLE,
        'pk' => array('session_id'),
    );
}

class DbSessionBackend
extends SessionBackend {
    var $data = null;

    function read($id) {
        try {
            $this->data = SessionData::objects()->filter([
                'session_id' => $id,
                'session_expire__gt' => SqlFunction::NOW(),
            ])->one();
            $this->id = $id;
        }
        catch (DoesNotExist $e) {
            $this->data = new SessionData(['session_id' => $id]);
        }
        catch (OrmException $e) {
            return false;
        }
        return $this->data->session_data;
    }

    function update($id, $data){
        global $thisstaff;

        if (defined('DISABLE_SESSION') && $this->data->__new__)
            return true;

        $ttl = $this && method_exists($this, 'getTTL')
            ? $this->getTTL() : SESSION_TTL;

        // Create a session data obj if not loaded.
        if (!isset($this->data))
            $this->data = new SessionData(['session_id' => $id]);

        $this->data->session_data = $data;
        $this->data->session_expire =
            SqlFunction::NOW()->plus(SqlInterval::SECOND($ttl));
        $this->data->user_id = $thisstaff ? $thisstaff->getId() : 0;
        $this->data->user_ip = $_SERVER['REMOTE_ADDR'];
        $this->data->user_agent = $_SERVER['HTTP_USER_AGENT'];

        return $this->data->save();
    }

    function destroy($id){
        return SessionData::objects()->filter(['session_id' => $id])->delete();
    }

    function gc($maxlife){
        SessionData::objects()->filter([
            'session_expire__lte' => SqlFunction::NOW()
        ])->delete();
    }
}

class MemcacheSessionBackend
extends SessionBackend {
    var $memcache;
    var $servers = array();

    function __construct($ttl) {
        parent::__construct($ttl);

        if (!extension_loaded('memcache'))
            throw new Exception('Memcached extension is missing');
        if (!defined('MEMCACHE_SERVERS'))
            throw new Exception('MEMCACHE_SERVERS must be defined');

        $servers = explode(',', MEMCACHE_SERVERS);
        $this->memcache = new Memcache();

        foreach ($servers as $S) {
            @list($host, $port) = explode(':', $S);
            if (strpos($host, '/') !== false)
                // Use port '0' for unix sockets
                $port = 0;
            else
                $port = $port ?: ini_get('memcache.default_port') ?: 11211;
            $this->servers[] = array(trim($host), (int) trim($port));
            // FIXME: Crash or warn if invalid $host or $port
        }
    }

    function getKey($id) {
        return sha1($id.SECRET_SALT);
    }

    function read($id) {
        $key = $this->getKey($id);

        // Try distributed read first
        foreach ($this->servers as $S) {
            list($host, $port) = $S;
            $this->memcache->addServer($host, $port);
        }
        $data = $this->memcache->get($key);

        // Read from other servers on failure
        if ($data === false && count($this->servers) > 1) {
            foreach ($this->servers as $S) {
                list($host, $port) = $S;
                $this->memcache->pconnect($host, $port);
                if ($data = $this->memcache->get($key))
                    break;
            }
        }

        // No session data on record -- new session
        $this->isnew = $data === false;

        return $data;
    }

    function update($id, $data) {
        if (defined('DISABLE_SESSION') && $this->isnew)
            return;

        $key = $this->getKey($id);
        foreach ($this->servers as $S) {
            list($host, $port) = $S;
            $this->memcache->pconnect($host, $port);
            if (!$this->memcache->replace($key, $data, 0, $this->getTTL()));
                $this->memcache->set($key, $data, 0, $this->getTTL());
        }
    }

    function destroy($id) {
        $key = $this->getKey($id);
        foreach ($this->servers as $S) {
            list($host, $port) = $S;
            $this->memcache->pconnect($host, $port);
            $this->memcache->replace($key, '', 0, 1);
            $this->memcache->delete($key, 0);
        }
    }

    function gc($maxlife) {
        // Memcache does this automatically
    }
}

class FallbackSessionBackend {
    // Use default PHP settings, with some edits for best experience
    function __construct() {
        // FIXME: Consider extra possible security tweaks such as adjusting
        // the session.save_path
    }
}

?>
