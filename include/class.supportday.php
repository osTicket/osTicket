<?php
/*********************************************************************
    class.supportday.php

    Support Day
    Nathan Kitchen <nathan.kitchen@trustmarque.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
*********************************************************************/

class SupportDay {
    const MODE_ZERO = 0;
    const MODE_24HOUR = 1;
    const MODE_TIMED = 2;

    var $mode;
    var $startTime;
    var $endTime;

    function __construct($md, $start, $end) {
        
        $mdParsed = intval($md);
        $startParsed = '1970-01-01';
        $endParsed = '1970-01-01';

        if ($mdParsed < 0) { $mdParsed = self::MODE_ZERO; }
        if ($mdParsed > 2) { $mdParsed = self::MODE_ZERO; }
        
        if (preg_match('/[0-2][0-9]:[0-5][0-9]/', $start)) {
            $startParsed = $startParsed . ' ' . $start;
        }

        if (preg_match('/[0-2][0-9]:[0-5][0-9]/', $end)) {
            $endParsed = $endParsed . ' ' . $end;
        }

        $this->mode = $mdParsed;
        $this->startTime = new DateTime($startParsed);
        $this->endTime = new DateTime($endParsed);
    }

    function getMode() {
        return $this->mode;
    }

    function getStartTime() {
        return $this->startTime;
    }

    function getEndTime() {
        return $this->startTime;
    }

    function getGracePeriodSeconds($seconds, $begin) {
        if ($this->mode === self::MODE_TIMED) {

            $start = $this->startTime;
            $end = $this->endTime;

            if (isset($begin) && $begin instanceof DateTime) {
                $beginCorrect = new DateTime('1970-01-01 ' . $begin->format('H:i:s'));
                
                if ($beginCorrect >= $end) {
                    return $seconds;
                }
                else if ($beginCorrect > $start) {
                    $start = $beginCorrect;
                }
            }

            $difference = round(abs($end->getTimestamp() - $start->getTimestamp()), 2);
            return $seconds - $difference;
        }
        else if ($this->mode === self::MODE_ZERO) {
            return $seconds;
        }
        else {
            // Assume mode 24
            $passed = 0;

            if (isset($begin) && $begin instanceof DateTime) {
                $start = new DateTime('1970-01-01 00:00:00');
                $beginCorrect = new DateTime('1970-01-01 ' . $begin->format('H:i:s'));
                $passed = round(abs($beginCorrect->getTimestamp() - $start->getTimestamp()), 2);
            }

            return $seconds - ((24 * 60 * 60) - $passed);
        }
    }
}
?>