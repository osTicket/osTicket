<?php
/*************************************************************************
    class.export.php

    Exports stuff (details to follow)

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Export {

    // XXX: This may need to be moved to a print-specific class
    static $paper_sizes = array(
        /* @trans */ 'Letter',
        /* @trans */ 'Legal',
        'A4',
        'A3',
    );

    static function dumpQuery($sql, $headers, $how='csv', $options=array()) {
        $exporters = array(
            'csv' => 'CsvResultsExporter',
            'json' => 'JsonResultsExporter'
        );
        $exp = new $exporters[$how]($sql, $headers, $options);
        return $exp->dump();
    }

    # XXX: Think about facilitated exporting. For instance, we might have a
    #      TicketExporter, which will know how to formulate or lookup a
    #      format query (SQL), and cooperate with the output process to add
    #      extra (recursive) information. In this funciton, the top-level
    #      SQL is exported, but for something like tickets, we will need to
    #      export attached messages, reponses, and notes, as well as
    #      attachments associated with each, ...
    static function dumpTickets($sql, $target=array(), $how='csv',
            $options=array()) {
        // Add custom fields to the $sql statement
        $cdata = $fields = array();
        foreach (TicketForm::getInstance()->getFields() as $f) {
            // Ignore core fields
            if (in_array($f->get('name'), array('priority')))
                continue;
            // Ignore non-data fields
            elseif (!$f->hasData() || $f->isPresentationOnly())
                continue;

            $name = $f->get('name') ?: 'field_'.$f->get('id');
            $key = 'cdata.'.$name;
            $fields[$key] = $f;
            $cdata[$key] = $f->getLocal('label');
        }

        if (!is_array($target))
          $target = CustomQueue::getExportableFields() + $cdata;

        // Reset the $sql query
        $tickets = $sql->models()
            ->select_related('user', 'user__default_email', 'dept', 'staff',
                'team', 'staff', 'cdata', 'topic', 'status', 'cdata__:priority')
            ->annotate(array(
                'collab_count' => TicketThread::objects()
                    ->filter(array('ticket__ticket_id' => new SqlField('ticket_id', 1)))
                    ->aggregate(array('count' => SqlAggregate::COUNT('collaborators__id'))),
                'attachment_count' => TicketThread::objects()
                    ->filter(array('ticket__ticket_id' => new SqlField('ticket_id', 1)))
                    ->filter(array('entries__attachments__inline' => 0))
                    ->aggregate(array('count' => SqlAggregate::COUNT('entries__attachments__id'))),
                'reopen_count' => TicketThread::objects()
                    ->filter(array('ticket__ticket_id' => new SqlField('ticket_id', 1)))
                    ->filter(array('events__annulled' => 0, 'events__event_id' => Event::getIdByName('reopened')))
                    ->aggregate(array('count' => SqlAggregate::COUNT('events__id'))),
                'thread_count' => TicketThread::objects()
                    ->filter(array('ticket__ticket_id' => new SqlField('ticket_id', 1)))
                    ->exclude(array('entries__flags__hasbit' => ThreadEntry::FLAG_HIDDEN))
                    ->aggregate(array('count' => SqlAggregate::COUNT('entries__id'))),
            ));

        // Fetch staff information
        // FIXME: Adjust Staff model so it doesn't do extra queries
        foreach (Staff::objects() as $S)
            $S->get('junk');

        return self::dumpQuery($tickets,
            $target,
            $how,
            array('modify' => function(&$record, $keys) use ($fields) {
                foreach ($fields as $k=>$f) {
                    if (($i = array_search($k, $keys)) !== false) {
                        $record[$i] = $f->export($f->to_php($record[$i]));
                    }
                }
                return $record;
            },
            'delimiter' => @$options['delimiter'])
            );
    }

    static  function saveTickets($sql, $fields, $filename, $how='csv',
            $options=array()) {
       global $thisstaff;

       if (!$thisstaff)
               return null;

       $sql->filter($thisstaff->getTicketsVisibility());
        Http::download($filename, "text/$how");
        self::dumpTickets($sql, $fields, $how, $options);
        exit;
    }


    static function dumpTasks($sql, $how='csv') {
        // Add custom fields to the $sql statement
        $cdata = $fields = array();
        foreach (TaskForm::getInstance()->getFields() as $f) {
            // Ignore non-data fields
            if (!$f->hasData() || $f->isPresentationOnly())
                continue;

            $name = $f->get('name') ?: 'field_'.$f->get('id');
            $key = 'cdata.'.$name;
            $fields[$key] = $f;
            $cdata[$key] = $f->getLocal('label');
        }
        // Reset the $sql query
        $tasks = $sql->models()
            ->select_related('dept', 'staff', 'team', 'cdata')
            ->annotate(array(
            'collab_count' => SqlAggregate::COUNT('thread__collaborators'),
            'attachment_count' => SqlAggregate::COUNT('thread__entries__attachments'),
            'thread_count' => SqlAggregate::COUNT('thread__entries'),
        ));

        return self::dumpQuery($tasks,
            array(
                'number' =>         __('Task Number'),
                'created' =>        __('Date Created'),
                'cdata.title' =>    __('Title'),
                'dept::getLocalName' => __('Department'),
                '::getStatus' =>    __('Current Status'),
                'duedate' =>        __('Due Date'),
                'staff::getName' => __('Agent Assigned'),
                'team::getName' =>  __('Team Assigned'),
                'thread_count' =>   __('Thread Count'),
                'attachment_count' => __('Attachment Count'),
            ) + $cdata,
            $how,
            array('modify' => function(&$record, $keys, $obj) use ($fields) {
                foreach ($fields as $k=>$f) {
                    if (($i = array_search($k, $keys)) !== false) {
                        $record[$i] = $f->export($f->to_php($record[$i]));
                    }
                }
                return $record;
            })
            );
    }


    static function saveTasks($sql, $filename, $how='csv') {

        ob_start();
        self::dumpTasks($sql, $how);
        $stuff = ob_get_contents();
        ob_end_clean();
        if ($stuff)
            Http::download($filename, "text/$how", $stuff);

        return false;
    }

    static function saveUsers($sql, $filename, $how='csv') {

        $exclude = array('name', 'email');
        $form = UserForm::getUserForm();
        $fields = $form->getExportableFields($exclude, 'cdata.');

        $cdata = array_combine(array_keys($fields),
                array_values(array_map(
                        function ($f) { return $f->getLocal('label'); }, $fields)));

        $users = $sql->models()
            ->select_related('org', 'cdata');

        ob_start();
        echo self::dumpQuery($users,
                array(
                    'name'  =>          __('Name'),
                    'org' =>   __('Organization'),
                    '::getEmail' =>          __('Email'),
                    ) + $cdata,
                $how,
                array('modify' => function(&$record, $keys, $obj) use ($fields) {
                    foreach ($fields as $k=>$f) {
                        if ($f && ($i = array_search($k, $keys)) !== false) {
                            $record[$i] = $f->export($f->to_php($record[$i]));
                        }
                    }
                    return $record;
                    })
                );
        $stuff = ob_get_contents();
        ob_end_clean();

        if ($stuff)
            Http::download($filename, "text/$how", $stuff);

        return false;
    }

    static function saveOrganizations($sql, $filename, $how='csv') {

        $exclude = array('name');
        $form = OrganizationForm::getDefaultForm();
        $fields = $form->getExportableFields($exclude, 'cdata.');
        $cdata = array_combine(array_keys($fields),
                array_values(array_map(
                        function ($f) { return $f->getLocal('label'); }, $fields)));

        $cdata += array(
                '::getNumUsers' => 'Users',
                '::getAccountManager' => 'Account Manager',
                );

        $orgs = $sql->models();
        ob_start();
        echo self::dumpQuery($orgs,
                array(
                    'name'  =>  'Name',
                    ) + $cdata,
                $how,
                array('modify' => function(&$record, $keys, $obj) use ($fields) {
                    foreach ($fields as $k=>$f) {
                        if ($f && ($i = array_search($k, $keys)) !== false) {
                            $record[$i] = $f->export($f->to_php($record[$i]));
                        }
                    }
                    return $record;
                    })
                );
        $stuff = ob_get_contents();
        ob_end_clean();

        if ($stuff)
            Http::download($filename, "text/$how", $stuff);

        return false;
    }

    static function agents($agents, $filename='', $how='csv') {

        // Filename or stream to export agents to
        $filename = $filename ?: sprintf('Agents-%s.csv',
                strftime('%Y%m%d'));
        Http::download($filename, "text/$how");
        $depts = Dept::getDepartments(null, true, Dept::DISPLAY_DISABLED);
        echo self::dumpQuery($agents, array(
                    '::getName'  =>  'Name',
                    '::getUsername' => 'Username',
                    '::getStatus' => 'Status',
                    'permissions' => 'Permissions',
                    '::getDept'  => 'Primary Department',
                    ) + $depts,
                $how,
                array('modify' => function(&$record, $keys, $obj) use ($depts) {

                   if (($i = array_search('permissions', $keys)))
                       $record[$i] = implode(",", array_keys($obj->getPermission()->getInfo()));

                    $roles = $obj->getRoles();
                    foreach ($depts as $k => $v) {
                        if (is_numeric($k) && ($i = array_search($k, $keys)) !== false) {
                            $record[$i] = $roles[$k] ?: '';
                        }
                    }
                    return $record;
                    })
                );
        exit;

    }

