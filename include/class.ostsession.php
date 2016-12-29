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

        if ($this->backend instanceof SessionHandlerInterface)
            session_set_save_handler($this->backend);

        // Start the session. Custom locking session cannot be supported if
        // PHP is using session auto start.
        $auto_start = ini_get('session.auto_start') ?: false;
        if (!$auto_start && $this->backend instanceof SessionBackend) {
            $new = false;
            $id = $this->backend->start($new);
            $data = $this->backend->read($id);
            $session = unserialize($data) ?: array();
            if ($new)
                self::renewCookie();

            // Wrap to synchronize updates between same session_id's. This
            // prevents a race condition where multiple writes to the same
            // session will clobber the data inside it. This is normally
            // enforced by PHP for file-backed sessions; however, it will
            // need to be manually implemented here for db- or memcache-backed
            // sessions
            $_SESSION = new LockingArray($session);
            $_SESSION->setBackend($id, $this->backend);
            register_shutdown_function(function() use ($id) {
                $i = new ArrayObject(array('touched' => false));
                Signal::send('session.close', null, $i);
                // FIXME: It would be possible to check if the session is
                //      locked. If not, it might be safe to assume no
                //      changes were made and no write() is necessary.
                $_SESSION->releaseLock();
                $this->backend->write($id, serialize($_SESSION->asArray()));
            });
        }
        else {
            // Use built-in PHP session engine
            session_start();
        }
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

