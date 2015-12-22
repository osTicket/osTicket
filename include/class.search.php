<?php
/*********************************************************************
    module.search.php

    Search Engine for osTicket

    This module defines the pieces for a search engine for osTicket.
    Searching can be performed by various search engine backends which can
    make use of the features of various search providers.

    A reference search engine backend is provided which uses MySQL MyISAM
    tables. This default backend should not be used on Galera clusters.

    Jared Hancock <jared@osticket.com>
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once INCLUDE_DIR . 'class.role.php';
require_once INCLUDE_DIR . 'class.list.php';

abstract class SearchBackend {
    static $id = false;
    static $registry = array();

    const SORT_RELEVANCE = 1;
    const SORT_RECENT = 2;
    const SORT_OLDEST = 3;

    const PERM_EVERYTHING = 'search.all';

    static protected $perms = array(
        self::PERM_EVERYTHING => array(
            'title' => /* @trans */ 'Search',
            'desc'  => /* @trans */ 'See all tickets in search results, regardless of access',
            'primary' => true,
        ),
    );

    abstract function update($model, $id, $content, $new=false, $attrs=array());
    abstract function find($query, QuerySet $criteria, $addRelevance=true);

    static function register($backend=false) {
        $backend = $backend ?: get_called_class();

        if ($backend::$id == false)
            throw new Exception('SearchBackend must define an ID');

        static::$registry[$backend::$id] = $backend;
    }

    function getInstance($id) {
        if (!isset(self::$registry[$id]))
            return null;

        return new self::$registry[$id]();
    }

    static function getPermissions() {
        return self::$perms;
    }
}
RolePermission::register(/* @trans */ 'Miscellaneous', SearchBackend::getPermissions());

// Register signals to intercept saving of various content throughout the
// system

class SearchInterface {

    var $backend;

    function __construct() {
        $this->bootstrap();
    }

    function find($query, QuerySet $criteria, $addRelevance=true) {
        $query = Format::searchable($query);
        return $this->backend->find($query, $criteria, $addRelevance);
    }

    function update($model, $id, $content, $new=false, $attrs=array()) {
        if ($this->backend)
            $this->backend->update($model, $id, $content, $new, $attrs);
    }

    function createModel($model) {
        return $this->updateModel($model, true);
    }

    function deleteModel($model) {
        if ($this->backend)
            $this->backend->delete($model);
    }

    function updateModel($model, $new=false) {
        // The MySQL backend does not need to index attributes of the
        // various models, because those other attributes are available in
        // the local database in other tables.
        switch (true) {
        case $model instanceof ThreadEntry:
            // Only index an entry for threads if a human created the
            // content
            if (!$model->getUserId() && !$model->getStaffId())
                break;

            $this->update($model, $model->getId(),
                $model->getBody()->getSearchable(),
                $new === true,
                array(
                    'title' =>      $model->getTitle(),
                    'created' =>    $model->getCreateDate(),
                )
            );
            break;

        case $model instanceof Ticket:
            $cdata = array();
            foreach ($model->loadDynamicData() as $a)
                if ($v = $a->getSearchable())
                    $cdata[] = $v;
            $this->update($model, $model->getId(),
                trim(implode("\n", $cdata)),
                $new === true,
                array(
                    'title'=>       Format::searchable($model->getSubject()),
                    'number'=>      $model->getNumber(),
                    'status'=>      $model->getStatus(),
                    'topic_id'=>    $model->getTopicId(),
                    'priority_id'=> $model->getPriorityId(),
                    // Stats (comments, attachments)
                    // Access constraints
                    'dept_id'=>     $model->getDeptId(),
                    'staff_id'=>    $model->getStaffId(),
                    'team_id'=>     $model->getTeamId(),
                    // Sorting and ranging preferences
                    'created'=>     $model->getCreateDate(),
                    // Add last-updated timestamp
                )
            );
            break;

        case $model instanceof User:
            $cdata = array();
            foreach ($model->getDynamicData($false) as $e)
                foreach ($e->getAnswers() as $tag=>$a)
                    if ($tag != 'subject' && ($v = $a->getSearchable()))
                        $cdata[] = $v;
            $this->update($model, $model->getId(),
                trim(implode("\n", $cdata)),
                $new === true,
                array(
                    'title'=>       Format::searchable($model->getFullName()),
                    'emails'=>      $model->emails->asArray(),
                    'org_id'=>      $model->getOrgId(),
                    'created'=>     $model->getCreateDate(),
                )
            );
            break;

        case $model instanceof Organization:
            $cdata = array();
            foreach ($model->getDynamicData(false) as $e)
                foreach ($e->getAnswers() as $a)
                    if ($v = $a->getSearchable())
                        $cdata[] = $v;
            $this->update($model, $model->getId(),
                trim(implode("\n", $cdata)),
                $new === true,
                array(
                    'title'=>       Format::searchable($model->getName()),
                    'created'=>     $model->getCreateDate(),
                )
            );
            break;

        case $model instanceof FAQ:
            $this->update($model, $model->getId(),
                $model->getSearchableAnswer(),
                $new === true,
                array(
                    'title'=>       Format::searchable($model->getQuestion()),
                    'keywords'=>    $model->getKeywords(),
                    'topics'=>      $model->getHelpTopicsIds(),
                    'category_id'=> $model->getCategoryId(),
                    'created'=>     $model->getCreateDate(),
                )
            );
            break;

        default:
            // Not indexed
            break;
        }
    }

    function bootstrap() {
        // Determine the backend
        if (defined('SEARCH_BACKEND'))
            $bk = SearchBackend::getInstance(SEARCH_BACKEND);

        if (!$bk && !($bk = SearchBackend::getInstance('mysql')))
            // No backend registered or defined
            return false;

        $this->backend = $bk;
        $this->backend->bootstrap();

        $self = $this;

        // Thread entries
        // Tickets, which can be edited as well
        // Knowledgebase articles (FAQ and canned responses)
        // Users, organizations
        Signal::connect('threadentry.created', array($this, 'createModel'));
        Signal::connect('ticket.created', array($this, 'createModel'));
        Signal::connect('user.created', array($this, 'createModel'));
        Signal::connect('organization.created', array($this, 'createModel'));
        Signal::connect('model.created', array($this, 'createModel'), 'FAQ');

        Signal::connect('model.updated', array($this, 'updateModel'));
        Signal::connect('model.deleted', array($this, 'deleteModel'));
    }
}

