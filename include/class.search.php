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

    function register($backend=false) {
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
            foreach ($model->getDynamicData() as $e)
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
            foreach ($model->getDynamicData() as $e)
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
        Signal::connect('model.deleted', array($this, 'deleteModel'));
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

    function find($query, $criteria=array(), $model=false, $sort=array()) {
        global $thisstaff;

        $tables = array();
        $mode = ' IN BOOLEAN MODE';
        #if (count(explode(' ', $query)) == 1)
        #    $mode = ' WITH QUERY EXPANSION';
        $where = array('MATCH (search.title, search.content) AGAINST ('
            .db_input($query)
            .$mode.') ');
        $fields = array($where[0] . ' AS `relevance`');

        switch ($model) {
        case false:
        case 'Ticket':
            $tables[] = ORGANIZATION_TABLE . " A4 ON (A4.id = `search`.object_id
                AND `search`.object_type = 'O')";
            $tables[] = USER_TABLE . " A3 ON ((A3.id = `search`.object_id
                AND `search`.object_type = 'U') OR A3.org_id = A4.id)";
            $tables[] = TICKET_THREAD_TABLE . " A2 ON (A2.id = `search`.object_id
                AND `search`.object_type = 'H')";
            $tables[] = TICKET_TABLE . " A1 ON ((A1.ticket_id = `search`.object_id
                AND `search`.object_type='T')
                OR (A4.id = A3.org_id AND A1.user_id = A3.id)
                OR A3.id = A1.user_id
                OR A2.ticket_id = A1.ticket_id)";
            $fields[] = 'A1.`ticket_id`';
            $key = 'ticket_id';

            if ($criteria) {
                foreach ($criteria as $name=>$value) {
                    switch ($name) {
                    case 'status':
                    case 'topic_id':
                    case 'staff_id':
                    case 'dept_id':
                    case 'user_id':
                        $where[] = sprintf('A1.%s = %s', $name, db_input($value));
                        break;
                    case 'email':
                    case 'org_id':
                    case 'form_id':
                    }
                }
            }

            // Always consider the current staff's access
            $thisstaff->getDepts();
            $access = array();
            $access[] = '(A1.staff_id=' . db_input($thisstaff->getId())
                .' AND A1.status="open")';

            if (!$thisstaff->showAssignedOnly() && ($depts=$thisstaff->getDepts()))
                $access[] = 'A1.dept_id IN ('
                    . ($depts ? implode(',', db_input($depts)) : 0)
                    . ')';

            if (($teams = $thisstaff->getTeams()) && count(array_filter($teams)))
                $access[] = 'A1.team_id IN ('
                    .implode(',', db_input(array_filter($teams)))
                    .') AND A1.status="open"';

            $where[] = '(' . implode(' OR ', $access) . ')';

            $sql = 'SELECT DISTINCT '
                . $key
                . ' FROM ( SELECT '
                . implode(', ', $fields)
                . ' FROM `'.TABLE_PREFIX.'_search` `search` '
                . (count($tables) ? ' LEFT JOIN ' : '')
                . implode(' LEFT JOIN ', $tables)
                . ' WHERE ' . implode(' AND ', $where)
                // TODO: Consider sorting preferences
                . ' ORDER BY `relevance` DESC'
                . ') __ LIMIT 500';
        }

        $res = db_query($sql);
        $object_ids = array();

        while ($row = db_fetch_row($res))
            $object_ids[] = $row[0];

        return $object_ids;
    }

    static function createSearchTable() {
        $sql = 'CREATE TABLE '.TABLE_PREFIX.'_search (
            `object_type` varchar(8) not null,
            `object_id` int(11) unsigned not null,
            `title` text collate utf8_general_ci,
            `content` text collate utf8_general_ci,
            primary key `object` (`object_type`, `object_id`),
            fulltext key `search` (`title`, `content`)
        ) ENGINE=MyISAM CHARSET=utf8';
        return db_query($sql);
    }

    /**
     * Cooperates with the cron system to automatically find content that is
     * not index in the _search table and add it to the index.
     */
    function IndexOldStuff() {
        print 'Indexing old stuff!';

        $class = get_class();
        $auto_create = function($db_error) use ($class) {

            if ($db_error != 1146)
                // Perform the standard error handling
                return true;

            // Create the search table automatically
            $class::createSearchTable();
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
            $records[] = array('H', $row[0], $title, $body);
            if (count($records) > self::$BATCH_SIZE)
                if (null === ($records = self::__searchFlush($records)))
                    return;
        }
        $records = self::__searchFlush($records);

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
            $records[] = array('T', $ticket->getId(),
                Format::searchable($ticket->getSubject()),
                implode("\n", $content));
            if (count($records) > self::$BATCH_SIZE)
                if (null === ($records = self::__searchFlush($records)))
                    return;
        }
        $records = self::__searchFlush($records);

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
            $records[] = array('U', $user->getId(),
                Format::searchable($user->getFullName()),
                trim(implode("\n", $content)));
            if (count($records) > self::$BATCH_SIZE)
                if (null === ($records = self::__searchFlush($records)))
                    return;
        }
        $records = self::__searchFlush($records);

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
            $records[] = array('O', $org->getId(),
                Format::searchable($org->getName()),
                trim(implode("\n", $content)));
            if (count($records) > self::$BATCH_SIZE)
                $records = self::__searchFlush($records);
        }
        $records = self::__searchFlush($records);

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
            $records[] = array('K', $faq->getId(),
                Format::searchable($faq->getQuestion()),
                $faq->getSearchableAnswer());
            if (count($records) > self::$BATCH_SIZE)
                if (null === ($records = self::__searchFlush($records)))
                    return;
        }
        $records = self::__searchFlush($records);

        // FILES ------------------------------------
    }

    function __searchFlush($records) {
        if (!$records)
            return $records;

        foreach ($records as &$r)
            $r = sprintf('(%s)', implode(',', db_input($r)));
        unset($r);

        $sql = 'INSERT INTO `'.TABLE_PREFIX.'_search` (`object_type`, `object_id`, `title`, `content`)
            VALUES '.implode(',', $records);
        if (!db_query($sql) || count($records) != db_affected_rows())
            throw new Exception('Unable to index content');

        if (!--$this->max_batches)
            return null;

        return array();
    }
}
MysqlSearchBackend::register();
