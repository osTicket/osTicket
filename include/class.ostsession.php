<?php
/*********************************************************************
    class.ostsession.php

    osTicket Session Management Backend

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2022 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
include_once INCLUDE_DIR.'class.session.php';

class osTicketSession {
    // Session SSID
    private $name = 'OSTSESSID';
    // Session Backend instance
    private $backend;
    // Session default TTL
    private $ttl;

    function __construct($name, $ttl = SESSION_TTL, $checkdbversion = false) {
        // session name/ssid
        if ($name && strcmp($this->name, $name))
            $this->name = $name;
        // Session ttl cannot exceed php.ini maxlifetime setting
        $maxlife =  ini_get('session.gc_maxlifetime');
        $this->ttl = min($ttl ?: ($maxlife ?: SESSION_TTL), $maxlife);
        // Set osTicket specific session name/sessid
        session_name($this->name);
        // Set Default cookie Params before we start the session
        session_set_cookie_params($this->ttl, ROOT_PATH, Http::domain(),
            osTicket::is_https(), true);

        /** Determine Session Backend to use **/
        if ((!defined('MAJOR_VERSION') || $checkdbversion)
                && OsticketConfig::getDBVersion())
            // Default PHP SessionHandler
            $bk = 'system';
        else  // default is database
            $bk = self::session_backend() ?: 'database';

        // Tag the backend with storage backend class
        switch ($bk) {
            case 'database':
                $bk = "$bk:DatabaseSessionStorageBackend";
                break;
            case 'memcache':
                $bk = "$bk:MemcacheSessionStorageBackend";
                break;
            case 'memcache.database':
                // memcache is primary while database is secondary
                // Database doesn't store data when set as secondary, but
                // very useful when session data is offloaded to memcache with
                // database tracking sessions ids and expire time for the
                // purpose of knowing who is online.
                $bk = sprintf('%s:%s:%s',
                        'memcache',
                        'MemcacheSessionStorageBackend',
                        'DatabaseSessionStorageBackend');
                break;
            case 'database.memcache':
                // database is primary while memcache is secondary
                // This setup only makes sense if memcache is being used as
                // backup backend
                $bk = sprintf('%s:%s:%s',
                        'database',
                        'DatabaseSessionStorageBackend',
                        'MemcacheSessionStorageBackend');
                break;
            case 'system':
            case 'noop':
                // system & noop don't require storage backends
                break;
            default:
                // Assume invalid entry - default to system
                $bk = 'system';
        }

        /** Session Backend options **/
        $options = [
            // Default TTL (maxlife)
            'session_ttl' => $this->ttl,
            // It indicates that the session is API session - which session
            // handler should handle as stateless for new sessions.
            'api_session' => defined('API_SESSION'),
            'callbacks' => $this->getCallbacks($bk),
        ];

        // Set MaxLifeTime if defined. This is defined per user so it's
        // preferred over ttl.
        if (defined('SESSION_MAXLIFE') && is_numeric(SESSION_MAXLIFE))
            $options['session_maxlife'] = SESSION_MAXLIFE;

        // If $bk doesn't exit then an Exception will be thrown
        // Backend is object is turned on success
        try {
            // If $bk doesn't exit then an Exception will be thrown
            // backend object is turned on success
            $this->backend = osTicket\Session\SessionBackend::register($bk,
                $options, true);
        } catch (Throwable $t) {
            die($t->getMessage());
            // We're just gonna default to php session handler and hope for
            // the best.
            // TODO: Log the error and perhaps rethrow the exception so it
            // can be fatal?
        }

        // Finally start the damn session.
        session_start();
    }

    // returns session callbacks we might be interested in monitoring
    private function getCallbacks($bk) {
        return [
            // see onClose routine for details
            'close' => [$this, 'onClose']
        ];
    }

    // onClose - is used to signal those interested on changing session
    // data, to do so,  before data is commited.
    public function onClose($handler) {
        $i = new ArrayObject(['touched' => false]);
        Signal::send('session.close', null, $i);
        return (bool) $i['touched'];
    }

    /*
     * session_backend
     *
     * Get configured session backend if any.
     */
    static function session_backend($default = false) {
        // Session Disabled?
        // This is useful when fetching logos or on CLI - we don't want to update the
        // session in such cases.
        if (defined('NOOP_SESSION') || defined('DISABLE_SESSION'))
            return 'noop'; // Ignore session data

         // No session backend set - return default
        if (!defined('SESSION_BACKEND'))
            return $default;

        // Explode backend incase it's chained
        list($bk, $secondary) = explode('.', SESSION_BACKEND);
        // Only recongnize supported primary backends
        switch (strtolower($bk)) {
            case 'memcache':
                // Make sure we have memcache servers defined  - if not
                // then break so we can return default.
                // TODO: Log an error or throw an exception
                if (!defined('MEMCACHE_SERVERS'))
                    break;
                // No break on purpose
            case 'database':
            case 'system':
                return strtolower(SESSION_BACKEND);
                break;
            default:
                return $default;
        }
    }

    /*
     * registered_backend
     *
     * get registered backend if any
     */
    static function registered_backend() {
        global $ost;
        if ($ost && isset($ost->session))
            return $ost->session->backend;
    }

    /*
     * regenerate($ttl)
     *
     * Regenerate current session_id and expire the old one in $ttl seconds
     * This is preferred over destroying the session immediately just incase
     * we have pending ajax requests for example
     *
     */
    static function regenerate(int $ttl=60) {
        // Make sure session is active and headers are not already sent
        if (session_status() !== PHP_SESSION_ACTIVE
                || headers_sent())
            return false;

        // Save current ($old) session id
        $old = session_id();
        // Expire current session cookie now + ttl
        // We have to do it here before re regenerate becase renewCookie has
        // hardcoded session calls to get name & id
        // FIXME: Maybe bae...
        self::renewCookie(time(), $ttl);
        // Regenerate the session
        // TODO: use session_create_id() instead of session_regenerate_id -
        // but PHP (even 8) is still bugy and inconsistent when it comes to
        // using SessionIdInterface::create_sid depening on session settings.
        session_regenerate_id(false);
        // get a new session id and force commit
        // Expire old session now + ttl
        session_write_close();
        // Expire old session now + ttl
        // ::expire() is not a standard session routine so we have to commit
        // the current "new" session first
        self::expire($old, $ttl);
        // Restart the new session
        session_start();
        // Return the new session id
        return session_id();
    }

    /*
     * expire session
     *
     * Expire session in $ttl from now  if the storage backend supports
     * it otherwise it should destroy it by calling this->destroy($id)
     */
    // Expire session - end is near mb!
    static function expire($id, int $ttl) {
        // See if we have a backend to ask to expire the session - otherwise
        // we destroy session now!
        if (!($backend=self::registered_backend()))
            return false;

        // Expire session soonish (now() + $ttl) - end is near mb!
        return (bool) $backend->expire($id, $ttl);
    }

    // Aks the backend to destroy a session now
    static function destroy($id) {
        // Expire with ttl of 0 destroys the session
        return (bool) self::expire($id, 0);
    }

    // Cleanup Expired Sessions
    static function cleanup() {
        // get active backend
        if (!($backend=self::registered_backend()))
            return false;

        return (bool) $backend->cleanup();
    }

    static function renewCookie($baseTime=false, $window=false) {
        $ttl = $window ?: SESSION_TTL;
        $expire = ($baseTime ?: time()) + $ttl;
        setcookie(session_name(), session_id(), $expire,
            ini_get('session.cookie_path'),
            ini_get('session.cookie_domain'),
            ini_get('session.cookie_secure'),
            ini_get('session.cookie_httponly'));
        // Trigger expire update - neeed for secondary handlers that only
        // log new sessions
         self::expire(session_id(), $ttl);
    }

    static function destroyCookie() {
        setcookie(session_name(), 'deleted', 1,
            ini_get('session.cookie_path'),
            ini_get('session.cookie_domain'),
            ini_get('session.cookie_secure'),
            ini_get('session.cookie_httponly'));
    }

    static function get_online_users(int $seconds = 0) {
        // Authoretative lookup is DatabaseSessionRecords assuming
        // database is the primary backend or secondary logger
        $records = DatabaseSessionRecord::active_sessions([
                'lastseen' => $seconds,
                'authenticated' => true,
         ]);
        $users = [];
        foreach ($records as $record)
             $users[] = $record->getUserId();
        return $users;
    }

    static function start($name, $ttl=0, $checkdbversion=false) {
        return new static($name, $ttl, $checkdbversion);
    }
}

