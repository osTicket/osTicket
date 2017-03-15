<?php
/*********************************************************************
    class.sla.php

    SLA
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class SLA extends VerySimpleModel
implements TemplateVariable {

    static $meta = array(
        'table' => SLA_TABLE,
        'pk' => array('id'),
    );

    const FLAG_ACTIVE       = 0x0001;
    const FLAG_ESCALATE     = 0x0002;
    const FLAG_NOALERTS     = 0x0004;
    const FLAG_TRANSIENT    = 0x0008;

    var $_config;

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->getLocal('name');
    }

    function getGracePeriod() {
        return $this->grace_period;
    }

    function getBusinessHoursId() {
        return $this->business_hours_id;
    }

    function getInfo() {
        $base = $this->ht;
        $base['isactive'] = $this->flags & self::FLAG_ACTIVE;
        $base['disable_overdue_alerts'] = $this->flags & self::FLAG_NOALERTS;
        $base['enable_priority_escalation'] = $this->flags & self::FLAG_ESCALATE;
        $base['transient'] = $this->flags & self::FLAG_TRANSIENT;
        return $base;
    }

    function getCreateDate() {
        return $this->created;
    }

    function getUpdateDate() {
        return $this->updated;
    }

    function isActive() {
        return $this->flags & self::FLAG_ACTIVE;
    }

    function isTransient() {
        return $this->flags & self::FLAG_TRANSIENT;
    }

    function sendAlerts() {
        return 0 === ($this->flags & self::FLAG_NOALERTS);
    }

    function alertOnOverdue() {
        return $this->sendAlerts();
    }

    function priorityEscalation() {
        return $this->flags && self::FLAG_ESCALATE;
    }

    function getTranslateTag($subtag) {
        return _H(sprintf('sla.%s.%s', $subtag, $this->getId()));
    }

    function getLocal($subtag) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->ht[$subtag];
    }

    static function getLocalById($id, $subtag, $default) {
        $tag = _H(sprintf('sla.%s.%s', $subtag, $id));
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $default;
    }

    // TemplateVariable interface
    function asVar() {
        return $this->getName();
    }

    static function getVarScope() {
        return array(
            'name' => __('Service Level Agreement'),
            'graceperiod' => __("Grace Period (hrs)"),
        );
    }

    function update($vars, &$errors) {

        if (!$vars['grace_period'])
            $errors['grace_period'] = __('Grace period required');
        elseif (!is_numeric($vars['grace_period']))
            $errors['grace_period'] = __('Numeric value required (in hours)');

        if (!$vars['name'])
            $errors['name'] = __('Name is required');
        elseif (($sid=SLA::getIdByName($vars['name'])) && $sid!=$vars['id'])
            $errors['name'] = __('Name already exists');

        if ($errors)
            return false;

        $this->name = $vars['name'];
        $this->grace_period = $vars['grace_period'];
        $this->notes = Format::sanitize($vars['notes']);
        $this->business_hours_id = $vars['business_hours_id'];
        $this->flags =
              ($vars['isactive'] ? self::FLAG_ACTIVE : 0)
            | (isset($vars['disable_overdue_alerts']) ? self::FLAG_NOALERTS : 0)
            | (isset($vars['enable_priority_escalation']) ? self::FLAG_ESCALATE : 0)
            | (isset($vars['transient']) ? self::FLAG_TRANSIENT : 0);

        if ($this->save())
            return true;

        if (isset($this->id)) {
            $errors['err']=sprintf(__('Unable to update %s.'), __('this SLA plan'))
               .' '.__('Internal error occurred');
        } else {
            $errors['err']=sprintf(__('Unable to add %s.'), __('this SLA plan'))
               .' '.__('Internal error occurred');
        }

        return false;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();

        return parent::save($refetch || $this->dirty);
    }

    function delete() {
        global $cfg;

        if(!$cfg || $cfg->getDefaultSLAId()==$this->getId())
            return false;

        //TODO: Use ORM to delete & update
        $id=$this->getId();
        $sql='DELETE FROM '.SLA_TABLE.' WHERE id='.db_input($id).' LIMIT 1';
        if(db_query($sql) && ($num=db_affected_rows())) {
            db_query('UPDATE '.DEPT_TABLE.' SET sla_id=0 WHERE sla_id='.db_input($id));
            db_query('UPDATE '.TOPIC_TABLE.' SET sla_id=0 WHERE sla_id='.db_input($id));
            db_query('UPDATE '.TICKET_TABLE.' SET sla_id='.db_input($cfg->getDefaultSLAId()).' WHERE sla_id='.db_input($id));
        }

        return $num;
    }

    // Handles human times of day and converts them to decimal,
    // rounded to 2 decimal places, handles military time
    // Ex: timeOfDayToDec("9:59 AM ") === 9.98
    static function timeOfDayToDec($tod) {
      $tod = strtolower(trim($tod));
      $m = strrpos($tod,'p') > 0 ? 12 : 0;
      $hm = explode(':',$tod);
      $result = intval($hm[0])+$m+(round(intval($hm[1])/60,2));
      return $result;
    }

    function getParsedBusinessHours() {
        $bh = BusinessHours::lookup($this->getBusinessHoursId());
        $strings = $bh->getUnixMtF();
        $parsed = [];
        for($i=0; $i < 7; $i++) {
            $shifts = explode(',',$strings[$i]); // "5-11,13-17" to ["5-11","13-17"]
            $parsedShift = [];
            foreach($shifts as $shift) {
                $sne = explode('-', $shift); // start and end
                if(count($sne) !== 2) { // some days you don't work
                    continue;
                }
                $parsedShift[] = self::timeOfDayToDec($sne[0]);
                $parsedShift[] = self::timeOfDayToDec($sne[1]);
            }
            $parsed[] = $parsedShift;
        }
        $parsed[] = $bh->getVar("timezone");
        return $parsed;
    }

    /**
    * @param DateTime   $fromdt  The start date to calculate the due date from.
    */
    function calcSLAWithBusinessHours( $rawdt) {
        global $cfg;
        // 8:30a-12:00pm, 1:00pm-5:00pm
        /*$parsed_mtf = [8.5,12.0,13,17];
        $parsedSchedule = [
                    [], // 0 = sunday
                    $parsed_mtf, // 1 = monday
                    $parsed_mtf, // 2 = tuesday
                    $parsed_mtf, // 3 = wednesday
                    $parsed_mtf, // 4 = thursday
                    $parsed_mtf, // 5 = friday
                    [], // 6 = saturday
        ];*/

        $parsedSchedule = $this->getParsedBusinessHours();
        $sla_gracetime = $this->getGracePeriod();

        $fromdt = new DateTime();
        $fromdt->setTimestamp($rawdt->getTimestamp());
        $is_timezoned = ! empty($parsedSchedule[7]); // the Schedule has it's own timezone, ignore system one
        if ( $is_timezoned ) { // fromdt timezone adjustment
            $sched_tz = new DateTimeZone($parsedSchedule[7]);
            $sched_offset = $sched_tz->getOffset($fromdt);

            $tz = new DateTimeZone($cfg->getTimezone());
            $now_dt = new DateTime("now", $tz);
            $system_offset = $tz->getOffset($fromdt);

            $offset_diff = $system_offset-$sched_offset;
            $fromdt->setTimestamp($fromdt->getTimestamp()+$offset_diff);
        }

        $fromDayDow = intval( $fromdt->format('N') );
        $fromDayDow = $fromDayDow === 7 ? 0 : $fromDayDow; // Make Sunday = 0
        $fromTime = floatval( $fromdt->format('G') ) + floatval( $fromdt->format('i') ) / 60; // 13:30:59 = 13.5

        $timeleft = $sla_gracetime;
        $markerdt = clone $fromdt;

        // Day one
        $calculatedFromTime = null;
        for( $i=0; $i<count($parsedSchedule[$fromDayDow]); $i++) { // find calculatedFromTime
            $time = $parsedSchedule[$fromDayDow][$i]; // rounding should be the same as fromTime

            if ( ($i & 1) === 0 || $time < $fromTime  ) { // we don't care about start times (even) or end times before fromdt's time
                continue;
            }

            if( is_null($calculatedFromTime) ) { // this is odd, thus a closing time bracket
                $bracketStart = $parsedSchedule[$fromDayDow][$i-1];
                $calculatedFromTime = $fromTime > $bracketStart ? $fromTime : $bracketStart;
                $timeleft -= $time - $calculatedFromTime;
            } else { // odd means end
                $bracketStart = $parsedSchedule[$fromDayDow][$i-1];
                $timeleft -= $time - $bracketStart;
            }

            if( $timeleft < 0) { // we've over shot, on day one
                $duetime = $time + $timeleft; // this is a substraction
                $duehour = floor($duetime);
                $dueminute = floor(($duetime-$duehour) * 60);
                $markerdt->setTime($duehour,$dueminute);
                $timeleft = 0;
                break;
            }
        }

        // Day 2 to N-1
        while ( $timeleft > 0 && $markerdt->getTimestamp()-$fromdt->getTimestamp() < 90*24*60*60 ) {
            $markerdt->add(new DateInterval('P1D'));
            $dayDow = intval( $markerdt->format('N'));
            $dayDow = $dayDow === 7 ? 0 : $dayDow;
            for( $i=0; $i<count($parsedSchedule[$dayDow]); $i++) {
                $time = $parsedSchedule[$dayDow][$i];
                if ( $i & 1) { // odd means end
                    $bracketStart = $parsedSchedule[$dayDow][$i-1];
                    $timeleft -= $time - $bracketStart;
                }
                if( $timeleft < 0) { // we've over shot, on day one
                    $duetime = $time + $timeleft; // this is a substraction
                    $duehour = floor($duetime);
                    $dueminute = floor(($duetime-$duehour) * 60);
                    $markerdt->setTime($duehour,$dueminute);
                    $timeleft = 0;
                    break;
                }
            }
        }


        if ( $timeleft === 0) {
            if ( $is_timezoned ) {
                $markerdt->setTimestamp($markerdt->getTimestamp()-$offset_diff); // revert timezone back to system offset
                return $markerdt;
            } else
                return $markerdt;
            }
        } else {
            $result = new DateTime('1970-01-01 00:00:00');
            return $result;
        }
    }

    /** static functions **/
    static function getSLAs($criteria=array()) {
       $slas = self::objects()
           ->order_by('name')
           ->values_flat('id', 'name', 'flags', 'grace_period');

        $entries = array();
        foreach ($slas as $row) {
            $row[2] = $row[2] & self::FLAG_ACTIVE;
            $entries[$row[0]] = sprintf(__('%s (%d hours - %s)'
                        /* Tokens are <name> (<#> hours - <Active|Disabled>) */),
                        self::getLocalById($row[0], 'name', $row[1]),
                        $row[3],
                        $row[2] ? __('Active') : __('Disabled'));
        }

        return $entries;
    }

    static function updateEstDueDate ($ticket) {
        //$thread = $ticket->getThread();
        $slaDeadline = SLA::calcEstDueDate($ticket);
        if( is_null($slaDeadline) || $ticket->getEstDueDate() == $slaDeadline) { //Minute granularity
            return;
        }

        $slaid = $ticket->getSLAId();
        $ticket->setEstDueDate($slaDeadline);
        //$ticket->save();
        $sla = SLA::lookup($slaid);

        // Update the config information
        $_config = new Config('sla.'.$ticket->getId());
        $_config->updateAll(array(
                    'time_elapsed' => 7
                )
        );

    }

    static function calcEstDueDate($ticket) {
        $slaid = $ticket->getSLAId();
        if ( ! $slaid)
            return NULL;
        $sla = SLA::lookup($slaid);
        $fromdt = new DateTime($ticket->getCreateDate());
        $bhid = $sla->getBusinessHoursId();
        if ( $bhid !== null && $bhid > 0 ) {
            return $sla->calcSLAWithBusinessHours($fromdt)->format('Y-m-d H:i:s');
        } else {
            return '2015-12-24 04:00:00';
        }
    }

    static function getSLAName($id) {
        $slas = static::getSLAs();
        return @$slas[$id];
    }

    static function getIdByName($name) {
        $row = static::objects()
            ->filter(array('name'=>$name))
            ->values_flat('id')
            ->first();

        return $row ? $row[0] : 0;
    }

    static function create($vars=false, &$errors=array()) {
        $sla = new static($vars);
        $sla->created = SqlFunction::NOW();
        return $sla;
    }

    static function __create($vars, &$errors=array()) {
        $sla = self::create($vars);
        $sla->save();
        return $sla;
    }
}

// Update the sla effective due date
Signal::connect('model.updated',
    array('SLA', 'updateEstDueDate'),
    'Ticket');
Signal::connect('model.created',
    array('SLA', 'updateEstDueDate'),
    'Ticket');
?>
