<?php
/*********************************************************************
    ajax.reports.php

    AJAX interface for reports -- both plot and tabular data are retrievable
    in JSON format from this utility. Please put plumbing in /scp/ajax.php
    pattern rules.

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

if(!defined('INCLUDE_DIR')) die('403');

include_once(INCLUDE_DIR.'class.ticket.php');

/**
 * Overview Report
 * 
 * The overview report allows for the display of basic ticket statistics in
 * both graphical and tabular formats.
 */
class OverviewReportAjaxAPI extends AjaxController {
    function enumTabularGroups() {
        return $this->encode(array("dept"=>"Department", "topic"=>"Topics",
            # XXX: This will be relative to permissions based on the
            # logged-in-staff. For basic staff, this will be 'My Stats'
            "staff"=>"Staff"));
    }

    function getData() {
        global $thisstaff;

        $start = $this->get('start', 'last month');
        $stop = $this->get('stop', 'now');
        if (substr($stop, 0, 1) == '+')
            $stop = $start . $stop;
        $start = 'FROM_UNIXTIME('.strtotime($start).')';
        $stop = 'FROM_UNIXTIME('.strtotime($stop).')';

        $groups = array(
            "dept" => array(
                "table" => DEPT_TABLE,
                "pk" => "dept_id",
                "sort" => '1',
                "fields" => 'T1.dept_name',
                "headers" => array('Department'),
                "filter" => "1"
            ),
            "topic" => array(
                "table" => TOPIC_TABLE,
                "pk" => "topic_id",
                "sort" => '1',
                "fields" => "CONCAT_WS(' / ',"
                    ."(SELECT P.topic FROM ".TOPIC_TABLE." P WHERE P.topic_id = T1.topic_pid),"
                    ."T1.topic)",
                "headers" => array('Help Topic'),
                "filter" => "1"
            ),
            "staff" => array(
                "table" => STAFF_TABLE,
                "pk" => 'staff_id',
                "sort" => 'T1.lastname, T1.firstname',
                "fields" => "CONCAT_WS(' ', T1.firstname, T1.lastname)",
                "headers" => array('Staff Member'),
                "filter" =>
                    ($thisstaff->isAdmin())
                        ? "1"
                        : (($thisstaff->isManager())
                            ? "T1.dept_id=".db_input($thisstaff->getDeptId())
                            : "T1.staff_id=".db_input($thisstaff->getId()))
            )
        );
        $group = $this->get('group', 'dept');
        $info = $groups[$group];
        # XXX: Die if $group not in $groups

        $queries=array(
            array(5, 'SELECT '.$info['fields'].',
                COUNT(*)-COUNT(NULLIF(A1.state, "created")) AS Opened,
                COUNT(*)-COUNT(NULLIF(A1.state, "assigned")) AS Assigned,
                COUNT(*)-COUNT(NULLIF(A1.state, "overdue")) AS Overdue,
                COUNT(*)-COUNT(NULLIF(A1.state, "closed")) AS Closed,
                COUNT(*)-COUNT(NULLIF(A1.state, "reopened")) AS Reopened
            FROM '.$info['table'].' T1 LEFT JOIN '.TICKET_TABLE.' T2 USING ('.$info['pk'].')
                LEFT JOIN '.TICKET_EVENT_TABLE.' A1 USING (ticket_id)
            WHERE '.$info['filter'].' AND A1.timestamp BETWEEN '.$start.' AND '.$stop.'
            GROUP BY '.$info['fields'].'
            ORDER BY '.$info['sort']),

            array(1, 'SELECT '.$info['fields'].',
                FORMAT(AVG(DATEDIFF(T2.closed, T2.created)),1) AS ServiceTime
            FROM '.$info['table'].' T1 LEFT JOIN '.TICKET_TABLE.' T2 USING ('.$info['pk'].')
            WHERE '.$info['filter'].' AND T2.closed BETWEEN '.$start.' AND '.$stop.'
            GROUP BY '.$info['fields'].'
            ORDER BY '.$info['sort']),

            array(1, 'SELECT '.$info['fields'].',
                FORMAT(AVG(DATEDIFF(B2.created, B1.created)),1) AS ResponseTime
            FROM '.$info['table'].' T1 LEFT JOIN '.TICKET_TABLE.' T2 USING ('.$info['pk'].')
                LEFT JOIN '.TICKET_THREAD_TABLE.' B2 ON (B2.ticket_id = T2.ticket_id
                    AND B2.thread_type="R")
                LEFT JOIN '.TICKET_THREAD_TABLE.' B1 ON (B2.pid = B1.id)
            WHERE '.$info['filter'].' AND B1.created BETWEEN '.$start.' AND '.$stop.'
            GROUP BY '.$info['fields'].'
            ORDER BY '.$info['sort'])
        );
        $rows = array();
        $cols = 1;
        foreach ($queries as $q) {
            list($c, $sql) = $q;
            $res = db_query($sql);
            $cols += $c;
            while ($row = db_fetch_row($res)) {
                $found = false;
                foreach ($rows as &$r) {
                    if ($r[0] == $row[0]) {
                        $r = array_merge($r, array_slice($row, -$c));
                        $found = true;
                        break;
                    }
                }
                if (!$found)
                    $rows[] = array_merge(array($row[0]), array_slice($row, -$c));
            }
            # Make sure each row has the same number of items
            foreach ($rows as &$r)
                while (count($r) < $cols)
                    $r[] = null;
        }
        return array("columns" => array_merge($info['headers'],
                        array('Open','Assigned','Overdue','Closed','Reopened',
                              'Service Time','Response Time')),
                     "data" => $rows);
    }

    function getTabularData() {
        return $this->encode($this->getData());
    }

    function downloadTabularData() {
        $data = $this->getData();
        $csv = '"' . implode('","',$data['columns']) . '"';
        foreach ($data['data'] as $row)
            $csv .= "\n" . '"' . implode('","', $row) . '"';
        Http::download(
            sprintf('%s-report.csv', $this->get('group', 'Department')),
            'text/csv', $csv);
    }

    function getPlotData() {
        $start = $this->get('start', 'last month');
        $stop = $this->get('stop', 'now');
        if (substr($stop, 0, 1) == '+')
            $stop = $start . $stop;
        $start = strtotime($start);
        $stop = strtotime($stop);

        # Fetch all types of events over the timeframe
        $res = db_query('SELECT DISTINCT(state) FROM '.TICKET_EVENT_TABLE
            .' WHERE timestamp BETWEEN FROM_UNIXTIME('.db_input($start)
                .') AND FROM_UNIXTIME('.db_input($stop)
                .') ORDER BY 1');
        $events = array();
        while ($row = db_fetch_row($res)) $events[] = $row[0];

        # TODO: Handle user => db timezone offset
        # XXX: Implement annulled column from the %ticket_event table
        $res = db_query('SELECT state, DATE_FORMAT(timestamp, \'%Y-%m-%d\'), '
                .'COUNT(ticket_id)'
            .' FROM '.TICKET_EVENT_TABLE
            .' WHERE timestamp BETWEEN FROM_UNIXTIME('.db_input($start)
                .') AND FROM_UNIXTIME('.db_input($stop)
            .') AND NOT annulled'
            .' GROUP BY state, DATE_FORMAT(timestamp, \'%Y-%m-%d\')'
            .' ORDER BY 2, 1');
        # Initialize array of plot values
        $plots = array();
        foreach ($events as $e) { $plots[$e] = array(); }

        $time = null; $times = array();
        # Iterate over result set, adding zeros for missing ticket events
        while ($row = db_fetch_row($res)) {
            $row_time = strtotime($row[1]);
            if ($time != $row_time) {
                # New time (and not the first), figure out which events did
                # not have any tickets associated for this time slot
                if ($time !== null) {
                    # Not the first record -- add zeros all the arrays that
                    # did not have at least one entry for the timeframe
                    foreach (array_diff($events, $slots) as $slot)
                        $plots[$slot][] = 0;
                }
                $slots = array();
                $times[] = $time = $row_time;
            }
            # Keep track of states for this timeframe
            $slots[] = $row[0];
            $plots[$row[0]][] = (int)$row[2];
        }
        foreach (array_diff($events, $slots) as $slot)
            $plots[$slot][] = 0;
        return $this->encode(array("times" => $times, "plots" => $plots,
            "events"=>$events));
    }
}