static function departmentMembers($dept, $agents, $filename='', $how='csv') {
    $primaryMembers = array();
    foreach ($dept->getPrimaryMembers() as $agent) {
      $primaryMembers[] = $agent->getId();
    }

    // Filename or stream to export depts' agents to
    $filename = $filename ?: sprintf('%s-%s.csv', $dept->getName(),
            strftime('%Y%m%d'));
    Http::download($filename, "text/$how");
    echo self::dumpQuery($agents, array(
                '::getName'  =>  'Name',
                '::getUsername' => 'Username',
                2 => 'Access Type',
                3 => 'Access Role',
              ),
            $how,
            array('modify' => function(&$record, $keys, $obj) use ($dept, $primaries, $primaryMembers) {
                $role = $obj->getRole($dept);

                if (array_search($obj->getId(), $primaryMembers, true) === false)
                  $type = 'Extended';
                else {
                  $type = 'Primary';
                }

                $record[2] = $type;
                $record[3] = $role->name;
                return $record;
                })
            );
    exit;
  }
}

class ResultSetExporter {
    var $output;

    function __construct($sql, $headers, $options=array()) {
        $this->headers = array_values($headers);
        // Remove limit and offset
        $sql->limit(null)->offset(null);
        # TODO: If $filter, add different LIMIT clause to query
        $this->options = $options;
        $this->output = $options['output'] ?: fopen('php://output', 'w');

        $this->headers = array();
        $this->keys = array();
        foreach ($headers as $field=>$name) {
            $this->headers[] = $name;
            $this->keys[] = $field;
        }
        $this->_res = $sql->getIterator();
        if ($this->_res instanceof IteratorAggregate)
            $this->_res = $this->_res->getIterator();
        $this->_res->rewind();
    }