require_once(INCLUDE_DIR.'class.config.php');
class MySqlSearchConfig extends Config {
    var $table = CONFIG_TABLE;

    function __construct() {
        parent::__construct("mysqlsearch");
    }
}

class MysqlSearchBackend extends SearchBackend {
    static $id = 'mysql';
    static $BATCH_SIZE = 30;

    // Only index 20 batches per cron run
    var $max_batches = 60;
    var $_reindexed = 0;
    var $SEARCH_TABLE;

    function __construct() {
        $this->SEARCH_TABLE = TABLE_PREFIX . '_search';
    }

    function getConfig() {
        if (!isset($this->config))
            $this->config = new MySqlSearchConfig();
        return $this->config;
    }


    function bootstrap() {
        if ($this->getConfig()->get('reindex', true))
            Signal::connect('cron', array($this, 'IndexOldStuff'));
    }

    function update($model, $id, $content, $new=false, $attrs=array()) {
        if (!($type=ObjectModel::getType($model)))
            return;

        if ($model instanceof Ticket)
            $attrs['title'] = $attrs['number'].' '.$attrs['title'];
        elseif ($model instanceof User)
            $content .=' '.implode("\n", $attrs['emails']);

        $title = $attrs['title'] ?: '';

        if (!$content && !$title)
            return;
        if (!$id)
            return;

        $sql = 'REPLACE INTO '.$this->SEARCH_TABLE
            . ' SET object_type='.db_input($type)
            . ', object_id='.db_input($id)
            . ', content='.db_input($content)
            . ', title='.db_input($title);
        return db_query($sql, false);
    }

    function delete($model) {
        switch (true) {
        case $model instanceof Thread:
            $sql = 'DELETE s.* FROM '.$this->SEARCH_TABLE
                . " s JOIN ".THREAD_ENTRY_TABLE." h ON (h.id = s.object_id) "
                . " WHERE s.object_type='H'"
                . ' AND h.thread_id='.db_input($model->getId());
            return db_query($sql);

        default:
            if (!($type = ObjectModel::getType($model)))
                return;

            $sql = 'DELETE FROM '.$this->SEARCH_TABLE
                . ' WHERE object_type='.db_input($type)
                . ' AND object_id='.db_input($model->getId());
            return db_query($sql);
        }
    }

    // Quote things like email addresses
    function quote($query) {
        $parts = array();
        if (!preg_match_all('`(?:([^\s"\']+)|"[^"]*"|\'[^\']*\')(\s*)`', $query, $parts,
                PREG_SET_ORDER))
            return $query;

        $results = array();
        foreach ($parts as $m) {
            // Check for quoting
            if ($m[1] // Already quoted?
                && preg_match('`@`u', $m[0])
            ) {
                $char = strpos($m[1], '"') ? "'" : '"';
                $m[0] = $char . $m[0] . $char;
            }
            $results[] = $m[0].$m[2];
        }
        return implode('', $results);
    }

    function find($query, QuerySet $criteria, $addRelevance=true) {
        global $thisstaff;

        // MySQL usually doesn't handle words shorter than three letters
        // (except with special configuration)
        if (strlen($query) < 3)
            return $criteria;

        $criteria = clone $criteria;

        $mode = ' IN NATURAL LANGUAGE MODE';

        // According to the MySQL full text boolean mode, this grammar is
        // assumed:
        // see http://dev.mysql.com/doc/refman/5.6/en/fulltext-boolean.html
        //
        // PREOP    = [<>~+-]
        // POSTOP   = [*]
        // WORD     = [\w][\w-]*
        // TERM     = PREOP? WORD POSTOP?
        // QWORD    = " [^"]+ "
        // PARENS   = \( { { TERM | QWORD } { \s+ { TERM | QWORD } }+ } \)
        // EXPR     = { PREOP? PARENS | TERM | QWORD }
        // BOOLEAN  = EXPR { \s+ EXPR }*
        //
        // Changing '{' for (?: and '}' for ')', collapsing whitespace, we
        // have this regular expression
        $BOOLEAN = '(?:[<>~+-]?\((?:(?:[<>~+-]?[\w][\w-]*[*]?|"[^"]+")(?:\s+(?:[<>~+-]?[\w][\w-]*[*]?|"[^"]+"))+)\)|[<>~+-]?[\w][\w-]*[*]?|"[^"]+")(?:\s+(?:[<>~+-]?\((?:(?:[<>~+-]?[\w][\w-]*[*]?|"[^"]+")(?:\s+(?:[<>~+-]?[\w][\w-]*[*]?|"[^"]+"))+)\)|[<>~+-]?[\w][\w-]*[*]?|"[^"]+"))*';

        // Require the use of at least one operator and conform to the
        // boolean mode grammar
        if (preg_match('`(^|\s)["()<>~+-]`u', $query, $T = array())
            && preg_match("`^{$BOOLEAN}$`u", $query, $T = array())
        ) {
            // If using boolean operators, search in boolean mode. This regex
            // will ensure proper placement of operators, whitespace, and quotes
            // in an effort to avoid crashing the query at MySQL
            $query = $this->quote($query);
            $mode = ' IN BOOLEAN MODE';
        }
        #elseif (count(explode(' ', $query)) == 1)
        #    $mode = ' WITH QUERY EXPANSION';
        $search = 'MATCH (Z1.title, Z1.content) AGAINST ('.db_input($query).$mode.')';

        switch ($criteria->model) {
        case false:
        case 'Ticket':
            if ($addRelevance) {
                $criteria = $criteria->extra(array(
                    'select' => array(
                        '__relevance__' => 'Z1.`relevance`',
                    ),
                ));
            }
            $criteria->extra(array(
                'tables' => array(
                    str_replace(array(':', '{}'), array(TABLE_PREFIX, $search),
                    "(SELECT COALESCE(Z3.`object_id`, Z5.`ticket_id`, Z8.`ticket_id`) as `ticket_id`, SUM({}) AS `relevance` FROM `:_search` Z1 LEFT JOIN `:thread_entry` Z2 ON (Z1.`object_type` = 'H' AND Z1.`object_id` = Z2.`id`) LEFT JOIN `:thread` Z3 ON (Z2.`thread_id` = Z3.`id` AND Z3.`object_type` = 'T') LEFT JOIN `:ticket` Z5 ON (Z1.`object_type` = 'T' AND Z1.`object_id` = Z5.`ticket_id`) LEFT JOIN `:user` Z6 ON (Z6.`id` = Z1.`object_id` and Z1.`object_type` = 'U') LEFT JOIN `:organization` Z7 ON (Z7.`id` = Z1.`object_id` AND Z7.`id` = Z6.`org_id` AND Z1.`object_type` = 'O') LEFT JOIN :ticket Z8 ON (Z8.`user_id` = Z6.`id`) WHERE {} GROUP BY `ticket_id`) Z1"),
                )
            ));
            $criteria->filter(array('ticket_id'=>new SqlCode('Z1.`ticket_id`')));
            break;

        case 'User':
            $criteria->extra(array(
                'select' => array(
                    '__relevance__' => 'Z1.`relevance`',
                ),
                'tables' => array(
                    str_replace(array(':', '{}'), array(TABLE_PREFIX, $search),
                    "(SELECT Z6.`id` as `user_id`, {} AS `relevance` FROM `:_search` Z1 LEFT JOIN `:user` Z6 ON (Z6.`id` = Z1.`object_id` and Z1.`object_type` = 'U') LEFT JOIN `:organization` Z7 ON (Z7.`id` = Z1.`object_id` AND Z7.`id` = Z6.`org_id` AND Z1.`object_type` = 'O') WHERE {}) Z1"),
                )
            ));
            $criteria->filter(array('id'=>new SqlCode('Z1.`user_id`')));
            break;

        case 'Organization':
            $criteria->extra(array(
                'select' => array(
                    '__relevance__' => 'Z1.`relevance`',
                ),
                'tables' => array(
                    str_replace(array(':', '{}'), array(TABLE_PREFIX, $search),
                    "(SELECT Z2.`id` as `org_id`, {} AS `relevance` FROM `:_search` Z1 LEFT JOIN `:organization` Z2 ON (Z2.`id` = Z1.`object_id` AND Z1.`object_type` = 'O') WHERE {}) Z1"),
                )
            ));
            $criteria->filter(array('id'=>new SqlCode('Z1.`org_id`')));
            break;
        }

        // TODO: Ensure search table exists;
        if (false) {
            // TODO: Create the search table automatically
            // $class::createSearchTable();
        }
        return $criteria;
    }

