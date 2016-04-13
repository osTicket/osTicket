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
require_once INCLUDE_DIR . 'class.queue.php';

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
 * A special case of the custom queues used to represent an advanced search.
 */
class SavedSearch extends CustomQueue {
    // Override the ORM relationship to force no children
    private $children = false;

    static function forStaff(Staff $agent) {
        return static::objects()->filter(Q::any(array(
            'staff_id' => $agent->getId(),
            'flags__hasbit' => self::FLAG_PUBLIC,
        )))
        ->exclude(array('flags__hasbit'=>self::FLAG_QUEUE));
    }

    function update($vars, $form=false, &$errors=array()) {
        if (!parent::update($vars, $errors))
            return false;

        // Personal queues _always_ inherit from their parent
        $this->setFlag(self::FLAG_INHERIT_CRITERIA, $this->parent_id > 0);

        return count($errors) === 0;
    }

    static function create() {
        $search = parent::create();
        $search->clearFlag(self::FLAG_QUEUE);
        return $search;
    }
}

class AdhocSearch
extends SavedSearch {
    function getName() {
        return $this->describeCriteria();
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

    function applyOrderBy($query, $reverse=false, $name=false) {
        $reverse = $reverse ? '-' : '';
        return $query->order_by("{$reverse}staff__firstname",
            "{$reverse}staff__lastname", "{$reverse}team__name");
    }
}

class AgentSelectionField extends ChoiceField {
    function getChoices() {
        return Staff::getStaffMembers();
    }

    function applyOrderBy($query, $reverse=false, $name=false) {
        global $cfg;

        $reverse = $reverse ? '-' : '';
        switch ($cfg->getAgentNameFormat()) {
        case 'last':
        case 'lastfirst':
        case 'legal':
            $query->order_by("{$reverse}staff__lastname",
                "{$reverse}staff__firstname");
            break;

        default:
            $query->order_by("{$reverse}staff__firstname",
                "{$reverse}staff__lastname");
        }
        return $query;
    }
}

class TeamSelectionField extends ChoiceField {
    function getChoices() {
        return Team::getTeams();
    }

    function applyOrderBy($query, $reverse=false, $name=false) {
        $reverse = $reverse ? '-' : '';
        return $query->order_by("{$reverse}team__name");
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
