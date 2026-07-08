<?php
/*********************************************************************
    SlaGracePeriodCalculator.php

    Pure wrapper around BusinessHoursSchedule::addWorkingHours for SLA
    grace period calculation. Schedule resolution stays in the caller.

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

include_once INCLUDE_DIR.'class.schedule.php';

class SlaGracePeriodCalculator {

    /**
     * Add grace period hours to $date.
     *
     * When $schedule is provided, delegates to
     * BusinessHoursSchedule::addWorkingHours. Otherwise, or when that call
     * returns false (empty schedule entries), falls back to wall-clock
     * addition. Mutates and returns the same DateTime instance.
     */
    public function calculate(DateTime $date, float $graceHours,
            ?BusinessHoursSchedule $schedule, array &$timeline = []): DateTime {

        if ($schedule && $schedule->addWorkingHours($date, $graceHours, $timeline))
            return $date;

        $time = round($graceHours * 3600);
        $interval = new DateInterval('PT'.$time.'S');
        $date->add($interval);

        return $date;
    }
}

?>
