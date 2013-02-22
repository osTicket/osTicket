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
    
    /* static */ function dumpQuery($sql, $headers, $how='csv', $filter=false) {
        $exporters = array(
            'csv' => CsvResultsExporter,
            'json' => JsonResultsExporter
        );
        $exp = new $exporters[$how]($sql, $headers, $filter);
        return $exp->dump();
    }

    # XXX: Think about facilitated exporting. For instance, we might have a
    #      TicketExporter, which will know how to formulate or lookup a
    #      format query (SQL), and cooperate with the output process to add
    #      extra (recursive) information. In this funciton, the top-level
    #      SQL is exported, but for something like tickets, we will need to
    #      export attached messages, reponses, and notes, as well as
    #      attachments associated with each, ...
    /* static */ function dumpTickets($sql, $how='csv') {
        return self::dumpQuery($sql,
            array(
                'ticketID' =>       'Ticket Id',
                'created' =>        'Date',
                'subject' =>        'Subject',
                'name' =>           'From',
                'priority_desc' =>  'Priority',
                'dept_name' =>      'Department',
                'helptopic' =>      'Help Topic',
                'source' =>         'Source',
                'status' =>         'Current Status'
            ),
            $how);
    }

    /* static */ function saveTickets($sql, $filename, $how='csv') {
        ob_start();
        self::dumpTickets($sql, $how);
        $stuff = ob_get_contents();
        ob_end_clean();
        if ($stuff)
            Http::download($filename, "text/$how", $stuff);

        return false;
    }
}

class ResultSetExporter {
    function ResultSetExporter($sql, $headers, $filter=false) {
        $this->headers = array_values($headers);
        if ($s = strpos(strtoupper($sql), ' LIMIT '))
            $sql = substr($sql, 0, $s);
        # TODO: If $filter, add different LIMIT clause to query
        $this->_res = db_query($sql);
        if ($row = db_fetch_array($this->_res)) {
            $query_fields = array_keys($row);
            $this->headers = array();
            $this->keys = array();
            $this->lookups = array();
            foreach ($headers as $field=>$name) {
                if (isset($row[$field])) {
                    $this->headers[] = $name;
                    $this->keys[] = $field;
                    # Remember the location of this header in the query results
                    # (column-wise) so we don't have to do hashtable lookups for every
                    # column of every row.
                    $this->lookups[] = array_search($field, $query_fields);
                }
            }
            db_data_reset($this->_res);
        }
    }

    function getHeaders() {
        return $this->headers;
    }

    function next() {
        if (!($row = db_fetch_row($this->_res)))
            return false;

        $record = array();
        foreach ($this->lookups as $idx)
            $record[] = $row[$idx];
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
            var_dump($row);
        }
    }
}

class CsvResultsExporter extends ResultSetExporter {
    function dump() {
        echo '"' . implode('","', $this->getHeaders()) . "\"\n";
        while ($row=$this->next()) {
            foreach ($row as &$val)
                # Escape enclosed double-quotes
                $val = str_replace('"','""',$val);
            echo '"' . implode('","', $row) . "\"\n";
        }
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