    function getHeaders() {
        return $this->headers;
    }

    function next() {
        if (!$this->_res->valid())
            return false;

        $object = $this->_res->current();
        $this->_res->next();

        $record = array();
        foreach ($this->keys as $field) {
            list($field, $func) = explode('::', $field);
            $path = explode('.', $field);

            $current = $object;
            // Evaluate dotted ORM path
            if ($field) {
                foreach ($path as $P) {
                    if (isset($current->{$P}))
                        $current = $current->{$P};
                    else  {
                        $current = $P;
                        break;
                    }
                }
            }
            // Evalutate :: function call on target current
            if ($func && (method_exists($current, $func) || method_exists($current, '__call'))) {
                $current = $current->{$func}();
            }

            $record[] = (string) $current;
        }

        if (isset($this->options['modify']) && is_callable($this->options['modify']))
            $record = $this->options['modify']($record, $this->keys, $object);

        return $record;
    }

    function nextArray() {
        if (!($row = $this->next()))
            return false;
        return array_combine($this->keys, $row);
    }

    function dump() {
        # Useful for debug output
        while ($row=$this->nextArray()) {
            var_dump($row); //nolint
        }
    }
}

class CsvResultsExporter extends ResultSetExporter {


    function getDelimiter() {

        if (isset($this->options['delimiter']))
            return $this->options['delimiter'];

        return Internationalization::getCSVDelimiter();
    }

    function dump() {

        if (!$this->output)
             $this->output = fopen('php://output', 'w');


        $delimiter = $this->getDelimiter();
        // Output a UTF-8 BOM (byte order mark)
        fputs($this->output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($this->output, $this->getHeaders(), $delimiter);
        while ($row=$this->next())
            fputcsv($this->output, $row, $delimiter);

        fclose($this->output);
    }
}

class JsonResultsExporter extends ResultSetExporter {
    function dump() {
        require_once(INCLUDE_DIR.'class.json.php');
        $exp = new JsonDataEncoder();
        $rows = array();
        while ($row=$this->nextArray()) {
            $rows[] = $row;
        }
        echo $exp->encode($rows);
    }
}

require_once INCLUDE_DIR . 'class.json.php';
require_once INCLUDE_DIR . 'class.migrater.php';
require_once INCLUDE_DIR . 'class.signal.php';

define('OSTICKET_BACKUP_SIGNATURE', 'osTicket-Backup');
define('OSTICKET_BACKUP_VERSION', 'B');

class DatabaseExporter {