    static function createSearchTable() {
        // Use InnoDB with Galera, MyISAM with v5.5, and the database
        // default otherwise
        $sql = "select count(*) from information_schema.tables where
            table_schema='information_schema' and table_name =
            'INNODB_FT_CONFIG'";
        $mysql56 = db_result(db_query($sql));

        $sql = "show status like 'wsrep_local_state'";
        $galera = db_result(db_query($sql));

        if ($galera && !$mysql56)
            throw new Exception('Galera cannot be used with MyISAM tables. Upgrade to MariaDB 10 / MySQL 5.6 is required');
        $engine = $galera ? 'InnodB' : ($mysql56 ? '' : 'MyISAM');
        if ($engine)
            $engine = 'ENGINE='.$engine;

        $sql = 'CREATE TABLE IF NOT EXISTS '.TABLE_PREFIX."_search (
            `object_type` varchar(8) not null,
            `object_id` int(11) unsigned not null,
            `title` text collate utf8_general_ci,
            `content` text collate utf8_general_ci,
            primary key `object` (`object_type`, `object_id`),
            fulltext key `search` (`title`, `content`)
        ) $engine CHARSET=utf8";
        if (!db_query($sql))
            return false;

        // Start rebuilding the index
        $config = new MySqlSearchConfig();
        $config->set('reindex', 1);
        return true;
    }

    /**
     * Cooperates with the cron system to automatically find content that is
     * not indexed in the _search table and add it to the index.
     */
    function IndexOldStuff() {
        $class = get_class();
        $auto_create = function($db_error) use ($class) {

            if ($db_error != 1146)
                // Perform the standard error handling
                return true;

            // Create the search table automatically
            $class::__init();

        };

        // THREADS ----------------------------------
        $sql = "SELECT A1.`id`, A1.`title`, A1.`body`, A1.`format` FROM `".THREAD_ENTRY_TABLE."` A1
            LEFT JOIN `".TABLE_PREFIX."_search` A2 ON (A1.`id` = A2.`object_id` AND A2.`object_type`='H')
            WHERE A2.`object_id` IS NULL AND (A1.poster <> 'SYSTEM')
            AND (LENGTH(A1.`title`) + LENGTH(A1.`body`) > 0)
            ORDER BY A1.`id` DESC LIMIT 500";
        if (!($res = db_query_unbuffered($sql, $auto_create)))
            return false;

        while ($row = db_fetch_row($res)) {
            $body = ThreadEntryBody::fromFormattedText($row[2], $row[3]);
            $body = $body->getSearchable();
            $title = Format::searchable($row[1]);
            if (!$body && !$title)
                continue;
            $record = array('H', $row[0], $title, $body);
            if (!$this->__index($record))
                return;
        }

        // TICKETS ----------------------------------

        $sql = "SELECT A1.`ticket_id` FROM `".TICKET_TABLE."` A1
            LEFT JOIN `".TABLE_PREFIX."_search` A2 ON (A1.`ticket_id` = A2.`object_id` AND A2.`object_type`='T')
            WHERE A2.`object_id` IS NULL
            ORDER BY A1.`ticket_id` DESC LIMIT 300";
        if (!($res = db_query_unbuffered($sql, $auto_create)))
            return false;

        while ($row = db_fetch_row($res)) {
            if (!($ticket = Ticket::lookup($row[0])))
                continue;
            $cdata = $ticket->loadDynamicData();
            $content = array();
            foreach ($cdata as $k=>$a)
                if ($k != 'subject' && ($v = $a->getSearchable()))
                    $content[] = $v;
            $record = array('T', $ticket->getId(),
                Format::searchable($ticket->getNumber().' '.$ticket->getSubject()),
                implode("\n", $content));
            if (!$this->__index($record))
                return;
        }

        // USERS ------------------------------------

        $sql = "SELECT A1.`id` FROM `".USER_TABLE."` A1
            LEFT JOIN `".TABLE_PREFIX."_search` A2 ON (A1.`id` = A2.`object_id` AND A2.`object_type`='U')
            WHERE A2.`object_id` IS NULL
            ORDER BY A1.`id` DESC";
        if (!($res = db_query_unbuffered($sql, $auto_create)))
            return false;

        while ($row = db_fetch_row($res)) {
            $user = User::lookup($row[0]);
            $cdata = $user->getDynamicData();
            $content = array();
            foreach ($user->emails as $e)
                $content[] = $e->address;
            foreach ($cdata as $e)
                foreach ($e->getAnswers() as $a)
                    if ($c = $a->getSearchable())
                        $content[] = $c;
            $record = array('U', $user->getId(),
                Format::searchable($user->getFullName()),
                trim(implode("\n", $content)));
            if (!$this->__index($record))
                return;
        }

        // ORGANIZATIONS ----------------------------

        $sql = "SELECT A1.`id` FROM `".ORGANIZATION_TABLE."` A1
            LEFT JOIN `".TABLE_PREFIX."_search` A2 ON (A1.`id` = A2.`object_id` AND A2.`object_type`='O')
            WHERE A2.`object_id` IS NULL
            ORDER BY A1.`id` DESC";
        if (!($res = db_query_unbuffered($sql, $auto_create)))
            return false;

        while ($row = db_fetch_row($res)) {
            $org = Organization::lookup($row[0]);
            $cdata = $org->getDynamicData();
            $content = array();
            foreach ($cdata as $e)
                foreach ($e->getAnswers() as $a)
                    if ($c = $a->getSearchable())
                        $content[] = $c;
            $record = array('O', $org->getId(),
                Format::searchable($org->getName()),
                trim(implode("\n", $content)));
            if (!$this->__index($record))
                return null;
        }

        // KNOWLEDGEBASE ----------------------------

        require_once INCLUDE_DIR . 'class.faq.php';
        $sql = "SELECT A1.`faq_id` FROM `".FAQ_TABLE."` A1
            LEFT JOIN `".TABLE_PREFIX."_search` A2 ON (A1.`faq_id` = A2.`object_id` AND A2.`object_type`='K')
            WHERE A2.`object_id` IS NULL
            ORDER BY A1.`faq_id` DESC";
        if (!($res = db_query_unbuffered($sql, $auto_create)))
            return false;

        while ($row = db_fetch_row($res)) {
            $faq = FAQ::lookup($row[0]);
            $q = $faq->getQuestion();
            if ($k = $faq->getKeywords())
                $q = $k.' '.$q;
            $record = array('K', $faq->getId(),
                Format::searchable($q),
                $faq->getSearchableAnswer());
            if (!$this->__index($record))
                return;
        }

        // FILES ------------------------------------

        // Flush non-full batch of records
        $this->__index(null, true);

        if (!$this->_reindexed) {
            // Stop rebuilding the index
            $this->getConfig()->set('reindex', 0);
        }
    }

    function __index($record, $force_flush=false) {
        static $queue = array();

        if ($record)
            $queue[] = $record;
        elseif (!$queue)
            return;

        if (!$force_flush && count($queue) < $this::$BATCH_SIZE)
            return true;

        foreach ($queue as &$r)
            $r = sprintf('(%s)', implode(',', db_input($r)));
        unset($r);

        $sql = 'INSERT INTO `'.TABLE_PREFIX.'_search` (`object_type`, `object_id`, `title`, `content`)
            VALUES '.implode(',', $queue);
        if (!db_query($sql, false) || count($queue) != db_affected_rows())
            throw new Exception('Unable to index content');

        $this->_reindexed += count($queue);
        $queue = array();

        if (!--$this->max_batches)
            return null;

        return true;
    }

    static function __init() {
        self::createSearchTable();
    }

}

