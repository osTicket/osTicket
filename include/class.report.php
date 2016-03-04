<?php

class ReportModel {

    const PERM_AGENTS = 'stats.agents';

    static protected $perms = array(
            self::PERM_AGENTS => array(
                'title' =>
                /* @trans */ 'Stats',
                'desc'  =>
                /* @trans */ 'Ability to view stats of other agents in allowed departments',
                'primary' => true,
            ));

    static function getPermissions() {
        return self::$perms;
    }
}

RolePermission::register(/* @trans */ 'Miscellaneous', ReportModel::getPermissions());

class OverviewReport {
    var $start;
    var $end;

    var $format;

    function __construct($start, $end='now', $format=null) {
        global $cfg;

        $this->start = $start;
        $this->end = $end;
        $this->format = $format ?: $cfg->getDateFormat(true);
    }


    function getStartDate($format=null, $translate=true) {

        if (!$this->start)
            return '';

        $format =  $format ?: $this->format;
        if ($translate) {
            $format = str_replace(
                    array('y', 'Y', 'm'),
                    array('yy', 'yyyy', 'mm'),
                    $format);
        }

        return Format::date(Misc::dbtime($this->start), false, $format);
    }


    function getDateRange() {
        global $cfg;

        $start = $this->start ?: 'last month';
        $stop = $this->end ?: 'now';

        // Convert user time to db time
        $start = Misc::dbtime($start);
        // Stop time can be relative.
        if ($stop[0] == '+') {
            // $start time + time(X days)
            $now = time();
            $stop = $start + (strtotime($stop, $now)-$now);
        } else {
            $stop = Misc::dbtime($stop);
        }

        $start = 'FROM_UNIXTIME('.$start.')';
        $stop = 'FROM_UNIXTIME('.$stop.')';

        return array($start, $stop);
    }

    function getPlotData() {
        list($start, $stop) = $this->getDateRange();

        # Fetch all types of events over the timeframe
        $res = db_query('SELECT DISTINCT(state) FROM '.THREAD_EVENT_TABLE
            .' WHERE timestamp BETWEEN '.$start.' AND '.$stop
            .' AND state IN ("created", "closed", "reopened", "assigned", "overdue", "transferred")'
            .' ORDER BY 1');
        $events = array();
        while ($row = db_fetch_row($res)) $events[] = $row[0];

        # TODO: Handle user => db timezone offset
        # XXX: Implement annulled column from the %ticket_event table
        $res = db_query('SELECT state, DATE_FORMAT(timestamp, \'%Y-%m-%d\'), '
                .'COUNT(DISTINCT T.id)'
            .' FROM '.THREAD_EVENT_TABLE. ' E '
            .' JOIN '.THREAD_TABLE. ' T
                ON (T.id = E.thread_id AND T.object_type = "T") '
            .' WHERE E.timestamp BETWEEN '.$start.' AND '.$stop
            .' AND NOT annulled'
            .' AND E.state IN ("created", "closed", "reopened", "assigned", "overdue", "transferred")'
            .' GROUP BY E.state, DATE_FORMAT(E.timestamp, \'%Y-%m-%d\')'
            .' ORDER BY 2, 1');
        # Initialize array of plot values
        $plots = array();
        foreach ($events as $e) { $plots[$e] = array(); }

        $time = null; $times = array();
        # Iterate over result set, adding zeros for missing ticket events
        $slots = array();
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

        return array("times" => $times, "plots" => $plots, "events" => $events);
    }

    function enumTabularGroups() {
        return array("dept"=>__("Department"), "topic"=>__("Topics"),
            # XXX: This will be relative to permissions based on the
            # logged-in-staff. For basic staff, this will be 'My Stats'
            "staff"=>__("Agent"));
    }

