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
        "staff"=>"Staff"));
    }

    function getData() {
        $start = $this->get('start', strtotime('last month'));
        $stop = $this->get('stop', time());

        $groups = array(
            "dept" => array(
                "table" => DEPT_TABLE,
                "pk" => "dept_id",
                "sort" => 'ORDER BY dept_name',
                "fields" => 'T1.dept_name',
                "headers" => array('Department')
            ),
            "topic" => array(
                "table" => TOPIC_TABLE,
                "pk" => "topic_id",
                "sort" => 'ORDER BY topic',
                "fields" => "T1.topic",
                "headers" => array('Help Topic')
            ),
            "staff" => array(
                "table" => STAFF_TABLE,
                "pk" => 'staff_id',
                "sort" => 'ORDER BY T1.lastname, T1.firstname',
                "fields" => "CONCAT_WS(' ', T1.firstname, T1.lastname)",
                "headers" => array('Staff Member')
            )
        );
        $group = $this->get('group', 'dept');
        $info = $groups[$group];
        # XXX: Die if $group not in $groups

        $res = db_query(
            'SELECT ' . $info['fields'] . ','
                .' COUNT(A1.ticket_id) AS Opened,'
                .' COUNT(A2.ticket_id) AS Closed,'
                .' COUNT(A3.ticket_id) AS Reopened'
            .' FROM ' . $info['table'] . ' T1'
            .' LEFT JOIN ' . TICKET_EVENT_TABLE 
                . ' A1 ON (T1.' . $info['pk'] . ' = A1.' . $info['pk']
                    .' AND A1.state=\'opened\' AND NOT A1.annulled)'
            .' LEFT JOIN ' . TICKET_EVENT_TABLE
                . ' A2 ON (T1.' . $info['pk'] . ' = A2.' . $info['pk']
                    .' AND A2.state=\'closed\' AND NOT A2.annulled)'
            .' LEFT JOIN ' . TICKET_EVENT_TABLE
                . ' A3 ON (T1.' . $info['pk'] . ' = A3.' . $info['pk']
                    .' AND A3.state=\'reopened\' AND NOT A3.annulled)'
            .' GROUP BY '.$info['fields']
        );
        $rows = array();
        while ($row = db_fetch_row($res)) {
            $rows[] = $row;
        }
        return array("columns" => array_merge($info['headers'],
                        array('Opened','Closed','Reopened')),
                     "data" => $rows);
    }

    function getTabularData() {
        return $this->encode($this->getData());
    }

    function downloadTabularData() {
        $data = $this->getData();
        $csv = array($data['columns']);
        $csv = array_merge($csv, $data['data']);
        Http::download(
            sprintf('%s-report.csv', $this->get('group', 'Department')),
            Format::implode_array(',', "\n", $csv));
    }

    function getPlotData() {
        $start = $this->get('start', strtotime('last month'));
        $stop = $this->get('stop', time());

        # Fetch all types of events over the timeframe
        $res = db_query('SELECT DISTINCT(state) FROM '.TICKET_EVENT_TABLE
            .' WHERE timestamp BETWEEN FROM_UNIXTIME('.db_input($start)
                .') AND FROM_UNIXTIME('.db_input($stop).')');
        $events = array();
        while ($row = db_fetch_row($res)) $events[] = $row[0];

        # TODO: Handle user => db timezone offset
        # XXX: Implement annulled column from the %ticket_event table
        $res = db_query('SELECT state, DATE_FORMAT(timestamp, \'%Y-%m-%d\'), '
                .'COUNT(ticket_id)'
            .' FROM '.TICKET_EVENT_TABLE
            .' WHERE timestamp BETWEEN FROM_UNIXTIME('.db_input($start)
                .') AND FROM_UNIXTIME('.db_input($stop)
            .') GROUP BY state, DATE_FORMAT(timestamp, \'%Y-%m-%d\')');
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
                    # Not the first record
                    foreach (array_diff($events, $slots) as $slot) {
                        $plots[$slot][] = 0;
                    }
                }
                $slots = array();
                $times[] = $time = $row_time;
            }
            # Keep track of states for this timeframe
            if (!in_array($row[0], $slots)) $slots[] = $row[0];
            $plots[$row[0]][] = (int)$row[2];
        }
        return $this->encode(array("times" => $times, "plots" => $plots));
    }
}