Signal::connect('system.install',
        array('MysqlSearchBackend', '__init'));

MysqlSearchBackend::register();

// Saved search system

/**
 *
 * Fields:
 * id - (int:unsigned:auto:pk) unique identifier
 * flags - (int:unsigned) flags for this queue
 * staff_id - (int:unsigned) Agent to whom this queue belongs (can be null
 *      for public saved searches)
 * title - (text:60) name of the queue
 * config - (text) JSON encoded search configuration for the queue
 * created - (date) date initially created
 * updated - (date:auto_update) time of last update
 */
class SavedSearch extends VerySimpleModel {
    static $meta = array(
        'table' => QUEUE_TABLE,
        'pk' => array('id'),
        'ordering' => array('sort'),
        'joins' => array(
            'staff' => array(
                'constraint' => array(
                    'staff_id' => 'Staff.staff_id',
                )
            ),
            'parent' => array(
                'constraint' => array(
                    'parent_id' => 'CustomQueue.id',
                ),
                'null' => true,
            ),
        ),
    );

    const FLAG_PUBLIC =         0x0001; // Shows up in e'eryone's saved searches
    const FLAG_QUEUE =          0x0002; // Shows up in queue navigation
    const FLAG_CONTAINER =      0x0004; // Container for other queues ('Open')
    const FLAG_INHERIT_CRITERIA = 0x0008; // Include criteria from parent
    const FLAG_INHERIT_COLUMNS = 0x0010; // Inherit column layout from parent

    var $criteria;
    private $columns;