/*
 * DatabaseSessionRecord
 *
 * ORM hook to session table with SessionRecordInterface implementation
 */
class DatabaseSessionRecord extends VerySimpleModel
    implements osTicket\Session\SessionRecordInterface {
    static $meta = array(
        'table' => SESSION_TABLE,
        'pk' => array('session_id'),
    );

    public function isNew() {
        return $this->__new__;
    }

    public function isValid() {
        // NOTE: We're not enforcing IP match here because it's a user space
        // setting and checked elsewhere

        // User Agent must remain same for the lifecycle of the session
        if (isset($this->user_agent)
                && strcmp($_SERVER['HTTP_USER_AGENT'], $this->user_agent) !== 0)
            return false;

        // Make sure id len is 32
        return (strlen($this->getId()) == 32);
    }

    public function setId(string $id) {
        $this->session_id = $id;
        return $this;
    }

    public function getId() {
        return $this->session_id;
    }

    public function setData(string $data = null) {
        $this->session_data = $data;
        return $this;
    }

    public function getData() {
        return $this->session_data;
    }

    public function getExpireTime() {
        return $this->session_expire;
    }

    private function setExpire(int $maxlife) {
        $this->session_expire = SqlFunction::NOW()
            ->plus(SqlInterval::SECOND($maxlife));
    }

    public function setTTL($ttl) {
        $this->setExpire($ttl);
        return $this;
    }

    public function expire(int $ttl) {
        $this->setTTL($ttl);
        // Assume it will expire shortly - clear user_id
        $this->user_id = 0;
        return $this->commit();
    }

    public function getUpdateTime() {
        return $this->session_updated;
    }

    public function commit() {
        return ($this->save());
    }

    public function save($refresh=false) {
        global $thisstaff;
        // See if we need to set the user id - should only be set once onlogin
        if (!$this->user_id && $thisstaff)
            $this->user_id = $thisstaff->getId();
        if (count($this->dirty))
            $this->session_updated = SqlFunction::NOW();
        return parent::save($refresh);
    }

    public function delete() {
        return (parent::delete());
    }

    public function toArray() {
        return [
            'id' => $this->getId(),
            'data' => $this->getData(),
            'updated' => $this->getUpdateTime(),
            'expires' => $this->getExpireTime(),
        ];
    }

    /*
     *  lookupRecord
     *
     *  Given session id - lookup the record
     *
     *  Possible returns;
     *      false: on lookup failure
     *      null: doesn't exists and autocreate is false
     *      record: fetched or newly created session record
     *
     */
    public static function lookupRecord($id, $autocreate = false,
            $backend=null) {
        // We're doing lookup locally so we can auto-create one if the
        // session doesn't exist.
        $record = false;
        try {
            $record = self::objects()
              ->filter(['session_id' => $id])
              ->annotate(array('is_expired' =>
                new SqlExpr(new Q(array('session_expire__lt' => SqlFunction::NOW())))))
              ->one();
            if ($record->is_expired > 0) {
                // session_expire is in the past. Pretend it is expired and
                // reset the data. This will assist with CSRF issues
                $record->session_data = '';
            }
        }
        catch (DoesNotExist $e) {
            // We're auto-creating model (unsaved) when one doesn't exist?
            $record = $autocreate ? self::create($id) : null;
        }
        catch (OrmException | Exception $ex) {
            // This could happen if more than one record exits in the
            // database for example.
        }
        return $record;
    }

    static function create($vars) {
        // We expect ::init($id) or ::init(array $vars)
        if ($vars && !is_array($vars))
            $vars = ['session_id' => $vars];
        elseif (!isset($vars['session_id']))
            // Session Id is required
            throw Exception(sprintf('session_id: %s', __('Required')));

        // Set User Session Atrributes
        if (!isset($vars['user_ip']))
            $vars['user_ip'] = $_SERVER['REMOTE_ADDR'];
        if (!isset($vars['user_agent']))
            $vars['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        // Default to system SESSION_TTL if session_maxlife is not set
        $maxlife = $vars['session_maxlife'] ?? SESSION_TTL;
        // filter out the $vars to just the fields we support - backend can
        // send way more data than we want.
        $vars = array_intersect_key($vars,
                array_flip([
                    'session_id',
                    'session_data',
                    'session_expire',
                    'session_updated',
                    'user_id',
                    'user_ip',
                    'user_agent',
        ]));

        // Create an instance
        $record = new self($vars);
        // set session_expire timestamp based on $maxlife
        $record->setExpire($maxlife);
        // Set updated to now()
        $record->session_updated = SqlFunction::NOW();
        return $record;
    }

    static function destroy($id) {
        return ( (bool) self::objects()
                ->filter(['session_id' => $id])
                ->delete());
    }

    static function cleanupExpired() {
        return self::objects()->filter([
                'session_expire__lte' => SqlFunction::NOW()
        ])->delete();
    }

    static function user_sessions(int $id)  {
        $criteria = ['user_id' => $id];
        return self::active_sessions($criteria);
    }

    static function active_sessions(array $criteria = []) {
        $criteria['active'] = true;
        return self::sessions($criteria);
    }

    static function expired_sessions(array $criteria = []) {
        $criteria['active'] = false;
        return self::sessions($criteria);
    }

    static function sessions(array $criteria = []) {
        // now
        $now = SqlFunction::NOW();
        // empty filters
        $filters = [];
        // Active or Expired
        if (isset($criteria['active']) && $criteria['active']) {
            // Active must not be expired
            $filters = ['session_expire__gt' => $now];
        } elseif (isset($criteria['active'])) {
            // expired session if active is set to false
            $filters = ['session_expire__lt' => $now];
        }

        // Authenticated users have user_id set (only Agents at the moment)
        if (isset($criteria['user_id']) && $criteria['user_id'])
            $filters['user_id'] = $criteria['user_id'];
        elseif (isset($criteria['authenticated']) && $criteria['authenticated'])
            $filters['user_id__gt'] = 0;
        elseif (isset($criteria['authenticated']))
            $filters['user_id'] = 0; // Guests only

        // last seen since X seconds
        if (isset($criteria['lastseen']) && $criteria['lastseen']) {
            $interval = new SqlInterval('SECOND', $criteria['lastseen']);
            $filters['session_updated__gte'] = $now->minus($interval);
        }

        return self::objects()->filter($filters);
    }
}

/*
 * Database Session Storage Backend
 *
 */
class DatabaseSessionStorageBackend
    extends osTicket\Session\AbstractSessionStorageBackend {

    public function expireRecord($id, $ttl) {
        if (!($record=$this->getRecord($id)))
            return false;

        return (bool) $record->expire($ttl);
    }

    public function saveRecord($record, $secondary=false) {
        if ($secondary || !is_a($record, 'DatabaseSessionRecord'))  {
            // We're only recording new sessions without data if we're
            // secondary backend
            if ($record->isNew())
                $this->writeRecord($record->getId(), null);
            return true;
        }
        return (bool) $record->commit();
    }

    public function writeRecord($id, $data=null) {
        $record = self:: lookupRecord($id, true);
        $record->setData($data);
        return (bool) $record->commit();
    }

    public function lookupRecord($id, $autocreate) {
        return DatabaseSessionRecord::lookupRecord($id, $autocreate, $this);
    }

    public function destroyRecord($id) {
        return (bool) (DatabaseSessionRecord::destroy($id));
    }

    public function cleanupExpiredRecords() {
        return DatabaseSessionRecord::cleanupExpired();
    }
}
/*
 * Memchache Session Storage Backend
 *
 */
class MemcacheSessionStorageBackend
    extends osTicket\Session\AbstractMemcacheSessionStorageBackend {

    public function __construct($options) {
        // Make sure we have memcache servers
        $servers = [];
        if (isset($options['servers']) && is_array($options['servers']))
            $servers = $options['servers'];
        elseif (defined('MEMCACHE_SERVERS'))
            $servers = explode(',', MEMCACHE_SERVERS);
        // Bro Got Servers or Nah?!
        if (!count($servers))
            throw new Exception('MEMCACHE_SERVERS must be defined');

        $options['servers'] = $servers;
        parent::__construct($options);
    }

    public function saveRecord($record, $secondary = false) {
        if ($secondary || !is_a($record, 'MemcacheSessionRecord'))  {
            if (!$record->isNew())
                return true;
        }
        return $this->writeRecord($record->getId(), $record->getData());
    }

    public function writeRecord($id, $data) {
        return $this->set($id, $data);
    }

    public function lookupRecord($id, $autocreate = false) {
        if (!($data = $this->get($id)) && !$autocreate)
            return false;
        return new  MemcacheSessionRecord([
                'id' => $id,
                'new' => $data === false,
                'data' => $data,
        ], $this);
    }
}

class MemcacheSessionRecord
implements osTicket\Session\SessionRecordInterface {
    private $backend;
    private $data;

    public function __construct(array $ht, $backend) {
        // Make sure we have an id
        if (!isset($ht['id']) || !$ht['id'])
            throw new Exception('Session id required');
        // Set backend
        $this->backend = $backend;
        // Set the data as array object
        $this->data = new ArrayObject($ht, ArrayObject::ARRAY_AS_PROPS);
    }

    public function isNew() {
        return ($this->data->new);
    }

    public function isValid() {
        return (strlen($this->getId()) == 32);
    }

    public function getId() {
        return $this->data->id;
    }

    public function setId(string $id) {
        $this->data->id = $id;
    }

    public function getData() {
        return $this->data->data;
    }

    public function setData(string $data = null) {
        $this->data->data = $data;
    }

    public function setTTl(int $ttl) {
        $this->data->ttl = $ttl;
    }

    // Unsupported call but required
    public function expire(int $ttl) {
        return true;
    }

    public function commit() {
        return $this->backend
            ? $this->backend->saveRecord($this)
            : false;
    }

    public function toArray() {
        return [
            'id' => $this->getId(),
            'data' => $this->getData(),
        ];
    }

    public static function create($vars) {
        return new self($vars);
    }
}
?>
