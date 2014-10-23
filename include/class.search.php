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

    abstract function update($model, $id, $content, $new=false, $attrs=array());
    abstract function find($query, $criteria, $model=false, $sort=array());

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
}

// Register signals to intercept saving of various content throughout the
// system

class SearchInterface {

    var $backend;

    function __construct() {
        $this->bootstrap();
    }

    function find($query, $criteria, $model=false, $sort=array()) {
        $query = Format::searchable($query);
        return $this->backend->find($query, $criteria, $model, $sort);
    }

    function update($model, $id, $content, $new=false, $attrs=array()) {
        if (!$this->backend)
            return;

        $this->backend->update($model, $id, $content, $new, $attrs);
    }

    function createModel($model) {
        return $this->updateModel($model, true);
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
                $model->getBody()->getSearchable(), $new,
                array(
                    'title' =>      $model->getTitle(),
                    'ticket_id' =>  $model->getTicketId(),
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
                $new,
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
                $new,
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
                $new,
                array(
                    'title'=>       Format::searchable($model->getName()),
                    'created'=>     $model->getCreateDate(),
                )
            );
            break;

        case $model instanceof FAQ:
            $this->update($model, $model->getId(),
                $model->getSearchableAnswer(),
                $new,
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
        Signal::connect('model.created', array($this, 'createModel'));
        Signal::connect('model.updated', array($this, 'updateModel'));
        #Signal::connect('model.deleted', array($this, 'deleteModel'));
    }
}

class MysqlSearchBackend extends SearchBackend {
    static $id = 'mysql';
    static $BATCH_SIZE = 30;

    // Only index 20 batches per cron run
    var $max_batches = 60;

    function __construct() {
        $this->SEARCH_TABLE = TABLE_PREFIX . '_search';
    }

    function bootstrap() {
        Signal::connect('cron', array($this, 'IndexOldStuff'));
    }

    function update($model, $id, $content, $new=false, $attrs=array()) {
        switch (true) {
        case $model instanceof ThreadEntry:
            $type = 'H';
            break;
        case $model instanceof Ticket:
            $attrs['title'] = $attrs['number'].' '.$attrs['title'];
            $type = 'T';
            break;
        case $model instanceof User:
            $content .= implode("\n", $attrs['emails']);
            $type = 'U';
            break;
        case $model instanceof Organization:
            $type = 'O';
            break;
        case $model instanceof FAQ:
            $type = 'K';
            break;
        case $model instanceof AttachmentFile:
            $type = 'F';
            break;
        default:
            // Not indexed
            return;
        }

        $title = $attrs['title'] ?: '';

        if (!$content && !$title)
            return;

        $sql = 'REPLACE INTO '.$this->SEARCH_TABLE
            . ' SET object_type='.db_input($type)
            . ', object_id='.db_input($id)
            . ', content='.db_input($content)
            . ', title='.db_input($title);
        return db_query($sql);
    }