    static function forStaff(Staff $agent) {
        return static::objects()->filter(Q::any(array(
            'staff_id' => $agent->getId(),
            'flags__hasbit' => self::FLAG_PUBLIC,
        )))
        ->exclude(array('flags__hasbit'=>self::FLAG_QUEUE));
    }

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->title;
    }

    function getHref() {
        // TODO: Get base page from getRoot();
        $root = $this->getRoot();
        return 'tickets.php?queue='.$this->getId();
    }

    function getRoot() {
        switch ($this->root) {
        case 'T':
        default:
            return 'Ticket';
        }
    }

    function getPath() {
        return $this->path ?: $this->buildPath();
    }

    function getCriteria($include_parent=false) {
        if (!isset($this->criteria)) {
            $old = @$this->config[0] === '{';
            $this->criteria = is_string($this->config)
                ? JsonDataParser::decode($this->config)
                : $this->config;
            // Auto-upgrade criteria to new format
            if ($old) {
                // TODO: Upgrade old ORM path names
                $this->criteria = $this->isolateCriteria($this->criteria);
            }
        }
        $criteria = $this->criteria ?: array();
        if ($include_parent && $this->parent_id && $this->parent) {
            $criteria = array_merge($this->parent->getCriteria(true),
                $criteria);
        }
        return $criteria;
    }

    function describeCriteria($criteria=false){
        $all = $this->getSupportedMatches($this->getRoot());
        $items = array();
        $criteria = $criteria ?: $this->getCriteria(true);
        foreach ($criteria as $C) {
            list($path, $method, $value) = $C;
            if (!isset($all[$path]))
                continue;
             list($label, $field) = $all[$path];
             $items[] = $field->describeSearch($method, $value, $label);
        }
        return implode("\nAND ", $items);
    }

    /**
     * Fetch an AdvancedSearchForm instance for use in displaying or
     * configuring this search in the user interface.
     *
     * Parameters:
     * $search - <array> Request parameters ($_POST) used to update the
     *      search beyond the current configuration of the search criteria
     */
    function getForm($source=null) {
        $searchable = $this->getCurrentSearchFields($source);
        $fields = array(
            ':keywords' => new TextboxField(array(
                'id' => 3001,
                'configuration' => array(
                    'size' => 40,
                    'length' => 400,
                    'autofocus' => true,
                    'classes' => 'full-width headline',
                    'placeholder' => __('Keywords — Optional'),
                ),
            )),
        );
        foreach ($searchable as $path=>$field) {
            $fields = array_merge($fields, self::getSearchField($field, $path));
        }

        $form = new AdvancedSearchForm($fields, $source);
        $form->addValidator(function($form) {
            $selected = 0;
            foreach ($form->getFields() as $F) {
                if (substr($F->get('name'), -7) == '+search' && $F->getClean())
                    $selected += 1;
                // Consider keyword searches
                elseif ($F->get('name') == ':keywords' && $F->getClean())
                    $selected += 1;
            }
            if (!$selected)
                $form->addError(__('No fields selected for searching'));
        });

        // Load state from current configuraiton
        if (!$source) {
            foreach ($this->getCriteria() as $I) {
                list($path, $method, $value) = $I;
                if ($path == ':keywords' && $method === null) {
                    if ($F = $form->getField($path))
                        $F->value = $value;
                    continue;
                }

                if (!($F = $form->getField("{$path}+search")))
                    continue;
                $F->value = true;

                if (!($F = $form->getField("{$path}+method")))
                    continue;
                $F->value = $method;

                if ($value && ($F = $form->getField("{$path}+{$method}")))
                    $F->value = $value;
            }
        }
        return $form;
    }

    /**
     * Fetch a bucket of fields for a custom search. The fields should be
     * added to a form before display. One searchable field may encompass 10
     * or more actual fields because fields are expanded to support multiple
     * search methods along with the fields for each search method. This
     * method returns all the FormField instances for all the searchable
     * model fields currently in use.
     *
     * Parameters:
     * $source - <array> data from a request. $source['fields'] is expected
     *      to contain a list extra fields by ORM path, of newly added
     *      fields not yet saved in this object's getCriteria().
     */
    function getCurrentSearchFields($source=array()) {
        static $basic = array(
            'Ticket' => array(
                'status__state',
                'dept_id',
                'assignee',
                'topic_id',
                'created',
                'est_duedate',
            )
        );

        $all = $this->getSupportedMatches();
        $core = array();

        // Include basic fields for new searches
        if (!isset($this->id))
            foreach ($basic[$this->getRoot()] as $path)
                if (isset($all[$path]))
                    $core[$path] = $all[$path];

        // Add others from current configuration
        foreach ($this->getCriteria() as $C) {
            list($path) = $C;
            if (isset($all[$path]))
                $core[$path] = $all[$path];
        }

        if (isset($source['fields']))
            foreach ($source['fields'] as $path)
                if (isset($all[$path]))
                    $core[$path] = $all[$path];

        return $core;
    }

    /**
     * Fetch all supported ORM fields searchable by this search object. The
     * returned list represents searchable fields, keyed by the ORM path.
     * Use ::getCurrentSearchFields() or ::getSearchField() to retrieve for
     * use in the user interface.
     */
    function getSupportedMatches() {
        return static::getSearchableFields($this->getRoot());
    }

    /**
     * Trace ORM fields from a base object and retrieve a complete list of
     * fields which can be used in an ORM query based on the base object.
     * The base object must implement Searchable interface and extend from
     * VerySimpleModel. Then all joins from the object are also inspected,
     * and any which implement the Searchable interface are traversed and
     * automatically added to the list. The resulting list is cached based
     * on the $base class, so multiple calls for the same $base return
     * quickly.
     *
     * Parameters:
     * $base - Class, name of a class implementing Searchable
     * $recurse - int, number of levels to recurse, default is 2
     * $cache - bool, cache results for future class for the same base
     * $customData - bool, include all custom data fields for all general
     *      forms
     */
    static function getSearchableFields($base, $recurse=2,
        $customData=true, $exclude=array()
    ) {
        static $cache = array(), $otherFields;

        if (!in_array('Searchable', class_implements($base)))
            return array();

        // Early exit if already cached
        $fields = &$cache[$base];
        if ($fields)
            return $fields;

        $fields = $fields ?: array();
        foreach ($base::getSearchableFields() as $path=>$F) {
            if (is_array($F)) {
                list($label, $field) = $F;
            }
            else {
                $label = $F->get('label');
                $field = $F;
            }
            $fields[$path] = array($label, $field);
        }

        if ($customData && $base::supportsCustomData()) {
            if (!isset($otherFields)) {
                $otherFields = array();
                $dfs = DynamicFormField::objects()
                    ->filter(array('form__type' => 'G'))
                    ->select_related('form');
                foreach ($dfs as $field) {
                    $otherFields[$field->getId()] = array($field->form,
                        $field->getImpl());
                }
            }
            foreach ($otherFields as $id=>$F) {
                list($form, $field) = $F;
                $label = sprintf("%s / %s",
                    $form->getTitle(), $field->get('label'));
                $fields["entries__answers!{$id}__value"] = array(
                    $label, $field);
            }
        }

        if ($recurse) {
            $exclude[$base] = 1;
            foreach ($base::getMeta('joins') as $path=>$j) {
                $fc = $j['fkey'][0];
                if (isset($exclude[$fc]) || $j['list'])
                    continue;
                foreach (static::getSearchableFields($fc, $recurse-1,
                    true, $exclude)
                as $path2=>$F) {
                    list($label, $field) = $F;
                    $fields["{$path}__{$path2}"] = array(
                        sprintf("%s / %s", $fc, $label),
                        $field);
                }
            }
        }

        return $fields;
    }

    /**
     * Fetch the FormField instances used when for configuring a searchable
     * field in the user interface. This is the glue between a field
     * representing a searchable model field and the configuration of that
     * search in the user interface.
     *
     * Parameters:
     * $F - <array<string, FormField>> the label and the FormField instance
     *      representing the configurable search
     * $name - <string> ORM path for the search
     */
    static function getSearchField($F, $name) {
        list($label, $field) = $F;

        $pieces = array();
        $pieces["{$name}+search"] = new BooleanField(array(
            'id' => sprintf('%u', crc32($name)) >> 1,
            'configuration' => array(
                'desc' => $label ?: $field->getLocal('label'),
                'classes' => 'inline',
            ),
        ));
        $methods = $field->getSearchMethods();
        $pieces["{$name}+method"] = new ChoiceField(array(
            'choices' => $methods,
            'default' => key($methods),
            'visibility' => new VisibilityConstraint(new Q(array(
                "{$name}+search__eq" => true,
            )), VisibilityConstraint::HIDDEN),
        ));
        $offs = 0;
        foreach ($field->getSearchMethodWidgets() as $m=>$w) {
            if (!$w)
                continue;
            list($class, $args) = $w;
            $args['required'] = true;
            $args['__searchval__'] = true;
            $args['visibility'] = new VisibilityConstraint(new Q(array(
                    "{$name}+method__eq" => $m,
                )), VisibilityConstraint::HIDDEN);
            $pieces["{$name}+{$m}"] = new $class($args);
        }
        return $pieces;
    }

    function getField($path) {
        $searchable = $this->getSupportedMatches();
        return $searchable[$path];
    }

    // Remove this and adjust advanced-search-criteria template to use the
    // getCriteria() list and getField()
    function getSearchFields($form=false) {
        $form = $form ?: $this->getForm();
        $searchable = $this->getCurrentSearchFields();
        $info = array();
        foreach ($form->getFields() as $f) {
            if (substr($f->get('name'), -7) == '+search') {
                $name = substr($f->get('name'), 0, -7);
                $value = null;
                // Determine the search method and fetch the original field
                if (($M = $form->getField("{$name}+method"))
                    && ($method = $M->getClean())
                    && (list(,$field) = $searchable[$name])
                ) {
                    // Request the field to generate a search Q for the
                    // search method and given value
                    if ($value = $form->getField("{$name}+{$method}"))
                        $value = $value->getClean();
                }
                $info[$name] = array(
                    'field' => $field,
                    'method' => $method,
                    'value' => $value,
                    'active' =>  $f->getClean(),
                );
            }
        }
        return $info;
    }

    /**
     * Take the criteria from the SavedSearch fields setup and isolate the
     * field name being search, the method used for searhing, and the method-
     * specific data entered in the UI.
     */
    function isolateCriteria($criteria, $root=null) {
        $searchable = static::getSearchableFields($root ?: $this->getRoot());
        $items = array();
        if (!$criteria)
            return null;
        foreach ($criteria as $k=>$v) {
            if (substr($k, -7) === '+method') {
                list($name,) = explode('+', $k, 2);
                if (!isset($searchable[$name]))
                    continue;

                // Require checkbox to be checked too
                if (!$criteria["{$name}+search"])
                    continue;

                // Lookup the field to search this condition
                list($label, $field) = $searchable[$name];

                // Get the search method and value
                $method = $v;
                // Not all search methods require a value
                $value = $criteria["{$name}+{$method}"];

                $items[] = array($name, $method, $value);
            }
        }
        if (isset($criteria[':keywords'])) {
            $items[] = array(':keywords', null, $criteria[':keywords']);
        }
        return $items;
    }

    function getColumns() {
        if ($this->columns_id
            && ($q = CustomQueue::lookup($this->columns_id))
        ) {
            // Use columns from cited queue
            return $q->getColumns();
        }
        elseif ($this->parent_id
            && $this->hasFlag(self::FLAG_INHERIT_COLUMNS)
            && $this->parent
        ) {
            return $this->parent->getColumns();
        }

        // Last resort — use standard columns
        return array(
            QueueColumn::create(array(
                "heading" => "Number",
                "primary" => 'number',
                "width" => 85,
                "filter" => "link:ticketP",
                "annotations" => '[{"c":"TicketSourceDecoration","p":"b"}]',
                "conditions" => '[{"crit":["isanswered","set",null],"prop":{"font-weight":"bold"}}]',
            )),
            QueueColumn::create(array(
                "heading" => "Created",
                "primary" => 'created',
                "width" => 100,
            )),
            QueueColumn::create(array(
                "heading" => "Subject",
                "primary" => 'cdata__subject',
                "width" => 250,
                "filter" => "link:ticket",
                "annotations" => '[{"c":"TicketThreadCount","p":">"},{"c":"ThreadAttachmentCount","p":"a"},{"c":"OverdueFlagDecoration","p":"<"}]',
                "truncate" => 'ellipsis',
            )),
            QueueColumn::create(array(
                "heading" => "From",
                "primary" => 'user__name',
                "width" => 150,
            )),
            QueueColumn::create(array(
                "heading" => "Priority",
                "primary" => 'cdata__priority',
                "width" => 120,
            )),
            QueueColumn::create(array(
                "heading" => "Assignee",
                "primary" => 'assignee',
                "width" => 100,
            )),
        );
    }

    /**
     * Get a description of a field in a search. Expects an entry from the
     * array retrieved in ::getSearchFields()
     */
    function describeField($info, $name=false) {
        return $info['field']->describeSearch($info['method'], $info['value'], $name);
    }

    function getQuery() {
        $root = $this->getRoot();
        $base = $root::objects();
        $query = $this->mangleQuerySet($base);

        // Apply column, annotations and conditions additions
        foreach ($this->getColumns() as $C) {
            $query = $C->mangleQuery($query, $this->getRoot());
        }
        return $query;
    }

    function mangleQuerySet(QuerySet $qs, $form=false) {
        $qs = clone $qs;
        $searchable = $this->getSupportedMatches();

        // Figure out fields to search on
        foreach ($this->getCriteria() as $I) {
            list($name, $method, $value) = $I;

            // Consider keyword searching
            if ($name === ':keywords') {
                global $ost;
                $qs = $ost->searcher->find($value, $qs);
            }
            else {
                // XXX: Move getOrmPath to be more of a utility
                // Ensure the special join is created to support custom data joins
                $name = @static::getOrmPath($name, $qs);

                if (preg_match('/__answers!\d+__/', $name)) {
                    $qs->annotate(array($name2 => SqlAggregate::MAX($name)));
                }

                // Fetch a criteria Q for the query
                if (list(,$field) = $searchable[$name])
                    if ($q = $field->getSearchQ($method, $value, $name))
                        $qs = $qs->filter($q);
            }
        }
        return $qs;
    }

    static function getOrmPath($name, $query=null) {
        // Special case for custom data `__answers!id__value`. Only add the
        // join and constraint on the query the first pass, when the query
        // being mangled is received.
        $path = array();
        if ($query && preg_match('/^(.+?)__(answers!(\d+))/', $name, $path)) {
            // Add a join to the model of the queryset where the custom data
            // is forked from — duplicate the 'answers' join and add the
            // constraint to the query based on the field_id
            // $path[1] - part before the answers (user__org__entries)
            // $path[2] - answers!xx join part
            // $path[3] - the `xx` part of the answers!xx join component
            $root = $query->model;
            $meta = $root::getMeta()->getByPath($path[1]);
            $joins = $meta['joins'];
            if (!isset($joins[$path[2]])) {
                $meta->addJoin($path[2], $joins['answers']);
            }
            // Ensure that the query join through answers!xx is only for the
            // records which match field_id=xx
            $query->constrain(array("{$path[1]}__{$path[2]}" =>
                array("{$path[1]}__{$path[2]}__field_id" => (int) $path[3])
            ));
            // Leave $name unchanged
        }
        return $name;
    }


    function checkAccess(Staff $agent) {
        return $agent->getId() == $this->staff_id
            || $this->hasFlag(self::FLAG_PUBLIC);
    }

    function ignoreVisibilityConstraints() {
        global $thisstaff;

        // For saved searches (not queues), staff can have a permission to
        // see all records
        return !$this->isAQueue()
            && $thisstaff->hasPerm(SearchBackend::PERM_EVERYTHING);
    }

    function inheritCriteria() {
        return $this->flags & self::FLAG_INHERIT_CRITERIA;
    }

    function buildPath() {
        if (!$this->id)
            return;

        $path = $this->parent ? $this->parent->getPath() : '';
        return $path . "/{$this->id}";
    }

    function getFullName() {
        $base = $this->getName();
        if ($this->parent)
            $base = sprintf("%s / %s", $this->parent->getFullName(), $base);
        return $base;
    }

    function isAQueue() {
        return $this->hasFlag(self::FLAG_QUEUE);
    }

    function isPrivate() {
        return !$this->isAQueue() && !$this->hasFlag(self::FLAG_PUBLIC);
    }

    protected function hasFlag($flag) {
        return $this->flags & $flag !== 0;
    }

    protected function clearFlag($flag) {
        return $this->flags &= ~$flag;
    }

    protected function setFlag($flag, $value=true) {
        return $value
            ? $this->flags |= $flag
            : $this->clearFlag($flag);
    }

    static function create($vars=array()) {
        $inst = new static($vars);
        $inst->created = SqlFunction::NOW();
        return $inst;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    function update($vars, $form=false, &$errors=array()) {
        // Set basic search information
        if (!$vars['name'])
            $errors['name'] = __('A title is required');

        $this->title = $vars['name'];
        $this->parent_id = @$vars['parent_id'] ?: 0;
        $this->path = $this->buildPath();
        // Personal queues _always_ inherit from their parent
        $this->setFlag(self::FLAG_INHERIT_CRITERIA, $this->parent_id > 0);

        // TODO: Move this to SavedSearch::update() and adjust
        //       AjaxSearch::_saveSearch()
        $form = $form ?: $this->getForm($vars);
        if (!$vars || !$form->isValid()) {
            $errors['criteria'] = __('Validation errors exist on criteria');
        }
        else {
            $this->config = JsonDataEncoder::encode(
                $this->isolateCriteria($form->getClean()));
        }


        return count($errors) === 0;
    }
}