    function getTabularData($group='dept') {
        global $thisstaff;

        list($start, $stop) = $this->getDateRange();
        $times = Ticket::objects()
            ->constrain(array(
                'thread__entries' => array(
                    'thread__entries__type' => 'R'
                ),
            ))
            ->aggregate(array(
                'ServiceTime' => SqlAggregate::AVG(SqlFunction::DATEDIFF(
                    new SqlField('closed'), new SqlField('created')
                )),
                'ResponseTime' => SqlAggregate::AVG(SqlFunction::DATEDIFF(
                    new SqlField('thread__entries__created'), new SqlField('thread__entries__parent__created')
                )),
            ));

        $stats = Ticket::objects()
            ->constrain(array(
                'thread__events' => array(
                    'thread__events__annulled' => 0,
                    'thread__events__timestamp__range' => array($start, $stop),
                ),
            ))
            ->aggregate(array(
                'Opened' => SqlAggregate::COUNT(
                    SqlCase::N()
                        ->when(new Q(array('thread__events__state' => 'created')), 1)
                ),
                'Assigned' => SqlAggregate::COUNT(
                    SqlCase::N()
                        ->when(new Q(array('thread__events__state' => 'assigned')), 1)
                ),
                'Overdue' => SqlAggregate::COUNT(
                    SqlCase::N()
                        ->when(new Q(array('thread__events__state' => 'overdue')), 1)
                ),
                'Closed' => SqlAggregate::COUNT(
                    SqlCase::N()
                        ->when(new Q(array('thread__events__state' => 'closed')), 1)
                ),
                'Reopened' => SqlAggregate::COUNT(
                    SqlCase::N()
                        ->when(new Q(array('thread__events__state' => 'reopened')), 1)
                ),
            ));

        switch ($group) {
        case 'dept':
            $headers = array(__('Department'));
            $header = function($row) { return Dept::getLocalNameById($row['dept_id'], $row['dept__name']); };
            $pk = 'dept_id';
            $stats = $stats
                ->filter(array('dept_id__in' => $thisstaff->getDepts()))
                ->values('dept__id', 'dept__name');
            $times = $times
                ->filter(array('dept_id__in' => $thisstaff->getDepts()))
                ->values('dept__id');
            break;
        case 'topic':
            $headers = array(__('Help Topic'));
            $header = function($row) { return Topic::getLocalNameById($row['topic_id'], $row['topic__topic']); };
            $pk = 'topic_id';
            $stats = $stats
                ->values('topic_id', 'topic__topic')
                ->filter(array('topic_id__gt' => 0));
            $times = $times
                ->values('topic_id')
                ->filter(array('topic_id__gt' => 0));
            break;
        case 'staff':
            $headers = array(__('Agent'));
            $header = function($row) { return new AgentsName(array(
                'first' => $row['staff__firstname'], 'last' => $row['staff__lastname'])); };
            $pk = 'staff_id';
            $stats = $stats->values('staff_id', 'staff__firstname', 'staff__lastname');
            $times = $times->values('staff_id');
            $depts = $thisstaff->getManagedDepartments();
            if ($thisstaff->hasPerm(ReportModel::PERM_AGENTS))
                $depts = array_merge($depts, $thisstaff->getDepts());
            $Q = Q::any(array(
                'staff_id' => $thisstaff->getId(),
            ));
            if ($depts)
                $Q->add(array('dept_id__in' => $depts));
            $stats = $stats->filter(array('staff_id__gt'=>0))->filter($Q);
            $times = $times->filter(array('staff_id__gt'=>0))->filter($Q);
            break;
        default:
            # XXX: Die if $group not in $groups
        }

        $timings = array();
        foreach ($times as $T) {
            $timings[$T[$pk]] = $T;
        }

        $rows = array();
        foreach ($stats as $R) {
            $T = $timings[$R[$pk]];
            $rows[] = array($header($R), $R['Opened'], $R['Assigned'],
                $R['Overdue'], $R['Closed'], $R['Reopened'],
                number_format($T['ServiceTime'], 1),
                number_format($T['ResponseTime'], 1));
        }
        return array("columns" => array_merge($headers,
                        array(__('Opened'),__('Assigned'),__('Overdue'),__('Closed'),__('Reopened'),
                              __('Service Time'),__('Response Time'))),
                     "data" => $rows);
    }
}