    var $stream;
    var $options;
    var $tables = array(CONFIG_TABLE, SYSLOG_TABLE, FILE_TABLE,
        FILE_CHUNK_TABLE, STAFF_TABLE, DEPT_TABLE, TOPIC_TABLE, GROUP_TABLE,
        STAFF_DEPT_TABLE, TEAM_TABLE, TEAM_MEMBER_TABLE, FAQ_TABLE,
        FAQ_TOPIC_TABLE, FAQ_CATEGORY_TABLE, DRAFT_TABLE,
        CANNED_TABLE, TICKET_TABLE, ATTACHMENT_TABLE,
        THREAD_TABLE, THREAD_ENTRY_TABLE, THREAD_ENTRY_EMAIL_TABLE,
        LOCK_TABLE, THREAD_EVENT_TABLE, TICKET_PRIORITY_TABLE,
        EMAIL_TABLE, EMAIL_TEMPLATE_TABLE, EMAIL_TEMPLATE_GRP_TABLE,
        FILTER_TABLE, FILTER_RULE_TABLE, SLA_TABLE, API_KEY_TABLE,
        TIMEZONE_TABLE, SESSION_TABLE, PAGE_TABLE,
        FORM_SEC_TABLE, FORM_FIELD_TABLE, LIST_TABLE, LIST_ITEM_TABLE,
        FORM_ENTRY_TABLE, FORM_ANSWER_TABLE, USER_TABLE, USER_EMAIL_TABLE,
        PLUGIN_TABLE, THREAD_COLLABORATOR_TABLE, TRANSLATION_TABLE,
        USER_ACCOUNT_TABLE, ORGANIZATION_TABLE, NOTE_TABLE
    );

    function __construct($stream, $options=array()) {
        $this->stream = $stream;
        $this->options = $options;
    }

    function write_block($what) {
        fwrite($this->stream, JsonDataEncoder::encode($what));
        fwrite($this->stream, "\n");
    }

    function dump_header() {
        $header = array(
            array(OSTICKET_BACKUP_SIGNATURE, OSTICKET_BACKUP_VERSION),
            array(
                'version'=>THIS_VERSION,
                'table_prefix'=>TABLE_PREFIX,
                'salt'=>SECRET_SALT,
                'dbtype'=>DBTYPE,
                'streams'=>DatabaseMigrater::getUpgradeStreams(
                    UPGRADE_DIR . 'streams/'),
            ),
        );
        $this->write_block($header);
    }

    function dump($error_stream) {
        // Allow plugins to change the tables exported
        Signal::send('export.tables', $this, $this->tables);
        $this->dump_header();

        foreach ($this->tables as $t) {
            if ($error_stream) $error_stream->write("$t\n");

            // Inspect schema
            $table = array();
            $res = db_query("select column_name from information_schema.columns
                where table_schema=DATABASE() and table_name='$t'");
            while (list($field) = db_fetch_row($res))
                $table[] = $field;

            if (!$table) {
                if ($error_stream) $error_stream->write(
                    sprintf(__("%s: Cannot export table with no fields\n"), $t));
                die();
            }
            $this->write_block(
                array('table', substr($t, strlen(TABLE_PREFIX)), $table));

            db_query("select * from $t");

            // Dump row data
            while ($row = db_fetch_row($res))
                $this->write_block($row);

            $this->write_block(array('end-table'));
        }
    }

    function transfer($destination, $query, $callback=false, $options=array()) {
        $header_out = false;
        $res = db_query($query, true, false);
        $i = 0;
        while ($row = db_fetch_array($res)) {
            if (is_callable($callback))
                $callback($row);
            if (!$header_out) {
                $fields = array_keys($row);
                $this->write_block(
                    array('table', $destination, $fields, $options));
                $header_out = true;

            }
            $this->write_block(array_values($row));
        }
        $this->write_block(array('end-table'));
    }

    function transfer_array($destination, $array, $keys, $options=array()) {
        $this->write_block(
            array('table', $destination, $keys, $options));
        foreach ($array as $row) {
            $this->write_block(array_values($row));
        }
        $this->write_block(array('end-table'));
    }
}