class AdhocSearch
extends SavedSearch {
    function getName() {
        return __('Ad-Hoc Search');
    }

    function getHref() {
        return 'tickets.php?queue=adhoc';
    }
}

class AdvancedSearchForm extends SimpleForm {
    static $id = 1337;
}

// Advanced search special fields

class HelpTopicChoiceField extends ChoiceField {
    function hasIdValue() {
        return true;
    }

    function getChoices($verbose=false) {
        return Topic::getHelpTopics(false, Topic::DISPLAY_DISABLED);
    }
}

require_once INCLUDE_DIR . 'class.dept.php';
class DepartmentChoiceField extends ChoiceField {
    function getChoices($verbose=false) {
        return Dept::getDepartments();
    }

    function getSearchMethods() {
        return array(
            'includes' =>   __('is'),
            '!includes' =>  __('is not'),
        );
    }
}

class AssigneeChoiceField extends ChoiceField {
    function getChoices($verbose=false) {
        global $thisstaff;

        $items = array(
            'M' => __('Me'),
            'T' => __('One of my teams'),
        );
        foreach (Staff::getStaffMembers() as $id=>$name) {
            // Don't include $thisstaff (since that's 'Me')
            if ($thisstaff && $thisstaff->getId() == $id)
                continue;
            $items['s' . $id] = $name;
        }
        foreach (Team::getTeams() as $id=>$name) {
            $items['t' . $id] = $name;
        }
        return $items;
    }