    // Quote things like email addresses
    function quote($query) {
        $parts = array();
        if (!preg_match_all('`([^\s"\']+)|"[^"]*"|\'[^\']*\'`', $query, $parts,
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
            $results[] = $m[0];
        }
        return implode(' ', $results);
    }

    function find($query, $criteria=array(), $model=false, $sort=array()) {
        global $thisstaff;

        $mode = ' IN BOOLEAN MODE';
        #if (count(explode(' ', $query)) == 1)
        #    $mode = ' WITH QUERY EXPANSION';
        $query = $this->quote($query);
        $search = 'MATCH (search.title, search.content) AGAINST ('
            .db_input($query)
            .$mode.')';
        $tables = array();
        $P = TABLE_PREFIX;
        $sort = '';

        if ($query) {
            $tables[] = "(
                SELECT object_type, object_id, $search AS `relevance`
                FROM `{$P}_search` `search`
                WHERE $search
            ) `search`";
            $sort = 'ORDER BY `search`.`relevance`';
        }

        switch ($model) {
        case false:
        case 'Ticket':
            $tables[] = "(select ticket_id as ticket_id from {$P}ticket
            ) B1 ON (B1.ticket_id = search.object_id and search.object_type = 'T')";
            $tables[] = "(select A2.id as thread_id, A1.ticket_id from {$P}ticket A1
                join {$P}ticket_thread A2 on (A1.ticket_id = A2.ticket_id)
            ) B2 ON (B2.thread_id = search.object_id and search.object_type = 'H')";
            $tables[] = "(select A3.id as user_id, A1.ticket_id from {$P}user A3
                join {$P}ticket A1 on (A1.user_id = A3.id)
            ) B3 ON (B3.user_id = search.object_id and search.object_type = 'U')";
            $tables[] = "(select A4.id as org_id, A1.ticket_id from {$P}organization A4
                join {$P}user A3 on (A3.org_id = A4.id) join {$P}ticket A1 on (A1.user_id = A3.id)
            ) B4 ON (B4.org_id = search.object_id and search.object_type = 'O')";
            $key = 'COALESCE(B1.ticket_id, B2.ticket_id, B3.ticket_id, B4.ticket_id)';
            $tables[] = "{$P}ticket A1 ON (A1.ticket_id = {$key})";
            $tables[] = "{$P}ticket_status A2 ON (A1.status_id = A2.id)";
            $cdata_search = false;
            $where = array();

            if ($criteria) {
                foreach ($criteria as $name=>$value) {
                    switch ($name) {
                    case 'status_id':
                        $where[] = 'A2.id = '.db_input($value);
                        break;
                    case 'state':
                        $where[] = 'A2.state = '.db_input($value);
                        break;
                    case 'state__in':
                        $where[] = 'A2.state IN ('.implode(',',db_input($value)).')';
                        break;
                    case 'topic_id':
                    case 'staff_id':
                    case 'team_id':
                    case 'dept_id':
                    case 'user_id':
                    case 'isanswered':
                    case 'isoverdue':
                    case 'number':
                        $where[] = sprintf('A1.%s = %s', $name, db_input($value));
                        break;
                    case 'created__gte':
                        $where[] = sprintf('A1.created >= %s', db_input($value));
                        break;
                    case 'created__lte':
                        $where[] = sprintf('A1.created <= %s', db_input($value));
                        break;
                    case 'email':
                    case 'org_id':
                    case 'form_id':
                    default:
                        if (strpos($name, 'cdata.') === 0) {
                            // Search ticket CDATA table
                            $cdata_search = true;
                            $name = substr($name, 6);
                            if (is_array($value)) {
                                $where[] = '(' . implode(' OR ', array_map(
                                    function($k) use ($name) {
                                        return sprintf('FIND_IN_SET(%s, cdata.`%s`)',
                                            db_input($k), $name);
                                    }, $value)
                                ) . ')';
                            }
                            else {
                                $where[] = sprintf("cdata.%s = %s", $name, db_input($value));
                            }
                        }
                    }
                }
            }
            if ($cdata_search)
                $tables[] = TABLE_PREFIX.'ticket__cdata cdata'
                    .' ON (cdata.ticket_id = A1.ticket_id)';

            // Always consider the current staff's access
            $thisstaff->getDepts();
            $access = array();
            $access[] = '(A1.staff_id=' . db_input($thisstaff->getId())
                .' AND A2.state="open")';

            if (!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts()))
                $access[] = 'A1.dept_id IN ('
                    . ($depts ? implode(',', db_input($depts)) : 0)
                    . ')';

            if (($teams = $thisstaff->getTeams()) && count(array_filter($teams)))
                $access[] = 'A1.team_id IN ('
                    .implode(',', db_input(array_filter($teams)))
                    .') AND A2.state="open"';

            $where[] = '(' . implode(' OR ', $access) . ')';

            // TODO: Consider sorting preferences

            $sql = 'SELECT DISTINCT '
                . $key
                . ' FROM '
                . implode(' LEFT JOIN ', $tables)
                . ' WHERE ' . implode(' AND ', $where)
                . $sort
                . ' LIMIT 500';
        }

        $class = get_class();
        $auto_create = function($db_error) use ($class) {

            if ($db_error != 1146)
                // Perform the standard error handling
                return true;

            // Create the search table automatically
            $class::createSearchTable();
        };
        $res = db_query($sql, $auto_create);
        $object_ids = array();

        while ($row = db_fetch_row($res))
            $object_ids[] = $row[0];

        return $object_ids;
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
            throw new Exception('Galera cannot be used with MyISAM tables');
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
        return db_query($sql);
    }

    /**
     * Cooperates with the cron system to automatically find content that is
     * not index in the _search table and add it to the index.
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

        $sql = "SELECT A1.`id`, A1.`title`, A1.`body`, A1.`format` FROM `".TICKET_THREAD_TABLE."` A1
            LEFT JOIN `".TABLE_PREFIX."_search` A2 ON (A1.`id` = A2.`object_id` AND A2.`object_type`='H')
            WHERE A2.`object_id` IS NULL AND (A1.poster <> 'SYSTEM')
            AND (LENGTH(A1.`title`) + LENGTH(A1.`body`) > 0)
            ORDER BY A1.`id` DESC";
        if (!($res = db_query_unbuffered($sql, $auto_create)))
            return false;

        while ($row = db_fetch_row($res)) {
            $body = ThreadBody::fromFormattedText($row[2], $row[3]);
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
            ORDER BY A1.`ticket_id` DESC";
        if (!($res = db_query_unbuffered($sql, $auto_create)))
            return false;

        while ($row = db_fetch_row($res)) {
            $ticket = Ticket::lookup($row[0]);
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
        if (!db_query($sql) || count($queue) != db_affected_rows())
            throw new Exception('Unable to index content');

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
