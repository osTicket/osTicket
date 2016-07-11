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
        case 'TicketModel':
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
    );

    const FLAG_PUBLIC =     0x0001;
    const FLAG_QUEUE =      0x0002;

    static function forStaff(Staff $agent) {
        return static::objects()->filter(Q::any(array(
            'staff_id' => $agent->getId(),
            'flags__hasbit' => self::FLAG_PUBLIC,
        )));
    }

    function loadFromState($source=false) {
        // Pull out 'other' fields from the state so the fields will be
        // added to the form. The state will be loaded below
        $state = $source ?: array();
        foreach ($state as $k=>$v) {
            $info = array();
            if (!preg_match('/^:(\w+)(?:!(\d+))?\+search/', $k, $info)) {
                continue;
            }
            list($k,) = explode('+', $k, 2);
            $state['fields'][] = $k;
        }
        return $this->getForm($state);
    }

    function getFormFromSession($key) {
        if (isset($_SESSION[$key])) {
            return $this->loadFromState($_SESSION[$key]);
        }
    }

    function getForm($source=false) {
        // XXX: Ensure that the UIDs generated for these fields are
        //      consistent between requests

        $searchable = $this->getCurrentSearchFields($source);
        $fields = array(
            'keywords' => new TextboxField(array(
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
        foreach ($searchable as $name=>$field) {
            $fields = array_merge($fields, self::getSearchField($field, $name));
        }

        // Don't send the state as the souce because it is not in the
        // ::parse format (it's in ::to_php format). Instead, source is set
        // via ::loadState() below
        $form = new AdvancedSearchForm($fields, $source);
        $form->addValidator(function($form) {
            $selected = 0;
            foreach ($form->getFields() as $F) {
                if (substr($F->get('name'), -7) == '+search' && $F->getClean())
                    $selected += 1;
                // Consider keyword searches
                elseif ($F->get('name') == 'keywords' && $F->getClean())
                    $selected += 1;
            }
            if (!$selected)
                $form->addError(__('No fields selected for searching'));
        });
        if ($source)
            $form->loadState($source);
        return $form;
    }

    function getCurrentSearchFields($source=false) {
        $core = array(
            'status_id' =>  new TicketStatusChoiceField(array(
                'id' => 3101,
                'label' => __('Status'),
            )),
            'dept_id'   =>  new DepartmentChoiceField(array(
                'id' => 3102,
                'label' => __('Department'),
            )),
            'assignee'  =>  new AssigneeChoiceField(array(
                'id' => 3103,
                'label' => __('Assignee'),
            )),
            'topic_id'  =>  new HelpTopicChoiceField(array(
                'id' => 3104,
                'label' => __('Help Topic'),
            )),
            'created'   =>  new DateTimeField(array(
                'id' => 3105,
                'label' => __('Created'),
            )),
            'est_duedate'   =>  new DateTimeField(array(
                'id' => 3106,
                'label' => __('Due Date'),
            )),
        );

        // Add 'other' fields added dynamically
        if (is_array($source) && isset($source['fields'])) {
            $extended = self::getExtendedTicketFields();
            foreach ($source['fields'] as $f) {
                $info = array();
                if (isset($extended[$f])) {
                    $core[$f] = $extended[$f];
                    continue;
                }
                if (!preg_match('/^:(\w+)!(\d+)/', $f, $info)) {
                    continue;
                }
                $id = $info[2];
                if (is_numeric($id) && ($field = DynamicFormField::lookup($id))) {
                    $impl = $field->getImpl();
                    $impl->set('label', sprintf('%s / %s',
                        $field->form->getLocal('title'), $field->getLocal('label')
                    ));
                    $core[":{$info[1]}!{$info[2]}"] = $impl;
                }
            }
        }
        return $core;
    }

    static function getExtendedTicketFields() {
        return array(
#            ':user' =>       new UserChoiceField(array(
#                'label' => __('Ticket Owner'),
#            )),
#            ':org' =>        new OrganizationChoiceField(array(
#                'label' => __('Organization'),
#            )),
            ':closed' =>     new DatetimeField(array(
                'id' => 3204,
                'label' => __('Closed Date'),
            )),
            ':thread__lastresponse' => new DatetimeField(array(
                'id' => 3205,
                'label' => __('Last Response'),
            )),
            ':thread__lastmessage' => new DatetimeField(array(
                'id' => 3206,
                'label' => __('Last Message'),
            )),
            ':source' =>     new TicketSourceChoiceField(array(
                'id' => 3201,
                'label' => __('Source'),
            )),
            ':state' =>      new TicketStateChoiceField(array(
                'id' => 3202,
                'label' => __('State'),
            )),
            ':flags' =>      new TicketFlagChoiceField(array(
                'id' => 3203,
                'label' => __('Flags'),
            )),
        );
    }

    static function getSearchField($field, $name) {
        $baseId = $field->getId() * 20;
        $pieces = array();
        $pieces["{$name}+search"] = new BooleanField(array(
            'id' => $baseId + 50000,
            'configuration' => array(
                'desc' => $field->getLocal('label'),
                'classes' => 'inline',
            ),
        ));
        $methods = $field->getSearchMethods();
        $pieces["{$name}+method"] = new ChoiceField(array(
            'id' => $baseId + 50001,
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
            $args['id'] = $baseId + 50002 + $offs++;
            $args['required'] = true;
            $args['__searchval__'] = true;
            $args['visibility'] = new VisibilityConstraint(new Q(array(
                    "{$name}+method__eq" => $m,
                )), VisibilityConstraint::HIDDEN);
            $pieces["{$name}+{$m}"] = new $class($args);
        }
        return $pieces;
    }

    /**
     * Collect information on the search form.
     *
     * Returns:
     * (<array(name => array('field' => <FormField>, 'method' => <string>,
     *      'value' => <mixed>, 'active' => <bool>))>), which will help to
     * explain each field active in the search form.
     */
    function getSearchFields($form=false) {
        $form = $form ?: $this->getForm();
        $searchable = $this->getCurrentSearchFields($form->state);
        $info = array();
        foreach ($form->getFields() as $f) {
            if (substr($f->get('name'), -7) == '+search') {
                $name = substr($f->get('name'), 0, -7);
                $value = null;
                // Determine the search method and fetch the original field
                if (($M = $form->getField("{$name}+method"))
                    && ($method = $M->getClean())
                    && ($field = $searchable[$name])
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
     * Get a description of a field in a search. Expects an entry from the
     * array retrieved in ::getSearchFields()
     */
    function describeField($info, $name=false) {
        return $info['field']->describeSearch($info['method'], $info['value'], $name);
    }

    function mangleQuerySet(QuerySet $qs, $form=false) {
        $form = $form ?: $this->getForm();
        $searchable = $this->getCurrentSearchFields($form->state);
        $qs = clone $qs;

        // Figure out fields to search on
        foreach ($this->getSearchFields($form) as $name=>$info) {
            if (!$info['active'])
                continue;
            $field = $info['field'];
            $filter = new Q();
            if ($name[0] == ':') {
                // This was an 'other' field, fetch a special "name"
                // for it which will be the ORM join path
                static $other_paths = array(
                    ':ticket' => 'cdata__',
                    ':user' => 'user__cdata__',
                    ':organization' => 'user__org__cdata__',
                );
                $column = $field->get('name') ?: 'field_'.$field->get('id');
                list($type,$id) = explode('!', $name, 2);
                // XXX: Last mile — find a better idea
                switch (array($type, $column)) {
                case array(':user', 'name'):
                    $name = 'user__name';
                    break;
                case array(':user', 'email'):
                    $name = 'user__emails__address';
                    break;
                case array(':organization', 'name'):
                    $name = 'user__org__name';
                    break;
                default:
                    if ($type == ':field' && $id) {
                        $name = 'entries__answers__value';
                        $filter->add(array('entries__answers__field_id' => $id));
                        break;
                    }
                    if ($OP = $other_paths[$type])
                        $name = $OP . $column;
                    else
                        $name = substr($name, 1);
                }
            }

            // Add the criteria to the QuerySet
            if ($Q = $field->getSearchQ($info['method'], $info['value'], $name)) {
                $filter->add($Q);
                $qs = $qs->filter($filter);
            }
        }

        // Consider keyword searching
        if ($keywords = $form->getField('keywords')->getClean()) {
            global $ost;

            $qs = $ost->searcher->find($keywords, $qs);
        }

        return $qs;
    }

    function checkAccess(Staff $agent) {
        return $agent->getId() == $this->staff_id
            || $this->hasFlag(self::FLAG_PUBLIC);
    }

    protected function hasFlag($flag) {
        return $this->get('flag') & $flag !== 0;
    }

    protected function clearFlag($flag) {
        return $this->set('flag', $this->get('flag') & ~$flag);
    }

    protected function setFlag($flag) {
        return $this->set('flag', $this->get('flag') | $flag);
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
}

class AdvancedSearchForm extends SimpleForm {
    var $state;

    function __construct($fields, $state) {
        parent::__construct($fields);
        $this->state = $state;
    }
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
        return array(
            'web' => __('Web'),
            'email' => __('Email'),
            'phone' => __('Phone'),
            'api' => __('API'),
            'other' => __('Other'),
        );
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