    function getSearchMethods() {
        return array(
            'assigned' =>   __('assigned'),
            '!assigned' =>  __('unassigned'),
            'includes' =>   __('includes'),
            '!includes' =>  __('does not include'),
        );
    }

    function getSearchMethodWidgets() {
        return array(
            'assigned' => null,
            '!assigned' => null,
            'includes' => array('ChoiceField', array(
                'choices' => $this->getChoices(),
                'configuration' => array('multiselect' => true),
            )),
            '!includes' => array('ChoiceField', array(
                'choices' => $this->getChoices(),
                'configuration' => array('multiselect' => true),
            )),
        );
    }

    function getSearchQ($method, $value, $name=false) {
        global $thisstaff;

        $Q = new Q();
        switch ($method) {
        case 'assigned':
            $Q->negate();
        case '!assigned':
            $Q->add(array('team_id' => 0,
                'staff_id' => 0));
            break;
        case '!includes':
            $Q->negate();
        case 'includes':
            $teams = $agents = array();
            foreach ($value as $id => $ST) {
                switch ($id[0]) {
                case 'M':
                    $agents[] = $thisstaff->getId();
                    break;
                case 's':
                    $agents[] = (int) substr($id, 1);
                    break;
                case 'T':
                    $teams = array_merge($thisstaff->getTeams());
                    break;
                case 't':
                    $teams[] = (int) substr($id, 1);
                    break;
                }
            }
            $constraints = array();
            if ($teams)
                $constraints['team_id__in'] = $teams;
            if ($agents)
                $constraints['staff_id__in'] = $agents;
            $Q->add(Q::any($constraints));
        }
        return $Q;
    }

