<?php
/*********************************************************************
    class.businesshours.php

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2019 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

include_once INCLUDE_DIR.'class.schedule.php';

/*
 * BusinessHours utility class
 *
 */
class BusinessHours {

    // Schedule
    protected $schedule;

    // Options
    protected $options;

    // Occurrence buckets
    protected $workhours;
    protected $holidays;

    // Timeline log
    protected $timeline;


    function __construct(BusinessHoursSchedule $schedule,
            $options=array()) {

        $this->schedule = $schedule;
        $this->options = $options;
    }

    function __onload() {
        $this->workhours = array();
        $this->holidays = array();
        $this->timeline = array();
    }

    public function getSchedule() {
        return $this->schedule;
    }

    // Intitialize occurrenses buckets
    private function initOccurrences(Datetime $date) {

        $dt = clone $date;
        // Start from next day if current date is already processed
        if ($this->workhours[$dt->format('Y-m-d')])
            $dt->modify('+1 day');

        // Reset occurrense buckets
        $this->workhours = $this->holidays = array();
        // Init workhours
        foreach ($this->getSchedule()->getEntries() as $entry)
            $this->workhours += $entry->getOccurrences($dt, null, 4);
        ksort($this->workhours);
        // Init holidays taking into account end date of the workhours in
        // the current scope.
        $enddate = array_pop(array_keys($this->workhours));
        foreach ($this->getSchedule()->getHolidaysSchedules() as $schedule) {
            foreach ($schedule->getEntries() as $entry)
                $this->holidays += $entry->getOccurrences($dt, $enddate, 5);
        }
        ksort($this->holidays);

        // Return number of work hours occurrences
        return count($this->workhours);
    }

    // Add requesed Working Hours to a given date in accordance to schedule
    public function addWorkingHours(Datetime $date, $hours, &$auditlog=array()) {

        // We ain't doing shit without hours
        if (!$hours || !is_numeric($hours))
            return $date;

        // If the schedule has no entries then return false - indicating an
        // error to the caller
        if (!$this->getSchedule()->getNumEntries())
            return false;

        // Set timezone to schedule's one
        $date->setTimezone($this->getSchedule()->getDatetimeZone());
        $this->timeline(sprintf('*** Add %d Working Hours to %s ***',
                 $hours, $date->format('Y-m-d H:i:s')));
        $seconds = $hours*3600; // Requested hours in seconds
        $_seconds = 0; // Bucker to collect working hours in seconds
        while ($_seconds < $seconds) {
            // Initialize occurrences of schedule entries from this date.
            if (!$this->initOccurrences($date))
                break;
            // Loop thro' business hours while accounting for requested
            // working hours
            foreach ($this->workhours as $d => $e) {
                $partial = false;
                // Handle edge case where we're starting with a date that
                // might or not be within Schedule Entry hours.
                if ($d == $date->format('Y-m-d')) {
                    // If it's after hours then continue to the next working
                    // day
                    if ($e->isAfterHours($date))
                        continue;

                    // If we're already into the day then we need to do
                    // partial / remaining hours
                    if (!$e->isBeforeHours($date))
                        $partial = true;
                }
                // Handle holidays - if within scope of current work day
                $leadtime =0;
                if (($holiday=$this->holidays[$d])) {
                    $this->timeline(sprintf('%s -> Skip %f Holiday Hours from %s [%s]',
                            $d,
                            $holiday->getHours(),
                            $holiday->getSchedule()->getName(),
                            $holiday->getDesc()));
                    // If the holiday is a full day then assume the day is a goner
                    if ($holiday->isFullDay()) continue;
                    //Move the date to end of the partial day of the holiday
                    $date->modify("$d ".$holiday->getEndsTime());
                    $partial = true;
                    // See if we need to recover any time prior to start of
                    // holiday e.g if the day starts at 8am but the  holiday
                    // starts at 10am. Not typical but I foresee people
                    // using holiday concept to account for lunch hours.
                    $hstarts = strtotime($holiday->getStartsTime());
                    $dstarts = strtotime($e->getStartsTime());
                    if ($hstarts > $dstarts)
                        $leadtime = $hstarts-$dstarts;
                }

                // Add time to the bucket.
                $time = $partial ? ($e->diffTime($date) + $leadtime) : $e->diff();
                $_seconds += $time;
                // Advance the date to end of this business day
                $date->modify("$d ".$e->getEndsTime());
                $this->timeline(sprintf('%s -> Apply %f Working Hours from %s
                        - %s (%d)',
                        $d, $time/3600, $e->getName(),
                        $date->format('Y-m-d H:i:s'),
                         ($_seconds/3600)));
                // Back track if the quota is met and break out of the loop.
                if ($_seconds > $seconds) {
                    // TODO: Guard aganist backtracking to non working
                    // hours.
                    $time = round(($_seconds-$seconds));
                    $interval = new DateInterval('PT'.$time.'S');
                    $date->sub($interval);
                    $this->timeline(sprintf('%s -> Backtrack %f Hours  %s (%s)',
                            $d, ($time)/3600,
                            $date->format('Y-m-d H:i:s'), ($seconds/3600)));
                    break;
                }
            }
        }
        $this->timeline(sprintf('*** Final Datetime  %s ***',
                $date->format('Y-m-d H:i:s')));

        return $date;
    }

    // Log of the timeline for audit purposes
    private function timeline($log) {
        $this->timeline[] = $log;
    }

    public function getTimeline() {
        return $this->timeline;
    }
}

?>