abstract class SessionBackend
implements SessionHandlerInterface {
    var $ttl;
    var $deflate;

    function __construct($ttl=SESSION_TTL) {
        $this->ttl = $ttl;
        $this->deflate = function_exists('gzdeflate');
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

    function start(&$new=false) {
        if (!session_id()) {
            $name = session_name();
            if (isset($_COOKIE[$name])) {
                session_id($_COOKIE[$name]);
            }
            else {
                // There's a sort-of race here. randCode will use the value
                // of session_id() on some platforms as a source of random
                // data. Let's put something slightly less predictable in
                // it. Also, for random data on the installer, don't
                // completely rely on the SECRET_SALT code
                session_id(uniqid('', true));

                // 6 bits per char (64 characters), 30*6 = 180-bit
                session_id(Misc::randCode(30));
                $new = true;
            }
        }
        // Implement PHP's session garbage collection
        $probability = ini_get('session.gc_probability') ?: 1;
        $divisor = ini_get('session.gc_divisor') ?: 100;
        $likliness = $divisor / $probability;
        if (rand(0, $likliness) == 1) {
            $this->gc($this->getTTL());
        }
        return session_id();
    }

    function write($id, $data) {
        // Last chance session update
        if ($this->deflate)
            $data = gzdeflate($data, 2);
        return $this->update($id, $data);
    }

    function read($id) {
        $data = $this->fetch($id);
        if ($this->deflate && strlen($data))
            $data = @gzinflate($data) ?: $data;
        return $data;
    }

    abstract function fetch($id);
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

    function fetch($id) {
        try {
            $this->data = SessionData::objects()->filter([
                'session_id' => $id,
                'session_expire__gt' => SqlFunction::NOW(),
            ])->one();
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

        // Create a session data obj if not loaded.
        if (!isset($this->data))
            $this->data = new SessionData(['session_id' => $id]);

        if (defined('DISABLE_SESSION') && $this->data->__new__)
            return true;

        $ttl = $this && method_exists($this, 'getTTL')
            ? $this->getTTL() : SESSION_TTL;

        $this->data->session_data = $data;
        $this->data->session_expire =
            SqlFunction::NOW()->plus(SqlInterval::SECOND($ttl));
        $this->data->session_updated = SqlFunction::NOW();
        $this->data->user_id = $thisstaff ? $thisstaff->getId() : 0;
        $this->data->user_ip = $_SERVER['REMOTE_ADDR'];
        $this->data->user_agent = $_SERVER['HTTP_USER_AGENT'];

        return $this->data->save();
    }

    function checkTouched() {
        list($lastupdate) = SessionData::objects()
            ->filter(['session_id' => $id])
            ->values_flat('session_updated')
            ->first();

        if (isset($lastupdate)) {
            // If we think the data is new, then it must have been touched.
            // This case should never happen for existing sessions.
            if ($this->data->__new__)
                return true;

            // XXX: Only accurate to one second
            return $this->data->session_updated != $lastupdate;
        }
        return false;
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

    function fetch($id) {
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
            if (!$this->memcache->replace($key, $data, 0, $this->getTTL())) {
                $this->memcache->set($key, $data, 0, $this->getTTL());
                $this->memcache->set("$key.time", Misc::gmtime(), 0, $this->getTTL());
            }
        }
    }

    function checkTouched() {
        // Just check if the timestamp occurred after the start of this
        // session, assuming the session was fetched very shortly thereafter
        $key = $this->getKey($id);
        if ($timestamp = $this->memcache->get("$key.time")) {
            // XXX: Only accurate to 1 second
            return $timestamp > gmdate('U', $_SERVER['REQUEST_TIME_FLOAT']);
        }
        // Else it hasn't yet been saved
        return false;
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

// Locking semantic systems for assistance on parallel request race
// conditions. This functions like a global mutex with a unique identifier.
// For session locking, the session id is used.
abstract class BaseLockSystem {
    protected $id;
    static $registry;

    function __construct($id) {
        $this->id = $id;
    }

    static function isSupported() {
        return true;
    }
    static function register($class) {
        self::$registry[] = $class;
    }

    static function getImpl($id) {
        foreach (self::$registry as $class) {
            if ($class::isSupported())
                return new $class($id);
        }
    }

    /**
     * Acquire the lock. After the timeout period, the lock is granted
     * implicitly. This will prevent long session hangs because of an
     * aborted request or what not
     *
     * Parameters:
     * $timeout - (float) number of seconds to wait for a lock before a
     *      timeout occurs.
     * $waited - (boolean) byref parameter set to TRUE if the locking system
     *      had to wait for the lock
     *
     * Returns:
     * TRUE if the lock was acquired, and FALSE otherwise, including if a
     * timeout occurred.
     */
    abstract function acquire($timeout=5, &$waited=false);
    abstract function release();
}

class ApcuLockingImpl
extends BaseLockSystem {
    protected $key;

    function acquire($timeout=5, &$waited=false) {
        $this->key = 'sesslock'.SECRET_SALT.$this->id;
        $ttl = ini_get('max_execution_time');
        $timeout *= 400;
        while (--$timeout) {
            if (apcu_add($this->key, 'x', $ttl))
                return true;
            // Spinlock in 2.5ms increments
            usleep(2500);
            $waited = true;
        }
    }

    function release() {
        if (isset($this->key))
            apcu_delete($this->key);
    }

    static function isSupported() {
        return function_exists('apcu_add');
    }
}
BaseLockSystem::register('ApcuLockingImpl');

class FileLockingImpl
extends BaseLockSystem {
    protected $fp;
    protected $path;

    function acquire($timeout=5, &$waited=false) {
        $basepath = rtrim(sys_get_temp_dir(), '\\/') . '/sesslock_';
        // Consider cleaning up old lock files every so often, in case of
        // PHP crashes and the file was not cleaned.
        if (rand(0, 200) == 42) {
            $now = time();
            $ttl = ini_get('max_execution_time');
            foreach (glob("{$basepath}*", GLOB_NOSORT) as $f) {
                if ($now - filectime($f) > $ttl)
                    @unlink($f);
            }
        }
        // Don't use the session ID in the filename, as that could allow for
        // session hijacking
        $this->path = $basepath . hash_hmac('md5', $this->id, SECRET_SALT);
        // Use 'c' for the file mode as that is recommended for a following
        // flock() call in the PHP docs
        $this->fp = fopen($this->path, 'c');
        if ($this->fp === false) {
            // Cannot be supported on this platform
            return true;
        }
        $timeout *= 400;
        $blocked = null;
        while (--$timeout) {
            if (flock($this->fp, LOCK_EX | LOCK_NB, $blocked))
                return true;
            if ($blocked != 1)
                // Odd. Should have indiciated block would be required
                break;
            // Spinlock in 2.5ms increments
            usleep(2500);
            $waited = true;
        }
    }

    function release() {
        if ($this->fp) {
            @flock($this->fp, LOCK_UN);
            @unlink($this->path);
        }
    }

    static function isSupported() {
        return function_exists('flock');
    }
}
BaseLockSystem::register('FileLockingImpl');

class LockingArray
extends ArrayObject {
    protected $id;
    protected $lock;
    protected $backend;
    protected $parent;

    function __construct(array $contents, $parent=null) {
        parent::__construct();
        $this->parent = $parent;
        // Unpack contents and wrap nested arrays
        foreach ($contents as $k=>$v)
            $this->offsetSet($k, $v, false);
    }

    function setBackend($id, $backend) {
        $this->id = $id;
        $this->backend = $backend;
    }

    function getRoot() {
        $P = $this;
        while ($P->parent)
            $P = $P->parent;
        return $P;
    }

    function acquireLock($timeout=5) {
        if (!isset($this->lock)) {
            if (!($this->lock = BaseLockSystem::getImpl($this->id))) {
                // Locking cannot be supported on this platform. Perhaps
                // emit a NOTICE?
                return false;
            }
            $waited = false;
            if (!$this->lock->acquire($timeout, $waited)) {
                return false;
            }
            // Check and see if we waited for a lock or if the backend
            // indicates session has been touched since it was loaded in
            // this session
            if ($waited || $this->backend->checkTouched()) {
                // Reload the session from the backend. (If we waited, then
                // it is likely that another request updated the session
                // while we were waiting).
                $this->reload();
            }
        }
        return true;
    }

    function releaseLock() {
        if (isset($this->lock)) {
            $this->lock->release();
        }
    }

    function isLocked() {
        return isset($this->lock);
    }

    function reload() {
        $data = $this->backend->read($this->id);
        // TODO: Deserialize and load $data
        $this->exchangeArray(unserialize($data));
    }

    function asArray() {
        $copy = array();
        foreach ($this as $k=>$v)
            $copy[$k] = $v instanceof self ? $v->asArray() : $v;
        return $copy;
    }

    // ArrayAccess delegates
    function offsetSet($offs, $what, $lock=true) {
        if ($lock)
            $this->getRoot()->acquireLock();
        if (is_array($what))
            $what = new static($what, $this);
        parent::offsetSet($offs, $what);
    }
}