    function describeSearchMethod($method) {
        switch ($method) {
        case 'assigned':
            return __('assigned');
        case '!assigned':
            return __('unassigned');
        default:
            return parent::describeSearchMethod($method);
        }
    }

    function addToQuery($query, $name=false) {
        return $query->values('staff__firstname', 'staff__lastname', 'team__name', 'team_id');
    }

    function from_query($row, $name=false) {
        if ($row['staff__firstname'])
            return new AgentsName(array('first' => $row['staff__firstname'], 'last' => $row['staff__lastname']));
        if ($row['team_id'])
            return Team::getLocalById($row['team_id'], 'name', $row['team__name']);
    }

    function display($value) {
        return (string) $value;
    }
}

class AgentSelectionField extends ChoiceField {
    function getChoices() {
        return Staff::getStaffMembers();
    }
}

class TeamSelectionField extends ChoiceField {
    function getChoices() {
        return Team::getTeams();
    }
}

class TicketStateChoiceField extends ChoiceField {
    function getChoices($verbose=false) {
        return array(
            'open' => __('Open'),
            'closed' => __('Closed'),
            'archived' => _P('ticket state name', 'Archived'),
            'deleted' => _P('ticket state name','Deleted'),
        );
    }

    function getSearchMethods() {
        return array(
            'includes' =>   __('is'),
            '!includes' =>  __('is not'),
        );
    }

    function getSearchQ($method, $value, $name=false) {
        return parent::getSearchQ($method, $value, 'status__state');
    }
}

class TicketFlagChoiceField extends ChoiceField {
    function getChoices($verbose=false) {
        return array(
            'isanswered' =>   __('Answered'),
            'isoverdue' =>    __('Overdue'),
        );
    }

    function getSearchMethods() {
        return array(
            'includes' =>   __('is'),
            '!includes' =>  __('is not'),
        );
    }

    function getSearchQ($method, $value, $name=false) {
        $Q = new Q();
        if (isset($value['isanswered']))
            $Q->add(array('isanswered' => 1));
        if (isset($value['isoverdue']))
            $Q->add(array('isoverdue' => 1));
        if ($method == '!includes')
            $Q->negate();
        if ($Q->constraints)
            return $Q;
    }
}

class TicketSourceChoiceField extends ChoiceField {
    function getChoices($verbose=false) {
        return Ticket::getSources();
    }

    function getSearchMethods() {
        return array(
            'includes' =>   __('is'),
            '!includes' =>  __('is not'),
        );
    }

    function getSearchQ($method, $value, $name=false) {
        return parent::getSearchQ($method, $value, 'source');
    }
}

class OpenClosedTicketStatusList extends TicketStatusList {
    function getItems($criteria=array()) {
        $rv = array();
        $base = parent::getItems($criteria);
        foreach ($base as $idx=>$S) {
            if (in_array($S->state, array('open', 'closed')))
                $rv[$idx] = $S;
        }
        return $rv;
    }
}
class TicketStatusChoiceField extends SelectionField {
    static $widget = 'ChoicesWidget';

    function getList() {
        return new OpenClosedTicketStatusList(
            DynamicList::lookup(
                array('type' => 'ticket-status'))
        );
    }

    function getSearchMethods() {
        return array(
            'includes' =>   __('is'),
            '!includes' =>  __('is not'),
        );
    }

    function getSearchQ($method, $value, $name=false) {
        $name = $name ?: $this->get('name');
        switch ($method) {
        case '!includes':
            return Q::not(array("{$name}__in" => array_keys($value)));
        case 'includes':
            return new Q(array("{$name}__in" => array_keys($value)));
        default:
            return parent::getSearchQ($method, $value, $name);
        }
    }
}

interface Searchable {
    // Fetch an array of [ orm__path => Field() ] pairs. The field label is
    // used when this list is rendered in a dropdown, and the field search
    // mechanisms are use to apply query filtering based on the field.
    static function getSearchableFields();

    // Determine if the object supports abritrary form additions, through
    // the "Manage Forms" dialog usually
    static function supportsCustomData();
}
