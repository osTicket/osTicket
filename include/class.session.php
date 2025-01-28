<?php
/*********************************************************************
    class.session.php

    osTicket core Session Handlers & Backends

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2022 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

namespace osTicket\Session {
    // Extend global Exception
    class Exception extends \Exception {}

    // Session backend factory
    // TODO: Allow plugins to register backends.
    class SessionBackend {
        static private $backends = [
            // Database is the default backend for osTicket
            'database'  => ':AbstractSessionStorageBackend',
            // Session data are stored in  memcache saving database the pain
            // of storing and retriving the records
            'memcache'  => ':AbstractMemcacheSessionStorageBackend',
            // Noop/Null SessionHandler doesn't care about sh*t
            'noop'      => 'NoopSessionStorageBackend',
            // Default SessionHandler
            'system'    => 'SystemSessionHandler',
        ];

        static public function getBackends() {
            return self::$backends;
        }

        static public function getBackend(string $bk) {
            return (($backends=self::getBackends())
                    && isset($backends[$bk]))
                ? $backends[$bk]
                : null;
        }

        static public function has(string $bk) {
            return (self::getBackend($bk));
        }

        static public function factory(string $bk, array $options = []) {
            list ($bk, $handler, $secondary) = explode(':', $bk);
            if (!($backend=self::getBackend($bk)))
                throw new Exception('Unknown Session Backend: '.$bk);

            if ($backend[0] == ':') {
                // External implementation required
                if (!$handler)
                    throw new Exception(sprintf(
                                '%s: requires storage backend driver',
                                $bk));
                // External handler needs global namespacing
                $handler ="\\$handler";
                // handler must implement parent
                $impl = substr($backend, 1);
            } elseif ($handler) {
                 $handler ="\\$handler";
            } else {
                // local handler is being used force this namespace
                $handler = sprintf('%s\%s', __NAMESPACE__, $backend);
                $impl = ($bk == 'system') ? 'SystemSessionHandler' : 'AbstractSessionHandler';
            }

            // Make sure handler / backend class exits
            if (!$handler
                    || !class_exists($handler))
                throw new Exception(sprintf('unknown storage backend - [%s]',
                            $handler));

            // Set secondary backend with global namespacing if it is
            // chained to primary backend ($bk). Primary backend will
            // validate and instanciate it if it can support the interface
            // of the said backend.
            if ($secondary)
                $options['secondary'] = "\\$secondary";

            $sessionHandler = new $handler($options);
            // Make sure the handler implements the right interface
            $impl = sprintf('%s\%s', __NAMESPACE__, $impl);
            if ($impl && !is_a($sessionHandler, $impl))
                throw new Exception(sprintf('%s: must implement %s',
                            $handler,  $impl));

            return $sessionHandler;
        }

        static public function register(string $bk, array $options = [], bool
                $register_shutdown = true) {
            if (($handler=self::factory($bk, $options))
                    && session_set_save_handler($handler,
                        $register_shutdown))
                return $handler;
        }
    }

    interface SessionRecordInterface {
        // Basic setters and getters
        function getId();
        function setId(string $id);
        function getData();
        function setData(string $data);
        function setTTL(int $ttl);
        // expire in ttl & commit
        function expire(int $ttl);
        // commit / save the record to storage
        function commit();
        // Checkers
        function isNew();
        function isValid();
        // to array [id, data] expected - other attributes can be returned
        // as well dependin on the storage backend
        function toArray();
    }

    abstract class AbstractSessionHandler implements
        \SessionHandlerInterface,
        \SessionUpdateTimestampHandlerInterface {
        // Options
        protected $options;
        // Callback registry
        private $callbacks;
        // Flags
        private $isnew = false;
        private $isapi = false;
        // maxlife & ttl
        private $ttl;
        private $maxlife;
        // Secondary backend
        protected $secondary = null;

        public function __construct($options = []) {
            // Set flags based on passed in options
            $this->options = $options;

            // Default TTL
            $this->ttl = $this->options['session_ttl'] ??
                ini_get('session.gc_maxlifetime');

            // Dynamic maxlife (MLT)
            if (isset($this->options['session_maxlife']))
                $this->maxlife = $this->options['session_maxlife'];

            // API Session Flag
            if (isset($this->options['api_session']))
                $this->isapi = $this->options['api_session'];

            // callbacks
            if (isset($options['callbacks'])) {
                $this->callbacks = $options['callbacks'];
                unset($options['callbacks']);
            }

            // Set Secondary Backend if any
            if (isset($options['secondary']))
                $this->setSecondaryBackend($options['secondary']);
        }

        /*
         * set a seconday backend... for now it's private but will be public
         * down the road.
         */
        private function setSecondaryBackend($backend, $options = null) {
            // Ignore invalid backend
            if (!$backend
                    // class exists
                    || !class_exists($backend)
                    // Not same backend as handler
                    || !strcasecmp(get_class($this), $backend))
                return false;

            // Init Secondary handler if set and valid
            $options = $options ?? $this->options;
            unset($options['secondary']); //unset secondary to avoid loop.
            $this->secondary = new $backend($options);
            // Make sure it's truly a storage backend and not a Ye!
            if (!is_a($this->secondary,
                         __NAMESPACE__.'\AbstractSessionStorageBackend'))
                $this->secondary = null; // nah...

            return ($this->secondary);
        }

        /*
         * API Sessions are Stateless and new sessions shouldn't be created
         * when this flag is turned on.
         *
         */
        protected function isApiSession() {
            return ($this->isapi);
        }

        /*
         * onEvent
         *
         * Storage Backends that's interested in monitoring / reporting on
         * events can implement this routine.
         *
         */
        protected function onEvent($event) {
            return false;
        }

        // Handles callback for registered event listeners
        protected function callbackOn($event) {
            if (!$this->callbacks
                    || !isset($this->callbacks[$event]))
                return false;

            return (($collable=$this->callbacks[$event])
                    &&  is_callable($collable))
                ? call_user_func($collable, $this)
                : false;
        }


        /*
         * pre_save
         *
         * This is a hook called by session storage backends before saving a
         * session record.
         *
         */
        public function pre_save(SessionRecordInterface $record)  {
            // We're NOT creating new API Sessions since API is stateless.
            // However existing sessions are updated, allowing for External
            // Authentication / Authorization to be processed via API endpoints.
            if ($record->isNew() && $this->isApiSession())
                return false;

            return true;
        }

        /*
         * Default osTicket TTL is the default Session's Maxlife
         */
        public function getTTL() {
            return $this->ttl;
        }

        /*
         * Maxlife is based on logged in user (if any)
         *
         */
        public function getMaxlife() {
            // Prefer maxlife defined based on settings for the
            // current session user - otherwise use default ttl
            if (!isset($this->maxlife))
                $this->maxlife = (defined('SESSION_MAXLIFE')
                        && is_numeric(SESSION_MAXLIFE))
                    ? SESSION_MAXLIFE : $this->getTTL();

            return $this->maxlife;
        }

        public function setMaxLife(int $maxlife) {
            $this->maxlife = $maxlife;
        }

        public function open($save_path, $session_name) {
            return true;
        }

        public function close() {
            return true;
        }

        public function write($id, $data) {
            // Last chance session update - returns true if update is done
            if ($this->onEvent('close'))
                $data = session_encode();
            return $this->update($id, $data);
        }

        public function validateId($id) {
            return true;
        }

        public function updateTimestamp($id, $data) {
            return true;
        }

        public function gc($maxlife) {
           $this->cleanup();
        }

        abstract function read($id);
        abstract function update($id, $data);
        abstract function expire($id, $ttl);
        abstract function destroy($id);
        abstract function cleanup();
    }

    abstract class AbstractSessionStorageBackend extends  AbstractSessionHandler {
        // Record we cache between read & update/write
        private $record;

        protected function onEvent($event) {
            return $this->callbackOn($event);
        }

        public function getRecord($id, $autocreate = false) {
            if (!isset($this->record)
                    || !is_object($this->record)
                   // Mismatch here means new session id
                    || strcmp($id, $this->record->getId()))
                $this->record = static::lookupRecord($id, $autocreate, $this);

            return $this->record;
        }

        // This is the wrapper for to ask the backend to lookup or
        // create/init a record when not found
        protected function getRecordOrCreate($id) {
            return $this->getRecord($id, true);
        }

        public function read($id) {
            // we're auto creating the record if it doesn't exist so we can
            // have a new cached recoed on write/update.
            return (($record = $this->getRecordOrCreate($id))
                    && !$record->isNew())
                ? $record->getData()
                : '';
        }

        public function update($id, $data) {
            if (!($record = $this->getRecord($id)))
                return false;

            // Upstream backend can overwrite saving the record via pre_save hook
            // depending on the type of session or class of user etc.
            if ($this->pre_save($record) === false)
                return true; // record is being ignored

            // Set id & data
            $record->setId($id);
            $record->setData($data);
            // Ask backend to save the record
            if (!$this->saveRecord($record))
                return false;

            // See if we need to send the record to secondary backend to
            // send the record to for logging or audit reasons or whatever!
            try {
                if (isset($this->secondary))
                    $this->secondary->saveRecord($record, true);
            } catch (\Trowable $t) {
                // Ignore any BS!
            }
            // clear cache
            $this->record = null;
            return true;
        }

        public function expire($id, $ttl) {
            // Destroy session record if expire is now.
            if ($ttl == 0)
                return $this->destroy($id);

            if (!$this->expireRecord($id, $ttl))
                return false;

            try {
                if (isset($this->secondary))
                    $this->secondary->expireRecord($id, $ttl);
            } catch (\Trowable $t) {
                // Ignore any BS!
            }
            return true;
        }

        public function destroy($id) {
            if (!($this->destroyRecord($id)))
                return false;

            try {
                if (isset($this->secondary))
                    $this->secondary->destroyRecord($id);
            } catch (\Trowable $t) {
                // Ignore any BS!
            }
            return true;
        }

        public function cleanup() {
            $this->cleanupExpiredRecords();
            try {
                if (isset($this->secondary))
                    $this->secondary->cleanupExpiredRecords();
            } catch (\Trowable $t) {
                // Ignore any BS!
            }
            return true;
        }

        // Backend must implement lookup method to return a record that
        // implements SessionRecordInterface.
        abstract function lookupRecord($id, $autocreate);
        // save record
        abstract function saveRecord($record, $secondary = false);
        // writeRecord is useful when replicating records without the need
        // to transcode it when backends are different
        abstract function writeRecord($id, $data);
        // expireRecord
        abstract function expireRecord($id, $ttl);
        // Backend should implement destroyRecord that takes $id to avoid
        // the need to do record lookup
        abstract function destroyRecord($id);
        // Clear expired records - backend knows best how
        abstract function cleanupExpiredRecords();
    }

    abstract class AbstractMemcacheSessionStorageBackend
     extends AbstractSessionStorageBackend {
        private $memcache;
        private $servers = [];

        public function __construct($options) {
            parent::__construct($options);
            // Make sure we have memcache module installed
            if (!extension_loaded('memcache'))
                throw new Exception('Memcache extension is missing');

            // Require servers to be defined
            if (!isset($options['servers'])
                    || !is_array($options['servers'])
                    || !count($options['servers']))
                 throw new Exception('Memcache severs required');

            // Init Memchache module
            $this->memcache = new \Memcache();

            // Add servers
            foreach ($options['servers'] as $server) {
                list($host, $port) = explode(':', $server);
                // Use port '0' for unix sockets
                if (strpos($host, '/') !== false)
                    $port = 0;
                elseif (!$port)
                    $port = ini_get('memcache.default_port') ?: 11211;
                $this->addServer(trim($host), (int) trim($port));
            }

            if (!$this->getNumServers())
                throw new Exception('At least one memcache severs required');

            // Init Memchache module
            $this->memcache = new \Memcache();
        }

        protected function addServer($host, $port) {
            // TODO: Support add options
            // FIXME: Crash or warn if invalid $host or $port
            // Cache Servers locally
            $this->servers[] = [$host, $port];
            // Add Server
            $this->memcache->addServer($host, $port);
        }

        protected function getNumServers() {
            return count($this->getServers());
        }

        protected function getServers() {
            return $this->servers;
        }

        protected function getKey($id) {
            return sha1($id.SECRET_SALT);
        }

        protected function get($id) {
            // get key
            $key = $this->getKey($id);
            // Attempt distributed read
            $data = $this->memcache->get($key);
            // Read from other servers on failure
            if ($data === false
                    && $this->getNumServers()) {
                foreach ($this->getServers() as $server) {
                    list($host, $port) = $server;
                    $this->memcache->pconnect($host, $port);
                    if ($data = $this->memcache->get($key))
                        break;
                }
            }
            return $data;
        }

        protected function set($id, $data) {
            // Since memchache takes care of carbage collection internally
            // we want to make sure we set data to expire based on the session's
            // maxidletime (if available) otherwise it defailts to SESSION_TTL.
            // Memchache has a maximum ttl of 30 days
            $ttl = min($this->getMaxlife(), 2592000);
            $key = $this->getKey($id);
            foreach ($this->getServers() as $server) {
                list($host, $port) = $server;
                $this->memcache->pconnect($host, $port);
                if (!$this->memcache->replace($key, $data, 0, $ttl))
                    $this->memcache->set($key, $data, 0, $ttl);
            }
            // FIXME: Return false if we fail to write to at least one server
            return true;
        }

        public function expireRecord($id, $ttl) {
            return true;
        }

        public function destroyRecord($id) {
            $key = $this->getKey($id);
            foreach ($this->getServers() as $server) {
                list($host, $port) = $server;
                $this->memcache->pconnect($host, $port);
                $this->memcache->replace($key, '', 0, 1);
                $this->memcache->delete($key, 0);
            }
            return true;
        }

        public function cleanupExpiredRecords() {
             // Memcache does this automatically
            return true;
        }

        abstract function writeRecord($id, $data);
        abstract function saveRecord($record, $secondary = false);
    }

    /*
     * NoopSessionHandler
     *
     * Use this session handler when you don't care about session data.
     *
     */
    class NoopSessionStorageBackend extends AbstractSessionHandler {
        public function read($id) {
            return "";
        }

        public function update($id, $data) {
            return true;
        }

        public function expire($id, $ttl) {
            return true;
        }

        public function destroy($id) {
            return true;
        }

        public function gc($maxlife) {
            return true;
        }

        public function cleanup() {
            return true;
        }
    }

    // Delegate everything to PHP Default SessionHandler
    class SystemSessionHandler extends \SessionHandler {
        public function __construct() {
        }
    }
}
